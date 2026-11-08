<?php
declare(strict_types=1);

namespace Nexus\Auth;

use RuntimeException;

/**
 * Declarative Policy & RBAC Authorization Gate
 *
 * Eliminates Insecure Direct Object References (IDOR) through fine-grained policy checks.
 */
class Gate
{
    private array $abilities = [];
    private $userResolver = null;

    public function define(string $ability, callable $callback): static
    {
        $this->abilities[$ability] = $callback;
        return $this;
    }

    public function setUserResolver(callable $resolver): void
    {
        $this->userResolver = $resolver;
    }

    public function getCurrentUser(): mixed
    {
        return $this->userResolver !== null ? ($this->userResolver)() : null;
    }

    public function allows(string $ability, mixed ...$arguments): bool
    {
        if (!isset($this->abilities[$ability])) {
            return false;
        }

        $user = $this->getCurrentUser();
        return (bool)($this->abilities[$ability])($user, ...$arguments);
    }

    public function denies(string $ability, mixed ...$arguments): bool
    {
        return !$this->allows($ability, ...$arguments);
    }

    public function authorize(string $ability, mixed ...$arguments): void
    {
        if ($this->denies($ability, ...$arguments)) {
            throw new RuntimeException("Unauthorized: Access denied for ability '{$ability}'.", 403);
        }
    }
}

