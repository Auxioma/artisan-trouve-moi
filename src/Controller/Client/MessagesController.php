<?php

namespace App\Controller\Client;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MessagesController extends AbstractController
{
    #[Route('/espace-client/messages', name: 'app_client_messages')]
    public function __invoke(): Response
    {
        return $this->render('client/messages.html.twig');
    }
}
