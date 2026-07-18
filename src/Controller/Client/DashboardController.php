<?php

namespace App\Controller\Client;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/espace-client', name: 'client_dashboard')]
    public function __invoke(): Response
    {
        return $this->render('client/dashboard.html.twig');
    }
}
