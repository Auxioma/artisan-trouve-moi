<?php

declare(strict_types=1);

namespace App\Controller\Artisan;

use App\Entity\Users\ArtisanProfile;
use App\Entity\Users\User;
use App\Form\Artisan\ParametreArtisanType;
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
    ): Response {
        /** @var User $artisan */
        $artisan = $this->getUser();

        /*
         * ============================================================
         * CRÉATION DU PROFIL ARTISAN SI NÉCESSAIRE
         * ============================================================
         */

        $profile = $artisan->getArtisanProfile();

        if (null === $profile) {
            $profile = new ArtisanProfile();

            /*
             * setArtisanProfile() synchronise déjà normalement
             * le côté ArtisanProfile::user.
             */
            $artisan->setArtisanProfile($profile);
        }

        /*
         * ============================================================
         * CRÉATION DES PRÉFÉRENCES DE NOTIFICATION SI NÉCESSAIRE
         * ============================================================
         *
         * Cette méthode crée ArtisanNotificationPreferences
         * et configure automatiquement la relation OneToOne.
         */
        $notificationPreferences =
            $profile->getOrCreateNotificationPreferences();

        /*
         * ============================================================
         * FORMULAIRE PRINCIPAL
         * ============================================================
         *
         * ParametreArtisanType reçoit l'entité User.
         *
         * Sa structure est normalement :
         *
         * User
         * └── artisanProfile
         *     └── notificationPreferences
         */
        $form = $this->createForm(
            ParametreArtisanType::class,
            $artisan
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /*
             * Grâce aux cascades :
             *
             * User
             *   cascade persist vers ArtisanProfile
             *
             * ArtisanProfile
             *   cascade persist vers ArtisanNotificationPreferences
             *
             * Doctrine enregistrera les trois entités.
             */
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

        return $this->render('artisan/parametres.html.twig', [
            'form' => $form->createView(),
            'artisan' => $artisan,
            'profile' => $profile,
            'notificationPreferences' => $notificationPreferences,
        ]);
    }
}