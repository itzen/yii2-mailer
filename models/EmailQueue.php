<?php

namespace itzen\mailer\models;

use common\models\User;
use itzen\setting\models\Setting;
use kartik\grid\GridView;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;
use itzen\mailer\behaviors\TranslateAttributeBehavior;

/**
 * This is the model class for table "{{%email_queue}}".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $category
 * @property string $from_name
 * @property string $from_address
 * @property string $to_name
 * @property string $to_address
 * @property string $subject
 * @property string $body
 * @property string $alternative_body
 * @property string $headers
 * @property string $attachments
 * @property integer $max_attempts
 * @property integer $attempt
 * @property integer $priority
 * @property integer $status
 * @property string $sent_time
 * @property string $create_time
 * @property string $update_time
 *
 * @property User $user
 */
class EmailQueue extends \yii\db\ActiveRecord
{
    const STATUS_NOT_SENT = 0;
    const STATUS_SENT = 1;
    const STATUS_FAILED = 2;


    public $expandable = GridView::ROW_COLLAPSED;

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%email_queue}}';
    }


    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'create_time',
                'updatedAtAttribute' => 'update_time',
                'value' => function () {
                    return date('Y-m-d H:i:s');
                },
            ],
            [
                'class' => TranslateAttributeBehavior::className()
            ]
        ];
    }


    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['user_id', 'max_attempts', 'attempt', 'priority', 'status'], 'integer'],
            [
                [
                    'category',
                    'from_name',
                    'from_address',
                    'to_name',
                    'to_address',
                    'subject',
                    'body',
                    'alternative_body',
                    'headers',
                    'attachments'
                ],
                'string'
            ],
            [
                [
                    'user_id',
                    'category',
                    'from_name',
                    'from_address',
                    'to_name',
                    'alternative_body',
                    'headers',
                    'attachments',
                    'sent_time',
                    'update_time'
                ],
                'default',
                'value' => null
            ],
            [['to_address', 'subject', 'body', 'status'], 'required'],
            [['to_address', 'from_address'], 'email'],
            [
                ['from_name', 'from_address'],
                'required',
                'when' => function ($model) {
                    return $model->from_address !== null || $model->from_name !== null;
                },
                'whenClient' => "function (attribute, value) {
                    return $('#emailqueue-from_address').val() !== '' || $('#emailqueue-from_name').val() !== '';
                }",
                'message' => \Yii::t('common', 'Fill both attributes (from name and from address) or none of them.')
            ],
            [['sent_time', 'update_time'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => Yii::t('common', 'ID'),
            'user_id' => Yii::t('common', 'User ID'),
            'category' => Yii::t('common', 'Category'),
            'from_name' => Yii::t('common', 'From Name'),
            'from_address' => Yii::t('common', 'From Address'),
            'to_name' => Yii::t('common', 'To Name'),
            'to_address' => Yii::t('common', 'To Address'),
            'subject' => Yii::t('common', 'Subject'),
            'body' => Yii::t('common', 'Body'),
            'alternative_body' => Yii::t('common', 'Alternative Body'),
            'headers' => Yii::t('common', 'Headers'),
            'attachments' => Yii::t('common', 'Attachments'),
            'max_attempts' => Yii::t('common', 'Max Attempts'),
            'attempt' => Yii::t('common', 'Attempt'),
            'priority' => Yii::t('common', 'Priority'),
            'status' => Yii::t('common', 'Status'),
            'sent_time' => Yii::t('common', 'Send Time'),
            'create_time' => Yii::t('common', 'Create Time'),
            'update_time' => Yii::t('common', 'Update Time'),
            'statusName' => Yii::t("common","Status"),
            'translated_category' => Yii::t("common", "Category")
        ];
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser() {
        return $this->hasOne(User::className(), ['ID' => 'user_id']);
    }

    /**
     * @return array()
     */
    public function getAvailableUsers() {
        return ArrayHelper::map(User::find()->asArray()->all(), 'ID', 'Email');
    }

    public function getStatusName() {

        if($this->status === 0) {
            return Yii::t("backend","Not sent");
        }
        else if($this->status === 1) {
            return Yii::t("backend","Sent");
        }
        else if($this->status === 2) {
            return Yii::t("backend","Failed to sent");
        }

    }

    public static function getAvailableStatusNames() {
        return [0 => Yii::t("backend","Not sent"), 1 =>  Yii::t("backend","Sent"), 2 => Yii::t("backend","Failed to sent")];
    }

    /**
     * @return boolean
     */
    public function sendEmail() {
        $sent = false;
        $this->attempt++;
        try {




            $message = Yii::$app->mailer->compose(
                ['html' => '@common/mail/standard'],
                [
                    'content' => $this->body,
                    'category' => $this->category
                ]
            )
                ->setSubject($this->subject)
                ->setTo([$this->to_address => $this->to_name]);
               // ->setHtmlBody($this->body);

            if ($this->alternative_body !== null) {
                $message->setTextBody($this->alternative_body);
            }

            if ($this->from_address !== null && $this->from_name !== null) {
                $message->setFrom([$this->from_address => $this->from_name]);
            }

            if ($message->send()) {
                $sent = true;
            }
        }
        catch (\Exception $e) {
            $sent = false;
            Yii::error($e->getMessage(), 'mailer');
        }

        if ($sent) {
            $this->sent_time = date('Y-m-d H:i:s');
            $this->status = self::STATUS_SENT;
            Yii::info(sprintf("Message to %s %s sent successfully (category:%s).\n", $this->to_name, $this->to_address, $this->category), 'mailer');
            $this->save(false);
            return true;
        } elseif ($this->attempt >= $this->max_attempts) {
            $this->status = self::STATUS_FAILED;
            Yii::warning(sprintf("Sending message to %s %s failed and further attempts won't be made (category:%s).\n", $this->to_name, $this->to_address, $this->category), 'mailer');
            $this->save(false);
            return false;
        } else {
            $this->status = self::STATUS_NOT_SENT;
            Yii::warning(sprintf("Sending message to %s %s with no success. Next attempt will be made (category:%s).\n", $this->to_name, $this->to_address, $this->category), 'mailer');
            $this->save(false);
            return false;
        }
    }

}
