<?php
declare(strict_types=1);

namespace Nexus\Auth\OAuth;

interface OAuthProviderInterface
{
    public function getName(): string;
    public function getAuthorizationUrl(string $state): string;
    public function handleCallback(string $code): array;
    public function isMock(): bool;
    public function getMockUser(): array;
}
