<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

/** ItemRepository contract - defines all Item database operations */
interface ItemRepository
{
    /** Get all items ordered by name */
    public function getAll(): Collection;

    /** Get item by ID */
    public function getById(int $id);

    /** Get all items with sale counts */
    public function getAllWithSaleCount(): Collection;

    /** Get items by category */
    public function getByCategory(string $category): Collection;

    /** Count total items */
    public function count(): int;
}
