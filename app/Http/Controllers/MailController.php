<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\MailModel;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use App\Mail\ContactMail; 


class MailController extends Controller
{
    function send(Request $request) {
        $request->validate([
            'email' => 'required|email',
        ]);

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return back()->withErrors('We could not find a user with that email address.');
            }

            $name = $user->name;

            $token = Password::getRepository()->create($user);

            $resetLink = url(route('show.new-password', ['token' => $token, 'email' => $request->email], false));

            $mailData = [
                'name' => $name,
                'email' => $request->email,
                'resetLink' => $resetLink,
            ];

            // Send the email
            Mail::to($request->email)->send(new MailModel($mailData));

            return redirect()->back()->withSuccess('An email has been sent!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors('Failed to send email: ' . $e->getMessage());
        }    
    }

    public function contact(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'message' => 'required|string|max:2000',
        ]);

        try {
            $mailData = [
                'name' => $request->name,
                'email' => $request->email,
                'message' => $request->message,
            ];

            Mail::to(env('MAIL_FROM_ADDRESS'))->send(new ContactMail($mailData));

            return redirect()->back()->withSuccess('An email has been sent!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors('Failed to send email: ' . $e->getMessage());
        }
    }
}
