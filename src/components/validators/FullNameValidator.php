<?php

namespace croacworks\essentials\components\validators;

use Yii;
use yii\validators\Validator;

class FullNameValidator extends Validator
{

    public function init()
    {
        parent::init();
        $this->message =  Yii::t('app', 'Inválid Name!');
    }

    public function validateAttribute($model, $attribute)
    {
        if ($model->fullname !== null) {
            $fullname_array = explode(' ', $model->fullname);
            if (count($fullname_array) < 2) {
                $this->addError($model, $attribute, $this->message);
                return false;
            }
            return true;
        }
    }

    public function clientValidateAttribute($model, $attribute, $view)
    {
        $message = json_encode($this->message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return  <<< JS
            let split = value.trim().split(' ');
            if(split.length < 2 || split[1].trim() == '') {
                messages.push({$message});
            }
        JS;

    }
    
}
