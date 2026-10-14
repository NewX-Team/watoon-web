<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MessageController extends Controller
{
    /**
     * Display the messages view.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('messages/Index', [
            'selectedCreatorId' => $request->query('creator'),
        ]);
    }
}
