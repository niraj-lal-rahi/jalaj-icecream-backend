<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Http\Traits\ApiResponse;
use App\Models\Item;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of all items.
     */
    public function index()
    {
        try {
            $items = Item::orderBy('order_by')
                ->paginate(50);

            return $this->successPaginated($items->items(), 'Items retrieved successfully', [
                'total' => $items->total(),
                'per_page' => $items->perPage(),
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
            ]);
        } catch (\Exception $e) {
            Log::error('API Error: ItemController::index', [
                'method' => 'index',
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            return $this->error('Failed to retrieve items', 500, $e->getMessage());
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

            return $this->success($item, 'Item created successfully', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation Error: ItemController::store', [
                'method' => 'store',
                'errors' => $e->errors(),
            ]);
            return $this->error('Validation failed', 422, null, [
                'errors' => $e->errors(),
            ]);
        } catch (\Exception $e) {
            Log::error('API Error: ItemController::store', [
                'method' => 'store',
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            return $this->error('Failed to create item', 500, $e->getMessage());
        }
    }

    /**
     * Display the specified item.
     */
    public function show(string $id)
    {
        try {
            $item = Item::findOrFail($id);

            return $this->success($item, 'Item retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Not Found: ItemController::show', [
                'method' => 'show',
                'id' => $id,
            ]);
            return $this->error('Item not found', 404);
        } catch (\Exception $e) {
            Log::error('API Error: ItemController::show', [
                'method' => 'show',
                'id' => $id,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            return $this->error('Failed to retrieve item', 500, $e->getMessage());
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

            return $this->success($item, 'Item updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Not Found: ItemController::update', [
                'method' => 'update',
                'id' => $id,
            ]);
            return $this->error('Item not found', 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation Error: ItemController::update', [
                'method' => 'update',
                'id' => $id,
                'errors' => $e->errors(),
            ]);
            return $this->error('Validation failed', 422, null, [
                'errors' => $e->errors(),
            ]);
        } catch (\Exception $e) {
            Log::error('API Error: ItemController::update', [
                'method' => 'update',
                'id' => $id,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            return $this->error('Failed to update item', 500, $e->getMessage());
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

            return $this->success(null, 'Item deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Not Found: ItemController::destroy', [
                'method' => 'destroy',
                'id' => $id,
            ]);
            return $this->error('Item not found', 404);
        } catch (\Exception $e) {
            Log::error('API Error: ItemController::destroy', [
                'method' => 'destroy',
                'id' => $id,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            return $this->error('Failed to delete item', 500, $e->getMessage());
        }
    }
}
