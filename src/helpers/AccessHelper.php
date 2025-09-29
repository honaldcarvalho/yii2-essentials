<?php
namespace croacworks\essentials\helpers;

use Yii;
use yii\db\ActiveRecord;
use yii\web\Controller;
use croacworks\essentials\models\AccessLog;

class AccessHelper
{
    /**
     * Use no beforeAction:
     *   \common\helpers\AccessHelper::registerAccess($this, $action->id);
     */
    public static function registerAccess(Controller $controller, ?string $actionId = null): void
    {
        $request = Yii::$app->request;
        $ip  = $request->userIP;
        $url = $request->absoluteUrl;

        // --- Filtros ---------------------------------------------------------
        $host    = parse_url($url, PHP_URL_HOST);
        $appHost = parse_url($request->hostInfo, PHP_URL_HOST);
        if ($host !== $appHost) return;

        $ignoredExt = [
            'jpg','jpeg','png','gif','svg',
            'pdf','doc','docx','xls','xlsx','ppt','pptx',
            'zip','rar','mp4','mp3','avi','mov',
            'css','js','json','xml','txt'
        ];
        $path = parse_url($url, PHP_URL_PATH);
        $ext  = strtolower((string)pathinfo($path, PATHINFO_EXTENSION));
        if ($ext && in_array($ext, $ignoredExt, true)) return;

        if (stripos($url, 'vercaptcha') !== false || stripos($url, 'recaptcha') !== false) return;

        // --- Deduplicação por 5 minutos (IP + URL) --------------------------
        $cacheKey = "access_log_{$ip}_" . md5($url);
        if (Yii::$app->cache->exists($cacheKey)) {
            return; // mantém a semântica: só incrementa junto com o log
        }

        // --- Persistir AccessLog --------------------------------------------
        $log = new AccessLog();
        $log->ip_address = $ip;
        $log->url = $url;
        $log->save(false);

        Yii::$app->cache->set($cacheKey, true, 300);

        // --- Incrementar `access` no modelo exibido (se houver) -------------
        self::incrementModelAccessIfPossible($controller, $actionId);
    }

    /**
     * Tenta descobrir o modelo exibido e, se tiver atributo `access`, incrementa +1.
     * Estratégias: findModel() e/ou modelClass (PK/slug/coluna compatível).
     */
    protected static function incrementModelAccessIfPossible(Controller $controller, ?string $actionId = null): void
    {
        $request  = Yii::$app->request;
        $actionId = $actionId ?? ($controller->action->id ?? null);

        // Em geral só faz sentido em páginas "de detalhe"
        $likelyViewActions = ['view','public','detail','show'];
        $isLikelyView = $actionId ? in_array($actionId, $likelyViewActions, true) : false;

        // Parâmetros disponíveis (GET + params resolvidos pela URL rule)
        $params = array_merge(
            (array)$request->get(),
            (array)(Yii::$app->requestedParams ?? [])
        );

        // Se não é uma action de detalhe e não há nenhum param, não insista
        if (!$isLikelyView && empty($params)) {
            return;
        }

        // Prioridade de chaves "clássicas"
        $preferredKeys = ['id','slug','uuid','code','key'];
        $candidatePairs = [];

        // 1) Adiciona pares preferidos se existirem e forem escalares
        foreach ($preferredKeys as $k) {
            if (array_key_exists($k, $params) && (is_scalar($params[$k]) || (is_object($params[$k]) && method_exists($params[$k],'__toString')))) {
                $candidatePairs[] = [$k, (string)$params[$k]];
            }
        }
        // 2) Adiciona demais pares escalares
        foreach ($params as $k => $v) {
            if (in_array($k, $preferredKeys, true)) continue;
            if (is_scalar($v) || (is_object($v) && method_exists($v,'__toString'))) {
                $candidatePairs[] = [$k, (string)$v];
            }
        }

        $model = null;

        // --- (A) Tentar via findModel($value) -------------------------------
        if (method_exists($controller, 'findModel')) {
            foreach ($candidatePairs as [$k, $v]) {
                try {
                    $maybe = $controller->findModel($v);
                    if ($maybe instanceof ActiveRecord) {
                        $model = $maybe;
                        break;
                    }
                } catch (\Throwable $e) {
                    // tenta o próximo
                }
            }
        }

        // --- (B) Tentar via modelClass --------------------------------------
        if ($model === null && property_exists($controller, 'modelClass')) {
            $cls = $controller->modelClass;
            if (is_string($cls) && class_exists($cls) && is_subclass_of($cls, ActiveRecord::class)) {
                try {
                    $schema = $cls::getTableSchema();

                    // (B1) PK composta ou simples com params completos
                    $pk = $schema->primaryKey;
                    $pkCond = [];
                    $hasAllPk = true;
                    foreach ($pk as $pkCol) {
                        if (!array_key_exists($pkCol, $params)) { $hasAllPk = false; break; }
                        $pkCond[$pkCol] = $params[$pkCol];
                    }
                    if ($hasAllPk && !empty($pkCond)) {
                        $model = $cls::findOne($pkCond);
                    }

                    // (B2) slug, se existir coluna e param
                    if ($model === null && $schema->getColumn('slug') && isset($params['slug'])) {
                        $model = $cls::find()->where(['slug' => $params['slug']])->limit(1)->one();
                    }

                    // (B3) primeira coluna compatível com um param scalar
                    if ($model === null) {
                        foreach ($candidatePairs as [$k, $v]) {
                            if ($schema->getColumn($k)) {
                                $model = $cls::find()->where([$k => $v])->limit(1)->one();
                                if ($model) break;
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    // silencioso
                }
            }
        }

        // --- Incrementar se tiver atributo `access` --------------------------
        if ($model instanceof ActiveRecord && $model->hasAttribute('access')) {
            try {
                $model::updateAllCounters(['access' => 1], $model->getPrimaryKey(true));
            } catch (\Throwable $e) {
                // não quebra a página por causa do contador
            }
        }
    }
}
