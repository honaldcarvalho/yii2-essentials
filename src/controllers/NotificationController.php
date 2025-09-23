<?php

namespace croacworks\essentials\controllers;

use Yii;
use croacworks\essentials\models\Notification;
use yii\data\ActiveDataProvider;
use yii\helpers\HtmlPurifier;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * NotificationController implements the CRUD actions for Notification model.
 */
class NotificationController extends AuthorizationController
{
    public $enableCsrfValidation = false; // se for usar via fetch; habilite se enviar CSRF
    public $guest = ['list', 'index', 'view', 'read', 'delete', 'delete-all'];
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


    public function actionBroadcast()
    {
        $model = new \croacworks\essentials\models\forms\NotificationBroadcastForm();

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            /** @var \croacworks\essentials\services\Notify $notify */
            $notify = Yii::$app->notify;

            $title   = (string)$model->title;
            $content = $model->content !== '' ? (string)$model->content : null;
            $type    = (string)$model->type;
            $url     = $model->url !== '' ? (string)$model->url : null;
            $expo    = (bool)$model->push_expo;
            $edata   = (array)$model->expoData();

            $created = 0;

            if ($model->recipient_mode === 'user') {
                $uid = (int)$model->user_id;
                if (!$uid) {
                    Yii::$app->session->setFlash('danger', Yii::t('app', 'Invalid user.'));
                    return $this->refresh();
                }

                $persistGroupId = (int)(static::currentGroupId() ?? 0) ?: null;

                if ($expo) {
                    $n = $notify->createAndPush($uid, $title, $content, $type, $url, $persistGroupId, null, $edata);
                    $created = $n ? 1 : 0;
                } else {
                    $created = $notify->createForUsers([$uid], $title, $content, $type, $url, $persistGroupId);
                }

                Yii::$app->session->setFlash(
                    $created ? 'success' : 'danger',
                    $created
                        ? Yii::t('app', 'Notification sent to 1 user.')
                        : Yii::t('app', 'Failed to send to the user.')
                );
                return $this->refresh();
            }

            if ($model->recipient_mode === 'group') {
                $gid = (int)$model->group_id;
                if (!$gid) {
                    Yii::$app->session->setFlash('danger', Yii::t('app', 'Invalid group.'));
                    return $this->refresh();
                }
                $created = $notify->createForGroup(
                    $gid,
                    $title,
                    $content,
                    $type,
                    $url,
                    (bool)$model->include_children,
                    null,           // persistGroupId
                    $expo,
                    $edata
                );
                Yii::$app->session->setFlash(
                    $created ? 'success' : 'warning',
                    $created
                        ? Yii::t('app', 'Notification sent to {n} user(s) in the group.', ['n' => $created])
                        : Yii::t('app', 'No recipients found in the group.')
                );
                return $this->refresh();
            }

            if ($model->recipient_mode === 'all_global') {
                // optional guard: only master can blast the entire system
                if (!static::isMaster()) {
                    Yii::$app->session->setFlash('danger', Yii::t('app', 'You are not allowed to send to all users (global).'));
                    return $this->refresh();
                }

                /** @var \croacworks\essentials\services\Notify $notify */
                $notify = Yii::$app->notify;

                $persistGroupId = (int)(static::currentGroupId() ?? 0) ?: null; // origin marker (optional)
                $created = $notify->createForAllUsers(
                    (string)$model->title,
                    $model->content !== '' ? (string)$model->content : null,
                    (string)$model->type,
                    $model->url !== '' ? (string)$model->url : null,
                    $persistGroupId,
                    (bool)$model->push_expo,
                    (array)$model->expoData()
                );

                Yii::$app->session->setFlash(
                    $created ? 'success' : 'warning',
                    $created
                        ? Yii::t('app', 'Notification sent to {n} user(s) globally.', ['n' => $created])
                        : Yii::t('app', 'No recipients found.')
                );
                return $this->refresh();
            }

            // 'all' → send to the current scope (root + all descendants)
            $current = static::currentGroupId();
            if (!$current) {
                Yii::$app->session->setFlash('danger', Yii::t('app', 'Current group is not defined.'));
                return $this->refresh();
            }
            $rootId = \croacworks\essentials\models\Group::getRootId((int)$current);

            $created = $notify->createForGroup(
                (int)$rootId,
                $title,
                $content,
                $type,
                $url,
                true,   // include children
                null,
                $expo,
                $edata
            );
            Yii::$app->session->setFlash(
                $created ? 'success' : 'warning',
                $created
                    ? Yii::t('app', 'Notification sent to {n} user(s) in the scope.', ['n' => $created])
                    : Yii::t('app', 'No recipients found in the scope.')
            );
            return $this->refresh();
        }

        // Simple lists (for demo). For large datasets use Select2 with AJAX.
        $users  = \croacworks\essentials\models\User::find()->select(['id', 'username', 'email'])->orderBy(['id' => SORT_ASC])->limit(500)->asArray()->all();
        $groups = \croacworks\essentials\models\Group::find()->select(['id', 'name'])->orderBy(['name' => SORT_ASC])->asArray()->all();

        $userItems = [];
        foreach ($users as $u) {
            $label = trim(($u['name'] ?? '') . ' ' . ($u['username'] ? "(@{$u['username']})" : '') . ' ' . ($u['email'] ? "<{$u['email']}>" : ''));
            $label = preg_replace('/\s+/', ' ', $label);
            $userItems[(int)$u['id']] = $label ?: ('#' . $u['id']);
        }

        $groupItems = [];
        foreach ($groups as $g) {
            $groupItems[(int)$g['id']] = $g['name'] ?: ('#' . $g['id']);
        }

        return $this->render('broadcast', [
            'model'      => $model,
            'userItems'  => $userItems,
            'groupItems' => $groupItems,
        ]);
    }

    public function actionView($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $n = Notification::find()
            ->where(['id' => (int)$id])->one();

        if (!$n) return ['success' => false, 'message' => Yii::t('app', 'Notification not found.')];

        if ((int)$n->status === 1) {
            $n->status = 0;
            $n->save(false);
        }

        return [
            'success' => true,
            'notification' => [
                'id' => $n->id,
                'title' => Yii::t('app', $n->description ?: 'Notification'),
                'message' => Yii::t('app', $n->content ?: $n->description),
                'type' => $n->type,
                'created_at' => Yii::$app->formatter->asDatetime($n->created_at),
            ]
        ];
    }

    public function actionDelete($id = null)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $userId = Yii::$app->user->id;
        if (!$userId) throw new \yii\web\BadRequestHttpException('Not authenticated.');
        if ($id === null || !ctype_digit((string)$id)) throw new \yii\web\BadRequestHttpException('Invalid id.');

        $model = \croacworks\essentials\models\Notification::find()
            ->where(['id' => (int)$id, 'recipient_type' => 'user', 'recipient_id' => (int)$userId])->one();

        if (!$model) throw new \yii\web\NotFoundHttpException('Notification not found.');

        $ok = (bool)$model->delete();
        return $this->asJson(['ok' => $ok]);
    }

    public function actionDeleteAll()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $userId = Yii::$app->user->id;
        if (!$userId) throw new \yii\web\BadRequestHttpException('Not authenticated.');

        $onlyRead = (int)Yii::$app->request->post('onlyRead', 0) === 1;
        $cond = ['recipient_type' => 'user', 'recipient_id' => (int)$userId];
        if ($onlyRead) $cond['status'] = \croacworks\essentials\models\Notification::STATUS_READ;

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
