<?php

namespace itzen\mailer;

use Yii;
use yii\helpers\ArrayHelper;

class Mailer extends \yii\swiftmailer\Mailer
{
    public $transport;

    /**
     * Overwrite mailer config using values from itzen/yii2-setting extension
     */
    public function init() {

        $this->messageConfig = ArrayHelper::merge($this->messageConfig, [
            'charset' => 'UTF-8',
            'from' => [Yii::$app->setting->get('adminEmail') => Yii::$app->setting->get('adminName')]
        ]);

        $this->transport = [
            'class' => 'Swift_SmtpTransport',
            'host' => Yii::$app->setting->get('smtpHost'),
            'username' => Yii::$app->setting->get('smtpUser'),
            'password' => Yii::$app->setting->get('smtpPassword'),
            'port' => Yii::$app->setting->get('smtpPort'),
            'encryption' => Yii::$app->setting->get('smtpEncryption'),
        ];

        $this->setTransport($this->transport);

        $this->htmlLayout = Yii::$app->setting->get('htmlLayout');
        $this->textLayout = Yii::$app->setting->get('textLayout');

    }


    /**
     * zapisywanie w wysłanych (poczta home.pl)
     * @param \yii\swiftmailer\Message $msg
     * @return bool
     * @throws \ErrorException
     */
    public function appendMessageToSent(\yii\swiftmailer\Message $msg) {
        try {
            $message = $msg->toString();
            $dataToConnect = '{' . $this->transport['host'] . ':143/novalidate-cert}Sent';
            $stream = imap_open($dataToConnect, $this->transport['username'], $this->transport['password']);
            $result = imap_append($stream, $dataToConnect, $message . "\r\n");
            //$mailboxes = imap_getmailboxes($stream, $dataToConnect, '*');
            //$errors = imap_errors();
            //$check = imap_check($stream);
            imap_close($stream);
            return $result;
        }
        catch (\ErrorException $ex) {
            throw new \ErrorException ($ex->getMessage());
        }

    }

}




