<?php

use App\Enums\StatutDemande;
use App\Models\DemandeActe;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

function admin(): User
{
    return User::factory()->admin()->create();
}

// --- Acces ----------------------------------------------------------------------

it('redirige un visiteur non connecte vers la page de connexion admin', function () {
    $this->get('/admin')->assertRedirect('/admin/connexion');
    $this->get('/admin/demandes')->assertRedirect('/admin/connexion');
});

it('refuse (401) l\'API admin sans connexion', function () {
    $this->getJson('/admin/api/demandes')->assertUnauthorized();
    $this->patchJson('/admin/api/demandes/'.Str::uuid().'/statut', ['statut' => 'en_cours'])->assertUnauthorized();
});

it('refuse (403) un utilisateur connecte qui n\'est pas administrateur', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/admin')->assertForbidden();
    $this->getJson('/admin/api/demandes')->assertForbidden();
});

it('donne acces a un administrateur', function () {
    $this->actingAs(admin());

    $this->get('/admin')->assertOk()->assertSee('Tableau de bord');
    $this->get('/admin/demandes')->assertOk();
    $this->getJson('/admin/api/demandes')->assertOk();
});

// --- Connexion ------------------------------------------------------------------

it('connecte un administrateur avec les bons identifiants', function () {
    $user = User::factory()->admin()->create(['password' => 'MotDePasse#123']);

    $this->postJson('/admin/connexion', ['email' => $user->email, 'password' => 'MotDePasse#123'])
        ->assertOk()
        ->assertJsonPath('redirect', route('admin.dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('refuse un mauvais mot de passe avec un message unique', function () {
    $user = User::factory()->admin()->create(['password' => 'MotDePasse#123']);

    $this->postJson('/admin/connexion', ['email' => $user->email, 'password' => 'mauvais'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.email.0', 'Email ou mot de passe incorrect.');

    $this->postJson('/admin/connexion', ['email' => 'inconnu@exemple.com', 'password' => 'x'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.email.0', 'Email ou mot de passe incorrect.');

    $this->assertGuest();
});

it('refuse la connexion d\'un compte non administrateur', function () {
    $user = User::factory()->create(['password' => 'MotDePasse#123']);

    $this->postJson('/admin/connexion', ['email' => $user->email, 'password' => 'MotDePasse#123'])->assertUnprocessable();
    $this->assertGuest();
});

it('bloque apres 5 tentatives de connexion en une minute (429)', function () {
    foreach (range(1, 5) as $i) {
        $this->postJson('/admin/connexion', ['email' => 'a@b.com', 'password' => 'x'])->assertUnprocessable();
    }

    $this->postJson('/admin/connexion', ['email' => 'a@b.com', 'password' => 'x'])
        ->assertStatus(429)
        ->assertJsonStructure(['message']);
});

it('deconnecte l\'administrateur', function () {
    $this->actingAs(admin())->post('/admin/deconnexion')->assertRedirect('/admin/connexion');
    $this->assertGuest();
});

// --- Traitement (admin) + historique ----------------------------------------------------

it('permet a un admin de traiter une demande et journalise qui a fait quoi', function () {
    $admin = admin();
    $demande = DemandeActe::factory()->create();
    $this->actingAs($admin);

    $this->patchJson("/admin/api/demandes/{$demande->id}/statut", ['statut' => 'en_cours'])->assertOk();
    $this->patchJson("/admin/api/demandes/{$demande->id}/statut", ['statut' => 'validee'])->assertOk();

    $this->getJson("/admin/api/demandes/{$demande->id}")
        ->assertOk()
        ->assertJsonCount(2, 'data.historique')
        ->assertJsonPath('data.historique.1.nouveau_statut', 'Validée')
        ->assertJsonPath('data.historique.1.acteur', $admin->name);
});

it('filtre la liste admin par statut, type et debut de NPI', function () {
    DemandeActe::factory()->pourNpi('0123456789')->statut(StatutDemande::Validee)->create(['type_acte' => 'acte_naissance']);
    DemandeActe::factory()->pourNpi('0123999999')->create(['type_acte' => 'casier_judiciaire']);
    DemandeActe::factory()->pourNpi('5555555555')->create(['type_acte' => 'acte_naissance']);
    $this->actingAs(admin());

    $this->getJson('/admin/api/demandes?npi=0123')->assertOk()->assertJsonCount(2, 'data');
    $this->getJson('/admin/api/demandes?statut=validee')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson('/admin/api/demandes?type_acte=acte_naissance')->assertOk()->assertJsonCount(2, 'data');
    $this->getJson('/admin/api/demandes?npi=abc')->assertUnprocessable();
});

// --- API de traitement : jeton Bearer ---------------------------------------------------

it('refuse l\'API de traitement sans jeton ou avec un mauvais jeton', function () {
    config(['demandes.api_token' => 'bon-jeton']);
    $demande = DemandeActe::factory()->create();

    $this->patchJson("/api/demandes/{$demande->id}/statut", ['statut' => 'en_cours'])->assertUnauthorized();
    $this->patchJson("/api/demandes/{$demande->id}/statut", ['statut' => 'en_cours'], ['Authorization' => 'Bearer faux'])->assertUnauthorized();
    $this->getJson("/api/demandes/{$demande->id}")->assertUnauthorized();

    expect($demande->fresh()->statut)->toBe(StatutDemande::Deposee);
});

it('desactive l\'API de traitement si aucun jeton n\'est configure', function () {
    config(['demandes.api_token' => '']);
    $demande = DemandeActe::factory()->create();

    $this->patchJson("/api/demandes/{$demande->id}/statut", ['statut' => 'en_cours'], ['Authorization' => 'Bearer '])->assertUnauthorized();
});

// --- Confidentialite --------------------------------------------------------------------

it('masque l\'email dans les reponses publiques', function () {
    DemandeActe::factory()->pourNpi('0123456789')->create(['email' => 'usager@exemple.com']);

    $this->getJson('/api/usagers/0123456789/demandes')
        ->assertOk()
        ->assertJsonPath('data.0.email', 'u***@exemple.com');

    $this->postJson('/api/demandes', ['npi' => '9876543210', 'email' => 'secret@exemple.com', 'type_acte' => 'acte_naissance', 'nombre_copies' => 1])
        ->assertCreated()
        ->assertJsonPath('data.email', 's***@exemple.com');
});

it('envoie les en-tetes de securite', function () {
    $this->get('/demandes/nouvelle')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Content-Security-Policy');
});

it('limite le debit du depot public (429)', function () {
    foreach (range(1, 10) as $i) {
        $this->postJson('/api/demandes', ['npi' => 'x'])->assertUnprocessable();
    }

    $this->postJson('/api/demandes', ['npi' => 'x'])->assertStatus(429);
});

// --- Jamais d'erreur technique cote utilisateur -----------------------------------------------

it('ne divulgue jamais une erreur SQL a l\'utilisateur', function () {
    config(['app.debug' => true]); // meme en mode debug
    Schema::dropIfExists('demande_historiques');
    Schema::dropIfExists('demandes_actes');

    $reponse = $this->postJson('/api/demandes', ['npi' => '0123456789', 'email' => 'a@b.com', 'type_acte' => 'acte_naissance', 'nombre_copies' => 1]);

    $reponse->assertStatus(500)->assertExactJson(['message' => 'Une erreur technique est survenue. Veuillez réessayer dans un instant.']);
    expect($reponse->getContent())->not->toContain('SQLSTATE')->not->toContain('demandes_actes');

    $liste = $this->getJson('/api/usagers/0123456789/demandes');
    $liste->assertStatus(500);
    expect($liste->getContent())->not->toContain('SQLSTATE');
});

it('renvoie un message propre pour une route inconnue ou une methode interdite', function () {
    $this->getJson('/api/inexistant')->assertNotFound()->assertExactJson(['message' => 'Ressource introuvable.']);
    $this->deleteJson('/api/demandes')->assertStatus(405)->assertExactJson(['message' => 'Méthode non autorisée.']);
});


// --- Interface admin : jamais visible publiquement --------------------------------------

it('ne montre aucun lien ni reference a l\'espace admin sur les pages publiques', function (string $url) {
    $html = $this->get($url)->assertOk()->getContent();

    expect(strtolower($html))->not->toContain('/admin')->not->toContain('administrateur')->not->toContain('connexion');
})->with(['/demandes/nouvelle', '/demandes']);

it('affiche les boutons d\'acces par statut dans l\'espace admin', function () {
    $this->actingAs(admin());

    $this->get('/admin/demandes')->assertOk()->assertSee('data-statut="en_cours"', false)->assertSee('data-statut="validee"', false);
    $this->get('/admin')->assertOk()->assertSee('statut=rejetee', false);
});

it('filtre la liste admin par numero de demande', function () {
    $cible = DemandeActe::factory()->create();
    DemandeActe::factory()->count(2)->create();
    $this->actingAs(admin());

    $this->getJson('/admin/api/demandes?numero='.$cible->numero)->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $cible->id);
});

// --- Page de details --------------------------------------------------------------------

it('affiche la page de details avec les actions possibles selon le statut', function () {
    $this->actingAs(admin());

    $enCours = DemandeActe::factory()->statut(StatutDemande::EnCours)->create();
    $this->get('/admin/demandes/'.$enCours->id)
        ->assertOk()->assertSee($enCours->numero)
        ->assertSee('data-action="validee"', false)->assertSee('data-action="rejetee"', false)
        ->assertDontSee('data-action="en_cours"', false);

    $deposee = DemandeActe::factory()->create();
    $this->get('/admin/demandes/'.$deposee->id)->assertSee('data-action="en_cours"', false)->assertDontSee('data-action="validee"', false);

    $validee = DemandeActe::factory()->statut(StatutDemande::Validee)->create();
    $this->get('/admin/demandes/'.$validee->id)->assertOk()->assertSee('Traitement terminé')->assertDontSee('data-action=', false);
});

it('protege la page de details et renvoie 404 pour une demande inconnue', function () {
    $demande = DemandeActe::factory()->create();

    $this->get('/admin/demandes/'.$demande->id)->assertRedirect('/admin/connexion');

    $this->actingAs(admin());
    $this->get('/admin/demandes/'.Str::uuid())->assertNotFound();
    $this->get('/admin/demandes/pas-un-uuid')->assertNotFound();
});

it('utilise des UUID pour tous les identifiants', function () {
    $user = admin();
    $demande = DemandeActe::factory()->create();
    $this->actingAs($user)->patchJson("/admin/api/demandes/{$demande->id}/statut", ['statut' => 'en_cours'])->assertOk();

    expect(Str::isUuid($user->id))->toBeTrue()
        ->and(Str::isUuid($demande->id))->toBeTrue()
        ->and(Str::isUuid($demande->historiques()->latest('id')->first()->id))->toBeTrue();
});
