<?php

/**
 * Copyright(c) 2026 TrouveMoi.
 *
 * Ce fichier fait partie d'un projet développé par Auxioma Web Agency.
 * Tous droits réservés.
 */

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }
    public function index(): Response
    {
        return $this->render('admin/pages/index.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Artisan Trouve Moi');
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            // Typographies du design system « Atelier de confiance »
            ->addHtmlContentToHead('<link rel="preconnect" href="https://fonts.googleapis.com">')
            ->addHtmlContentToHead('<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>')
            ->addHtmlContentToHead('<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">')

            // Thème TrouveMoi
            ->addCssFile('css/admin/trouvemoi-admin.css')
            ->addHtmlContentToHead(sprintf(
                '<meta name="admin-geocode-url" content="%s">',
                htmlspecialchars(
                    $this->urlGenerator->generate('admin_geocode'),
                    ENT_QUOTES,
                ),
            ))

            /*
             * Autocomplete OSM : chargé UNE SEULE FOIS ici, pour tout
             * le back-office. Ne PAS le re-déclarer dans un
             * CrudController, sinon EasyAdmin lève l'erreur
             * « each asset can only be added once ».
             */
            ->addJsFile('js/admin/address-autocomplete.js');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::section('Gestion des comptes');

        /*
         * EasyAdmin 5 : linkTo(ControllerFqcn, label, icône).
         * L'ancien linkToCrud(label, icône, Entity::class) de la
         * v4 a été supprimé — ne pas y revenir.
         */
        yield MenuItem::linkTo(
            UserCrudController::class,
            'Utilisateurs',
            'fas fa-users'
        );

        yield MenuItem::linkTo(
            ArtisanProfileCrudController::class,
            'Artisans',
            'fas fa-screwdriver-wrench'
        );

        yield MenuItem::linkTo(
            CommercialPartnerProfileCrudController::class,
            'Partenaires',
            'fas fa-handshake'
        );
    }
}