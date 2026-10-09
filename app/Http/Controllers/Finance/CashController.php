<?php

namespace App\Http\Controllers\Finance;

use App\Enums\Currency;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\CashEntry;
use App\Services\CashService;
use App\Services\ExchangeService;
use App\Support\Money;
use App\Support\OperationsPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CashController extends Controller
{
    public function index(Request $request, CashService $cash, ExchangeService $exchange): Response
    {
        $user = $request->user();
        $rate = $exchange->currentRate($user->organization_id);

        $entries = CashEntry::query()
            ->where('organization_id', $user->organization_id)
            ->with('user')
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Finance/Cash/Index', [
            'balance' => $cash->displayedBalance($user->organization_id, Currency::Usd),
            'balances' => $cash->balances($user->organization_id),
            'rate' => $rate === null ? null : [
                'cdf_per_usd' => Money::normalizeRate($rate->cdf_per_usd),
                'effective_at_label' => $rate->effective_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
            ],
            'entries' => [
                'data' => $entries->getCollection()->map(fn (CashEntry $entry) => OperationsPresenter::cashEntry($entry))->all(),
                'links' => $entries->linkCollection()->toArray(),
            ],
            'declarations' => CashDeclarationController::declarations($user->organization_id),
            'canValidate' => $user->hasPermission(Permission::ManageExpenses),
            'canAdjust' => $user->hasPermission(Permission::ManageExpenses),
        ]);
    }
}
