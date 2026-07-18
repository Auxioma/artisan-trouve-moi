<?php

namespace App\Controller\Client;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AvisController extends AbstractController
{
    #[Route('/espace-client/mes-avis', name: 'app_client_avis')]
    public function __invoke(): Response
    {
        return $this->render('client/mes_avis.html.twig');
    }
}
