<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Services\MaintenanceService;
use App\Support\UserPresenter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceController extends Controller
{
    public function __construct(private MaintenanceService $maintenance) {}

    public function edit(Request $request): Response
    {
        $organization = $this->organization($request);

        return Inertia::render('Maintenance/Edit', [
            'enabled' => (bool) $organization->maintenance_enabled,
            'started_at' => $organization->maintenance_started_at
                ?->timezone(config('app.timezone'))
                ->format('d/m/Y H:i'),
            'started_by' => UserPresenter::identity($organization->maintenanceStarter),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->operate($request, enable: true);

        return redirect()
            ->route('maintenance.edit')
            ->with('status', 'Mode maintenance activé.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->operate($request, enable: false);

        return redirect()
            ->route('maintenance.edit')
            ->with('status', 'Mode maintenance désactivé.');
    }

    private function operate(Request $request, bool $enable): void
    {
        try {
            if ($enable) {
                $this->maintenance->enable($request->user());
            } else {
                $this->maintenance->disable($request->user());
            }
        } catch (AuthorizationException) {
            abort(403, 'Action non autorisée.');
        }
    }

    private function organization(Request $request): Organization
    {
        $organization = $request->user()->organization()->with('maintenanceStarter')->first();

        abort_if($organization === null, 403, 'Action non autorisée.');

        return $organization;
    }
}
