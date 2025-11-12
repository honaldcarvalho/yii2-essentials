<?php

namespace croacworks\essentials\widgets\form;

use yii\bootstrap5\ActiveForm as YiiActiveForm;

class ActiveForm extends YiiActiveForm
{
    public $fieldClass = SearchActiveField::class;
}
