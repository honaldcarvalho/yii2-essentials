<?php

namespace croacworks\essentials\controllers;

use Yii;
use croacworks\essentials\models\Notification;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * NotificationController implements the CRUD actions for Notification model.
 */
class NotificationController extends AuthorizationController
{
    public $enableCsrfValidation = false; // se for usar via fetch; habilite se enviar CSRF

    /**
     * Lists all Notification models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new Notification();
        $searchModel->scenario = Notification::SCENARIO_SEARCH;
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Notification model.
     * @param int $id ID
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Notification model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {


        if(Yii::$app->request->isPost){

            $post = Yii::$app->request->post();
            $success = 0;
            $error = 0;

            foreach ($post['Notification']['user_id'] as $key => $value) {
                $model = new Notification();
                $model->user_id = $value;
                $model->description = $post['Notification']['description'];
                $model->notification_message_id = $post['Notification']['notification_message_id'];
                if($model->save()){
                    $success++;
                }else{
                    dd($model->getErrors());
                    $error++;
                }
            }
            Yii::$app->session->setFlash("info", Yii::t('app', "Message sended to {success}. Fail send fail: {fail}",['success'=>$success,'fail'=>$error]));
            return $this->redirect(['index']);
        }

        $model = new Notification();
        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Notification model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Notification model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * GET /notification/list?limit=10
     * Retorna {count, items[]} para o usuário logado.
     */
    public function actionList(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $userId = Yii::$app->user->id;
        if (!$userId) throw new BadRequestHttpException('Not authenticated.');

        $limit = (int)Yii::$app->request->get('limit', 10);

        $query = Notification::find()
            ->where(['recipient_type'=>'user','recipient_id'=>$userId])
            ->orderBy(['created_at'=>SORT_DESC])
            ->limit($limit);

        $items = array_map(function(Notification $n){
            return [
                'id'          => (int)$n->id,
                'title'       => (string)$n->description,
                'content'     => (string)($n->content ?? ''),
                'url'         => (string)($n->url ?? ''),
                'status'      => (int)$n->status,
                'created_at'  => (string)$n->created_at,
                'read_at'     => (string)($n->read_at ?? ''),
            ];
        }, $query->all());

        $countUnread = (int) Notification::find()
            ->where(['recipient_type'=>'user','recipient_id'=>$userId,'status'=>Notification::STATUS_UNREAD])
            ->count();

        return ['count'=>$countUnread,'items'=>$items];
    }

    /**
     * POST /notification/read
     * Body: {id: number} ou {ids: number[]} ou {all: 1}
     */
    public function actionRead(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $userId = Yii::$app->user->id;
        if (!$userId) throw new BadRequestHttpException('Not authenticated.');

        $body = Yii::$app->request->post();
        $ids = [];
        if (isset($body['id']))  $ids = [(int)$body['id']];
        if (isset($body['ids']) && is_array($body['ids'])) $ids = array_map('intval', $body['ids']);
        $all = (int)($body['all'] ?? 0) === 1;

        $q = Notification::find()->where(['recipient_type'=>'user','recipient_id'=>$userId,'status'=>Notification::STATUS_UNREAD]);
        if (!$all && $ids) $q->andWhere(['id'=>$ids]);

        $rows = $q->all();
        foreach ($rows as $n) { $n->markAsRead(); }

        $countUnread = (int) Notification::find()
            ->where(['recipient_type'=>'user','recipient_id'=>$userId,'status'=>Notification::STATUS_UNREAD])
            ->count();

        return ['ok'=>true,'count'=>$countUnread];
    }
}
