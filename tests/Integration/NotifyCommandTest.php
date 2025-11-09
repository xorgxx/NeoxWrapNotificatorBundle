<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Neox\WrapNotificatorBundle\Command\NotifyCommand;
use Neox\WrapNotificatorBundle\Notification\DeliveryStatus;
use Neox\WrapNotificatorBundle\Service\NotifierFacade;

final class NotifyCommandTest extends TestCase
{
    private function getCommandTesterReturning(DeliveryStatus $status, string $expectedChannel): CommandTester
    {
        $facade = $this->createMock(NotifierFacade::class);
        $facade->method('notifyEmail')->willReturnCallback(function (...$args) use ($status, $expectedChannel) {
            $this->assertSame('email', $expectedChannel);
            return $status;
        });
        $facade->method('notifySms')->willReturnCallback(function (...$args) use ($status, $expectedChannel) {
            $this->assertSame('sms', $expectedChannel);
            return $status;
        });
        $facade->method('notifyChat')->willReturnCallback(function (...$args) use ($status, $expectedChannel) {
            $this->assertSame('chat', $expectedChannel);
            return $status;
        });
        $facade->method('notifyBrowser')->willReturnCallback(function (...$args) use ($status, $expectedChannel) {
            $this->assertSame('browser', $expectedChannel);
            return $status;
        });
        $facade->method('notifyPush')->willReturnCallback(function (...$args) use ($status, $expectedChannel) {
            $this->assertTrue(in_array($expectedChannel, ['push','desktop'], true));
            return $status;
        });
        $facade->method('notifyDesktop')->willReturnCallback(function (...$args) use ($status, $expectedChannel) {
            $this->assertSame('desktop', $expectedChannel);
            return $status;
        });

        $app = new Application();
        $app->add(new NotifyCommand($facade));
        $command = $app->find('notify:send');

        return new CommandTester($command);
    }

    public function testEmailSuccess(): void
    {
        $tester = $this->getCommandTesterReturning(DeliveryStatus::sent('email'), 'email');
        $exitCode = $tester->execute([
            '--channel' => 'email',
            '--to' => 'to@example.com',
            '--subject' => 'Hi',
            '--html' => '<b>Hi</b>',
        ]);
        self::assertSame(0, $exitCode);
        $output = $tester->getDisplay();
        self::assertStringContainsString('"status": "sent"', $output);
        self::assertStringContainsString('"channel": "email"', $output);
    }

    public function testSmsFailureMissingArgs(): void
    {
        $tester = $this->getCommandTesterReturning(DeliveryStatus::failed('sms', 'oops'), 'sms');
        $exitCode = $tester->execute([
            '--channel' => 'sms',
        ]);
        self::assertSame(1, $exitCode);
        $output = $tester->getDisplay();
        self::assertStringContainsString('failed', $output);
    }

    public function testChatSuccess(): void
    {
        $tester = $this->getCommandTesterReturning(DeliveryStatus::sent('chat'), 'chat');
        $exitCode = $tester->execute([
            '--channel' => 'chat',
            '--transport' => 'slack',
            '--text' => 'Hello',
            '--subject' => 'Subj',
            '--opt' => ['channel:ops','iconEmoji::bell:'],
        ]);
        self::assertSame(0, $exitCode);
        $output = $tester->getDisplay();
        self::assertStringContainsString('"channel": "chat"', $output);
    }

    public function testBrowserSuccess(): void
    {
        $tester = $this->getCommandTesterReturning(DeliveryStatus::sent('browser', 'id-1'), 'browser');
        $exitCode = $tester->execute([
            '--channel' => 'browser',
            '--topic' => 'users:1',
            '--data' => ['a:1','b:2'],
        ]);
        self::assertSame(0, $exitCode);
        $output = $tester->getDisplay();
        self::assertStringContainsString('"id": "id-1"', $output);
    }

    public function testPushAndDesktopSuccess(): void
    {
        $subscription = [
            'endpoint' => 'https://ep',
            'keys' => ['p256dh' => 'p', 'auth' => 'a'],
        ];
        $tmp = tempnam(sys_get_temp_dir(), 'sub');
        file_put_contents($tmp, json_encode($subscription));

        $testerPush = $this->getCommandTesterReturning(DeliveryStatus::sent('push'), 'push');
        $exitPush = $testerPush->execute([
            '--channel' => 'push',
            '--subscription-file' => $tmp,
            '--data' => ['x:1'],
            '--ttl' => '60',
        ]);
        self::assertSame(0, $exitPush);

        $testerDesktop = $this->getCommandTesterReturning(DeliveryStatus::sent('desktop'), 'desktop');
        $exitDesktop = $testerDesktop->execute([
            '--channel' => 'desktop',
            '--subscription-file' => $tmp,
            '--data' => ['x:1'],
        ]);
        self::assertSame(0, $exitDesktop);

        @unlink($tmp);
    }
}
