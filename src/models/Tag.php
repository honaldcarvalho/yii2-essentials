<?php

namespace croacworks\essentials\models;

use croacworks\essentials\models\ModelCommon;
use yii\behaviors\TimestampBehavior;
use yii\helpers\Inflector;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int $status
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Post[] $posts
 */
class Tag extends ModelCommon
{
    public static function tableName()
    {
        return '{{%tags}}';
    }

    public function behaviors()
    {
        return [TimestampBehavior::class];
    }

    public function rules()
    {
        return [
            [['name'], 'required', 'message' => \Yii::t('app', 'The field "{attr}" is required.', ['attr' => \Yii::t('app', 'Name')]),'on'=>['create','update']],
            [['status'], 'integer'],
            [['name'], 'string', 'max' => 100],
            [['slug'], 'string', 'max' => 120],
            [['slug'], 'unique'],
            [['name'], 'unique', 'message' => \Yii::t('app', 'This name is already in use.')],
            [['slug'], 'unique', 'message' => \Yii::t('app', 'This slug is already in use.')],
            [['status'], 'default', 'value' => 1,'on'=>['create','update']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'         => \Yii::t('app', 'ID'),
            'name'       => \Yii::t('app', 'Name'),
            'slug'       => \Yii::t('app', 'Slug'),
            'status'     => \Yii::t('app', 'Status'),
            'created_at' => \Yii::t('app', 'Created At'),
            'updated_at' => \Yii::t('app', 'Updated At'),
        ];
    }

    public function beforeValidate()
    {
        if (!parent::beforeValidate()) {
            return false;
        }
        if (empty($this->slug) && !empty($this->name)) {
            $this->slug = Inflector::slug($this->name);
        }
        return true;
    }

    public function getPages()
    {
        return $this->hasMany(Page::class, ['id' => 'post_id'])
            ->viaTable('{{%page_tags}}', ['tag_id' => 'id']);
    }
}
