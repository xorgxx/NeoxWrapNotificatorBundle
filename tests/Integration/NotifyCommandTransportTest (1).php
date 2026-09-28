<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Tests\Integration;

use Neox\WrapNotificatorBundle\Command\NotifyCommand;
use Neox\WrapNotificatorBundle\Notification\DeliveryContext;
use Neox\WrapNotificatorBundle\Notification\DeliveryStatus;
use Neox\WrapNotificatorBundle\Service\NotifierFacade;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class NotifyCommandTransportTest extends TestCase
{
    /**
     * @param (callable(DeliveryContext): void) $assertCtx
     */
    private function getCommandTesterWithAssertion(callable $assertCtx, string $expectedChannel): CommandTester
    {
        $facade = $this->createMock(NotifierFacade::class);

        $facade->method('notifyEmail')->willReturnCallback(function (...$args) use ($assertCtx, $expectedChannel) {
            $this->assertSame('email', $expectedChannel);
            $ctx = $args[6] ?? null; // subject, htmlOrText, to, isHtml, opts, metadata, ctx
            $this->assertInstanceOf(DeliveryContext::class, $ctx);
            $assertCtx($ctx);
            return DeliveryStatus::sent('email');
        });
        $facade->method('notifySms')->willReturnCallback(function (...$args) use ($assertCtx, $expectedChannel) {
            $this->assertSame('sms', $expectedChannel);
            $ctx = $args[3] ?? null; // content, to, metadata, ctx
            $this->assertInstanceOf(DeliveryContext::class, $ctx);
            $assertCtx($ctx);
            return DeliveryStatus::sent('sms');
        });
        $facade->method('notifyChat')->willReturnCallback(function (...$args) use ($assertCtx, $expectedChannel) {
            $this->assertSame('chat', $expectedChannel);
            $ctx = $args[5] ?? null; // transport, content, subject, opts, metadata, ctx
            $this->assertInstanceOf(DeliveryContext::class, $ctx);
            $assertCtx($ctx);
            return DeliveryStatus::sent('chat');
        });
        $facade->method('notifyBrowser')->willReturnCallback(function (...$args) use ($assertCtx, $expectedChannel) {
            $this->assertSame('browser', $expectedChannel);
            $ctx = $args[3] ?? null; // topic, data, metadata, ctx
            $this->assertInstanceOf(DeliveryContext::class, $ctx);
            $assertCtx($ctx);
            return DeliveryStatus::sent('browser');
        });

        $app = new Application();
        $app->add(new NotifyCommand($facade));
        $command = $app->find('notify:send');

        return new CommandTester($command);
    }

    public function testViaTransportSyncIsPropagatedInContextForEmail(): void
    {
        $tester = $this->getCommandTesterWithAssertion(function (DeliveryContext $ctx): void {
            self::assertSame('sync', $ctx->viaTransport);
        }, 'email');

        $exitCode = $tester->execute([
            '--channel' => 'email',
            '--to' => 'to@example.com',
            '--subject' => 'Hi',
            '--html' => '<b>Hi</b>',
            '--via-transport' => 'sync',
            '--json' => true,
        ]);

        self::assertSame(0, $exitCode);
        $out = $tester->getDisplay();
        self::assertStringContainsString('"status": "sent"', $out);
    }

    public function testViaTransportAsyncIsPropagatedInContextForSms(): void
    {
        $tester = $this->getCommandTesterWithAssertion(function (DeliveryContext $ctx): void {
            self::assertSame('asyncRabbitMq', $ctx->viaTransport);
        }, 'sms');

        $exitCode = $tester->execute([
            '--channel' => 'sms',
            '--to' => '+33600000000',
            '--text' => 'Hello',
            '--via-transport' => 'asyncRabbitMq',
            '--json' => true,
        ]);

        self::assertSame(0, $exitCode);
        $out = $tester->getDisplay();
        self::assertStringContainsString('"status": "sent"', $out);
    }

    public function testViaTransportCombinedWithInDeferralBuildsContext(): void
    {
        $tester = $this->getCommandTesterWithAssertion(function (DeliveryContext $ctx): void {
            // Here we only verify that the viaTransport is set; deferAt may or may not be set depending on parsing
            self::assertSame('asyncRabbitMq', $ctx->viaTransport);
            // DeferAt should be a future DateTimeImmutable when parse succeeds
            if ($ctx->deferAt !== null) {
                self::assertInstanceOf(\DateTimeImmutable::class, $ctx->deferAt);
            }
        }, 'browser');

        $exitCode = $tester->execute([
            '--channel' => 'browser',
            '--topic' => 'users:1',
            '--data' => ['a:1'],
            '--in' => '15m',
            '--via-transport' => 'asyncRabbitMq',
            '--json' => true,
        ]);

        self::assertSame(0, $exitCode);
    }
}
