<?php
namespace croacworks\essentials\services;

use croacworks\essentials\controllers\AuthorizationController;
use Yii;
use yii\db\Query;

class GrantScopeService
{
    /**
     * Retorna o escopo efetivo do usuário atual no formato:
     * [
     *   'App\\controllers\\FileController' => ['index','view','update'],
     *   'croacworks\\essentials\\controllers\\MenuController' => ['*'],
     * ]
     *
     * Regras:
     * - Junta roles por group_id (direto + tabela user_groups) E por user_id (roles diretas).
     * - Normaliza lista de actions por ';' e suporta wildcard '*'.
     */
    public static function currentUserGrantScope(): array
    {
        $user = Yii::$app->user->identity;
        if (!$user) return [];

        // ✅ Master: acesso total (curto-circuito)
        if (AuthorizationController::isMaster()) {
            // Não precisamos listar tudo; quem consumir pode tratar esse sentinel
            // ou simplesmente usar canGrant/canGrantAll que já respeitam o bypass.
            return ['*' => ['*']];
        }

        // Tabelas
        $ugTable      = class_exists(\croacworks\essentials\models\UserGroup::class)
            ? \croacworks\essentials\models\UserGroup::tableName()
            : '{{%user_groups}}';
        $groupsTable  = \croacworks\essentials\models\Group::tableName();
        $rolesTable   = \croacworks\essentials\models\Role::tableName();

        // 1) Groups do usuário: principal + extras de user_groups
        $groupIds = [];
        if (!empty($user->group_id)) $groupIds[] = (int)$user->group_id;

        $extraGroupIds = (new Query())
            ->select(['ug.group_id'])
            ->from("$ugTable ug")
            ->innerJoin("$groupsTable g", 'g.id = ug.group_id')
            ->where(['ug.user_id' => (int)$user->id])
            ->column();

        $groupIds = array_values(array_unique(array_merge($groupIds, $extraGroupIds)));

        // 2) Roles efetivas (por grupo E por usuário)
        $q = (new Query())
            ->select(['controller', 'actions'])
            ->from($rolesTable);

        $where = ['or'];
        if ($groupIds) {
            $where[] = ['group_id' => $groupIds];
        }
        $where[] = ['user_id' => (int)$user->id];

        $rows = $q->where($where)->all();

        // 3) Montar mapa normalizado
        $map = [];
        foreach ($rows as $r) {
            $c = (string)$r['controller'];
            $a = trim((string)($r['actions'] ?? ''));

            if ($c === '') continue;

            if (!isset($map[$c])) $map[$c] = [];

            // '*' domina
            if ($a === '*' || $a === '') {
                $map[$c] = ['*'];
                continue;
            }

            // pode ter várias actions separadas por ';'
            $parts = array_filter(array_map('trim', explode(';', $a)), fn($x) => $x !== '');
            if ($parts) {
                if ($map[$c] !== ['*']) {
                    $map[$c] = array_values(array_unique(array_merge($map[$c], $parts)));
                }
            }
        }

        return $map;
    }

    /**
     * Verifica se o usuário atual pode conceder UMA action de um controller.
     * Bypass para master.
     */
    public static function canGrant(string $controller, string $action): bool
    {
        // ✅ Master concede tudo
        if (AuthorizationController::isMaster()) return true;

        $scope = self::currentUserGrantScope();

        // Sentinel de acesso total no escopo
        if (isset($scope['*']) && $scope['*'] === ['*']) return true;

        if (!isset($scope[$controller])) return false;
        $allowed = $scope[$controller];
        return $allowed === ['*'] || in_array($action, $allowed, true);
    }

    /**
     * Verifica múltiplas actions de uma vez (todas devem ser permitidas).
     */
    public static function canGrantAll(string $controller, array $actions): bool
    {
        if (AuthorizationController::isMaster()) return true;
        foreach ($actions as $a) {
            if (!self::canGrant($controller, $a)) return false;
        }
        return true;
    }

    /**
     * Filtra um conjunto de actions retornando apenas as que o usuário pode conceder
     * para aquele controller. Para master, devolve todas.
     */
    public static function grantablesForController(string $controller, array $allActions): array
    {
        if (AuthorizationController::isMaster()) return array_values(array_unique($allActions));

        $scope = self::currentUserGrantScope();

        // Acesso total por sentinel
        if (isset($scope['*']) && $scope['*'] === ['*']) {
            return array_values(array_unique($allActions));
        }

        if (!isset($scope[$controller])) return [];

        $allowed = $scope[$controller];
        if ($allowed === ['*']) return array_values(array_unique($allActions));

        // Interseção
        $allowedSet = array_flip($allowed);
        return array_values(array_filter($allActions, fn($a) => isset($allowedSet[$a])));
    }

}
