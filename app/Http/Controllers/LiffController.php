<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LiffController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        return view('index');
    }
}
