<?php

namespace croacworks\essentials\models;

use Yii;

/**
 * This is the model class for table "page_sections".
 *
 * @property int $id
 * @property int|null $page_section_id
 * @property int|null $group_id
 * @property string|null $name
 * @property string $slug
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
            [['status'], 'integer'],
            [['name'], 'string', 'max' => 255],
            [
                ['slug', 'group_id'],
                'unique',
                'targetAttribute' => ['slug', 'group_id'],
                'message' => Yii::t('app', 'Already exists a section with this Slug/Group/Language combination.')
            ],
            [['group_id'], 'exist', 'skipOnError' => true, 'targetClass' => Group::class, 'targetAttribute' => ['group_id' => 'id']],
            [['page_section_id'], 'exist', 'skipOnError' => true, 'targetClass' => PageSection::class, 'targetAttribute' => ['page_section_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => Yii::t('app', 'Name'),
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
        return $this->hasMany(Page::class, ['page_section_id' => 'id']);
    }

    /**
     * Gets query for [[PageSection]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPageSection()
    {
        return $this->hasOne(PageSection::class, ['id' => 'page_section_id']);
    }

    /**
     * Gets query for [[PageSection]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPageSections()
    {
        return $this->hasMany(PageSection::class, ['id' => 'page_section_id']);
    }
}
