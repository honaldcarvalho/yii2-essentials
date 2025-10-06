<?php

namespace croacworks\essentials\models;

use Yii;
use croacworks\essentials\models\City;
use croacworks\essentials\models\File;
use croacworks\essentials\models\Group;
use croacworks\essentials\models\State;
use croacworks\essentials\validators\CpfCnpjValidator;
use croacworks\essentials\validators\FullNameValidator;
use croacworks\essentials\validators\PostalCodeValidator;

/**
 * This is the model class for table "clients".
 *
 * @property int $id
 * @property int|null $group_id
 * @property string|null $file_id
 * @property string|null $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $identity_number
 * @property string|null $cpf_cnpj
 * @property int $state_id
 * @property int $city_id
 * @property string $street
 * @property string|null $district
 * @property int|null $number
 * @property string|null $postal_code
 * @property string $auth_key
 * @property string|null $username
 * @property string $password
 * @property string|null $password_reset_token
 * @property string|null $verification_token
 * @property string|null $access_token
 * @property string|null $token_validate
 * @property int $status
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property File    $file
 * @property State $state
 * @property City $city
 * @property Group $group
 */
class Client extends Account
{
    public $picture;
    public $verGroup = true;
    public $password_confirm;
    public $agree = false;
    const STATUS_DISABLED = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 3;
    const SCENARIO_UPDATE = 'update';
    const SCENARIO_REGISTER = 'register';
    const SCENARIO_PICTURE = 'picture';
    const SCENARIO_PASSWORD = 'password';

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_SEARCH] = ['id', 'group_id','state_id','city_id', 'street','identity_number', 'district', 'postal_code', 'status', 'created_at', 'updated_at', 'email', 'phone', 'fullname', 'cpf_cnpj', 'username'];
        $scenarios[self::SCENARIO_UPDATE] = ['state_id','city_id', 'street','identity_number', 'district', 'status', 'postal_code', 'email', 'phone', 'fullname'];
        $scenarios[self::SCENARIO_PICTURE] = ['file_id'];
        $scenarios[self::SCENARIO_PASSWORD] = ['password,password_confirm'];
        return $scenarios;
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'clients';
    }

/**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['fullname', FullNameValidator::class, 'on' => self::SCENARIO_DEFAULT],
            [['group_id','state_id', 'city_id', 'number','identity_number','status'], 'integer'],
            [['fullname', 'email', 'phone', 'identity_number', 'cpf_cnpj','state_id', 'city_id', 'street', 'district', 'postal_code', 'auth_key', 'username', 'password'], 'required', 'on' => self::SCENARIO_DEFAULT],
            [['created_at', 'updated_at'], 'safe'],
            ['cpf_cnpj', CpfCnpjValidator::class, 'on' => self::SCENARIO_DEFAULT],
            ['cpf_cnpj', 'string', 'max' => 18, 'on' => self::SCENARIO_DEFAULT],
            ['cpf_cnpj', 'unique', 'targetAttribute' => ['cpf_cnpj','group_id'], 'message' => Yii::t('app','This CPF/CNPJ has already been taken.')],
            [['file_id', 'fullname', 'email', 'phone', 'street', 'district', 'postal_code', 'username', 'password', 'password_reset_token', 'verification_token'], 'string', 'max' => 255],
            [['cpf_cnpj'], 'string', 'max' => 18],
            [['auth_key'], 'string', 'max' => 32],
            ['postal_code', PostalCodeValidator::class, 'on' => self::SCENARIO_DEFAULT],
            [['city_id'], 'exist', 'skipOnError' => true, 'targetClass' => City::class, 'targetAttribute' => ['city_id' => 'id']],
            [['state_id'], 'exist', 'skipOnError' => true, 'targetClass' => State::class, 'targetAttribute' => ['state_id' => 'id']],
            [['group_id'], 'exist', 'skipOnError' => true, 'targetClass' => Group::class, 'targetAttribute' => ['group_id' => 'id']],
            [['file_id'], 'exist', 'skipOnError' => true, 'targetClass' => File::class, 'targetAttribute' => ['file_id' => 'id']],
            ['agree', 'required', 'isEmpty' => function ($value) {
                return empty($value);
            }, 'on' => self::SCENARIO_REGISTER]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app','ID'),
            'group_id' => Yii::t('app','Group ID'),
            'file_id' => Yii::t('app','Picture'),
            'fullname' => Yii::t('app','Full Name'),
            'email' => Yii::t('app','Email'),
            'phone' => Yii::t('app','Phone'),
            'identity_number' => Yii::t('app','Identity Number'),
            'cpf_cnpj' => Yii::t('app','Cpf Cnpj'),
            'state_id' => Yii::t('app','State'),
            'city_id' => Yii::t('app','City'),
            'street' => Yii::t('app','Street'),
            'district' => Yii::t('app','District'),
            'number' => Yii::t('app','Number'),
            'address_complement' => Yii::t('app','Address Complement'),
            'notes' => Yii::t('app','Notes'),
            'postal_code' => Yii::t('app','Postal Code'),
            'auth_key' => Yii::t('app','Auth Key'),
            'username' => Yii::t('app','Username'),
            'password' => Yii::t('app','Password'),
            'password_reset_token' => Yii::t('app','Password Reset Token'),
            'verification_token' => Yii::t('app','Verification Token'),
            'status' => Yii::t('app','Status'),
            'created_at' => Yii::t('app','Created At'),
            'updated_at' => Yii::t('app','Updated At'),
        ];
    }

    /**
     * Gets query for [[City]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFile()
    {
        return $this->hasOne(File::class, ['id' => 'file_id']);
    }

    /**
     * Gets query for [[State]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getState()
    {
        return $this->hasOne(State::class, ['id' => 'state_id']);
    }

    /**
     * Gets query for [[City]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCity()
    {
        return $this->hasOne(City::class, ['id' => 'city_id']);
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

    /*** 
     * IMPLEMENTS IdentityInterface
     * */

    /**
     * {@inheritdoc}
     */

    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * {@inheritdoc}
     */

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['access_token' => $token]);
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

}
