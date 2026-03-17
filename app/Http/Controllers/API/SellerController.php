<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Http\Traits\ApiResponse;
use App\Models\Seller;
use App\Exceptions\SellerNotFoundException;
use App\Http\Requests\CreateSellerRequest;
use App\Http\Requests\UpdateSellerRequest;
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
                ->paginate(100);

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
     *
     * @param CreateSellerRequest $request Validated seller data
     * @return \Illuminate\Http\JsonResponse Created seller or error response
     */
    public function store(CreateSellerRequest $request)
    {
        try {
            $validated = $request->validated();

            $seller = Seller::create($validated);

            return $this->success($seller->load('documents'), 'Seller created successfully', 201);
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
     *
     * @param string $id Seller ID
     * @return \Illuminate\Http\JsonResponse Seller data or error response
     * @throws SellerNotFoundException
     */
    public function show(string $id)
    {
        try {
            $seller = Seller::with('documents')->find($id);

            if (!$seller) {
                throw new SellerNotFoundException((int) $id);
            }

            return $this->success($seller, 'Seller retrieved successfully');
        } catch (SellerNotFoundException $e) {
            Log::warning('Seller not found: SellerController::show', [
                'method' => 'show',
                'seller_id' => $id,
                'message' => $e->getMessage(),
            ]);
            return $this->error($e->getMessage(), 404);
        } catch (\Exception $e) {
            Log::error('API Error: SellerController::show', [
                'method' => 'show',
                'seller_id' => $id,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            return $this->error('Failed to retrieve seller', 500, $e->getMessage());
        }
    }

    /**
     * Update the specified seller.
     *
     * @param UpdateSellerRequest $request Validated seller data
     * @param string $id Seller ID to update
     * @return \Illuminate\Http\JsonResponse Updated seller or error response
     * @throws SellerNotFoundException
     */
    public function update(UpdateSellerRequest $request, string $id)
    {
        try {
            $seller = Seller::find($id);

            if (!$seller) {
                throw new SellerNotFoundException((int) $id);
            }

            $validated = $request->validated();

            $seller->update($validated);

            return $this->success($seller->load('documents'), 'Seller updated successfully');
        } catch (SellerNotFoundException $e) {
            Log::warning('Seller not found: SellerController::update', [
                'method' => 'update',
                'seller_id' => $id,
                'message' => $e->getMessage(),
            ]);
            return $this->error($e->getMessage(), 404);
        } catch (\Exception $e) {
            Log::error('API Error: SellerController::update', [
                'method' => 'update',
                'seller_id' => $id,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            return $this->error('Failed to update seller', 500, $e->getMessage());
        }
    }

    /**
     * Remove the specified seller.
     *
     * @param string $id Seller ID to delete
     * @return \Illuminate\Http\JsonResponse Success or error response
     * @throws SellerNotFoundException
     */
    public function destroy(string $id)
    {
        try {
            $seller = Seller::find($id);

            if (!$seller) {
                throw new SellerNotFoundException((int) $id);
            }

            $seller->delete();

            return $this->success(null, 'Seller deleted successfully');
        } catch (SellerNotFoundException $e) {
            Log::warning('Seller not found: SellerController::destroy', [
                'method' => 'destroy',
                'seller_id' => $id,
                'message' => $e->getMessage(),
            ]);
            return $this->error($e->getMessage(), 404);
        } catch (\Exception $e) {
            Log::error('API Error: SellerController::destroy', [
                'method' => 'destroy',
                'seller_id' => $id,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            return $this->error('Failed to delete seller', 500, $e->getMessage());
        }
    }
}
