<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class AdminProjectHomeController extends Controller
{
    public function __invoke(string $project_key)
    {
        return redirect()->route('admin.dashboard', ['project_key' => $project_key]);
    }
}
