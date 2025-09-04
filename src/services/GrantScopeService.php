<?php
namespace croacworks\essentials\services;

use Yii;
use yii\db\Query;

class GrantScopeService
{
    /**
     * Retorna o escopo de concessão do usuário logado, no formato:
     * [
     *   'app\controllers\FileController' => ['index','view','update'] // actions
     *   'croacworks\essentials\controllers\MenuController' => ['*'],  // wildcard
     * ]
     */
    public static function currentUserGrantScope(): array
    {
        $user = Yii::$app->user->identity;
        if (!$user) return [];

        // 1) Descobrir todos os groups do usuário (group_id direto + users_groups)
        $ugTable = class_exists(\croacworks\essentials\models\UsersGroup::class)
            ? \croacworks\essentials\models\UsersGroup::tableName()
            : '{{%users_groups}}';
        $groupsTable = \croacworks\essentials\models\Group::tableName();
        $rolesTable  = \croacworks\essentials\models\Role::tableName();

        $groupIds = [];
        if ($user->group_id) $groupIds[] = (int)$user->group_id;

        $extraGroupIds = (new Query())
            ->select(['ug.group_id'])
            ->from("$ugTable ug")
            ->innerJoin("$groupsTable g", 'g.id = ug.group_id')
            ->where(['ug.user_id' => (int)$user->id])
            ->column();

        $groupIds = array_values(array_unique(array_merge($groupIds, $extraGroupIds)));

        if (!$groupIds) return [];

        // 2) Pegar as roles efetivas desses grupos
        $rows = (new Query())
            ->select(['controller','action'])
            ->from($rolesTable)
            ->where(['group_id' => $groupIds])
            ->all();

        $map = [];
        foreach ($rows as $r) {
            $c = $r['controller'];
            $a = $r['action'] ?: '*';
            if (!isset($map[$c])) $map[$c] = [];
            // normalizar '*' (qualquer action naquele controller)
            if ($a === '*') { $map[$c] = ['*']; continue; }
            if ($map[$c] !== ['*']) $map[$c][] = $a;
        }
        // ordenar e remover duplicatas
        foreach ($map as $c => $list) {
            if ($list !== ['*']) $map[$c] = array_values(array_unique($list));
        }
        return $map;
    }

    public static function canGrant(string $controller, string $action): bool
    {
        $scope = self::currentUserGrantScope();
        if (!isset($scope[$controller])) return false;
        $allowed = $scope[$controller];
        return $allowed === ['*'] || in_array($action, $allowed, true);
    }
}
