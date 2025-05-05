<?php
 
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use App\Models\User; 
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;

class LoginController extends Controller
{

    /**
     * Display a login form.
     *
     * */
    public function showLoginForm(): View
    {
        return view('auth.login'); // Assuming you have a login view in resources/views/auth/login.blade.php
    }
    /**
     * Handle an authentication attempt.
     */
    public function authenticate(Request $request): RedirectResponse
    {
        // Check if there is a database connection
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            return back()->withErrors([
                'error_msg' => "Can't connect to the database. Please try again later.",
            ]);
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if ($user->is_banned) {
            return back()->withErrors([
                'error_msg' => 'You are not allowed to loggin. Please contact the support team.',
            ]);
        }

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();
    
            return redirect()->intended('home');
        }
 
        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Log out the user from application.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')
            ->withSuccess('You have logged out successfully!');
    } 

    public function showResetPassword() 
    {
        return view('auth.reset-password');
    }

    public function showNewPassword($token, $email)
    {
        return view('auth.new-password', compact('token', 'email'));
    }

    public function newPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);
    
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));
    
                $user->save();
    
                event(new PasswordReset($user));
            }
        );
    
        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('success', __('Your password has been successfully reset.'));
        } else {
            return back()->withErrors(['email' => [__($status)]]);
        }
    }
}
