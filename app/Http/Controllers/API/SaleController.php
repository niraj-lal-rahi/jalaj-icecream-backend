<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Http\Traits\ApiResponse;
use App\Models\Item;
use App\Models\Sale;
use App\Models\Seller;
use App\Exceptions\SaleNotFoundException;
use App\Exceptions\SellerNotFoundException;
use App\Exceptions\ItemNotFoundException;
use App\Http\Requests\CreateSaleRequest;
use App\Http\Requests\UpdateSaleRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

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
     *
     * @param CreateSaleRequest $request Validated sale data
     * @return \Illuminate\Http\JsonResponse Created sale or error response
     */
    public function store(CreateSaleRequest $request)
    {
        try {
            $validated = $request->validated();

            // Check if sale already exists for this seller on this date for this item
            $existingSale = Sale::where('seller_id', $validated['seller_id'])
                ->where('item_id', $validated['item_id'])
                ->whereDate('date', $validated['date'])
                ->first();

            if ($existingSale) {
                return $this->error('Sale already exists for this seller on this date for this item', 409);
            }

            $sale = Sale::create($validated);

            // Invalidate top performers cache as seller performance has changed
            Cache::forget('top_performers');

            return $this->success($sale->load(['seller', 'item']), 'Sale created successfully', 201);
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
     *
     * @param string $id Sale ID
     * @return \Illuminate\Http\JsonResponse Sale data or error response
     * @throws SaleNotFoundException
     */
    public function show(string $id)
    {
        try {
            $sale = Sale::with(['seller', 'item'])->find($id);

            if (!$sale) {
                throw new SaleNotFoundException((int) $id);
            }

            return $this->success($sale, 'Sale retrieved successfully');
        } catch (SaleNotFoundException $e) {
            Log::warning('Sale not found: SaleController::show', [
                'method' => 'show',
                'sale_id' => $id,
                'message' => $e->getMessage(),
            ]);
            return $this->error($e->getMessage(), 404);
        } catch (\Exception $e) {
            Log::error('API Error: SaleController::show', [
                'method' => 'show',
                'sale_id' => $id,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            return $this->error('Failed to retrieve sale', 500, $e->getMessage());
        }
    }

    /**
     * Update the specified sale.
     *
     * @param UpdateSaleRequest $request Validated sale data
     * @param string $id Sale ID to update
     * @return \Illuminate\Http\JsonResponse Updated sale or error response
     * @throws SaleNotFoundException
     */
    public function update(UpdateSaleRequest $request, string $id)
    {
        try {
            $sale = Sale::find($id);

            if (!$sale) {
                throw new SaleNotFoundException((int) $id);
            }

            $validated = $request->validated();

            $sale->update($validated);

            // Invalidate top performers cache as seller performance has changed
            Cache::forget('top_performers');

            return $this->success($sale->load(['seller', 'item']), 'Sale updated successfully');
        } catch (SaleNotFoundException $e) {
            Log::warning('Sale not found: SaleController::update', [
                'method' => 'update',
                'sale_id' => $id,
                'message' => $e->getMessage(),
            ]);
            return $this->error($e->getMessage(), 404);
        } catch (\Exception $e) {
            Log::error('API Error: SaleController::update', [
                'method' => 'update',
                'sale_id' => $id,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            return $this->error('Failed to update sale', 500, $e->getMessage());
        }
    }

    /**
     * Remove the specified sale.
     *
     * @param string $id Sale ID to delete
     * @return \Illuminate\Http\JsonResponse Success or error response
     * @throws SaleNotFoundException
     */
    public function destroy(string $id)
    {
        try {
            $sale = Sale::find($id);

            if (!$sale) {
                throw new SaleNotFoundException((int) $id);
            }

            $sale->delete();

            // Invalidate top performers cache as seller performance has changed
            Cache::forget('top_performers');

            return $this->success(null, 'Sale deleted successfully');
        } catch (SaleNotFoundException $e) {
            Log::warning('Sale not found: SaleController::destroy', [
                'method' => 'destroy',
                'sale_id' => $id,
                'message' => $e->getMessage(),
            ]);
            return $this->error($e->getMessage(), 404);
        } catch (\Exception $e) {
            Log::error('API Error: SaleController::destroy', [
                'method' => 'destroy',
                'sale_id' => $id,
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
