<?php

use app\assets\ManagementAsset;
use nirvana\showloading\ShowLoadingAsset;
use yii\web\View;



/* @var $this View */

$this->title = "Глобальное управление";
ManagementAsset::register($this);
ShowLoadingAsset::register($this);

?>

<div class="btn-group-vertical">
    <button class="post-activator btn btn-danger" data-action="/management/db-erase">Опустошить базу</button>
    <button class="post-activator btn btn-success" data-action="/management/db-fill">Наполнить базу</button>
</div>
