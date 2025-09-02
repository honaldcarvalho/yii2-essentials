<?php

namespace croacworks\essentials\models;

use Yii;
use yii\data\ActiveDataProvider;
use croacworks\essentials\controllers\AuthorizationController;

class ModelCommon extends \yii\db\ActiveRecord
{

    public $verGroup = false;
    public $created_atFDTsod;
    public $created_atFDTeod;

    const SCENARIO_CREATE = 'create';
    const SCENARIO_UPDATE = 'update';
    const SCENARIO_STATUS = 'status';
    const SCENARIO_SEARCH = 'search';
    const SCENARIO_FILE = 'file';

    public function scenarios()
    {
        $scenarios = parent::scenarios();

        // Lista de atributos deste model (chaves)
        $attrs = array_keys($this->getAttributes());

        // Garante que existam os índices
        foreach ([self::SCENARIO_DEFAULT, self::SCENARIO_SEARCH, 'create', 'update'] as $scene) {
            if (!isset($scenarios[$scene]) || !is_array($scenarios[$scene])) {
                $scenarios[$scene] = [];
            }
        }

        // Popular cenários com todos os atributos do model
        foreach ($attrs as $attr) {
            $scenarios[self::SCENARIO_DEFAULT][] = $attr;
            $scenarios[self::SCENARIO_SEARCH][]  = $attr;
            $scenarios['create'][]               = $attr;
            $scenarios['update'][]               = $attr;
        }

        // Cenários específicos já existentes no seu código
        if (!isset($scenarios[self::SCENARIO_STATUS]) || !is_array($scenarios[self::SCENARIO_STATUS])) {
            $scenarios[self::SCENARIO_STATUS] = [];
        }
        $scenarios[self::SCENARIO_STATUS] = array_unique(array_merge($scenarios[self::SCENARIO_STATUS], ['status']));

        // Cenário de arquivo
        $scenarios[self::SCENARIO_FILE] = ['file_id'];

        // Remove duplicados por segurança
        foreach ($scenarios as $k => $list) {
            if (is_array($list)) {
                $scenarios[$k] = array_values(array_unique($list));
            }
        }

        return $scenarios;
    }


    public static function find($verGroup = null)
    {
        $query = parent::find();

        // evita intervir em chamadas dentro de relações
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        foreach ($backtrace as $trace) {
            if (isset($trace['function']) && str_starts_with($trace['function'], 'get') && isset($trace['class'])) {
                if (is_subclass_of($trace['class'], \yii\db\BaseActiveRecord::class)) {
                    return $query;
                }
            }
        }

        if ($verGroup === null) {
            $instance = new static();
            $verGroup = $instance->verGroup ?? true;
        }

        if ($verGroup && property_exists(static::class, 'verGroup')) {
            $user = \croacworks\essentials\controllers\AuthorizationController::User();

            if ($user) {
                // NOVO: usa a FAMÍLIA dos grupos do usuário (pai ⇄ filhos ⇄ irmãos)
                $groupIds = Group::familyIdsFromUser($user);

                // Mantém acesso ao grupo 1 se for tua política (opcional)
                $groupIds[] = 1;
                $groupIds = array_values(array_unique(array_map('intval', $groupIds)));

                $table = static::tableName();
                $model = new static();

                if ($model->hasAttribute('group_id')) {
                    $query->andWhere(["{$table}.group_id" => $groupIds]);
                } elseif (method_exists($model, 'groupRelationPath')) {
                    $path = $model::groupRelationPath();
                    $relationPath = implode('.', $path);

                    $valid = true;
                    $currentModel = $model;

                    foreach ($path as $relation) {
                        $method = 'get' . ucfirst($relation);
                        if (!method_exists($currentModel, $method)) {
                            Yii::warning("Relação inválida '{$relation}' em groupRelationPath() de " . static::class);
                            $valid = false;
                            break;
                        }
                        $relationQuery = $currentModel->$method();
                        $currentModel = new ($relationQuery->modelClass);
                    }

                    if ($valid) {
                        $query->joinWith([$relationPath]);
                        $finalTable = $currentModel::tableName();
                        $query->andWhere(["{$finalTable}.group_id" => $groupIds]);
                    }
                }
            }
        }

        return $query;
    }

