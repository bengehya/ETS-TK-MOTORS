<?php

namespace App\Http\Controllers\Rentals;

use App\Enums\RentalStatus;
use App\Exceptions\OperationAlreadyProcessedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Rentals\CloseRentalRequest;
use App\Http\Requests\Rentals\StoreRentalRequest;
use App\Models\Rental;
use App\Services\RentalService;
use App\Support\OperationsPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RentalController extends Controller
{
    public function index(Request $request, RentalService $rentals): Response
    {
        $rentals->syncExpired($request->user());

        $rows = Rental::query()
            ->forOrganization($request->user()->organization_id)
            ->with(['recorder', 'closer'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('q'), function ($query) use ($request): void {
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($request->string('q')->toString())).'%';
                $query->where('label', 'like', $like);
            })
            ->orderBy('ends_on')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Rentals/Index', [
            'rentals' => [
                'data' => $rows->getCollection()->map(fn (Rental $rental) => OperationsPresenter::rental($rental, true))->all(),
                'links' => $rows->linkCollection()->toArray(),
            ],
            'filters' => [
                'q' => $request->string('q')->toString(),
                'status' => $request->string('status')->toString(),
            ],
            'statuses' => collect(RentalStatus::cases())->map(fn (RentalStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->values(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Rentals/Create');
    }

    public function store(StoreRentalRequest $request, RentalService $rentals): RedirectResponse
    {
        $rental = $rentals->open(
            $request->user(),
            $request->string('label')->toString(),
            (string) $request->input('amount'),
            $request->date('started_on')->toDateString(),
            $request->integer('duration_months'),
        );

        return redirect()
            ->route('rentals.show', $rental)
            ->with('status', 'Location enregistrée.');
    }

    public function show(Request $request, Rental $rental, RentalService $rentals): Response
    {
        $rentals->syncExpired($request->user());
        $rental->refresh()->load(['recorder', 'closer']);

        return Inertia::render('Rentals/Show', [
            'rental' => OperationsPresenter::rental($rental, true),
        ]);
    }

    public function close(CloseRentalRequest $request, Rental $rental, RentalService $rentals): RedirectResponse
    {
        try {
            $rentals->close($request->user(), $rental, $request->string('reason')->toString());
        } catch (OperationAlreadyProcessedException $exception) {
            throw ValidationException::withMessages(['reason' => $exception->getMessage()]);
        }

        return redirect()
            ->route('rentals.show', $rental)
            ->with('status', 'Location clôturée.');
    }
}
