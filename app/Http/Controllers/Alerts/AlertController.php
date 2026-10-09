<?php

namespace App\Http\Controllers\Alerts;

use App\Http\Controllers\Controller;
use App\Services\AlertService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlertController extends Controller
{
    public function index(Request $request, AlertService $alerts): Response
    {
        return Inertia::render('Alerts/Index', [
            'alerts' => $alerts->forUser($request->user()),
        ]);
    }
}
