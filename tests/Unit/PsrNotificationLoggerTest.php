<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Tests\Unit;

use Neox\WrapNotificatorBundle\Infrastructure\Logging\PsrNotificationLogger;
use Neox\WrapNotificatorBundle\Notification\DeliveryStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(PsrNotificationLogger::class)]
final class PsrNotificationLoggerTest extends TestCase
{
    #[Test]
    public function log_calls_info_on_success(): void
    {
        $psrLogger = $this->createMock(LoggerInterface::class);
        $status = DeliveryStatus::sent('email', 'msg-123', 'Sent successfully');

        $psrLogger->expects(self::once())
            ->method('info')
            ->with(
                self::stringContains('[WrapNotificator] [EMAIL] [SENT]'),
                self::callback(fn ($context) => $context['id'] === 'msg-123' && $context['status'] === 'sent')
            );

        $logger = new PsrNotificationLogger($psrLogger);
        $logger->log($status);
    }

    #[Test]
    public function log_calls_error_on_failure(): void
    {
        $psrLogger = $this->createMock(LoggerInterface::class);
        $status = DeliveryStatus::failed('sms', 'Invalid number');

        $psrLogger->expects(self::once())
            ->method('error')
            ->with(
                self::stringContains('[WrapNotificator] [SMS] [FAILED]'),
                self::callback(fn ($context) => $context['status'] === 'failed' && $context['message'] === 'Invalid number')
            );

        $logger = new PsrNotificationLogger($psrLogger);
        $logger->log($status);
    }
}
