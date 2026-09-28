<?php

declare(strict_types=1);

namespace WrapNotificatorBundle\Examples\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Neox\WrapNotificatorBundle\Form\GenericNotificationType;
use Neox\WrapNotificatorBundle\Notification\Dto\SmsNotificationDto;
use Neox\WrapNotificatorBundle\Service\NotifierFacade;

final class NotificationsController extends AbstractController
{
    public function __construct(private readonly NotifierFacade $facade)
    {
    }

    /**
     * Exemple d'utilisation du formulaire dynamique (CRUD-like) pour envoyer un SMS
     */
    #[Route(path: '/notify/sms-form', name: 'notify_sms_form', methods: ['GET', 'POST'])]
    public function smsForm(Request $request): Response
    {
        $dto = new SmsNotificationDto();
        $form = $this->createForm(GenericNotificationType::class, $dto);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $status = $this->facade->send($dto);

            if ($status->status === 'sent' || $status->status === 'queued') {
                $this->addFlash('success', 'Notification envoyée avec succès !');
            } else {
                $this->addFlash('error', 'Échec de l\'envoi : ' . $status->message);
            }
        }

        return $this->render('examples/sms_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Exemple d'utilisation du widget Twig pour afficher plusieurs formulaires
     */
    #[Route(path: '/notify/widgets', name: 'notify_widgets', methods: ['GET'])]
    public function widgetDashboard(): Response
    {
        return $this->render('examples/widget_example.html.twig');
    }

    #[Route(path: '/notify/ag', name: 'notify_ag', methods: ['POST'])]
    public function notifyAssembleeGenerale(Request $request): JsonResponse
    {
        $email = $request->request->getString('email', 'participant@example.com');
        $topic = $request->request->getString('topic', 'users:42');

        $emailStatus = $this->facade->notifyEmail(
            subject: 'Convocation à l\'Assemblée Générale',
            htmlOrText: $this->renderSimpleAgEmail(),
            to: $email,
            isHtml: true,
            opts: [],
            metadata: ['source' => 'controller'],
        );

        $browserStatus = $this->facade->notifyBrowser(
            topic: $topic,
            data: [
                'type' => 'ag_convocation',
                'message' => 'Une convocation a été envoyée par email',
            ],
            metadata: ['source' => 'controller'],
        );

        return new JsonResponse([
            'email' => $emailStatus->toArray(),
            'browser' => $browserStatus->toArray(),
        ]);
    }

    private function renderSimpleAgEmail(): string
    {
        // In a real app, render via Twig using the provided template.
        return '<h1>Convocation à l\'AG</h1><p>Madame, Monsieur,</p><p>Vous êtes convié(e) à l\'Assemblée Générale annuelle.</p>';
    }
}
