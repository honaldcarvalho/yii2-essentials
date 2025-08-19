<?php
namespace croacworks\essentials\components\authorization;

use Yii;
use yii\base\BaseObject;
use croacworks\essentials\models\Role;

/**
 * Regra v0:
 *  - Permite se existir em roles uma linha ATIVA que case:
 *      (controller = FQCN exato) AND (action = actionId OR action='*')
 *    e group_id = NULL ou = $groupId informado.
 */
class AuthorizationService extends BaseObject implements AuthorizationContract
{
    /** @var callable|null Resolve o groupId atual (ex.: fn(): ?int { ... }) */
    public $groupIdResolver;

    public function can(string $controllerFqcn, string $actionId, ?int $userId = null, ?int $groupId = null): bool
    {
        $gid = $groupId ?? $this->resolveGroupId();

        $q = Role::find()
            ->andWhere(['controller' => $controllerFqcn, 'status' => 1])
            ->andWhere(['or', ['action' => $actionId], ['action' => '*']]);

        // group_id NULL vale para todos; se não for null, precisa bater
        $q->andWhere(['or',
            ['group_id' => null],
            ['group_id' => $gid],
        ]);

        return $q->exists();
    }

    public function canCurrent(?int $userId = null, ?int $groupId = null): bool
    {
        $controllerFqcn = get_class(Yii::$app->controller);
        $actionId       = Yii::$app->controller->action->id ?? 'index';
        return $this->can($controllerFqcn, $actionId, $userId, $groupId);
    }

    private function resolveGroupId(): ?int
    {
        if (is_callable($this->groupIdResolver)) {
            return (int)call_user_func($this->groupIdResolver) ?: null;
        }
        // fallback comum: tentar do usuário logado
        $u = Yii::$app->user->identity ?? null;
        return $u->group_id ?? null;
    }
}
