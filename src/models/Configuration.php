<?php

namespace croacworks\essentials\models;

use croacworks\essentials\behaviors\AttachFileBehavior;
use croacworks\essentials\controllers\AuthorizationController;
use Yii;
use yii\symfonymailer\Mailer;

/**
 * This is the model class for table "Configuration".
 *
 * @property int $id
 * @property string $description
 * @property int $language_id
 * @property int|null $file_id
 * @property int|null $group_id
 * @property int|null $email_service_id
 * @property string $host
 * @property string $homepage
 * @property string $title
 * @property string $slogan
 * @property string $bussiness_name
 * @property string $email
 * @property string $email_template
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property int|null $status
 * @property int|null $logging
 *
 * @property EmailService $emailService
 * @property File $file
 * @property Language $language
 * @property Group $group
 */
class Configuration extends ModelCommon
{
    public $verGroup = false;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'configurations';
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

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        return $scenarios;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['description', 'host', 'title', 'slogan', 'bussiness_name', 'email'], 'required', 'on' => ['create', 'update']],
            [['file_id'], 'default', 'value' => null],
            [['language_id', 'group_id', 'email_service_id', 'status', 'logging'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['description', 'host', 'title', 'bussiness_name', 'email'], 'string', 'max' => 255],
            [['email_service_id'], 'exist', 'skipOnError' => true, 'targetClass' => EmailService::class, 'targetAttribute' => ['email_service_id' => 'id']],
            [['language_id'], 'exist', 'skipOnError' => true, 'targetClass' => Language::class, 'targetAttribute' => ['language_id' => 'id']],
            [['file_id'], 'exist', 'skipOnError' => true, 'targetClass' => File::class, 'targetAttribute' => ['file_id' => 'id']],
            [['group_id'], 'exist', 'skipOnError' => true, 'targetClass' => Group::class, 'targetAttribute' => ['group_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'description' => Yii::t('app', 'Description'),
            'language_id' => Yii::t('app', 'Site Language'),
            'file_id' => Yii::t('app', 'Logo'),
            'group_id' => Yii::t('app', 'Client\'s Group'),
            'email_service_id' => Yii::t('app', 'Email Service'),
            'host' => Yii::t('app', 'Host'),
            'homepage' => Yii::t('app', 'Homepage'),
            'title' => Yii::t('app', 'Title'),
            'slogan' => Yii::t('app', 'Slogan'),
            'bussiness_name' => Yii::t('app', 'Bussines Name'),
            'email' => Yii::t('app', 'Email'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'status' => Yii::t('app', 'Status'),
            'email_template' => Yii::t('app', 'Email Template'),
            'logging' => Yii::t('app', 'Logging'),
        ];
    }

    /**
     * Gets query for [[EmailService]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEmailService()
    {
        return $this->hasOne(EmailService::class, ['id' => 'email_service_id']);
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
     * Gets query for [[Group]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGroup()
    {
        return $this->hasOne(Group::class, ['id' => 'group_id']);
    }

    /**
     * Gets query for [[Language]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLanguage()
    {
        return $this->hasOne(Language::class, ['id' => 'language_id']);
    }

    /**
     * Gets query for [[EmailService]].
     *
     * @return \yii\db\ActiveQuery
     */
    public static function get()
    {
        $hostName = Yii::$app->request->hostName;

        // Tenta buscar pela coluna "host"
        $model = self::find(false)->where(['host' => $hostName])->one();

        // Se não encontrar, busca pelo grupo do usuário
        // if (!$model) {
        //     $model = self::find(false)->where(['id' => AuthorizationController::userGroup()])->one();
        // }

        // Se ainda não encontrar, tenta buscar o id 1
        if (!$model) {
            $model = self::find(false)->where(['id' => 1])->one();
        }

        // Retorna o encontrado ou uma nova instância
        return $model ?? new static();
    }

    public function getParameters()
    {
        return $this->hasMany(Parameter::class, ['configuration_id' => 'id']);
    }

    public function getMetaTags()
    {
        return $this->hasMany(MetaTag::class, ['configuration_id' => 'id']);
    }

    static function mailer()
    {

        $params = self::get();
        $mailer = new Mailer();
        $model = $params->emailService;

        $mailer->transport = [
            'scheme' => $model->scheme,
            'host' => $model->host,
            'username' => $model->username,
            'password' => $model->password,
            'port' => $model->port,
            'enableMailerLogging' => true
            //'dsn' => "{$model->scheme}://{$model->username}:{$model->password}@{$model->host}:{$model->port}"
        ];

        return $mailer;
    }
}
