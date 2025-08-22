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
            ['email', 'exist',
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
        $params = Configuration::get();
        $mailer =  AuthorizationController::mailer();
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
        
        $content = " 
            <tr>
                <td>
                <p>".Yii::t('app','Hello, {name}',['name'=>$user->fullname])."</p>
                <p>".Yii::t('app','Follow the link below to reset your password:')."</b></p>
                    
                <table role='presentation' border='0' cellpadding='0' cellspacing='0' class='btn btn-primary'>
                    <tbody>
                    <tr>
                        <td align='center'>
                        <table border='0' cellpadding='0' cellspacing='0' role='presentation' style='box-sizing:border-box'>
                            <tbody>
                                <tr>
                                    <td style='box-sizing:border-box'>
                                        <a href='{$resetLink}' target='_blank' rel='noopener noreferrer' data-auth='NotApplicable' class='x_button x_button-primary' style='box-sizing:border-box; border-radius:4px; color:#fff; display:inline-block; overflow:hidden; text-decoration:none; background-color:#2d3748; border-bottom:8px solid #2d3748; border-left:18px solid #2d3748; border-right:18px solid #2d3748; border-top:8px solid #2d3748' data-safelink='true' data-linkindex='1'>
                                        ".Yii::t('app','Reset Password')."
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <p><small>".Yii::t("backend","This message was sent automatically by the {title}, do not respond.",['title'=>$params->title])."</small></p>
                </td>
            </tr>
        ";
        return $mailer
            ->compose('layouts/template',
                ['user' => $user,'subject'=>$mailer->transport->getUsername(),'content'=>$content]
            )
            ->setFrom([$mailer->transport->getUsername()=> $params->title . ' robot'])
            ->setTo($this->email)
            ->setSubject('Reset Password - ' . $params->title)
            ->send();
    }
}
