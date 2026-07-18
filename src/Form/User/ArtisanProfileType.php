<?php

declare(strict_types=1);

namespace App\Form\User;

use App\Entity\Users\ArtisanProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class ArtisanProfileType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('legalName', TextType::class, [
                'label' => 'Nom de l’entreprise',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Exemple : Dupont Rénovation',
                    'autocomplete' => 'organization',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Le nom de l’entreprise est obligatoire.',
                    ),
                    new Length(
                        max: 180,
                        maxMessage: 'Le nom de l’entreprise ne peut pas dépasser {{ limit }} caractères.',
                    ),
                ],
            ])

            ->add('siret', TextType::class, [
                'label' => 'Numéro SIRET',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '123 456 789 00012',
                    'autocomplete' => 'off',
                    'inputmode' => 'numeric',
                    'maxlength' => 17,
                ],
                'help' => 'Le numéro SIRET contient exactement 14 chiffres.',
                'constraints' => [
                    new NotBlank(
                        message: 'Le numéro SIRET est obligatoire.',
                    ),
                    new Regex(
                        pattern: '/^(?:\d[\s]*){14}$/',
                        message: 'Le numéro SIRET doit contenir exactement 14 chiffres.',
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(
        OptionsResolver $resolver
    ): void {
        $resolver->setDefaults([
            'data_class' => ArtisanProfile::class,
        ]);
    }
}