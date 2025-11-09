<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Contract;

interface DedupeRepositoryInterface
{
    public function remember(string $key, int $ttlSeconds): void;

    public function exists(string $key): bool;
}
