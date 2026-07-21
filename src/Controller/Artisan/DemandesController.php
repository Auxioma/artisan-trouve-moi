<?php

declare(strict_types=1);

namespace App\Controller\Artisan;

use App\Repository\Requests\ServiceRequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ARTISAN')]
#[Route('/espace-prestataire/demandes', name: 'app_artisan_demandes')]
class DemandesController extends AbstractController
{
    public function __construct(
        private readonly ServiceRequestRepository $requestRepo,
    ) {
    }

    #[Route('', name: '', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $artisan = $this->getUser()?->getArtisanProfile();
        $statut = $request->query->getString('statut', 'all');
        $categorie = $request->query->getInt('categorie', 0);

        // TODO : filtrer par zone d'intervention de l'artisan + statut + catégorie
        // $demandes = $this->requestRepo->findForArtisan($artisan, $statut, $categorie);

        return $this->render('artisan/demandes.html.twig', [
            'artisan_name' => $artisan?->getCommercialName() ?? 'Mon entreprise',
            'artisan_initials' => $this->initiales($artisan),
            'demandes_count' => 12,
            'messages_count' => 2,
            'notifications_count' => 3,
            // 'demandes'      => $demandes,
            'statut_actif' => $statut,
            'categorie_active' => $categorie,
        ]);
    }

    private function initiales($a): string
    {
        $mots = explode(' ', $a?->getCommercialName() ?? 'Prestataire');

        return mb_strtoupper(mb_substr($mots[0], 0, 1).mb_substr($mots[1] ?? 'E', 0, 1));
    }
}
