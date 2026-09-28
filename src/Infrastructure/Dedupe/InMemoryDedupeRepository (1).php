<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Infrastructure\Dedupe;

use Neox\WrapNotificatorBundle\Contract\DedupeRepositoryInterface;

final class InMemoryDedupeRepository implements DedupeRepositoryInterface
{
    /** @var array<string,int> */
    private array $expirations = [];

    public function remember(string $key, int $ttlSeconds): void
    {
        $this->cleanup();
        $this->expirations[$key] = time() + max(1, $ttlSeconds);
    }

    public function exists(string $key): bool
    {
        $this->cleanup();
        if (!isset($this->expirations[$key])) {
            return false;
        }
        if ($this->expirations[$key] < time()) {
            unset($this->expirations[$key]);
            return false;
        }
        return true;
    }

    private function cleanup(): void
    {
        $now = time();
        foreach ($this->expirations as $k => $exp) {
            if ($exp < $now) {
                unset($this->expirations[$k]);
            }
        }
    }
}
