<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Enum\VerificationStatus;
use App\Entity\Enum\UserType;
use App\Entity\Users\CommercialPartnerProfile;
use App\Entity\Users\User;
use App\Entity\Users\UserProfile;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class CommercialPartnerProfileCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $userPasswordHasher,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return CommercialPartnerProfile::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Partenaire commercial')
            ->setEntityLabelInPlural('Partenaires commerciaux')
            ->setPageTitle(
                Crud::PAGE_INDEX,
                'Partenaires commerciaux'
            )
            ->setPageTitle(
                Crud::PAGE_NEW,
                'Créer un partenaire commercial'
            )
            ->setPageTitle(
                Crud::PAGE_EDIT,
                static fn (CommercialPartnerProfile $partner): string =>
                sprintf(
                    'Modifier le partenaire : %s',
                    $partner->getCompanyName() ?? 'sans nom'
                )
            )
            ->setPageTitle(
                Crud::PAGE_DETAIL,
                static fn (CommercialPartnerProfile $partner): string =>
                sprintf(
                    'Partenaire commercial : %s',
                    $partner->getCompanyName() ?? 'sans nom'
                )
            )
            ->setDefaultSort([
                'createdAt' => 'DESC',
            ])
            ->setSearchFields([
                'id',
                'companyName',
                'contactJobTitle',
                'businessEmail',
                'businessPhone',
                'siren',
                'siret',
                'vatNumber',
                'countryCode',
                'commercialArea',
                'contractReference',
                'internalNotes',
                'user.email',
                'user.firstName',
                'user.lastName',
            ]);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->reorder(
                Crud::PAGE_INDEX,
                [
                    Action::DETAIL,
                    Action::EDIT,
                    Action::DELETE,
                ]
            );
    }

    /**
     * Le commercial est créé avec son compte utilisateur complet.
     */
    public function createEntity(string $entityFqcn): CommercialPartnerProfile
    {
        $user = new User();
        $user->setType(UserType::COMMERCIAL_PARTNER);
        $user->setUserProfile(new UserProfile());
        $user->getOrCreatePreferences();

        return (new CommercialPartnerProfile())->setUser($user);
    }

    public function persistEntity(
        EntityManagerInterface $entityManager,
        mixed $entityInstance,
    ): void {
        $this->prepareRelations($entityInstance);

        if ($entityInstance instanceof CommercialPartnerProfile) {
            $user = $entityInstance->getUser();

            if ($user instanceof User) {
                $password = $user->getPassword();

                if (null === $password || '' === $password) {
                    throw new \LogicException(
                        'Le mot de passe du compte commercial est obligatoire.',
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

        parent::updateEntity($entityManager, $entityInstance);
    }

    private function prepareRelations(mixed $entityInstance): void
    {
        if (!$entityInstance instanceof CommercialPartnerProfile) {
            return;
        }

        $user = $entityInstance->getUser();

        if (!$user instanceof User) {
            throw new \LogicException(
                'Un compte utilisateur est obligatoire pour le commercial.',
            );
        }

        $user->setType(UserType::COMMERCIAL_PARTNER);

        if (null === $user->getUserProfile()) {
            $user->setUserProfile(new UserProfile());
        }

        $user->getOrCreatePreferences();
    }

    public function configureFields(string $pageName): iterable
    {
        /*
         * ============================================================
         * IDENTITÉ DU PARTENAIRE
         * ============================================================
         */

        if (\in_array($pageName, [Crud::PAGE_NEW, Crud::PAGE_EDIT], true)) {
            yield FormField::addTab(
                'Identité',
                icon: 'fa fa-building',
                propertySuffix: 'identity',
            );
        }

        yield FormField::addFieldset('Compte associé')
            ->setIcon('fa fa-user');

        yield IdField::new('id', 'ID')
            ->hideOnForm();

        yield AssociationField::new('user', 'Compte commercial')
            ->setRequired(true)
            ->setColumns('col-12')
            ->renderAsEmbeddedForm(
                UserCrudController::class,
                'commercial_account',
                'commercial_account',
            );

        if (Crud::PAGE_NEW === $pageName) {
            yield TextField::new('commercialUserPassword', 'Mot de passe')
                ->setColumns('col-12 col-lg-6')
                ->setFormType(PasswordType::class)
                ->setFormTypeOption('property_path', 'user.password')
                ->setHelp('Le mot de passe est haché avant l’enregistrement.')
                ->setRequired(true);
        }

        yield FormField::addFieldset('Entreprise')
            ->setIcon('fa fa-briefcase');

        yield TextField::new('siren', 'SIREN')
            ->setColumns('col-12 col-lg-4')
            ->setFormTypeOption('attr', [
                'data-company-lookup' => 'true',
                'data-company-legal-name-target' => 'companyName',
                'data-company-address-target' => 'CommercialPartnerProfile[user][profileAddressLine1]',
                'data-company-address-complement-target' => 'CommercialPartnerProfile[user][profileAddressLine2]',
                'data-company-postal-code-target' => 'CommercialPartnerProfile[user][profilePostalCode]',
                'data-company-city-target' => 'CommercialPartnerProfile[user][profileCity]',
                'data-company-profile-country-code-target' => 'CommercialPartnerProfile[user][profileCountryCode]',
                'data-company-partner-country-code-target' => 'CommercialPartnerProfile[countryCode]',
                'data-company-latitude-target' => 'CommercialPartnerProfile[user][profileLatitude]',
                'data-company-longitude-target' => 'CommercialPartnerProfile[user][profileLongitude]',
                'data-company-formatted-address-target' => 'CommercialPartnerProfile[user][profileFormattedAddress]',
                'data-company-description-target' => 'CommercialPartnerProfile[description]',
                'data-company-commercial-area-target' => 'CommercialPartnerProfile[commercialArea]',
                'data-company-contact-job-title-target' => 'CommercialPartnerProfile[contactJobTitle]',
                'data-company-representative-first-name-target' => 'CommercialPartnerProfile[user][firstName]',
                'data-company-representative-last-name-target' => 'CommercialPartnerProfile[user][lastName]',
                'data-company-is-active-target' => 'CommercialPartnerProfile[isActive]',
                'inputmode' => 'numeric',
                'maxlength' => 9,
                'placeholder' => '123456789',
            ])
            ->setHelp('Saisissez les 9 chiffres puis lancez la recherche officielle pour préremplir les informations disponibles.')
            ->setRequired(false);

        yield TextField::new('companyName', 'Nom de l’entreprise')
            ->setRequired(true)
            ->setColumns('col-12 col-lg-8');

        yield TextField::new(
            'contactJobTitle',
            'Fonction du contact'
        )
            ->setColumns('col-12 col-lg-4');

        yield EmailField::new(
            'businessEmail',
            'Adresse e-mail professionnelle'
        )
            ->setColumns('col-12 col-lg-8');

        yield TelephoneField::new(
            'businessPhone',
            'Téléphone professionnel'
        )
            ->setColumns('col-12 col-lg-4');

        /*
         * ============================================================
         * INFORMATIONS LÉGALES
         * ============================================================
         */

        if (\in_array($pageName, [Crud::PAGE_NEW, Crud::PAGE_EDIT], true)) {
            yield FormField::addTab(
                'Informations légales',
                icon: 'fa fa-scale-balanced',
                propertySuffix: 'legal',
            );
        }

        yield FormField::addFieldset('Identification légale')
            ->setIcon('fa fa-file-signature');

        yield TextField::new('siret', 'Numéro SIRET')
            ->setFormTypeOption('attr', [
                'data-company-lookup' => 'true',
                'data-company-lookup-length' => 14,
                'data-company-lookup-label' => 'SIRET',
                'data-company-legal-name-target' => 'companyName',
                'data-company-address-target' => 'CommercialPartnerProfile[user][profileAddressLine1]',
                'data-company-address-complement-target' => 'CommercialPartnerProfile[user][profileAddressLine2]',
                'data-company-postal-code-target' => 'CommercialPartnerProfile[user][profilePostalCode]',
                'data-company-city-target' => 'CommercialPartnerProfile[user][profileCity]',
                'data-company-profile-country-code-target' => 'CommercialPartnerProfile[user][profileCountryCode]',
                'data-company-partner-country-code-target' => 'CommercialPartnerProfile[countryCode]',
                'data-company-latitude-target' => 'CommercialPartnerProfile[user][profileLatitude]',
                'data-company-longitude-target' => 'CommercialPartnerProfile[user][profileLongitude]',
                'data-company-formatted-address-target' => 'CommercialPartnerProfile[user][profileFormattedAddress]',
                'data-company-description-target' => 'CommercialPartnerProfile[description]',
                'data-company-commercial-area-target' => 'CommercialPartnerProfile[commercialArea]',
                'data-company-contact-job-title-target' => 'CommercialPartnerProfile[contactJobTitle]',
                'data-company-representative-first-name-target' => 'CommercialPartnerProfile[user][firstName]',
                'data-company-representative-last-name-target' => 'CommercialPartnerProfile[user][lastName]',
                'data-company-is-active-target' => 'CommercialPartnerProfile[isActive]',
                'inputmode' => 'numeric',
                'maxlength' => 14,
                'placeholder' => '12345678901234',
            ])
            ->setHelp('Saisissez les 14 chiffres puis lancez la recherche officielle pour préremplir les informations disponibles.')
            ->setColumns('col-12 col-md-6');

        yield TextField::new(
            'vatNumber',
            'Numéro de TVA intracommunautaire'
        )
            ->setColumns('col-12 col-md-6');

        yield CountryField::new('countryCode', 'Pays')
            ->setRequired(true)
            ->setColumns('col-12 col-md-6');

        /*
         * ============================================================
         * ACTIVITÉ COMMERCIALE
         * ============================================================
         */

        if (\in_array($pageName, [Crud::PAGE_NEW, Crud::PAGE_EDIT], true)) {
            yield FormField::addTab(
                'Activité commerciale',
                icon: 'fa fa-chart-line',
                propertySuffix: 'commercial',
            );
        }

        yield FormField::addFieldset('Présentation')
            ->setIcon('fa fa-align-left');

        yield TextareaField::new('description', 'Description')
            ->setHelp('Présentation du partenaire commercial, limitée à 5 000 caractères.')
            ->setNumOfRows(8)
            ->setColumns('col-12')
            ->hideOnIndex();

        yield TextField::new(
            'commercialArea',
            'Zone commerciale'
        )
            ->setHelp('Exemple : Normandie, France entière, secteur Grand Ouest.')
            ->setColumns('col-12');

        /*
         * ============================================================
         * CONTRAT ET COMMISSION
         * ============================================================
         */

        if (\in_array($pageName, [Crud::PAGE_NEW, Crud::PAGE_EDIT], true)) {
            yield FormField::addTab(
                'Contrat',
                icon: 'fa fa-file-contract',
                propertySuffix: 'contract',
            );
        }

        yield FormField::addFieldset('Informations contractuelles')
            ->setIcon('fa fa-file-contract');

        yield TextField::new(
            'contractReference',
            'Référence du contrat'
        )
            ->setColumns('col-12 col-lg-4');

        yield DateTimeField::new(
            'contractStartsAt',
            'Début du contrat'
        )
            ->setFormat('dd/MM/yyyy HH:mm')
            ->setColumns('col-12 col-md-6 col-lg-4');

        yield DateTimeField::new(
            'contractEndsAt',
            'Fin du contrat'
        )
            ->setFormat('dd/MM/yyyy HH:mm')
            ->setColumns('col-12 col-md-6 col-lg-4');

        yield NumberField::new(
            'commissionRate',
            'Taux de commission'
        )
            ->setNumDecimals(2)
            ->setHelp('Valeur comprise entre 0 et 100.')
            ->setColumns('col-12 col-md-6')
            ->setFormTypeOption('html5', true)
            ->setFormTypeOption('attr', [
                'min' => 0,
                'max' => 100,
                'step' => '0.01',
                'inputmode' => 'decimal',
            ])
            ->formatValue(
                static fn (
                    mixed $value,
                    CommercialPartnerProfile $partner
                ): string => number_format(
                        (float) $partner->getCommissionRate(),
                        2,
                        ',',
                        ' '
                    ).' %'
            );

        yield TextField::new(
            'contractValidity',
            'Validité actuelle du contrat'
        )
            ->formatValue(
                static fn (
                    mixed $value,
                    CommercialPartnerProfile $partner
                ): string => $partner->isContractCurrentlyValid()
                    ? 'Contrat valide'
                    : 'Contrat non valide'
            )
            ->renderAsHtml(false)
            ->onlyOnDetail();

        /*
         * ============================================================
         * VALIDATION ET ACTIVATION
         * ============================================================
         */

        if (\in_array($pageName, [Crud::PAGE_NEW, Crud::PAGE_EDIT], true)) {
            yield FormField::addTab(
                'Validation',
                icon: 'fa fa-shield-halved',
                propertySuffix: 'validation',
            );
        }

        yield FormField::addFieldset('État du partenaire')
            ->setIcon('fa fa-circle-check');

        yield ChoiceField::new(
            'verificationStatus',
            'Statut de vérification'
        )
            ->setChoices(self::getVerificationStatusChoices())
            ->renderAsBadges(self::getVerificationStatusBadges())
            ->setColumns('col-12 col-md-6');

        yield BooleanField::new('isActive', 'Partenaire actif')
            ->setColumns('col-12 col-md-6');

        yield DateTimeField::new(
            'validatedAt',
            'Date de validation'
        )
            ->setFormat('dd/MM/yyyy HH:mm')
            ->hideOnForm()
            ->hideOnIndex();

        yield FormField::addFieldset('Notes administratives')
            ->setIcon('fa fa-note-sticky');

        yield TextareaField::new(
            'internalNotes',
            'Notes internes'
        )
            ->setHelp('Ces notes sont réservées à l’administration.')
            ->setNumOfRows(8)
            ->setColumns('col-12')
            ->hideOnIndex();

        /*
         * ============================================================
         * HISTORIQUE
         * ============================================================
         */

        yield FormField::addFieldset('Historique')
            ->setIcon('fa fa-clock')
            ->hideOnForm();

        yield DateTimeField::new(
            'createdAt',
            'Date de création'
        )
            ->setFormat('dd/MM/yyyy HH:mm')
            ->hideOnForm();

        yield DateTimeField::new(
            'updatedAt',
            'Dernière modification'
        )
            ->setFormat('dd/MM/yyyy HH:mm')
            ->hideOnForm()
            ->hideOnIndex();
    }

    /**
     * @return array<string, VerificationStatus>
     */
    private static function getVerificationStatusChoices(): array
    {
        $choices = [];

        foreach (VerificationStatus::cases() as $status) {
            $choices[self::formatEnumLabel($status->name)] = $status;
        }

        return $choices;
    }

    /**
     * @return array<string, string>
     */
    private static function getVerificationStatusBadges(): array
    {
        $badges = [];

        foreach (VerificationStatus::cases() as $status) {
            $badges[$status->value] = match ($status->name) {
                'VERIFIED', 'APPROVED', 'VALIDATED' => 'success',
                'REJECTED', 'REFUSED', 'FAILED' => 'danger',
                'PENDING', 'IN_REVIEW', 'UNDER_REVIEW' => 'warning',
                'NOT_SUBMITTED' => 'secondary',
                default => 'info',
            };
        }

        return $badges;
    }

    private static function formatEnumLabel(string $name): string
    {
        return ucfirst(
            strtolower(
                str_replace('_', ' ', $name)
            )
        );
    }
}
