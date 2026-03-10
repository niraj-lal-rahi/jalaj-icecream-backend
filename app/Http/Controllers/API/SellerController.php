<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Http\Traits\ApiResponse;
use App\Models\Seller;
use Illuminate\Http\Request;

class SellerController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of all sellers.
     */
    public function index()
    {
        try {
            $sellers = Seller::with('documents')
                ->orderBy('name')
                ->paginate(20);

            return $this->successPaginated($sellers->items(), 'Sellers retrieved successfully', [
                'total' => $sellers->total(),
                'per_page' => $sellers->perPage(),
                'current_page' => $sellers->currentPage(),
                'last_page' => $sellers->lastPage(),
            ]);
        } catch (\Exception $e) {
            Log::error('API Error: SellerController::index', [
                'method' => 'index',
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            return $this->error('Failed to retrieve sellers', 500, $e->getMessage());
        }
    }

    /**
     * Store a newly created seller.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'number' => 'required|string|max:20',
                'address' => 'required|string',
            ]);

            $seller = Seller::create($validated);

            return $this->success($seller->load('documents'), 'Seller created successfully', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation Error: SellerController::store', [
                'method' => 'store',
                'errors' => $e->errors(),
            ]);
            return $this->error('Validation failed', 422, null, [
                'errors' => $e->errors(),
            ]);
        } catch (\Exception $e) {
            Log::error('API Error: SellerController::store', [
                'method' => 'store',
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            return $this->error('Failed to create seller', 500, $e->getMessage());
        }
    }

    /**
     * Display the specified seller.
     */
    public function show(string $id)
    {
        try {
            $seller = Seller::with('documents')->findOrFail($id);

            return $this->success($seller, 'Seller retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Not Found: SellerController::show', [
                'method' => 'show',
                'id' => $id,
            ]);
            return $this->error('Seller not found', 404);
        } catch (\Exception $e) {
            Log::error('API Error: SellerController::show', [
                'method' => 'show',
                'id' => $id,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            return $this->error('Failed to retrieve seller', 500, $e->getMessage());
        }
    }

    /**
     * Update the specified seller.
     */
    public function update(Request $request, string $id)
    {
        try {
            $seller = Seller::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'number' => 'sometimes|string|max:20',
                'address' => 'sometimes|string',
            ]);

            $seller->update($validated);

            return $this->success($seller->load('documents'), 'Seller updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Not Found: SellerController::update', [
                'method' => 'update',
                'id' => $id,
            ]);
            return $this->error('Seller not found', 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation Error: SellerController::update', [
                'method' => 'update',
                'id' => $id,
                'errors' => $e->errors(),
            ]);
            return $this->error('Validation failed', 422, null, [
                'errors' => $e->errors(),
            ]);
        } catch (\Exception $e) {
            Log::error('API Error: SellerController::update', [
                'method' => 'update',
                'id' => $id,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            return $this->error('Failed to update seller', 500, $e->getMessage());
        }
    }

    /**
     * Remove the specified seller.
     */
    public function destroy(string $id)
    {
        try {
            $seller = Seller::findOrFail($id);
            $seller->delete();

            return $this->success(null, 'Seller deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Not Found: SellerController::destroy', [
                'method' => 'destroy',
                'id' => $id,
            ]);
            return $this->error('Seller not found', 404);
        } catch (\Exception $e) {
            Log::error('API Error: SellerController::destroy', [
                'method' => 'destroy',
                'id' => $id,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            return $this->error('Failed to delete seller', 500, $e->getMessage());
        }
    }
}
