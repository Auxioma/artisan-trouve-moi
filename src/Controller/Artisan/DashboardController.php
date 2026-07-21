<?php

declare(strict_types=1);

namespace App\Controller\Artisan;

use App\Entity\Users\ArtisanProfile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ARTISAN')]
#[Route('/espace-prestataire', name: 'app_artisan_dashboard')]
class DashboardController extends AbstractController
{
    #[Route('', name: '', methods: ['GET'])]
    public function __invoke(): Response
    {
        /** @var \App\Entity\Users\User $user */
        $user = $this->getUser();
        $artisan = $user->getArtisanProfile();

        return $this->render('artisan/dashboard.html.twig', [
            // ── Variables globales du layout ────────────────────
            'artisan_name' => $artisan?->getCommercialName() ?? 'Mon entreprise',
            'artisan_initials' => $this->initiales($artisan),
            'notifications_count' => 3,   // TODO : injecter NotificationRepository
            'messages_count' => 2,   // TODO : injecter ConversationRepository
            'demandes_count' => 12,  // TODO : injecter ServiceRequestRepository
            // ── Données page ────────────────────────────────────
            'artisan' => $artisan,
        ]);
    }

    private function initiales(?ArtisanProfile $a): string
    {
        if (null === $a) {
            return 'PE';
        }
        $mots = explode(' ', $a->getCommercialName() ?? '');

        return mb_strtoupper(
            mb_substr($mots[0] ?? 'P', 0, 1).mb_substr($mots[1] ?? 'E', 0, 1)
        );
    }
}
