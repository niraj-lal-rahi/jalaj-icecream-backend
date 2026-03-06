<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    /**
     * Display a listing of all items.
     */
    public function index()
    {
        try {
            $items = Item::orderBy('order_by')
                ->paginate(50);

            return response()->json([
                'status' => true,
                'message' => 'Items retrieved successfully',
                'data' => $items->items(),
                'pagination' => [
                    'total' => $items->total(),
                    'per_page' => $items->perPage(),
                    'current_page' => $items->currentPage(),
                    'last_page' => $items->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve items',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created item.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:250',
                'price' => 'required|integer|min:0',
                'order_by' => 'required|integer|min:0',
            ]);

            $item = Item::create($validated);

            return response()->json([
                'status' => true,
                'message' => 'Item created successfully',
                'data' => $item,
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
                'message' => 'Failed to create item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified item.
     */
    public function show(string $id)
    {
        try {
            $item = Item::findOrFail($id);

            return response()->json([
                'status' => true,
                'message' => 'Item retrieved successfully',
                'data' => $item,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Item not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified item.
     */
    public function update(Request $request, string $id)
    {
        try {
            $item = Item::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|string|max:250',
                'price' => 'sometimes|integer|min:0',
                'order_by' => 'sometimes|integer|min:0',
            ]);

            $item->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Item updated successfully',
                'data' => $item,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Item not found',
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
                'message' => 'Failed to update item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified item.
     */
    public function destroy(string $id)
    {
        try {
            $item = Item::findOrFail($id);
            $item->delete();

            return response()->json([
                'status' => true,
                'message' => 'Item deleted successfully',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Item not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
