<?php

/* @var $this yii\web\View */
/* @var $name string */
/* @var $message string */
/* @var $exception Exception */

use yii\helpers\Html;
use app\assets\MainAsset;

MainAsset::register($this);

$this->title = $name;
?>
<div class="site-error">


    <h1><?= Html::encode("Ошибка") ?></h1>

    <div class="alert alert-danger">
        <?= nl2br(Html::encode($message)) ?>
    </div>

    <p>
        Произошла ошибка во время запроса!
    </p>
</div>
