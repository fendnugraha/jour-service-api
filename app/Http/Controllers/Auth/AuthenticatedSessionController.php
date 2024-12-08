<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): Response
    {
        // Lakukan autentikasi dengan login request
        $request->authenticate();

        // Regenerasi session ID dan CSRF token
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        // Ambil data pengguna setelah login
        $user = auth()->user();

        // Membuat token menggunakan Laravel Sanctum
        $token = $user->createToken('Jour-api')->plainTextToken;

        // Kembalikan respons dalam bentuk Response (bukan JSON)
        return response([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token // Token untuk autentikasi API
        ], 200); // Mengembalikan status code 200
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): Response
    {
        Auth::user()->tokens->each(function ($token) {
            $token->delete();  // Menghapus token yang ada
        });

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // // Hapus cookie XSRF-TOKEN dan laravel_session

        return response()->noContent()
            ->withCookie(cookie()->forget('XSRF-TOKEN', '/', env('APP_URL'), true))
            ->withCookie(cookie()->forget('jourapps_session', '/', env('APP_URL'), true));

        return response([
            'message' => 'Logout successful'
        ]);
    }
}
