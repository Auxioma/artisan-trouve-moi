'use strict';

(() => {
    function slugify(value) {
        return String(value || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/(^-|-$)/g, '');
    }

    function initialize(legalNameField) {
        if (legalNameField.dataset.artisanSlugPreviewInitialized === 'true') {
            return;
        }

        const form = legalNameField.closest('form');
        const slugField = form?.querySelector('input[data-artisan-slug="true"]');

        if (!slugField) {
            return;
        }

        legalNameField.dataset.artisanSlugPreviewInitialized = 'true';

        const updatePreview = () => {
            slugField.value = slugify(legalNameField.value);
        };

        legalNameField.addEventListener('input', updatePreview);
        legalNameField.addEventListener('change', updatePreview);

        if (!slugField.value) {
            updatePreview();
        }
    }

    function initializeAll() {
        document.querySelectorAll('input[data-artisan-legal-name="true"]')
            .forEach(initialize);
    }

    document.addEventListener('DOMContentLoaded', initializeAll);
    document.addEventListener('turbo:load', initializeAll);
})();
