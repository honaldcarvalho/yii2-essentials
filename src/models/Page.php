<?php

namespace croacworks\essentials\models;

use croacworks\essentials\behaviors\AttachFileBehavior;
use croacworks\essentials\models\Language;
use Yii;
use yii\helpers\Inflector;

/**
 * This is the model class for table "pages".
 *
 * @property int $id
 * @property int|null $group_id
 * @property int|null $page_section_id
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
 * @property PageSection $pageSection
 * @property Group $group
 */
class Page extends ModelCommon
{
    public $verGroup = true;
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
            [['page_section_id', 'status'], 'integer'],
            [['title'], 'required', 'on' => self::SCENARIO_DEFAULT],
            [['content', 'keywords', 'custom_js', 'custom_css', 'language_id'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['slug', 'title'], 'string', 'max' => 255],
            [['description'], 'string', 'max' => 300],
            [['page_section_id'], 'exist', 'skipOnError' => true, 'targetClass' => PageSection::class, 'targetAttribute' => ['page_section_id' => 'id']],
            [['language_id'], 'exist', 'skipOnError' => true, 'targetClass' => Language::class, 'targetAttribute' => ['language_id' => 'id']],
            [['group_id'], 'exist', 'skipOnError' => true, 'targetClass' => Group::class, 'targetAttribute' => ['group_id' => 'id']],
            [
                ['slug', 'group_id', 'language_id', 'page_section_id'],
                'unique',
                'targetAttribute' => ['slug', 'group_id', 'language_id', 'page_section_id'],
                'message' => Yii::t('app', 'Already exists a page with this Slug/Group/Language/Section combination.')
            ],
            [['file_id'], 'exist', 'skipOnError' => true, 'targetClass' => File::class, 'targetAttribute' => ['file_id' => 'id']],
        ];
    }

    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            [
                'class' => AttachFileBehavior::class,
                'attribute' => 'file_id',
                'removeFlagParam' => 'remove',
                'deleteOldOnReplace' => true,
                'deleteOnOwnerDelete' => false,
                'debug' => true, // ligue por enquanto
            ],
        ]);
    }

    public function beforeValidate()
    {
        if (!parent::beforeValidate()) return false;

        if (empty($this->slug)) {
            // usa title, cai para description e por fim id
            $baseStr = $this->title ?: $this->description ?: (string)$this->id;
            $base    = Inflector::slug($baseStr) ?: (string)$this->id;

            $try = $base;
            $i = 2;
            while (self::find()
                ->andWhere(['post_section_id' => $this->post_section_id, 'slug' => $try])
                ->andFilterWhere(['<>', 'id', $this->id])
                ->exists()
            ) {
                $try = $base . '-' . $i++;
            }
            $this->slug = $try;
        }
        return true;
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
            'file_id' => Yii::t('app', 'Cover'),
            'page_section_id' => Yii::t('app', 'Page Section'),
            'slug' => Yii::t('app', 'Slug'),
            'language' => Yii::t('app', 'Language'),
            'title' => Yii::t('app', 'Title'),
            'description' => Yii::t('app', 'Description'),
            'content' => Yii::t('app', 'Content'),
            'custom_js' => Yii::t('app', 'Custom Javascript'),
            'custom_css' => Yii::t('app', 'Custom Style'),
            'keywords' => Yii::t('app', 'Keywords'),
            'created_at' => Yii::t('app', 'Created at'),
            'updated_at' => Yii::t('app', 'Updated at'),
            'status' => Yii::t('app', 'Active'),
        ];
    }

    public function getPageFiles()
    {
        return $this->hasMany(PageFile::class, ['page_id' => 'id'])
            ->inverseOf('page')
            ->with('file');
    }

    public function getFiles()
    {
        return $this->hasMany(File::class, ['id' => 'file_id'])
            ->via('pageFiles');
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
    public function getLanguage()
    {
        return $this->hasOne(Language::class, ['id' => 'language_id']);
    }
}
