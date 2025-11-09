<?php

declare(strict_types=1);

namespace WrapNotificatorBundle\Examples\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use WrapNotificatorBundle\Service\NotifierFacade;

final class NotificationsController
{
    public function __construct(private readonly NotifierFacade $facade)
    {
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
