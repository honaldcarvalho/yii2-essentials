<?php

namespace croacworks\essentials\components\auth;

interface AuthServiceInterface {
    public function login(string $usernameOrEmail, string $password, bool $remember = false): bool;
    public function logout(): void;
    public function isGuest(): bool;
    public function userId(): ?int;
}
