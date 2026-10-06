/** Connexion administrateur : envoi AJAX, erreurs et succes via SweetAlert2. */
(function ($, Swal, D) {
    'use strict';

    const $form = $('#form-login');
    const $btn = $('#btn-login');

    function marquerErreurs(erreurs) {
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('[data-error-for]').text('');
        Object.entries(erreurs || {}).forEach(([champ, messages]) => {
            $form.find('[name="' + champ + '"]').addClass('is-invalid');
            $form.find('[data-error-for="' + champ + '"]').text(messages[0]);
        });
    }

    $form.on('submit', function (e) {
        e.preventDefault();

        const donnees = { email: $('#email').val().trim(), password: $('#password').val() };
        const erreurs = {};
        if (!donnees.email) erreurs.email = ["L'adresse email est obligatoire."];
        if (!donnees.password) erreurs.password = ['Le mot de passe est obligatoire.'];
        marquerErreurs(erreurs);
        if (Object.keys(erreurs).length) return;

        $btn.prop('disabled', true);

        D.api('POST', '/connexion', donnees, window.APP.adminApiUrl.replace(/\/api$/, ''))
            .done((reponse) => {
                Swal.fire({ icon: 'success', title: 'Connexion réussie', text: 'Redirection en cours…', timer: 1200, showConfirmButton: false })
                    .then(() => { window.location.href = reponse.redirect; });
            })
            .fail((xhr) => {
                marquerErreurs(D.decrireErreur(xhr).erreurs);
                $('#password').val('');
                D.erreur(xhr);
            })
            .always(() => $btn.prop('disabled', false));
    });
})(window.jQuery, window.Swal, window.Demandes);
