<?php

namespace App\Controller\Client;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ParametresController extends AbstractController
{
    #[Route('/espace-client/parametres', name: 'app_client_parametres')]
    public function __invoke(): Response
    {
        return $this->render('client/parametres.html.twig');
    }
}
