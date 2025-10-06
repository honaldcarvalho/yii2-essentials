<?php

namespace croacworks\essentials\controllers\rest;

use croacworks\essentials\controllers\AuthorizationController;
use Yii;

use croacworks\essentials\models\City;
use croacworks\essentials\models\State;
use yii\web\BadRequestHttpException;

class AddressController extends AuthorizationController {
    
    public function __construct($id, $module, $config = array())
    {
        parent::__construct($id, $module, $config);
        $this->free = ['cities', 'states'];
    }

    public function actionCities(){
        $body = Yii::$app->request->getBodyParams();
        if(!isset($body['state_id'])) {
            throw new BadRequestHttpException('Provide the state_id.');
        }
        return City::findAll(['state_id'=>$body['state_id'], 'status'=>1]);
    }

    public function actionStates(){
        return State::findAll(['status'=>1]);
    }

}