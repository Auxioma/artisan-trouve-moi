<?php

declare(strict_types=1);

namespace App\Controller\Artisan;

use App\Repository\Quotes\QuoteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ARTISAN')]
#[Route('/espace-prestataire/devis', name: 'app_artisan_devis')]
class DevisController extends AbstractController
{
    public function __construct(
        private readonly QuoteRepository $quoteRepo,
    ) {
    }

    #[Route('', name: '', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $artisan = $this->getUser()?->getArtisanProfile();
        $statut = $request->query->getString('statut', 'all');

        // TODO : $devis = $this->quoteRepo->findByArtisan($artisan, $statut);

        return $this->render('artisan/devis.html.twig', [
            'artisan_name' => $artisan?->getCommercialName() ?? 'Mon entreprise',
            'artisan_initials' => $this->initiales($artisan),
            'demandes_count' => 12,
            'messages_count' => 2,
            'notifications_count' => 3,
            // 'devis'            => $devis,
            'statut_actif' => $statut,
        ]);
    }

    private function initiales($a): string
    {
        $mots = explode(' ', $a?->getCommercialName() ?? 'Prestataire');

        return mb_strtoupper(mb_substr($mots[0], 0, 1).mb_substr($mots[1] ?? 'E', 0, 1));
    }
}
