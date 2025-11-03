<?php

namespace croacworks\essentials\models;

use Yii;
use yii\helpers\Inflector;

/**
 * This is the model class for table "page_sections".
 *
 * @property int $id
 * @property int|null $parent_id
 * @property int|null $group_id
 * @property string|null $name
 * @property string $slug
 * @property int|null $list
 * @property int|null $status
 *
 * @property Pages[] $pages
 * @property Group $group
 * @property PageSection $pageSection
 * @property PageSection[] $page_sections
 */
class PageSection extends ModelCommon
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'page_sections';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status','list'], 'integer'],
            [['name'], 'string', 'max' => 255],
            [
                ['slug', 'group_id'],
                'unique',
                'targetAttribute' => ['slug', 'group_id'],
                'message' => Yii::t('app', 'Already exists a section with this Slug/Group/Language combination.')
            ],
            [['group_id'], 'exist', 'skipOnError' => true, 'targetClass' => Group::class, 'targetAttribute' => ['group_id' => 'id']],
            [['parent_id'], 'exist', 'skipOnError' => true, 'targetClass' => PageSection::class, 'targetAttribute' => ['parent_id' => 'id']],
        ];
    }

    public function generateUniqueSlug(?string $baseName = null): string
    {
        $baseStr = trim((string)($baseName ?? $this->name ?? $this->id));
        $seed    = Inflector::slug($baseStr) ?: (string)$this->id;

        $try = $seed;
        $i = 2;
        while (static::find()
            ->andWhere(['parent_id' => $this->parent_id, 'slug' => $try])
            ->andFilterWhere(['<>', 'id', $this->id])
            ->exists()
        ) {
            $try = $seed . '-' . $i++;
        }
        return $try;
    }

    public function beforeValidate()
    {
        if (!parent::beforeValidate()) return false;
        if (empty($this->slug)) {
            $this->slug = $this->generateUniqueSlug();
        }
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => Yii::t('app', 'Name'),
            'group' => Yii::t('app', 'Group'),
            'list' => Yii::t('app', 'List?'),
            'status' => Yii::t('app', 'Active'),
        ];
    }

    /**
     * Gets query for [[Group]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGroup()
    {
        return $this->hasOne(Group::class, ['id' => 'group_id']);
    }

    /**
     * Gets query for [[Pages]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPages()
    {
        return $this->hasMany(Page::class, ['parent_id' => 'id']);
    }

    /**
     * Gets query for [[PageSection]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPageSection()
    {
        return $this->hasOne(PageSection::class, ['id' => 'parent_id']);
    }

    public function getParent()
    {
        return $this->hasOne(PageSection::class, ['id' => 'parent_id']);
    }

    public function getChildren()
    {
        return $this->hasMany(PageSection::class, ['parent_id' => 'id']);
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

    /**
     * Gets query for [[PageSection]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPageSections()
    {
        return $this->hasMany(PageSection::class, ['id' => 'parent_id']);
    }
}
