/** Espace admin : liste des demandes, acces par statut, filtres, pagination. Le traitement se fait sur la page de details. */
(function ($, Swal, D) {
    'use strict';

    const BASE = window.APP.adminApiUrl;
    const URL_DEMANDES = window.APP.urls_admin.demandes;
    const $liste = $('#liste-demandes');
    const etat = { page: 1, statut: new URLSearchParams(window.location.search).get('statut') || '' };

    function urlDetail(id) {
        return URL_DEMANDES + '/' + encodeURIComponent(id);
    }

    function ligneVide(message) {
        $liste.html('<tr><td colspan="8" class="text-center text-muted py-5">' + D.echapper(message) + '</td></tr>');
    }

    function actionsLigne(d) {
        const code = d.statut.code;
        if (code === 'deposee') {
            return '<button type="button" class="btn btn-sm btn-warning btn-action" data-prendre="' + D.echapper(d.id) + '">' +
                '<i class="ti ti-player-play me-1"></i>Prendre en charge</button>';
        }
        if (code === 'en_cours') {
            return '<a class="btn btn-sm btn-primary btn-action" href="' + D.echapper(urlDetail(d.id)) + '"><i class="ti ti-adjustments me-1"></i>Traiter</a>';
        }
        return '<a class="btn btn-sm btn-outline-secondary btn-action" href="' + D.echapper(urlDetail(d.id)) + '"><i class="ti ti-eye me-1"></i>Détails</a>';
    }

    function afficherLignes(demandes) {
        if (!demandes.length) {
            ligneVide('Aucune demande ne correspond à ces critères.');
            return;
        }

        $liste.html(demandes.map((d) => {
            const motif = d.motif_rejet
                ? '<div class="motif-rejet text-danger fs-12 mt-1"><i class="ti ti-message-exclamation me-1"></i>' + D.echapper(d.motif_rejet) + '</div>'
                : '';

            return '<tr>' +
                '<td class="fw-semibold"><a href="' + D.echapper(urlDetail(d.id)) + '">' + D.echapper(d.numero) + '</a></td>' +
                '<td>' + D.echapper(d.npi) + '</td>' +
                '<td>' + (d.email ? D.echapper(d.email) : '<span class="text-muted">—</span>') + '</td>' +
                '<td>' + D.echapper(d.type_acte.libelle) + '</td>' +
                '<td class="text-center">' + D.echapper(d.nombre_copies) + '</td>' +
                '<td>' + D.badgeStatut(d.statut) + motif + '</td>' +
                '<td>' + D.echapper(D.formaterDate(d.created_at)) + '</td>' +
                '<td class="text-end">' + actionsLigne(d) + '</td>' +
                '</tr>';
        }).join(''));
    }

    function afficherPagination(meta) {
        const $bar = $('#pagination-bar');
        if (!meta || meta.total === 0) {
            $bar.addClass('d-none').removeClass('d-flex');
            return;
        }

        $('#pagination-info').text('Affichage de ' + meta.from + ' à ' + meta.to + ' sur ' + meta.total + ' demande(s)');

        const page = (n, libelle, desactive, actif) =>
            '<li class="page-item' + (desactive ? ' disabled' : '') + (actif ? ' active' : '') + '">' +
            '<a class="page-link" href="#" data-page="' + n + '">' + libelle + '</a></li>';

        let html = page(meta.current_page - 1, '&laquo;', meta.current_page <= 1, false);
        for (let i = 1; i <= meta.last_page; i++) html += page(i, i, false, i === meta.current_page);
        html += page(meta.current_page + 1, '&raquo;', meta.current_page >= meta.last_page, false);

        $('#pagination').html(html);
        $bar.removeClass('d-none').addClass('d-flex');
    }

    /** Met en evidence le bouton du statut courant et ajuste le titre. */
    function marquerBoutonActif() {
        $('#boutons-statut button').each(function () {
            const actif = ($(this).data('statut') || '') === etat.statut;
            $(this).toggleClass('active', actif);
        });
        const $actif = $('#boutons-statut button.active').first();
        $('#titre-liste').text($actif.length ? $actif.clone().children().remove().end().text().trim() : 'Toutes les demandes');
    }

    function afficherCompteurs(compteurs, total) {
        Object.entries(compteurs).forEach(([code, n]) => $('#n-' + code).text(n));
        $('#n-total').text(total);
    }

    function charger() {
        marquerBoutonActif();
        ligneVide('Chargement…');

        const params = { page: etat.page, per_page: 20 };
        const numero = $('#f-numero').val().trim();
        const npi = $('#f-npi').val().trim();
        if (numero) params.numero = numero;
        if (npi) params.npi = npi;
        if (etat.statut) params.statut = etat.statut;
        if ($('#f-type').val()) params.type_acte = $('#f-type').val();

        return D.api('GET', '/demandes', params, BASE)
            .done((reponse) => {
                afficherLignes(reponse.data);
                afficherPagination(reponse.meta);
                afficherCompteurs(reponse.compteurs, reponse.total_general);
            })
            .fail((xhr) => {
                ligneVide('Impossible de charger les demandes.');
                D.erreur(xhr);
            });
    }

    // Boutons d'acces par statut
    $('#boutons-statut').on('click', 'button', function () {
        etat.statut = $(this).data('statut') || '';
        etat.page = 1;
        const url = new URL(window.location.href);
        if (etat.statut) url.searchParams.set('statut', etat.statut); else url.searchParams.delete('statut');
        window.history.replaceState({}, '', url);
        charger();
    });

    $('#f-npi').on('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 10);
    });
    $('#f-numero').on('input', function () {
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9-]/g, '').slice(0, 20);
    });

    $('#form-filtres').on('submit', function (e) {
        e.preventDefault();
        etat.page = 1;
        charger();
    });
    $('#f-type').on('change', () => {
        etat.page = 1;
        charger();
    });

    $('#pagination').on('click', 'a.page-link', function (e) {
        e.preventDefault();
        const n = parseInt($(this).data('page'), 10);
        if ($(this).parent().hasClass('disabled') || !n) return;
        etat.page = n;
        charger();
    });

    /** « Prendre en charge » : deposee -> en_cours, puis redirection vers la page de details. */
    $liste.on('click', 'button[data-prendre]', function () {
        const id = $(this).data('prendre');

        Swal.fire({
            icon: 'question',
            title: 'Prendre en charge cette demande ?',
            text: 'Elle passera « en cours de traitement » et vous serez redirigé vers sa page de détails.',
            showCancelButton: true,
            confirmButtonText: 'Prendre en charge',
            cancelButtonText: 'Annuler',
        }).then((r) => {
            if (!r.isConfirmed) return;

            D.api('PATCH', '/demandes/' + encodeURIComponent(id) + '/statut', { statut: 'en_cours' }, BASE)
                .done((reponse) => {
                    D.memoriserToast(reponse.message);
                    window.location.href = urlDetail(id);
                })
                .fail((xhr) => {
                    D.erreur(xhr).then(charger);
                });
        });
    });

    charger();
})(window.jQuery, window.Swal, window.Demandes);
