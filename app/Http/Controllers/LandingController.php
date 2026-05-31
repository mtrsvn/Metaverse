<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function show(Request $request)
    {
        return view('landing');
    }
}
