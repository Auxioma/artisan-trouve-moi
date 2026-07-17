<?php

namespace App\Controller\Users;

use App\Entity\Users\User;
use App\Form\RegistrationFormType;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    public function __construct(
        private EmailVerifier $emailVerifier
    ) {
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $user = new User();

        /*
         * Ne surtout pas créer ArtisanProfile ici.
         * Un particulier ne doit avoir aucun profil artisan.
         */

        $form = $this->createForm(
            RegistrationFormType::class,
            $user
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $accountType */
            $accountType = $form->get('accountType')->getData();

            /*
             * Sécurité :
             * un particulier ne doit jamais conserver
             * un ArtisanProfile.
             */
            if ($accountType === 'client') {
                $user->setArtisanProfile(null);
            }

            /** @var string $plainPassword */
            $plainPassword = $form
                ->get('plainPassword')
                ->getData();

            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $plainPassword
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

            $this->emailVerifier->sendEmailConfirmation(
                'app_verify_email',
                $user,
                (new TemplatedEmail())
                    ->from(
                        new Address(
                            'hello@trouvemoi.com',
                            'Trouve Moi'
                        )
                    )
                    ->to((string) $user->getEmail())
                    ->subject('Please Confirm your Email')
                    ->htmlTemplate(
                        'email/auth/confirmation_email.html.twig'
                    )
            );

            return $this->redirectToRoute('app_login');
        }

        return $this->render(
            'auth/registration/register.html.twig',
            [
                'registrationForm' => $form,
            ]
        );
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(
        Request $request,
        TranslatorInterface $translator
    ): Response {
        $this->denyAccessUnlessGranted(
            'IS_AUTHENTICATED_FULLY'
        );

        try {
            /** @var User $user */
            $user = $this->getUser();

            $this->emailVerifier
                ->handleEmailConfirmation(
                    $request,
                    $user
                );
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash(
                'verify_email_error',
                $translator->trans(
                    $exception->getReason(),
                    [],
                    'VerifyEmailBundle'
                )
            );

            return $this->redirectToRoute(
                'app_register'
            );
        }

        $this->addFlash(
            'success',
            'Your email address has been verified.'
        );

        return $this->redirectToRoute(
            'app_register'
        );
    }
}