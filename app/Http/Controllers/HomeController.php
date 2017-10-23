<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return redirect('/threads');
    }

    public function xxx(){

        for ($x = 1; $x <= 20000000; $x++) {
            factory('App\User', 1)->create(['email'=>str_random(100)]);
        }

//        \App\User::where('id', '>', 0)->update(['created_at' => '2017-08-04 10:34:50']);
    }
}
