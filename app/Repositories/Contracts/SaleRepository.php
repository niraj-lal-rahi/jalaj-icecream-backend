<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

/** SaleRepository contract - defines all Sale database operations */
interface SaleRepository
{
    /** Get all sales with relationships eager-loaded */
    public function getAll(): Collection;

    /** Get sales by date range */
    public function getByDateRange($from, $to): Collection;

    /** Get sales for specific seller on a date */
    public function getBySellerOnDate(int $sellerId, $date): Collection;

    /** Get sales by seller across date range */
    public function getBySellerDateRange(int $sellerId, $from, $to): Collection;

    /** Get top sales by amount */
    public function getTopByAmount(int $limit = 10): Collection;

    /** Count sales in date range */
    public function countByDateRange($from, $to): int;

    /** Calculate total sale amount in date range */
    public function getTotalAmountByDateRange($from, $to): float;

    /** Get sales by date (with relationships) */
    public function getByDate($date): Collection;

    /** Get sales grouped by date (for dashboard statistics) */
    public function getGroupedByDate($from, $to): array;
}
