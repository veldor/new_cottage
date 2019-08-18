<?php

/* @var $this \yii\web\View */
/* @var $content string */

use app\widgets\Alert;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\helpers\Html;
use yii\widgets\Breadcrumbs;

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<div class="wrap">
    <?php
    NavBar::begin([
        'options' => [
            'class' => 'navbar-inverse navbar-fixed-top',
        ],
    ]);
    try {
        echo Nav::widget([
            'options' => ['class' => 'navbar-nav navbar-right'],
            'encodeLabels' => false,
            'items' => [
                ['label' => 'Главная', 'url' => ['/site/index'], 'options' => ['class' => 'visible-lg']],
                ['label' => Html::tag('span', '', ['class'=>'glyphicon glyphicon-home']), 'url' => ['/site/index'], 'options' => ['class' => 'hidden-lg hidden-xs', 'title' => 'Главная']],
                ['label' => 'Статистика', 'url' => ['/count/index'], 'options' => ['class' => 'visible-lg']],
                ['label' => 'Выборки', 'url' => ['/search'], 'options' => ['class' => 'visible-lg']],
                ['label' => 'Заполнение', 'url' => ['/filling'], 'options' => ['class' => 'visible-lg']],
                ['label' => 'Тарифы', 'url' => ['/tariffs/index'], 'options' => ['class' => 'visible-lg']],
                ['label' => 'Управление', 'url' => ['/management/index'], 'options' => ['class' => 'visible-lg']],
                ['label' => 'С', 'url' => ['/count/index'], 'options' => ['class' => 'hidden-lg hidden-xs', 'title' => 'Статистика']],
                ['label' => 'В', 'url' => ['/search'], 'options' => ['class' => 'hidden-lg hidden-xs', 'title' => 'Выборки']],
                ['label' => 'З', 'url' => ['/filling'], 'options' => ['class' => 'hidden-lg hidden-xs', 'title' => 'Действия']],
                ['label' => 'Т', 'url' => ['/tariffs/index'], 'options' => ['class' => 'hidden-lg hidden-xs', 'title' => 'Тарифы']],
                Yii::$app->user->can('manage') ? ['label' => Html::tag('span', '', ['class'=>'glyphicon glyphicon-cog']), 'url' => ['/management/index'], 'options' => ['class' => 'hidden-lg hidden-xs', 'title' => 'Управление']] : '',
                ['label' => 'Статистика', 'url' => ['/count/index'], 'options' => ['class' => 'visible-xs']],
                ['label' => 'Выборки', 'url' => ['/search'], 'options' => ['class' => 'visible-xs']],
                ['label' => 'Действия', 'url' => ['/filling/power'], 'options' => ['class' => 'visible-xs']],
                ['label' => 'Тарифы', 'url' => ['/tariffs/index'], 'options' => ['class' => 'visible-xs']],
                Yii::$app->user->can('manage') ? ['label' => 'Управление', 'url' => ['/management/index'], 'options' => ['class' => 'visible-xs']] : '',
            ],
        ]);
    } catch (Exception $e) {
    }
    NavBar::end();
    ?>

    <div class="container">
        <?= Breadcrumbs::widget([
            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
            'homeLink'=>false,
        ]) ?>
        <?= Alert::widget() ?>
        <?= $content ?>
    </div>
</div>
<div id="alertsContentDiv" class="no-print"></div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
