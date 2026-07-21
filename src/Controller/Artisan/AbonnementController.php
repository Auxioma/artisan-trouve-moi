<?php

declare(strict_types=1);

namespace App\Controller\Artisan;

use App\Repository\Billing\SubscriptionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ARTISAN')]
#[Route('/espace-prestataire/abonnement', name: 'app_artisan_abonnement')]
class AbonnementController extends AbstractController
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptionRepo,
    ) {
    }

    #[Route('', name: '', methods: ['GET'])]
    public function __invoke(): Response
    {
        $artisan = $this->getUser()?->getArtisanProfile();
        // TODO : $subscription = $this->subscriptionRepo->findActiveByArtisan($artisan);

        return $this->render('artisan/abonnement.html.twig', [
            'artisan_name' => $artisan?->getCommercialName() ?? 'Mon entreprise',
            'artisan_initials' => $this->initiales($artisan),
            'demandes_count' => 12,
            'messages_count' => 2,
            'notifications_count' => 3,
            // 'subscription'     => $subscription,
        ]);
    }

    private function initiales($a): string
    {
        $mots = explode(' ', $a?->getCommercialName() ?? 'Prestataire');

        return mb_strtoupper(mb_substr($mots[0], 0, 1).mb_substr($mots[1] ?? 'E', 0, 1));
    }
}
