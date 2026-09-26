<?php

namespace App\Http\Controllers;

class ApiInfoController extends Controller
{
    public function __invoke()
    {
        return response()->json(['message' => 'Reggit API']);
    }
}
