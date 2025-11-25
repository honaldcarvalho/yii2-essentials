<?php

namespace croacworks\essentials\controllers\rest;

use Yii;
use yii\web\ForbiddenHttpException;
use croacworks\essentials\models\User;
use croacworks\essentials\models\Configuration;
use croacworks\essentials\models\UserGroup;

class AuthController extends ControllerRest
{

    public $modelClass = 'croacworks\essentials\models\User';

    public function init(): void
    {
        parent::init();
        \Yii::$app->user->enableSession = false;
    }

    public function behaviors()
    {
        $this->free[] = 'login';
        $behaviors = parent::behaviors();
        return $behaviors;
    }

    public function actionLogin()
    {

        $post = $this->request->post();

        if (isset($post['login']) && isset($post['password'])) {
            $user = User::find()->where(['username' => $post['login']])->orWhere(['email' => $post['login']])->one();
            if ($user) {

                $user->username = $post['login'];
                $user->password = $post['password'];

                if ($user && \Yii::$app->security->validatePassword($user->password, $user->password_hash)) {
                    $extends = '+30 days';
                    $expires = strtotime($extends, strtotime($user->token_validate));
                    if ($expires < time() ||  $user->access_token == null) {
                        $user->scenario = $user::SCENARIO_AUTH;
                        $user->access_token = Yii::$app->security->generateRandomString();
                        $user->token_validate = date('Y-m-d H:i:s', strtotime('now'));
                        $user->save();
                    }

                    return [
                        'fullname' => $user->profile->fullname,
                        'username' => $user->username,
                        'email' => $user->email,
                        'access_token' => $user->access_token,
                        'token_validate' => $user->token_validate
                    ];
                }
            }
        }

        throw new ForbiddenHttpException(Yii::t('app', 'Your request was made with invalid credentials.'));
    }

    public function actionList()
    {
        return User::findAll(['status' => 10]);
    }

    public function actionSignup()
    {
        if (!$this->validate()) {
            return null;
        }
        $params = Configuration::get();
        $user = new User();
        $user_group = new UserGroup();
        $user_group->group_id = $params->group_id;
        $user->fullname = $this->fullname;

        $name_split = explode(' ', $user->fullname);
        $username = $name_split[0] . (isset($name_split[1]) ? ' ' . end($name_split) : '');
        $user->username = $this->sanatizeReplace(strtolower($username)) . '_' . date('ymdhims');
        $user->email = $this->email;
        $user->company = $this->company;
        $user->cpf_cnpj = $this->cpf_cnpj;
        $user->phone = $this->phone;
        $user->language_id = $this->language_id;
        $user->setPassword($this->password);
        $user->generateAuthKey();
        $user->generateEmailVerificationToken();

        if ($user->save()) {
            $user_group->user_id = $user->id;
            $user_group->save();
            return $this->sendEmail($user, $params);
        }
    }

    static function getUserByToken()
    {
        $user = null;
        $token = Yii::$app->request->headers["authorization"];
        if ($token !== null) {
            [$type, $value] = explode(' ', $token);
            if ($type == 'Bearer') {
                $user = User::find()->where(['status' => User::STATUS_ACTIVE])->filterwhere(['or', ['access_token' => $value], ['auth_key' => $value]])->one();
            }
        }
        return $user;
    }
}
