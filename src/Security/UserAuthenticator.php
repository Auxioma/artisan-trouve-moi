<?php

/**
 * Copyright(c) 2026 Boolts (https://boolts.com).
 *
 * Ce fichier fait partie d’un projet développé par Auxioma Web Agency
 * pour l’entreprise Pastelit Co.
 * Tous droits réservés.
 *
 * Ce code source est la propriété exclusive de Auxioma Web Agency
 * et Pastelit Co.
 * Toute reproduction, modification, distribution ou utilisation
 * sans autorisation préalable est interdite.
 */

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
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

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        /*
         * Les noms doivent correspondre exactement aux attributs "name"
         * du formulaire Twig :
         *
         * name="_username"
         * name="_password"
         * name="_csrf_token"
         */
        $email = trim(
            $request->getPayload()->getString('_username')
        );

        $password = $request->getPayload()->getString('_password');

        $csrfToken = $request->getPayload()->getString('_csrf_token');

        $request->getSession()->set(
            SecurityRequestAttributes::LAST_USERNAME,
            $email
        );

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge(
                    'authenticate',
                    $csrfToken
                ),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName,
    ): ?Response {
        $targetPath = $this->getTargetPath(
            $request->getSession(),
            $firewallName
        );

        if (null !== $targetPath) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse(
            $this->urlGenerator->generate(
                'client_dashboard'
            )
        );
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(
            self::LOGIN_ROUTE
        );
    }
}
