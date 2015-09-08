<?php
namespace itzen\mailer\commands;

use common\components\helpers\ShortcutProcessor;
use common\models\Firm;
use common\models\invitation\Invitation;
use common\models\invitation\InvitationAccountOffice;
use common\models\invitation\InvitationAccountOfficeStatus;
use common\models\invitation\InvitationStatus;
use common\models\NotAcceptedFirms;
use common\models\SystemEvent;
use common\models\User;
use itzen\mailer\models\EmailQueue;
use Yii;
use yii\console\Controller;
use yii\helpers\ArrayHelper;

class MailerController extends Controller
{
    const TYPE_TEXT = "Text";

    const TYPE_AFTER_REGISTRATION = "AfterRegistration";
    const TYPE_BEFORE_SUBSCRIPTION_EXPIRE = "BeforeSubscriptionExpire";

    const TYPE_FROM_INVITATION_TABLE = "FromInvitationTable";
    const TYPE_FROM_INVITATION_ACCOUNTING_OFFICE_TABLE = "FromInvitationAccountingOfficeTable";

    const TYPE_AFTER_CHANGE_ACCOUNTING_OFFICE = "AfterChangeAccountingOffice";
    const TYPE_AFTER_NEW_USER_REGISTER = "AfterNewUserRegister";
    const USER_WAITING_FOR_ACCEPTATION = "UserWaitingForAcceptation";
    const TYPE_AFTER_CANCEL_ACCEPT_MANUAL_PAYMENT = "AfterCancelAcceptManualPayment";

    const TYPE_AFTER_NEW_USER_INVITATION = "AfterNewUserInvitation";
    const TYPE_AFTER_USER_ACCEPTED_INVITATION = "AfterUserAcceptedInvitation";
    const TYPE_AFTER_AFFILIATE_USER_REGISTER = "AfterAffiliateUserRegister";
    const TYPE_AFTER_ACCEPT_FIRM = "AfterAcceptFirm";
    const TYPE_AFTER_ACCEPT_INVITATION_FROM_ACCOUNTING_OFFICE = "AfterAcceptInvitationFromAccountingOffice";
    const TYPE_AFTER_ACCEPT_INVITATION_FROM_CLIENT = "AfterAcceptInvitationFromClient";
    const TYPE_AFTER_DENIED_INVITATION = "AfterDeniedInvitation";
    const TYPE_BONUS_POINTS_ADDED = "BonusPointsAdded";
    const TYPE_AFTER_ACCEPT_MANUAL_PAYMENT = "AfterAcceptManualPayment";
    const TYPE_AFTER_NEW_FIRM_ACCEPTED = "AfterNewFirmAccepted";
    const TYPE_AFTER_NEW_FIRM_CREATED = "AfterNewFirmCreated";
    const TYPE_MANUAL_PAYMENT = "ManualPayment";
    const TYPE_ONLINE_PAYMENT = "OnlinePayment";

