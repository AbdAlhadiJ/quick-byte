<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Lunaweb\RecaptchaV3\Facades\RecaptchaV3;

class LoginController extends Controller
{
    public function index(Request $request)
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $throttleKey = strtolower($request->input('email')).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => __('Too many login attempts. Please try again in :seconds seconds.', [
                    'seconds' => RateLimiter::availableIn($throttleKey)
                ]),
            ]);
        }

        $credentials = $request->validate([
            'email'                => ['required', 'string', 'email'],
            'password'             => ['required', 'string'],
            'g-recaptcha-response' => ['required', 'string'],
        ]);

        $score = RecaptchaV3::verify(
            $credentials['g-recaptcha-response'],
            'login'
        );

        if ($score < 0.5) {
            RateLimiter::hit($throttleKey);
            throw ValidationException::withMessages([
                'captcha' => __('Suspicious activity detected. Please try again.'),
            ]);
        }

        if (! Auth::attempt(
            ['email' => $credentials['email'], 'password' => $credentials['password']],
            $request->boolean('remember')
        )) {
            RateLimiter::hit($throttleKey);
            throw ValidationException::withMessages([
                'email' => __('The provided credentials do not match our records.'),
            ]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        return redirect()->intended('/dashboard');

    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        // Log the logout event
        Log::info('User logged out', [
            'user_id'  => Auth::id(),
            'email'    => Auth::user()?->email,
            'ip'       => $request->ip(),
            'userAgent'=> $request->userAgent(),
        ]);

        Auth::logout();

        // Invalidate the session and regenerate CSRF token
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redirect to login page or homepage
        return redirect('/login');
    }
}
