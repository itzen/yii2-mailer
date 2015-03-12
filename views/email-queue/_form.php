<?php

use yii\helpers\Html;
use kartik\widgets\ActiveForm;
use kartik\builder\Form;
use kartik\datecontrol\DateControl;

/**
 * @var yii\web\View $this
 * @var itzen\mailer\models\EmailQueue $model
 * @var yii\widgets\ActiveForm $form
 */
?>

<div class="email-queue-form">

    <?php $form = ActiveForm::begin(['type' => ActiveForm::TYPE_VERTICAL]);
    echo $form->errorSummary($model);
    echo Form::widget([
        'model' => $model,
        'form' => $form,
        'columns' => 1,
        'attributes' => [
            'user_id' => [
                'type' => Form::INPUT_DROPDOWN_LIST,
                'items' => $model->availableUsers,
                'options' => ['placeholder' => Yii::t('common', 'Choose user...')]
            ],
            'max_attempts' => [
                'type' => Form::INPUT_TEXT,
                'options' => ['placeholder' => Yii::t('common', 'Enter Max Attempts...')]
            ],
            'attempt' => [
                'type' => Form::INPUT_TEXT,
                'options' => ['placeholder' => Yii::t('common', 'Enter Attempt...')]
            ],
            'priority' => [
                'type' => Form::INPUT_TEXT,
                'options' => ['placeholder' => Yii::t('common', 'Enter Priority...')]
            ],
            'status' => [
                'type' => Form::INPUT_TEXT,
                'options' => ['placeholder' => Yii::t('common', 'Enter Status...')]
            ],
            'category' => [
                'type' => Form::INPUT_TEXT,
                'options' => ['placeholder' => Yii::t('common', 'Enter Category...')]
            ],
            'from_name' => [
                'type' => Form::INPUT_TEXT,
                'options' => ['placeholder' => Yii::t('common', 'Enter From Name...')]
            ],
            'from_address' => [
                'type' => Form::INPUT_TEXT,
                'options' => ['placeholder' => Yii::t('common', 'Enter From Address...')]
            ],
            'to_name' => [
                'type' => Form::INPUT_TEXT,
                'options' => ['placeholder' => Yii::t('common', 'Enter To Name...')]
            ],
            'to_address' => [
                'type' => Form::INPUT_TEXT,
                'options' => ['placeholder' => Yii::t('common', 'Enter To Address...')]
            ],
            'subject' => [
                'type' => Form::INPUT_TEXT,
                'options' => ['placeholder' => Yii::t('common', 'Enter Subject...')]
            ],
            'body' => [
                'type' => Form::INPUT_TEXTAREA,
                'options' => ['placeholder' => Yii::t('common', 'Enter Body...'), 'rows' => 6]
            ],
            'alternative_body' => [
                'type' => Form::INPUT_TEXTAREA,
                'options' => [
                    'placeholder' => Yii::t('common', 'Enter Alternative Body...'),
                    'rows' => 6
                ]
            ],
            'headers' => [
                'type' => Form::INPUT_TEXTAREA,
                'options' => ['placeholder' => Yii::t('common', 'Enter Headers...'), 'rows' => 6]
            ],
            'attachments' => [
                'type' => Form::INPUT_TEXTAREA,
                'options' => ['placeholder' => Yii::t('common', 'Enter Attachments...'), 'rows' => 6]
            ],
            'sent_time' => [
                'type' => Form::INPUT_WIDGET,
                'widgetClass' => DateControl::classname(),
                'options' => ['type' => DateControl::FORMAT_DATETIME]
            ],
        ]
    ]);
    echo Html::submitButton($model->isNewRecord ? Yii::t('common', 'Create') : Yii::t('common', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']);
    ActiveForm::end(); ?>

</div>
