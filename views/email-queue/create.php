<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var itzen\mailer\models\EmailQueue $model
 */

$this->title = Yii::t('common', 'Create');
$this->params['breadcrumbs'][] = ['label' => Yii::t('common', 'Email Queues'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="email-queue-create">
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
