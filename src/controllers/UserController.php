<?php

namespace croacworks\essentials\controllers;

use Yii;
use croacworks\essentials\models\File;
use croacworks\essentials\models\User;
use croacworks\essentials\models\Group;
use croacworks\essentials\models\UserGroup;
use croacworks\essentials\models\Language;
use croacworks\essentials\models\UserProfile;
use yii\bootstrap5\ActiveForm;
use yii\db\Transaction;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * UserController implements the CRUD actions for User model.
 */
class UserController extends AuthorizationController
{
    public function __construct($id, $module, $config = array())
    {
        parent::__construct($id, $module, $config);
        $this->free = ['change-theme'];
    }

    /**
     * Lists all User models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new User();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionChangeTheme()
    {
        $theme = Yii::$app->request->post('theme');
        $model =  self::User();
        if ($model !== null && $theme !== null && !empty($theme)) {
            $model->theme = $theme;
        }
        return $model->save();
    }

    public function actionChangeLang()
    {
        $lang = Yii::$app->request->post('lang');
        $model =  self::User();
        if ($model !== null && $lang !== null && !empty($lang)) {
            $model->language_id = Language::findOne(['code' => $lang])->id;
        }
        return $model->save();
    }

    /**
     * Displays a single User model.
     * @param int $id
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $user_group =  new UserGroup();

        $groups_free_arr = [];
        $group_selecteds = [];

        foreach (UserGroup::find()->select('group_id')->where(['user_id' => $model->id])->asArray()->all() as $group_selected) {
            $group_selecteds[] = $group_selected['group_id'];
        }

        $groups_free = Group::find(["status" => 1])->select('id,name')->where([
            'not in',
            'id',
            $group_selecteds
        ])->all();

        foreach ($groups_free as $group_arr) {
            $groups_free_arr[$group_arr['id']] = $group_arr['name'];
        }

        $groups = new \yii\data\ActiveDataProvider([
            'query' => UserGroup::find()->where(['user_id' => $id]),
            'pagination' => false,
        ]);

        return $this->render('view', [
            'groups' => $groups,
            'groups_free_arr' => $groups_free_arr,
            'group_selecteds' => $group_selecteds,
            'user_group' => $user_group,
            'model' => $model,
        ]);
    }

    public function actionProfile($id)
    {
        $model = $this->findModel($id);

        return $this->render(
            'profile',
            ['model' => $model]
        );
    }

    /**
     * Creates a new User with its UserProfile on the same screen.
     * @return string|Response
     */
    public function actionCreate()
    {
        $user = new User();
        $user->scenario = 'create';

        $profile = new UserProfile();

        if (
            Yii::$app->request->isAjax
            && $user->load(Yii::$app->request->post())
            && $profile->load(Yii::$app->request->post())
        ) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return array_merge(
                \yii\widgets\ActiveForm::validate($user),
                \yii\widgets\ActiveForm::validate($profile)
            );
        }

        if ($user->load(Yii::$app->request->post()) && $profile->load(Yii::$app->request->post())) {
            $name_array = explode(' ', $profile->fullname);
            $user->username = strtolower($name_array[0] . '_' . end($name_array)).'_'.Yii::$app->security->generateRandomString(8);

            $user->setPassword($user->password);
            $user->generateAuthKey();
            $isValid = $user->validate();
            $isValid = $profile->validate() && $isValid;

            if ($isValid) {
                $tx = Yii::$app->db->beginTransaction(\yii\db\Transaction::SERIALIZABLE);
                try {
                    if (!$user->save(false)) {
                        throw new \RuntimeException('Unable to save User.');
                    }

                    $profile->user_id = $user->id;
                    if (!$profile->save(false)) {
                        throw new \RuntimeException('Unable to save UserProfile.');
                    }

                    $tx->commit();
                    Yii::$app->session->setFlash('success', Yii::t('app', 'User created successfully.'));
                    return $this->redirect(['view', 'id' => $user->id]);
                } catch (\Throwable $e) {
                    $tx->rollBack();
                    Yii::error($e->getMessage(), __METHOD__);
                    Yii::$app->session->setFlash('error', Yii::t('app', 'Error while saving. Please try again.'));
                }
            }
        }

