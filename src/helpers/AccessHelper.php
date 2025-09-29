<?php
namespace croacworks\essentials\helpers;

use croacworks\essentials\controllers\CommonController;
use croacworks\essentials\models\AccessLog;
use Yii;

class AccessHelper
{
    /**
     * Registra acesso se for válido.
     * Se um modelo for passado e a action for "de detalhe",
     * incrementa o campo 'access' (se existir).
     */
    public static function registerAccess($controller = null,$action = null, $id = null)
    {
        $ip  = Yii::$app->request->userIP;
        $url = Yii::$app->request->absoluteUrl;

        // Host local vs externo
        $host    = parse_url($url, PHP_URL_HOST);
        $appHost = parse_url(Yii::$app->request->hostInfo, PHP_URL_HOST);

        if ($host !== $appHost) {
            return;
        }

        // Extensões ignoradas
        $extensoesIgnoradas = [
            'jpg','jpeg','png','gif','svg',
            'pdf','doc','docx','xls','xlsx','ppt','pptx',
            'zip','rar','mp4','mp3','avi','mov',
            'css','js','json','xml','txt'
        ];
        $path     = parse_url($url, PHP_URL_PATH);
        $extensao = pathinfo($path, PATHINFO_EXTENSION);

        if (in_array(strtolower($extensao), $extensoesIgnoradas)) {
            return;
        }

        // URLs específicas a ignorar
        if (stripos($url, 'vercaptcha') !== false || stripos($url, 'recaptcha') !== false) {
            return;
        }

        // Cache para não duplicar
        $cacheKey = "access_log_{$ip}_" . md5($url);

        if (Yii::$app->cache->get($cacheKey) === false) {

            $log = new AccessLog();
            $log->ip_address = $ip;
            $log->url = $url;
            $log->save(false);

            $request  = Yii::$app->request;
            $actionId = $actionId ?? ($controller->action->id ?? null);

            $controller = Yii::$app->controller;
            $controllerId = $controller->id ?? null;
            $modelName = str_replace(' ', '', ucwords(str_replace('-', ' ', $controllerId)));

            // Se for action de detalhe e modelo tem campo access -> incrementa
            $likelyViewActions = ['view','public','detail','show','path','page'];
            $isLikelyView = $actionId ? in_array($actionId, $likelyViewActions, true) : false;

            $preferredKeys = ['id','slug','uuid','code','key','path','page'];

            $params = array_merge(
                (array)$request->get(),
                (array)(Yii::$app->requestedParams ?? [])
            );

            // Se não é uma action de detalhe e não há nenhum param, não insista
            if ($isLikelyView && !empty($params)) {
                $param = '';
                // 1) Adiciona pares preferidos se existirem e forem escalares
                foreach ($preferredKeys as $k) {
                    if (array_key_exists($k, $params) && (is_scalar($params[$k]) || (is_object($params[$k]) && method_exists($params[$k],'__toString')))) {
                        $param = ['name'=> $k, 'value'=> (string)$params[$k]];
                    }
                }

                // Procura a classe do model
                $modelClass = CommonController::classExist($modelName);

                if ($modelClass !== null && !empty($param)) {  
                    // Busca o registro original;
                    if($param['name'] === 'path'){
                        $value = explode('/',$param['value']);
                        $originalModel = $modelClass::find()->where(['slug'=>end($value)])->one();
                    } else if($param['name'] === 'page'){
                        $value = explode('/',$param['value']);
                        $originalModel = $modelClass::find()->where(['id'=>end($value)])->one();
                    } else{
                        $originalModel = $modelClass::find()->where([$param['name']=>$param['value']])->one();
                    }

                    if ($originalModel) {
                        if ($originalModel !== null 
                            && in_array($actionId, $likelyViewActions, true) 
                            && $originalModel->hasAttribute('access')) 
                        {
                            $originalModel->updateCounters(['access' => 1]);
                        }
                    }
                }
            }

            Yii::$app->cache->set($cacheKey, true, 60);
        }

    }
}
