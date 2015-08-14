<?php

namespace itzen\mailer;

use Yii;
use itzen\setting\Setting;
use yii\helpers\ArrayHelper;

class Mailer extends \yii\swiftmailer\Mailer
{
    /**
     * Overwrite mailer config using values from itzen/yii2-setting extension
     */
    public function init() {

        $this->messageConfig = ArrayHelper::merge($this->messageConfig, [
                'charset' => 'UTF-8',
                'from'    => [Yii::$app->setting->get('adminEmail') => Yii::$app->setting->get('adminName')]
            ]
        );

        $transport = [
            'class'      => 'Swift_SmtpTransport',
            'host'       => Yii::$app->setting->get('smtpHost'),
            'username'   => Yii::$app->setting->get('smtpUser'),
            'password'   => Yii::$app->setting->get('smtpPassword'),
            'port'       => Yii::$app->setting->get('smtpPort'),
            'encryption' => Yii::$app->setting->get('smtpEncryption'),
        ];

        $this->setTransport($transport);

        $this->htmlLayout = Yii::$app->setting->get('htmlLayout');
        $this->textLayout = Yii::$app->setting->get('textLayout');

    }
}
