<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Http\Traits\ApiResponse;
use App\Models\Item;
use App\Exceptions\ItemNotFoundException;
use App\Http\Requests\CreateItemRequest;
use App\Http\Requests\UpdateItemRequest;

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
     *
     * @param CreateItemRequest $request Validated item data
     * @return \Illuminate\Http\JsonResponse Created item or error response
     */
    public function store(CreateItemRequest $request)
    {
        try {
            $validated = $request->validated();
            $item = Item::create($validated);

            return $this->success($item, 'Item created successfully', 201);
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
     *
     * @param string $id Item ID
     * @return \Illuminate\Http\JsonResponse Item data or error response
     * @throws ItemNotFoundException
     */
    public function show(string $id)
    {
        try {
            $item = Item::find($id);

            if (!$item) {
                throw new ItemNotFoundException((int) $id);
            }

            return $this->success($item, 'Item retrieved successfully');
        } catch (ItemNotFoundException $e) {
            Log::warning('Item not found: ItemController::show', [
                'method' => 'show',
                'item_id' => $id,
                'message' => $e->getMessage(),
            ]);
            return $this->error($e->getMessage(), 404);
        } catch (\Exception $e) {
            Log::error('API Error: ItemController::show', [
                'method' => 'show',
                'item_id' => $id,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            return $this->error('Failed to retrieve item', 500, $e->getMessage());
        }
    }

    /**
     * Update the specified item.
     *
     * @param UpdateItemRequest $request Validated item data
     * @param string $id Item ID to update
     * @return \Illuminate\Http\JsonResponse Updated item or error response
     * @throws ItemNotFoundException
     */
    public function update(UpdateItemRequest $request, string $id)
    {
        try {
            $item = Item::find($id);

            if (!$item) {
                throw new ItemNotFoundException((int) $id);
            }

            $validated = $request->validated();
            $item->update($validated);

            return $this->success($item, 'Item updated successfully');
        } catch (ItemNotFoundException $e) {
            Log::warning('Item not found: ItemController::update', [
                'method' => 'update',
                'item_id' => $id,
                'message' => $e->getMessage(),
            ]);
            return $this->error($e->getMessage(), 404);
        } catch (\Exception $e) {
            Log::error('API Error: ItemController::update', [
                'method' => 'update',
                'item_id' => $id,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            return $this->error('Failed to update item', 500, $e->getMessage());
        }
    }

    /**
     * Remove the specified item.
     *
     * @param string $id Item ID to delete
     * @return \Illuminate\Http\JsonResponse Success or error response
     * @throws ItemNotFoundException
     */
    public function destroy(string $id)
    {
        try {
            $item = Item::find($id);

            if (!$item) {
                throw new ItemNotFoundException((int) $id);
            }

            $item->delete();

            return $this->success(null, 'Item deleted successfully');
        } catch (ItemNotFoundException $e) {
            Log::warning('Item not found: ItemController::destroy', [
                'method' => 'destroy',
                'item_id' => $id,
                'message' => $e->getMessage(),
            ]);
            return $this->error($e->getMessage(), 404);
        } catch (\Exception $e) {
            Log::error('API Error: ItemController::destroy', [
                'method' => 'destroy',
                'item_id' => $id,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            return $this->error('Failed to delete item', 500, $e->getMessage());
        }
    }
}
