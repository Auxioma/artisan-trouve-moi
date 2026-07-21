<?php

declare(strict_types=1);

namespace App\Controller\Artisan;

use App\Repository\Messaging\ConversationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ARTISAN')]
#[Route('/espace-prestataire/messages', name: 'app_artisan_messages')]
class MessagesController extends AbstractController
{
    public function __construct(
        private readonly ConversationRepository $convRepo,
    ) {
    }

    #[Route('', name: '', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $artisan = $this->getUser()?->getArtisanProfile();
        $convId = $request->query->getInt('conv', 0);

        // TODO : $conversations = $this->convRepo->findByArtisan($artisan);
        // TODO : $active = $convId ? $this->convRepo->find($convId) : null;

        return $this->render('artisan/messages.html.twig', [
            'artisan_name' => $artisan?->getCommercialName() ?? 'Mon entreprise',
            'artisan_initials' => $this->initiales($artisan),
            'demandes_count' => 12,
            'messages_count' => 3,
            'notifications_count' => 3,
            // 'conversations'    => $conversations,
            // 'active_conv'      => $active,
        ]);
    }

    private function initiales($a): string
    {
        $mots = explode(' ', $a?->getCommercialName() ?? 'Prestataire');

        return mb_strtoupper(mb_substr($mots[0], 0, 1).mb_substr($mots[1] ?? 'E', 0, 1));
    }
}
