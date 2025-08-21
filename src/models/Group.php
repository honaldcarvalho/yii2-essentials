<?php

namespace croacworks\essentials\models;

use Yii;

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
}
