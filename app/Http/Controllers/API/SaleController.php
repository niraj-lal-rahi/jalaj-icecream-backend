<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Http\Traits\ApiResponse;
use App\Models\Item;
use App\Models\Sale;
use App\Models\Seller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of all sales.
     *
     * Query Parameters:
     * - seller_id: Filter by seller ID (takes precedence over seller_name)
     * - seller_name: Filter by seller name using partial match (LIKE)
     * - date: Filter by exact date (YYYY-MM-DD format)
     * - date_from & date_to: Filter by date range (both required for range filter)
     * - limit: Number of items per page (default: 30)
     */
    public function index(Request $request)
    {
        try {
            $query = Sale::with(['seller', 'item']);

            // Filter by seller_id (takes precedence over seller_name)
            if ($request->has('seller_id')) {
                $query->where('seller_id', $request->seller_id);
            } elseif ($request->has('seller_name')) {
                // Filter by seller name using partial match
                $query->whereHas('seller', function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->seller_name . '%');
                });
            }

            // Filter by exact date
            if ($request->has('date')) {
                $query->where('date', $request->date);
            }
            // Or filter by date range (if both date_from and date_to provided)
            elseif ($request->has('date_from') && $request->has('date_to')) {
                $query->whereBetween('date', [
                    $request->date_from,
                    $request->date_to,
                ]);
            }

            $sales = $query->orderByDesc('date')
                ->orderBy('seller_id')
                ->paginate($request->get('limit', 30));

            return $this->successPaginated($sales->items(), 'Sales retrieved successfully', [
                'total' => $sales->total(),
                'per_page' => $sales->perPage(),
                'current_page' => $sales->currentPage(),
                'last_page' => $sales->lastPage(),
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve sales', 500, $e->getMessage());
        }
    }

    /**
     * Store a newly created sale.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'seller_id' => 'required|exists:sellers,id',
                'item_id' => 'required|exists:items,id',
                'pick' => 'required|integer|min:0',
                'returned' => 'nullable|integer|min:0',
                'custom_price' => 'nullable|integer|min:0',
                'red_flag' => 'nullable|boolean',
                'remarks' => 'nullable|string',
                'date' => 'required|date',
            ]);

            // Check if sale already exists for this seller on this date for this item
            $existingSale = Sale::where('seller_id', $validated['seller_id'])
                ->where('item_id', $validated['item_id'])
                ->whereDate('date', $validated['date'])
                ->first();

            if ($existingSale) {
                return $this->error('Sale already exists for this seller on this date for this item', 409);
            }

            $sale = Sale::create($validated);

            return $this->success($sale->load(['seller', 'item']), 'Sale created successfully', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation Error: SaleController::store', [
                'method' => 'store',
                'errors' => $e->errors(),
            ]);
            return $this->error('Validation failed', 422, null, [
                'errors' => $e->errors(),
            ]);
        } catch (\Exception $e) {
            Log::error('API Error: SaleController::store', [
                'method' => 'store',
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            return $this->error('Failed to create sale', 500, $e->getMessage());
        }
    }

    /**
     * Display the specified sale.
     */
    public function show(string $id)
    {
        try {
            $sale = Sale::with(['seller', 'item'])->findOrFail($id);

            return $this->success($sale, 'Sale retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Not Found: SaleController::show', [
                'method' => 'show',
                'id' => $id,
            ]);
            return $this->error('Sale not found', 404);
        } catch (\Exception $e) {
            Log::error('API Error: SaleController::show', [
                'method' => 'show',
                'id' => $id,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            return $this->error('Failed to retrieve sale', 500, $e->getMessage());
        }
    }

    /**
     * Update the specified sale.
     */
    public function update(Request $request, string $id)
    {
        try {
            $sale = Sale::findOrFail($id);

            $validated = $request->validate([
                'seller_id' => 'sometimes|exists:sellers,id',
                'date' => 'sometimes|date_format:Y-m-d',
                'pick' => 'sometimes|integer|min:0',
                'returned' => 'nullable|integer|min:0',
                'custom_price' => 'nullable|integer|min:0',
                'red_flag' => 'nullable|boolean',
                'remarks' => 'nullable|string',
            ]);

            $sale->update($validated);

            return $this->success($sale->load(['seller', 'item']), 'Sale updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Not Found: SaleController::update', [
                'method' => 'update',
                'id' => $id,
            ]);
            return $this->error('Sale not found', 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation Error: SaleController::update', [
                'method' => 'update',
                'id' => $id,
                'errors' => $e->errors(),
            ]);
            return $this->error('Validation failed', 422, null, [
                'errors' => $e->errors(),
            ]);
        } catch (\Exception $e) {
            Log::error('API Error: SaleController::update', [
                'method' => 'update',
                'id' => $id,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            return $this->error('Failed to update sale', 500, $e->getMessage());
        }
    }

    /**
     * Remove the specified sale.
     */
    public function destroy(string $id)
    {
        try {
            $sale = Sale::findOrFail($id);
            $sale->delete();

            return $this->success(null, 'Sale deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Not Found: SaleController::destroy', [
                'method' => 'destroy',
                'id' => $id,
            ]);
            return $this->error('Sale not found', 404);
        } catch (\Exception $e) {
            Log::error('API Error: SaleController::destroy', [
                'method' => 'destroy',
                'id' => $id,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            return $this->error('Failed to delete sale', 500, $e->getMessage());
        }
    }

    /**
     * Get sales for a specific seller.
     */
    public function showSellerSales(string $sellerId, Request $request)
    {
        try {
            Seller::findOrFail($sellerId);

            $sales = Sale::with('item')
                ->where('seller_id', $sellerId)
                ->orderByDesc('date')
                ->paginate($request->get('limit', 30));

            return $this->successPaginated($sales->items(), 'Sales retrieved successfully', [
                'total' => $sales->total(),
                'per_page' => $sales->perPage(),
                'current_page' => $sales->currentPage(),
                'last_page' => $sales->lastPage(),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Not Found: SaleController::showSellerSales', [
                'method' => 'showSellerSales',
                'seller_id' => $sellerId,
            ]);
            return $this->error('Seller not found', 404);
        } catch (\Exception $e) {
            Log::error('API Error: SaleController::showSellerSales', [
                'method' => 'showSellerSales',
                'seller_id' => $sellerId,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            return $this->error('Failed to retrieve seller sales', 500, $e->getMessage());
        }
    }
}
