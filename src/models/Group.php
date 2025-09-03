<?php

namespace croacworks\essentials\models;

use Yii;
use yii\db\Query;
use yii\data\ActiveDataProvider;
use croacworks\essentials\controllers\AuthorizationController;

/**
 * This is the model class for table "groups".
 *
 * @property int $id
 * @property int $parent_id
 * @property string|null $name
 * @property int|null $status
 *
 * @property Project $project
 * @property Rules[] $rules
 * @property UserGroup[] $userGroups
 */
class Group extends ModelCommon
{
    public $verGroup = false;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'groups';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required','on' => ['create','update']],
            [['parent_id'], 'default', 'value' => null],
            [['parent_id'], 'exist', 'skipOnError' => true, 'targetClass' => Group::class, 'targetAttribute' => ['parent_id' => 'id']],
            [['status','parent_id'], 'integer'],
            [['name'], 'string', 'max' => 255],
            ['name', 'unique', 'targetClass' => 'croacworks\essentials\models\Group'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'parent_id' => Yii::t('app', 'Parent'),
            'name' => Yii::t('app', 'Name'),
            'status' => Yii::t('app', 'Active'),
        ];
    }

    /**
     * Gets query for [[Rules]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRoles()
    {
        return $this->hasMany(Role::class, ['group_id' => 'id']);
    }

    /**
     * Gets query for [[UserGroups]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUserGroups()
    {
        return $this->hasMany(UserGroup::class, ['group_id' => 'id']);
    }

    public function getParent()
    {
        return $this->hasOne(Group::class, ['id' => 'parent_id']);
    }

    public function getChildren()
    {
        return $this->hasMany(Group::class, ['parent_id' => 'id']);
    }

    public static function getAllDescendantIds($groupIds)
    {
        $all = [];
        $queue = (array) $groupIds;

        while (!empty($queue)) {
            $current = array_shift($queue);
            if (!in_array($current, $all)) {
                $all[] = $current;
                $children = static::find()
                    ->select('id')
                    ->where(['parent_id' => $current])
                    ->column();
                $queue = array_merge($queue, $children);
            }
        }

        return $all;
    }

    public static function cloneGroupWithRules($groupId, $newGroupName = null)
    {
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $originalGroup = self::findOne($groupId);
            if (!$originalGroup) {
                throw new \Exception("Grupo original não encontrado.");
            }

            // Clonar o grupo
            $newGroup = new self();
            $newGroup->name = $newGroupName ?? $originalGroup->name . ' (Clone)';
            $newGroup->status = $originalGroup->status;
            $newGroup->parent_id = $originalGroup->parent_id;

            if (!$newGroup->save()) {
                throw new \Exception("Erro ao salvar o grupo clonado: " . json_encode($newGroup->errors));
            }

            // Remove as regras padrão criadas pelo trigger
            Yii::$app->db->createCommand()
                ->delete('rules', ['group_id' => $newGroup->id])
                ->execute();

            // Clona as regras do grupo original
            foreach ($originalGroup->rules as $rule) {
                $newRule = new \croacworks\essentials\models\Role();
                $newRule->attributes = $rule->attributes;
                $newRule->group_id = $newGroup->id;

                unset($newRule->id, $newRule->created_at, $newRule->updated_at); // se existirem

                if (!$newRule->save()) {
                    throw new \Exception("Erro ao salvar regra clonada: " . json_encode($newRule->errors));
                }
            }

            $transaction->commit();
            return ['success' => true, 'group' => $newGroup];
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error("Erro ao clonar grupo: " . $e->getMessage(), __METHOD__);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Sobe na árvore até achar o root de um grupo.
     */
    public static function getRootId(int $groupId): int
    {
        $db   = Yii::$app->db;
        $curr = $groupId;

        while (true) {
            $row = (new Query())
                ->from(static::tableName())
                ->select(['parent_id'])
                ->where(['id' => $curr])
                ->one($db);

            if (!$row || empty($row['parent_id'])) {
                return (int)$curr;
            }
            $curr = (int)$row['parent_id'];
        }
    }

    /**
     * Retorna TODOS os ids da árvore daquele ROOT (root + descendentes).
     * Tenta CTE recursivo; cai no fallback se não suportado.
     */
    public static function familyIdsByRoot(int $rootId): array
    {
        $db = Yii::$app->db;

        try {
            $sql = "
                WITH RECURSIVE grp AS (
                    SELECT id, parent_id
                    FROM " . static::tableName() . " 
                    WHERE id = :root
                  UNION ALL
                    SELECT g.id, g.parent_id
                    FROM " . static::tableName() . " g
                    JOIN grp ON g.parent_id = grp.id
                )
                SELECT id FROM grp
            ";
            $ids = $db->createCommand($sql, [':root' => $rootId])->queryColumn();
            return array_values(array_unique(array_map('intval', $ids)));
        } catch (\Throwable $e) {
            // FALLBACK: sem CTE (MariaDB muito antiga)
            $all = (new Query())
                ->from(static::tableName())
                ->select(['id','parent_id'])
                ->all($db);

            $byParent = [];
            foreach ($all as $g) {
                $pid = (int)($g['parent_id'] ?? 0);
                $byParent[$pid][] = (int)$g['id'];
            }

            $stack = [$rootId];
            $seen  = [];
            while ($stack) {
                $curr = array_pop($stack);
                if (isset($seen[$curr])) continue;
                $seen[$curr] = true;

                foreach ($byParent[$curr] ?? [] as $child) {
                    if (!isset($seen[$child])) $stack[] = $child;
                }
            }
            return array_map('intval', array_keys($seen));
        }
    }

    /**
     * A partir de VÁRIOS grupos, une as famílias (caso o usuário tenha mais de um root).
     */
    public static function familyIdsFromMany(array $groupIds): array
    {
        $roots    = [];
        $families = [];

        foreach ($groupIds as $gid) {
            $gid = (int)$gid;
            if ($gid <= 0) continue;
            $root = static::getRootId($gid);
            $roots[$root] = true;
        }

        foreach (array_keys($roots) as $rootId) {
            foreach (static::familyIdsByRoot((int)$rootId) as $id) {
                $families[$id] = true;
            }
        }

        return array_values(array_map('intval', array_keys($families)));
    }

    /**
     * Helper direto para o usuário.
     */
    public static function familyIdsFromUser(\croacworks\essentials\models\User $user): array
    {
        $ids = $user->getUserGroupsId(); // já existe no teu projeto
        return static::familyIdsFromMany($ids);
    }
    
    /**
     * find() com escopo de família para não-admin.
     * ⚠️ Sem return type custom aqui!
     */
    public static function find($applyScope = true) // <-- sem type!
    {
        $query = parent::find();

        // Se for chamado dentro de uma relação (getXxx), não aplica escopo
        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 6);
        foreach ($bt as $t) {
            if (
                isset($t['function'], $t['class']) &&
                str_starts_with($t['function'], 'get') &&
                is_subclass_of($t['class'], \yii\db\BaseActiveRecord::class)
            ) {
                return $query;
            }
        }

        if ($applyScope === false) {
            return $query; // listar sem escopo quando precisar
        }

        $user = AuthorizationController::User();
        if (!$user) {
            return $query->where('1=0'); // guest: nada
        }

        if (AuthorizationController::isMaster()) {
            return $query; // admin: vê tudo
        }

        // família do(s) grupo(s) do usuário
        $familyIds = static::familyIdsFromUser($user);
        $familyIds = array_values(array_unique(array_map('intval', $familyIds)));

        if (empty($familyIds)) {
            return $query->where('1=0');
        }

        return $query->andWhere([static::tableName() . '.id' => $familyIds]);
    }

    /**
     * search() usando o escopo acima.
     */
    public function search($params, $options = ['pageSize' => 10, 'orderBy' => ['id' => SORT_DESC]]): ActiveDataProvider
    {
        $query = static::find(true); // aplica escopo (admin: sem filtro; não-admin: família)

        $dataProvider = new ActiveDataProvider([
            'query'      => $query,
            'pagination' => ['pageSize' => (int)($options['pageSize'] ?? 10)],
            'sort'       => [
                'defaultOrder' => $options['orderBy'] ?? ['id' => SORT_DESC],
                'attributes'   => ['id','name','level','status','parent_id','created_at','updated_at'],
            ],
        ]);

        $this->load($params);
        if (method_exists($this, 'validate') && !$this->validate()) {
            return $dataProvider;
        }

        $t = static::tableName();
        $query->andFilterWhere([$t.'.id' => $this->id])
              ->andFilterWhere([$t.'.level' => $this->level])
              ->andFilterWhere([$t.'.status' => $this->status])
              ->andFilterWhere([$t.'.parent_id' => $this->parent_id])
              ->andFilterWhere(['like', $t.'.name', $this->name]);

        return $dataProvider;
    }

}
