<?php

namespace App\Http\Controllers;

use App\User;

class ConfirmRegistration extends Controller
{
    public function index()
    {
        $user = User::where('confirmation_token', request('token'))
            ->first();

        if (!$user) {
            return redirect('/threads')->with('flash', "Invalid token");
        }

        $user->confirm();
        return redirect('/threads')->with('flash', "Your account is now confirmed! You may post to the forum.");
    }
    //
}
