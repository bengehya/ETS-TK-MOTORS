<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\OperationsPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $organizationId = $request->user()->organization_id;

        $logs = AuditLog::query()
            ->where('organization_id', $organizationId)
            ->with('user')
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->string('action')->toString()))
            ->when($request->filled('date_from'), fn ($query) => $query->where('created_at', '>=', $request->date('date_from')->startOfDay()))
            ->when($request->filled('date_to'), fn ($query) => $query->where('created_at', '<=', $request->date('date_to')->endOfDay()))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $actions = AuditLog::query()
            ->where('organization_id', $organizationId)
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->values();

        return Inertia::render('Audit/Index', [
            'logs' => [
                'data' => $logs->getCollection()->map(fn (AuditLog $log) => OperationsPresenter::audit($log))->all(),
                'links' => $logs->linkCollection()->toArray(),
            ],
            'actions' => $actions,
            'filters' => [
                'action' => $request->string('action')->toString(),
                'date_from' => $request->string('date_from')->toString(),
                'date_to' => $request->string('date_to')->toString(),
            ],
        ]);
    }
}