    const TYPE_SPECIAL_OFFER = "SpecialOffer";
    const TYPE_SUBSCRIPTION_LOW_NOTIFICATION = "SubscriptionLowNotification";
    const TYPE_MAX_FIRM_LIMIT_REACHED = "MaxFirmLimitReached";
    const TYPE_PASSWORD_RESET = "PasswordReset";
    const TYPE_CONTACT = "Contact";


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
        $emailQueue = EmailQueue::find()->where(['status' => EmailQueue::STATUS_NOT_SENT,])->andWhere('attempt<max_attempts')->orderBy(['priority' => SORT_ASC])->limit($limit)->all();
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
                $days = Yii::$app->setting->get('Days.' . self::TYPE_AFTER_REGISTRATION);
                $users = User::find()->where(['DATEDIFF(d, CreatedDateUtc, GETDATE())' => $days])->distinct()->all();
                echo sprintf("Found %d emails for type: %s\n", count($users), self::TYPE_AFTER_REGISTRATION);
                foreach ($users as $user) {
                    $model = $this->createMessage($user, self::TYPE_AFTER_REGISTRATION);
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
                $days = Yii::$app->setting->get('Days.' . self::TYPE_BEFORE_SUBSCRIPTION_EXPIRE);
                //                $users = User::find()->where(['DATEDIFF(d, GETDATE(), companySubscription.expireDate)' => $days])
                //                    ->joinWith('ownedFirms.lastSubscription')->distinct()->all();


                /** @var Firm[] $firms */
                $firms = Firm::findBySql('
                               SELECT Firm.*, cs.*
                               FROM Firm
                               JOIN companySubscription cs ON (cs.id = lastSubscriptionId)
                               WHERE DATEDIFF(d, GETDATE(), cs.expireDate) = :days;')->params(['days' => $days])->all();

                echo sprintf("Found %d emails for type: %s\n", count($firms), self::TYPE_BEFORE_SUBSCRIPTION_EXPIRE);
                foreach ($firms as $firm) {
                    $model = $this->createMessage($firm->user, self::TYPE_BEFORE_SUBSCRIPTION_EXPIRE, [
                        'subscriptionInfo' => \Yii::t('common', 'Subscription will expire {days, plural, =0{tomorrow} =1{in less than one day} other{in less than # days}}', ['days' => $days]),
                        'firm' => $firm,
                        'lastSubscription' => $firm->lastSubscription,
                    ]);
                    if ($model === true) {
                        echo sprintf("Email to user %s added to queue in category %s.\n", $firm->user->publicIdentity, self::TYPE_BEFORE_SUBSCRIPTION_EXPIRE);
                        Yii::info(sprintf("Email to user %s added to queue in category %s.\n", $firm->user->publicIdentity, self::TYPE_BEFORE_SUBSCRIPTION_EXPIRE), 'mailer');

                    } else {
                        echo sprintf("Error while adding user %s to queue in category %s.\n", $firm->user->publicIdentity, count($firm), self::TYPE_BEFORE_SUBSCRIPTION_EXPIRE);
                        Yii::error(sprintf("Error while adding user %s to queue in category %s.\n Errors: %s", $firm->user->publicIdentity, self::TYPE_BEFORE_SUBSCRIPTION_EXPIRE, print_r($model, true)), 'mailer');
                    }
                }


                /** @var Firm[] $firms */
                $firms = Firm::findBySql('
                      SELECT Firm.*
                      FROM Firm
                      JOIN companySubscription cs ON (cs.id = lastSubscriptionId)
                      JOIN subscription s ON (s.id = subscriptionId)
                      WHERE notificationSent = 0
                      AND (
                          cs.invoiceSend < s.invoiceSend * 0.1
                          OR cs.invoiceReceived < s.invoiceReceived * 0.1
                          OR cs.invoiceToAc < s.invoiceToAc * 0.1)')->all();

                echo sprintf("Found %d emails for type: %s\n", count($firms), self::TYPE_SUBSCRIPTION_LOW_NOTIFICATION);

                foreach ($firms as $firm) {
                    if ($firm->IsAccountingOffice) {
                        $subscriptionInfo = \Yii::t('common', 'Remaining invoice to send: {invoiceSend}, Remaining invoice to receive {invoiceReceived}.', [
                            'invoiceSend' => $firm->lastSubscription->invoiceSend,
                            'invoiceReceived' => $firm->lastSubscription->invoiceReceived,

                        ]);
                    } else {
                        $subscriptionInfo = \Yii::t('common', 'Remaining invoice to accounting office: {invoiceToAc}.', [
                            'invoiceToAc' => $firm->lastSubscription->invoiceToAc,
                        ]);
                    }

                    $result = $this->createMessage($firm->user, self::TYPE_SUBSCRIPTION_LOW_NOTIFICATION, [
                        'subscriptionInfo' => $subscriptionInfo,
                        'firm' => $firm,
                        'lastSubscription' => $firm->lastSubscription,
                    ]);

                    if ($result) {
                        echo sprintf("Email to user %s added to queue in category %s.\n", $firm->user->publicIdentity, self::TYPE_SUBSCRIPTION_LOW_NOTIFICATION);
                        Yii::info(sprintf("Email to user %s added to queue in category %s.\n", $firm->user->publicIdentity, self::TYPE_SUBSCRIPTION_LOW_NOTIFICATION), 'mailer');

                        $subscription = $firm->lastSubscription;
                        $subscription->notificationSent = 1;
                        $subscription->save(false);
                    }

                }


            break;

            case self::TYPE_FROM_INVITATION_TABLE:

                /** @var Invitation[] $invitationsToSend */
                $invitationsToSend = Invitation::find()->where(['Status_ID' => InvitationStatus::STATUS_AWAITING])->all();

                echo sprintf("Found %d emails for type: %s\n", count($invitationsToSend), self::TYPE_FROM_INVITATION_TABLE);

                foreach ($invitationsToSend as $invitation) {
                    $invID = $invitation->Type_ID;
                    if (($invID == 4 || $invID == 5) && $invitation->userAlreadyExist()) {
                        $invitation->Status_ID = InvitationStatus::STATUS_ALREADY_EXIST;
                        $invitation->save(false);
                        continue;
                    }

                    $user = new User();
                    $user->Email = $invitation->ReceiverEmail;
                    $invitationID = $invitation->ID;
                    $fromFirm = $invitation->senderFirm;

                    $result = self::createMessage($user, 'InvitationType_' . $invitation->Type_ID, [
                        'fromFirm' => $fromFirm,
                        'fromUser' => $fromFirm->user,
                        'toFirm' => $invitation->receiverFirm,
                        'toUser' => $invitation->receiverFirm != null ? $invitation->receiverFirm->user : null,
                        'toFirmName' => $invitation->ReceiverName,
                        'toFirmNip' => $invitation->ReceiverNip,
                        'fid' => $fromFirm->ID,
                        'iid' => $invitationID,
                        'blockUrl' => Yii::$app->urlManagerFrontend->createAbsoluteUrl([
                            '/affiliates/affiliates/block',
                            'id' => $invitationID,
                            'token' => $invitation->hash
                        ]),
                    ]);

                    if ($result === true) {
                        echo sprintf("Email to user %s added to queue in category %s.\n", $user->publicIdentity, 'InvitationType_' . $invitation->Type_ID);
                        Yii::info(sprintf("Email to user %s added to queue in category %s.\n", $user->publicIdentity, 'InvitationType_' . $invitation->Type_ID), 'mailer');

                        $invitation->Status_ID = InvitationStatus::STATUS_SENT;
                        $invitation->save(false);
                    }
                    $invitation->deactivateOtherInvitations($fromFirm, $invitation->ReceiverEmail);


                }
            break;

            case self::TYPE_FROM_INVITATION_ACCOUNTING_OFFICE_TABLE:
                /** @var InvitationAccountOffice[] $invitationsToSend */
                $invitationsToSend = InvitationAccountOffice::find()->where(['Status_ID' => InvitationAccountOfficeStatus::STATUS_ACTIVE])->all();

                echo sprintf("Found %d emails for type: %s\n", count($invitationsToSend), self::TYPE_FROM_INVITATION_ACCOUNTING_OFFICE_TABLE);


                foreach ($invitationsToSend as $invitation) {

                    $user = new User();
                    $user->Email = $invitation->ReceiverEmail;
                    $invitationID = $invitation->ID;
                    $fromFirm = $invitation->senderFirm;


                    $result = MailerController::createMessage($user, self::TYPE_AFTER_CHANGE_ACCOUNTING_OFFICE, [
                        'date' => \Yii::t('common', '{date, date, long}', ['date' => strtotime($invitation->AccountingOfficeStartDate)]),
                        'fromFirm' => $fromFirm,
                        'toFirm' => $invitation->receiverFirm,
                    ]);


                    if ($result === true) {
                        echo sprintf("Email to user %s added to queue in category %s.\n", $user->publicIdentity, self::TYPE_AFTER_CHANGE_ACCOUNTING_OFFICE);
                        Yii::info(sprintf("Email to user %s added to queue in category %s.\n", $user->publicIdentity, self::TYPE_AFTER_CHANGE_ACCOUNTING_OFFICE), 'mailer');

                        $invitation->Status_ID = InvitationAccountOfficeStatus::STATUS_SENT;
                        $invitation->save(false);
                    }

                }

            break;
            case self::USER_WAITING_FOR_ACCEPTATION:

                $notAcceptedFirms = NotAcceptedFirms::find()->joinWith(['user' => function($query){
                    return $query->where([
                        '{{%User%}}.status' => User::STATUS_ACTIVE
                    ]);
                }])->where([
                    'NotificationSent' => 0
                ])->all();

                foreach ($notAcceptedFirms as $notAcceptedFirm) {

                    $adminUser = new \common\models\User();
                    $adminUser->Email = Yii::$app->params['adminEmail'];
                    $result = MailerController::createMessage($adminUser, MailerController::USER_WAITING_FOR_ACCEPTATION, [
                        'newUser' => $notAcceptedFirm->user,
                        'userType' => ($notAcceptedFirm->user!== null && $notAcceptedFirm->user->status == 1) ? \Yii::t('common', 'User account is confirmed.') : \Yii::t('common', 'User account is not confirmed.'),
                        'firm' => $notAcceptedFirm
                    ]);


                    if ($result) {
                        $notAcceptedFirm->NotificationSent = 1;
                        $notAcceptedFirm->save(false, ['NotificationSent']);

                        echo sprintf("Email to user %s added to queue in category %s.\n", $notAcceptedFirm->user->publicIdentity, self::USER_WAITING_FOR_ACCEPTATION);
                        Yii::info(sprintf("Email to user %s added to queue in category %s.\n", $notAcceptedFirm->user->publicIdentity, self::USER_WAITING_FOR_ACCEPTATION), 'mailer');

                    }

                }


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

        $params = ArrayHelper::merge($params, ['user' => $user]);

        $content = ShortcutProcessor::replaceShortcuts($content, $params);
        $message->body = $content;

        $message->max_attempts = 3;
        $message->attempt = 0;
        $message->priority = 5;
        $message->status = EmailQueue::STATUS_NOT_SENT;

        if ($message->save(false)) {
            return true;
        } else {
            return false;
        }
    }
}
