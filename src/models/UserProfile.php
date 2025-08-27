<?php

namespace croacworks\essentials\models;

use croacworks\essentials\behaviors\AttachFileBehavior;
use Yii;

/**
 * This is the model class for table "user_profiles".
 *
 * @property int $id
 * @property int|null $user_id
 * @property int|null $file_id
 * @property int|null $language_id
 * @property string|null $theme
 * @property string|null $fullname
 * @property string|null $cpf_cnpj
 * @property string $phone
 * @property string $created_at
 * @property string $updated_at
 *
 * @property File $file
 * @property User $user
 */

class UserProfile extends ModelCommon
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_profiles';
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
    
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['fullname'], 'require'],
            [['user_id', 'file_id', 'fullname', 'cpf_cnpj'], 'default', 'value' => null],
            [['user_id'], 'integer'],
            [['fullname', 'phone'], 'string', 'max' => 255],
            [['cpf_cnpj'], 'string', 'max' => 18],
            [['file_id'], 'exist', 'skipOnError' => true, 'targetClass' => File::class, 'targetAttribute' => ['file_id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'file_id' => Yii::t('app', 'File ID'),
            'fullname' => Yii::t('app', 'Fullname'),
            'cpf_cnpj' => Yii::t('app', 'Cpf Cnpj'),
            'phone' => Yii::t('app', 'Phone'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
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

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Gets query for [[Language]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLanguage()
    {
        return $this->hasOne(Language::class, ['id'=>'language_id']);
    }

}
