<?php

namespace App\Controller\Client;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FavorisController extends AbstractController
{
    #[Route('/espace-client/favoris', name: 'app_client_favoris')]
    public function __invoke(): Response
    {
        return $this->render('client/favoris.html.twig');
    }
}
