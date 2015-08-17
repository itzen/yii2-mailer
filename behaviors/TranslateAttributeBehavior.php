<?php

/**
 * Behavior that allows to use shortcuts in content. Shortcuts are special strings ({{"class":"widget_class_name", "param1":"value1", "anotherParam":"Some value"}}) which are replaced by widgets.
 *
 * @author Paweł Kania
 */

namespace itzen\mailer\behaviors;

use Yii;
use yii\base\Behavior;
use yii\base\Exception;
use yii\base\UnknownPropertyException;
use yii\db\ActiveRecord;

class TranslateAttributeBehavior extends Behavior
{

    public function __get($name) {
        if ($this->canGetProperty($name)) {
            $name = str_replace('translated_', '', $name, $count);
            return \Yii::t('common', $this->owner->$name);
        }
    }

    public function canGetProperty($name, $checkVars = true) {
        $count = 0;
        $name = str_replace('translated_', '', $name, $count);
        return $count && isset($this->owner->$name) && $this->owner->$name !== null;
    }

}
