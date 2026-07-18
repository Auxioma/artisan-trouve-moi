<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Espace client TrouveMoi — pages statiques du kit UI.
 * Le contenu de démonstration vit dans les templates ;
 * remplacez-le progressivement par vos données Doctrine.
 */
final class ClientController extends AbstractController
{
    #[Route('/espace-client', name: 'app_client_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('client/dashboard.html.twig');
    }

    #[Route('/espace-client/mes-demandes', name: 'app_client_demandes')]
    public function mes_demandes(): Response
    {
        return $this->render('client/mes_demandes.html.twig');
    }

    #[Route('/espace-client/devis-recus', name: 'app_client_devis_recus')]
    public function devis_recus(): Response
    {
        return $this->render('client/devis_recus.html.twig');
    }

    #[Route('/espace-client/mes-projets', name: 'app_client_projets')]
    public function mes_projets(): Response
    {
        return $this->render('client/mes_projets.html.twig');
    }

    #[Route('/espace-client/messages', name: 'app_client_messages')]
    public function messages(): Response
    {
        return $this->render('client/messages.html.twig');
    }

    #[Route('/espace-client/mes-avis', name: 'app_client_avis')]
    public function mes_avis(): Response
    {
        return $this->render('client/mes_avis.html.twig');
    }

    #[Route('/espace-client/trouver-artisan', name: 'app_client_trouver_artisan')]
    public function trouver_artisan(): Response
    {
        return $this->render('client/trouver_artisan.html.twig');
    }

    #[Route('/espace-client/favoris', name: 'app_client_favoris')]
    public function favoris(): Response
    {
        return $this->render('client/favoris.html.twig');
    }

    #[Route('/espace-client/parametres', name: 'app_client_parametres')]
    public function parametres(): Response
    {
        return $this->render('client/parametres.html.twig');
    }
}
