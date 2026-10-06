<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DemandeActe;
use Illuminate\View\View;

/** Page « details d'une demande » de l'espace admin. */
class DemandePageController extends Controller
{
    public function __invoke(string $id): View
    {
        $demande = DemandeActe::query()->with('historiques.acteur')->findOrFail($id);

        return view('admin.demande', ['demande' => $demande]);
    }
}
