<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Infrastructure\Dedupe;

use Psr\Cache\CacheItemPoolInterface;
use Neox\WrapNotificatorBundle\Contract\DedupeRepositoryInterface;

final class CacheDedupeRepository implements DedupeRepositoryInterface
{
    /** @var array<string,int> */
    private array $fallbackExpirations = [];

    public function __construct(private readonly ?CacheItemPoolInterface $cache = null)
    {
    }

    public function remember(string $key, int $ttlSeconds): void
    {
        if ($this->cache !== null) {
            $item = $this->cache->getItem($this->key($key));
            $item->set(1);
            $item->expiresAfter(max(1, $ttlSeconds));
            $this->cache->save($item);
            return;
        }
        // Fallback in-memory
        $this->fallbackExpirations[$key] = time() + max(1, $ttlSeconds);
    }

    public function exists(string $key): bool
    {
        if ($this->cache !== null) {
            return $this->cache->hasItem($this->key($key));
        }
        // Fallback in-memory
        $exp = $this->fallbackExpirations[$key] ?? null;
        if ($exp === null) {
            return false;
        }
        if ($exp < time()) {
            unset($this->fallbackExpirations[$key]);
            return false;
        }
        return true;
    }

    private function key(string $key): string
    {
        return 'wrap_notificator_dedupe:' . sha1($key);
    }
}
