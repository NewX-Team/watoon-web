<?php

namespace App\Http\Controllers;

use App\Enums\CreatorType;
use App\Enums\Role;
use App\Models\User;
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
        $user = $request->user();

        return match ($user?->role) {
            Role::Admin => Inertia::render('dashboard/Admin'),
            Role::Kreator => $this->renderKreatorDashboard($request, $user),
            default => Inertia::render('dashboard/User'),
        };
    }

    /**
     * Render the appropriate dashboard for creators.
     */
    protected function renderKreatorDashboard(Request $request, User $user): Response
    {
        $requestedType = $request->query('type');
        $creatorType = ($requestedType === CreatorType::Film->value || $requestedType === CreatorType::Novel->value)
            ? CreatorType::from($requestedType)
            : ($user->creator_type ?? CreatorType::Film);

        if ($creatorType === CreatorType::Novel) {
            return Inertia::render('dashboard/KreatorNovel', [
                'creatorType' => 'novel',
            ]);
        }

        return Inertia::render('dashboard/KreatorFilm', [
            'creatorType' => 'film',
        ]);
    }
}
