<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

/** SellerRepository contract - defines all Seller database operations */
interface SellerRepository
{
    /** Get all sellers with relationships */
    public function getAll(): Collection;

    /** Get seller by ID with relationships */
    public function getById(int $id);

    /** Get seller by phone number */
    public function getByPhone(string $phone);

    /** Get sellers with sale counts */
    public function getAllWithSaleCount(): Collection;

    /** Get sellers with sales in date range (with eager loading) */
    public function getWithSalesByDateRange($from, $to): Collection;

    /** Get seller sale statistics */
    public function getStatistics(int $sellerId, $from = null, $to = null): array;

    /** Count total sellers */
    public function count(): int;
}
