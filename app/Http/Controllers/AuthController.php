<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\vendeurs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Inscription d'un nouvel utilisateur (user normal)
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'post_nom' => 'nullable|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'nom' => $validated['nom'],
            'post_nom' => $validated['post_nom'] ?? null,
            'prenom' => $validated['prenom'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'user', // Par dÃ©faut, rÃ´le user
        ]);

        // CrÃ©er un token Sanctum pour l'utilisateur
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Inscription rÃ©ussie',
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer'
        ], 201);
    }

    /**
     * CrÃ©ation d'un compte admin (Ã  protÃ©ger ou Ã  dÃ©sactiver aprÃ¨s usage)
     */
    public function registerAdmin(Request $request)
    {
        // Option 1: ProtÃ©ger par un mot de passe secret
        $request->validate([
            'secret' => 'required|string|in:' . env('ADMIN_SECRET_KEY', 'admin123'),
        ]);

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'post_nom' => 'nullable|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'nom' => $validated['nom'],
            'post_nom' => $validated['post_nom'] ?? null,
            'prenom' => $validated['prenom'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'admin',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Compte admin crÃ©Ã© avec succÃ¨s',
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer'
        ], 201);
    }

    /**
     * Connexion
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email ou mot de passe incorrect'
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Connexion rÃ©ussie',
            'user' => [
                'id' => $user->id,
                'nom' => $user->nom,
                'prenom' => $user->prenom,
                'email' => $user->email,
                'role' => $user->role
            ],
            'token' => $token,
            'token_type' => 'Bearer'
        ]);
    }

    /**
     * DÃ©connexion
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'DÃ©connexion rÃ©ussie'
        ]);
    }

    /**
     * Inscription d'un vendeur
     */
    public function registerVendeur(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'code' => 'required|string|max:50|unique:vendeurs',
            'email' => 'required|string|email|max:255|unique:vendeurs',
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $vendeur = vendeurs::create([
            'nom' => $validated['nom'],
            'prenom' => $validated['prenom'],
            'code' => $validated['code'],
            'email' => $validated['email'],
            'telephone' => $validated['telephone'] ?? null,
            'adresse' => $validated['adresse'] ?? null,
            'password' => Hash::make($validated['password']),
        ]);

        // CrÃ©er un token Sanctum pour le vendeur
        $token = $vendeur->createToken('vendeur_auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Inscription vendeur rÃ©ussie',
            'vendeur' => $vendeur,
            'token' => $token,
            'token_type' => 'Bearer'
        ], 201);
    }

    /**
     * Connexion d'un vendeur
     */
    public function loginVendeur(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Tentative d'authentification avec le guard vendeur
        if (!Auth::guard('vendeur')->attempt($request->only('email', 'password'))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email ou mot de passe incorrect'
            ], 401);
        }

        $vendeur = vendeurs::where('email', $request->email)->firstOrFail();
        $token = $vendeur->createToken('vendeur_auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Connexion vendeur rÃ©ussie',
            'vendeur' => [
                'id' => $vendeur->id,
                'nom' => $vendeur->nom,
                'prenom' => $vendeur->prenom,
                'code' => $vendeur->code,
                'email' => $vendeur->email,
                'telephone' => $vendeur->telephone,
                'adresse' => $vendeur->adresse,
            ],
            'token' => $token,
            'token_type' => 'Bearer'
        ]);
    }

    /**
     * DÃ©connexion d'un vendeur
     */
    public function logoutVendeur(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'DÃ©connexion vendeur rÃ©ussie'
        ]);
    }
}