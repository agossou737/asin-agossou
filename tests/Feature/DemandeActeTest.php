<?php

use App\Enums\StatutDemande;
use App\Mail\DemandeRecueMail;
use App\Mail\DemandeRejeteeMail;
use App\Mail\DemandeValideeMail;
use App\Mail\NouvelleDemandeAdminMail;
use App\Models\DemandeActe;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

const NPI = '0123456789';

const TOKEN = 'jeton-de-test';

function entetes(): array
{
    config(['demandes.api_token' => TOKEN]);

    return ['Authorization' => 'Bearer '.TOKEN];
}

function payload(array $surcharge = []): array
{
    return array_merge(['npi' => NPI, 'email' => 'usager@exemple.com', 'type_acte' => 'acte_naissance', 'nombre_copies' => 2], $surcharge);
}

// --- 1. Deposer une demande ---------------------------------------------------

it('depose une demande valide avec le statut deposee et un identifiant', function () {
    $this->postJson('/api/demandes', payload())
        ->assertCreated()
        ->assertJsonPath('data.statut.code', 'deposee')
        ->assertJsonPath('data.npi', NPI)
        ->assertJsonPath('data.nombre_copies', 2)
        ->assertJsonStructure(['data' => ['id', 'numero'], 'message']);

    expect(Str::isUuid($this->getJson('/api/usagers/'.NPI.'/demandes')->json('data.0.id')))->toBeTrue();

    expect(DemandeActe::count())->toBe(1);
});

it('accepte les trois types d\'acte', function (string $type) {
    $this->postJson('/api/demandes', payload(['type_acte' => $type]))->assertCreated();
})->with(['acte_naissance', 'casier_judiciaire', 'certificat_residence']);

