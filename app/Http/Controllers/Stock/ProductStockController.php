<?php

namespace App\Http\Controllers\Stock;

use App\Exceptions\InactiveProductException;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\StoreStockAdjustmentRequest;
use App\Http\Requests\Stock\StoreStockReceiptRequest;
use App\Http\Requests\Stock\StoreStockTransferRequest;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class ProductStockController extends Controller
{
    public function receive(StoreStockReceiptRequest $request, Product $product, InventoryService $inventory): RedirectResponse
    {
        try {
            $inventory->receive(
                $request->user(),
                $product,
                $request->location(),
                $request->integer('quantity'),
                $request->input('notes'),
            );
        } catch (InactiveProductException $exception) {
            throw ValidationException::withMessages(['quantity' => $exception->getMessage()]);
        }

        return back()->with('status', 'Entrée de stock enregistrée.');
    }

    public function transferToBoutique(StoreStockTransferRequest $request, Product $product, InventoryService $inventory): RedirectResponse
    {
        try {
            $inventory->transferDepotToBoutique(
                $request->user(),
                $product,
                $request->integer('quantity'),
                $request->input('notes'),
            );
        } catch (InsufficientStockException|InactiveProductException $exception) {
            throw ValidationException::withMessages(['quantity' => $exception->getMessage()]);
        }

        return back()->with('status', 'Transfert dépôt → boutique enregistré.');
    }

    public function adjust(StoreStockAdjustmentRequest $request, Product $product, InventoryService $inventory): RedirectResponse
    {
        try {
            $inventory->adjust(
                $request->user(),
                $product,
                $request->location(),
                $request->integer('quantity'),
                $request->string('direction')->toString(),
                $request->string('reason')->toString(),
            );
        } catch (InsufficientStockException $exception) {
            throw ValidationException::withMessages(['quantity' => $exception->getMessage()]);
        }

        return back()->with('status', 'Ajustement de stock enregistré.');
    }
}
