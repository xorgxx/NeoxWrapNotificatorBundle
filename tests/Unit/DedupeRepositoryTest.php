<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Tests\Unit;

use Neox\WrapNotificatorBundle\Infrastructure\Dedupe\InMemoryDedupeRepository;
use PHPUnit\Framework\TestCase;

final class DedupeRepositoryTest extends TestCase
{
    public function testInMemoryRememberAndExists(): void
    {
        $repo = new InMemoryDedupeRepository();
        $key = 'k1';
        self::assertFalse($repo->exists($key));
        $repo->remember($key, 60);
        self::assertTrue($repo->exists($key));
    }
}
