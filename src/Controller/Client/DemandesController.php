<?php

namespace App\Controller\Client;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DemandesController extends AbstractController
{
    #[Route('/espace-client/mes-demandes', name: 'app_client_demandes')]
    public function __invoke(): Response
    {
        return $this->render('client/mes_demandes.html.twig');
    }
}
