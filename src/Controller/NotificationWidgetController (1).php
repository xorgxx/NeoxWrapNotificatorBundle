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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\Translation\TranslatorInterface;

final class NotificationWidgetController extends AbstractController
{
    /**
     * @param array<string,mixed> $config
     */
    public function __construct(
        private readonly NotifierFacade $facade,
        private readonly array $config,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('/wrap-notificator/form/{type}', name: 'wrap_notificator_form', methods: ['GET', 'POST'], requirements: ['type' => 'email'])]
    public function renderForm(Request $request, string $type): Response
    {
        if ($type !== 'email') {
            throw new NotFoundHttpException();
        }
        $dto = new EmailNotificationDto();

        $data = $request->query->all();
        foreach ($data as $key => $value) {
            if ($key !== 'exclude' && property_exists($dto, $key)) {
                $dto->$key = $value;
            }
        }

        $defaultRecipients = $this->config['default_recipients'] ?? [];
        if (isset($defaultRecipients[$type]) && property_exists($dto, 'recipient')) {
            $dto->recipient = $defaultRecipients[$type];
        }

        // Note: sender is NOT auto-filled - it should be provided by the user (client email in contact forms)
        // If you need to auto-fill sender for specific use cases, configure it via query parameters

        $formOptions = [];
        
        $exclude = $request->query->get('exclude', '');
        if (is_string($exclude) && $exclude !== '') {
            $exclude = array_map('trim', explode(',', $exclude));
        } else {
            $exclude = [];
        }
        $formOptions['exclude_fields'] = $exclude;

        $form = $this->createForm(GenericNotificationType::class, $dto, $formOptions);
        $form->handleRequest($request);

        $status = null;
        $rateLimitedRetryAfterSeconds = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $hp = $form->has('fax') ? (string) $form->get('fax')->getData() : '';
            if (trim($hp) !== '') {
                $status = DeliveryStatus::queued($type, null, 'honeypot', ['reason' => 'honeypot']);
            }

            if ($status === null && $this->container->has('limiter.wrap_notificator_form_ip')) {
                $ip = (string) ($request->getClientIp() ?? 'unknown');
                $rateLimiter = $this->container->get('limiter.wrap_notificator_form_ip');
                $limiter = is_object($rateLimiter) && method_exists($rateLimiter, 'create')
                    ? $rateLimiter->create($ip)
                    : $rateLimiter;
                if (is_object($limiter) && method_exists($limiter, 'consume')) {
                    $limit = $limiter->consume(1);
                    if (is_object($limit) && method_exists($limit, 'isAccepted') && $limit->isAccepted() === false) {
                        if (method_exists($limit, 'getRetryAfter')) {
                            $retryAfter = $limit->getRetryAfter();
                            if ($retryAfter instanceof \DateTimeInterface) {
                                $rateLimitedRetryAfterSeconds = max(1, $retryAfter->getTimestamp() - time());
                            }
                        }
                        $status = DeliveryStatus::queued($type, null, 'rate-limited', ['reason' => 'rate_limiter']);
                    }
                }
            }

            // Apply email template for contact forms if enabled
            if ($status === null && $dto instanceof EmailNotificationDto && ($this->config['email_template']['enabled'] ?? true)) {
                $dto->template = $this->config['email_template']['template'] ?? '@WrapNotificator/email/contact_form.html.twig';
                $siteName = $this->config['site_name'] ?? null;
                if (!is_string($siteName) || trim($siteName) === '') {
                    try {
                        $projectName = $this->getParameter('name_projet');
                        if (is_string($projectName) && trim($projectName) !== '') {
                            $siteName = $projectName;
                        }
                    } catch (\Throwable) {
                        // ignore
                    }
                }
                if (!is_string($siteName) || trim($siteName) === '') {
                    $siteName = $request->getHost();
                }

                $siteUrl = null;
                try {
                    $configuredSiteUrl = $this->getParameter('web_site');
                    if (is_string($configuredSiteUrl) && trim($configuredSiteUrl) !== '') {
                        $siteUrl = $configuredSiteUrl;
                    }
                } catch (\Throwable) {
                    // ignore
                }
                if (!is_string($siteUrl) || trim($siteUrl) === '') {
                    $siteUrl = $request->getSchemeAndHttpHost();
                }
                $dto->templateVars = [
                    'siteName' => $siteName,
                    'siteUrl' => $siteUrl,
                    'sender' => $dto->sender,
                    'subject' => $dto->subject,
                    'content' => $dto->content,
                    'attachments' => array_map(fn($file) => [
                        'name' => $file->getClientOriginalName(),
                        'mime' => $file->getMimeType() ?? 'application/octet-stream'
                    ], $dto->attachments),
                    'receivedAt' => new \DateTimeImmutable(),
                    'clientIp' => $request->getClientIp()
                ];
            }
            
            if ($status === null) {
                $status = $this->facade->send($dto);
            }
            $flashType = 'info';
            $flashMessage = $this->translator->trans('wrap_notificator.form.queued', ['%type%' => $type], 'messages');
            $closeModal = false;

            if ($status->message === 'rate-limited') {
                $flashType = 'warning';
                if ($rateLimitedRetryAfterSeconds === null) {
                    $delay = $this->translator->trans('wrap_notificator.form.retry_in_a_moment', [], 'messages');
                } elseif ($rateLimitedRetryAfterSeconds >= 60) {
                    $minutes = (int) ceil($rateLimitedRetryAfterSeconds / 60);
                    $delay = $this->translator->trans('wrap_notificator.form.delay_minutes', ['%count%' => $minutes], 'messages');
                } else {
                    $seconds = (int) $rateLimitedRetryAfterSeconds;
                    $delay = $this->translator->trans('wrap_notificator.form.delay_seconds', ['%count%' => $seconds], 'messages');
                }
                $flashMessage = $this->translator->trans('wrap_notificator.form.rate_limited', ['%delay%' => $delay], 'messages');
            }

            if ($status->status === DeliveryStatus::STATUS_FAILED) {
                $flashType = 'error';
                $flashMessage = $this->translator->trans('wrap_notificator.form.failed', ['%type%' => $type], 'messages');
            } elseif ($status->status === DeliveryStatus::STATUS_SENT) {
                $flashType = 'success';
                $flashMessage = $this->translator->trans('wrap_notificator.form.sent', ['%type%' => $type], 'messages');
                $closeModal = true;
            }

            $this->addFlash($flashType, $flashMessage);
            
            // For AJAX requests, return success response to trigger modal close
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'ok' => $status->status !== DeliveryStatus::STATUS_FAILED,
                    'closeModal' => $closeModal,
                    'status' => $status->toArray(),
                    'flashes' => [
                        ['type' => $flashType, 'message' => $flashMessage],
                    ],
                ]);
            }
        }

        return $this->render('@WrapNotificator/widget/form.html.twig', [
            'form'   => $form->createView(),
            'type'   => $type,
            'status' => $status,
        ]);
    }
}
