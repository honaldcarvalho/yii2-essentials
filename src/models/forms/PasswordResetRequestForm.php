<?php

namespace croacworks\essentials\models\forms;


use Yii;
use croacworks\essentials\controllers\AuthorizationController;
use croacworks\essentials\models\Configuration;
use croacworks\essentials\models\User;
use yii\base\Model;

/**
 * Password reset request form
 */
class PasswordResetRequestForm extends Model
{
    public $email;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['email', 'trim'],
            ['email', 'required'],
            ['email', 'email'],
            [
                'email',
                'exist',
                'targetClass' => '\croacworks\essentials\models\User',
                'filter' => ['status' => User::STATUS_ACTIVE],
                'message' => 'There is no user with this email address.'
            ],
        ];
    }

    /**
     * Sends an email with a link, for resetting the password.
     *
     * @return bool whether the email was send
     */
    public function sendEmail()
    {
        // Pega config e serviço de e-mail vinculado
        $cfg     = Configuration::get();
        /** @var \croacworks\essentials\models\EmailService $service */
        $service = $cfg->emailService;
        
        /* @var $user User */
        $user = User::findOne([
            'status' => User::STATUS_ACTIVE,
            'email' => $this->email,
        ]);

        if (!$user) {
            return false;
        }
        
        if (!User::isPasswordResetTokenValid($user->password_reset_token)) {
            $user->generatePasswordResetToken();
            if (!$user->save()) {
                return false;
            }
        }
        $resetLink = Yii::$app->urlManager->createAbsoluteUrl(['site/reset-password', 'token' => $user->password_reset_token]);
        
        // Conteúdo do corpo do e-mail (HTML parcial que vai no {{content}})
        $content = " 
            <p>" . Yii::t('app', 'Hello, {name}', ['name' => $user->profile->fullname]) . "</p>
            <p>" . Yii::t('app', 'Follow the link below to reset your password:') . "</p>
            <table role='presentation' border='0' cellpadding='0' cellspacing='0' class='btn'>
                <tr><td align='center'>
                    <a href='{$resetLink}' target='_blank' rel='noopener'>" . Yii::t('app', 'Reset Password') . "</a>
                </td></tr>
            </table>
            <p><small>" . Yii::t('app', "This message was sent automatically by the {title}, do not respond.", ['title' => $cfg->title]) . "</small></p>
        ";

        // Usa o método novo (template do banco)
        return $service->sendUsingTemplate(
            $this->email,
            Yii::t('app', 'Reset Password') . ' - ' . $cfg->title,
            $content,
            ['fromName' => $cfg->title . ' robot']
        );
    }
}
