<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Sale;
use App\Models\Seller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    /**
     * Display a listing of all sales.
     */
    public function index(Request $request)
    {
        try {
            $query = Sale::with(['seller', 'item']);

            // Optional filters
            if ($request->has('seller_id')) {
                $query->where('seller_id', $request->seller_id);
            }

            if ($request->has('date_from') && $request->has('date_to')) {
                $query->whereBetween('date', [
                    $request->date_from,
                    $request->date_to,
                ]);
            }

            $sales = $query->orderByDesc('date')
                ->orderBy('seller_id')
                ->paginate($request->get('limit', 30));

            return response()->json([
                'status' => true,
                'message' => 'Sales retrieved successfully',
                'data' => $sales->items(),
                'pagination' => [
                    'total' => $sales->total(),
                    'per_page' => $sales->perPage(),
                    'current_page' => $sales->currentPage(),
                    'last_page' => $sales->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve sales',
                'error' => $e->getMessage(),
            ], 500);
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
                return response()->json([
                    'status' => false,
                    'message' => 'Sale already exists for this seller on this date for this item',
                ], 409);
            }

            $sale = Sale::create($validated);

            return response()->json([
                'status' => true,
                'message' => 'Sale created successfully',
                'data' => $sale->load(['seller', 'item']),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create sale',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified sale.
     */
    public function show(string $id)
    {
        try {
            $sale = Sale::with(['seller', 'item'])->findOrFail($id);

            return response()->json([
                'status' => true,
                'message' => 'Sale retrieved successfully',
                'data' => $sale,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Sale not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve sale',
                'error' => $e->getMessage(),
            ], 500);
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
                'pick' => 'sometimes|integer|min:0',
                'returned' => 'nullable|integer|min:0',
                'custom_price' => 'nullable|integer|min:0',
                'red_flag' => 'nullable|boolean',
                'remarks' => 'nullable|string',
            ]);

            $sale->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Sale updated successfully',
                'data' => $sale->load(['seller', 'item']),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Sale not found',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update sale',
                'error' => $e->getMessage(),
            ], 500);
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

            return response()->json([
                'status' => true,
                'message' => 'Sale deleted successfully',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Sale not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete sale',
                'error' => $e->getMessage(),
            ], 500);
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

            return response()->json([
                'status' => true,
                'message' => 'Sales retrieved successfully',
                'data' => $sales->items(),
                'pagination' => [
                    'total' => $sales->total(),
                    'per_page' => $sales->perPage(),
                    'current_page' => $sales->currentPage(),
                    'last_page' => $sales->lastPage(),
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Seller not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve seller sales',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
