<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\PasswordReset;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;

class PasswordResetWebController extends Controller
{
    public function showResetForm(Request $request, $token)
    {
        $email = $request->email;

        $tokenValid = \DB::table('password_resets')
            ->where('email', $email)
            ->where('token', Hash::make($token))
            ->where('created_at', '>', now()->subHours(config('auth.passwords.users.expire', 1)))
            ->exists();

        if (!$tokenValid) {
            return view('auth.passwords.expired');
        }

        return view('auth.passwords.reset', ['token' => $token, 'email' => $email]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return view('auth.passwords.success');
        } else {
            return back()->withErrors(['email' => [__($status)]]);
        }
    }
}
