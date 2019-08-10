<?php


namespace app\models\selection_classes;


class ActivatorAnswer
{
    public $status;
    public $header;
    public $view;

    public function return()
    {
        return ['status' => $this->status, 'header' => $this->header, 'view' => $this->view];
    }
}