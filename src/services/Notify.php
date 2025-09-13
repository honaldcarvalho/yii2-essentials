<?php
namespace croacworks\essentials\services;

use Yii;
use yii\db\Schema;
use croacworks\essentials\controllers\AuthorizationController;

/**
 * Serviço mínimo para criar notificações sem acoplar a nomes de colunas.
 * - Se existir `status`, usa como lido/não-lido (com ou sem constantes).
 * - Se NÃO existir `status`, tenta `is_read`.
 * - Títulos: tenta `title`, depois `subject`, depois `name`.
 * - Texto: `body` -> `message` -> `content`.
 * - Link: `url` -> `link` -> `href`.
 * - Datas: `created_at` se existir.
 * - Grupo/Usuário: `group_id` e `user_id` se existirem.
 */
class Notify
{
    /** @var class-string */
    public static string $modelClass = 'croacworks\\essentials\\models\\Notification';

    /**
     * Cria uma notificação não lida para $userId.
     * Retorna o AR salvo ou null.
     */
    public static function create(
        int $userId,
        string $title,
        ?string $text = null,
        ?string $url  = null,
        ?int $groupId = null
    ) {
        $cls = static::$modelClass;
        /** @var \yii\db\ActiveRecord $n */
        $n = new $cls();

        // introspeciona tabela/atributos com segurança
        $table = $cls::tableName();
        $schema = Yii::$app->db->schema->getTableSchema($table, true);
        $has = fn(string $attr) => property_exists($n, $attr) || ($schema && isset($schema->columns[$attr])) || $n->hasAttribute($attr);

        // user_id, group_id
        if ($has('user_id'))  $n->setAttribute('user_id',  $userId);
        if ($has('group_id')) $n->setAttribute('group_id', $groupId ?: AuthorizationController::userGroup());

        // título
        if ($has('title'))   $n->setAttribute('title', $title);
        elseif ($has('subject')) $n->setAttribute('subject', $title);
        elseif ($has('name'))    $n->setAttribute('name', $title);

        // corpo/texto
        if ($text !== null) {
            if     ($has('body'))    $n->setAttribute('body', $text);
            elseif ($has('message')) $n->setAttribute('message', $text);
            elseif ($has('content')) $n->setAttribute('content', $text);
        }

        // link
        if ($url !== null) {
            if     ($has('url'))  $n->setAttribute('url', $url);
            elseif ($has('link')) $n->setAttribute('link', $url);
            elseif ($has('href')) $n->setAttribute('href', $url);
        }

        // status / is_read
        $unread = null;
        if (defined($cls.'::STATUS_UNREAD')) {
            $unread = constant($cls.'::STATUS_UNREAD');
        } elseif (defined($cls.'::STATUS_NEW')) {
            $unread = constant($cls.'::STATUS_NEW');
        } else {
            $unread = 0; // fallback numérico
        }

        if ($has('status')) {
            $n->setAttribute('status', $unread);
        } elseif ($has('is_read')) {
            $n->setAttribute('is_read', 0);
        }

        // datas
        if ($has('created_at') && !$n->getAttribute('created_at')) {
            $n->setAttribute('created_at', date('Y-m-d H:i:s'));
        }

        // salve sem validar (ou troque para save() se já tem rules coesas)
        return $n->save(false) ? $n : null;
    }

    /**
     * Conta não lidas do usuário atual usando `status` quando existir.
     */
    public static function unreadCount(?int $userId = null): int
    {
        $cls = static::$modelClass;
        $userId = $userId ?: (int) Yii::$app->user->id;

        $q = $cls::find()->andWhere(['user_id' => $userId]);

        // multi-grupo, se coluna existir
        $schema = Yii::$app->db->schema->getTableSchema($cls::tableName(), true);
        $hasGroup = $schema && isset($schema->columns['group_id']);
        if ($hasGroup) {
            $q->andWhere(['group_id' => AuthorizationController::getUserGroups()]);
        }

        // filtra não lidas
        $hasStatus = $schema && isset($schema->columns['status']);
        if ($hasStatus) {
            // tenta constantes se existirem
            if (defined($cls.'::STATUS_UNREAD')) {
                $q->andWhere(['status' => constant($cls.'::STATUS_UNREAD')]);
            } elseif (defined($cls.'::STATUS_NEW')) {
                $q->andWhere(['status' => constant($cls.'::STATUS_NEW')]);
            } else {
                $q->andWhere(['status' => 0]);
            }
        } elseif ($schema && isset($schema->columns['is_read'])) {
            $q->andWhere(['is_read' => 0]);
        }

        return (int) $q->count();
    }

    /**
     * Marca como lida usando `status` (preferencial) ou `is_read`.
     */
    public static function markRead(int $id): bool
    {
        $cls = static::$modelClass;
        /** @var \yii\db\ActiveRecord|null $n */
        $n = $cls::findOne($id);
        if (!$n) return false;

        $schema = Yii::$app->db->schema->getTableSchema($cls::tableName(), true);
        $has = fn(string $attr) => ($schema && isset($schema->columns[$attr])) || $n->hasAttribute($attr);

        // segurança multi-tenant: user atual + group
        if ($has('user_id') && (int)$n->getAttribute('user_id') !== (int)Yii::$app->user->id) {
            return false;
        }
        if ($has('group_id')) {
            $groups = AuthorizationController::getUserGroups();
            if (!in_array((int)$n->getAttribute('group_id'), array_map('intval', $groups), true)) {
                return false;
            }
        }

        // aplicar lida
        if ($has('status')) {
            $read = defined($cls.'::STATUS_READ') ? constant($cls.'::STATUS_READ') : 1;
            $n->setAttribute('status', $read);
        } elseif ($has('is_read')) {
            $n->setAttribute('is_read', 1);
        }

        if ($has('read_at')) $n->setAttribute('read_at', date('Y-m-d H:i:s'));

        return $n->save(false);
    }
}
