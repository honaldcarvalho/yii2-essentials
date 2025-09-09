<?php

namespace croacworks\essentials\widgets\form;

use yii\widgets\ActiveForm as YiiActiveForm;

class ActiveForm extends YiiActiveForm
{
    public $fieldClass = SearchActiveField::class;
}
