<?php

declare(strict_types=1);

namespace App\Controller\Artisan;

use App\Entity\Users\ArtisanProfile;
use App\Entity\Users\User;
use App\Form\Artisan\ParametreArtisanType;
use App\Repository\Users\UserSessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ARTISAN')]
#[Route(
    '/espace-prestataire/parametres',
    name: 'app_artisan_parametres'
)]
final class ParametresController extends AbstractController
{
    #[Route('', name: '', methods: ['GET', 'POST'])]
    public function __invoke(
        Request $request,
        EntityManagerInterface $entityManager,
        UserSessionRepository $userSessionRepository,
    ): Response {
        /** @var User $artisan */
        $artisan = $this->getUser();

        $profile = $artisan->getArtisanProfile();

        if (null === $profile) {
            $profile = new ArtisanProfile();

            $artisan->setArtisanProfile($profile);
        }

        $notificationPreferences =
            $profile->getOrCreateNotificationPreferences();


        $form = $this->createForm(
            ParametreArtisanType::class,
            $artisan
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($artisan);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Vos paramètres ont bien été enregistrés.'
            );

            return $this->redirectToRoute(
                'app_artisan_parametres'
            );
        }

        $sessions = $userSessionRepository->findBy(['user' => $artisan->getId()], ['id' => 'DESC']);
        $currentToken = $request->getSession()->get('_user_session_token');

        return $this->render('artisan/parametres.html.twig', [
            'form' => $form->createView(),
            'artisan' => $artisan,
            'profile' => $profile,
            'notificationPreferences' => $notificationPreferences,
            'sessions' => $sessions,
            'currentSessionToken' => $currentToken,
        ]);
    }
}