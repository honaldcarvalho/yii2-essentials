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
 * @property int|null $model_group_id
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
 * @property int|null $list
 * @property int|null $status
 *
 * @property PageFiles[] $pageFiles
 * @property PageSection $pageSection
 * @property Group $group
 * @property File $file
 */
class Page extends ModelCommon
{
    public $verGroup = true;
    private $_oldSlug;
    /** @var int[] Selected tag IDs (form helper) */
    public $tagIds = [];

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
            [['page_section_id', 'list', 'status'], 'integer'],
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

    public function afterFind()
    {
        parent::afterFind();
        $this->_oldSlug = $this->slug;
        $this->tagIds = $this->getTags()->select('id')->column();
    }


    public function beforeValidate()
    {
        if (!parent::beforeValidate()) {
            return false;
        }

        // Se for um novo registro, forçar model_group_id = 0 se estiver nulo
        if ($this->isNewRecord && empty($this->model_group_id)) {
            $this->model_group_id = 0;
        }

        // Correção 3: Se é um novo registro E tem um slug (veio de um clone),
        // ou se o slug está vazio, gere o novo slug.
        if ($this->isNewRecord) {
            if (!empty($this->slug)) {
                // Força a regeneração do slug para garantir que seja único em um clone.
                // Isso cobre o caso em que o slug foi copiado via $source->attributes
                $this->slug = $this->generateUniqueSlug($this->slug);
            } elseif (empty($this->slug)) {
                // Caso em que o slug está realmente vazio (comportamento original)
                $this->slug = $this->generateUniqueSlug($this->slug);
            }
        } elseif ($this->_oldSlug !== $this->slug) {
            // Se o slug foi modificado manualmente (comportamento original)
            $this->slug = $this->generateUniqueSlug($this->slug);
        }

        return true;
    }

    public function getIsGroupOwner(): bool
    {
        return (int)$this->id === (int)$this->model_group_id;
    }

    public function generateUniqueSlug(?string $baseTitle = null): string
    {
        $base = trim((string)($baseTitle ?? $this->title ?? $this->description ?? $this->id));
        $seed = Inflector::slug($base);
        if ($seed === '') {
            $seed = (string)$this->id; // fallback duro
        }

        $try = $seed;
        $i = 2;

        while (static::find()
            ->andWhere(['page_section_id' => $this->page_section_id, 'slug' => $try])
            ->andFilterWhere(['<>', 'id', $this->id])
            ->exists()
        ) {
            $try = $seed . '-' . $i++;
        }
        return $try;
    }

    /** Sync pivot after save */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        // Garante que model_group_id seja atualizado se 0. Isso deve ocorrer apenas para o cloneTotal
        if ($insert && (int)$this->model_group_id === 0) {
            static::updateAll(['model_group_id' => $this->id], ['id' => $this->id]);
            $this->model_group_id = $this->id;
        }

        // 1) Transform inputs (ids ou nomes) em IDs válidos
        $resolvedIds = [];
        foreach ((array)$this->tagIds as $val) {
            $val = is_string($val) ? trim($val) : $val;

            if ($val === '' || $val === null) {
                continue;
            }

            if (ctype_digit((string)$val)) {
                // já é ID
                $resolvedIds[] = (int)$val;
                continue;
            }

            // é um "nome" digitado -> criar/achar tag
            $name = $val;
            $slug = Inflector::slug($name);

            $tag = Tag::find()->where(['slug' => $slug])->one();
            if ($tag === null) {
                $tag = new Tag([
                    'name'   => $name,
                    'slug'   => $slug,
                    'status' => 1,
                ]);
                // validação com i18n nas mensagens já está no model Tag
                if (!$tag->save()) {
                    // se falhar, pula silenciosamente (ou loga)
                    \Yii::error(['tag_create_failed' => $tag->errors, 'name' => $name], __METHOD__);
                    continue;
                }
            }

            $resolvedIds[] = (int)$tag->id;
        }

        // 2) Sincroniza pivô
        $resolvedIds = array_values(array_unique(array_map('intval', $resolvedIds)));
        $currentIds  = $this->getTags()->select('id')->column();

        $toAdd = array_diff($resolvedIds, $currentIds);
        $toDel = array_diff($currentIds, $resolvedIds);

        if ($toDel) {
            \Yii::$app->db->createCommand()
                ->delete('{{%page_tags}}', ['page_id' => $this->id, 'tag_id' => $toDel])
                ->execute();
        }
        foreach ($toAdd as $id) {
            \Yii::$app->db->createCommand()
                ->insert('{{%page_tags}}', ['page_id' => $this->id, 'tag_id' => $id])
                ->execute();
        }
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
            'model_group_id' => Yii::t('app', 'Model Group'),
            'slug' => Yii::t('app', 'Slug'),
            'language' => Yii::t('app', 'Language'),
            'title' => Yii::t('app', 'Title'),
            'description' => Yii::t('app', 'Description'),
            'content' => Yii::t('app', 'Content'),
            'custom_js' => Yii::t('app', 'Custom Javascript'),
            'custom_css' => Yii::t('app', 'Custom Style'),
            'keywords' => Yii::t('app', 'Keywords'),
            'list' => Yii::t('app', 'List?'),
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
        return $this->hasOne(PageSection::class, ['id' => 'page_section_id'])->alias('pageSection');
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

    /**
     * Gets query for [[File]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFile()
    {
        return $this->hasOne(File::class, ['id' => 'file_id']);
    }
}