        return $this->render('create', [
            'model' => $user,
            'profile' => $profile,
        ]);
    }

    /**
     * Updates an existing User and its UserProfile on the same screen.
     * @param int $id
     * @return string|Response
     * @throws NotFoundHttpException
     */
    public function actionUpdate($id)
    {
        $user = $this->findModel($id);
        $user->scenario = 'update';

        $profile = $user->profile ?: new UserProfile(['user_id' => $user->id]);

        if (
            Yii::$app->request->isAjax
            && $user->load(Yii::$app->request->post())
            && $profile->load(Yii::$app->request->post())
        ) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return array_merge(
                \yii\widgets\ActiveForm::validate($user),
                \yii\widgets\ActiveForm::validate($profile)
            );
        }

        if ($user->load(Yii::$app->request->post()) && $profile->load(Yii::$app->request->post())) {

            $isValid = $user->validate();
            $isValid = $profile->validate() && $isValid;

            if ($isValid) {
                $tx = Yii::$app->db->beginTransaction(\yii\db\Transaction::SERIALIZABLE);
                try {
                    if (!$user->save(false)) {
                        throw new \RuntimeException('Unable to save User.');
                    }

                    $profile->user_id = $user->id;
                    if (!$profile->save(false)) {
                        throw new \RuntimeException('Unable to save UserProfile.');
                    }

                    $tx->commit();
                    Yii::$app->session->setFlash('success', Yii::t('app', 'User updated successfully.'));
                    return $this->redirect(['view', 'id' => $user->id]);
                } catch (\Throwable $e) {
                    $tx->rollBack();
                    Yii::error($e->getMessage(), __METHOD__);
                    Yii::$app->session->setFlash('error', Yii::t('app', 'Error while saving. Please try again.'));
                }
            }
        }

        return $this->render('update', [
            'model' => $user,
            'profile' => $profile,
        ]);
    }

    public function actionEdit()
    {
        $model = $this->findModel(Yii::$app->user->id);
        $model->username_old = $model->username;
        $model->email_old = $model->email;

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            $post = Yii::$app->request->post();
            $model->resetPassword();
            return $this->redirect(['profile', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionAddGroup()
    {

        $post = Yii::$app->request->post();
        $i = 1;
        foreach ($post['UserGroup']['group_id'] as $group) {
            $model = new UserGroup();
            $model->user_id = $post['UserGroup']['user_id'];
            $model->group_id = $group;
            $model->save();
            $i++;
        }

        return $this->redirect(['user/view/' . $model->user_id]);
    }

    public function actionRemoveGroup($id)
    {
        $model = UserGroup::findOne(['id' => $id]);
        $user_id = $model->user_id;
        $model->delete();

        return $this->redirect(['/user/view/' . $user_id]);
    }
    /**
     * Deletes an existing User model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        if ($id != 1) {
            $user = $this->findModel($id);
            if ($user->file_id !== null) {
                $picture = File::findOne($user->file_id);
                if (file_exists($picture->path)) {
                    @unlink($picture->path);
                    if ($picture->pathThumb) {
                        @unlink($picture->pathThumb);
                    }
                }
            }
            $delete = $user->delete();
        } else {
            \Yii::$app->session->setFlash('error', 'Is not possible exclude master user');
        }
        if ($delete) {
            \Yii::$app->session->setFlash('success', 'User removed');
        }
        return $this->redirect(['index']);
    }


    protected function findModel($id, $model = null)
    {
        if (isset(Yii::$app->user->identity) && !$this::isAdmin()) {
            return Yii::$app->user->identity;
        } else if (($model = User::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
