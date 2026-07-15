<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use Symfony\Component\Validator\Constraints\Regex;

final class ArtisanRegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('companyName', TextType::class, [
                'label' => 'Nom de l\'entreprise',
                'constraints' => [
                    new NotBlank(message: 'Le nom de l\'entreprise est obligatoire.'),
                    new Length(max: 180),
                ],
                'attr' => [
                    'autocomplete' => 'organization',
                    'placeholder' => 'Plomberie Express',
                ],
            ])
            ->add('siret', TextType::class, [ 
                'label' => 'SIRET',
                'constraints' => [
                    new NotBlank(message: 'Le numéro SIRET est obligatoire.'),
                    new Regex(
                        pattern: '/^\d{14}$/',
                        message: 'Le numéro SIRET doit contenir exactement 14 chiffres.'
                    ),
                ],
                'attr' => [
                    'autocomplete' => 'off',
                    'inputmode' => 'numeric',
                    'placeholder' => '12345678901234',
                ],
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [
                    new NotBlank(message: 'Le prénom est obligatoire.'),
                    new Length(
                        min: 2,
                        max: 100,
                        minMessage: 'Le prénom doit contenir au moins {{ limit }} caractères.'
                    ),
                ],
                'attr' => [
                    'autocomplete' => 'given-name',
                    'placeholder' => 'Sophie',
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank(message: 'Le nom est obligatoire.'),
                    new Length(
                        min: 2,
                        max: 100,
                        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.'
                    ),
                ],
                'attr' => [
                    'autocomplete' => 'family-name',
                    'placeholder' => 'Martin',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
                'constraints' => [
                    new NotBlank(message: 'L\'adresse e-mail est obligatoire.'),
                    new Email(message: 'L\'adresse e-mail renseignée n\'est pas valide.'),
                    new Length(max: 180),
                ],
                'attr' => [
                    'autocomplete' => 'username',
                    'placeholder' => 'vous@exemple.fr',
                ],
            ])
                        ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue(
                        message: 'Vous devez accepter nos conditions générales.',
                    ),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe',
                'mapped' => false,
                'constraints' => [
                    new NotBlank(message: 'Le mot de passe est obligatoire.'),
                    new Length(
                        min: 12,
                        max: 4096,
                        minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.'
                    ),
                    new PasswordStrength(),
                    new NotCompromisedPassword(),
                ],
                'attr' => [
                    'autocomplete' => 'new-password',
                    'placeholder' => '12 caractères minimum',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
