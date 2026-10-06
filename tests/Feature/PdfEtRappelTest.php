<?php

use App\Enums\StatutDemande;
use App\Mail\DemandeValideeMail;
use App\Mail\RappelDemandesAdminMail;
use App\Models\DemandeActe;
use App\Models\User;
use App\Services\PdfDemandeService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Mail;

// --- PDF -----------------------------------------------------------------------------

it('genere un vrai document PDF pour une demande validee', function () {
    $demande = DemandeActe::factory()->statut(StatutDemande::Validee)->create();

    $contenu = app(PdfDemandeService::class)->generer($demande);

    expect($contenu)->toStartWith('%PDF')->and(strlen($contenu))->toBeGreaterThan(1000);
});

it('permet a l\'usager de telecharger le PDF d\'une demande validee par son numero', function () {
    $demande = DemandeActe::factory()->statut(StatutDemande::Validee)->create();

    $reponse = $this->get('/api/suivi/'.strtolower($demande->numero).'/pdf');

    $reponse->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect($reponse->headers->get('Content-Disposition'))->toContain('demande-'.$demande->numero.'.pdf')
        ->and($reponse->getContent())->toStartWith('%PDF');
});

it('refuse le telechargement tant que la demande n\'est pas validee', function (StatutDemande $statut) {
    $demande = DemandeActe::factory()->statut($statut)->create();

    $this->getJson('/api/suivi/'.$demande->numero.'/pdf')
        ->assertStatus(409)
        ->assertJsonPath('message', "Le document n'est disponible que pour une demande validée.");
})->with([StatutDemande::Deposee, StatutDemande::EnCours, StatutDemande::Rejetee]);

it('renvoie 404 pour un numero inconnu et 422 pour un numero mal forme', function () {
    $this->getJson('/api/suivi/DEM-20260101-ABC234/pdf')->assertNotFound();
    $this->getJson('/api/suivi/abc/pdf')->assertUnprocessable();
});

it('permet a un admin de telecharger le PDF mais protege la route', function () {
    $demande = DemandeActe::factory()->statut(StatutDemande::Validee)->create();

    $this->get('/admin/demandes/'.$demande->id.'/pdf')->assertRedirect('/admin/connexion');

    $this->actingAs(User::factory()->create())->get('/admin/demandes/'.$demande->id.'/pdf')->assertForbidden();

    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin/demandes/'.$demande->id.'/pdf')
        ->assertOk()->assertHeader('Content-Type', 'application/pdf');
});

it('affiche le bouton de telechargement a l\'admin uniquement pour une demande validee', function () {
    $this->actingAs(User::factory()->admin()->create());
    $validee = DemandeActe::factory()->statut(StatutDemande::Validee)->create();
    $enCours = DemandeActe::factory()->statut(StatutDemande::EnCours)->create();

    $this->get('/admin/demandes/'.$validee->id)->assertSee('Télécharger le PDF');
    $this->get('/admin/demandes/'.$enCours->id)->assertDontSee('Télécharger le PDF');
});

it('joint le PDF a l\'email de validation', function () {
    Mail::fake();
    $demande = DemandeActe::factory()->statut(StatutDemande::EnCours)->create(['email' => 'usager@exemple.com']);
    config(['demandes.api_token' => 'jeton']);

    $this->patchJson("/api/demandes/{$demande->id}/statut", ['statut' => 'validee'], ['Authorization' => 'Bearer jeton'])->assertOk();

    Mail::assertSent(DemandeValideeMail::class, function ($mail) use ($demande) {
        $pieces = $mail->attachments();

        return $mail->hasTo('usager@exemple.com') && count($pieces) === 1;
    });
});

it('construit la piece jointe PDF seulement si le PDF existe', function () {
    $demande = DemandeActe::factory()->statut(StatutDemande::Validee)->create();

    expect((new DemandeValideeMail($demande, '%PDF-contenu'))->attachments())->toHaveCount(1)
        ->and((new DemandeValideeMail($demande, null))->attachments())->toHaveCount(0);
});

// --- Rappel admin (scheduler) -------------------------------------------------------------

it('envoie aux administrateurs un rappel des demandes en attente et en cours', function () {
    Mail::fake();
    config(['demandes.admin_email' => 'admin@asin.bj']);
    User::factory()->admin()->create(['email' => 'agent@exemple.com']);
    DemandeActe::factory()->count(2)->create();                                   // en attente
    DemandeActe::factory()->statut(StatutDemande::EnCours)->create();
    DemandeActe::factory()->statut(StatutDemande::Validee)->create();             // ignoree
    DemandeActe::factory()->statut(StatutDemande::Rejetee)->create();             // ignoree

    $this->artisan('demandes:rappel-admin')->assertSuccessful();

    Mail::assertSent(RappelDemandesAdminMail::class, 2);
    Mail::assertSent(RappelDemandesAdminMail::class, fn ($m) => $m->hasTo('agent@exemple.com')
        && $m->compteurs === ['deposee' => 2, 'en_cours' => 1]
        && $m->demandes->count() === 3);
    Mail::assertSent(RappelDemandesAdminMail::class, fn ($m) => $m->hasTo('admin@asin.bj') && str_contains($m->render(), 'DEM-'));
});

it('n\'envoie aucun rappel quand il n\'y a rien a traiter', function () {
    Mail::fake();
    DemandeActe::factory()->statut(StatutDemande::Validee)->create();

    $this->artisan('demandes:rappel-admin')
        ->expectsOutput('Aucune demande à traiter : aucun rappel envoyé.')
        ->assertSuccessful();

    Mail::assertNothingSent();
});

it('signale un echec si aucun email de rappel ne peut partir', function () {
    DemandeActe::factory()->create();
    Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP indisponible'));

    $this->artisan('demandes:rappel-admin')->assertFailed();
});

it('planifie le rappel chaque jour a 8h00 et 16h00', function () {
    $evenements = collect(app(Schedule::class)->events())
        ->filter(fn ($e) => str_contains((string) $e->command, 'demandes:rappel-admin'));

    expect($evenements)->toHaveCount(2)
        ->and($evenements->pluck('expression')->all())->toEqualCanonicalizing(['0 8 * * *', '0 16 * * *'])
        ->and($evenements->pluck('timezone')->unique()->all())->toBe([config('app.timezone')]);

    $this->artisan('schedule:list')->expectsOutputToContain('demandes:rappel-admin')->assertSuccessful();
});

it('limite le nombre de lignes detaillees dans le rappel', function () {
    Mail::fake();
    DemandeActe::factory()->count(55)->create();

    $this->artisan('demandes:rappel-admin')->assertSuccessful();

    Mail::assertSent(RappelDemandesAdminMail::class, fn ($m) => $m->demandes->count() === 50 && $m->reste === 5);
});
