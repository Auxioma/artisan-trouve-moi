<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Billing\Invoice;
use App\Entity\Billing\PaymentMethod;
use App\Entity\Billing\Subscription;
use App\Entity\Billing\SubscriptionPlan;
use App\Entity\Catalog\ArtisanPhoto;
use App\Entity\Catalog\ArtisanService;
use App\Entity\Catalog\Category;
use App\Entity\Enum\AppointmentStatus;
use App\Entity\Enum\AppointmentType;
use App\Entity\Enum\InvoiceStatus;
use App\Entity\Enum\NotificationType;
use App\Entity\Enum\PriceUnit;
use App\Entity\Enum\ProjectStatus;
use App\Entity\Enum\QuoteStatus;
use App\Entity\Enum\RequestStatus;
use App\Entity\Enum\SubscriptionPlanCode;
use App\Entity\Enum\SubscriptionStatus;
use App\Entity\Enum\VerificationDocumentType;
use App\Entity\Enum\VerificationStatus;
use App\Entity\Favorites\Favorite;
use App\Entity\Geo\InterventionCity;
use App\Entity\Geo\InterventionZone;
use App\Entity\Messaging\Conversation;
use App\Entity\Messaging\Message;
use App\Entity\Messaging\MessageAttachment;
use App\Entity\Notifications\Notification;
use App\Entity\Projects\Project;
use App\Entity\Projects\ProjectStep;
use App\Entity\Quotes\Quote;
use App\Entity\Quotes\QuoteLine;
use App\Entity\Requests\RequestPhoto;
use App\Entity\Requests\ServiceRequest;
use App\Entity\Reviews\Review;
use App\Entity\Scheduling\Appointment;
use App\Entity\Users\ArtisanProfile;
use App\Entity\Users\User;
use App\Entity\Users\UserProfile;
use App\Entity\Verification\VerificationDocument;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Complements the identity fixtures with every marketplace entity.
 *
 * This class creates exactly 127 records; with the 73 records from the
 * dependent fixtures, the complete dataset contains 200 records.
 */