it('refuse un NPI qui n\'a pas exactement 10 chiffres', function (mixed $npi) {
    $this->postJson('/api/demandes', payload(['npi' => $npi]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('npi');
})->with(['123456789', '12345678901', '01234abcde', '', '0123 56789']);

it('refuse un type d\'acte inconnu ou absent', function () {
    $this->postJson('/api/demandes', payload(['type_acte' => 'permis_conduire']))
        ->assertUnprocessable()->assertJsonValidationErrors('type_acte');

    $this->postJson('/api/demandes', ['npi' => NPI, 'email' => 'a@b.com', 'nombre_copies' => 1])
        ->assertUnprocessable()->assertJsonValidationErrors('type_acte');
});

it('refuse un nombre de copies hors de 1 a 5', function (mixed $copies) {
    $this->postJson('/api/demandes', payload(['nombre_copies' => $copies]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('nombre_copies');
})->with([0, 6, -1, 'abc', 2.5]);

it('accepte les bornes 1 et 5 copies', function (int $copies) {
    $this->postJson('/api/demandes', payload(['nombre_copies' => $copies]))->assertCreated();
})->with([1, 5]);

it('renvoie des messages d\'erreur clairs en francais', function () {
    $this->postJson('/api/demandes', payload(['npi' => '123']))
        ->assertUnprocessable()
        ->assertJsonPath('errors.npi.0', 'Le NPI doit comporter exactement 10 chiffres.');
});

it('refuse un email invalide', function (string $email) {
    $this->postJson('/api/demandes', payload(['email' => $email]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');
})->with(['pas-un-email', 'a@']);

it('accepte une demande sans email (facultatif)', function (mixed $email) {
    Mail::fake();

    $this->postJson('/api/demandes', payload(['email' => $email]))
        ->assertCreated()
        ->assertJsonPath('data.email', null);

    Mail::assertNotSent(DemandeRecueMail::class);
    Mail::assertSent(NouvelleDemandeAdminMail::class); // l'administrateur est quand meme alerte
})->with([null, '']);

// --- Numero de demande et suivi --------------------------------------------------------

it('genere un numero de demande unique au bon format', function () {
    $a = $this->postJson('/api/demandes', payload())->assertCreated()->json('data.numero');
    $b = $this->postJson('/api/demandes', payload(['type_acte' => 'casier_judiciaire']))->assertCreated()->json('data.numero');

    expect($a)->toMatch(DemandeActe::FORMAT_NUMERO)->and($b)->toMatch(DemandeActe::FORMAT_NUMERO)->and($a)->not->toBe($b);
});

it('envoie le numero de demande par email a l\'usager', function () {
    Mail::fake();

    $numero = $this->postJson('/api/demandes', payload())->assertCreated()->json('data.numero');

    Mail::assertSent(DemandeRecueMail::class, fn ($m) => $m->demande->numero === $numero && str_contains($m->render(), $numero));
});

it('permet de suivre une demande par son numero (insensible a la casse)', function () {
    $numero = $this->postJson('/api/demandes', payload())->json('data.numero');

    $this->getJson('/api/suivi/'.$numero)->assertOk()->assertJsonPath('data.numero', $numero)->assertJsonPath('data.statut.code', 'deposee');
    $this->getJson('/api/suivi/'.strtolower($numero))->assertOk()->assertJsonPath('data.numero', $numero);
});

it('refuse un numero mal forme (422) et signale un numero inconnu (404)', function () {
    $this->getJson('/api/suivi/abc')->assertUnprocessable()->assertJsonValidationErrors('numero');
    $this->getJson('/api/suivi/DEM-20260101-ABC234')->assertNotFound();
});

it('permet toujours de suivre ses demandes par NPI', function () {
    $this->postJson('/api/demandes', payload())->assertCreated();

    $this->getJson('/api/usagers/'.NPI.'/demandes')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.npi', NPI);
});

// --- Doublons : une seule demande par NPI et par type d'acte --------------------------

it('refuse une seconde demande du meme type tant que la premiere est deposee, en cours ou validee', function (StatutDemande $statut) {
    DemandeActe::factory()->pourNpi(NPI)->statut($statut)->create(['type_acte' => 'acte_naissance']);

    $this->postJson('/api/demandes', payload(['email' => 'autre@exemple.com', 'nombre_copies' => 1]))
        ->assertStatus(409)
        ->assertJsonPath('message', fn ($m) => str_contains($m, 'acte de naissance') && str_contains($m, 'ce NPI'));

    expect(DemandeActe::count())->toBe(1);
})->with([StatutDemande::Deposee, StatutDemande::EnCours, StatutDemande::Validee]);

it('autorise un autre type d\'acte pour le meme NPI, et le meme type pour un autre NPI', function () {
    $this->postJson('/api/demandes', payload())->assertCreated();
    $this->postJson('/api/demandes', payload(['type_acte' => 'casier_judiciaire']))->assertCreated();
    $this->postJson('/api/demandes', payload(['npi' => '9876543210']))->assertCreated();

    expect(DemandeActe::count())->toBe(3);
});

it('autorise un nouveau depot du meme type apres un rejet', function () {
    $rejetee = DemandeActe::factory()->pourNpi(NPI)->statut(StatutDemande::Rejetee)->create(['type_acte' => 'acte_naissance']);

    $nouvelle = $this->postJson('/api/demandes', payload())
        ->assertCreated()
        ->assertJsonPath('data.statut.code', 'deposee')
        ->json('data');

    expect($nouvelle['id'])->not->toBe($rejetee->id)
        ->and($nouvelle['numero'])->not->toBe($rejetee->numero)
        ->and(DemandeActe::count())->toBe(2);

    // L'ancienne demande rejetee est conservee, et un nouveau doublon est de nouveau refuse.
    $this->postJson('/api/demandes', payload())->assertStatus(409);
});

// --- Emails -----------------------------------------------------------------------

it('envoie un accuse de reception a l\'usager et une alerte a l\'administrateur au depot', function () {
    Mail::fake();
    config(['demandes.admin_email' => 'admin@asin.bj']);

    $this->postJson('/api/demandes', payload())->assertCreated();

    Mail::assertSent(DemandeRecueMail::class, fn ($m) => $m->hasTo('usager@exemple.com'));
    Mail::assertSent(NouvelleDemandeAdminMail::class, fn ($m) => $m->hasTo('admin@asin.bj'));
});

it('n\'envoie aucun email quand le depot est refuse', function () {
    Mail::fake();

    $this->postJson('/api/demandes', payload(['npi' => '123']))->assertUnprocessable();

    Mail::assertNothingSent();
});

it('notifie l\'usager quand sa demande est validee', function () {
    Mail::fake();
    $demande = DemandeActe::factory()->statut(StatutDemande::EnCours)->create(['email' => 'usager@exemple.com']);

    $this->patchJson("/api/demandes/{$demande->id}/statut", ['statut' => 'validee'], entetes())->assertOk();

    Mail::assertSent(DemandeValideeMail::class, fn ($m) => $m->hasTo('usager@exemple.com'));
});

it('notifie l\'usager, avec le motif, quand sa demande est rejetee', function () {
    Mail::fake();
    $demande = DemandeActe::factory()->statut(StatutDemande::EnCours)->create(['email' => 'usager@exemple.com']);

    $this->patchJson("/api/demandes/{$demande->id}/statut", ['statut' => 'rejetee', 'motif_rejet' => 'Pièce illisible'], entetes())->assertOk();

    Mail::assertSent(DemandeRejeteeMail::class, fn ($m) => $m->hasTo('usager@exemple.com') && $m->demande->motif_rejet === 'Pièce illisible');
});

it('n\'envoie pas d\'email pour le passage en cours ni pour une transition refusee', function () {
    Mail::fake();
    $deposee = DemandeActe::factory()->create();

    $this->patchJson("/api/demandes/{$deposee->id}/statut", ['statut' => 'en_cours'], entetes())->assertOk();
    $this->patchJson("/api/demandes/{$deposee->id}/statut", ['statut' => 'validee'], entetes())->assertOk();
    $this->patchJson("/api/demandes/{$deposee->id}/statut", ['statut' => 'validee'], entetes())->assertStatus(409);

    Mail::assertSent(DemandeValideeMail::class, 1);
});

it('enregistre la demande meme si l\'envoi des emails echoue', function () {
    Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP indisponible'));

    $this->postJson('/api/demandes', payload())->assertCreated();

    expect(DemandeActe::count())->toBe(1);
});

// --- 2. Consulter les demandes d'un usager ---------------------------------------

it('liste les demandes d\'un usager de la plus recente a la plus ancienne', function () {
    $ancienne = DemandeActe::factory()->pourNpi(NPI)->create(['created_at' => now()->subDays(2)]);
    $recente = DemandeActe::factory()->pourNpi(NPI)->create(['created_at' => now()]);
    $milieu = DemandeActe::factory()->pourNpi(NPI)->create(['created_at' => now()->subDay()]);
    DemandeActe::factory()->pourNpi('9999999999')->create(); // autre usager

    $this->getJson('/api/usagers/'.NPI.'/demandes')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.id', $recente->id)
        ->assertJsonPath('data.1.id', $milieu->id)
        ->assertJsonPath('data.2.id', $ancienne->id);
});

it('filtre les demandes par statut', function () {
    DemandeActe::factory()->pourNpi(NPI)->count(2)->create();
    DemandeActe::factory()->pourNpi(NPI)->statut(StatutDemande::Validee)->create();

    $this->getJson('/api/usagers/'.NPI.'/demandes?statut=validee')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.statut.code', 'validee');
});

it('refuse un statut de filtre inconnu et un NPI invalide', function () {
    $this->getJson('/api/usagers/'.NPI.'/demandes?statut=nimporte')->assertUnprocessable()->assertJsonValidationErrors('statut');
    $this->getJson('/api/usagers/123/demandes')->assertUnprocessable()->assertJsonValidationErrors('npi');
});

it('retourne une liste vide pour un usager sans demande', function () {
    $this->getJson('/api/usagers/'.NPI.'/demandes')->assertOk()->assertJsonCount(0, 'data');
});

// --- Bonus : pagination et compteurs -------------------------------------------------

it('pagine la liste avec au plus 20 demandes par page', function () {
    DemandeActe::factory()->pourNpi(NPI)->count(25)->create();

    $this->getJson('/api/usagers/'.NPI.'/demandes')
        ->assertOk()
        ->assertJsonCount(20, 'data')
        ->assertJsonPath('meta.total', 25)
        ->assertJsonPath('meta.last_page', 2);

    $this->getJson('/api/usagers/'.NPI.'/demandes?page=2')->assertOk()->assertJsonCount(5, 'data');
    $this->getJson('/api/usagers/'.NPI.'/demandes?per_page=50')->assertUnprocessable()->assertJsonValidationErrors('per_page');
});

it('affiche le nombre de demandes par statut', function () {
    DemandeActe::factory()->pourNpi(NPI)->count(2)->create();
    DemandeActe::factory()->pourNpi(NPI)->statut(StatutDemande::EnCours)->create();
    DemandeActe::factory()->pourNpi(NPI)->statut(StatutDemande::Rejetee)->count(3)->create();
    DemandeActe::factory()->pourNpi('9999999999')->create();

    $this->getJson('/api/usagers/'.NPI.'/demandes')
        ->assertOk()
        ->assertJsonPath('compteurs', ['deposee' => 2, 'en_cours' => 1, 'validee' => 0, 'rejetee' => 3]);
});

// --- 3. Cycle de vie -------------------------------------------------------------

it('fait avancer une demande de deposee a en_cours puis validee', function () {
    $id = $this->postJson('/api/demandes', payload())->json('data.id');

    $this->patchJson("/api/demandes/{$id}/statut", ['statut' => 'en_cours'], entetes())
        ->assertOk()->assertJsonPath('data.statut.code', 'en_cours');

    $this->patchJson("/api/demandes/{$id}/statut", ['statut' => 'validee'], entetes())
        ->assertOk()->assertJsonPath('data.statut.code', 'validee');
});

it('rejette une demande en cours avec un motif', function () {
    $demande = DemandeActe::factory()->statut(StatutDemande::EnCours)->create();

    $this->patchJson("/api/demandes/{$demande->id}/statut", ['statut' => 'rejetee', 'motif_rejet' => 'Pièce illisible'], entetes())
        ->assertOk()
        ->assertJsonPath('data.statut.code', 'rejetee')
        ->assertJsonPath('data.motif_rejet', 'Pièce illisible');
});

it('refuse un rejet sans motif', function (mixed $motif) {
    $demande = DemandeActe::factory()->statut(StatutDemande::EnCours)->create();

    $this->patchJson("/api/demandes/{$demande->id}/statut", ['statut' => 'rejetee', 'motif_rejet' => $motif], entetes())
        ->assertUnprocessable()
        ->assertJsonValidationErrors('motif_rejet');

    expect($demande->fresh()->statut)->toBe(StatutDemande::EnCours);
})->with([null, '', '   ']);

it('refuse de sauter une etape du cycle de vie', function (string $cible) {
    $demande = DemandeActe::factory()->create(); // deposee

    $this->patchJson("/api/demandes/{$demande->id}/statut", ['statut' => $cible, 'motif_rejet' => 'Motif'], entetes())
        ->assertStatus(409)
        ->assertJsonStructure(['message']);

    expect($demande->fresh()->statut)->toBe(StatutDemande::Deposee);
})->with(['validee', 'rejetee']);

it('ne change plus une demande validee ou rejetee', function (StatutDemande $final) {
    $demande = DemandeActe::factory()->statut($final)->create();

    foreach (['en_cours', 'validee'] as $cible) {
        $this->patchJson("/api/demandes/{$demande->id}/statut", ['statut' => $cible], entetes())->assertStatus(409);
    }

    $this->patchJson("/api/demandes/{$demande->id}/statut", ['statut' => 'rejetee', 'motif_rejet' => 'Autre motif'], entetes())->assertStatus(409);

    expect($demande->fresh()->statut)->toBe($final);
})->with([StatutDemande::Validee, StatutDemande::Rejetee]);

it('refuse de revenir au statut deposee ou un statut inconnu', function () {
    $demande = DemandeActe::factory()->statut(StatutDemande::EnCours)->create();

    $this->patchJson("/api/demandes/{$demande->id}/statut", ['statut' => 'deposee'], entetes())->assertUnprocessable()->assertJsonValidationErrors('statut');
    $this->patchJson("/api/demandes/{$demande->id}/statut", ['statut' => 'archivee'], entetes())->assertUnprocessable()->assertJsonValidationErrors('statut');
});

it('renvoie 404 pour une demande inexistante', function () {
    $this->patchJson('/api/demandes/'.Str::uuid().'/statut', ['statut' => 'en_cours'], entetes())->assertNotFound();
    $this->getJson('/api/demandes/'.Str::uuid(), entetes())->assertNotFound();
});

// --- Ecran ---------------------------------------------------------------------------

it('affiche les pages de l\'interface', function () {
    $this->get('/')->assertRedirect('/demandes/nouvelle');
    $this->get('/demandes/nouvelle')->assertOk()->assertSee('Déposer une demande');
    $this->get('/demandes')->assertOk()->assertSee('Suivre ma demande');
});
