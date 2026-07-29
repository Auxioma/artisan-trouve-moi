<?php

declare(strict_types=1);

namespace App\Controller\Artisan;

use App\Entity\Billing\PaymentMethod;
use App\Entity\Users\ArtisanProfile;
use App\Entity\Users\User;
use App\Repository\Billing\PaymentMethodRepository;
use App\Repository\Billing\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[IsGranted('ROLE_ARTISAN')]
#[Route('/espace-prestataire/abonnement', name: 'app_artisan_abonnement')]
class AbonnementController extends AbstractController
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptionRepo,
        private readonly PaymentMethodRepository $paymentMethodRepo,
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

        return $this->render('artisan/abonnement.html.twig', [
            'stripe_public_key' => $this->stripePublicKey,
            'payment_method' => $paymentMethod,
            'artisan' => $user instanceof User ? $user->getArtisanProfile() : null,
        ]);
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
            $customerId = $artisan->getStripeCustomerId();
            $customerPayload = $this->stripeCustomerPayload($user, $artisan);

            if (null === $customerId) {
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
