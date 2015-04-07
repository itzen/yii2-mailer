<?php
namespace itzen\mailer\commands;

use common\components\helpers\ShortcutProcessor;
use common\models\SystemEvent;
use common\models\User;
use itzen\mailer\models\EmailQueue;
use Yii;
use yii\console\Controller;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use yii\helpers\VarDumper;

class MailerController extends Controller
{
    const TYPE_AFTER_REGISTRATION = "AfterRegistration";
    const TYPE_BEFORE_SUBSCRIPTION_EXPIRE = "BeforeSubscriptionExpire";
    const TYPE_AFTER_CHANGE_ACCOUNTING_OFFICE = "AfterChangeAccountingOffice";
    const TYPE_AFTER_NEW_USER_INVITATION = "AfterNewUserInvitation";
    const TYPE_AFTER_USER_ACCEPTED_INVITATION = "AfterUserAcceptedInvitation";
    const TYPE_AFTER_AFFILIATE_USER_REGISTER = "AfterAffiliateUserRegister";
    const TYPE_AFTER_ACCEPT_FIRM = "AfterAcceptFirm";
    const TYPE_AFTER_ACCEPT_INVITATION_FROM_ACCOUNTING_OFFICE = "AfterAcceptInvitationFromAccountingOffice";
    const TYPE_AFTER_ACCEPT_INVITATION_FROM_CLIENT = "AfterAcceptInvitationFromClient";
    const TYPE_AFTER_DENIED_INVITATION = "AfterDeniedInvitation";

    /**
     * @inheritdoc
     */
    public $defaultAction = 'send';

    /**
     * Gets emails from queue and sends it
     * @param int $limit
     * @param string|null $category
     */
    public function actionSend($limit = 5, $category = null) {
        /** @var EmailQueue[] $emailQueue */
        $emailQueue = EmailQueue::find()
            ->where(['status' => EmailQueue::STATUS_NOT_SENT,])
            ->andWhere('attempt<max_attempts')
            ->orderBy(['priority' => SORT_ASC])
            ->limit($limit)
            ->all();
        $emailsCount = count($emailQueue);

        echo sprintf("Found %d messages.\n", $emailsCount);

        $sendCount = 0;
        foreach ($emailQueue as $email) {
            if ($email->sendEmail()) {
                $sendCount++;
            }
        }

        echo sprintf("Successfully sent %d messages.\n", $sendCount);

        SystemEvent::log('cron', 'SendEmails', [
            'emailsCount' => $emailsCount,
            'sendCount' => $sendCount,
            'created_at' => time()
        ]);
    }

    public function addEmailsToQueue($type) {

    }

    /**
     * Collects emails which should be send and adds it on queue for later sending
     * @param string $type
     */
    public function actionAddEmailsByType($type = self::TYPE_AFTER_REGISTRATION) {
        switch ($type) {
            case self::TYPE_AFTER_REGISTRATION:
                $days = Yii::$app->setting->get('Days.' . $type);
                $users = User::find()->where(['DATEDIFF(d, CreatedDateUtc, GETDATE())' => $days])->distinct()->all();
                echo sprintf("Found %d emails for type: %s\n", count($users), self::TYPE_AFTER_REGISTRATION);
                foreach ($users as $user) {
                    $model = $this->createMessage($user, $type);
                    if ($model === true) {
                        echo sprintf("Email to user %s added to queue in category %s.\n", $user->publicIdentity, self::TYPE_AFTER_REGISTRATION);
                        Yii::info(sprintf("Email to user %s added to queue in category %s.\n", $user->publicIdentity, self::TYPE_AFTER_REGISTRATION), 'mailer');

                    } else {
                        echo sprintf("Error while adding user %s to queue in category %s.\n", $user->publicIdentity, count($users), self::TYPE_AFTER_REGISTRATION);
                        Yii::error(sprintf("Error while adding user %s to queue in category %s.\n Errors: %s", $user->publicIdentity, self::TYPE_AFTER_REGISTRATION, print_r($model, true)), 'mailer');
                    }
                }

                //
                //  SELECT * FROM [Emsi.InvoiceTest].[dbo].[User]  WHERE
                //  DATEDIFF(d, CreatedDateUtc, GETDATE()) = 7
                break;

            case self::TYPE_BEFORE_SUBSCRIPTION_EXPIRE:
                $days = Yii::$app->setting->get('Days.' . $type);
                $users = User::find()->where(['DATEDIFF(d, GETDATE(), companySubscription.expireDate)' => $days])
                    ->joinWith('ownedFirms.lastSubscription')->distinct()->all();
                echo sprintf("Found %d emails for type: %s\n", count($users), self::TYPE_AFTER_REGISTRATION);
                foreach ($users as $user) {
                    $model = $this->createMessage($user, $type);
                    if ($model === true) {
                        echo sprintf("Email to user %s added to queue in category %s.\n", $user->publicIdentity, self::TYPE_AFTER_REGISTRATION);
                        Yii::info(sprintf("Email to user %s added to queue in category %s.\n", $user->publicIdentity, self::TYPE_AFTER_REGISTRATION), 'mailer');

                    } else {
                        echo sprintf("Error while adding user %s to queue in category %s.\n", $user->publicIdentity, count($users), self::TYPE_AFTER_REGISTRATION);
                        Yii::error(sprintf("Error while adding user %s to queue in category %s.\n Errors: %s", $user->publicIdentity, self::TYPE_AFTER_REGISTRATION, print_r($model, true)), 'mailer');
                    }
                }
                //                SELECT DATEDIFF(d, GETDATE(), companySubscription.expireDate), companySubscription.expireDate, GETDATE(),*
                //                 FROM [Emsi.Invoice].[dbo].[User]
                //                 JOIN [Emsi.Invoice].[dbo].[Firm] ON [Firm].[User_ID]=[User].[ID]
                //                 JOIN [Emsi.Invoice].[dbo].[companySubscription]
                //                    ON Firm.lastSubscriptionId=companySubscription.id
                //                 WHERE
                //                 DATEDIFF(d, GETDATE(), companySubscription.expireDate) = 165;
                break;
        }
    }

    /**
     * @param User $user
     * @param string $type
     * @return bool
     */
    public static function createMessage($user, $type, $params = []) {
        $message = new EmailQueue();

        $message->user_id = $user->ID;
        $message->category = $type;
        $message->from_name = Yii::$app->setting->get('adminName');
        $message->from_address = Yii::$app->setting->get('adminEmail');
        $message->to_name = $user->publicIdentity;
        $message->to_address = $user->Email;
        $message->subject = Yii::$app->setting->get('email.subject.' . $type);

        $content = Yii::$app->setting->get('email.content.' . $type);

        $params = ArrayHelper::merge(
            $params, ['user' => $user]
        );

        $content = ShortcutProcessor::replaceShortcuts($content, $params);
        $message->body = $content;

        $message->max_attempts = 3;
        $message->attempt = 0;
        $message->priority = 5;
        $message->status = EmailQueue::STATUS_NOT_SENT;

        if ($message->save()) {
            return true;
        } else {
            return $message->getErrors();
        }
    }
}