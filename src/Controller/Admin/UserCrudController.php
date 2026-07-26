<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Enum\UserStatus;
use App\Entity\Enum\UserType;
use App\Entity\Users\User;
use App\Entity\Users\UserPreferences;
use App\Entity\Users\UserProfile;
use App\Entity\Users\UserSession;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\FormBuilderInterface;

final class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    /**
     * Page NEW : garantit que les relations existent
     * pour que les property_path fonctionnent.
     */
    public function createEntity(string $entityFqcn): User
    {
        $user = new User();

        $user->setUserProfile(new UserProfile());
        $user->getOrCreatePreferences();

        return $user;
    }

    /**
     * Page EDIT : crée les relations manquantes
     * avant la construction du formulaire.
     */
    public function createEditFormBuilder(
        EntityDto $entityDto,
        KeyValueStore $formOptions,
        AdminContext $context,
    ): FormBuilderInterface {
        $user = $entityDto->getInstance();

        if ($user instanceof User) {
            if (null === $user->getUserProfile()) {
                $user->setUserProfile(new UserProfile());
            }

            $user->getOrCreatePreferences();
        }

        return parent::createEditFormBuilder(
            $entityDto,
            $formOptions,
            $context,
        );
    }

    /*
     * NOTE : le JS d'autocomplétion OSM (address-autocomplete.js)
     * est chargé globalement par DashboardController::configureAssets().
     * Ne pas le re-déclarer ici, sinon EasyAdmin lève l'erreur
     * « each asset can only be added once ».
     */

    /**
     * Renseigne geocodedAt si l'adresse a été géocodée
     * via l'autocomplétion OSM.
     */
    public function persistEntity(
        EntityManagerInterface $entityManager,
        $entityInstance,
    ): void {
        $this->stampGeocodedAt($entityInstance);

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(
        EntityManagerInterface $entityManager,
        $entityInstance,
    ): void {
        $this->stampGeocodedAt($entityInstance);

        parent::updateEntity($entityManager, $entityInstance);
    }

    private function stampGeocodedAt(mixed $entityInstance): void
    {
        if (!$entityInstance instanceof User) {
            return;
        }

        $profile = $entityInstance->getUserProfile();

        if (!$profile instanceof UserProfile) {
            return;
        }

        if ($profile->isGeocoded() && null === $profile->getGeocodedAt()) {
            $profile->setGeocodedAt(new \DateTimeImmutable());
        }

        if (!$profile->isGeocoded()) {
            $profile->setGeocodedAt(null);
        }
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs')
            ->setPageTitle(
                Crud::PAGE_INDEX,
                'Utilisateurs particuliers'
            )
            ->setPageTitle(
                Crud::PAGE_NEW,
                'Créer un utilisateur'
            )
            ->setPageTitle(
                Crud::PAGE_EDIT,
                static fn (User $user): string => sprintf(
                    'Modifier %s',
                    $user->getFullName()
                )
            )
            ->setPageTitle(
                Crud::PAGE_DETAIL,
                static fn (User $user): string => sprintf(
                    'Utilisateur : %s',
                    $user->getFullName()
                )
            )
            ->setDefaultSort([
                'createdAt' => 'DESC',
            ])
            ->setSearchFields([
                'id',
                'email',
                'firstName',
                'lastName',
                'phoneNumber',
                'userProfile.addressLine1',
                'userProfile.addressLine2',
                'userProfile.postalCode',
                'userProfile.city',
                'userProfile.district',
                'userProfile.region',
                'userProfile.department',
                'userProfile.countryCode',
                'userProfile.formattedAddress',
            ]);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    /**
     * Affiche uniquement les utilisateurs particuliers.
     */
    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters,
    ): QueryBuilder {
        $queryBuilder = parent::createIndexQueryBuilder(
            $searchDto,
            $entityDto,
            $fields,
            $filters,
        );

        return $queryBuilder
            ->leftJoin('entity.userProfile', 'userProfile')
            ->addSelect('userProfile')
            ->leftJoin('entity.preferences', 'preferences')
            ->addSelect('preferences')
            ->andWhere('entity.type = :userType')
            ->setParameter('userType', UserType::CUSTOMER);
    }

    public function configureFields(string $pageName): iterable
    {
        /*
         * ============================================================
         * INFORMATIONS PERSONNELLES
         * ============================================================
         */

        if (\in_array($pageName, [Crud::PAGE_NEW, Crud::PAGE_EDIT], true)) {
            yield FormField::addTab(
                'Identité',
                icon: 'fa fa-user',
                propertySuffix: 'identity',
            );
        }

        yield FormField::addFieldset('Informations personnelles')
            ->setIcon('fa fa-user');

        yield IdField::new('id', 'ID')
            ->hideOnForm();

        yield TextField::new('firstName', 'Prénom')
            ->setColumns('col-12 col-md-6');

        yield TextField::new('lastName', 'Nom')
            ->setColumns('col-12 col-md-6');

        yield TextField::new('fullName', 'Nom complet')
            ->onlyOnDetail();

        yield EmailField::new('email', 'Adresse e-mail')
            ->setColumns('col-12 col-lg-8');

        yield TelephoneField::new('phoneNumber', 'Téléphone')
            ->setColumns('col-12 col-lg-4');

        /*
         * ============================================================
         * SÉCURITÉ ET ACCÈS
         * ============================================================
         */

        if (\in_array($pageName, [Crud::PAGE_NEW, Crud::PAGE_EDIT], true)) {
            yield FormField::addTab(
                'Sécurité et consentements',
                icon: 'fa fa-shield-halved',
                propertySuffix: 'security',
            );
        }

        yield FormField::addFieldset('Sécurité et accès')
            ->setIcon('fa fa-lock')
            ->hideOnIndex();

        /*
         * User.roles
         */
        yield ArrayField::new('roles', 'Rôles Symfony')
            ->setColumns('col-12')
            ->hideOnIndex();

        /*
         * User.password (hash uniquement, jamais éditable ici)
         */
        yield TextField::new('passwordHashPreview', 'Mot de passe (hash)')
            ->formatValue(
                static function (mixed $value, User $user): string {
                    $hash = $user->getPassword();

                    if (null === $hash || '' === $hash) {
                        return 'Non défini';
                    }

                    return substr($hash, 0, 20).'…';
                }
            )
            ->onlyOnDetail();

        /*
         * ============================================================
         * TYPE ET STATUT
         * ============================================================
         */

        yield FormField::addFieldset('État du compte')
            ->setIcon('fa fa-shield-halved');

        yield ChoiceField::new('type', 'Type de compte')
            ->setChoices(self::getUserTypeChoices())
            ->renderExpanded(false)
            ->renderAsBadges([
                UserType::CUSTOMER->value => 'info',
            ])
            ->onlyOnDetail();

        yield ChoiceField::new('status', 'Statut')
            ->setColumns('col-12 col-md-4')
            ->setChoices(self::getUserStatusChoices())
            ->renderExpanded(false)
            ->renderAsBadges();

        yield BooleanField::new(
            'isVerified',
            'Adresse e-mail vérifiée'
        )
            ->setColumns('col-12 col-md-4');

        yield BooleanField::new(
            'isPhoneVerified',
            'Téléphone vérifié'
        )
            ->setColumns('col-12 col-md-4')
            ->hideOnIndex();

        /*
         * ============================================================
         * CONDITIONS GÉNÉRALES
         * ============================================================
         */

        yield FormField::addFieldset('Conditions générales')
            ->setIcon('fa fa-file-signature')
            ->hideOnIndex();

        yield BooleanField::new(
            'hasAcceptedTerms',
            'Conditions générales acceptées'
        )
            ->setColumns('col-12 col-md-6')
            ->hideOnIndex();

        yield DateTimeField::new(
            'termsAcceptedAt',
            'Date d’acceptation des conditions'
        )
            ->setFormat('dd/MM/yyyy HH:mm')
            ->hideOnForm()
            ->onlyOnDetail();

        yield TextField::new(
            'termsVersion',
            'Version des conditions'
        )->onlyOnDetail();

        /*
         * ============================================================
         * POLITIQUE DE CONFIDENTIALITÉ
         * ============================================================
         */

        yield FormField::addFieldset('Confidentialité')
            ->setIcon('fa fa-user-shield')
            ->hideOnIndex();

        yield BooleanField::new(
            'hasAcceptedPrivacyPolicy',
            'Politique de confidentialité acceptée'
        )
            ->setColumns('col-12 col-md-6')
            ->hideOnIndex();

        yield DateTimeField::new(
            'privacyPolicyAcceptedAt',
            'Date d’acceptation de la confidentialité'
        )
            ->setFormat('dd/MM/yyyy HH:mm')
            ->hideOnForm()
            ->onlyOnDetail();

        yield TextField::new(
            'privacyPolicyVersion',
            'Version de la politique de confidentialité'
        )->onlyOnDetail();

        /*
         * ============================================================
         * CONSENTEMENT MARKETING
         * ============================================================
         */

        yield FormField::addFieldset('Marketing')
            ->setIcon('fa fa-bullhorn')
            ->hideOnIndex();

        yield BooleanField::new(
            'marketingConsent',
            'Consentement marketing'
        )
            ->setColumns('col-12 col-md-6')
            ->hideOnIndex();

        yield DateTimeField::new(
            'marketingConsentAt',
            'Date du consentement marketing'
        )
            ->setFormat('dd/MM/yyyy HH:mm')
            ->hideOnForm()
            ->onlyOnDetail();

        /*
         * ============================================================
         * PARAMÈTRES DU COMPTE
         * ============================================================
         */

        yield FormField::addFieldset('Paramètres du compte')
            ->setIcon('fa fa-gear')
            ->hideOnIndex();

        yield TextField::new('locale', 'Langue')
            ->setColumns('col-12 col-md-4')
            ->hideOnIndex();

        yield TextField::new('countryCode', 'Pays du compte')
            ->setColumns('col-12 col-md-4')
            ->hideOnIndex();

        yield TextField::new('timezone', 'Fuseau horaire')
            ->setColumns('col-12 col-md-4')
            ->hideOnIndex();

        yield TextField::new(
            'avatarFilename',
            'Nom du fichier avatar'
        )
            ->hideOnForm()
            ->onlyOnDetail();

        /*
         * ============================================================
         * USER PROFILE
         * ------------------------------------------------------------
         * Lecture : formatValue (index + detail)
         * Écriture : property_path vers userProfile.* (new + edit)
         * ============================================================
         */

        if (\in_array($pageName, [Crud::PAGE_NEW, Crud::PAGE_EDIT], true)) {
            yield FormField::addTab(
                'Adresse et géolocalisation',
                icon: 'fa fa-location-dot',
                propertySuffix: 'address',
            );
        }

        yield FormField::addFieldset('Profil utilisateur')
            ->setIcon('fa fa-address-card');

        /*
         * UserProfile.id (lecture seule)
         */
        yield TextField::new('profileId', 'ID du profil')
            ->formatValue(
                static fn (
                    mixed $value,
                    User $user
                ): string => self::getProfileValue(
                    $user,
                    static fn (UserProfile $profile): ?int =>
                        $profile->getId()
                )
            )
            ->onlyOnDetail();

        /*
         * UserProfile.user (lecture seule)
         */
        yield TextField::new(
            'profileLinkedUser',
            'Utilisateur associé au profil'
        )
            ->formatValue(
                static function (
                    mixed $value,
                    User $user
                ): string {
                    $profileUser = $user
                        ->getUserProfile()
                        ?->getUser();

                    if (!$profileUser instanceof User) {
                        return 'Profil absent';
                    }

                    return sprintf(
                        '%s — %s',
                        $profileUser->getFullName(),
                        $profileUser->getEmail() ?? 'E-mail absent'
                    );
                }
            )
            ->onlyOnDetail();

        /*
         * UserProfile.type (lecture + écriture)
         */
        yield ChoiceField::new('profileType', 'Type d’adresse')
            ->setColumns('col-12 col-lg-4')
            ->setChoices([
                'Domicile' => 'HOME',
                'Facturation' => 'BILLING',
                'Travail' => 'WORK',
                'Siège social' => 'HEADQUARTERS',
            ])
            ->setFormTypeOption('property_path', 'userProfile.type')
            ->setRequired(true)
            ->formatValue(
                static fn (
                    mixed $value,
                    User $user
                ): string => self::getProfileValue(
                    $user,
                    static fn (UserProfile $profile): string =>
                        $profile->getType()
                )
            )
            ->hideOnIndex();

        /*
         * UserProfile.label (lecture + écriture)
         */
        yield TextField::new('profileLabel', 'Libellé de l’adresse')
            ->setColumns('col-12 col-lg-4')
            ->setFormTypeOption('property_path', 'userProfile.label')
            ->setFormTypeOption('attr', [
                'autocomplete' => 'off',
                'placeholder' => 'Ex. Domicile, résidence principale…',
            ])
            ->setHelp('Libellé interne de cette adresse.')
            ->setRequired(false)
            ->formatValue(
                static fn (
                    mixed $value,
                    User $user
                ): string => self::getProfileValue(
                    $user,
                    static fn (UserProfile $profile): ?string =>
                        $profile->getLabel()
                )
            )
            ->hideOnIndex();

        /*
         * UserProfile.addressLine1 (lecture + écriture)
         */
        yield TextField::new('profileAddressLine1', 'Rechercher une adresse')
            ->setColumns('col-12 col-lg-8')
            ->setFormTypeOption(
                'property_path',
                'userProfile.addressLine1'
            )
            ->setFormTypeOption('attr', [
                'data-osm-search' => 'true',
                'data-osm-endpoint' => '/admin/geocode',
                'autocomplete' => 'off',
                'spellcheck' => 'false',
                'placeholder' => 'Ex. 57 cours de la République, Le Havre',
            ])
            ->setHelp(
                'Saisissez au moins 3 caractères, puis sélectionnez '
                .'une proposition OpenStreetMap.'
            )
            ->setRequired(true)
            ->formatValue(
                static fn (
                    mixed $value,
                    User $user
                ): string => self::getProfileValue(
                    $user,
                    static fn (UserProfile $profile): ?string =>
                        $profile->getAddressLine1()
                )
            )
            ->hideOnIndex();

        /*
         * UserProfile.addressLine2 (lecture + écriture)
         */
        yield TextField::new(
            'profileAddressLine2',
            'Complément d’adresse'
        )
            ->setColumns('col-12 col-lg-4')
            ->setFormTypeOption(
                'property_path',
                'userProfile.addressLine2'
            )
            ->setRequired(false)
            ->formatValue(
                static fn (
                    mixed $value,
                    User $user
                ): string => self::getProfileValue(
                    $user,
                    static fn (UserProfile $profile): ?string =>
                        $profile->getAddressLine2()
                )
            )
            ->hideOnIndex();

        /*
         * UserProfile.postalCode (lecture + écriture)
         */
        yield TextField::new('profilePostalCode', 'Code postal')
            ->setColumns('col-12 col-md-3')
            ->setFormTypeOption(
                'property_path',
                'userProfile.postalCode'
            )
            ->setRequired(true)
            ->formatValue(
                static fn (
                    mixed $value,
                    User $user
                ): string => self::getProfileValue(
                    $user,
                    static fn (UserProfile $profile): ?string =>
                        $profile->getPostalCode()
                )
            );

        /*
         * UserProfile.city (lecture + écriture)
         */
        yield TextField::new('profileCity', 'Ville')
            ->setColumns('col-12 col-md-5')
            ->setFormTypeOption('property_path', 'userProfile.city')
            ->setRequired(true)
            ->formatValue(
                static fn (
                    mixed $value,
                    User $user
                ): string => self::getProfileValue(
                    $user,
                    static fn (UserProfile $profile): ?string =>
                        $profile->getCity()
                )
            );

        /*
         * UserProfile.district (lecture + écriture)
         */
        yield TextField::new('profileDistrict', 'Quartier')
            ->setColumns('col-12 col-md-4')
            ->setFormTypeOption('property_path', 'userProfile.district')
            ->setRequired(false)
            ->formatValue(
                static fn (
                    mixed $value,
                    User $user
                ): string => self::getProfileValue(
                    $user,
                    static fn (UserProfile $profile): ?string =>
                        $profile->getDistrict()
                )
            )
            ->hideOnIndex();

        /*
         * UserProfile.region (lecture + écriture)
         */
        yield TextField::new('profileRegion', 'Région')
            ->setColumns('col-12 col-md-4')
            ->setFormTypeOption('property_path', 'userProfile.region')
            ->setRequired(false)
            ->formatValue(
                static fn (
                    mixed $value,
                    User $user
                ): string => self::getProfileValue(
                    $user,
                    static fn (UserProfile $profile): ?string =>
                        $profile->getRegion()
                )
            )
            ->hideOnIndex();

        /*
         * UserProfile.department (lecture + écriture)
         */
        yield TextField::new('profileDepartment', 'Département')
            ->setColumns('col-12 col-md-4')
            ->setFormTypeOption(
                'property_path',
                'userProfile.department'
            )
            ->setRequired(false)
            ->formatValue(
                static fn (
                    mixed $value,
                    User $user
                ): string => self::getProfileValue(
                    $user,
                    static fn (UserProfile $profile): ?string =>
                        $profile->getDepartment()
                )
            )
            ->hideOnIndex();

        /*
         * UserProfile.countryCode (lecture + écriture)
         */
        yield TextField::new('profileCountryCode', 'Pays de l’adresse')
            ->setColumns('col-12 col-md-4')
            ->setFormTypeOption(
                'property_path',
                'userProfile.countryCode'
            )
            ->setRequired(true)
            ->setHelp('Code ISO 3166-1 alpha-2 : FR, BE, DE…')
            ->formatValue(
                static fn (
                    mixed $value,
                    User $user
                ): string => self::getProfileValue(
                    $user,
                    static fn (UserProfile $profile): string =>
                        $profile->getCountryCode()
                )
            )
            ->hideOnIndex();

        /*
         * UserProfile.formattedAddress (lecture + écriture)
         */
        yield TextField::new(
            'profileFormattedAddress',
            'Adresse complète'
        )
            ->setColumns('col-12')
            ->setFormTypeOption(
                'property_path',
                'userProfile.formattedAddress'
            )
            ->setRequired(false)
            ->formatValue(
                static fn (
                    mixed $value,
                    User $user
                ): string => self::getProfileValue(
                    $user,
                    static fn (UserProfile $profile): ?string =>
                        $profile->getFormattedAddress()
                )
            );

        /*
         * ============================================================
         * GÉOLOCALISATION
         * ============================================================
         */

        yield FormField::addFieldset('Géolocalisation')
            ->setIcon('fa fa-map-location-dot')
            ->hideOnIndex();

        /*
         * UserProfile.providerPlaceId (lecture + écriture)
         */
        yield TextField::new(
            'profileProviderPlaceId',
            'Identifiant du lieu'
        )
            ->setColumns('col-12 col-lg-6')
            ->setFormTypeOption(
                'property_path',
                'userProfile.providerPlaceId'
            )
            ->setRequired(false)
            ->formatValue(
                static fn (
                    mixed $value,
                    User $user
                ): string => self::getProfileValue(
                    $user,
                    static fn (UserProfile $profile): ?string =>
                        $profile->getProviderPlaceId()
                )
            )
            ->hideOnIndex();

        /*
         * UserProfile.providerName (lecture + écriture)
         */
        yield TextField::new(
            'profileProviderName',
            'Service de géocodage'
        )
            ->setColumns('col-12 col-lg-6')
            ->setFormTypeOption(
                'property_path',
                'userProfile.providerName'
            )
            ->setRequired(false)
            ->setHelp('Mapbox, Google, OSM…')
            ->formatValue(
                static fn (
                    mixed $value,
                    User $user
                ): string => self::getProfileValue(
                    $user,
                    static fn (UserProfile $profile): ?string =>
                        $profile->getProviderName()
                )
            )
            ->hideOnIndex();

        /*
         * UserProfile.latitude (lecture + écriture)
         */
        yield TextField::new('profileLatitude', 'Latitude')
            ->setColumns('col-12 col-md-6')
            ->setFormTypeOption('property_path', 'userProfile.latitude')
            ->setRequired(false)
            ->formatValue(
                static fn (
                    mixed $value,
                    User $user
                ): string => self::getProfileValue(
                    $user,
                    static fn (UserProfile $profile): ?string =>
                        $profile->getLatitude()
                )
            )
            ->hideOnIndex();

        /*
         * UserProfile.longitude (lecture + écriture)
         */
        yield TextField::new('profileLongitude', 'Longitude')
            ->setColumns('col-12 col-md-6')
            ->setFormTypeOption(
                'property_path',
                'userProfile.longitude'
            )
            ->setRequired(false)
            ->formatValue(
                static fn (
                    mixed $value,
                    User $user
                ): string => self::getProfileValue(
                    $user,
                    static fn (UserProfile $profile): ?string =>
                        $profile->getLongitude()
                )
            )
            ->hideOnIndex();

        /*
         * UserProfile.isDefault (écriture : formulaire)
         */
        yield BooleanField::new(
            'profileIsDefaultForm',
            'Adresse par défaut'
        )
            ->setColumns('col-12 col-sm-6 col-xl-3')
            ->setFormTypeOption(
                'property_path',
                'userProfile.isDefault'
            )
            ->onlyOnForms();

        /*
         * UserProfile.isDefault (lecture : détail)
         */
        yield TextField::new('profileIsDefault', 'Adresse par défaut')
            ->formatValue(
                static fn (
                    mixed $value,
                    User $user
                ): string => self::getProfileBooleanValue(
                    $user,
                    static fn (UserProfile $profile): bool =>
                        $profile->isDefault()
                )
            )
            ->onlyOnDetail();

        /*
         * UserProfile.isBillingAddress (écriture : formulaire)
         */
        yield BooleanField::new(
            'profileIsBillingAddressForm',
            'Adresse de facturation'
        )
            ->setColumns('col-12 col-sm-6 col-xl-3')
            ->setFormTypeOption(
                'property_path',
                'userProfile.isBillingAddress'
            )
            ->onlyOnForms();

        /*
         * UserProfile.isBillingAddress (lecture : détail)
         */
        yield TextField::new(
            'profileIsBillingAddress',
            'Adresse de facturation'
        )
            ->formatValue(
                static fn (
                    mixed $value,
                    User $user
                ): string => self::getProfileBooleanValue(
                    $user,
                    static fn (UserProfile $profile): bool =>
                        $profile->isBillingAddress()
                )
            )
            ->onlyOnDetail();

        /*
         * UserProfile.isPublic (écriture : formulaire)
         */
        yield BooleanField::new(
            'profileIsPublicForm',
            'Adresse publique'
        )
            ->setColumns('col-12 col-sm-6 col-xl-3')
            ->setFormTypeOption('property_path', 'userProfile.isPublic')
            ->onlyOnForms();

        /*
         * UserProfile.isPublic (lecture : détail)
         */
        yield TextField::new('profileIsPublic', 'Adresse publique')
            ->formatValue(
                static fn (
                    mixed $value,
                    User $user
                ): string => self::getProfileBooleanValue(
                    $user,
                    static fn (UserProfile $profile): bool =>
                        $profile->isPublic()
                )
            )
            ->onlyOnDetail();

        /*
         * UserProfile.isGeocoded (écriture : formulaire)
         */
        yield BooleanField::new(
            'profileIsGeocodedForm',
            'Adresse géocodée'
        )
            ->setColumns('col-12 col-sm-6 col-xl-3')
            ->setFormTypeOption(
                'property_path',
                'userProfile.isGeocoded'
            )
            ->onlyOnForms();

        /*
         * UserProfile.isGeocoded (lecture : détail)
         */
        yield TextField::new('profileIsGeocoded', 'Adresse géocodée')
            ->formatValue(
                static fn (
                    mixed $value,
                    User $user
                ): string => self::getProfileBooleanValue(
                    $user,
                    static fn (UserProfile $profile): bool =>
                        $profile->isGeocoded()
                )
            )
            ->onlyOnDetail();

        /*
         * UserProfile.geocodedAt (lecture seule)
         */
        yield TextField::new('profileGeocodedAt', 'Date du géocodage')
            ->formatValue(
                static function (
                    mixed $value,
                    User $user
                ): string {
                    $profile = $user->getUserProfile();

                    if (!$profile instanceof UserProfile) {
                        return 'Profil absent';
                    }

                    return $profile
                        ->getGeocodedAt()
                        ?->format('d/m/Y H:i')
                        ?? 'Non renseigné';
                }
            )
            ->onlyOnDetail();

        /*
         * ============================================================
         * DATES DU PROFIL (lecture seule)
         * ============================================================
         */

        yield FormField::addFieldset('Historique du profil')
            ->setIcon('fa fa-clock-rotate-left')
            ->onlyOnDetail();

        /*
         * UserProfile.createdAt
         */
        yield TextField::new(
            'profileCreatedAt',
            'Date de création du profil'
        )
            ->formatValue(
                static function (
                    mixed $value,
                    User $user
                ): string {
                    $profile = $user->getUserProfile();

                    if (!$profile instanceof UserProfile) {
                        return 'Profil absent';
                    }

                    return $profile
                        ->getCreatedAt()
                        ->format('d/m/Y H:i');
                }
            )
            ->onlyOnDetail();

        /*
         * UserProfile.updatedAt
         */
        yield TextField::new(
            'profileUpdatedAt',
            'Dernière modification du profil'
        )
            ->formatValue(
                static function (
                    mixed $value,
                    User $user
                ): string {
                    $profile = $user->getUserProfile();

                    if (!$profile instanceof UserProfile) {
                        return 'Profil absent';
                    }

                    return $profile
                        ->getUpdatedAt()
                        ->format('d/m/Y H:i');
                }
            )
            ->onlyOnDetail();

        /*
         * ============================================================
         * PRÉFÉRENCES — NOTIFICATIONS "MES PROJETS"
         * ------------------------------------------------------------
         * Lecture : formatValue (detail)
         * Écriture : property_path vers preferences.* (new + edit)
         * ============================================================
         */

        if (\in_array($pageName, [Crud::PAGE_NEW, Crud::PAGE_EDIT], true)) {
            yield FormField::addTab(
                'Préférences',
                icon: 'fa fa-sliders',
                propertySuffix: 'preferences',
            );
        }

        yield FormField::addFieldset('Préférences — Notifications')
            ->setIcon('fa fa-bell')
            ->hideOnIndex();

        /*
         * UserPreferences.id (lecture seule)
         */
        yield TextField::new('preferencesId', 'ID des préférences')
            ->formatValue(
                static fn (
                    mixed $value,
                    User $user
                ): string => self::getPreferencesValue(
                    $user,
                    static fn (UserPreferences $preferences): ?int =>
                        $preferences->getId()
                )
            )
            ->onlyOnDetail();

        /*
         * UserPreferences.user (lecture seule)
         */
        yield TextField::new(
            'preferencesLinkedUser',
            'Utilisateur associé aux préférences'
        )
            ->formatValue(
                static function (
                    mixed $value,
                    User $user
                ): string {
                    $preferencesUser = $user
                        ->getPreferences()
                        ?->getUser();

                    if (!$preferencesUser instanceof User) {
                        return 'Préférences absentes';
                    }

                    return sprintf(
                        '%s — %s',
                        $preferencesUser->getFullName(),
                        $preferencesUser->getEmail() ?? 'E-mail absent'
                    );
                }
            )
            ->onlyOnDetail();

        /*
         * UserPreferences.newQuotesEnabled (écriture : formulaire)
         */
        yield BooleanField::new(
            'preferencesNewQuotesForm',
            'Nouveaux devis'
        )
            ->setColumns('col-12 col-md-6')
            ->setFormTypeOption(
                'property_path',
                'preferences.newQuotesEnabled'
            )
            ->onlyOnForms();

        /*
         * UserPreferences.newQuotesEnabled (lecture : détail)
         */
        yield TextField::new('preferencesNewQuotes', 'Nouveaux devis')
            ->formatValue(
                static fn (
                    mixed $value,
                    User $user
                ): string => self::getPreferencesBooleanValue(
                    $user,
                    static fn (UserPreferences $preferences): bool =>
                        $preferences->isNewQuotesEnabled()
                )
            )
            ->onlyOnDetail();

        /*
         * UserPreferences.artisanMessagesEnabled (écriture : formulaire)
         */
        yield BooleanField::new(
            'preferencesArtisanMessagesForm',
            'Messages des artisans'
        )
            ->setColumns('col-12 col-md-6')
            ->setFormTypeOption(
                'property_path',
                'preferences.artisanMessagesEnabled'
            )
            ->onlyOnForms();

        /*
         * UserPreferences.artisanMessagesEnabled (lecture : détail)
         */
        yield TextField::new(
            'preferencesArtisanMessages',
            'Messages des artisans'
        )
            ->formatValue(
                static fn (
                    mixed $value,
                    User $user
                ): string => self::getPreferencesBooleanValue(
                    $user,
                    static fn (UserPreferences $preferences): bool =>
                        $preferences->isArtisanMessagesEnabled()
                )
            )
            ->onlyOnDetail();

        /*
         * UserPreferences.appointmentRemindersEnabled
         * (écriture : formulaire)
         */
        yield BooleanField::new(
            'preferencesAppointmentRemindersForm',
            'Rappels de rendez-vous'
        )
            ->setColumns('col-12 col-md-6')
            ->setFormTypeOption(
                'property_path',
                'preferences.appointmentRemindersEnabled'
            )
            ->onlyOnForms();

        /*
         * UserPreferences.appointmentRemindersEnabled
         * (lecture : détail)
         */
        yield TextField::new(
            'preferencesAppointmentReminders',
            'Rappels de rendez-vous'
        )
            ->formatValue(
                static fn (
                    mixed $value,
                    User $user
                ): string => self::getPreferencesBooleanValue(
                    $user,
                    static fn (UserPreferences $preferences): bool =>
                        $preferences->isAppointmentRemindersEnabled()
                )
            )
            ->onlyOnDetail();

        /*
         * UserPreferences.reviewInvitationsEnabled
         * (écriture : formulaire)
         */
        yield BooleanField::new(
            'preferencesReviewInvitationsForm',
            'Invitations à laisser un avis'
        )
            ->setColumns('col-12 col-md-6')
            ->setFormTypeOption(
                'property_path',
                'preferences.reviewInvitationsEnabled'
            )
            ->onlyOnForms();

        /*
         * UserPreferences.reviewInvitationsEnabled
         * (lecture : détail)
         */
        yield TextField::new(
            'preferencesReviewInvitations',
            'Invitations à laisser un avis'
        )
            ->formatValue(
                static fn (
                    mixed $value,
                    User $user
                ): string => self::getPreferencesBooleanValue(
                    $user,
                    static fn (UserPreferences $preferences): bool =>
                        $preferences->isReviewInvitationsEnabled()
                )
            )
            ->onlyOnDetail();

        /*
         * ============================================================
         * PRÉFÉRENCES — VISIBILITÉ
         * ============================================================
         */

        yield FormField::addFieldset('Préférences — Visibilité')
            ->setIcon('fa fa-eye')
            ->hideOnIndex();

        /*
         * UserPreferences.profileVisibleToArtisans
         * (écriture : formulaire)
         */
        yield BooleanField::new(
            'preferencesProfileVisibleForm',
            'Profil visible par les artisans'
        )
            ->setColumns('col-12 col-md-6')
            ->setFormTypeOption(
                'property_path',
                'preferences.profileVisibleToArtisans'
            )
            ->onlyOnForms();

        /*
         * UserPreferences.profileVisibleToArtisans
         * (lecture : détail)
         */
        yield TextField::new(
            'preferencesProfileVisible',
            'Profil visible par les artisans'
        )
            ->formatValue(
                static fn (
                    mixed $value,
                    User $user
                ): string => self::getPreferencesBooleanValue(
                    $user,
                    static fn (UserPreferences $preferences): bool =>
                        $preferences->isProfileVisibleToArtisans()
                )
            )
            ->onlyOnDetail();

        /*
         * UserPreferences.phoneSharedAfterAcceptance
         * (écriture : formulaire)
         */
        yield BooleanField::new(
            'preferencesPhoneSharedForm',
            'Téléphone partagé après acceptation'
        )
            ->setColumns('col-12 col-md-6')
            ->setFormTypeOption(
                'property_path',
                'preferences.phoneSharedAfterAcceptance'
            )
            ->onlyOnForms();

        /*
         * UserPreferences.phoneSharedAfterAcceptance
         * (lecture : détail)
         */
        yield TextField::new(
            'preferencesPhoneShared',
            'Téléphone partagé après acceptation'
        )
            ->formatValue(
                static fn (
                    mixed $value,
                    User $user
                ): string => self::getPreferencesBooleanValue(
                    $user,
                    static fn (UserPreferences $preferences): bool =>
                        $preferences->isPhoneSharedAfterAcceptance()
                )
            )
            ->onlyOnDetail();

        /*
         * UserPreferences.createdAt (lecture seule)
         */
        yield TextField::new(
            'preferencesCreatedAt',
            'Date de création des préférences'
        )
            ->formatValue(
                static function (
                    mixed $value,
                    User $user
                ): string {
                    $preferences = $user->getPreferences();

                    if (!$preferences instanceof UserPreferences) {
                        return 'Préférences absentes';
                    }

                    return $preferences
                        ->getCreatedAt()
                        ->format('d/m/Y H:i');
                }
            )
            ->onlyOnDetail();

        /*
         * UserPreferences.updatedAt (lecture seule)
         */
        yield TextField::new(
            'preferencesUpdatedAt',
            'Dernière modification des préférences'
        )
            ->formatValue(
                static function (
                    mixed $value,
                    User $user
                ): string {
                    $preferences = $user->getPreferences();

                    if (!$preferences instanceof UserPreferences) {
                        return 'Préférences absentes';
                    }

                    return $preferences
                        ->getUpdatedAt()
                        ->format('d/m/Y H:i');
                }
            )
            ->onlyOnDetail();

        /*
         * ============================================================
         * SESSIONS ACTIVES (lecture seule : générées à la connexion)
         * ============================================================
         */

        yield FormField::addFieldset('Sessions actives')
            ->setIcon('fa fa-desktop')
            ->onlyOnDetail();

        /*
         * User.sessions (nombre)
         */
        yield TextField::new('sessionsCount', 'Nombre de sessions')
            ->formatValue(
                static fn (
                    mixed $value,
                    User $user
                ): string => (string) $user->getSessions()->count()
            )
            ->onlyOnDetail();

        /*
         * User.sessions (détail complet : tous les champs de UserSession)
         */
        yield TextField::new('sessionsDetails', 'Détail des sessions')
            ->formatValue(
                static function (
                    mixed $value,
                    User $user
                ): string {
                    $sessions = $user->getSessions();

                    if ($sessions->isEmpty()) {
                        return 'Aucune session';
                    }

                    $rows = [];

                    foreach ($sessions as $session) {
                        if (!$session instanceof UserSession) {
                            continue;
                        }

                        $rows[] = sprintf(
                            '<tr>
                                <td>%s</td>
                                <td><code>%s</code></td>
                                <td>%s</td>
                                <td>%s</td>
                                <td>%s</td>
                                <td>%s</td>
                                <td>%s</td>
                                <td>%s</td>
                                <td>%s</td>
                            </tr>',
                            htmlspecialchars(
                                (string) $session->getId(),
                                ENT_QUOTES
                            ),
                            htmlspecialchars(
                                substr(
                                    (string) $session->getSessionToken(),
                                    0,
                                    12
                                ).'…',
                                ENT_QUOTES
                            ),
                            htmlspecialchars(
                                $session->getDeviceLabel()
                                    ?? 'Non renseigné',
                                ENT_QUOTES
                            ),
                            htmlspecialchars(
                                $session->getUserAgent()
                                    ?? 'Non renseigné',
                                ENT_QUOTES
                            ),
                            htmlspecialchars(
                                $session->getIpAddress()
                                    ?? 'Non renseignée',
                                ENT_QUOTES
                            ),
                            htmlspecialchars(
                                $session->getCity()
                                    ?? 'Non renseignée',
                                ENT_QUOTES
                            ),
                            htmlspecialchars(
                                $session->getCountryCode()
                                    ?? 'Non renseigné',
                                ENT_QUOTES
                            ),
                            $session
                                ->getCreatedAt()
                                ?->format('d/m/Y H:i')
                                ?? 'Non renseignée',
                            $session
                                ->getLastActivityAt()
                                ?->format('d/m/Y H:i')
                                ?? 'Non renseignée',
                        );
                    }

                    return sprintf(
                        '<table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Jeton</th>
                                    <th>Appareil</th>
                                    <th>Navigateur (User-Agent)</th>
                                    <th>Adresse IP</th>
                                    <th>Ville</th>
                                    <th>Pays</th>
                                    <th>Créée le</th>
                                    <th>Dernière activité</th>
                                </tr>
                            </thead>
                            <tbody>%s</tbody>
                        </table>',
                        implode('', $rows)
                    );
                }
            )
            ->renderAsHtml()
            ->onlyOnDetail();

        /*
         * ============================================================
         * HISTORIQUE DU COMPTE
         * ============================================================
         */

        yield FormField::addFieldset('Historique du compte')
            ->setIcon('fa fa-clock')
            ->hideOnForm();

        yield DateTimeField::new(
            'createdAt',
            'Date d’inscription'
        )
            ->setFormat('dd/MM/yyyy HH:mm')
            ->hideOnForm();

        yield DateTimeField::new(
            'updatedAt',
            'Dernière modification du compte'
        )
            ->setFormat('dd/MM/yyyy HH:mm')
            ->hideOnForm()
            ->hideOnIndex();

        yield DateTimeField::new(
            'lastLoginAt',
            'Dernière connexion'
        )
            ->setFormat('dd/MM/yyyy HH:mm')
            ->hideOnForm()
            ->hideOnIndex();

        yield DateTimeField::new(
            'suspendedAt',
            'Date de suspension'
        )
            ->setFormat('dd/MM/yyyy HH:mm')
            ->hideOnForm()
            ->onlyOnDetail();

        yield TextField::new(
            'suspensionReason',
            'Motif de suspension'
        )->onlyOnDetail();

        yield DateTimeField::new(
            'deletedAt',
            'Date de suppression'
        )
            ->setFormat('dd/MM/yyyy HH:mm')
            ->hideOnForm()
            ->onlyOnDetail();
    }

    /**
     * @return array<string, UserType>
     */
    private static function getUserTypeChoices(): array
    {
        $choices = [];

        foreach (UserType::cases() as $case) {
            $choices[self::formatEnumLabel($case->name)] = $case;
        }

        return $choices;
    }

    /**
     * @return array<string, UserStatus>
     */
    private static function getUserStatusChoices(): array
    {
        $choices = [];

        foreach (UserStatus::cases() as $case) {
            $choices[self::formatEnumLabel($case->name)] = $case;
        }

        return $choices;
    }

    private static function formatEnumLabel(string $name): string
    {
        return ucfirst(
            strtolower(
                str_replace('_', ' ', $name)
            )
        );
    }

    /**
     * Récupère une valeur réelle de UserProfile.
     */
    private static function getProfileValue(
        User $user,
        callable $getter,
    ): string {
        $profile = $user->getUserProfile();

        if (!$profile instanceof UserProfile) {
            return 'Profil absent';
        }

        $value = $getter($profile);

        if (null === $value) {
            return 'Non renseigné';
        }

        if (is_string($value) && '' === trim($value)) {
            return 'Non renseigné';
        }

        return (string) $value;
    }

    /**
     * Récupère une valeur booléenne réelle de UserProfile.
     */
    private static function getProfileBooleanValue(
        User $user,
        callable $getter,
    ): string {
        $profile = $user->getUserProfile();

        if (!$profile instanceof UserProfile) {
            return 'Profil absent';
        }

        return true === $getter($profile)
            ? 'Oui'
            : 'Non';
    }

    /**
     * Récupère une valeur réelle de UserPreferences.
     */
    private static function getPreferencesValue(
        User $user,
        callable $getter,
    ): string {
        $preferences = $user->getPreferences();

        if (!$preferences instanceof UserPreferences) {
            return 'Préférences absentes';
        }

        $value = $getter($preferences);

        if (null === $value) {
            return 'Non renseigné';
        }

        if (is_string($value) && '' === trim($value)) {
            return 'Non renseigné';
        }

        return (string) $value;
    }

    /**
     * Récupère une valeur booléenne réelle de UserPreferences.
     */
    private static function getPreferencesBooleanValue(
        User $user,
        callable $getter,
    ): string {
        $preferences = $user->getPreferences();

        if (!$preferences instanceof UserPreferences) {
            return 'Préférences absentes';
        }

        return true === $getter($preferences)
            ? 'Activé'
            : 'Désactivé';
    }
}