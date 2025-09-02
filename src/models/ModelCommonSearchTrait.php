<?php
namespace croacworks\essentials\models;

use Yii;
use yii\db\ActiveQuery;
use croacworks\essentials\controllers\AuthorizationController;

trait ModelCommonSearchTrait
{
    /**
     * Aplica o filtro de grupo se $this->verGroup === true
     * Usa escopo de família (árvore do grupo raiz).
     */
    protected function applyGroupScope(ActiveQuery $query, string $tableAlias = null): void
    {
        if (property_exists($this, 'verGroup') && $this->verGroup) {
            $ids = AuthorizationController::groupScopeIds();
            if (!$ids) {
                // Sem grupo: retorna vazio controlado
                $query->andWhere('1=0');
                return;
            }

            // Garante alias
            $alias = $tableAlias ?: $query->modelClass::tableName();
            // se já usa alias fixo no search, passe-o em $tableAlias

            $query->andWhere(["IN", "{$alias}.group_id", $ids]);
        }
    }
}
