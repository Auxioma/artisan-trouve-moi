<?php

namespace App\EventListener;

use App\Entity\Users\User;
use App\Entity\Users\UserSession;
use App\Repository\Users\UserSessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserSessionTracker implements EventSubscriberInterface
{
    private const SESSION_KEY = '_user_session_token';

    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private UserSessionRepository $repository,
        private EntityManagerInterface $em,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onRequest', 4]];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();

        if (!$user instanceof User) {
            return;
        }

        $request = $event->getRequest();
        if (!$request->hasSession()) {
            return;
        }

        $phpSession = $request->getSession();
        $sessionToken = $phpSession->get(self::SESSION_KEY);
        $now = new \DateTimeImmutable();

        // Première requête authentifiée de cette session PHP → on crée la ligne.
        if (!$sessionToken) {
            $sessionToken = bin2hex(random_bytes(16));
            $phpSession->set(self::SESSION_KEY, $sessionToken);

            $userAgent = (string) $request->headers->get('User-Agent', '');

            $entry = (new UserSession())
                ->setUser($user)
                ->setSessionToken($sessionToken)
                ->setUserAgent($userAgent)
                ->setDeviceLabel($this->buildDeviceLabel($userAgent))
                ->setIpAddress($request->getClientIp())
                ->setCreatedAt($now)
                ->setLastActivityAt($now);

            $this->em->persist($entry);
            $this->em->flush();

            return;
        }

        $entry = $this->repository->findOneBy(['sessionToken' => $sessionToken]);
        if (!$entry) {
            return;
        }

        $diff = $now->getTimestamp() - $entry->getLastActivityAt()->getTimestamp();
        if ($diff >= 60) {
            $entry->setLastActivityAt($now);
            $this->em->flush();
        }
    }

    private function buildDeviceLabel(string $userAgent): string
    {
        $ua = strtolower($userAgent);

        $device = match (true) {
            str_contains($ua, 'iphone') => 'iPhone',
            str_contains($ua, 'ipad') => 'iPad',
            str_contains($ua, 'android') => 'Android',
            str_contains($ua, 'macintosh'), str_contains($ua, 'mac os') => 'MacBook',
            str_contains($ua, 'windows') => 'Windows',
            str_contains($ua, 'linux') => 'Linux',
            default => 'Appareil inconnu',
        };

        $browser = match (true) {
            str_contains($ua, 'edg/') => 'Edge',
            str_contains($ua, 'chrome') && !str_contains($ua, 'edg/') => 'Chrome',
            str_contains($ua, 'firefox') => 'Firefox',
            str_contains($ua, 'safari') && !str_contains($ua, 'chrome') => 'Safari',
            default => 'Navigateur',
        };

        return sprintf('%s · %s', $device, $browser);
    }
}
