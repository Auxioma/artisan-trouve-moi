'use strict';

/**
 * Carte de la zone d'intervention dans le formulaire EasyAdmin des artisans.
 * Le centre est enregistré dans latitude/longitude et le cercle dans
 * travelRadiusKm : aucune géométrie supplémentaire n'est nécessaire.
 */
(() => {
    const INITIALIZED_ATTRIBUTE = 'data-intervention-map-initialized';
    const DEFAULT_CENTER = [46.603354, 1.888334];
    const DEFAULT_ZOOM = 6;

    function findField(form, property) {
        return form?.querySelector(`[name$="[${property}]"]`) ?? null;
    }

    function numberValue(field) {
        const value = Number.parseFloat(field?.value ?? '');

        return Number.isFinite(value) ? value : null;
    }

    function radiusValue(field) {
        const value = Number.parseInt(field?.value ?? '', 10);

        return Number.isFinite(value) && value > 0 ? value : 20;
    }

    function setFieldValue(field, value) {
        if (!field) {
            return;
        }

        field.value = String(value);
        field.dispatchEvent(new Event('input', {bubbles: true}));
        field.dispatchEvent(new Event('change', {bubbles: true}));
    }

    function coordinates(latitudeField, longitudeField) {
        const latitude = numberValue(latitudeField);
        const longitude = numberValue(longitudeField);

        if (
            latitude === null || longitude === null
            || latitude < -90 || latitude > 90
            || longitude < -180 || longitude > 180
        ) {
            return null;
        }

        return [latitude, longitude];
    }

    function injectStyles() {
        if (document.getElementById('intervention-map-styles')) {
            return;
        }

        const style = document.createElement('style');
        style.id = 'intervention-map-styles';
        style.textContent = `
            .intervention-map { height: 390px; border: 1px solid var(--border-color, #d8dbe2); border-radius: 10px; }
            .intervention-map-help { margin: 10px 0 0; color: var(--text-muted, #64748b); font-size: .875rem; }
        `;
        document.head.appendChild(style);
    }

    function initialize(radiusField) {
        if (radiusField.hasAttribute(INITIALIZED_ATTRIBUTE) || !window.L) {
            return;
        }

        const form = radiusField.closest('form');
        const latitudeField = findField(form, 'latitude');
        const longitudeField = findField(form, 'longitude');

        if (!form || !latitudeField || !longitudeField) {
            return;
        }

        radiusField.setAttribute(INITIALIZED_ATTRIBUTE, 'true');
        injectStyles();

        const container = document.createElement('div');
        container.className = 'col-12 mt-2';
        container.innerHTML = '<div class="intervention-map" aria-label="Carte de la zone d’intervention"></div><p class="intervention-map-help"><i class="fa fa-circle-info"></i> Cliquez sur la carte pour déplacer l’adresse de référence. Le cercle représente le rayon d’intervention.</p>';
        radiusField.closest('.field-integer, .form-widget, .form-group')?.after(container);

        const mapElement = container.querySelector('.intervention-map');
        const initialCoordinates = coordinates(latitudeField, longitudeField);
        const map = window.L.map(mapElement, {scrollWheelZoom: false}).setView(
            initialCoordinates ?? DEFAULT_CENTER,
            initialCoordinates ? 12 : DEFAULT_ZOOM
        );

        window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19,
        }).addTo(map);

        let marker = null;
        let circle = null;

        function updateMap({fit = false} = {}) {
            const point = coordinates(latitudeField, longitudeField);
            const radius = radiusValue(radiusField) * 1000;

            if (!point) {
                marker?.remove();
                circle?.remove();
                marker = null;
                circle = null;
                return;
            }

            if (!marker) {
                marker = window.L.marker(point, {draggable: true}).addTo(map);
                marker.on('dragend', () => {
                    const position = marker.getLatLng();
                    setFieldValue(latitudeField, position.lat.toFixed(7));
                    setFieldValue(longitudeField, position.lng.toFixed(7));
                });
            } else {
                marker.setLatLng(point);
            }

            if (!circle) {
                circle = window.L.circle(point, {
                    radius,
                    color: '#5d00ff',
                    fillColor: '#5d00ff',
                    fillOpacity: 0.16,
                }).addTo(map);
            } else {
                circle.setLatLng(point);
                circle.setRadius(radius);
            }

            if (fit) {
                map.fitBounds(circle.getBounds(), {padding: [24, 24], maxZoom: 13});
            }
        }

        map.on('click', (event) => {
            setFieldValue(latitudeField, event.latlng.lat.toFixed(7));
            setFieldValue(longitudeField, event.latlng.lng.toFixed(7));
            updateMap({fit: true});
        });

        [latitudeField, longitudeField, radiusField].forEach((field) => {
            field.addEventListener('input', () => updateMap());
            field.addEventListener('change', () => updateMap());
        });

        document.addEventListener('shown.bs.tab', () => map.invalidateSize());
        new ResizeObserver(() => map.invalidateSize()).observe(mapElement);
        window.setTimeout(() => {
            map.invalidateSize();
            updateMap({fit: Boolean(initialCoordinates)});
        }, 100);
    }

    function initializeAll() {
        document.querySelectorAll('input[name$="[travelRadiusKm]"]').forEach(initialize);
    }

    document.addEventListener('DOMContentLoaded', initializeAll);
    document.addEventListener('turbo:load', initializeAll);
})();
