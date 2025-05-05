<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use Illuminate\View\View;

use App\Models\User;

class RegisterController extends Controller
{
    /**
     * Display the registration form.
     */
    public function showRegistrationForm(): View
    {
        return view('auth.register');
    }

    /**
     * Register a new user.
     */
    public function register(Request $request)
    {
        // Check if there is a database connection
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            return back()->withErrors([
                'error_msg' => "Can't connect to the database. Please try again later.",
            ]);
        }

        // Validate the input
        $request->validate([
            'name' => 'required|string|max:255',
            'tagname' => 'required|string|max:255|unique:user,tagname',
            'email' => 'required|email|max:255|unique:user,email',
            'password' => 'required|string|min:8|confirmed',
            'age' => 'nullable|integer|min:18',
            'country' => 'nullable|string|max:100',
            'degree' => 'nullable|string|max:255',
            'icon' => 'nullable|string',
            'is_admin' => 'boolean',
            'is_moderator' => 'boolean',
            'is_banned' => 'boolean',
        ]);

        // Create the new user
        $user = User::create([
            'name' => $request->name,
            'tagname' => $request->tagname,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'age' => $request->age,
            'country' => $request->country,
            'degree' => $request->degree,
            'icon' => $request->icon,
            'is_admin' => $request->is_admin ?? false,
            'is_moderator' => $request->is_moderator ?? false,
            'is_banned' => $request->is_banned ?? false,
        ]);

        // Log the user in
        Auth::login($user);

        // Regenerate the session to prevent session fixation
        $request->session()->regenerate();

        return redirect()->route('home')
            ->withSuccess('You have successfully registered & logged in!');
    }
}
