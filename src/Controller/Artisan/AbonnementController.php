<?php

declare(strict_types=1);

namespace App\Controller\Artisan;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Billing\PaymentMethod;
use App\Entity\Billing\Invoice;
use App\Entity\Billing\Subscription;
use App\Entity\Billing\SubscriptionPlan;
use App\Entity\Enum\SubscriptionPlanCode;
use App\Entity\Enum\SubscriptionBillingPeriod;
use App\Entity\Enum\InvoiceStatus;
use App\Entity\Enum\SubscriptionStatus;
use App\Entity\Users\ArtisanProfile;
use App\Entity\Users\User;
use App\Repository\Billing\PaymentMethodRepository;
use App\Repository\Billing\InvoiceRepository;
use App\Repository\Billing\SubscriptionRepository;
use App\Repository\Billing\SubscriptionPlanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[IsGranted('ROLE_ARTISAN')]
#[Route('/espace-prestataire/abonnement', name: 'app_artisan_abonnement')]
class AbonnementController extends AbstractController
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptionRepo,
        private readonly SubscriptionPlanRepository $subscriptionPlanRepo,
        private readonly PaymentMethodRepository $paymentMethodRepo,
        private readonly InvoiceRepository $invoiceRepo,
        private readonly EntityManagerInterface $entityManager,
        private readonly HttpClientInterface $httpClient,
        #[Autowire(env: 'STRIPE_SECRET_KEY')]
        private readonly string $stripeSecretKey,
        #[Autowire(env: 'STRIPE_PUBLIC_KEY')]
        private readonly string $stripePublicKey,
    ) {
    }

    #[Route('', name: '', methods: ['GET'])]
    public function __invoke(): Response
    {
        $user = $this->getUser();
        $paymentMethod = $user instanceof User
            ? $this->paymentMethodRepo->findOneBy(['user' => $user, 'isDefault' => true])
            : null;
        $artisan = $user instanceof User ? $user->getArtisanProfile() : null;
        $subscription = null !== $artisan ? $this->subscriptionRepo->findLatestForArtisan($artisan) : null;
        if (null !== $subscription && is_string($subscription->getProviderSubscriptionId())) {
            try {
                $stripeSubscription = $this->stripeRequest('GET', 'subscriptions/'.rawurlencode($subscription->getProviderSubscriptionId()));
                $this->syncStripeInvoice($stripeSubscription['latest_invoice'] ?? null, $subscription);
                $this->entityManager->flush();
            } catch (\RuntimeException) {
                // L’affichage reste disponible même si Stripe est momentanément indisponible.
            }
        }

        return $this->render('artisan/abonnement.html.twig', [
            'stripe_public_key' => $this->stripePublicKey,
            'payment_method' => $paymentMethod,
            'artisan' => $artisan,
            'subscription_plans' => $this->activePlansByCode(),
            'current_subscription' => $subscription,
            'last_payment' => $this->lastPaymentSummary($artisan),
            'invoices' => null !== $artisan ? $this->invoiceRepo->findAllForArtisan($artisan) : [],
        ]);
    }

    #[Route('/invoice/{id}/pdf', name: '_invoice_pdf', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function invoicePdf(int $id): Response
    {
        $user = $this->getUser();
        $artisan = $user instanceof User ? $user->getArtisanProfile() : null;
        $invoice = $this->invoiceRepo->find($id);
        if (null === $artisan || null === $invoice || $invoice->getSubscription()?->getArtisanProfile() !== $artisan) {
            throw $this->createNotFoundException('Facture introuvable.');
        }

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->renderView('artisan/invoice_pdf.html.twig', ['invoice' => $invoice]), 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = $invoice->getPdfFilename() ?? sprintf('facture-%s.pdf', $invoice->getId());

        return new Response($dompdf->output(), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => (new ResponseHeaderBag())->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $filename
            ),
        ]);
    }

    #[Route('/stripe/subscription', name: '_stripe_subscription', methods: ['POST'])]
    public function createStripeSubscription(Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('artisan_subscription_payment', $request->headers->get('X-CSRF-TOKEN'))) {
            return $this->json(['error' => 'Jeton CSRF invalide.'], Response::HTTP_FORBIDDEN);
        }

        $payload = json_decode($request->getContent(), true);
        $code = is_array($payload) ? $payload['plan'] ?? null : null;
        $period = is_array($payload) ? $payload['period'] ?? null : null;
        $user = $this->getUser();
        $artisan = $user instanceof User ? $user->getArtisanProfile() : null;
        $paymentMethod = $user instanceof User ? $this->paymentMethodRepo->findOneBy(['user' => $user, 'isDefault' => true]) : null;

        if (!$user instanceof User || null === $artisan || null === $paymentMethod || !is_string($paymentMethod->getProviderPaymentMethodId()) || !is_string($code) || !in_array($period, ['monthly', 'yearly'], true)) {
            return $this->json(['error' => 'Enregistrez d’abord votre carte bancaire avant de souscrire.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $planCode = SubscriptionPlanCode::tryFrom($code);
        $plan = null !== $planCode ? $this->subscriptionPlanRepo->findOneBy(['code' => $planCode, 'isActive' => true]) : null;
        $price = null !== $plan ? ('monthly' === $period ? $plan->getMonthlyPriceHt() : $plan->getYearlyPriceHt()) : null;
        if (null === $plan || null === $price || (float) $price <= 0) {
            return $this->json(['error' => 'Ce forfait est indisponible pour cette période.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $customerId = $this->ensureStripeCustomer($user, $artisan);
            $providerPriceId = $this->stripePriceId($plan, $price, $period);
            $existingSubscription = $this->subscriptionRepo->findLatestForArtisan($artisan);
            if (null !== $existingSubscription && is_string($existingSubscription->getProviderSubscriptionId()) && str_starts_with($existingSubscription->getProviderSubscriptionId(), 'sub_')) {
                $providerSubscriptionId = $existingSubscription->getProviderSubscriptionId();
                $currentSubscription = $this->stripeRequest('GET', 'subscriptions/'.rawurlencode($providerSubscriptionId));
                $itemId = $currentSubscription['items']['data'][0]['id'] ?? null;
                if (($currentSubscription['customer'] ?? null) !== $customerId || !is_string($itemId)) {
                    throw new \RuntimeException('Abonnement Stripe existant invalide.');
                }
                if (($currentSubscription['status'] ?? null) === 'incomplete') {
                    $this->stripeRequest('DELETE', 'subscriptions/'.rawurlencode($providerSubscriptionId));
                    $subscription = $this->stripeRequest('POST', 'subscriptions', [
                        'customer' => $customerId,
                        'default_payment_method' => $paymentMethod->getProviderPaymentMethodId(),
                        'items[0][price]' => $providerPriceId,
                        'collection_method' => 'charge_automatically',
                        'payment_behavior' => 'allow_incomplete',
                        'payment_settings[save_default_payment_method]' => 'on_subscription',
                        'metadata[plan_code]' => $plan->getCode()->value,
                        'metadata[billing_period]' => $period,
                        'expand[0]' => 'latest_invoice.payment_intent',
                    ]);
                } else {
                    $subscription = $this->stripeRequest('POST', 'subscriptions/'.rawurlencode($providerSubscriptionId), [
                        'default_payment_method' => $paymentMethod->getProviderPaymentMethodId(),
                        'items[0][id]' => $itemId,
                        'items[0][price]' => $providerPriceId,
                        'collection_method' => 'charge_automatically',
                        'payment_behavior' => 'allow_incomplete',
                        'proration_behavior' => 'always_invoice',
                        'metadata[plan_code]' => $plan->getCode()->value,
                        'metadata[billing_period]' => $period,
                        'expand[0]' => 'latest_invoice.payment_intent',
                    ]);
                }
            } else {
                $subscription = $this->stripeRequest('POST', 'subscriptions', [
                    'customer' => $customerId,
                    'default_payment_method' => $paymentMethod->getProviderPaymentMethodId(),
                    'items[0][price]' => $providerPriceId,
                    'collection_method' => 'charge_automatically',
                    'payment_behavior' => 'allow_incomplete',
                    'payment_settings[save_default_payment_method]' => 'on_subscription',
                    'metadata[plan_code]' => $plan->getCode()->value,
                    'metadata[billing_period]' => $period,
                    'expand[0]' => 'latest_invoice.payment_intent',
                ]);
            }

            $subscriptionId = $subscription['id'] ?? null;
            if (!is_string($subscriptionId)) {
                throw new \RuntimeException('Abonnement Stripe introuvable.');
            }

            $paymentIntent = $subscription['latest_invoice']['payment_intent'] ?? null;
            $clientSecret = is_array($paymentIntent) ? $paymentIntent['client_secret'] ?? null : null;

            return $this->json(['subscription_id' => $subscriptionId, 'client_secret' => $clientSecret]);
        } catch (\RuntimeException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_GATEWAY);
        }
    }

    #[Route('/stripe/subscription/{id}/confirm', name: '_stripe_subscription_confirm', methods: ['POST'])]
    public function confirmStripeSubscription(string $id, Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('artisan_subscription_payment', $request->headers->get('X-CSRF-TOKEN')) || !str_starts_with($id, 'sub_')) {
            return $this->json(['error' => 'Confirmation invalide.'], Response::HTTP_FORBIDDEN);
        }

        $user = $this->getUser();
        $artisan = $user instanceof User ? $user->getArtisanProfile() : null;
        if (!$user instanceof User || null === $artisan) {
            return $this->json(['error' => 'Utilisateur non connecté.'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $stripeSubscription = $this->stripeRequest('GET', 'subscriptions/'.rawurlencode($id));
            $planCode = $stripeSubscription['metadata']['plan_code'] ?? null;
            $billingPeriod = $stripeSubscription['metadata']['billing_period'] ?? null;
            $plan = is_string($planCode) && null !== ($code = SubscriptionPlanCode::tryFrom($planCode))
                ? $this->subscriptionPlanRepo->findOneBy(['code' => $code, 'isActive' => true])
                : null;
            $period = is_string($billingPeriod) ? SubscriptionBillingPeriod::tryFrom($billingPeriod) : null;
            if (($stripeSubscription['customer'] ?? null) !== $artisan->getStripeCustomerId() || null === $plan || null === $period) {
                throw new \RuntimeException('Abonnement Stripe invalide.');
            }
            $subscriptionItem = $stripeSubscription['items']['data'][0] ?? [];

            $subscription = $this->subscriptionRepo->findOneBy(['providerSubscriptionId' => $id]) ?? new Subscription();
            $subscription
                ->setArtisanProfile($artisan)
                ->setPlan($plan)
                ->setPaymentMethod($this->paymentMethodRepo->findOneBy(['user' => $user, 'isDefault' => true]))
                ->setProviderSubscriptionId($id)
                ->setStatus($this->subscriptionStatus((string) ($stripeSubscription['status'] ?? 'active')))
                ->setBillingPeriod($period)
                ->setStartsAt($this->stripeDate($stripeSubscription['start_date'] ?? null) ?? new \DateTimeImmutable())
                ->setTrialEndsAt($this->stripeDate($stripeSubscription['trial_end'] ?? null))
                ->setCurrentPeriodStartsAt($this->stripeDate($stripeSubscription['current_period_start'] ?? $subscriptionItem['current_period_start'] ?? null))
                ->setCurrentPeriodEndsAt($this->stripeDate($stripeSubscription['current_period_end'] ?? $subscriptionItem['current_period_end'] ?? null))
                ->setCancelAtPeriodEnd((bool) ($stripeSubscription['cancel_at_period_end'] ?? false))
                ->setCancelledAt($this->stripeDate($stripeSubscription['canceled_at'] ?? null))
                ->setEndedAt($this->stripeDate($stripeSubscription['ended_at'] ?? null));
            $subscription->resetPeriodUsage();
            $this->syncStripeInvoice($stripeSubscription['latest_invoice'] ?? null, $subscription);
            $this->entityManager->persist($subscription);
            $this->entityManager->flush();

            return $this->json(['message' => 'Votre abonnement est actif.']);
        } catch (\RuntimeException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_GATEWAY);
        }
    }

    #[Route('/stripe/setup-intent', name: '_stripe_setup_intent', methods: ['POST'])]
    public function createStripeSetupIntent(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'Utilisateur non connecté.'], Response::HTTP_UNAUTHORIZED);
        }

        $artisan = $user->getArtisanProfile();

        if (null === $artisan) {
            return $this->json(['error' => 'Profil artisan introuvable.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $customerId = $this->ensureStripeCustomer($user, $artisan);

            $setupIntent = $this->stripeRequest('POST', 'setup_intents', [
                'customer' => $customerId,
                'usage' => 'off_session',
                'payment_method_types[0]' => 'card',
            ]);

            return $this->json(['client_secret' => $setupIntent['client_secret'] ?? null]);
        } catch (\RuntimeException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_GATEWAY);
        }
    }

    #[Route('/stripe/payment-method', name: '_stripe_payment_method', methods: ['POST'])]
    public function saveStripePaymentMethod(Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('artisan_stripe_payment_method', $request->headers->get('X-CSRF-TOKEN'))) {
            return $this->json(['error' => 'Jeton CSRF invalide.'], Response::HTTP_FORBIDDEN);
        }

        $user = $this->getUser();
        $payload = json_decode($request->getContent(), true);
        $providerPaymentMethodId = is_array($payload) ? $payload['payment_method_id'] ?? null : null;

        if (!$user instanceof User || !is_string($providerPaymentMethodId) || !str_starts_with($providerPaymentMethodId, 'pm_')) {
            return $this->json(['error' => 'Moyen de paiement invalide.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $artisan = $user->getArtisanProfile();

        if (null === $artisan) {
            return $this->json(['error' => 'Profil artisan introuvable.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $stripePaymentMethod = $this->stripeRequest('GET', 'payment_methods/'.$providerPaymentMethodId);
            $card = $stripePaymentMethod['card'] ?? null;

            if ($stripePaymentMethod['customer'] !== $artisan->getStripeCustomerId() || !is_array($card)) {
                return $this->json(['error' => 'Cette carte ne vous appartient pas.'], Response::HTTP_FORBIDDEN);
            }

            foreach ($this->paymentMethodRepo->findBy(['user' => $user]) as $existingMethod) {
                $existingMethod->setIsDefault(false);
            }

            $paymentMethod = $this->paymentMethodRepo->findOneBy(['providerPaymentMethodId' => $providerPaymentMethodId]) ?? new PaymentMethod();
            $paymentMethod
                ->setUser($user)
                ->setBrand((string) ($card['brand'] ?? 'card'))
                ->setLast4((string) ($card['last4'] ?? ''))
                ->setExpiresMonth((int) ($card['exp_month'] ?? 0))
                ->setExpiresYear((int) ($card['exp_year'] ?? 0))
                ->setProviderPaymentMethodId($providerPaymentMethodId)
                ->setIsDefault(true);

            $this->subscriptionRepo->findLatestForArtisan($artisan)?->setPaymentMethod($paymentMethod);

            $this->entityManager->persist($paymentMethod);
            $this->entityManager->flush();

            $this->stripeRequest('POST', 'customers/'.rawurlencode((string) $artisan->getStripeCustomerId()), [
                'invoice_settings[default_payment_method]' => $providerPaymentMethodId,
            ]);

            return $this->json([
                'brand' => $paymentMethod->getBrand(),
                'last4' => $paymentMethod->getLast4(),
                'expires_month' => $paymentMethod->getExpiresMonth(),
                'expires_year' => $paymentMethod->getExpiresYear(),
            ]);
        } catch (\RuntimeException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_GATEWAY);
        }
    }

    /** @return array<string, mixed> */
    private function stripeRequest(string $method, string $path, array $body = []): array
    {
        $response = $this->httpClient->request($method, 'https://api.stripe.com/v1/'.$path, [
            'auth_bearer' => $this->stripeSecretKey,
            'body' => $body,
        ]);
        $data = $response->toArray(false);

        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException($data['error']['message'] ?? 'Erreur Stripe.');
        }

        return $data;
    }

    private function ensureStripeCustomer(User $user, ArtisanProfile $artisan): string
    {
        $customerId = $artisan->getStripeCustomerId();
        $customerPayload = $this->stripeCustomerPayload($user, $artisan);

        if (null === $customerId || !str_starts_with($customerId, 'cus_')) {
            $customer = $this->stripeRequest('POST', 'customers', $customerPayload);
            $customerId = $customer['id'] ?? null;
            if (!is_string($customerId)) {
                throw new \RuntimeException('Client Stripe introuvable.');
            }
            $artisan->setStripeCustomerId($customerId);
            $this->entityManager->flush();
        } else {
            $this->stripeRequest('POST', 'customers/'.rawurlencode($customerId), $customerPayload);
        }

        return $customerId;
    }

    /** @return array<string, SubscriptionPlan> */
    private function activePlansByCode(): array
    {
        $plans = [];
        foreach ($this->subscriptionPlanRepo->findBy(['isActive' => true], ['position' => 'ASC']) as $plan) {
            $plans[$plan->getCode()->value] = $plan;
        }

        return $plans;
    }

    private function stripePriceId(SubscriptionPlan $plan, string $price, string $period): string
    {
        $interval = 'monthly' === $period ? 'month' : 'year';
        $amount = $this->priceToMinorUnits($price);
        $providerPriceId = $plan->getProviderPriceId();
        $stripePrice = null;

        if (is_string($providerPriceId) && str_starts_with($providerPriceId, 'price_')) {
            $stripePrice = $this->stripeRequest('GET', 'prices/'.rawurlencode($providerPriceId));
            if (($stripePrice['active'] ?? false)
                && ($stripePrice['currency'] ?? null) === 'eur'
                && (int) ($stripePrice['unit_amount'] ?? 0) === $amount
                && ($stripePrice['recurring']['interval'] ?? null) === $interval) {
                return $providerPriceId;
            }
        }

        $productId = is_array($stripePrice) ? $stripePrice['product'] ?? null : null;
        if (!is_string($productId) || !str_starts_with($productId, 'prod_')) {
            $product = $this->stripeRequest('POST', 'products', [
                'name' => sprintf('Abonnement %s', $plan->getName()),
                'metadata[plan_code]' => $plan->getCode()->value,
            ]);
            $productId = $product['id'] ?? null;
        }
        if (!is_string($productId)) {
            throw new \RuntimeException('Produit Stripe introuvable.');
        }

        $createdPrice = $this->stripeRequest('POST', 'prices', [
            'currency' => 'eur',
            'unit_amount' => (string) $amount,
            'recurring[interval]' => $interval,
            'product' => $productId,
            'metadata[plan_code]' => $plan->getCode()->value,
            'metadata[billing_period]' => $period,
        ]);
        $newPriceId = $createdPrice['id'] ?? null;
        if (!is_string($newPriceId)) {
            throw new \RuntimeException('Tarif Stripe introuvable.');
        }

        $plan->setProviderPriceId($newPriceId);
        $this->entityManager->flush();

        return $newPriceId;
    }

    private function priceToMinorUnits(string $price): int
    {
        return (int) round((float) $price * 100);
    }

    private function stripeDate(mixed $timestamp): ?\DateTimeImmutable
    {
        return is_int($timestamp) || ctype_digit((string) $timestamp)
            ? (new \DateTimeImmutable())->setTimestamp((int) $timestamp)
            : null;
    }

    private function subscriptionStatus(string $status): SubscriptionStatus
    {
        return match ($status) {
            'trialing' => SubscriptionStatus::TRIALING,
            'past_due', 'unpaid', 'incomplete' => SubscriptionStatus::PAST_DUE,
            'canceled', 'incomplete_expired' => SubscriptionStatus::CANCELLED,
            default => SubscriptionStatus::ACTIVE,
        };
    }

    /** @return array{amount_ttc: string, date: \DateTimeImmutable, period: string}|null */
    private function lastPaymentSummary(?ArtisanProfile $artisan): ?array
    {
        if (null === $artisan) {
            return null;
        }

        $invoice = $this->invoiceRepo->findLatestPaidForArtisan($artisan);
        $subscription = $invoice?->getSubscription();
        $date = $invoice?->getPaidAt() ?? $invoice?->getIssuedAt();
        if (null === $invoice || null === $subscription || null === $date) {
            return null;
        }

        return [
            'amount_ttc' => number_format((float) $invoice->getAmountTtc(), 2, ',', ' '),
            'date' => $date,
            'period' => $subscription->getBillingPeriod()->value,
        ];
    }

    private function syncStripeInvoice(mixed $stripeInvoice, Subscription $subscription): void
    {
        $invoiceId = is_array($stripeInvoice) ? ($stripeInvoice['id'] ?? null) : $stripeInvoice;
        if (!is_string($invoiceId) || !str_starts_with($invoiceId, 'in_')) {
            return;
        }

        $invoiceData = is_array($stripeInvoice) && isset($stripeInvoice['amount_paid'])
            ? $stripeInvoice
            : $this->stripeRequest('GET', 'invoices/'.rawurlencode($invoiceId));
        $subtotal = (int) ($invoiceData['subtotal'] ?? 0);
        $total = (int) ($invoiceData['total'] ?? $invoiceData['amount_paid'] ?? 0);
        $tax = max(0, $total - $subtotal);
        $invoice = $this->invoiceRepo->findOneBy(['providerInvoiceId' => $invoiceId]) ?? new Invoice();
        $artisan = $subscription->getArtisanProfile();
        $billingAddress = null !== $artisan ? implode(', ', array_filter([
            trim(sprintf('%s %s', $artisan->getHouseNumber() ?? '', $artisan->getRoad() ?? '')),
            $artisan->getAddressComplement(),
            $artisan->getPostalCode(),
            $artisan->getLocality(),
            $artisan->getCountry(),
        ], static fn (?string $value): bool => null !== $value && '' !== trim($value))) : null;
        $reference = substr((string) ($invoiceData['number'] ?? $invoiceId), 0, 30);
        $pdfSlug = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $invoiceId));
        $invoice
            ->setSubscription($subscription)
            ->setProviderInvoiceId($invoiceId)
            ->setReference($reference)
            ->setBillingName(null !== $artisan ? ($artisan->getLegalName() ?? $artisan->getCommercialName()) : null)
            ->setBillingAddress($billingAddress)
            ->setPdfFilename('facture-'.$pdfSlug.'.pdf')
            ->setAmountHt(number_format($subtotal / 100, 2, '.', ''))
            ->setAmountVat(number_format($tax / 100, 2, '.', ''))
            ->setAmountTtc(number_format($total / 100, 2, '.', ''))
            ->setPeriodStartsAt($this->stripeDate($invoiceData['period_start'] ?? null))
            ->setPeriodEndsAt($this->stripeDate($invoiceData['period_end'] ?? null))
            ->setIssuedAt($this->stripeDate($invoiceData['created'] ?? null))
            ->setDueAt($this->stripeDate($invoiceData['due_date'] ?? null));

        match ($invoiceData['status'] ?? null) {
            'paid' => $invoice->markPaid(),
            'void', 'uncollectible' => $invoice->setStatus(InvoiceStatus::CANCELLED),
            'open' => $invoice->setStatus(InvoiceStatus::ISSUED),
            default => $invoice->setStatus(InvoiceStatus::DRAFT),
        };
        $this->entityManager->persist($invoice);
    }

    /** @return array<string, string> */
    private function stripeCustomerPayload(User $user, ArtisanProfile $artisan): array
    {
        $street = trim(sprintf('%s %s', $artisan->getHouseNumber() ?? '', $artisan->getRoad() ?? ''));
        $address = [
            'address[line1]' => '' !== $street ? $street : null,
            'address[line2]' => $artisan->getAddressComplement(),
            'address[city]' => $artisan->getLocality(),
            'address[state]' => $artisan->getRegion() ?? $artisan->getState() ?? $artisan->getCounty(),
            'address[postal_code]' => $artisan->getPostalCode(),
            'address[country]' => $artisan->getCountryCode() ?? $user->getCountryCode(),
        ];
        $invoiceFields = array_filter([
            'SIREN' => $artisan->getSiren(),
            'SIRET' => $artisan->getSiret(),
            'Code APE' => $artisan->getApeCode(),
        ], static fn (?string $value): bool => null !== $value && '' !== $value);
        $payload = [
            'name' => $artisan->getLegalName() ?? $artisan->getCommercialName() ?? $user->getFullName(),
            'business_name' => $artisan->getCommercialName() ?? $artisan->getLegalName(),
            'individual_name' => $user->getFullName(),
            'email' => $user->getEmail(),
            'phone' => null !== $user->getPhoneNumber() ? mb_substr($user->getPhoneNumber(), 0, 20) : null,
            'preferred_locales[0]' => $user->getLocale(),
            'description' => sprintf('Artisan #%d — %s', (int) $artisan->getId(), $artisan->getCommercialName() ?? $user->getFullName()),
            'metadata[artisan_profile_id]' => (string) $artisan->getId(),
            'metadata[user_id]' => (string) $user->getId(),
            'metadata[siren]' => $artisan->getSiren(),
            'metadata[siret]' => $artisan->getSiret(),
            'metadata[ape_code]' => $artisan->getApeCode(),
            'metadata[legal_form]' => $artisan->getLegalForm(),
        ] + $address;

        foreach (array_values($invoiceFields) as $index => $value) {
            $name = array_keys($invoiceFields)[$index];
            $payload[sprintf('invoice_settings[custom_fields][%d][name]', $index)] = $name;
            $payload[sprintf('invoice_settings[custom_fields][%d][value]', $index)] = $value;
        }

        return array_filter($payload, static fn (?string $value): bool => null !== $value && '' !== $value);
    }

}
