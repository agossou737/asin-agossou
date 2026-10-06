/** Page « Suivre ma demande » (publique, consultation seule) : par NPI ou par numero de demande. */
(function ($, Swal, D) {
    'use strict';

    const FORMAT_NPI = /^\d{10}$/;
    const FORMAT_NUMERO = /^DEM-\d{8}-[A-HJ-NP-Z2-9]{6}$/;

    const $recherche = $('#recherche');
    const $statut = $('#filtre-statut');
    const $liste = $('#liste-demandes');

    const etat = { mode: null, valeur: null, statut: '', page: 1 };

    $recherche.on('input', function () {
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9-]/g, '').slice(0, 20);
    });

    function ligneVide(icone, message) {
        $liste.html(
            '<tr><td colspan="6" class="text-center text-muted py-5"><i class="ti ti-' + icone + ' fs-32 d-block mb-2"></i>' +
            D.echapper(message) + '</td></tr>'
        );
    }

    function boutonPdf(d) {
        if (d.statut.code !== 'validee') {
            return '<span class="text-muted">—</span>';
        }
        return '<button type="button" class="btn btn-sm btn-success btn-action" data-pdf="' + D.echapper(d.numero) + '">' +
            '<i class="ti ti-file-download me-1"></i>Télécharger</button>';
    }

    function afficherLignes(demandes) {
        if (!demandes.length) {
            ligneVide('inbox', 'Aucune demande trouvée pour ce NPI' + (etat.statut ? ' avec ce statut.' : '.'));
            return;
        }

        $liste.html(demandes.map((d) => {
            const motif = d.motif_rejet
                ? '<div class="motif-rejet text-danger fs-12 mt-1"><i class="ti ti-message-exclamation me-1"></i>' + D.echapper(d.motif_rejet) + '</div>'
                : '';

            return '<tr>' +
                '<td class="fw-semibold">' + D.echapper(d.numero) + '</td>' +
                '<td>' + D.echapper(d.type_acte.libelle) + '</td>' +
                '<td class="text-center">' + D.echapper(d.nombre_copies) + '</td>' +
                '<td>' + D.badgeStatut(d.statut) + motif + '</td>' +
                '<td>' + D.echapper(D.formaterDate(d.created_at)) + '</td>' +
                '<td class="text-end">' + boutonPdf(d) + '</td>' +
                '</tr>';
        }).join(''));
    }

    function afficherCompteurs(compteurs) {
        if (!compteurs) {
            $('#compteurs').addClass('d-none');
            return;
        }
        let total = 0;
        Object.entries(compteurs).forEach(([code, n]) => {
            $('#cpt-' + code).text(n);
            total += n;
        });
        $('#cpt-total').text(total);
        $('#compteurs').removeClass('d-none');
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
        for (let i = 1; i <= meta.last_page; i++) {
            html += page(i, i, false, i === meta.current_page);
        }
        html += page(meta.current_page + 1, '&raquo;', meta.current_page >= meta.last_page, false);

        $('#pagination').html(html);
        $bar.removeClass('d-none').addClass('d-flex');
    }

    function charger() {
        if (!etat.mode) return;

        ligneVide('loader-2', 'Chargement…');

        if (etat.mode === 'numero') {
            // Suivi d'une seule demande.
            afficherCompteurs(null);
            afficherPagination(null);

            D.api('GET', '/suivi/' + encodeURIComponent(etat.valeur))
                .done((reponse) => afficherLignes([reponse.data]))
                .fail((xhr) => {
                    ligneVide('inbox', 'Aucune demande ne correspond à ce numéro.');
                    if (xhr.status === 404) {
                        Swal.fire({ icon: 'info', title: 'Demande introuvable', text: 'Aucune demande ne correspond à ce numéro. Vérifiez-le puis réessayez.', confirmButtonText: 'Compris' });
                    } else {
                        D.erreur(xhr);
                    }
                });
            return;
        }

        const params = { page: etat.page, per_page: 20 };
        if (etat.statut) params.statut = etat.statut;

        D.api('GET', '/usagers/' + etat.valeur + '/demandes', params)
            .done((reponse) => {
                afficherCompteurs(reponse.compteurs);
                afficherLignes(reponse.data);
                afficherPagination(reponse.meta);
            })
            .fail((xhr) => {
                ligneVide('alert-triangle', 'Impossible de charger les demandes.');
                D.erreur(xhr);
            });
    }

    function lancerRecherche() {
        const valeur = $recherche.val().trim().toUpperCase();

        if (FORMAT_NPI.test(valeur)) {
            etat.mode = 'npi';
        } else if (FORMAT_NUMERO.test(valeur)) {
            etat.mode = 'numero';
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Saisie invalide',
                html: '<ul class="swal-liste-erreurs"><li>Saisissez votre NPI (exactement 10 chiffres)</li><li>ou votre numéro de demande (ex. DEM-20261006-ABC123).</li></ul>',
                confirmButtonText: 'Corriger',
            });
            return;
        }

        etat.valeur = valeur;
        etat.statut = etat.mode === 'npi' ? $statut.val() : '';
        etat.page = 1;
        charger();
    }

    $('#form-recherche').on('submit', function (e) {
        e.preventDefault();
        lancerRecherche();
    });

    $statut.on('change', () => {
        if (etat.mode === 'npi') lancerRecherche();
    });

    $('#pagination').on('click', 'a.page-link', function (e) {
        e.preventDefault();
        const n = parseInt($(this).data('page'), 10);
        if ($(this).parent().hasClass('disabled') || !n) return;
        etat.page = n;
        charger();
    });

    $liste.on('click', 'button[data-pdf]', function () {
        const $bouton = $(this).prop('disabled', true);
        D.telechargerPdf($bouton.data('pdf')).finally(() => $bouton.prop('disabled', false));
    });

    // Arrivee depuis la page de depot : ?q=DEM-...
    const q = new URLSearchParams(window.location.search).get('q');
    if (q) {
        $recherche.val(q.toUpperCase());
        lancerRecherche();
    }
})(window.jQuery, window.Swal, window.Demandes);
