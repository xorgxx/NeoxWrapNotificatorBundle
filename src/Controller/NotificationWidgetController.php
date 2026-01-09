<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Controller;

use Neox\WrapNotificatorBundle\Form\GenericNotificationType;
use Neox\WrapNotificatorBundle\Notification\DeliveryStatus;
use Neox\WrapNotificatorBundle\Notification\Dto\BrowserNotificationDto;
use Neox\WrapNotificatorBundle\Notification\Dto\ChatNotificationDto;
use Neox\WrapNotificatorBundle\Notification\Dto\EmailNotificationDto;
use Neox\WrapNotificatorBundle\Notification\Dto\PushNotificationDto;
use Neox\WrapNotificatorBundle\Notification\Dto\SmsNotificationDto;
use Neox\WrapNotificatorBundle\Service\NotifierFacade;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class NotificationWidgetController extends AbstractController
{
    public function __construct(private readonly NotifierFacade $facade)
    {
    }

    public function renderForm(Request $request, string $type): Response
    {
        $dto = match ($type) {
            'email'     => new EmailNotificationDto(),
            'sms'       => new SmsNotificationDto(),
            'chat'      => new ChatNotificationDto(),
            'browser'   => new BrowserNotificationDto(),
            'push'      => new PushNotificationDto(),
            default     => throw new \InvalidArgumentException("Unknown notification type: $type"),
        };

        $form = $this->createForm(GenericNotificationType::class, $dto);
        $form->handleRequest($request);

        $status = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $status = $this->facade->send($dto);
            if ($status->status === DeliveryStatus::STATUS_FAILED) {
                $this->addFlash('error', "Failed to send $type notification: " . $status->message);
            }
            else {
                $this->addFlash('success', ucfirst($type) . " notification sent successfully! (Status: {$status->status})");
            }
        }

        return $this->render('@NeoxWrapNotificator/widget/form.html.twig', [
            'form'   => $form->createView(),
            'type'   => $type,
            'status' => $status,
        ]);
    }
}
