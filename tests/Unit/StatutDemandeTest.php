<?php

use App\Enums\StatutDemande;

it('autorise uniquement deposee -> en_cours -> validee|rejetee', function () {
    expect(StatutDemande::Deposee->peutPasserA(StatutDemande::EnCours))->toBeTrue()
        ->and(StatutDemande::EnCours->peutPasserA(StatutDemande::Validee))->toBeTrue()
        ->and(StatutDemande::EnCours->peutPasserA(StatutDemande::Rejetee))->toBeTrue()
        ->and(StatutDemande::Deposee->peutPasserA(StatutDemande::Validee))->toBeFalse()
        ->and(StatutDemande::Deposee->peutPasserA(StatutDemande::Rejetee))->toBeFalse()
        ->and(StatutDemande::EnCours->peutPasserA(StatutDemande::Deposee))->toBeFalse();
});

it('considere validee et rejetee comme des etats finaux', function () {
    expect(StatutDemande::Validee->estFinal())->toBeTrue()
        ->and(StatutDemande::Rejetee->estFinal())->toBeTrue()
        ->and(StatutDemande::Deposee->estFinal())->toBeFalse()
        ->and(StatutDemande::EnCours->estFinal())->toBeFalse();
});
