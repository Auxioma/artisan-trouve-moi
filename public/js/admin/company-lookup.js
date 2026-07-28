'use strict';

(() => {
    const INITIALIZED_ATTRIBUTE = 'data-company-lookup-initialized';

    function findField(form, property) {
        return form?.querySelector(
            `[name$="[${property}]"]`
        ) ?? null;
    }

    function findTargetField(form, target) {
        if (!target) {
            return null;
        }

        return form?.querySelector(`[name="${target}"]`)
            ?? findField(form, target);
    }

    function setFieldValue(field, value) {
        if (!field || value === null || value === undefined) {
            return;
        }

        if (field instanceof HTMLInputElement && field.type === 'checkbox') {
            field.checked = Boolean(value);
            field.dispatchEvent(new Event('change', {bubbles: true}));
            return;
        }

        field.value = String(value);
        field.dispatchEvent(new Event('input', {bubbles: true}));
        field.dispatchEvent(new Event('change', {bubbles: true}));
    }

    function getEndpoint(input) {
        return input.dataset.companyLookupEndpoint
            || document.querySelector('meta[name="admin-company-lookup-url"]')?.content
            || '/admin/company-lookup';
    }

    function showMessage(input, message, type = 'info') {
        const existing = document.getElementById('company-lookup-message');
        existing?.remove();

        const alert = document.createElement('div');
        alert.id = 'company-lookup-message';
        alert.className = `alert alert-${type} mt-2 mb-0`;
        alert.setAttribute('role', 'status');
        alert.textContent = message;

        const widget = input.closest('.form-widget') ?? input.parentElement;
        widget?.append(alert);
    }

    async function lookup(input, button) {
        const identifier = input.value.replace(/\D/g, '');
        const expectedLength = Number.parseInt(
            input.dataset.companyLookupLength || '9',
            10
        );
        const identifierLabel = input.dataset.companyLookupLabel || 'SIREN';

        if (identifier.length !== expectedLength) {
            showMessage(
                input,
                `Saisissez un ${identifierLabel} de ${expectedLength} chiffres.`,
                'warning'
            );
            input.focus();
            return;
        }

        button.disabled = true;
        button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Recherche…';

        try {
            const url = new URL(getEndpoint(input), window.location.origin);
            url.searchParams.set('siren', identifier);

            const response = await fetch(url, {
                headers: {Accept: 'application/json'},
                credentials: 'same-origin',
            });
            const contentType = response.headers.get('content-type') || '';
            const content = await response.text();
            let payload = {};

            if (content) {
                try {
                    payload = JSON.parse(content);
                } catch {
                    if (contentType.includes('text/html')) {
                        throw new Error(
                            'Votre session a expiré. Rechargez la page puis reconnectez-vous.'
                        );
                    }

                    throw new Error('Le service a renvoyé une réponse invalide.');
                }
            }

            if (!response.ok) {
                throw new Error(payload.message || 'Recherche impossible.');
            }

            const form = input.closest('form');
            Object.entries(payload.company || {}).forEach(([property, value]) => {
                setFieldValue(findField(form, property), value);
            });

            /*
             * Les entités n'emploient pas toutes le même nom de propriété
             * pour la dénomination. Le profil artisan utilise legalName,
             * le partenaire commercial utilise companyName.
             */
            setFieldValue(
                findTargetField(form, input.dataset.companyLegalNameTarget),
                payload.company?.legalName
            );

            const company = payload.company || {};
            const addressLine1 = [company.houseNumber, company.road]
                .filter(Boolean)
                .join(' ');
            const formattedAddress = [
                addressLine1,
                company.addressComplement,
                [company.postalCode, company.city].filter(Boolean).join(' '),
                company.country,
            ].filter(Boolean).join(', ');

            [
                ['companyAddressTarget', addressLine1],
                ['companyAddressComplementTarget', company.addressComplement],
                ['companyPostalCodeTarget', company.postalCode],
                ['companyCityTarget', company.city],
                ['companyProfileCountryCodeTarget', company.countryCode],
                ['companyPartnerCountryCodeTarget', company.countryCode],
                ['companyLatitudeTarget', company.latitude],
                ['companyLongitudeTarget', company.longitude],
                ['companyFormattedAddressTarget', formattedAddress],
                ['companyDescriptionTarget', company.activityDescription],
                ['companyCommercialAreaTarget', company.commercialArea],
                ['companyContactJobTitleTarget', company.representativeJobTitle],
                ['companyRepresentativeFirstNameTarget', company.representativeFirstName],
                ['companyRepresentativeLastNameTarget', company.representativeLastName],
                ['companyIsActiveTarget', company.isActive],
            ].forEach(([target, value]) => {
                setFieldValue(findTargetField(form, input.dataset[target]), value);
            });

            showMessage(
                input,
                'Informations de l’entreprise préremplies depuis l’API publique.',
                'success'
            );
        } catch (error) {
            showMessage(
                input,
                error instanceof Error ? error.message : 'Recherche impossible.',
                'danger'
            );
        } finally {
            button.disabled = false;
            button.innerHTML = '<i class="fa fa-magnifying-glass"></i> Rechercher';
        }
    }

    function initialize(input) {
        if (input.hasAttribute(INITIALIZED_ATTRIBUTE)) {
            return;
        }

        input.setAttribute(INITIALIZED_ATTRIBUTE, 'true');
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-primary mt-2';
        button.innerHTML = '<i class="fa fa-magnifying-glass"></i> Rechercher';
        button.addEventListener('click', () => lookup(input, button));

        input.insertAdjacentElement('afterend', button);
    }

    function initializeAll() {
        document.querySelectorAll('input[data-company-lookup="true"]')
            .forEach(initialize);
    }

    document.addEventListener('DOMContentLoaded', initializeAll);
    document.addEventListener('turbo:load', initializeAll);
    document.addEventListener('ea.collection.item-added', initializeAll);
})();
