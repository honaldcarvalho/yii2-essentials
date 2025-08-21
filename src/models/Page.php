<?php

namespace croacworks\essentials\models;

use croacworks\essentials\models\Language;
use Yii;

/**
 * This is the model class for table "pages".
 *
 * @property int $id
 * @property int|null $group_id
 * @property int|null $section_id
 * @property string $language_id
 * @property string $slug
 * @property string $title
 * @property string $description
 * @property string|null $content
 * @property string|null $keywords
 * @property string|null $custom_css
 * @property string|null $custom_js
 * @property datetime|null $created_at
 * @property datetime|null $updated_at
 * @property int|null $status
 *
 * @property PageFiles[] $pageFiles
 * @property Section $section
 * @property Group $group
 */
class Page extends ModelCommon
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'pages';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['section_id', 'status'], 'integer'],
            [['slug', 'title'], 'required','on'=> self::SCENARIO_DEFAULT],
            [['content', 'keywords','custom_js','custom_css','language_id'], 'string'],
            [['created_at','updated_at'], 'safe'],
            [['slug', 'title'], 'string', 'max' => 255],
            [['description'], 'string', 'max' => 300],
            [['section_id'], 'exist', 'skipOnError' => true, 'targetClass' => Section::class, 'targetAttribute' => ['section_id' => 'id']],
            [['language_id'], 'exist', 'skipOnError' => true, 'targetClass' => Language::class, 'targetAttribute' => ['language_id' => 'id']],
            [['group_id'], 'exist', 'skipOnError' => true, 'targetClass' => Group::class, 'targetAttribute' => ['group_id' => 'id']],
            [['slug'], 'unique', 'targetAttribute' => ['slug', 'language_id','group_id'], 'message' => Yii::t('app', 'This slug is already used for this language.')],
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
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'section_id' => Yii::t('app', 'Section'),
            'slug' => Yii::t('app', 'Slug'),
            'language' => Yii::t('app', 'Language'),
            'title' => Yii::t('app', 'Title'),
            'description' => Yii::t('app', 'Description'),
            'content' => Yii::t('app', 'Content'),
            'custom_js' => Yii::t('app', 'Custom Javascript'),
            'custom_css' => Yii::t('app', 'Custom Style'),
            'keywords' => Yii::t('app', 'Keywords'),
            'created_at' =>Yii::t('app', 'Created at'),
            'updated_at' =>Yii::t('app', 'Updated at'),
            'status' => Yii::t('app', 'Active'),
        ];
    }

    /**
     * Gets query for [[PageFiles]].
     *
     * @return \yii\db\ActiveQuery
     */

    public function getFiles() {
        return $this->hasMany(File::class, ['id' => 'file_id'])
          ->viaTable('page_files', ['page_id' => 'id']);
    }

    /**
     * Gets query for [[Section]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSection()
    {
        return $this->hasOne(Section::class, ['id' => 'section_id']);
    }


    /**
     * Gets query for [[Section]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLanguage()
    {
        return $this->hasOne(Language::class, ['id' => 'language_id']);
    }    
}
