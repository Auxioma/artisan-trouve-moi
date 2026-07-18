<?php

namespace App\Controller\Client;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DevisRecusController extends AbstractController
{
    #[Route('/espace-client/devis-recus', name: 'app_client_devis_recus')]
    public function __invoke(): Response
    {
        return $this->render('client/devis_recus.html.twig');
    }
}
