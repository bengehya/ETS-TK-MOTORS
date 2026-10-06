<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\CashEntry;
use App\Services\CashService;
use App\Support\OperationsPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CashController extends Controller
{
    public function index(Request $request, CashService $cash): Response
    {
        $user = $request->user();

        $entries = CashEntry::query()
            ->where('organization_id', $user->organization_id)
            ->with('user')
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Finance/Cash/Index', [
            'balance' => $cash->displayedBalance($user->organization_id),
            'entries' => [
                'data' => $entries->getCollection()->map(fn (CashEntry $entry) => OperationsPresenter::cashEntry($entry))->all(),
                'links' => $entries->linkCollection()->toArray(),
            ],
        ]);
    }
}
