<?php

namespace App\Controller\Client;

use App\Entity\Users\ArtisanProfile;
use App\Entity\Users\User;
use App\Entity\Enum\UserType;
use App\Entity\Requests\ServiceRequest;
use App\Form\Client\ServiceRequestType;
use App\Repository\Reviews\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class DemandesController extends AbstractController
{
    public function __construct(
        private readonly ReviewRepository $reviewRepository,
    ) {
    }

    #[Route('/espace-client/mes-demandes', name: 'client_demandes')]
    public function __invoke(): Response
    {
        return $this->render('client/mes_demandes.html.twig');
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/espace-client/creation-devis/{slug}', name: 'client_creation_devis', methods: ['GET', 'POST'])]
    public function creationDevis(
        #[MapEntity(mapping: ['slug' => 'slug'])] ArtisanProfile $artisan,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response
    {
        if (!$artisan->isPublished()) {
            throw $this->createNotFoundException('Artisan introuvable.');
        }

        $client = $this->getUser();

        if (!$client instanceof User || UserType::CUSTOMER !== $client->getType()) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour envoyer une demande.');
        }

        $serviceRequest = (new ServiceRequest())
            ->setClient($client)
            ->setArtisanProfile($artisan);
        $form = $this->createForm(ServiceRequestType::class, $serviceRequest, [
            'artisan' => $artisan,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $serviceRequest->publish();
            $entityManager->persist($serviceRequest);
            $entityManager->flush();

            $this->addFlash('success', 'Votre demande a été envoyée à l’artisan.');

            return $this->redirectToRoute('client_demandes');
        }

        $reviewSummary = $this->reviewRepository
            ->getPublishedSummaryForArtisan($artisan);
        $serviceCategories = [];

        foreach ($artisan->getServices() as $service) {
            $categoryName = $service->isActive()
                ? $service->getCategory()?->getName()
                : null;

            if (null !== $categoryName && !in_array($categoryName, $serviceCategories, true)) {
                $serviceCategories[] = $categoryName;
            }
        }

        return $this->render('client/creation_devis.html.twig', [
            'artisan' => $artisan,
            'review_summary' => $reviewSummary,
            'service_categories' => $serviceCategories,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Envoie de devis a de multiples artisans.
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/espace-client/creation-devis-multiple', name: 'client_creation_devis_multiple', methods: ['GET', 'POST'])]
    public function creationDevisMultiple(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response
    {
        $client = $this->getUser();

        if (!$client instanceof User || UserType::CUSTOMER !== $client->getType()) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour envoyer une demande.');
        }

        // Reste de la logique pour la création de devis multiple

        return $this->render('client/creation_devis.html.twig', [
            'client' => $client,
        ]);
    }
}