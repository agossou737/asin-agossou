/**
 * Page de details d'une demande : le traitement se poursuit ici jusqu'a la validation ou au rejet.
 * - en_cours  -> on reste sur la page (l'admin peut encore changer le statut) ;
 * - validee / rejetee -> retour a la liste complete.
 */
(function ($, Swal, D) {
    'use strict';

    const BASE = window.APP.adminApiUrl;
    const LISTE = window.APP.urls_admin.demandes;
    const $carte = $('#carte-actions');
    const id = $carte.data('id');

    function appliquer(cible, motif) {
        const corps = { statut: cible };
        if (motif) corps.motif_rejet = motif;

        $carte.find('button').prop('disabled', true);

        return D.api('PATCH', '/demandes/' + encodeURIComponent(id) + '/statut', corps, BASE)
            .done((reponse) => {
                D.memoriserToast(reponse.message);
                const final = ['validee', 'rejetee'].includes(reponse.data.statut.code);
                // Statut final : retour a la liste ; sinon on recharge cette page pour continuer le traitement.
                window.location.href = final ? LISTE : window.location.pathname;
            })
            .fail((xhr) => {
                // L'etat a pu changer entre-temps (autre admin) : on affiche le message puis on rafraichit.
                D.erreur(xhr).then(() => window.location.reload());
            });
    }

    $carte.on('click', 'button[data-action]', function () {
        const cible = $(this).data('action');

        if (cible === 'rejetee') {
            Swal.fire({
                icon: 'warning',
                title: 'Rejeter cette demande',
                text: "Un rejet doit toujours être motivé. Le motif sera communiqué à l'usager.",
                input: 'textarea',
                inputLabel: 'Motif du rejet',
                inputPlaceholder: 'Ex. : pièces justificatives illisibles…',
                inputAttributes: { maxlength: 1000 },
                showCancelButton: true,
                confirmButtonText: 'Rejeter définitivement',
                cancelButtonText: 'Annuler',
                confirmButtonColor: '#dc3545',
                inputValidator: (valeur) => (!valeur || !valeur.trim() ? 'Veuillez saisir le motif du rejet.' : undefined),
            }).then((r) => {
                if (r.isConfirmed) appliquer(cible, r.value.trim());
            });
            return;
        }

        const textes = {
            en_cours: 'La demande passera « en cours de traitement ».',
            validee: "La demande sera validée. Ce statut est définitif et l'usager sera notifié.",
        };

        Swal.fire({
            icon: 'question',
            title: 'Confirmer cette action ?',
            text: textes[cible],
            showCancelButton: true,
            confirmButtonText: 'Confirmer',
            cancelButtonText: 'Annuler',
        }).then((r) => {
            if (r.isConfirmed) appliquer(cible);
        });
    });
})(window.jQuery, window.Swal, window.Demandes);
