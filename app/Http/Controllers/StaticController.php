<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StaticController extends Controller
{
    public function showAboutUs()
    {
        return view('pages.about-us');
    }
}