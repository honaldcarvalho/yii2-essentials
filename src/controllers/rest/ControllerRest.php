<?php

namespace croacworks\essentials\controllers\rest;

use Yii;

use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;

class ControllerRest extends \yii\rest\Controller
{
    public $enableCsrfValidation = false;
    public $origin;
    public $free = [];

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];
     
    public function init(): void
    {   
        parent::init();
        Yii::$app->user->enableSession = false;
        Yii::$app->response->format = yii\web\Response::FORMAT_JSON;
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (!in_array(Yii::$app->controller->action->id, $this->free)) {

            $behaviors['authenticator'] = [
                'class' => CompositeAuth::class,
                'authMethods' => [
                    HttpBasicAuth::class,
                    HttpBearerAuth::class
                ],
            ];
        }

        return $behaviors;
    }
    
}