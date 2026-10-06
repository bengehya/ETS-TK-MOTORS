<?php

namespace App\Http\Controllers\Finance;

use App\Enums\ExpenseStatus;
use App\Exceptions\OperationAlreadyProcessedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\RefuseExpenseRequest;
use App\Http\Requests\Finance\StoreExpenseRequest;
use App\Models\Expense;
use App\Services\ExpenseService;
use App\Support\OperationsPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ExpenseController extends Controller
{
    public function index(Request $request): Response
    {
        $expenses = Expense::query()
            ->forOrganization($request->user()->organization_id)
            ->with(['creator', 'decider'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('q'), function ($query) use ($request): void {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($request->string('q')->toString())).'%';
                $query->where(function ($inner) use ($like): void {
                    $inner->where('reference', 'like', $like)->orWhere('reason', 'like', $like);
                });
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Finance/Expenses/Index', [
            'expenses' => [
                'data' => $expenses->getCollection()->map(fn (Expense $expense) => OperationsPresenter::expense($expense))->all(),
                'links' => $expenses->linkCollection()->toArray(),
            ],
            'filters' => [
                'q' => $request->string('q')->toString(),
                'status' => $request->string('status')->toString(),
            ],
            'statuses' => collect(ExpenseStatus::cases())->map(fn (ExpenseStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->values(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Finance/Expenses/Create');
    }

    public function store(StoreExpenseRequest $request, ExpenseService $expenses): RedirectResponse
    {
        $expense = $expenses->create(
            $request->user(),
            (string) $request->input('amount'),
            $request->string('reason')->toString(),
            $request->date('spent_on')->toDateString(),
        );

        return redirect()
            ->route('expenses.show', $expense)
            ->with('status', 'Dépense '.$expense->reference.' enregistrée. Elle n’est pas encore débitée.');
    }

    public function show(Expense $expense): Response
    {
        $expense->load(['creator', 'decider']);

        return Inertia::render('Finance/Expenses/Show', [
            'expense' => OperationsPresenter::expense($expense),
        ]);
    }

    public function validateExpense(Request $request, Expense $expense, ExpenseService $expenses): RedirectResponse
    {
        try {
            $updated = $expenses->validate($request->user(), $expense);
        } catch (OperationAlreadyProcessedException $exception) {
            throw ValidationException::withMessages(['expense' => $exception->getMessage()]);
        }

        $message = $updated->status === ExpenseStatus::Validated
            ? 'Dépense validée. La caisse a été débitée.'
            : 'Dépense refusée : le solde de caisse est insuffisant.';

        return redirect()->route('expenses.show', $updated)->with('status', $message);
    }

    public function refuse(RefuseExpenseRequest $request, Expense $expense, ExpenseService $expenses): RedirectResponse
    {
        try {
            $expenses->refuse($request->user(), $expense, $request->string('decision_note')->toString());
        } catch (Throwable $exception) {
            if (! $exception instanceof OperationAlreadyProcessedException) {
                throw $exception;
            }

            throw ValidationException::withMessages(['decision_note' => $exception->getMessage()]);
        }

        return redirect()
            ->route('expenses.show', $expense)
            ->with('status', 'Dépense refusée. La caisse n’a pas été débitée.');
    }
}
