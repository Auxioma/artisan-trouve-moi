<?php

namespace App\Controller\Client;

use App\Entity\Users\User;
use App\Form\Client\ParametreType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ParametresController extends AbstractController
{
    #[Route('/espace-client/parametres', name: 'client_parametres')]
    public function parametres(): Response
    {
        $parametre = $this->getUser();
        $form = $this->createForm(ParametreType::class, $parametre);
        
        return $this->render('client/parametres.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
