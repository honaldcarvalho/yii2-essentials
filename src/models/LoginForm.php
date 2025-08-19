<?php
namespace croacworks\essentials\models\forms;

use Yii;
use yii\base\Model;

/**
 * LoginForm for handling authentication.
 * Uses AuthService (croacworks\essentials\components\auth\AuthService).
 */
class LoginForm extends Model
{
    public $username;
    public $password;
    public $rememberMe = true;

    private $_user = false;

    public function rules(): array
    {
        return [
            [['username', 'password'], 'required',
                'message' => Yii::t('app', 'This field is required.')],
            ['rememberMe', 'boolean'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'username'   => Yii::t('app', 'Username or Email'),
            'password'   => Yii::t('app', 'Password'),
            'rememberMe' => Yii::t('app', 'Remember Me'),
        ];
    }

    /**
     * Attempts login using AuthService.
     */
    public function login(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $authService = Yii::$app->get('authService');

        $ok = $authService->login($this->username, $this->password, $this->rememberMe);
        if (!$ok) {
            $this->addError('password', Yii::t('app', 'Invalid username or password.'));
            return false;
        }

        return true;
    }
}
