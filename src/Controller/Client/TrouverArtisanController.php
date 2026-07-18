<?php

namespace App\Controller\Client;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TrouverArtisanController extends AbstractController
{
    #[Route('/espace-client/trouver-artisan', name: 'client_trouver_artisan')]
    public function __invoke(): Response
    {
        return $this->render('client/trouver_artisan.html.twig');
    }
}
