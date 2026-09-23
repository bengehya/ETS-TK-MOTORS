<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\LocationProvisioner;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        DashboardService $dashboard,
        LocationProvisioner $locations,
    ): Response {
        $periode = $dashboard->normalizePeriode($request->string('periode')->toString());

        return Inertia::render('Dashboard', $dashboard->payload(
            $request->user(),
            $periode,
            $locations,
        ));
    }
}
