<?php

namespace App\Repositories\Eloquent;

use App\Models\Item;
use App\Repositories\Contracts\ItemRepository as ItemRepositoryContract;
use Illuminate\Database\Eloquent\Collection;

/** All item database queries (always includes eager loading) */
class ItemRepository implements ItemRepositoryContract
{
    /** Get all items ordered by name */
    public function getAll(): Collection
    {
        return Item::orderBy('name')->get();
    }

    /** Get item by ID */
    public function getById(int $id)
    {
        return Item::find($id);
    }

    /** Get all items with sale counts (efficient: single query + aggregate) */
    public function getAllWithSaleCount(): Collection
    {
        return Item::withCount('sales')  // ✅ Efficient: Single query + aggregate
            ->orderBy('name')
            ->get();
    }

    /** Get items by category */
    public function getByCategory(string $category): Collection
    {
        return Item::where('category', $category)
            ->orderBy('name')
            ->get();
    }

    /** Count total items */
    public function count(): int
    {
        return Item::count();
    }
}
