<?php

declare(strict_types=1);

namespace App\Form\User;

use App\Entity\Users\ArtisanProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
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
        array $options,
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

            ->add('commercialName', HiddenType::class, [
    'required' => false,
])

->add('siren', HiddenType::class, [
    'required' => false,
])

->add('vatNumber', HiddenType::class, [
    'required' => false,
])

->add('apeCode', HiddenType::class, [
    'required' => false,
])

->add('legalForm', HiddenType::class, [
    'required' => false,
])

->add('houseNumber', HiddenType::class, [
    'required' => false,
])

->add('road', HiddenType::class, [
    'required' => false,
])

->add('addressComplement', HiddenType::class, [
    'required' => false,
])

->add('neighbourhood', HiddenType::class, [
    'required' => false,
])

->add('suburb', HiddenType::class, [
    'required' => false,
])

->add('cityDistrict', HiddenType::class, [
    'required' => false,
])

->add('hamlet', HiddenType::class, [
    'required' => false,
])

->add('village', HiddenType::class, [
    'required' => false,
])

->add('town', HiddenType::class, [
    'required' => false,
])

->add('city', HiddenType::class, [
    'required' => false,
])

->add('municipality', HiddenType::class, [
    'required' => false,
])

->add('county', HiddenType::class, [
    'required' => false,
])

->add('stateDistrict', HiddenType::class, [
    'required' => false,
])

->add('state', HiddenType::class, [
    'required' => false,
])

->add('region', HiddenType::class, [
    'required' => false,
])

->add('postalCode', HiddenType::class, [
    'required' => false,
])

->add('country', HiddenType::class, [
    'required' => false,
])

->add('countryCode', HiddenType::class, [
    'required' => false,
])

->add('osmDisplayName', HiddenType::class, [
    'required' => false,
])

->add('latitude', HiddenType::class, [
    'required' => false,
])

->add('longitude', HiddenType::class, [
    'required' => false,
])

->add('osmId', HiddenType::class, [
    'required' => false,
])

->add('osmType', HiddenType::class, [
    'required' => false,
])

->add('osmCategory', HiddenType::class, [
    'required' => false,
])

->add('osmPlaceType', HiddenType::class, [
    'required' => false,
])

->add('nominatimPlaceId', HiddenType::class, [
    'required' => false,
])
        ;
    }

    public function configureOptions(
        OptionsResolver $resolver,
    ): void {
        $resolver->setDefaults([
            'data_class' => ArtisanProfile::class,
        ]);
    }
}