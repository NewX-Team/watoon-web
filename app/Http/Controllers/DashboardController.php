<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        return match ($request->user()?->role) {
            Role::Admin => Inertia::render('dashboard/Admin'),
            Role::Kreator => Inertia::render('dashboard/Kreator'),
            default => Inertia::render('dashboard/User'),
        };
    }
}
