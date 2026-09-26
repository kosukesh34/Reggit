<?php

namespace App\Http\Controllers;

class LandingController extends Controller
{
    public function __invoke()
    {
        if (auth('admin')->check()) {
            return redirect()->route('admin.root');
        }

        return view('landing');
    }
}
