<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Neox\WrapNotificatorBundle\Command\NotifyCommand;
use Neox\WrapNotificatorBundle\Notification\DeliveryStatus;
use Neox\WrapNotificatorBundle\Service\NotifierFacade;
use Neox\WrapNotificatorBundle\Notification\DeliveryContext;

final class NotifyCommandDedupeTest extends TestCase
{
    public function testDedupeKeyTwiceFirstSentSecondQueued(): void
    {
        $facade = new class extends NotifierFacade {
            private int $count = 0;
            public function __construct() {}
            public function notifySms(string $content, string $to, array $metadata = [], ? DeliveryContext $ctx = null): DeliveryStatus
            {
                $this->count++;
                if ($this->count === 1) {
                    return DeliveryStatus::sent('sms')->withContext($ctx ?? DeliveryContext::create());
                }
                return DeliveryStatus::queued('sms', null, 'noop', ['reason' => 'dedup-hit'])->withContext($ctx ?? DeliveryContext::create());
            }
        };

        $app = new Application();
        $app->add(new NotifyCommand($facade));
        $command = $app->find('notify:send');
        $tester = new CommandTester($command);

        $exit1 = $tester->execute([
            '--channel' => 'sms',
            '--to' => '+33600000000',
            '--text' => 'hello',
            '--dedupe-key' => 'k-user-42',
            '--dedupe-ttl' => '600',
        ]);
        self::assertSame(0, $exit1);
        $out1 = $tester->getDisplay();
        self::assertStringContainsString('"status": "sent"', $out1);
        self::assertStringContainsString('"dedupeKey": "k-user-42"', $out1);

        $tester2 = new CommandTester($command);
        $exit2 = $tester2->execute([
            '--channel' => 'sms',
            '--to' => '+33600000000',
            '--text' => 'hello',
            '--dedupe-key' => 'k-user-42',
        ]);
        self::assertSame(0, $exit2);
        $out2 = $tester2->getDisplay();
        self::assertStringContainsString('"status": "queued"', $out2);
        self::assertStringContainsString('"reason": "dedup-hit"', $out2);
    }
}
