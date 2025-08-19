<?php

namespace croacworks\essentials\components\authorization;

interface AuthorizationContract {
    public function can(string $controllerFqcn, string $actionId, ?int $userId = null, ?int $groupId = null): bool;
    public function canCurrent(?int $userId = null, ?int $groupId = null): bool;
}
