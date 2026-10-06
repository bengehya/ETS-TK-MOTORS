<?php

namespace App\Http\Controllers\Savings;

use App\Http\Controllers\Controller;
use App\Services\SavingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SavingsController extends Controller
{
    public function show(Request $request, SavingsService $savings): Response
    {
        return Inertia::render('Savings/Show', [
            'suggestion' => $savings->suggestion($request->user()->organization_id),
        ]);
    }
}
