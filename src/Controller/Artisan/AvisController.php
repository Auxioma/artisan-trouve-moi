<?php

declare(strict_types=1);

namespace App\Controller\Artisan;

use App\Repository\Reviews\ReviewRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ARTISAN')]
#[Route('/espace-prestataire/avis', name: 'app_artisan_avis')]
class AvisController extends AbstractController
{
    public function __construct(
        private readonly ReviewRepository $reviewRepo,
    ) {
    }

    #[Route('', name: '', methods: ['GET'])]
    public function __invoke(): Response
    {
        $artisan = $this->getUser()?->getArtisanProfile();

        // TODO : $avis = $this->reviewRepo->findPublishedByArtisan($artisan);

        return $this->render('artisan/avis.html.twig', [
            'artisan_name' => $artisan?->getCommercialName() ?? 'Mon entreprise',
            'artisan_initials' => $this->initiales($artisan),
            'demandes_count' => 12,
            'messages_count' => 2,
            'notifications_count' => 3,
            // 'avis'             => $avis,
        ]);
    }

    private function initiales($a): string
    {
        $mots = explode(' ', $a?->getCommercialName() ?? 'Prestataire');

        return mb_strtoupper(mb_substr($mots[0], 0, 1).mb_substr($mots[1] ?? 'E', 0, 1));
    }
}
