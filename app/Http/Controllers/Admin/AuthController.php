<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function form(): View
    {
        return view('admin.login');
    }

    public function login(Request $request): JsonResponse
    {
        $identifiants = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ], [
            'email.required' => "L'adresse email est obligatoire.",
            'email.email' => "L'adresse email n'est pas valide.",
            'password.required' => 'Le mot de passe est obligatoire.',
        ]);

        // Seuls les comptes administrateurs peuvent se connecter. Message volontairement unique :
        // on ne revele jamais si l'email existe.
        $ok = Auth::attempt([
            'email' => $identifiants['email'],
            'password' => $identifiants['password'],
            'is_admin' => true,
        ]);

        if (! $ok) {
            throw ValidationException::withMessages(['email' => 'Email ou mot de passe incorrect.']);
        }

        $request->session()->regenerate();

        return response()->json([
            'message' => 'Connexion réussie.',
            'redirect' => route('admin.dashboard'),
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
