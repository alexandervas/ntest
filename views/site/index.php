<?php
use yii\helpers\Html;
/* @var $this yii\web\View */

$this->title = 'My Yii Application';
?>
<div class="site-index">
    <?php if(!\Yii::$app->user->isGuest):?>
    <div class="jumbotron">
        <h1>«Добрый день, <?= Yii::$app->user->identity->username;?>»</h1>
    </div>
    <div class="body-content">
        <div class="row">
            <?php echo Html::beginForm(['/site/logout'], 'post');?>

            <div class="col-md-3 col-md-offset-5">
                <?php echo Html::submitButton('Выход',['class' => 'btn btn-lg btn-success']);?>
            </div>
            <?php echo Html::endForm();?>

        </div>
    <?php endif;?>
    </div>
</div>
