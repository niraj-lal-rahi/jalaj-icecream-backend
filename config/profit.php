<?php

/**
 * Profit Share Configuration
 *
 * Defines how profits are distributed between owner and sellers.
 * These values are used throughout the application for calculating
 * commissions and financial reports.
 *
 * CRITICAL: These values MUST sum to 1.0
 * NEVER hardcode profit percentages in code - always use config()
 */

return [
    /**
     * Owner's share percentage of each sale
     * Used for: Revenue calculations, profit exports, financial reports
     * Default: 60% (owner profit margin + operational costs)
     */
    'owner_share' => (float) env('OWNER_SHARE_PERCENTAGE', 0.60),

    /**
     * Seller's commission percentage of each sale
     * Used for: Seller commission calculations, earnings reports
     * Default: 40% (seller commission for sales effort)
     */
    'seller_share' => (float) env('SELLER_SHARE_PERCENTAGE', 0.40),

    /**
     * Validation: Ensure shares sum to 1.0
     * This is enforced at boot time in AppServiceProvider
     */
];
