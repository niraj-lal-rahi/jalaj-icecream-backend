<?php

/**
 * Cache Configuration
 *
 * Centralized cache settings for all business operations.
 * Durations in minutes (Laravel convention).
 *
 * All cache operations should use these config values,
 * NEVER hardcode cache durations or keys.
 */

return [
    /**
     * Cache Time-To-Live (TTL) in minutes
     *
     * Determines how long cache items persist before expiring.
     * Adjust based on data freshness requirements and performance needs.
     *
     * Formula: Cache::remember(key, minutes * 60, callback)
     * Example: 60 minutes = 3600 seconds
     */
    'ttl' => [
        /**
         * Top performers calculation cache
         * Used in: Dashboard controllers (admin & api)
         * Invalidated when: Sales created/updated/deleted
         */
        'top_performers' => (int) env('CACHE_TOP_PERFORMERS_MINUTES', 60),

        /**
         * Dashboard statistics cache (sales totals, counts, etc.)
         * Used in: Dashboard controllers
         * Invalidated when: Sales created/updated/deleted
         */
        'dashboard_stats' => (int) env('CACHE_DASHBOARD_STATS_MINUTES', 30),

        /**
         * Individual seller metrics cache
         * Used in: Seller profile, performance reports
         * Invalidated when: Specific seller's sales change
         */
        'seller_metrics' => (int) env('CACHE_SELLER_METRICS_MINUTES', 45),

        /**
         * Sales report cache
         * Used in: SalesReportExporter
         * Invalidated when: Any sale data changes
         */
        'sales_report' => (int) env('CACHE_SALES_REPORT_MINUTES', 120),
    ],

    /**
     * Cache Keys (Centralized)
     *
     * Use these constants to ensure consistency across the application.
     * Format: app_feature_identifier
     *
     * Usage: cache()->remember(config('cache.keys.top_performers'), ...)
     */
    'keys' => [
        'top_performers' => 'top_performers_cache',
        'dashboard_stats' => 'dashboard_stats',
        'seller_metrics_prefix' => 'seller_metrics_',
        'sales_report' => 'sales_report_cache',
    ],

    /**
     * Cache Invalidation Tags
     * Some caches should be invalidated together
     *
     * Used by: CacheInvalidationService
     */
    'tags' => [
        'sales' => 'cache_tag_sales',
        'sellers' => 'cache_tag_sellers',
        'statistics' => 'cache_tag_statistics',
    ],
];
