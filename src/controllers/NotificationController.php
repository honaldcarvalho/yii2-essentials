<?php

namespace croacworks\essentials\controllers;

use Yii;
use croacworks\essentials\models\Notification;
use yii\data\ActiveDataProvider;
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
    public function actionIndex(): string
    {
        $userId = Yii::$app->user->id;
        if (!$userId) {
            throw new BadRequestHttpException('Not authenticated.');
        }

        $query = Notification::find()
            ->where(['recipient_type' => 'user', 'recipient_id' => (int)$userId])
            ->orderBy(['created_at' => SORT_DESC]);

        // filtros simples opcionais
        $req = Yii::$app->request;
        $status = $req->get('status', null);
        if ($status !== null && $status !== '') {
            $query->andWhere(['status' => (int)$status]);
        }
        $type = $req->get('type', null);
        if ($type !== null && $type !== '') {
            $query->andWhere(['type' => (string)$type]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
            'sort' => [
                'defaultOrder' => ['created_at' => SORT_DESC],
                'attributes' => ['created_at', 'status', 'type', 'description'],
            ],
        ]);

        return $this->render('index', [
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


        if (Yii::$app->request->isPost) {

            $post = Yii::$app->request->post();
            $success = 0;
            $error = 0;

            foreach ($post['Notification']['user_id'] as $key => $value) {
                $model = new Notification();
                $model->user_id = $value;
                $model->description = $post['Notification']['description'];
                $model->notification_message_id = $post['Notification']['notification_message_id'];
                if ($model->save()) {
                    $success++;
                } else {
                    dd($model->getErrors());
                    $error++;
                }
            }
            Yii::$app->session->setFlash("info", Yii::t('app', "Message sended to {success}. Fail send fail: {fail}", ['success' => $success, 'fail' => $error]));
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

    public function actionDelete($id = null)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $userId = Yii::$app->user->id;
        if (!$userId) {
            throw new \yii\web\BadRequestHttpException('Not authenticated.');
        }
        if ($id === null || !ctype_digit((string)$id)) {
            throw new \yii\web\BadRequestHttpException('Invalid id.');
        }

        $model = \croacworks\essentials\models\Notification::find()
            ->where([
                'id'             => (int)$id,
                'recipient_type' => 'user',
                'recipient_id'   => (int)$userId,
            ])->one();

        if (!$model) {
            throw new \yii\web\NotFoundHttpException('Notificação não encontrada.');
        }

        $ok = (bool)$model->delete();
        return $this->asJson(['ok' => $ok]);
    }

    public function actionDeleteAll()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $userId = Yii::$app->user->id;
        if (!$userId) {
            throw new \yii\web\BadRequestHttpException('Not authenticated.');
        }

        $onlyRead = (int)Yii::$app->request->post('onlyRead', 0) === 1;

        $cond = ['recipient_type' => 'user', 'recipient_id' => (int)$userId];
        if ($onlyRead) {
            $cond['status'] = \croacworks\essentials\models\Notification::STATUS_READ;
        }

        $deleted = \croacworks\essentials\models\Notification::deleteAll($cond);
        return $this->asJson(['ok' => true, 'deleted' => (int)$deleted]);
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
            ->where(['recipient_type' => 'user', 'recipient_id' => $userId])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit);

        $items = array_map(function (Notification $n) {
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
            ->where(['recipient_type' => 'user', 'recipient_id' => $userId, 'status' => Notification::STATUS_UNREAD])
            ->count();

        return ['count' => $countUnread, 'items' => $items];
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

        $q = Notification::find()->where(['recipient_type' => 'user', 'recipient_id' => $userId, 'status' => Notification::STATUS_UNREAD]);
        if (!$all && $ids) $q->andWhere(['id' => $ids]);

        $rows = $q->all();
        foreach ($rows as $n) {
            $n->markAsRead();
        }

        $countUnread = (int) Notification::find()
            ->where(['recipient_type' => 'user', 'recipient_id' => $userId, 'status' => Notification::STATUS_UNREAD])
            ->count();

        return ['ok' => true, 'count' => $countUnread];
    }
}
