<?php

namespace App\Controller\Client;

use App\Entity\Users\User;
use App\Entity\Users\UserProfile;
use App\Form\Client\ParametreType;
use App\Repository\Users\UserSessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ParametresController extends AbstractController
{
    #[Route(
        '/espace-client/parametres',
        name: 'client_parametres',
        methods: ['GET', 'POST']
    )]
    public function parametres(
        Request $request,
        EntityManagerInterface $entityManager,
        UserSessionRepository $userSessionRepository,
    ): Response {
        /** @var User|null $parametre */
        $parametre = $this->getUser();

        if (!$parametre instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        /*
         * Adresse : créée uniquement si absente.
         */
        if (null === $parametre->getUserProfile()) {
            $userProfile = new UserProfile();
            $userProfile->setProviderName('OPENSTREETMAP_NOMINATIM');
            $userProfile->setIsDefault(true);

            $parametre->setUserProfile($userProfile);
        }

        /*
         * Préférences : on garantit qu'un objet existe
         * (valeurs par défaut) pour les anciens comptes.
         */
        $parametre->getOrCreatePreferences();

        $form = $this->createForm(
            ParametreType::class,
            $parametre
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /*
             * Cascade persist sur les relations OneToOne :
             * persister User suffit.
             */
            $entityManager->persist($parametre);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Vos paramètres ont été enregistrés.'
            );

            return $this->redirectToRoute('client_parametres');
        }

        $sessions = $userSessionRepository->findBy(['user' => $parametre->getId()], ['id' => 'DESC']);
        $currentToken = $request->getSession()->get('_user_session_token');

        return $this->render(
            'client/parametres.html.twig',
            [
                'form' => $form->createView(),
                'sessions' => $sessions,
                'currentSessionToken' => $currentToken,
            ]
        );
    }
}
