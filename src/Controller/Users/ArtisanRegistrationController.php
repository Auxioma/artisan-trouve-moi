<?php

declare(strict_types=1);

namespace App\Controller\Users;

use App\Entity\Enum\UserType;
use App\Entity\Users\ArtisanProfile;
use App\Entity\Users\User;
use App\Form\ArtisanRegistrationFormType;
use App\Repository\User\ArtisanProfileRepository;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

final class ArtisanRegistrationController extends AbstractController
{
    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

 
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        ArtisanProfileRepository $artisanProfileRepository,
        SluggerInterface $slugger,
        ValidatorInterface $validator,
    ): Response {
        $connectedUser = $this->getUser();

        if ($connectedUser instanceof User) {
            return $connectedUser->getType() === UserType::ARTISAN
                ? $this->redirectToRoute('app_artisan_account')
                : $this->redirectToRoute('app_account');
        }

        $form = $this->createForm(ArtisanRegistrationFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array<string, mixed> $data */
            $data = $form->getData();
            $normalizedSiret = preg_replace('/\D/', '', (string) $data['siret']) ?? '';

            $user = (new User())
                ->setType(UserType::ARTISAN)
                ->setFirstName((string) $data['firstName'])
                ->setLastName((string) $data['lastName'])
                ->setEmail((string) $data['email']);

            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword(
                $userPasswordHasher->hashPassword($user, $plainPassword)
            );

            $artisanProfile = (new ArtisanProfile())
                ->setUser($user)
                ->setLegalName((string) $data['companyName'])
                ->setSiret($normalizedSiret)
                ->setSiren(substr($normalizedSiret, 0, 9))
                ->setSlug(
                    $this->generateUniqueSlug(
                        (string) $data['companyName'],
                        $normalizedSiret,
                        $slugger,
                        $artisanProfileRepository
                    )
                );

            $this->addViolationsToForm(
                $form,
                $validator->validate($user),
                [
                    'email' => 'email',
                    'firstName' => 'firstName',
                    'lastName' => 'lastName',
                ]
            );

            $this->addViolationsToForm(
                $form,
                $validator->validate($artisanProfile),
                [
                    'legalName' => 'companyName',
                    'siret' => 'siret',
                ]
            );

            if ($form->isValid()) {
                $entityManager->persist($user);
                $entityManager->persist($artisanProfile);
                $entityManager->flush();

                $this->emailVerifier->sendEmailConfirmation(
                    'app_artisan_verify_email',
                    $user,
                    (new TemplatedEmail())
                        ->from(new Address('hello@trouvemoi.com', 'Trouve Moi'))
                        ->to((string) $user->getEmail())
                        ->subject('Confirmez votre adresse e-mail')
                        ->htmlTemplate('registration/confirmation_email.html.twig')
                );

                $this->addFlash(
                    'success',
                    'Votre compte artisan a été créé. Vérifiez votre boîte mail pour confirmer votre adresse e-mail.'
                );

                return $this->redirectToRoute('app_artisan_login');
            }
        }

        return $this->render('auth/_partials/_register/prestataires.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/artisan/verify/email', name: 'app_artisan_verify_email')]
    public function verifyArtisanEmail(
        Request $request,
        TranslatorInterface $translator,
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();

        if (!$user instanceof User || $user->getType() !== UserType::ARTISAN) {
            throw $this->createAccessDeniedException();
        }

        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash(
                'verify_email_error',
                $translator->trans(
                    $exception->getReason(),
                    [],
                    'VerifyEmailBundle'
                )
            );

            return $this->redirectToRoute('app_artisan_register');
        }

        $this->addFlash('success', 'Votre adresse e-mail a bien été confirmée.');

        return $this->redirectToRoute('app_artisan_account');
    }

    private function generateUniqueSlug(
        string $companyName,
        string $normalizedSiret,
        SluggerInterface $slugger,
        ArtisanProfileRepository $artisanProfileRepository,
    ): string {
        $baseSlug = $slugger->slug($companyName)->lower()->toString();
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'artisan';

        if ($normalizedSiret !== '') {
            $baseSlug .= '-'.substr($normalizedSiret, -4);
        }

        $slug = $baseSlug;
        $suffix = 1;

        while ($artisanProfileRepository->findOneBySlug($slug) !== null) {
            $slug = $baseSlug.'-'.$suffix;
            ++$suffix;
        }

        return $slug;
    }

    /**
     * @param array<string, string> $fieldMap
     */
    private function addViolationsToForm(
        FormInterface $form,
        ConstraintViolationListInterface $violations,
        array $fieldMap,
    ): void {
        foreach ($violations as $violation) {
            $propertyPath = $violation->getPropertyPath();
            $fieldName = $fieldMap[$propertyPath] ?? null;

            if ($fieldName !== null && $form->has($fieldName)) {
                $form->get($fieldName)->addError(
                    new FormError($violation->getMessage())
                );

                continue;
            }

            $form->addError(new FormError($violation->getMessage()));
        }
    }
}
