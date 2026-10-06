/**
 * Utilitaires communs : appels AJAX vers l'API et notifications SweetAlert2.
 * Expose l'objet global `Demandes`.
 */
(function (window, $, Swal) {
    'use strict';

    const echapper = (valeur) => $('<div>').text(valeur ?? '').html();

    /** Appel JSON vers /api/... (GET : parametres dans l'URL, autres : corps JSON). */
    function api(methode, chemin, donnees, base) {
        const options = {
            url: (base || window.APP.apiUrl) + chemin,
            method: methode,
            dataType: 'json',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '',
            },
        };

        if (methode === 'GET') {
            options.data = donnees || {};
        } else {
            options.contentType = 'application/json';
            options.data = JSON.stringify(donnees || {});
        }

        return $.ajax(options);
    }

    const MESSAGE_GENERIQUE = 'Une erreur technique est survenue. Veuillez réessayer dans un instant.';

    /**
     * Transforme une reponse d'erreur HTTP en { titre, html, erreurs }.
     * Seuls les messages de validation (422) et les messages metier (409, 403) du serveur sont affiches ;
     * pour toute autre erreur, un message generique est montre : jamais de detail technique.
     */
    function decrireErreur(xhr) {
        const json = xhr.responseJSON || {};
        const generique = (titre, texte) => ({ titre, html: echapper(texte || MESSAGE_GENERIQUE), erreurs: {} });

        if (xhr.status === 0) return generique('Serveur injoignable', 'Vérifiez votre connexion puis réessayez.');

        if (xhr.status === 422) {
            const erreurs = json.errors || {};
            const items = Object.values(erreurs).flat().map((m) => '<li>' + echapper(m) + '</li>').join('');
            return {
                titre: 'Données invalides',
                html: items ? '<ul class="swal-liste-erreurs">' + items + '</ul>' : echapper('Veuillez vérifier les informations saisies.'),
                erreurs,
            };
        }

        if (xhr.status === 409) return generique('Action impossible', typeof json.message === 'string' ? json.message : 'Cette action est impossible.');
        if (xhr.status === 404) return generique('Introuvable', 'La ressource demandée est introuvable.');
        if (xhr.status === 401) return generique('Session expirée', 'Veuillez vous reconnecter.');
        if (xhr.status === 403) return generique('Accès refusé', "Vous n'avez pas le droit d'effectuer cette action.");
        if (xhr.status === 419) return generique('Session expirée', 'Veuillez recharger la page puis réessayer.');
        if (xhr.status === 429) {
            const attente = parseInt(xhr.getResponseHeader('Retry-After'), 10);
            return generique('Trop de tentatives', 'Veuillez patienter' + (attente > 0 ? ' ' + attente + ' seconde(s)' : ' quelques instants') + ' avant de réessayer.');
        }

        return generique('Une erreur est survenue');
    }

    function erreur(xhr) {
        const e = decrireErreur(xhr);
        const redirection = window.APP.admin && (xhr.status === 401 || xhr.status === 419);

        return Swal.fire({ icon: xhr.status === 409 ? 'warning' : 'error', title: e.titre, html: e.html, confirmButtonText: 'Compris' })
            .then((r) => {
                if (redirection) window.location.href = window.APP.urls_admin.login;
                return r;
            });
    }

    function succes(titre, message, options) {
        return Swal.fire(Object.assign({ icon: 'success', title: titre, html: message, confirmButtonText: 'OK' }, options || {}));
    }

    function toast(message) {
        return Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: message, showConfirmButton: false, timer: 2500, timerProgressBar: true });
    }

    function badgeStatut(statut) {
        return '<span class="badge text-bg-' + echapper(statut.couleur) + ' ms-0 px-2 py-1">' + echapper(statut.libelle) + '</span>';
    }

    function formaterDate(iso) {
        if (!iso) return '';
        return new Date(iso).toLocaleString('fr-FR', { dateStyle: 'medium', timeStyle: 'short' });
    }

    /** Telecharge le PDF d'une demande validee ; les erreurs sont affichees avec SweetAlert (jamais de detail technique). */
    function telechargerPdf(numero) {
        return window.fetch(window.APP.apiUrl + '/suivi/' + encodeURIComponent(numero) + '/pdf', { headers: { Accept: 'application/pdf, application/json' } })
            .then(async (reponse) => {
                if (!reponse.ok) {
                    let json = {};
                    try { json = await reponse.json(); } catch (e) { /* corps non JSON */ }
                    throw { status: reponse.status, responseJSON: json, getResponseHeader: (n) => reponse.headers.get(n) };
                }

                const blob = await reponse.blob();
                const lien = document.createElement('a');
                lien.href = window.URL.createObjectURL(blob);
                lien.download = 'demande-' + numero + '.pdf';
                document.body.appendChild(lien);
                lien.click();
                lien.remove();
                window.setTimeout(() => window.URL.revokeObjectURL(lien.href), 2000);
                toast('Téléchargement du PDF lancé.');
            })
            .catch((e) => erreur(e && e.status !== undefined ? e : { status: 0, responseJSON: {}, getResponseHeader: () => null }));
    }

    /** Message a afficher apres une redirection (ex. admin : « statut mis a jour »). */
    function memoriserToast(message) {
        try { window.sessionStorage.setItem('flash', message); } catch (e) { /* stockage indisponible : on ignore */ }
    }

    $(function () {
        try {
            const message = window.sessionStorage.getItem('flash');
            if (message) {
                window.sessionStorage.removeItem('flash');
                toast(message);
            }
        } catch (e) { /* ignore */ }
    });

    window.Demandes = { telechargerPdf, memoriserToast, api, erreur, succes, toast, echapper, decrireErreur, badgeStatut, formaterDate };
})(window, window.jQuery, window.Swal);
