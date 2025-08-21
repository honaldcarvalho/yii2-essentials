<?php

namespace croacworks\essentials\models;

use Yii;
use croacworks\essentials\controllers\AuthorizationController;
use yii\symfonymailer\Mailer;

/**
 * This is the model class for table "email_services".
 *
 * @property int $id
 * @property string $description
 * @property string $scheme
 * @property int|null $enable_encryption
 * @property string $encryption
 * @property string $host
 * @property string $username
 * @property string $password
 * @property int $port
 */
class EmailService extends ModelCommon
{
    public $verGroup = false;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'email_services';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['enable_encryption', 'port'], 'integer'],
            [['host', 'username', 'password', 'port'], 'required'],
            [['description', 'scheme', 'encryption', 'host', 'username', 'password'], 'string', 'max' => 255],
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
            'scheme' => Yii::t('app', 'Scheme'),
            'enable_encryption' => Yii::t('app', 'Enable Encryption'),
            'encryption' => Yii::t('app', 'Encryption'),
            'host' => Yii::t('app', 'Host'),
            'username' => Yii::t('app', 'Username'),
            'password' => Yii::t('app', 'Password'),
            'port' => Yii::t('app', 'Port'),
        ];
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($old_password = '')
    {
        $password_hash = md5($this->password);

        if(md5($old_password) != $password_hash){
            try {
                $this->password = $password_hash;
                return true;
            } catch (\Throwable $th) {
                return false;
            }
        }
        return true;
    }

    /**
     * Sends an email with a link, for resetting the password.
     *
     * @return bool whether the email was send
     */
    public static function sendEmail(
        $subject,
        $from_name,
        $to,
        $content,
        $cc = '',
        $from = '',
        $layout= 'layouts/template')
    {

        $message_str = '';
        $response = false;
        $mailer =  AuthorizationController::mailer();
        if(empty($from)){
            $from = $mailer->transport->getUsername();
        }
        $mailer_email = $mailer->compose('@vendor/croacworks/yii2-essentials/src/mail/layouts/template', ['subject' => $subject, 'content' => $content]);
        $mailer_email->setFrom([$from=> $from_name])->setTo($to)
        ->setSubject($subject);
        if(!empty($cc)){
            $mailer_email->setCc($cc);
        }
        $response = $mailer_email->send();


        foreach (\Yii::getLogger()->messages as $key => $message) {
            if($message[2] == 'yii\symfonymailer\Mailer::sendMessage'){
                $message_str .= $message[2] .'|'.$message[0]."/";
                \Yii::$app->session->setFlash('error', 'Occoured some error: '.$message[0]);
            }
        }

        

        return ['result' => $response,'message' => $message_str];
    }

    public static function sendEmails(
        $subject,
        $from_email,
        $from_name,
        $to,
        $content,
        $layout= 'layouts/template')
    {
        $model = EmailService::findOne(1);
        $params = Configuration::get();
        $mailer = new Mailer();

        $mailer->transport = [
            'scheme' => $model->scheme,
            'host' => $model->host,
            'encryption' => $model->enable_encryption ? $model->encryption : '',
            'username' => $model->username,
            'password' => $model->password,
            'port' => $model->port,
            'enableMailerLogging'=>true
        ];
        

        $message = $mailer->compose('@vendor/croacworks/yii2-essentials/src/mail/layouts/template', ['subject' => $subject, 'content' => $content]);
        $response = $message->setFrom($model->username)->setTo($to)
        ->setSubject(Yii::t('app', $subject))
        ->send();

        if($response) {
            \Yii::$app->session->setFlash('success', "Email sended to {$params->email}.  See you email.");
        }else{
            foreach (Yii::getLogger()->messages as $key => $message) {
                if($message[2] == 'yii\symfonymailer\Mailer::sendMessage'){
                    \Yii::$app->session->setFlash('error', 'Occoured some error: '.$message[0]);
                }
            }

        }
        return $response;
    }

}
