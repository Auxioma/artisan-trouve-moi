<?php

namespace App\Controller\Client;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProjetsController extends AbstractController
{
    #[Route('/espace-client/mes-projets', name: 'app_client_projets')]
    public function __invoke(): Response
    {
        return $this->render('client/mes_projets.html.twig');
    }
}
