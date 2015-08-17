<?php

use yii\helpers\Html;
use kartik\detail\DetailView;
use kartik\datecontrol\DateControl;

/**
 * @var yii\web\View $this
 * @var itzen\mailer\models\EmailQueue $model
 */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('common', 'Email Queues'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="email-queue-view">
    <?= DetailView::widget([
        'model' => $model,
        'condensed' => true,
        'hover' => true,
        'mode' => (Yii::$app->request->get('edit') == 't' || $model->hasErrors()) ? DetailView::MODE_EDIT : DetailView::MODE_VIEW,
        'i18n' => Yii::$app->i18n->translations['*'],
        'panel' => [
            'heading' => $this->title,
            'type' => DetailView::TYPE_INFO,
        ],
        'attributes' => [
            //'id',
            [
                'attribute' => 'user_id',
                'value' => $model->user !== null ? $model->user->Email : '',
                'type' => DetailView::INPUT_WIDGET,
                'widgetOptions' => [
                    'class' => DetailView::INPUT_SELECT2,
                    'data' => $model->availableUsers,
                ]
            ],
            'category',
            'from_name',
            'from_address',
            'to_name',
            'to_address',
            'subject',
            'body:html',
            //'alternative_body:ntext',
            //'headers:ntext',
            //'attachments:ntext',
            'max_attempts',
            'attempt',
            //'priority',
            [
                'attribute' => 'statusName',
            ],
            [
                'attribute' => 'sent_time',
                'format' => [
                    'datetime',
                    (isset(Yii::$app->modules['datecontrol']['displaySettings']['datetime'])) ? Yii::$app->modules['datecontrol']['displaySettings']['datetime'] : 'd-m-Y H:i:s A'
                ],
                'type' => DetailView::INPUT_WIDGET,
                'widgetOptions' => [
                    'class' => DateControl::classname(),
                    'type' => DateControl::FORMAT_DATETIME
                ]
            ],
            [
                'attribute' => 'create_time',
                'format' => [
                    'datetime',
                    (isset(Yii::$app->modules['datecontrol']['displaySettings']['datetime'])) ? Yii::$app->modules['datecontrol']['displaySettings']['datetime'] : 'd-m-Y H:i:s A'
                ],
                'type' => DetailView::INPUT_WIDGET,
                'widgetOptions' => [
                    'class' => DateControl::classname(),
                    'type' => DateControl::FORMAT_DATETIME
                ]
            ],
            [
                'attribute' => 'update_time',
                'format' => [
                    'datetime',
                    (isset(Yii::$app->modules['datecontrol']['displaySettings']['datetime'])) ? Yii::$app->modules['datecontrol']['displaySettings']['datetime'] : 'd-m-Y H:i:s A'
                ],
                'type' => DetailView::INPUT_WIDGET,
                'widgetOptions' => [
                    'class' => DateControl::classname(),
                    'type' => DateControl::FORMAT_DATETIME
                ]
            ],
        ],
        'deleteOptions' => [
            'url' => ['delete', 'id' => $model->id],
            'data' => [
                'confirm' => Yii::t('common', 'Are you sure you want to delete this item?'),
                'method' => 'post',
            ],
        ],
        'enableEditMode' => true,
    ]) ?>

</div>
