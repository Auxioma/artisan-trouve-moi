<?php

declare(strict_types=1);

namespace App\Controller\Users;

use App\Entity\Enum\UserType;
use App\Entity\Users\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class ArtisanSecurityController extends AbstractController
{
    #[Route(path: '/artisan/login', name: 'app_artisan_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $user = $this->getUser();

        if ($user instanceof User) {
            return $user->getType() === UserType::ARTISAN
                ? $this->redirectToRoute('app_artisan_account')
                : $this->redirectToRoute('app_account');
        }

        return $this->render('artisan_security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/artisan/account', name: 'app_artisan_account')]
    public function account(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ARTISAN');

        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('artisan_security/account.html.twig', [
            'user' => $user,
        ]);
    }
}