    public function rules()
    {
        return [
            [['id', 'status'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['created_atFDTsod', 'created_atFDTeod'], 'safe'],
        ];
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($insert) {
            $user = AuthorizationController::User();

            if (AuthorizationController::isAdmin()) {
                if (!empty($this->group_id)) {
                    return true;
                } else if (($admin_group = Parameter::findOne(['name' => 'admin-group'])?->value) !== null && $this->hasAttribute('group_id')) {
                    $this->group_id = $admin_group;
                }
            }

            if ($this->hasAttribute('group_id') && empty($this->group_id)) {
                // Tenta usar parâmetro fixo (caso exista)
                $mainGroup = Parameter::findOne(['name' => 'main-group'])?->value;

                if ($mainGroup) {
                    $this->group_id = $mainGroup;
                } else {

                    if ($user) {
                        // Obtém todos os grupos do usuário
                        $userGroups = $user->getGroups()->all();

                        // Tenta encontrar o grupo raiz (sem parent)
                        foreach ($userGroups as $group) {
                            if (!$group->parent_id) {
                                $this->group_id = $group->id;
                                break;
                            }
                        }

                        // Se não achar nenhum root, pega o primeiro grupo mesmo
                        if (!$this->group_id && count($userGroups) > 0) {
                            $this->group_id = $userGroups[0]->id;
                        }
                    }
                }
            }
        }

        return true;
    }

    public static function getClass()
    {
        $array = explode('\\', get_called_class());
        return end($array);
    }

    public static function getClassPath()
    {
        return get_called_class();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params, $options = ['pageSize' => 10, 'orderBy' => ['id' => SORT_DESC], 'order' => false],)
    {
        $this->scenario = self::SCENARIO_SEARCH;

        $className = self::getClass();
        $table = static::tableName();
        $pageSize = 10;
        $order = false;
        $orderField = false;

        $query = static::find();

        if (isset($options['select'])) {
            $query->select($options['select']);
        }

        $sort = [
            'defaultOrder' => ['id' => SORT_DESC], // padrão
        ];

        if (isset($options['orderBy'])) {
            $sort['defaultOrder'] = $options['orderBy'];
        }

        if (isset($options['pageSize'])) {
            $pageSize = $options['pageSize'];
        }

        /**
            AQUI FAZ A VERIFICAÇÃO SE TEM UM ITEM DE ORDENAMENTO QUE MUDA A TAMANHO DA LISTAGEM. CASO SEJA FORNECIDO UM CAMPO FLAG E ELE NÃO SEJA NULO/VAZIO
            O TAMANHO PASSA PARA 10000
         */
        if (isset($options['order']) && $options['order'] && !empty($options['order']) && count($params) > 0) {
            $query->orderBy([$options['order']['field'] => SORT_ASC]);

            if (
                (
                    isset($options['order']['flag']) &&
                    $options['order']['flag'] != false &&
                    isset($params[$className][$options['order']['flag']]) &&
                    !empty($params[$className][$options['order']['flag']])
                )
            ) {
                foreach ($params["{$className}"] as $field => $search) {
                    if (!empty($search)) {
                        $pageSize = 10000;
                        break;
                    }
                }
            }
        }

        if (isset($options['join'])) {
            if (is_array($options['join'])) {
                foreach ($options['join'] as $model) {
                    [$method, $table, $criteria] = $model;
                    $query->join($method, $table, $criteria);
                }
            }
        }

        if (isset($options['groupModel'])) {
            $field =  AuthorizationController::addSlashUpperLower($className);
            $query->leftJoin($options['groupModel']['table'], "{$table}.{$options['groupModel']['field']} = {$options['groupModel']['table']}.id");
        }
        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize
            ],
            'sort' => $sort,
        ]);

        $this->load($params);

        // grid filtering conditions
        $user = AuthorizationController::User();

        if ($this->verGroup && $user) {
            // NOVO: família completa do(s) grupo(s) do usuário (pai ⇄ filhos ⇄ irmãos)
            $group_ids = Group::familyIdsFromUser($user);

            // (opcional) garante visibilidade do grupo 1
            $group_ids[] = 1;
            $group_ids = array_values(array_unique(array_map('intval', $group_ids)));

            $table = static::tableName();

            $groupPath = method_exists($this, 'groupRelationPath') ? static::groupRelationPath() : null;

            if ($groupPath) {
                $relationPath = '';
                foreach ($groupPath as $i => $relation) {
                    $relationPath .= ($i > 0 ? '.' : '') . $relation;
                    $query->joinWith([$relationPath]);
                }

                $tableAlias = Yii::createObject(static::class)->getRelation(end($groupPath))->modelClass::tableName();
                $query->andFilterWhere(['in', "{$tableAlias}.group_id", $group_ids]);
            } elseif (isset($options['groupModel'])) {
                $query->andFilterWhere(['in', "{$options['groupModel']['table']}.group_id", $group_ids]);
            } elseif ($this->hasAttribute('group_id')) {
                $query->andFilterWhere(["{$table}.group_id" => $group_ids]);
            }
        }

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        //create criteria by search type
        foreach ($params as $field => $search) {

            if ($field == 'page')
                continue;

            $field_type = gettype($search);
            $field_parts = explode(':', $field);
            if (count($field_parts) > 1) {
                [$field, $field_type] = $field_parts;
            }

            if (!isset($params["{$className}"]))
                continue;

            foreach ($params["{$className}"] as $field => $search) {

                $field_type = gettype($search);
                if (is_numeric($search) && (int)$search == $search) {
                    $field_type = "number";
                }
                $field_parts = explode(':', $field);

                if (count($field_parts) > 1) {
                    [$field, $field_type] = $field_parts;
                }

                if ($field_type == 'custom') {
                    $query->andFilterWhere(["$table.$field", $search[0], $search[1]]);
                } else if ($field_type == 'between') {
                    $query->andFilterWhere(['between', "$table.$field", $search[0], $search[1]]);
                } else if ($field_type == 'string') {
                    if (str_contains($field, 'sod') || str_contains($field, 'eod')) {
                        [$field_date, $pos] = explode('FDT', $field);
                        if ($pos == 'sod') {
                            $query->andFilterWhere(['>=', "$table.$field_date", $search]);
                        } else if ($pos == 'eod') {
                            $query->andFilterWhere(['<=', "$table.$field_date", $search]);
                        }
                    } else {
                        $query->andFilterWhere(['like', "$table.$field", $search]);
                    }
                } else if (str_contains($field, 'sod') || str_contains($field, 'eod')) {
                    [$field_date, $pos] = explode('FDT', $field);
                    if ($pos == 'sod') {
                        $query->andFilterWhere(['>=', "$table." . $field_date, $search]);
                    } else if ($pos == 'eod') {
                        $query->andFilterWhere(['<=', "$table." . $field_date, $search]);
                    }
                } else {
                    $query->andFilterWhere(["$table.$field" => $search]);
                }
            }
        }
        // $query = $dataProvider->query;
        // dd($query->createCommand()->getRawSql());
        return $dataProvider;
    }

    public static function clearFrontendCache($key)
    {
        // Envia uma solicitação para o frontend limpar o cache
        $url = Yii::getAlias("@host");
        $frontendUrl = "{$url}/site/clear-cache?key={$key}";

        $ch = curl_init($frontendUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        $response = curl_exec($ch);
        curl_close($ch);
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        $this->clearCache();
    }

    public function afterDelete()
    {
        parent::afterDelete();
        $this->clearCache();
    }

    protected function clearCache()
    {
        // Gera a chave de cache automaticamente com base no nome do modelo
        $cacheKey = 'cache_' . strtolower((new \ReflectionClass($this))->getShortName());

        \Yii::$app->cache->delete($cacheKey);
    }

    /**
     * Limpa uma chave de cache específica.
     * @param string $cacheKey Nome da chave do cache.
     */
    public static function clearCacheCustom($cacheKey)
    {
        \Yii::$app->cache->delete($cacheKey);
        return true;
    }

    /**
     * Limpa múltiplas chaves de cache específicas.
     * @param array $cacheKeys Lista de chaves para serem apagadas.
     */
    public static function clearMultipleCaches(array $cacheKeys)
    {
        foreach ($cacheKeys as $cacheKey) {
            \Yii::$app->cache->delete($cacheKey);
        }
        return true;
    }
}
