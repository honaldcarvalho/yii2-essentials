<?php
namespace common\helpers;

use croacworks\essentials\models\AccessLog;
use Yii;
use yii\db\ActiveRecord;
use yii\web\Controller;

class AccessHelper
{
    /**
     * Registra o acesso (com filtros) e, se houver um modelo sendo visualizado
     * e ele possuir o atributo `access`, incrementa +1.
     *
     * Use no beforeAction: AccessHelper::registerAccess($this, $actionId);
     */
    public static function registerAccess(Controller $controller, ?string $actionId = null): void
    {
        $request = Yii::$app->request;
        $ip  = $request->userIP;
        $url = $request->absoluteUrl;

        // --- Filtros de host/arquivo/urls indesejadas ------------------------
        $host    = parse_url($url, PHP_URL_HOST);
        $appHost = parse_url($request->hostInfo, PHP_URL_HOST);
        if ($host !== $appHost) {
            return;
        }

        $extsIgnore = [
            'jpg','jpeg','png','gif','svg',
            'pdf','doc','docx','xls','xlsx','ppt','pptx',
            'zip','rar','mp4','mp3','avi','mov',
            'css','js','json','xml','txt'
        ];
        $path     = parse_url($url, PHP_URL_PATH);
        $ext      = strtolower((string)pathinfo($path, PATHINFO_EXTENSION));
        if ($ext && in_array($ext, $extsIgnore, true)) {
            return;
        }

        if (stripos($url, 'vercaptcha') !== false || stripos($url, 'recaptcha') !== false) {
            return;
        }

        // --- Cache anti-duplicação por 5 min (ip + url) ----------------------
        $cacheKey = "access_log_{$ip}_" . md5($url);
        if (Yii::$app->cache->exists($cacheKey)) {
            return;
        }

        // --- Grava o AccessLog ----------------------------------------------
        $log = new AccessLog();
        $log->ip_address = $ip;
        $log->url = $url;
        $log->save(false);

        Yii::$app->cache->set($cacheKey, true, 300);

        // --- Incrementa `access` no modelo atual (se existir) ----------------
        self::incrementModelAccessIfPossible($controller, $actionId);
    }

    /**
     * Tenta identificar o modelo da página.
     * Estratégias:
     *  1) Controller::findModel($id|$slug) se existir.
     *  2) Controller::$modelClass (padrão REST) com id/slug.
     * Se o modelo tiver atributo `access`, faz updateAllCounters(+1).
     */
    protected static function incrementModelAccessIfPossible(Controller $controller, ?string $actionId = null): void
    {
        $request = Yii::$app->request;
        $id      = $request->get('id');
        $slug    = $request->get('slug');

        // só tenta em actions que geralmente exibem 1 registro
        $actionId = $actionId ?? ($controller->action ? $controller->action->id : null);
        $likelyViewActions = ['view','public','detail','show'];
        if ($actionId && !in_array($actionId, $likelyViewActions, true) && $id === null && $slug === null) {
            return;
        }

        $model = null;

        // 1) findModel() do controller, se existir
        if (method_exists($controller, 'findModel')) {
            try {
                if ($id !== null) {
                    $model = $controller->findModel($id);
                } elseif ($slug !== null) {
                    $model = $controller->findModel($slug);
                }
            } catch (\Throwable $e) {
                $model = null; // ignora se não achou
            }
        }

        // 2) Propriedade $modelClass (REST) ou controllers que definem isso
        if ($model === null && property_exists($controller, 'modelClass')) {
            $cls = $controller->modelClass;
            if (is_string($cls) && class_exists($cls) && is_subclass_of($cls, ActiveRecord::class)) {
                try {
                    if ($id !== null) {
                        $model = $cls::findOne($id);
                    } elseif ($slug !== null && $cls::getTableSchema()->getColumn('slug')) {
                        $model = $cls::find()->where(['slug' => $slug])->limit(1)->one();
                    }
                } catch (\Throwable $e) {
                    $model = null;
                }
            }
        }

        // Se encontrou e tem atributo `access`, incrementa
        if ($model instanceof ActiveRecord && $model->hasAttribute('access')) {
            try {
                // updateAllCounters usa a PK (funciona com PK composta também)
                $pkCondition = $model->getPrimaryKey(true);
                $model::updateAllCounters(['access' => 1], $pkCondition);
            } catch (\Throwable $e) {
                // silencioso; não deve quebrar a página por causa do contador
            }
        }
    }
}