final class MarketplaceFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable('2026-07-18 10:00:00');
        $customers = $this->customers();
        $artisans = $this->artisans();

        $plans = $this->loadPlans($manager);
        $paymentMethods = $this->loadPaymentMethods($manager, $artisans);
        $subscriptions = $this->loadSubscriptions($manager, $artisans, $plans, $paymentMethods, $now);
        $this->loadInvoices($manager, $subscriptions, $now);

        $categories = $this->loadCategories($manager);
        $this->loadServices($manager, $artisans, $categories);
        $this->loadArtisanPhotos($manager, $artisans, $now);
        $this->loadZones($manager, $artisans);
        $this->loadFavorites($manager, $customers, $artisans);
        $this->loadUserProfiles($manager, $customers, $now);

        $requests = $this->loadRequests($manager, $customers, $categories, $now);
        $this->loadRequestPhotos($manager, $requests);
        $quotes = $this->loadQuotes($manager, $requests, $artisans, $now);
        $this->loadQuoteLines($manager, $quotes);
        $projects = $this->loadProjects($manager, $quotes, $customers, $artisans, $now);
        $this->loadProjectSteps($manager, $projects, $now);
        $this->loadReviews($manager, $projects, $customers, $artisans, $now);
        $this->loadAppointments($manager, $projects, $requests, $customers, $artisans, $now);
        $messages = $this->loadConversationsAndMessages($manager, $requests, $customers, $artisans, $now);
        $this->loadMessageAttachment($manager, $messages);
        $this->loadNotifications($manager, $customers, $now);
        $this->loadVerificationDocuments($manager, $artisans, $now);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            ArtisanProfileFixtures::class,
            CommercialPartnerProfileFixtures::class,
            ResetPasswordRequestFixtures::class,
        ];
    }

    /** @return list<User> */
    private function customers(): array
    {
        $customers = [];
        for ($index = 1; $index <= FixtureReferences::CUSTOMER_USER_COUNT; ++$index) {
            $customers[] = $this->getReference(FixtureReferences::customerUser($index), User::class);
        }

        return $customers;
    }

    /** @return list<ArtisanProfile> */
    private function artisans(): array
    {
        $artisans = [];
        for ($index = 1; $index <= FixtureReferences::ARTISAN_PROFILE_COUNT; ++$index) {
            $artisans[] = $this->getReference(FixtureReferences::artisanProfile($index), ArtisanProfile::class);
        }

        return $artisans;
    }

    /** @return list<SubscriptionPlan> */
    private function loadPlans(ObjectManager $manager): array
    {
        $plans = [];
        foreach ([
            [SubscriptionPlanCode::ESSENTIEL, 'Essentiel', '29.00', 20],
            [SubscriptionPlanCode::PREMIUM, 'Premium', '59.00', 50],
            [SubscriptionPlanCode::EXCELLENCE, 'Excellence', '99.00', null],
        ] as $position => [$code, $name, $price, $maxQuotes]) {
            $plan = (new SubscriptionPlan())
                ->setCode($code)
                ->setName($name)
                ->setMonthlyPriceHt($price)
                ->setYearlyPriceHt((string) ((float) $price * 10))
                ->setVatRate('20.00')
                ->setTrialDays(14)
                ->setMaxQuotesPerMonth($maxQuotes)
                ->setMaxCategories(8)
                ->setMaxPhotos(20)
                ->setHasUrgentAccess($position > 0)
                ->setHasPriorityRanking($position > 1)
                ->setFeatures(['devis', 'messagerie', 'statistiques'])
                ->setProviderPriceId(sprintf('price_fixture_%d', $position + 1))
                ->setPosition($position + 1)
                ->setIsPopular($position === 1)
                ->setIsActive(true);
            $manager->persist($plan);
            $plans[] = $plan;
        }

        return $plans;
    }

    /** @param list<ArtisanProfile> $artisans @return list<PaymentMethod> */
    private function loadPaymentMethods(ObjectManager $manager, array $artisans): array
    {
        $methods = [];
        for ($index = 0; $index < 5; ++$index) {
            $method = (new PaymentMethod())
                ->setUser($artisans[$index]->getUser())
                ->setBrand($index % 2 === 0 ? 'visa' : 'mastercard')
                ->setLast4(sprintf('%04d', 1100 + $index))
                ->setExpiresMonth(6 + $index)
                ->setExpiresYear(2028)
                ->setHolderName($artisans[$index]->getLegalName())
                ->setIsDefault(true)
                ->setProviderPaymentMethodId(sprintf('pm_fixture_%02d', $index + 1));
            $manager->persist($method);
            $methods[] = $method;
        }

        return $methods;
    }

    /** @param list<ArtisanProfile> $artisans @param list<SubscriptionPlan> $plans @param list<PaymentMethod> $methods @return list<Subscription> */
    private function loadSubscriptions(ObjectManager $manager, array $artisans, array $plans, array $methods, \DateTimeImmutable $now): array
    {
        $subscriptions = [];
        for ($index = 0; $index < 5; ++$index) {
            $subscription = (new Subscription())
                ->setArtisanProfile($artisans[$index])
                ->setPlan($plans[$index % count($plans)])
                ->setPaymentMethod($methods[$index])
                ->setStatus(SubscriptionStatus::ACTIVE)
                ->setStartsAt($now->modify(sprintf('-%d months', $index + 1)))
                ->setTrialEndsAt($now->modify('-14 days'))
                ->setCurrentPeriodStartsAt($now->modify('first day of this month'))
                ->setCurrentPeriodEndsAt($now->modify('last day of this month'))
                ->setQuotesUsedInPeriod($index + 2)
                ->setCancelAtPeriodEnd(false)
                ->setProviderSubscriptionId(sprintf('sub_fixture_%02d', $index + 1));
            $manager->persist($subscription);
            $subscriptions[] = $subscription;
        }

        return $subscriptions;
    }

    /** @param list<Subscription> $subscriptions */
    private function loadInvoices(ObjectManager $manager, array $subscriptions, \DateTimeImmutable $now): void
    {
        foreach ($subscriptions as $index => $subscription) {
            $invoice = (new Invoice())
                ->setSubscription($subscription)
                ->setReference(sprintf('FAC-2026-%04d', $index + 1))
                ->setStatus(InvoiceStatus::PAID)
                ->setAmountHt('59.00')->setAmountVat('11.80')->setAmountTtc('70.80')
                ->setPeriodStartsAt($now->modify('first day of this month'))
                ->setPeriodEndsAt($now->modify('last day of this month'))
                ->setBillingName($subscription->getArtisanProfile()->getLegalName())
                ->setBillingAddress('10 rue des Artisans, 75001 Paris')
                ->setIssuedAt($now)->setDueAt($now->modify('+30 days'))->setPaidAt($now)
                ->setProviderInvoiceId(sprintf('in_fixture_%02d', $index + 1));
            $manager->persist($invoice);
        }
    }

    /** @return list<Category> */
    private function loadCategories(ObjectManager $manager): array
    {
        $categories = [];
        foreach (['Plomberie', 'Electricite', 'Menuiserie', 'Maconnerie', 'Peinture', 'Carrelage', 'Couverture', 'Renovation'] as $index => $name) {
            $category = (new Category())
                ->setName($name)->setSlug(strtolower($name))
                ->setIcon('wrench')->setDescription(sprintf('Prestations de %s.', strtolower($name)))
                ->setPosition($index + 1)->setIsActive(true);
            $manager->persist($category);
            $categories[] = $category;
        }

        return $categories;
    }

    /** @param list<ArtisanProfile> $artisans @param list<Category> $categories */
    private function loadServices(ObjectManager $manager, array $artisans, array $categories): void
    {
        for ($index = 0; $index < 10; ++$index) {
            $service = (new ArtisanService())
                ->setArtisanProfile($artisans[$index])
                ->setCategory($categories[$index % count($categories)])
                ->setTitle(sprintf('Prestation %d', $index + 1))
                ->setDescription('Intervention professionnelle avec devis detaille.')
                ->setPriceFrom((string) (80 + $index * 15))
                ->setPriceUnit(PriceUnit::FLAT)
                ->setEstimatedDurationHours(2 + ($index % 6))
                ->setPosition($index + 1)->setIsActive(true);
            $manager->persist($service);
        }
    }

    /** @param list<ArtisanProfile> $artisans */
    private function loadArtisanPhotos(ObjectManager $manager, array $artisans, \DateTimeImmutable $now): void
    {
        for ($index = 0; $index < 5; ++$index) {
            $photo = (new ArtisanPhoto())
                ->setArtisanProfile($artisans[$index])
                ->setTitle(sprintf('Realisation %d', $index + 1))
                ->setCity('Paris')->setCompletedAt($now->modify(sprintf('-%d months', $index + 1)))
                ->setPosition($index + 1)->setIsCover($index === 0)
                ->setImageName(sprintf('artisan-%02d.jpg', $index + 1))
                ->setImageSize(120000)->setImageMimeType('image/jpeg');
            $manager->persist($photo);
        }
    }

    /** @param list<ArtisanProfile> $artisans */
    private function loadZones(ObjectManager $manager, array $artisans): void
    {
        $zones = [];
        for ($index = 0; $index < 5; ++$index) {
            $zone = (new InterventionZone())
                ->setArtisanProfile($artisans[$index])->setBaseCity('Paris')
                ->setBasePostalCode(sprintf('750%02d', $index + 1))->setLatitude('48.8566000')
                ->setLongitude('2.3522000')->setRadiusKm(25 + $index * 5)
                ->setExtraTravelFeeHt('15.00')->setTravelNote('Deplacement inclus dans la zone.')
                ->setIsActive(true);
            $manager->persist($zone);
            $zones[] = $zone;
        }
        for ($index = 0; $index < 8; ++$index) {
            $city = (new InterventionCity())->setZone($zones[$index % count($zones)])
                ->setCityName(['Paris', 'Boulogne-Billancourt', 'Montreuil', 'Versailles'][$index % 4])
                ->setPostalCode(sprintf('92%03d', 100 + $index))->setPosition($index + 1);
            $manager->persist($city);
        }
    }

    /** @param list<User> $customers @param list<ArtisanProfile> $artisans */
    private function loadFavorites(ObjectManager $manager, array $customers, array $artisans): void
    {
        for ($index = 0; $index < 3; ++$index) {
            $manager->persist((new Favorite())->setUser($customers[$index])->setArtisanProfile($artisans[$index]));
        }
    }

    /** @param list<User> $customers */
    private function loadUserProfiles(ObjectManager $manager, array $customers, \DateTimeImmutable $now): void
    {
        for ($index = 0; $index < 5; ++$index) {
            $profile = (new UserProfile())->setUser($customers[$index])->setType('home')
                ->setLabel('Domicile')->setAddressLine1(sprintf('%d rue de Paris', $index + 1))
                ->setPostalCode(sprintf('750%02d', $index + 1))->setCity('Paris')->setCountryCode('FR')
                ->setFormattedAddress(sprintf('%d rue de Paris, 750%02d Paris', $index + 1, $index + 1))
                ->setLatitude('48.8566000')->setLongitude('2.3522000')->setIsDefault(true)
                ->setIsBillingAddress(true)->setIsPublic(false)->setIsGeocoded(true)
                ->setGeocodedAt($now)->setCreatedAt($now)->setUpdatedAt($now);
            $manager->persist($profile);
        }
    }

    /** @param list<User> $customers @param list<Category> $categories @return list<ServiceRequest> */
    private function loadRequests(ObjectManager $manager, array $customers, array $categories, \DateTimeImmutable $now): array
    {
        $requests = [];
        for ($index = 0; $index < 8; ++$index) {
            $request = (new ServiceRequest())->setClient($customers[$index])->setCategory($categories[$index])
                ->setTitle(sprintf('Demande de travaux %d', $index + 1))->setDescription('Besoin de travaux avec visite technique.')
                ->setStatus(RequestStatus::PUBLISHED)->setIsUrgent($index % 3 === 0)
                ->setBudgetMin('500.00')->setBudgetMax('2500.00')->setDesiredStartAt($now->modify('+1 month'))
                ->setPropertyType('Appartement')->setSurfaceM2('65.00')->setAddressLine1('12 rue des Clients')
                ->setPostalCode(sprintf('750%02d', $index + 1))->setCity('Paris')->setPublishedAt($now)
                ->setExpiresAt($now->modify('+30 days'))->setMaxQuotes(5)->setQuotesCount(1)->setViewsCount(10 + $index)
                ->setSource('fixture');
            $manager->persist($request);
            $requests[] = $request;
        }

        return $requests;
    }

    /** @param list<ServiceRequest> $requests */
    private function loadRequestPhotos(ObjectManager $manager, array $requests): void
    {
        for ($index = 0; $index < 3; ++$index) {
            $manager->persist((new RequestPhoto())->setRequest($requests[$index])->setCaption('Photo du chantier')
                ->setPosition($index + 1)->setImageName(sprintf('request-%02d.jpg', $index + 1))
                ->setImageSize(90000)->setImageMimeType('image/jpeg'));
        }
    }

    /** @param list<ServiceRequest> $requests @param list<ArtisanProfile> $artisans @return list<Quote> */
    private function loadQuotes(ObjectManager $manager, array $requests, array $artisans, \DateTimeImmutable $now): array
    {
        $quotes = [];
        for ($index = 0; $index < 8; ++$index) {
            $quote = (new Quote())->setServiceRequest($requests[$index])->setArtisanProfile($artisans[$index])
                ->setReference(sprintf('DEV-2026-%04d', $index + 1))->setStatus(QuoteStatus::SENT)
                ->setMessage('Proposition detaillee pour votre projet.')->setVatRate('20.00')
                ->setTotalHt('1000.00')->setTotalVat('200.00')->setTotalTtc('1200.00')
                ->setWorkDurationDays(4)->setCanStartAt($now->modify('+14 days'))->setWarrantyMonths(24)
                ->setValidUntil($now->modify('+30 days'))->setSentAt($now);
            $manager->persist($quote);
            $quotes[] = $quote;
        }

        return $quotes;
    }

    /** @param list<Quote> $quotes */
    private function loadQuoteLines(ObjectManager $manager, array $quotes): void
    {
        for ($index = 0; $index < 12; ++$index) {
            $manager->persist((new QuoteLine())->setQuote($quotes[$index % count($quotes)])
                ->setPosition($index + 1)->setLabel(sprintf('Poste de travaux %d', $index + 1))
                ->setDescription('Fourniture et pose.')->setQuantity('1.00')->setUnit('forfait')
                ->setUnitPriceHt('500.00')->setVatRate('20.00')->setTotalHt('500.00'));
        }
    }

    /** @param list<Quote> $quotes @param list<User> $customers @param list<ArtisanProfile> $artisans @return list<Project> */
    private function loadProjects(ObjectManager $manager, array $quotes, array $customers, array $artisans, \DateTimeImmutable $now): array
    {
        $projects = [];
        for ($index = 0; $index < 4; ++$index) {
            $project = (new Project())->setQuote($quotes[$index])->setClient($customers[$index])
                ->setArtisanProfile($artisans[$index])->setReference(sprintf('PRJ-2026-%04d', $index + 1))
                ->setTitle(sprintf('Chantier %d', $index + 1))->setStatus(ProjectStatus::IN_PROGRESS)
                ->setProgressPercent(25 + $index * 15)->setAmountTtc('1200.00')->setAddressLine1('12 rue des Clients')
                ->setPostalCode('75001')->setCity('Paris')->setStartsAt($now->modify('-5 days'))
                ->setEndsAt($now->modify('+15 days'))->setActualStartedAt($now->modify('-5 days'));
            $manager->persist($project);
            $projects[] = $project;
        }

        return $projects;
    }

    /** @param list<Project> $projects */
    private function loadProjectSteps(ObjectManager $manager, array $projects, \DateTimeImmutable $now): void
    {
        for ($index = 0; $index < 6; ++$index) {
            $manager->persist((new ProjectStep())->setProject($projects[$index % count($projects)])
                ->setPosition($index + 1)->setLabel(sprintf('Etape %d', $index + 1))
                ->setDescription('Suivi de chantier.')->setPlannedAt($now->modify(sprintf('+%d days', $index + 1))));
        }
    }

    /** @param list<Project> $projects @param list<User> $customers @param list<ArtisanProfile> $artisans */
    private function loadReviews(ObjectManager $manager, array $projects, array $customers, array $artisans, \DateTimeImmutable $now): void
    {
        for ($index = 0; $index < 3; ++$index) {
            $manager->persist((new Review())->setAuthor($customers[$index])->setArtisanProfile($artisans[$index])
                ->setProject($projects[$index])->setRating(5)->setQualityRating(5)->setPunctualityRating(5)
                ->setCleanlinessRating(5)->setWouldRecommend(true)->setComment('Prestation tres satisfaisante.')
                ->setIsPublished(true)->setPublishedAt($now)->setModeratedAt($now));
        }
    }

    /** @param list<Project> $projects @param list<ServiceRequest> $requests @param list<User> $customers @param list<ArtisanProfile> $artisans */
    private function loadAppointments(ObjectManager $manager, array $projects, array $requests, array $customers, array $artisans, \DateTimeImmutable $now): void
    {
        for ($index = 0; $index < 3; ++$index) {
            $startsAt = $now->modify(sprintf('+%d days', $index + 1));
            $manager->persist((new Appointment())->setArtisanProfile($artisans[$index])->setClient($customers[$index])
                ->setProject($projects[$index])->setServiceRequest($requests[$index])
                ->setType(AppointmentType::TECHNICAL_VISIT)->setStatus(AppointmentStatus::CONFIRMED)
                ->setTitle('Visite technique')->setStartsAt($startsAt)->setEndsAt($startsAt->modify('+1 hour'))
                ->setLocation('12 rue des Clients, Paris')->setNotes('Rendez-vous confirme.'));
        }
    }

    /** @param list<ServiceRequest> $requests @param list<User> $customers @param list<ArtisanProfile> $artisans @return list<Message> */
    private function loadConversationsAndMessages(ObjectManager $manager, array $requests, array $customers, array $artisans, \DateTimeImmutable $now): array
    {
        $conversations = [];
        for ($index = 0; $index < 3; ++$index) {
            $conversation = (new Conversation())->setClient($customers[$index])->setArtisanProfile($artisans[$index])
                ->setServiceRequest($requests[$index])->setSubject('Echange sur votre demande')
                ->setLastMessageAt($now)->setClientReadAt($now)->setArtisanReadAt($now);
            $manager->persist($conversation);
            $conversations[] = $conversation;
        }
        $messages = [];
        for ($index = 0; $index < 5; ++$index) {
            $message = (new Message())->setConversation($conversations[$index % count($conversations)])
                ->setAuthor($index % 2 === 0 ? $customers[$index % 3] : $artisans[$index % 3]->getUser())
                ->setContent('Message de demonstration pour la conversation.')->setIsSystem(false)
                ->setReadAt($now);
            $manager->persist($message);
            $messages[] = $message;
        }

        return $messages;
    }

    /** @param list<Message> $messages */
    private function loadMessageAttachment(ObjectManager $manager, array $messages): void
    {
        $manager->persist((new MessageAttachment())->setMessage($messages[0])->setOriginalName('document.pdf')
            ->setDocumentName('attachment-document.pdf')->setDocumentSize(25000)->setDocumentMimeType('application/pdf'));
    }

    /** @param list<User> $customers */
    private function loadNotifications(ObjectManager $manager, array $customers, \DateTimeImmutable $now): void
    {
        foreach ([NotificationType::NEW_REQUEST, NotificationType::NEW_QUOTE, NotificationType::NEW_MESSAGE, NotificationType::SYSTEM] as $index => $type) {
            $manager->persist((new Notification())->setUser($customers[$index])->setType($type)
                ->setTitle('Notification de demonstration')->setContent('Une nouvelle action est disponible.')
                ->setLinkUrl('/')->setEmailSentAt($now));
        }
    }

    /** @param list<ArtisanProfile> $artisans */
    private function loadVerificationDocuments(ObjectManager $manager, array $artisans, \DateTimeImmutable $now): void
    {
        $types = [VerificationDocumentType::IDENTITY, VerificationDocumentType::KBIS, VerificationDocumentType::RNE_EXTRACT, VerificationDocumentType::DECENNIAL_INSURANCE, VerificationDocumentType::QUALIFICATION];
        for ($index = 0; $index < 5; ++$index) {
            $manager->persist((new VerificationDocument())->setArtisanProfile($artisans[$index])->setType($types[$index])
                ->setStatus(VerificationStatus::VERIFIED)->setSubmittedAt($now->modify('-10 days'))
                ->setReviewedAt($now->modify('-5 days'))->setReviewedBy($artisans[0]->getUser())
                ->setDocumentName(sprintf('verification-%02d.pdf', $index + 1))->setDocumentSize(64000)
                ->setDocumentMimeType('application/pdf'));
        }
    }
}
