<?php

namespace App\Http\Controllers\Finance;

use App\Enums\CashDirection;
use App\Enums\Currency;
use App\Enums\Permission;
use App\Exceptions\InsufficientCashException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreCashAdjustmentRequest;
use App\Http\Requests\Finance\StoreCashDeclarationRequest;
use App\Models\CashDeclaration;
use App\Services\CashDeclarationService;
use App\Services\CashService;
use App\Services\ExchangeService;
use App\Support\Money;
use App\Support\OperationsPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class CashDeclarationController extends Controller
{
    public function index(Request $request, CashService $cash, ExchangeService $exchange): Response
    {
        $user = $request->user();
        $rate = $exchange->currentRate($user->organization_id);

        return Inertia::render('Finance/Cash/Count', [
            'balances' => $cash->balances($user->organization_id),
            'rate' => $rate === null ? null : [
                'cdf_per_usd' => Money::normalizeRate($rate->cdf_per_usd),
                'effective_at_label' => $rate->effective_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
            ],
            'declarations' => $this->declarations($user->organization_id),
            'canValidate' => $user->hasPermission(Permission::ManageExpenses),
            'canAdjust' => $user->hasPermission(Permission::ManageExpenses),
        ]);
    }

    public function store(StoreCashDeclarationRequest $request, CashDeclarationService $declarations): RedirectResponse
    {
        try {
            $declarations->declare(
                $request->user(),
                $request->string('note')->toString(),
                $request->input('counted_usd'),
                $request->input('counted_cdf'),
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['note' => $exception->getMessage()]);
        }

        return back()->with('status', 'Déclaration enregistrée. Les soldes comptables ne sont pas modifiés.');
    }

    public function validateDeclaration(Request $request, CashDeclaration $cashDeclaration, CashDeclarationService $declarations): RedirectResponse
    {
        abort_unless($request->user()->hasPermission(Permission::ManageExpenses), 403, 'Action non autorisée.');

        try {
            $declarations->validate($request->user(), $cashDeclaration);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['declaration' => $exception->getMessage()]);
        }

        return back()->with('status', 'Déclaration validée. Les soldes comptables ne sont pas modifiés.');
    }

    public function correct(StoreCashDeclarationRequest $request, CashDeclaration $cashDeclaration, CashDeclarationService $declarations): RedirectResponse
    {
        try {
            $declarations->correct(
                $request->user(),
                $cashDeclaration,
                $request->string('note')->toString(),
                $request->input('counted_usd'),
                $request->input('counted_cdf'),
                (string) $request->input('correction_reason'),
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['correction_reason' => $exception->getMessage()]);
        }

        return back()->with('status', 'Correction de déclaration enregistrée. Les soldes comptables ne sont pas modifiés.');
    }

    public function adjust(StoreCashAdjustmentRequest $request, CashService $cash): RedirectResponse
    {
        try {
            $cash->adjust(
                $request->user(),
                Currency::from($request->string('currency')->toString()),
                CashDirection::from($request->string('direction')->toString()),
                (string) $request->input('amount'),
                $request->string('reason')->toString(),
            );
        } catch (InsufficientCashException|InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['amount' => $exception->getMessage()]);
        }

        return redirect()
            ->route('cash.index')
            ->with('status', 'Correction de caisse enregistrée.');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function declarations(int $organizationId): array
    {
        return CashDeclaration::query()
            ->where('organization_id', $organizationId)
            ->with(['author', 'validator'])
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->map(fn (CashDeclaration $declaration) => OperationsPresenter::cashDeclaration($declaration))
            ->all();
    }
}
