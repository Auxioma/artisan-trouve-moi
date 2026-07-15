<?php

declare(strict_types=1);

namespace App\Controller\Users;

use App\Entity\Enum\UserType;
use App\Entity\Users\User;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use App\Repository\User\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route('/artisan/reset-password')]
final class ArtisanResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
    ) {
    }

    #[Route('', name: 'app_artisan_forgot_password_request')]
    public function request(
        Request $request,
        MailerInterface $mailer,
        TranslatorInterface $translator,
    ): Response {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $email */
            $email = $form->get('email')->getData();

            return $this->processSendingPasswordResetEmail(
                $email,
                $mailer,
                $translator
            );
        }

        return $this->render('artisan_reset_password/request.html.twig', [
            'requestForm' => $form,
        ]);
    }

    #[Route('/check-email', name: 'app_artisan_check_email')]
    public function checkEmail(): Response
    {
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            $resetToken = $this->resetPasswordHelper->generateFakeResetToken();
        }

        return $this->render('artisan_reset_password/check_email.html.twig', [
            'resetToken' => $resetToken,
        ]);
    }

    #[Route('/reset/{token}', name: 'app_artisan_reset_password')]
    public function reset(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        TranslatorInterface $translator,
        ?string $token = null,
    ): Response {
        if ($token !== null) {
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('app_artisan_reset_password');
        }

        $token = $this->getTokenFromSession();

        if ($token === null) {
            throw $this->createNotFoundException(
                'Aucun jeton de réinitialisation trouvé.'
            );
        }

        try {
            /** @var User $user */
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash(
                'reset_password_error',
                sprintf(
                    '%s - %s',
                    $translator->trans(
                        ResetPasswordExceptionInterface::MESSAGE_PROBLEM_VALIDATE,
                        [],
                        'ResetPasswordBundle'
                    ),
                    $translator->trans($e->getReason(), [], 'ResetPasswordBundle')
                )
            );

            return $this->redirectToRoute('app_artisan_forgot_password_request');
        }

        if ($user->getType() !== UserType::ARTISAN) {
            $this->addFlash(
                'reset_password_error',
                'Ce lien de réinitialisation ne correspond pas à un compte artisan.'
            );

            return $this->redirectToRoute('app_artisan_forgot_password_request');
        }

        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->resetPasswordHelper->removeResetRequest($token);

            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            $user->setPassword(
                $passwordHasher->hashPassword($user, $plainPassword)
            );
            $this->entityManager->flush();

            $this->cleanSessionAfterReset();

            return $this->redirectToRoute('app_artisan_login');
        }

        return $this->render('artisan_reset_password/reset.html.twig', [
            'resetForm' => $form,
            'artisanUser' => $user,
        ]);
    }

    private function processSendingPasswordResetEmail(
        string $emailFormData,
        MailerInterface $mailer,
        TranslatorInterface $translator,
    ): RedirectResponse {
        $user = $this->userRepository->findOneByEmail($emailFormData);

        if (!$user instanceof User || $user->getType() !== UserType::ARTISAN) {
            return $this->redirectToRoute('app_artisan_check_email');
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            return $this->redirectToRoute('app_artisan_check_email');
        }

        $email = (new TemplatedEmail())
            ->from(new Address('hello@trouvemoi.com', 'Trouve Moi'))
            ->to((string) $user->getEmail())
            ->subject('Réinitialisation de votre mot de passe artisan')
            ->htmlTemplate('artisan_reset_password/email.html.twig')
            ->context([
                'resetToken' => $resetToken,
            ]);

        $mailer->send($email);
        $this->setTokenObjectInSession($resetToken);

        return $this->redirectToRoute('app_artisan_check_email');
    }
}
