<?php

/**
 * Seller Performance Configuration
 *
 * Defines all thresholds, weights, and parameters used for calculating
 * seller performance scores in SellerPerformanceService.
 *
 * NEVER hardcode these values in services or controllers.
 */

return [
    /**
     * Performance Scoring Weights
     *
     * Used in: SellerPerformanceService::calculateAllSellerPerformance()
     *
     * - volume: Weight of sales volume (total number of sales)
     * - consistency: Weight of consistency (days with sales)
     *
     * These weights should sum to a meaningful total (usually 1.0 or 100)
     */
    'performance_weights' => [
        'volume' => (float) env('PERFORMANCE_VOLUME_WEIGHT', 0.5),
        'consistency' => (float) env('PERFORMANCE_CONSISTENCY_WEIGHT', 0.5),
    ],

    /**
     * Score Formatting: Decimal places for performance scores
     * Example: score_decimal_places = 2 → 85.50
     *
     * Used in: SellerPerformanceService when calculating final scores
     */
    'score_decimal_places' => (int) env('PERFORMANCE_SCORE_PRECISION', 2),

    /**
     * Top Performers Limit
     * How many top sellers to return in dashboard/reports
     *
     * Used in:
     * - Admin DashboardController::index()
     * - API DashboardController::index()
     * - Cache key operations
     */
    'top_performers_limit' => (int) env('TOP_PERFORMERS_LIMIT', 3),

    /**
     * Recent Activity Window (in days)
     * Used to filter "recent" transactions in reports
     *
     * Used in: Dashboard statistics, activity feeds
     */
    'recent_days_window' => (int) env('RECENT_DAYS_WINDOW', 7),

    /**
     * Minimum Sales Threshold
     * Sellers must have at least this many sales to be considered "active"
     *
     * Used in: Performance calculations, red flag detection
     */
    'minimum_sales_threshold' => (int) env('MINIMUM_SALES_THRESHOLD', 1),

    /**
     * Red Flag Thresholds
     * Values that indicate a seller needs attention (no sales in N days, etc.)
     */
    'red_flags' => [
        'no_sales_days' => (int) env('NO_SALES_DAYS_THRESHOLD', 3),
        'low_consistency' => (float) env('LOW_CONSISTENCY_SCORE', 0.3),
    ],
];
