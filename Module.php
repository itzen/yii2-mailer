<?php

namespace itzen\mailer;
use Yii;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'itzen\mailer\controllers';

    public function init()
    {
        parent::init();

        $this->setViewPath('@itzen/mailer/views');
    }


}
