<?php

namespace App\Security;

use App\Entity\Enum\UserType;
use App\Entity\Users\User;
use App\Repository\User\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class UserAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';
    public const ARTISAN_LOGIN_ROUTE = 'app_artisan_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private UserRepository $userRepository,
    ) {
    }

    public function supports(Request $request): bool
    {
        if (!$request->isMethod('POST')) {
            return false;
        }

        $requestPath = $request->getBaseUrl().$request->getPathInfo();

        return in_array($requestPath, [
            $this->urlGenerator->generate(self::LOGIN_ROUTE),
            $this->urlGenerator->generate(self::ARTISAN_LOGIN_ROUTE),
        ], true);
    }

    public function authenticate(Request $request): Passport
    {
        $email = mb_strtolower(trim($request->getPayload()->getString('email')));
        $artisanLogin = $this->isArtisanLoginRequest($request);

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email, fn (string $userIdentifier): User => $this->loadUserForLogin($userIdentifier, $artisanLogin)),
            new PasswordCredentials($request->getPayload()->getString('password')),
            [
                new CsrfTokenBadge('authenticate', $request->getPayload()->getString('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        $user = $token->getUser();

        if ($user instanceof User && $user->getType() === UserType::ARTISAN) {
            return new RedirectResponse(
                $this->urlGenerator->generate('app_artisan_account')
            );
        }

        return new RedirectResponse(
            $this->urlGenerator->generate('app_account')
        );
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(
            $this->isArtisanAreaRequest($request)
                ? self::ARTISAN_LOGIN_ROUTE
                : self::LOGIN_ROUTE
        );
    }

    private function isArtisanLoginRequest(Request $request): bool
    {
        return $request->attributes->get('_route') === self::ARTISAN_LOGIN_ROUTE
            || $request->getBaseUrl().$request->getPathInfo()
                === $this->urlGenerator->generate(self::ARTISAN_LOGIN_ROUTE);
    }

    private function isArtisanAreaRequest(Request $request): bool
    {
        $route = $request->attributes->get('_route');

        if (is_string($route) && str_starts_with($route, 'app_artisan_')) {
            return true;
        }

        return str_starts_with($request->getPathInfo(), '/artisan');
    }

    private function loadUserForLogin(
        string $email,
        bool $artisanLogin,
    ): User {
        $user = $this->userRepository->findOneByEmail($email);

        if (!$user instanceof User) {
            throw new CustomUserMessageAuthenticationException(
                'Adresse e-mail ou mot de passe invalide.'
            );
        }

        if ($artisanLogin && $user->getType() !== UserType::ARTISAN) {
            throw new CustomUserMessageAuthenticationException(
                'Ce compte n\'est pas un compte artisan.'
            );
        }

        return $user;
    }
}
