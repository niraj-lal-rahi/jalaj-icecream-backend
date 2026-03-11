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
     * Used in: SellerPerformanceService::calculatePerformanceScore()
     * Formula: (volumeScore * volume_weight) + (consistencyScore * consistency_weight)
     */
    'volume_weight' => (float) env('PERFORMANCE_VOLUME_WEIGHT', 0.5),
    'consistency_weight' => (float) env('PERFORMANCE_CONSISTENCY_WEIGHT', 0.5),

    /**
     * Score Precision: Decimal places for performance scores
     * Example: score_precision = 2 → 85.50
     * Used in: SellerPerformanceService when rounding final scores
     */
    'score_precision' => (int) env('PERFORMANCE_SCORE_PRECISION', 2),

    /**
     * Top Performers Limit
     * How many top sellers to return in dashboard/reports
     */
    'top_performers_limit' => (int) env('TOP_PERFORMERS_LIMIT', 3),

    /**
     * Recent Activity Window (in days)
     * Used to filter "recent" transactions in reports
     */
    'recent_days_window' => (int) env('RECENT_DAYS_WINDOW', 7),

    /**
     * Minimum Sales Threshold
     * Sellers must have at least this many sales to be considered "active"
     */
    'minimum_sales_threshold' => (int) env('MINIMUM_SALES_THRESHOLD', 1),

    /**
     * Red Flag Thresholds
     * Values that indicate a seller needs attention
     */
    'red_flags' => [
        'no_sales_days' => (int) env('NO_SALES_DAYS_THRESHOLD', 3),
        'low_consistency' => (float) env('LOW_CONSISTENCY_SCORE', 0.3),
    ],
];