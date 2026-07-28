<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Enum\QualificationType;
use App\Entity\Enum\UserType;
use App\Entity\Enum\VerificationStatus;
use App\Entity\Catalog\ArtisanService;
use App\Entity\Users\ArtisanNotificationPreferences;
use App\Entity\Users\ArtisanProfile;
use App\Entity\Users\User;
use App\Entity\Users\UserProfile;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Vich\UploaderBundle\Form\Type\VichFileType;

final class ArtisanProfileCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $userPasswordHasher,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return ArtisanProfile::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Artisan')
            ->setEntityLabelInPlural('Artisans')
            ->setPageTitle(Crud::PAGE_INDEX, 'Gestion des artisans')
            ->setPageTitle(Crud::PAGE_NEW, 'Créer un profil artisan')
            ->setPageTitle(
                Crud::PAGE_EDIT,
                static fn (ArtisanProfile $artisan): string => sprintf(
                    'Modifier l’artisan : %s',
                    $artisan->getDisplayName(),
                ),
            )
            ->setPageTitle(
                Crud::PAGE_DETAIL,
                static fn (ArtisanProfile $artisan): string => sprintf(
                    'Artisan : %s',
                    $artisan->getDisplayName(),
                ),
            )
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields([
                'id',
                'legalName',
                'commercialName',
                'slug',
                'siren',
                'siret',
                'vatNumber',
                'apeCode',
                'legalForm',
                'user.email',
                'user.firstName',
                'user.lastName',
                'road',
                'postalCode',
                'city',
                'region',
                'country',
                'osmDisplayName',
            ])
            ->setPaginatorPageSize(30);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL, Action::EDIT, Action::DELETE]);
    }

    public function createEntity(string $entityFqcn): ArtisanProfile
    {
        $user = new User();
        $user->setType(UserType::ARTISAN);
        $user->setUserProfile(new UserProfile());
        $user->getOrCreatePreferences();

        return (new ArtisanProfile())->setUser($user);
    }

    public function persistEntity(
        EntityManagerInterface $entityManager,
        mixed $entityInstance,
    ): void {
        $this->prepareRelations($entityInstance);
        $this->synchronizePublicationDates($entityInstance);

        if ($entityInstance instanceof ArtisanProfile) {
            $user = $entityInstance->getUser();

            if ($user instanceof User) {
                $password = $user->getPassword();

                if (null === $password || '' === $password) {
                    throw new \LogicException(
                        'Le mot de passe du compte artisan est obligatoire.',
                    );
                }

                $user->setPassword(
                    $this->userPasswordHasher->hashPassword($user, $password),
                );

                $entityManager->persist($user);
            }
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(
        EntityManagerInterface $entityManager,
        mixed $entityInstance,
    ): void {
        $this->prepareRelations($entityInstance);
        $this->synchronizePublicationDates($entityInstance);

        parent::updateEntity($entityManager, $entityInstance);
    }

    private function prepareRelations(mixed $entityInstance): void
    {
        if (!$entityInstance instanceof ArtisanProfile) {
            return;
        }

        if (null === $entityInstance->getNotificationPreferences()) {
            $entityInstance->setNotificationPreferences(
                new ArtisanNotificationPreferences(),
            );
        }

        $entityInstance->getUser()?->setType(UserType::ARTISAN);
    }

    private function synchronizePublicationDates(mixed $entityInstance): void
    {
        if (!$entityInstance instanceof ArtisanProfile) {
            return;
        }

        if ($entityInstance->isPublished() && null === $entityInstance->getPublishedAt()) {
            /*
             * L'entité ne possède pas de setPublishedAt(). On utilise donc
             * publish() uniquement lorsque les conditions légales sont remplies.
             * Sinon, EasyAdmin conserve simplement la valeur false.
             */
            if ($entityInstance->isLegallyReadyForPublication()) {
                $entityInstance->publish();
            } else {
                $entityInstance->unpublish();
            }
        }
    }

    public function configureFields(string $pageName): iterable
    {
        $isForm = \in_array($pageName, [Crud::PAGE_NEW, Crud::PAGE_EDIT], true);

        /* ============================================================
         * SYNTHÈSE
         * ============================================================ */
        if ($isForm) {
            yield FormField::addTab(
                'Recherche SIRET / SIREN',
                icon: 'fa fa-magnifying-glass',
                propertySuffix: 'company_lookup',
            );
        }

        yield FormField::addFieldset('Recherche automatique de l’entreprise')
            ->setIcon('fa fa-magnifying-glass');

        yield TextField::new('siret', 'SIRET')
            ->setColumns('col-12 col-lg-6')
            ->setFormTypeOption('attr', [
                'data-company-lookup' => 'true',
                'data-company-lookup-length' => 14,
                'data-company-lookup-label' => 'SIRET',
                'inputmode' => 'numeric',
                'maxlength' => 14,
                'placeholder' => '12345678901234',
            ])
            ->setHelp('Saisissez les 14 chiffres pour préremplir automatiquement les informations disponibles.')
            ->setRequired(false)
            ->hideOnIndex();

        yield TextField::new('siren', 'SIREN')
            ->setColumns('col-12 col-lg-6')
            ->setFormTypeOption('attr', [
                'data-company-lookup' => 'true',
                'inputmode' => 'numeric',
                'maxlength' => 9,
                'placeholder' => '123456789',
            ])
            ->setHelp('Saisissez les 9 chiffres pour préremplir automatiquement les informations disponibles.')
            ->setRequired(false)
            ->hideOnIndex();

        if ($isForm) {
            yield FormField::addTab(
                'Compte utilisateur',
                icon: 'fa fa-user',
                propertySuffix: 'user',
            );
        }

        yield FormField::addFieldset('Compte associé')
            ->setIcon('fa fa-user');

        yield IdField::new('id', 'ID')
            ->hideOnForm();

        if (Crud::PAGE_NEW === $pageName) {
            yield AssociationField::new('user', 'Compte artisan')
                ->setColumns('col-12')
                ->setRequired(true)
                ->renderAsEmbeddedForm(
                    UserCrudController::class,
                    'artisan_account',
                    'artisan_account',
                );

            yield TextField::new('artisanUserPassword', 'Mot de passe')
                ->setColumns('col-12 col-lg-6')
                ->setFormType(PasswordType::class)
                ->setFormTypeOption('property_path', 'user.password')
                ->setHelp('Le mot de passe est haché avant l’enregistrement.')
                ->setRequired(true);
        } else {
            yield AssociationField::new('user', 'Compte utilisateur')
                ->setColumns('col-12')
                ->setRequired(true)
                ->setQueryBuilder(
                    static fn (QueryBuilder $queryBuilder): QueryBuilder => $queryBuilder
                        ->andWhere('entity.type = :artisanType')
                        ->setParameter('artisanType', UserType::ARTISAN),
                )
                ->setFormTypeOption('choice_label', static function (User $user): string {
                    return sprintf(
                        '%s — %s',
                        $user->getFullName(),
                        $user->getEmail() ?? 'E-mail non renseigné',
                    );
                });
        }

        yield TextField::new('displayName', 'Artisan')
            ->onlyOnIndex();

        if ($isForm) {
            yield FormField::addTab(
                'Entreprise',
                icon: 'fa fa-building',
                propertySuffix: 'company',
            );
        }

        yield FormField::addFieldset('Identité de l’entreprise')
            ->setIcon('fa fa-building');

        yield TextField::new('legalName', 'Dénomination légale')
            ->setColumns('col-12 col-lg-7')
            ->setFormTypeOption('attr', [
                'data-artisan-legal-name' => 'true',
            ])
            ->setRequired(true);

        yield TextField::new('slug', 'Adresse publique / slug')
            ->setColumns('col-12 col-lg-6')
            ->setFormTypeOption('disabled', true)
            ->setFormTypeOption('attr', [
                'data-artisan-slug' => 'true',
            ])
            ->setHelp('Généré automatiquement depuis le nom de l’entreprise. En cas de doublon : -1, -2, etc.')
            ->onlyOnForms();

        yield TextField::new('commercialName', 'Nom commercial')
            ->setColumns('col-12 col-lg-6')
            ->setRequired(false);

        yield TextField::new('slug', 'Adresse publique / slug')
            ->setHelp('Généré automatiquement depuis le nom de l’entreprise. En cas de doublon : -1, -2, etc.')
            ->onlyOnDetail();

        yield TextField::new('legalForm', 'Forme juridique')
            ->setColumns('col-12 col-lg-6')
            ->setRequired(false)
            ->hideOnIndex();

        yield TextField::new('vatNumber', 'Numéro de TVA')
            ->setColumns('col-12 col-md-6 col-xl-3')
            ->setRequired(false)
            ->hideOnIndex();

        yield TextField::new('apeCode', 'Code APE')
            ->setColumns('col-12 col-md-6 col-xl-3')
            ->setHelp('Exemple : 43.22A.')
            ->setRequired(false)
            ->hideOnIndex();

        if ($isForm) {
            yield FormField::addTab(
                'Vérifications',
                icon: 'fa fa-shield-halved',
                propertySuffix: 'verification',
            );
        }

        yield FormField::addFieldset('Vérifications administratives')
            ->setIcon('fa fa-shield-halved');

        yield ChoiceField::new(
            'identityVerificationStatus',
            'Vérification de l’identité',
        )
            ->setChoices(VerificationStatus::cases())
            ->setColumns('col-12 col-lg-4')
            ->renderAsBadges(self::verificationBadges());

        yield ChoiceField::new(
            'companyVerificationStatus',
            'Vérification de l’entreprise',
        )
            ->setChoices(VerificationStatus::cases())
            ->setColumns('col-12 col-lg-4')
            ->renderAsBadges(self::verificationBadges());

        yield ChoiceField::new(
            'rneVerificationStatus',
            'Vérification RNE',
        )
            ->setChoices(VerificationStatus::cases())
            ->setColumns('col-12 col-lg-4')
            ->renderAsBadges(self::verificationBadges());

        yield BooleanField::new('isRegisteredInRne', 'Inscrit au RNE')
            ->setColumns('col-12 col-md-6');

        yield DateTimeField::new('rneVerifiedAt', 'RNE vérifié le')
            ->setColumns('col-12 col-md-6')
            ->setFormat('dd/MM/yyyy HH:mm')
            ->setRequired(false);

        /* ============================================================
         * QUALIFICATIONS
         * ============================================================ */
        if ($isForm) {
            yield FormField::addTab(
                'Qualifications',
                icon: 'fa fa-graduation-cap',
                propertySuffix: 'qualification',
            );
        }

        yield FormField::addFieldset('Qualification professionnelle')
            ->setIcon('fa fa-certificate');

        yield ChoiceField::new('qualificationType', 'Type de qualification')
            ->setChoices(QualificationType::cases())
            ->setColumns('col-12 col-lg-6')
            ->setRequired(false);

        yield ChoiceField::new(
            'qualificationVerificationStatus',
            'Statut de vérification',
        )
            ->setChoices(VerificationStatus::cases())
            ->setColumns('col-12 col-lg-6')
            ->renderAsBadges(self::verificationBadges());

        yield TextField::new('qualificationTitle', 'Intitulé du diplôme ou titre')
            ->setColumns('col-12 col-lg-6')
            ->setRequired(false);

        yield TextField::new('qualificationNumber', 'Numéro de qualification')
            ->setColumns('col-12 col-lg-3')
            ->setRequired(false);

        yield DateField::new('qualificationObtainedAt', 'Date d’obtention')
            ->setColumns('col-12 col-lg-3')
            ->setFormat('dd/MM/yyyy')
            ->setRequired(false);

        yield IntegerField::new('experienceYears', 'Années d’expérience')
            ->setColumns('col-12 col-md-4')
            ->setFormTypeOption('attr', ['min' => 0]);

        yield TextEditorField::new('description', 'Description professionnelle')
            ->setColumns('col-12')
            ->setRequired(false)
            ->hideOnIndex();

        if ($isForm) {
            yield FormField::addTab(
                'Responsable qualifié',
                icon: 'fa fa-user-check',
                propertySuffix: 'qualified_person',
            );
        }

        yield FormField::addFieldset('Personne qualifiée responsable')
            ->setIcon('fa fa-user-check');

        yield BooleanField::new(
            'underQualifiedPersonControl',
            'Activité sous le contrôle d’une personne qualifiée',
        )
            ->setColumns('col-12');

        yield TextField::new('qualifiedPersonFirstName', 'Prénom')
            ->setColumns('col-12 col-md-4')
            ->setRequired(false);

        yield TextField::new('qualifiedPersonLastName', 'Nom')
            ->setColumns('col-12 col-md-4')
            ->setRequired(false);

        yield TextField::new('qualifiedPersonPosition', 'Fonction')
            ->setColumns('col-12 col-md-4')
            ->setRequired(false);

        /* ============================================================
         * ASSURANCES
         * ============================================================ */
        if ($isForm) {
            yield FormField::addTab(
                'RC professionnelle',
                icon: 'fa fa-shield',
                propertySuffix: 'professional_liability',
            );
        }

        yield FormField::addFieldset('Responsabilité civile professionnelle')
            ->setIcon('fa fa-shield');

        yield BooleanField::new(
            'professionalLiabilityInsuranceRequired',
            'RC professionnelle obligatoire',
        )
            ->setColumns('col-12 col-md-6');

        yield BooleanField::new(
            'hasProfessionalLiabilityInsurance',
            'RC professionnelle souscrite',
        )
            ->setColumns('col-12 col-md-6');

        yield TextField::new(
            'professionalLiabilityInsurer',
            'Compagnie d’assurance',
        )
            ->setColumns('col-12 col-lg-6')
            ->setRequired(false);

        yield TextField::new(
            'professionalLiabilityPolicyNumber',
            'Numéro de contrat',
        )
            ->setColumns('col-12 col-lg-6')
            ->setRequired(false);

        yield DateField::new(
            'professionalLiabilityStartsAt',
            'Début de validité',
        )
            ->setColumns('col-12 col-md-6')
            ->setFormat('dd/MM/yyyy')
            ->setRequired(false);

        yield DateField::new(
            'professionalLiabilityExpiresAt',
            'Fin de validité',
        )
            ->setColumns('col-12 col-md-6')
            ->setFormat('dd/MM/yyyy')
            ->setRequired(false);

        yield ChoiceField::new(
            'professionalLiabilityVerificationStatus',
            'Vérification de la RC professionnelle',
        )
            ->setChoices(VerificationStatus::cases())
            ->setColumns('col-12 col-lg-6')
            ->renderAsBadges(self::verificationBadges());

        yield Field::new(
            'professionalLiabilityDocumentFile',
            'Attestation RC professionnelle',
        )
            ->setFormType(VichFileType::class)
            ->setFormTypeOptions([
                'required' => false,
                'allow_delete' => true,
                'download_uri' => true,
                'download_label' => 'Télécharger le document actuel',
            ])
            ->setColumns('col-12 col-lg-6')
            ->onlyOnForms();

        yield TextField::new(
            'professionalLiabilityDocumentName',
            'Document RC professionnelle',
        )
            ->onlyOnDetail();

        yield IntegerField::new(
            'professionalLiabilityDocumentSize',
            'Taille du document RC (octets)',
        )
            ->onlyOnDetail();

        yield TextField::new(
            'professionalLiabilityDocumentMimeType',
            'Type MIME du document RC',
        )
            ->onlyOnDetail();

        if ($isForm) {
            yield FormField::addTab(
                'Assurance décennale',
                icon: 'fa fa-house-circle-check',
                propertySuffix: 'decennial_insurance',
            );
        }

        yield FormField::addFieldset('Assurance décennale')
            ->setIcon('fa fa-house-circle-check');

        yield BooleanField::new(
            'decennialInsuranceRequired',
            'Assurance décennale obligatoire',
        )
            ->setColumns('col-12 col-md-6');

        yield BooleanField::new(
            'hasDecennialInsurance',
            'Assurance décennale souscrite',
        )
            ->setColumns('col-12 col-md-6');

        yield TextField::new('decennialInsurer', 'Compagnie d’assurance')
            ->setColumns('col-12 col-lg-6')
            ->setRequired(false);

        yield TextField::new('decennialPolicyNumber', 'Numéro de contrat')
            ->setColumns('col-12 col-lg-6')
            ->setRequired(false);

        yield DateField::new('decennialInsuranceStartsAt', 'Début de validité')
            ->setColumns('col-12 col-md-6')
            ->setFormat('dd/MM/yyyy')
            ->setRequired(false);

        yield DateField::new('decennialInsuranceExpiresAt', 'Fin de validité')
            ->setColumns('col-12 col-md-6')
            ->setFormat('dd/MM/yyyy')
            ->setRequired(false);

        yield TextField::new(
            'decennialGeographicalCoverage',
            'Zone géographique couverte',
        )
            ->setColumns('col-12 col-lg-6')
            ->setRequired(false);

        yield ChoiceField::new(
            'decennialInsuranceVerificationStatus',
            'Vérification de l’assurance décennale',
        )
            ->setChoices(VerificationStatus::cases())
            ->setColumns('col-12 col-lg-6')
            ->renderAsBadges(self::verificationBadges());

        yield Field::new(
            'decennialInsuranceDocumentFile',
            'Attestation d’assurance décennale',
        )
            ->setFormType(VichFileType::class)
            ->setFormTypeOptions([
                'required' => false,
                'allow_delete' => true,
                'download_uri' => true,
                'download_label' => 'Télécharger le document actuel',
            ])
            ->setColumns('col-12 col-lg-6')
            ->onlyOnForms();

        yield TextField::new(
            'decennialInsuranceDocumentName',
            'Document décennal',
        )
            ->onlyOnDetail();

        yield IntegerField::new(
            'decennialInsuranceDocumentSize',
            'Taille du document décennal (octets)',
        )
            ->onlyOnDetail();

        yield TextField::new(
            'decennialInsuranceDocumentMimeType',
            'Type MIME du document décennal',
        )
            ->onlyOnDetail();

        /* ============================================================
         * ADRESSE ET OPENSTREETMAP
         * ============================================================ */
        if ($isForm) {
            yield FormField::addTab(
                'Adresse',
                icon: 'fa fa-location-dot',
                propertySuffix: 'address',
            );
        }

        yield FormField::addFieldset('Adresse professionnelle')
            ->setIcon('fa fa-map-location-dot');

        yield TextField::new('road', 'Rechercher une adresse')
            ->setColumns('col-12 col-lg-9')
            ->setFormTypeOption('attr', [
                'data-osm-search' => 'true',
                'data-osm-endpoint' => '/admin/geocode',
                'autocomplete' => 'off',
                'spellcheck' => 'false',
                'placeholder' => 'Ex. 57 cours de la République, Le Havre',
            ])
            ->setHelp('Saisissez au moins 3 caractères puis sélectionnez une proposition OpenStreetMap.')
            ->setRequired(false);

        yield TextField::new('houseNumber', 'Numéro')
            ->setColumns('col-12 col-lg-3')
            ->setRequired(false);

        yield TextField::new('addressComplement', 'Complément d’adresse')
            ->setColumns('col-12')
            ->setRequired(false);

        yield TextField::new('postalCode', 'Code postal')
            ->setColumns('col-12 col-md-3')
            ->setRequired(false);

        yield TextField::new('city', 'Ville')
            ->setColumns('col-12 col-md-5')
            ->setRequired(false)
            ->hideOnIndex();

        yield TextField::new('countryCode', 'Code pays')
            ->setColumns('col-12 col-md-2')
            ->setRequired(false);

        yield TextField::new('country', 'Pays')
            ->setColumns('col-12 col-md-2')
            ->setRequired(false);

        if (Crud::PAGE_DETAIL === $pageName) {
        yield FormField::addFieldset('Découpage géographique OSM')
            ->setIcon('fa fa-map')
            ->onlyOnDetail();

        yield TextField::new('neighbourhood', 'Quartier')
            ->setColumns('col-12 col-md-4')
            ->setRequired(false);

        yield TextField::new('suburb', 'Faubourg / secteur')
            ->setColumns('col-12 col-md-4')
            ->setRequired(false);

        yield TextField::new('cityDistrict', 'Arrondissement / district')
            ->setColumns('col-12 col-md-4')
            ->setRequired(false);

        yield TextField::new('hamlet', 'Hameau')
            ->setColumns('col-12 col-md-4')
            ->setRequired(false);

        yield TextField::new('village', 'Village')
            ->setColumns('col-12 col-md-4')
            ->setRequired(false);

        yield TextField::new('town', 'Commune / town')
            ->setColumns('col-12 col-md-4')
            ->setRequired(false);

        yield TextField::new('municipality', 'Municipalité')
            ->setColumns('col-12 col-md-4')
            ->setRequired(false);

        yield TextField::new('county', 'Département / county')
            ->setColumns('col-12 col-md-4')
            ->setRequired(false);

        yield TextField::new('stateDistrict', 'District régional')
            ->setColumns('col-12 col-md-4')
            ->setRequired(false);

        yield TextField::new('state', 'État / région administrative')
            ->setColumns('col-12 col-md-6')
            ->setRequired(false);

        yield TextField::new('region', 'Région')
            ->setColumns('col-12 col-md-6')
            ->setRequired(false);

        yield TextareaField::new('osmDisplayName', 'Adresse complète OSM')
            ->setColumns('col-12')
            ->setRequired(false);

        yield FormField::addFieldset('Coordonnées et métadonnées OSM')
            ->setIcon('fa fa-crosshairs')
            ->onlyOnDetail();

        yield TextField::new('latitude', 'Latitude')
            ->setColumns('col-12 col-md-6')
            ->setRequired(false);

        yield TextField::new('longitude', 'Longitude')
            ->setColumns('col-12 col-md-6')
            ->setRequired(false);

        yield IntegerField::new('osmId', 'Identifiant OSM')
            ->setColumns('col-12 col-md-4')
            ->setRequired(false);

        yield ChoiceField::new('osmType', 'Type OSM')
            ->setChoices([
                'Nœud' => 'node',
                'Chemin' => 'way',
                'Relation' => 'relation',
            ])
            ->setColumns('col-12 col-md-4')
            ->setRequired(false);

        yield IntegerField::new('nominatimPlaceId', 'Place ID Nominatim')
            ->setColumns('col-12 col-md-4')
            ->setRequired(false);

        yield TextField::new('osmCategory', 'Catégorie OSM')
            ->setColumns('col-12 col-md-6')
            ->setRequired(false);

        yield TextField::new('osmPlaceType', 'Type de lieu OSM')
            ->setColumns('col-12 col-md-6')
            ->setRequired(false);
        }

        if ($isForm) {
            yield FormField::addTab(
                'Zone d’intervention',
                icon: 'fa fa-route',
                propertySuffix: 'intervention_zone',
            );
        }

        yield FormField::addFieldset('Zone d’intervention')
            ->setIcon('fa fa-route');

        yield TextField::new('latitude')
            ->setFormType(HiddenType::class)
            ->onlyOnForms();

        yield TextField::new('longitude')
            ->setFormType(HiddenType::class)
            ->onlyOnForms();

        yield IntegerField::new('travelRadiusKm', 'Rayon d’intervention')
            ->setFormType(RangeType::class)
            ->setFormTypeOptions([
                'attr' => [
                    'min' => 5,
                    'max' => 1000,
                    'step' => 5,
                ],
            ])
            ->setHelp('Rayon de 5 à 1 000 km. Déplacez le repère sur la carte ou ajustez le rayon : la zone se met à jour instantanément.')
            ->setColumns('col-12');

        yield BooleanField::new(
            'worksAtCustomerAddress',
            'Se déplace chez les clients',
        )
            ->setColumns('col-12 col-md-6');

        yield BooleanField::new(
            'receivesCustomers',
            'Reçoit les clients dans ses locaux',
        )
            ->setColumns('col-12 col-md-6');

        /* ============================================================
         * NOTIFICATIONS
         * ============================================================ */
        if ($isForm) {
            yield FormField::addTab(
                'Notifications',
                icon: 'fa fa-bell',
                propertySuffix: 'notifications',
            );
        }

        yield FormField::addFieldset('Préférences de notification')
            ->setIcon('fa fa-bell');

        yield BooleanField::new(
            'notificationNewRequests',
            'Nouvelles demandes',
        )
            ->setFormTypeOption(
                'property_path',
                'notificationPreferences.newRequestsEnabled',
            )
            ->setColumns('col-12 col-md-6')
            ->onlyOnForms();

        yield BooleanField::new(
            'notificationUrgentRequestsSms',
            'Demandes urgentes par SMS',
        )
            ->setFormTypeOption(
                'property_path',
                'notificationPreferences.urgentRequestsSmsEnabled',
            )
            ->setColumns('col-12 col-md-6')
            ->onlyOnForms();

        yield BooleanField::new(
            'notificationClientMessages',
            'Messages des clients',
        )
            ->setFormTypeOption(
                'property_path',
                'notificationPreferences.clientMessagesEnabled',
            )
            ->setColumns('col-12 col-md-6')
            ->onlyOnForms();

        yield BooleanField::new(
            'notificationNewReviews',
            'Nouveaux avis',
        )
            ->setFormTypeOption(
                'property_path',
                'notificationPreferences.newReviewsEnabled',
            )
            ->setColumns('col-12 col-md-6')
            ->onlyOnForms();

        yield BooleanField::new(
            'notificationQuoteReminders',
            'Rappels de devis',
        )
            ->setFormTypeOption(
                'property_path',
                'notificationPreferences.quoteRemindersEnabled',
            )
            ->setColumns('col-12 col-md-6')
            ->onlyOnForms();

        yield BooleanField::new(
            'notificationWeeklySummary',
            'Récapitulatif hebdomadaire',
        )
            ->setFormTypeOption(
                'property_path',
                'notificationPreferences.weeklySummaryEnabled',
            )
            ->setColumns('col-12 col-md-6')
            ->onlyOnForms();

        yield BooleanField::new(
            'notificationTipsAndNews',
            'Conseils et actualités TrouveMoi',
        )
            ->setFormTypeOption(
                'property_path',
                'notificationPreferences.tipsAndNewsEnabled',
            )
            ->setColumns('col-12')
            ->onlyOnForms();

        yield TextareaField::new(
            'notificationPreferencesSummary',
            'Préférences actives',
        )
            ->formatValue(
                static function (mixed $value, ArtisanProfile $artisan): string {
                    $preferences = $artisan->getNotificationPreferences();

                    if (!$preferences instanceof ArtisanNotificationPreferences) {
                        return 'Préférences absentes';
                    }

                    $items = [
                        'Nouvelles demandes' => $preferences->isNewRequestsEnabled(),
                        'Urgences par SMS' => $preferences->isUrgentRequestsSmsEnabled(),
                        'Messages clients' => $preferences->isClientMessagesEnabled(),
                        'Nouveaux avis' => $preferences->isNewReviewsEnabled(),
                        'Rappels de devis' => $preferences->isQuoteRemindersEnabled(),
                        'Récapitulatif hebdomadaire' => $preferences->isWeeklySummaryEnabled(),
                        'Conseils et actualités' => $preferences->isTipsAndNewsEnabled(),
                    ];

                    $active = [];
                    foreach ($items as $label => $enabled) {
                        if ($enabled) {
                            $active[] = $label;
                        }
                    }

                    return [] === $active ? 'Aucune notification active' : implode(', ', $active);
                },
            )
            ->onlyOnDetail();

        /* ============================================================
         * PUBLICATION ET SUIVI
         * ============================================================ */
        if ($isForm) {
            yield FormField::addTab(
                'Publication',
                icon: 'fa fa-globe',
                propertySuffix: 'publication',
            );
        }

        yield FormField::addFieldset('Publication du profil')
            ->setIcon('fa fa-globe');

        yield BooleanField::new('isPublished', 'Profil publié')
            ->setColumns('col-12 col-md-4')
            ->renderAsSwitch(false)
            ->hideOnIndex()
            ->hideWhenCreating()
            ->setHelp('La publication reste soumise aux vérifications légales requises.');

        yield BooleanField::new(
            'legallyReadyForPublication',
            'Prêt juridiquement pour publication',
        )
            ->formatValue(
                static fn (mixed $value, ArtisanProfile $artisan): bool =>
                $artisan->isLegallyReadyForPublication(),
            )
            ->renderAsSwitch(false)
            ->onlyOnDetail();

        yield DateTimeField::new('publishedAt', 'Publié le')
            ->setColumns('col-12 col-md-4')
            ->setFormat('dd/MM/yyyy HH:mm')
            ->hideOnForm();

        yield DateTimeField::new('validatedAt', 'Validé le')
            ->setColumns('col-12 col-md-4')
            ->setFormat('dd/MM/yyyy HH:mm')
            ->hideOnForm();

        yield TextareaField::new('rejectionReason', 'Motif de rejet')
            ->setColumns('col-12')
            ->onlyOnDetail();

        if ($isForm) {
            yield FormField::addTab(
                'Prestations',
                icon: 'fa fa-screwdriver-wrench',
                propertySuffix: 'services',
            );
        }

        yield FormField::addFieldset('Prestations')
            ->setIcon('fa fa-screwdriver-wrench');

        yield AssociationField::new('services', 'Prestations proposées')
            ->setColumns('col-12')
            ->setRequired(false)
            ->setFormTypeOption(
                'choice_label',
                static fn (ArtisanService $service): string => $service->getTitle()
                    ?? sprintf('Prestation #%d', $service->getId() ?? 0),
            )
            ->setFormTypeOption('by_reference', false)
            ->hideWhenCreating()
            ->hideOnIndex();

        yield FormField::addFieldset('Historique')
            ->setIcon('fa fa-clock')
            ->hideOnIndex();

        yield DateTimeField::new('createdAt', 'Créé le')
            ->setFormat('dd/MM/yyyy HH:mm')
            ->hideOnForm();

        yield DateTimeField::new('updatedAt', 'Modifié le')
            ->setFormat('dd/MM/yyyy HH:mm')
            ->hideOnForm();
    }

    /**
     * @param list<\BackedEnum> $cases
     *
     * @return array<string, int|string>
     */
    private static function enumChoices(array $cases): array
    {
        $choices = [];

        foreach ($cases as $case) {
            $label = ucfirst(
                mb_strtolower(
                    str_replace('_', ' ', $case->name),
                ),
            );

            $choices[$label] = $case->value;
        }

        return $choices;
    }

    /**
     * @return array<string, string>
     */
    private static function verificationBadges(): array
    {
        $badges = [];

        foreach (VerificationStatus::cases() as $case) {
            $badges[(string) $case->value] = match ($case->name) {
                'VERIFIED', 'APPROVED', 'VALIDATED' => 'success',
                'REJECTED', 'REFUSED', 'FAILED' => 'danger',
                'PENDING', 'SUBMITTED', 'IN_REVIEW' => 'warning',
                default => 'secondary',
            };
        }

        return $badges;
    }
}
