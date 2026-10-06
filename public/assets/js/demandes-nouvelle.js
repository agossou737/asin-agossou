/** Page « Deposer une demande » : validation cote client puis envoi AJAX. */
(function ($, Swal, D) {
    'use strict';

    const $form = $('#form-demande');
    const $btn = $('#btn-deposer');

    function afficherErreursChamps(erreurs) {
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('[data-error-for]').text('');

        Object.entries(erreurs).forEach(([champ, messages]) => {
            $form.find('[name="' + champ + '"]').addClass('is-invalid');
            $form.find('[data-error-for="' + champ + '"]').text(messages[0]);
        });
    }

    /** Memes regles que le serveur (le serveur reste l'autorite). */
    function validerLocalement(d) {
        const erreurs = {};
        if (!/^\d{10}$/.test(d.npi)) erreurs.npi = ['Le NPI doit comporter exactement 10 chiffres.'];
        if (d.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(d.email)) erreurs.email = ["L'adresse email n'est pas valide."];
        if (!d.type_acte) erreurs.type_acte = ["Veuillez choisir un type d'acte."];
        if (!(d.nombre_copies >= 1 && d.nombre_copies <= 5)) erreurs.nombre_copies = ['Le nombre de copies doit être compris entre 1 et 5.'];
        return erreurs;
    }

    // Saisie du NPI : chiffres uniquement.
    $('#npi').on('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 10);
    });

    $form.on('reset', () => afficherErreursChamps({}));

    $form.on('submit', function (e) {
        e.preventDefault();

        const donnees = {
            npi: $('#npi').val().trim(),
            email: $('#email').val().trim() || null,
            type_acte: $('#type_acte').val(),
            nombre_copies: parseInt($('#nombre_copies').val(), 10),
        };

        const erreursLocales = validerLocalement(donnees);
        afficherErreursChamps(erreursLocales);

        if (Object.keys(erreursLocales).length) {
            const items = Object.values(erreursLocales).map((m) => '<li>' + D.echapper(m[0]) + '</li>').join('');
            Swal.fire({ icon: 'error', title: 'Données invalides', html: '<ul class="swal-liste-erreurs">' + items + '</ul>', confirmButtonText: 'Corriger' });
            return;
        }

        $btn.prop('disabled', true);

        D.api('POST', '/demandes', donnees)
            .done((reponse) => {
                const demande = reponse.data;
                const html =
                    '<p class="mb-1">Votre numéro de suivi :</p>' +
                    '<p class="fs-22 fw-bold text-primary mb-2" style="letter-spacing:1px;user-select:all;">' + D.echapper(demande.numero) + '</p>' +
                    '<p class="mb-1">' + D.echapper(demande.type_acte.libelle) + ' &times; ' + D.echapper(demande.nombre_copies) + ' copie(s)</p>' +
                    '<p class="mb-2">Statut : ' + D.badgeStatut(demande.statut) + '</p>' +
                    '<p class="mb-0 text-muted fs-13">' + (donnees.email
                        ? 'Ce numéro a été envoyé à ' + D.echapper(donnees.email) + '.'
                        : '<strong>Notez ce numéro</strong> : vous pourrez suivre votre demande avec lui ou avec votre NPI.') + '</p>';

                $form[0].reset();
                afficherErreursChamps({});

                D.succes(reponse.message, html, {
                    showCancelButton: true,
                    confirmButtonText: 'Suivre mes demandes',
                    cancelButtonText: 'Nouvelle demande',
                }).then((r) => {
                    if (r.isConfirmed) {
                        window.location.href = window.APP.urls.liste + '?q=' + encodeURIComponent(demande.numero);
                    }
                });
            })
            .fail((xhr) => {
                const e = D.decrireErreur(xhr);
                afficherErreursChamps(e.erreurs);
                D.erreur(xhr);
            })
            .always(() => $btn.prop('disabled', false));
    });
})(window.jQuery, window.Swal, window.Demandes);
