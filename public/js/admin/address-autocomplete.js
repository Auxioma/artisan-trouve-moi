'use strict';

/**
 * Autocomplétion d’adresse OpenStreetMap pour EasyAdmin 5.
 *
 * Fonctionnement :
 * - écoute le champ EasyAdmin "profileAddressLine1" ;
 * - appelle la route Symfony /admin/geocode ;
 * - affiche une liste de suggestions ;
 * - remplit automatiquement les autres champs du UserProfile ;
 * - fonctionne avec les onglets EasyAdmin ;
 * - ne dépend pas des identifiants HTML générés par Symfony ;
 * - évite les requêtes en double ;
 * - gère les touches ↑, ↓, Entrée et Échap.
 */

(() => {
    const MINIMUM_CHARACTERS = 3;
    const DEBOUNCE_DELAY = 450;

    let activeAbortController = null;

    /**
     * Évite d’initialiser plusieurs fois le même champ.
     */
    const INITIALIZED_ATTRIBUTE = 'data-osm-autocomplete-initialized';

    /**
     * Ajoute le CSS nécessaire à la liste des suggestions.
     */
    function injectStyles() {
        if (document.getElementById('osm-autocomplete-styles')) {
            return;
        }

        const style = document.createElement('style');

        style.id = 'osm-autocomplete-styles';
        style.textContent = `
            .osm-autocomplete-wrapper {
                position: relative;
                width: 100%;
            }

            .osm-autocomplete-results {
                position: absolute;
                top: calc(100% + 6px);
                left: 0;
                right: 0;
                z-index: 99999;
                display: none;
                max-height: 320px;
                padding: 6px;
                margin: 0;
                overflow-y: auto;
                list-style: none;
                background: var(--body-bg, #ffffff);
                border: 1px solid var(--border-color, #d8dbe2);
                border-radius: 10px;
                box-shadow: 0 14px 35px rgba(15, 23, 42, 0.16);
            }

            .osm-autocomplete-results.is-visible {
                display: block;
            }

            .osm-autocomplete-item {
                display: flex;
                align-items: flex-start;
                gap: 10px;
                width: 100%;
                padding: 11px 12px;
                margin: 0;
                color: var(--text-color, #1f2937);
                text-align: left;
                cursor: pointer;
                background: transparent;
                border: 0;
                border-radius: 7px;
            }

            .osm-autocomplete-item:hover,
            .osm-autocomplete-item.is-active {
                background: var(--highlight-bg, #f1f5f9);
            }

            .osm-autocomplete-item-icon {
                flex: 0 0 auto;
                padding-top: 2px;
                color: #5d00ff;
            }

            .osm-autocomplete-item-content {
                min-width: 0;
            }

            .osm-autocomplete-item-title {
                display: block;
                margin-bottom: 2px;
                overflow: hidden;
                font-size: 14px;
                font-weight: 600;
                line-height: 1.35;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .osm-autocomplete-item-description {
                display: block;
                overflow: hidden;
                color: var(--text-muted, #64748b);
                font-size: 12px;
                line-height: 1.4;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .osm-autocomplete-message {
                padding: 12px;
                color: var(--text-muted, #64748b);
                font-size: 13px;
                text-align: center;
            }

            .osm-autocomplete-message.is-error {
                color: #b42318;
            }

            .osm-autocomplete-loading {
                position: absolute;
                top: 50%;
                right: 13px;
                width: 17px;
                height: 17px;
                margin-top: -8px;
                pointer-events: none;
                border: 2px solid rgba(93, 0, 255, 0.18);
                border-top-color: #5d00ff;
                border-radius: 50%;
                animation: osm-autocomplete-spin 0.7s linear infinite;
            }

            @keyframes osm-autocomplete-spin {
                to {
                    transform: rotate(360deg);
                }
            }
        `;

        document.head.appendChild(style);
    }

    /**
     * Exécute une fonction après un délai et annule l’appel précédent.
     */
    function debounce(callback, delay) {
        let timer = null;

        return (...args) => {
            window.clearTimeout(timer);

            timer = window.setTimeout(() => {
                callback(...args);
            }, delay);
        };
    }

    /**
     * Recherche un champ EasyAdmin par son nom Symfony.
     *
     * Exemple :
     * User[profileCity]
     */
    function findField(form, fieldName) {
        if (!form) {
            return null;
        }

        const selectors = [
            `[name="${fieldName}"]`,
            `[name$="[${fieldName}]"]`,
            `#${fieldName}`,
            `[data-osm-target="${fieldName}"]`,
        ];

        for (const selector of selectors) {
            const field = form.querySelector(selector);

            if (field) {
                return field;
            }
        }

        return null;
    }

    /**
     * Recherche prioritairement le véritable champ Adresse.
     *
     * Le data-osm-search qui était placé sur le libellé ne doit pas
     * prendre la priorité sur profileAddressLine1.
     */
    function findSearchInputs() {
        const addressFields = Array.from(
            document.querySelectorAll(
                [
                    'input[name$="[profileAddressLine1]"]',
                    'input[data-osm-target="profileAddressLine1"]',
                    'input#profileAddressLine1',
                ].join(',')
            )
        );

        if (addressFields.length > 0) {
            return addressFields;
        }

        return Array.from(
            document.querySelectorAll('input[data-osm-search="true"]')
        );
    }

    /**
     * Construit l’URL de la route de géocodage.
     *
     * Possibilités :
     * - data-geocode-url placé sur le champ ;
     * - data-admin-geocode-url placé sur body ;
     * - détection automatique de /admin/geocode.
     */
    function resolveGeocodeUrl(input) {
        const inputUrl = input.dataset.geocodeUrl;

        if (inputUrl) {
            return inputUrl;
        }

        const bodyUrl = document.body?.dataset.adminGeocodeUrl;

        if (bodyUrl) {
            return bodyUrl;
        }

        const currentUrl = new URL(window.location.href);
        const adminPosition = currentUrl.pathname.indexOf('/admin');

        if (adminPosition !== -1) {
            const applicationPrefix = currentUrl.pathname.substring(
                0,
                adminPosition
            );

            return `${applicationPrefix}/admin/geocode`;
        }

        return '/admin/geocode';
    }

    /**
     * Modifie proprement la valeur d’un champ et informe Symfony/JS.
     */
    function setFieldValue(field, value) {
        if (!field) {
            return;
        }

        const normalizedValue = value ?? '';

        if (
            field instanceof HTMLInputElement
            && field.type === 'checkbox'
        ) {
            const checked = Boolean(normalizedValue);

            if (field.checked !== checked) {
                field.checked = checked;
                field.dispatchEvent(
                    new Event('change', {
                        bubbles: true,
                    })
                );
            }

            return;
        }

        if (
            field instanceof HTMLInputElement
            || field instanceof HTMLTextAreaElement
            || field instanceof HTMLSelectElement
        ) {
            field.value = String(normalizedValue);

            field.dispatchEvent(
                new Event('input', {
                    bubbles: true,
                })
            );

            field.dispatchEvent(
                new Event('change', {
                    bubbles: true,
                })
            );
        }
    }

    /**
     * Crée l’élément qui contiendra les suggestions.
     */
    function createResultsContainer(input) {
        const existingWrapper = input.closest(
            '.osm-autocomplete-wrapper'
        );

        if (existingWrapper) {
            const existingResults = existingWrapper.querySelector(
                '.osm-autocomplete-results'
            );

            if (existingResults) {
                return existingResults;
            }
        }

        const wrapper = document.createElement('div');
        wrapper.className = 'osm-autocomplete-wrapper';

        const parent = input.parentNode;

        parent.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        const results = document.createElement('div');

        results.className = 'osm-autocomplete-results';
        results.setAttribute('role', 'listbox');
        results.setAttribute(
            'aria-label',
            'Suggestions d’adresses'
        );

        wrapper.appendChild(results);

        return results;
    }

    /**
     * Affiche ou masque l’indicateur de chargement.
     */
    function setLoading(input, loading) {
        const wrapper = input.closest('.osm-autocomplete-wrapper');

        if (!wrapper) {
            return;
        }

        const currentLoader = wrapper.querySelector(
            '.osm-autocomplete-loading'
        );

        if (!loading) {
            currentLoader?.remove();

            return;
        }

        if (currentLoader) {
            return;
        }

        const loader = document.createElement('span');

        loader.className = 'osm-autocomplete-loading';
        loader.setAttribute('aria-hidden', 'true');

        wrapper.appendChild(loader);
    }

    /**
     * Ferme la liste des suggestions.
     */
    function closeResults(results) {
        results.classList.remove('is-visible');
        results.replaceChildren();
    }

    /**
     * Affiche un message dans la liste.
     */
    function showMessage(results, message, isError = false) {
        results.replaceChildren();

        const element = document.createElement('div');

        element.className = 'osm-autocomplete-message';

        if (isError) {
            element.classList.add('is-error');
        }

        element.textContent = message;

        results.appendChild(element);
        results.classList.add('is-visible');
    }

    /**
     * Remplit tous les champs du profil après sélection.
     */
    function fillAddressFields(form, input, result) {
        const fields = {
            label: findField(form, 'profileLabel'),
            addressLine1: findField(form, 'profileAddressLine1'),
            postalCode: findField(form, 'profilePostalCode'),
            city: findField(form, 'profileCity'),
            district: findField(form, 'profileDistrict'),
            region: findField(form, 'profileRegion'),
            department: findField(form, 'profileDepartment'),
            countryCode: findField(form, 'profileCountryCode'),
            formattedAddress: findField(
                form,
                'profileFormattedAddress'
            ),
            latitude: findField(form, 'profileLatitude'),
            longitude: findField(form, 'profileLongitude'),
            providerPlaceId: findField(
                form,
                'profileProviderPlaceId'
            ),
            providerName: findField(form, 'profileProviderName'),
            isGeocoded: findField(
                form,
                'profileIsGeocodedForm'
            ),
        };

        /*
         * Le champ où l’utilisateur recherche est l’adresse principale.
         */
        setFieldValue(
            fields.addressLine1 ?? input,
            result.addressLine1 ?? result.label ?? ''
        );

        /*
         * Le libellé métier n’est rempli que s’il est vide.
         * Cela évite d’écraser "Domicile", "Facturation", etc.
         */
        if (
            fields.label
            && String(fields.label.value ?? '').trim() === ''
        ) {
            setFieldValue(fields.label, result.label ?? '');
        }

        setFieldValue(fields.postalCode, result.postalCode);
        setFieldValue(fields.city, result.city);
        setFieldValue(fields.district, result.district);
        setFieldValue(fields.region, result.region);
        setFieldValue(fields.department, result.department);
        setFieldValue(fields.countryCode, result.countryCode);

        setFieldValue(
            fields.formattedAddress,
            result.displayName
                ?? result.formattedAddress
                ?? result.label
                ?? ''
        );

        setFieldValue(fields.latitude, result.latitude);
        setFieldValue(fields.longitude, result.longitude);

        setFieldValue(
            fields.providerPlaceId,
            result.providerPlaceId
        );

        setFieldValue(
            fields.providerName,
            result.providerName ?? 'OSM'
        );

        setFieldValue(fields.isGeocoded, true);

        input.dataset.osmSelected = 'true';

        console.info(
            '[OSM] Adresse sélectionnée et champs remplis.',
            result
        );
    }

    /**
     * Crée une suggestion cliquable.
     */
    function createSuggestionElement(
        result,
        index,
        onSelect
    ) {
        const button = document.createElement('button');

        button.type = 'button';
        button.className = 'osm-autocomplete-item';
        button.setAttribute('role', 'option');
        button.setAttribute('data-index', String(index));

        const icon = document.createElement('span');

        icon.className = 'osm-autocomplete-item-icon';
        icon.setAttribute('aria-hidden', 'true');
        icon.innerHTML = '<i class="fa-solid fa-location-dot"></i>';

        const content = document.createElement('span');

        content.className = 'osm-autocomplete-item-content';

        const title = document.createElement('span');

        title.className = 'osm-autocomplete-item-title';
        title.textContent =
            result.label
            ?? result.addressLine1
            ?? result.city
            ?? 'Adresse';

        const description = document.createElement('span');

        description.className =
            'osm-autocomplete-item-description';

        description.textContent =
            result.displayName
            ?? [
                result.addressLine1,
                result.postalCode,
                result.city,
            ]
                .filter(Boolean)
                .join(', ');

        content.append(title, description);
        button.append(icon, content);

        /*
         * mousedown est utilisé afin que le blur de l’input
         * ne ferme pas la liste avant la sélection.
         */
        button.addEventListener('mousedown', (event) => {
            event.preventDefault();
            onSelect(result);
        });

        return button;
    }

    /**
     * Affiche les résultats retournés par Symfony.
     */
    function renderResults(
        resultsContainer,
        results,
        onSelect
    ) {
        resultsContainer.replaceChildren();

        if (!Array.isArray(results) || results.length === 0) {
            showMessage(
                resultsContainer,
                'Aucune adresse trouvée.'
            );

            return;
        }

        results.forEach((result, index) => {
            resultsContainer.appendChild(
                createSuggestionElement(
                    result,
                    index,
                    onSelect
                )
            );
        });

        resultsContainer.classList.add('is-visible');
    }

    /**
     * Appelle la route Symfony.
     */
    async function searchAddress(
        input,
        resultsContainer,
        query
    ) {
        if (activeAbortController) {
            activeAbortController.abort();
        }

        activeAbortController = new AbortController();

        const geocodeUrl = resolveGeocodeUrl(input);
        const url = new URL(geocodeUrl, window.location.origin);

        url.searchParams.set('q', query);

        setLoading(input, true);

        console.info('[OSM] Recherche :', url.toString());

        try {
            const response = await fetch(url.toString(), {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                signal: activeAbortController.signal,
            });

            if (!response.ok) {
                throw new Error(
                    `Erreur HTTP ${response.status} ${response.statusText}`
                );
            }

            const payload = await response.json();

            if (payload.error) {
                console.error(
                    '[OSM] Erreur renvoyée par Symfony :',
                    payload.error
                );
            }

            renderResults(
                resultsContainer,
                payload.results ?? [],
                (result) => {
                    const form = input.closest('form');

                    if (!form) {
                        console.error(
                            '[OSM] Le formulaire EasyAdmin est introuvable.'
                        );

                        return;
                    }

                    fillAddressFields(form, input, result);
                    closeResults(resultsContainer);
                    input.focus();
                }
            );
        } catch (error) {
            if (error instanceof DOMException && error.name === 'AbortError') {
                return;
            }

            console.error(
                '[OSM] Impossible de récupérer les adresses :',
                error
            );

            showMessage(
                resultsContainer,
                'Impossible de rechercher les adresses. Vérifiez la console.',
                true
            );
        } finally {
            setLoading(input, false);
        }
    }

    /**
     * Gère les touches clavier dans la liste.
     */
    function handleKeyboardNavigation(
        event,
        resultsContainer
    ) {
        const items = Array.from(
            resultsContainer.querySelectorAll(
                '.osm-autocomplete-item'
            )
        );

        if (items.length === 0) {
            return;
        }

        let activeIndex = items.findIndex((item) =>
            item.classList.contains('is-active')
        );

        if (event.key === 'ArrowDown') {
            event.preventDefault();

            activeIndex =
                activeIndex < items.length - 1
                    ? activeIndex + 1
                    : 0;
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();

            activeIndex =
                activeIndex > 0
                    ? activeIndex - 1
                    : items.length - 1;
        } else if (event.key === 'Enter') {
            if (activeIndex >= 0) {
                event.preventDefault();
                items[activeIndex].dispatchEvent(
                    new MouseEvent('mousedown', {
                        bubbles: true,
                    })
                );
            }

            return;
        } else if (event.key === 'Escape') {
            closeResults(resultsContainer);

            return;
        } else {
            return;
        }

        items.forEach((item, index) => {
            item.classList.toggle(
                'is-active',
                index === activeIndex
            );
        });

        items[activeIndex]?.scrollIntoView({
            block: 'nearest',
        });
    }

    /**
     * Initialise un champ d’adresse.
     */
    function initializeInput(input) {
        if (
            !(input instanceof HTMLInputElement)
            || input.hasAttribute(INITIALIZED_ATTRIBUTE)
        ) {
            return;
        }

        input.setAttribute(INITIALIZED_ATTRIBUTE, 'true');
        input.setAttribute('autocomplete', 'off');
        input.setAttribute('spellcheck', 'false');
        input.setAttribute('aria-autocomplete', 'list');

        if (!input.placeholder) {
            input.placeholder =
                'Commencez à écrire une adresse…';
        }

        const resultsContainer =
            createResultsContainer(input);

        const debouncedSearch = debounce(() => {
            const query = input.value.trim();

            if (query.length < MINIMUM_CHARACTERS) {
                closeResults(resultsContainer);

                return;
            }

            searchAddress(
                input,
                resultsContainer,
                query
            );
        }, DEBOUNCE_DELAY);

        input.addEventListener('input', () => {
            /*
             * L’utilisateur modifie l’adresse après une sélection :
             * l’adresse n’est plus considérée comme géocodée.
             */
            if (input.dataset.osmSelected === 'true') {
                input.dataset.osmSelected = 'false';

                const form = input.closest('form');
                const isGeocodedField = findField(
                    form,
                    'profileIsGeocodedForm'
                );

                setFieldValue(isGeocodedField, false);
            }

            debouncedSearch();
        });

        input.addEventListener('keydown', (event) => {
            handleKeyboardNavigation(
                event,
                resultsContainer
            );
        });

        input.addEventListener('focus', () => {
            if (
                resultsContainer.children.length > 0
                && input.value.trim().length >= MINIMUM_CHARACTERS
            ) {
                resultsContainer.classList.add('is-visible');
            }
        });

        document.addEventListener('mousedown', (event) => {
            const wrapper = input.closest(
                '.osm-autocomplete-wrapper'
            );

            if (
                wrapper
                && !wrapper.contains(event.target)
            ) {
                closeResults(resultsContainer);
            }
        });

        console.info(
            '[OSM] Autocomplétion initialisée sur :',
            input.name || input.id || input
        );
    }

    /**
     * Initialise tous les champs présents dans la page.
     */
    function initializeAutocomplete() {
        injectStyles();

        const inputs = findSearchInputs();

        if (inputs.length === 0) {
            console.warn(
                '[OSM] Aucun champ Adresse trouvé.',
                'Champ recherché : input[name$="[profileAddressLine1]"]'
            );

            return;
        }

        inputs.forEach(initializeInput);
    }

    /**
     * Chargement classique.
     */
    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            initializeAutocomplete
        );
    } else {
        initializeAutocomplete();
    }

    /**
     * Compatibilité avec les navigations Turbo éventuelles.
     */
    document.addEventListener(
        'turbo:load',
        initializeAutocomplete
    );

    /**
     * Compatibilité avec les événements EasyAdmin.
     */
    document.addEventListener(
        'ea.collection.item-added',
        initializeAutocomplete
    );

    /**
     * Réinitialisation si EasyAdmin remplace une partie du formulaire.
     */
    const observer = new MutationObserver((mutations) => {
        const containsNewInput = mutations.some((mutation) =>
            Array.from(mutation.addedNodes).some((node) => {
                if (!(node instanceof HTMLElement)) {
                    return false;
                }

                return (
                    node.matches?.(
                        'input[name$="[profileAddressLine1]"]'
                    )
                    || node.querySelector?.(
                        'input[name$="[profileAddressLine1]"]'
                    )
                );
            })
        );

        if (containsNewInput) {
            initializeAutocomplete();
        }
    });

    observer.observe(document.documentElement, {
        childList: true,
        subtree: true,
    });

    console.info(
        '[OSM] address-autocomplete.js chargé.'
    );
})();