<?php
namespace croacworks\essentials\components\auth;

use Yii;
use yii\base\BaseObject;
use yii\web\IdentityInterface;

/**
 * Serviço de autenticação mínimo.
 * - Procura usuário por username OU email (case-insensitive simples).
 * - Usa Yii::$app->user para efetivar o login.
 */
class AuthService extends BaseObject implements AuthServiceInterface
{
    /** @var class-string<IdentityInterface> */
    public $userModelClass = \croacworks\essentials\models\User::class;

    public function login(string $usernameOrEmail, string $password, bool $remember = false): bool
    {
        $class = $this->userModelClass;

        /** @var IdentityInterface|null $identity */
        $identity = $class::find()
            ->where(['or',
                ['username' => $usernameOrEmail],
                ['email'    => $usernameOrEmail],
            ])->andWhere(['status' => 1])
            ->one();

        if (!$identity) {
            return false;
        }

        // Precisa expor método validatePassword no model (ver User abaixo)
        if (method_exists($identity, 'validatePassword') && $identity->validatePassword($password)) {
            $duration = $remember ? 3600 * 24 * 30 : 0;
            return Yii::$app->user->login($identity, $duration);
        }

        return false;
    }

    public function logout(): void
    {
        Yii::$app->user->logout();
    }

    public function isGuest(): bool
    {
        return Yii::$app->user->isGuest;
    }

    public function userId(): ?int
    {
        return Yii::$app->user->id ?? null;
    }
}
