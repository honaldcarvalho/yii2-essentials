<?php

namespace croacworks\essentials\validators;

use Yii;
use yii\validators\Validator;

class PostalCodeValidator extends Validator
{

    public function init()
    {
        parent::init();
        $this->message =  Yii::t('app', 'Wrong postal code format.');
    }

    public function validateAttribute($model, $attribute)
    {
        if (!preg_match('/^[0-9]{5}-[0-9]{3}$/', $model->$attribute)){
            $model->addError($attribute, $this->message);
        }
    }

    public function clientValidateAttribute($model, $attribute, $view)
    {
        $message = json_encode($this->message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return <<<JS
            if (!/^[0-9]{5}-[0-9]{3}$/.test(value)) {
                messages.push({$message});
            } 
        JS;
    }
    
}
