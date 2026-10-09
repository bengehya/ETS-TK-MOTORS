<?php

namespace App\Http\Controllers\Finance;

use App\Enums\Currency;
use App\Exceptions\InsufficientCashException;
use App\Exceptions\MissingExchangeRateException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreExchangeRateRequest;
use App\Http\Requests\Finance\StoreExchangeRequest;
use App\Models\Exchange;
use App\Models\ExchangeRate;
use App\Services\CashService;
use App\Services\ExchangeService;
use App\Support\OperationsPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class ExchangeController extends Controller
{
    public function index(Request $request, ExchangeService $exchange, CashService $cash): Response
    {
        $user = $request->user();
        $preview = null;

        $sourceCurrency = Currency::tryFrom($request->string('source_currency')->toString());

        if ($request->filled('amount') && $sourceCurrency !== null) {
            $preview = $exchange->preview(
                $user->organization_id,
                $sourceCurrency,
                $request->string('amount')->toString(),
            );
        }

        return Inertia::render('Finance/Cash/Exchange', [
            'balances' => $cash->balances($user->organization_id),
            'rate' => ($current = $exchange->currentRate($user->organization_id))
                ? OperationsPresenter::exchangeRate($current->loadMissing('creator'))
                : null,
            'rates' => ExchangeRate::query()
                ->where('organization_id', $user->organization_id)
                ->with('creator')
                ->orderByDesc('effective_at')
                ->orderByDesc('id')
                ->limit(20)
                ->get()
                ->map(fn (ExchangeRate $rate) => OperationsPresenter::exchangeRate($rate))
                ->all(),
            'exchanges' => Exchange::query()
                ->where('organization_id', $user->organization_id)
                ->with('creator')
                ->orderByDesc('occurred_at')
                ->orderByDesc('id')
                ->limit(20)
                ->get()
                ->map(fn (Exchange $row) => OperationsPresenter::exchange($row))
                ->all(),
            'preview' => $preview,
            'filters' => [
                'amount' => $request->string('amount')->toString(),
                'source_currency' => $request->string('source_currency')->toString(),
            ],
        ]);
    }

    public function storeRate(StoreExchangeRateRequest $request, ExchangeService $exchange): RedirectResponse
    {
        try {
            $exchange->setRate($request->user(), (string) $request->input('cdf_per_usd'));
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['cdf_per_usd' => $exception->getMessage()]);
        }

        return redirect()
            ->route('cash.exchange')
            ->with('status', 'Taux enregistré. Les opérations déjà passées conservent leur taux.');
    }

    public function store(StoreExchangeRequest $request, ExchangeService $exchange): RedirectResponse
    {
        try {
            $created = $exchange->convert(
                $request->user(),
                Currency::from($request->string('source_currency')->toString()),
                (string) $request->input('amount'),
            );
        } catch (MissingExchangeRateException|InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['amount' => $exception->getMessage()]);
        } catch (InsufficientCashException $exception) {
            throw ValidationException::withMessages(['amount' => $exception->getMessage()]);
        }

        return redirect()
            ->route('cash.exchange')
            ->with('status', 'Change '.$created->reference.' enregistré.')
            ->with('exchange_result', $exchange->settlement($created));
    }
}
