<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Google\Client;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Socialite\Facades\Socialite;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/login', [
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request)
    {
        return response()->json([
            'message' => "Login request received",
        ]);

    }
    public function googleLogin(Request $request)
    {
        return Socialite::driver('google')->scopes(['email', 'profile'])->redirect();
    }
    public function googleCallback(Request $request): JsonResponse
    {

        $user = Socialite::driver('google')->stateless()->user();

        $token = $user->token;
        $refreshToken = $user->refreshToken;
        $expiresIn = $user->expiresIn;


        // All providers...
        $user->getId();
        $user->getNickname();
        $user->getName();
        $user->getEmail();
        $user->getAvatar();
        return response()->json([
            'message' => 'ok',
            'user' => $user,
            'token' => $token,
            'refreshToken' => $refreshToken,
            'expiresIn' => $expiresIn,
        ]);
        // dd($user, $token, $refreshToken, $expiresIn);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
