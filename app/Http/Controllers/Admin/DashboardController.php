<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DemandeService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(DemandeService $demandes): View
    {
        return view('admin.dashboard', ['stats' => $demandes->statistiques()]);
    }
}
