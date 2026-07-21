<?php

declare(strict_types=1);

namespace App\Form\Artisan;

use App\Entity\Users\ArtisanProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

class ArtisanProfileType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder

            /*
             * ============================================================
             * INFORMATIONS DE L’ENTREPRISE
             * ============================================================
             */

            ->add('legalName', TextType::class, [
                'label' => 'Dénomination légale',
                'attr' => [
                    'autocomplete' => 'organization',
                    'placeholder' => 'Exemple : Plomberie Express SAS',
                ],
            ])

            ->add('commercialName', TextType::class, [
                'label' => 'Nom commercial',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Exemple : Plomberie Express',
                ],
            ])

            /*
             * Le slug est généré automatiquement par Gedmo.
             * Il n’est donc pas ajouté au formulaire.
             */

            ->add('siret', TextType::class, [
                'label' => 'Numéro SIRET',
                'required' => false,
                'attr' => [
                    'inputmode' => 'numeric',
                    'autocomplete' => 'off',
                    'placeholder' => '12345678901234',
                ],
            ])

            ->add('siren', HiddenType::class, [
                'attr' => [
                    'data-company-field' => 'siren',
                ],
            ])

            /*
             * Le SIREN est automatiquement extrait des neuf premiers
             * chiffres du SIRET dans setSiret().
             * Il n’est donc pas nécessaire de l’afficher.
             */

            ->add('vatNumber', TextType::class, [
                'label' => 'Numéro de TVA intracommunautaire',
                'required' => false,
                'attr' => [
                    'autocomplete' => 'off',
                    'placeholder' => 'FR00123456789',
                ],
            ])

            ->add('apeCode', TextType::class, [
                'label' => 'Code APE / NAF',
                'required' => false,
                'attr' => [
                    'autocomplete' => 'off',
                    'placeholder' => '43.22A',
                ],
            ])

            ->add('legalForm', TextType::class, [
                'label' => 'Forme juridique',
                'required' => false,
                'attr' => [
                    'placeholder' => 'SAS, SARL, EI, micro-entreprise…',
                ],
            ])

            /*
             * ============================================================
             * ADRESSE PRINCIPALE
             * ============================================================
             */

            /*
             * Champ utilisé par ton contrôleur Stimulus pour effectuer
             * la recherche d’adresse auprès de Nominatim.
             *
             * Ce champ n’existe pas dans l’entité.
             */
            ->add('addressSearch', TextType::class, [
                'label' => 'Rechercher l’adresse professionnelle',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'autocomplete' => 'off',
                    'placeholder' => 'Saisissez une adresse complète',
                    'data-osm-address-target' => 'query',
                ],
            ])

            /*
             * Le complément d’adresse reste visible, car Nominatim
             * ne peut pas toujours le déterminer.
             */
            ->add('addressComplement', HiddenType::class, [
                'label' => 'Complément d’adresse',
                'required' => false,
                'attr' => [
                    'autocomplete' => 'address-line2',
                    'placeholder' => 'Bâtiment, étage, appartement…',
                ],
            ])

            /*
             * Données postales remplies automatiquement par Nominatim.
             */
            ->add('houseNumber', HiddenType::class, [
                'attr' => [
                    'data-osm-address-target' => 'houseNumber',
                ],
            ])

            ->add('road', HiddenType::class, [
                'attr' => [
                    'data-osm-address-target' => 'road',
                ],
            ])

            ->add('neighbourhood', HiddenType::class, [
                'attr' => [
                    'data-osm-address-target' => 'neighbourhood',
                ],
            ])

            ->add('suburb', HiddenType::class, [
                'attr' => [
                    'data-osm-address-target' => 'suburb',
                ],
            ])

            ->add('cityDistrict', HiddenType::class, [
                'attr' => [
                    'data-osm-address-target' => 'cityDistrict',
                ],
            ])

            ->add('hamlet', HiddenType::class, [
                'attr' => [
                    'data-osm-address-target' => 'hamlet',
                ],
            ])

            ->add('village', HiddenType::class, [
                'attr' => [
                    'data-osm-address-target' => 'village',
                ],
            ])

            ->add('town', HiddenType::class, [
                'attr' => [
                    'data-osm-address-target' => 'town',
                ],
            ])

            ->add('city', TextType::class, [
                'attr' => [
                    'data-osm-address-target' => 'city',
                ],
            ])

            ->add('municipality', HiddenType::class, [
                'attr' => [
                    'data-osm-address-target' => 'municipality',
                ],
            ])

            ->add('county', HiddenType::class, [
                'attr' => [
                    'data-osm-address-target' => 'county',
                ],
            ])

            ->add('stateDistrict', HiddenType::class, [
                'attr' => [
                    'data-osm-address-target' => 'stateDistrict',
                ],
            ])

            ->add('state', HiddenType::class, [
                'attr' => [
                    'data-osm-address-target' => 'state',
                ],
            ])

            ->add('region', HiddenType::class, [
                'attr' => [
                    'data-osm-address-target' => 'region',
                ],
            ])

            ->add('postalCode', TextType::class, [
                'attr' => [
                    'data-osm-address-target' => 'postalCode',
                ],
            ])

            ->add('country', HiddenType::class, [
                'attr' => [
                    'data-osm-address-target' => 'country',
                ],
            ])

            ->add('countryCode', HiddenType::class, [
                'attr' => [
                    'data-osm-address-target' => 'countryCode',
                ],
            ])

            ->add('osmDisplayName', HiddenType::class, [
                'attr' => [
                    'data-osm-address-target' => 'displayName',
                ],
            ])

            /*
             * ============================================================
             * COORDONNÉES GÉOGRAPHIQUES
             * ============================================================
             */

            ->add('latitude', HiddenType::class, [
                'attr' => [
                    'data-osm-address-target' => 'latitude',
                ],
            ])

            ->add('longitude', HiddenType::class, [
                'attr' => [
                    'data-osm-address-target' => 'longitude',
                ],
            ])

            /*
             * ============================================================
             * INFORMATIONS OPENSTREETMAP / NOMINATIM
             * ============================================================
             */

            ->add('osmId', HiddenType::class, [
                'attr' => [
                    'data-osm-address-target' => 'osmId',
                ],
            ])

            ->add('osmType', HiddenType::class, [
                'attr' => [
                    'data-osm-address-target' => 'osmType',
                ],
            ])

            ->add('osmCategory', HiddenType::class, [
                'attr' => [
                    'data-osm-address-target' => 'osmCategory',
                ],
            ])

            ->add('osmPlaceType', HiddenType::class, [
                'attr' => [
                    'data-osm-address-target' => 'osmPlaceType',
                ],
            ])

            ->add('nominatimPlaceId', HiddenType::class, [
                'attr' => [
                    'data-osm-address-target' => 'nominatimPlaceId',
                ],
            ])

            /*
             * ============================================================
             * ZONE D’INTERVENTION
             * ============================================================
             */
            ->add('travelRadiusKm', RangeType::class, [
                'label' => 'Rayon d’intervention',
                'required' => false,
                'empty_data' => '25',
                'attr' => [
                    'class' => 'form-range',
                    'min' => 5,
                    'max' => 80,
                    'step' => 5,
                    'aria-label' => 'Rayon d’intervention en kilomètres',
                ],
                'help' => 'Indiquez le rayon maximal en kilomètres.',
                'constraints' => [
                    new Range(
                        min: 5,
                        max: 80,
                        notInRangeMessage: 'Le rayon doit être compris entre {{ min }} et {{ max }} km.',
                    ),
                ],
            ])

            /*
             * ============================================================
             * QUALIFICATIONS
             * ============================================================
             */

            ->add('description', TextareaType::class, [
                'label' => 'Présentation de votre activité',
                'required' => false,
                'attr' => [
                    'rows' => 6,
                    'maxlength' => 10000,
                    'placeholder' => 'Présentez votre entreprise, votre expérience et vos services.',
                ],
            ])

            /*
             * ============================================================
             * RESPONSABILITÉ CIVILE PROFESSIONNELLE
             * ============================================================
             */

            /*
             * professionalLiabilityInsuranceRequired est une donnée
             * définie par l’administration ou par le métier.
             * Elle n’est volontairement pas modifiable ici.
             */

            ->add('hasProfessionalLiabilityInsurance', CheckboxType::class, [
                'label' => 'Je possède une assurance responsabilité civile professionnelle',
                'required' => false,
            ])

            ->add('professionalLiabilityInsurer', TextType::class, [
                'label' => 'Nom de l’assureur',
                'required' => false,
            ])

            ->add('professionalLiabilityPolicyNumber', TextType::class, [
                'label' => 'Numéro du contrat',
                'required' => false,
            ])

            ->add('professionalLiabilityStartsAt', DateType::class, [
                'label' => 'Date de début du contrat',
                'required' => false,
                'widget' => 'single_text',
            ])

            ->add('professionalLiabilityExpiresAt', DateType::class, [
                'label' => 'Date d’expiration du contrat',
                'required' => false,
                'widget' => 'single_text',
            ])

            /*
             * ============================================================
             * ASSURANCE DÉCENNALE
             * ============================================================
             */

            /*
             * decennialInsuranceRequired est définie par l’administration
             * ou selon le métier. Elle n’est pas exposée ici.
             */

            ->add('hasDecennialInsurance', CheckboxType::class, [
                'label' => 'Je possède une assurance décennale',
                'required' => false,
            ])

            ->add('decennialInsurer', TextType::class, [
                'label' => 'Nom de l’assureur décennal',
                'required' => false,
            ])

            ->add('decennialPolicyNumber', TextType::class, [
                'label' => 'Numéro du contrat décennal',
                'required' => false,
            ])

            ->add('decennialInsuranceStartsAt', DateType::class, [
                'label' => 'Date de début de la garantie décennale',
                'required' => false,
                'widget' => 'single_text',
            ])

            ->add('decennialInsuranceExpiresAt', DateType::class, [
                'label' => 'Date d’expiration de la garantie décennale',
                'required' => false,
                'widget' => 'single_text',
            ])

            ->add('decennialGeographicalCoverage', TextType::class, [
                'label' => 'Zone géographique couverte',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Exemple : France métropolitaine',
                ],
            ])
            ->add('notificationPreferences', NotificationType::class, [
                'label' => false,
                'required' => false,
            ])

            ->add('services', CollectionType::class, [
                'label' => false,
                'entry_type' => ServiceType::class,
                'entry_options' => [
                    'label' => false,
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ArtisanProfile::class,
        ]);
    }
}