<?php

/**
 * Pagination Configuration
 *
 * Defines default pagination limits for API endpoints.
 * NEVER hardcode pagination limits in controllers.
 *
 * Usage: $items->paginate(config('pagination.limits.sales'))
 */

return [
    /**
     * Default Pagination Limits
     *
     * Per-endpoint pagination defaults for list operations.
     * Adjust based on:
     * - Data size (larger datasets need smaller limits)
     * - Network conditions (mobile vs web)
     * - Performance requirements
     */
    'limits' => [
        /**
         * Default limit for unlisted endpoints
         * Used as fallback when specific limit not found
         */
        'default' => (int) env('PAGINATION_DEFAULT', 20),

        /**
         * Sales endpoint pagination
         * Typically larger due to high-volume data
         */
        'sales' => (int) env('PAGINATION_SALES', 50),

        /**
         * Items/Products pagination
         * Medium size - users typically browse items
         */
        'items' => (int) env('PAGINATION_ITEMS', 30),

        /**
         * Sellers/Users pagination
         * Typically smaller - fewer sellers than sales
         */
        'sellers' => (int) env('PAGINATION_SELLERS', 25),

        /**
         * Dashboard reports pagination
         * Usually small for quick overview
         */
        'reports' => (int) env('PAGINATION_REPORTS', 15),
    ],

    /**
     * Maximum Allowed Limit
     * Security measure: Prevent users from requesting huge datasets
     *
     * Usage: $limit = min($request->limit, config('pagination.max_limit'))
     */
    'max_limit' => (int) env('PAGINATION_MAX_LIMIT', 100),

    /**
     * Page Parameter Name
     * Default: 'page' (Laravel convention)
     */
    'page_parameter' => 'page',

    /**
     * Limit Parameter Name
     * Default: 'limit' (common REST convention)
     */
    'limit_parameter' => 'limit',
];
