<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Users\User;
use App\Form\User\ArtisanProfileType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PasswordStrength;

class RegistrationFormType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder
            ->add('accountType', HiddenType::class, [
                'mapped' => false,
                'data' => 'client',
                'constraints' => [
                    new NotBlank(
                        message: 'Le type de compte est obligatoire.',
                    ),
                    new Choice(
                        choices: [
                            'client',
                            'pro',
                        ],
                        message: 'Le type de compte sélectionné est invalide.',
                    ),
                ],
            ])

            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Votre prénom',
                    'autocomplete' => 'given-name',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Le prénom est obligatoire.',
                    ),
                    new Length(
                        min: 2,
                        max: 100,
                        minMessage: 'Le prénom doit contenir au moins {{ limit }} caractères.',
                        maxMessage: 'Le prénom ne peut pas dépasser {{ limit }} caractères.',
                    ),
                ],
            ])

            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Votre nom',
                    'autocomplete' => 'family-name',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Le nom est obligatoire.',
                    ),
                    new Length(
                        min: 2,
                        max: 100,
                        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.',
                        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.',
                    ),
                ],
            ])

            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'vous@entreprise.fr',
                    'autocomplete' => 'email',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'L’adresse e-mail est obligatoire.',
                    ),
                    new Email(
                        message: 'L’adresse e-mail renseignée n’est pas valide.',
                    ),
                ],
            ])

            ->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe',
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Votre mot de passe',
                    'autocomplete' => 'new-password',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Le mot de passe est obligatoire.',
                    ),
                    new Length(
                        min: 8,
                        max: 4096,
                        minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
                    ),
                    new PasswordStrength(
                        minScore: PasswordStrength::STRENGTH_MEDIUM,
                        message: 'Le mot de passe n’est pas suffisamment sécurisé.',
                    ),
                ],
            ])

            ->add('agreeTerms', CheckboxType::class, [
                'label' => 'J’accepte les conditions générales d’utilisation',
                'mapped' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'constraints' => [
                    new IsTrue(
                        message: 'Vous devez accepter les conditions générales.',
                    ),
                ],
            ]);

        /*
         * À l’affichage, le champ existe pour que Twig puisse
         * afficher les champs professionnels.
         *
         * empty_data => null empêche la création d’un profil
         * artisan lorsque les champs sont vides.
         */
        $builder->add('artisanProfile', ArtisanProfileType::class, [
            'label' => false,
            'required' => false,
            'empty_data' => null,
        ]);

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event): void {
                $submittedData = $event->getData();
                $form = $event->getForm();

                if (!is_array($submittedData)) {
                    return;
                }

                $accountType = $submittedData['accountType']
                    ?? 'client';

                if ('pro' === $accountType) {
                    $this->addArtisanProfileField($form);

                    return;
                }

                /*
                 * Pour un particulier, on retire complètement
                 * artisanProfile des données envoyées.
                 */
                unset($submittedData['artisanProfile']);

                $event->setData($submittedData);

                if ($form->has('artisanProfile')) {
                    $form->remove('artisanProfile');
                }
            }
        );
    }

    private function addArtisanProfileField(
        FormInterface $form,
    ): void {
        $form->add(
            'artisanProfile',
            ArtisanProfileType::class,
            [
                'label' => false,
                'required' => true,
            ]
        );
    }

    public function configureOptions(
        OptionsResolver $resolver,
    ): void {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
