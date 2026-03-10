<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\SellerPerformanceService;
use App\Models\Sale;
use App\Models\Seller;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SellerPerformanceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SellerPerformanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SellerPerformanceService();
    }

    /**
     * Test service calculates all seller performance returns collection
     */
    public function test_calculate_all_seller_performance_returns_collection(): void
    {
        // Arrange
        $sellers = Seller::factory()->count(3)->create();
        $item = Item::factory()->create(['price' => 100]);

        foreach ($sellers as $seller) {
            Sale::factory()->count(2)->create([
                'seller_id' => $seller->id,
                'item_id' => $item->id,
                'pick' => 10,
                'returned' => 0,
            ]);
        }

        // Act
        $result = $this->service->calculateAllSellerPerformance();

        // Assert
        $this->assertCount(3, $result);
        $this->assertIsArray($result[0]);
        $this->assertArrayHasKey('performanceScore', $result[0]);
        $this->assertArrayHasKey('id', $result[0]);
        $this->assertArrayHasKey('name', $result[0]);
    }

    /**
     * Test service returns results sorted by performance score descending
     */
    public function test_calculate_all_seller_performance_returns_sorted_results(): void
    {
        // Arrange
        $seller1 = Seller::factory()->create();
        $seller2 = Seller::factory()->create();
        $seller3 = Seller::factory()->create();
        $item = Item::factory()->create(['price' => 100]);

        // Seller 1: 10 units sold
        Sale::factory()->count(2)->create([
            'seller_id' => $seller1->id,
            'item_id' => $item->id,
            'pick' => 5,
            'returned' => 0,
        ]);

        // Seller 2: 20 units sold
        Sale::factory()->count(4)->create([
            'seller_id' => $seller2->id,
            'item_id' => $item->id,
            'pick' => 5,
            'returned' => 0,
        ]);

        // Seller 3: 5 units sold
        Sale::factory()->count(1)->create([
            'seller_id' => $seller3->id,
            'item_id' => $item->id,
            'pick' => 5,
            'returned' => 0,
        ]);

        // Act
        $result = $this->service->calculateAllSellerPerformance();

        // Assert - Should be sorted by performance score descending
        $this->assertTrue(
            $result[0]['performanceScore'] >= $result[1]['performanceScore'],
            'First seller should have higher or equal performance score than second'
        );
        $this->assertTrue(
            $result[1]['performanceScore'] >= $result[2]['performanceScore'],
            'Second seller should have higher or equal performance score than third'
        );
    }

    /**
     * Test get top performers returns correct number of results
     */
    public function test_get_top_performers_returns_correct_number_of_results(): void
    {
        // Arrange
        $sellers = Seller::factory()->count(5)->create();
        $item = Item::factory()->create(['price' => 100]);

        foreach ($sellers as $seller) {
            Sale::factory()->create([
                'seller_id' => $seller->id,
                'item_id' => $item->id,
                'pick' => 10,
                'returned' => 0,
            ]);
        }

        // Act
        $topPerformers = $this->service->getTopPerformers(3);

        // Assert
        $this->assertCount(3, $topPerformers);
    }

    /**
     * Test get top performers returns default limit of 3
     */
    public function test_get_top_performers_returns_default_limit_of_three(): void
    {
        // Arrange
        $sellers = Seller::factory()->count(5)->create();
        $item = Item::factory()->create(['price' => 100]);

        foreach ($sellers as $seller) {
            Sale::factory()->create([
                'seller_id' => $seller->id,
                'item_id' => $item->id,
                'pick' => 10,
                'returned' => 0,
            ]);
        }

        // Act
        $topPerformers = $this->service->getTopPerformers();

        // Assert
        $this->assertCount(3, $topPerformers);
    }

    /**
     * Test service calculates total sales amount correctly
     */
    public function test_service_calculates_total_sales_amount_correctly(): void
    {
        // Arrange
        $seller = Seller::factory()->create();
        $item = Item::factory()->create(['price' => 100]);

        // Create 3 sales: 10 units, 5 units, 2 units = 1700 total
        Sale::factory()->create([
            'seller_id' => $seller->id,
            'item_id' => $item->id,
            'pick' => 10,
            'returned' => 0,
        ]);
        Sale::factory()->create([
            'seller_id' => $seller->id,
            'item_id' => $item->id,
            'pick' => 5,
            'returned' => 0,
        ]);
        Sale::factory()->create([
            'seller_id' => $seller->id,
            'item_id' => $item->id,
            'pick' => 2,
            'returned' => 0,
        ]);

        // Act
        $result = $this->service->calculateAllSellerPerformance();

        // Assert
        $sellerMetrics = $result->firstWhere('id', $seller->id);
        $this->assertEquals(1700, $sellerMetrics['totalSalesAmount']);
    }

    /**
     * Test service calculates returned units correctly
     */
    public function test_service_calculates_returned_units_correctly(): void
    {
        // Arrange
        $seller = Seller::factory()->create();
        $item = Item::factory()->create(['price' => 100]);

        // Sales: pick=10, returned=2 = 800 (net 8 units)
        Sale::factory()->create([
            'seller_id' => $seller->id,
            'item_id' => $item->id,
            'pick' => 10,
            'returned' => 2,
        ]);

        // Act
        $result = $this->service->calculateAllSellerPerformance();

        // Assert
        $sellerMetrics = $result->firstWhere('id', $seller->id);
        $this->assertEquals(800, $sellerMetrics['totalSalesAmount']);
    }

    /**
     * Test service respects custom price override
     */
    public function test_service_respects_custom_price_override(): void
    {
        // Arrange
        $seller = Seller::factory()->create();
        $item = Item::factory()->create(['price' => 100]);

        // Sale with custom price 200 instead of item price 100
        Sale::factory()->create([
            'seller_id' => $seller->id,
            'item_id' => $item->id,
            'pick' => 5,
            'returned' => 0,
            'custom_price' => 200,
        ]);

        // Act
        $result = $this->service->calculateAllSellerPerformance();

        // Assert
        $sellerMetrics = $result->firstWhere('id', $seller->id);
        $this->assertEquals(1000, $sellerMetrics['totalSalesAmount']); // 5 * 200
    }

    /**
     * Test service calculates owner and seller shares correctly
     */
    public function test_service_calculates_profit_shares_correctly(): void
    {
        // Arrange
        $seller = Seller::factory()->create();
        $item = Item::factory()->create(['price' => 100]);

        // Total sales amount = 1000
        Sale::factory()->create([
            'seller_id' => $seller->id,
            'item_id' => $item->id,
            'pick' => 10,
            'returned' => 0,
        ]);

        // Act
        $result = $this->service->calculateAllSellerPerformance();

        // Assert
        $sellerMetrics = $result->firstWhere('id', $seller->id);
        $this->assertEquals(600, $sellerMetrics['ownerShare']); // 1000 * 0.6
        $this->assertEquals(400, $sellerMetrics['sellerShare']); // 1000 * 0.4
    }

    /**
     * Test service counts days with sales correctly
     */
    public function test_service_counts_days_with_sales_correctly(): void
    {
        // Arrange
        $seller = Seller::factory()->create();
        $item = Item::factory()->create();

        // Create sales on 3 different dates
        Sale::factory()->create([
            'seller_id' => $seller->id,
            'item_id' => $item->id,
            'date' => '2024-01-01',
        ]);
        Sale::factory()->create([
            'seller_id' => $seller->id,
            'item_id' => $item->id,
            'date' => '2024-01-02',
        ]);
        Sale::factory()->create([
            'seller_id' => $seller->id,
            'item_id' => $item->id,
            'date' => '2024-01-03',
        ]);

        // Act
        $result = $this->service->calculateAllSellerPerformance();

        // Assert
        $sellerMetrics = $result->firstWhere('id', $seller->id);
        $this->assertEquals(3, $sellerMetrics['daysWithSales']);
    }

    /**
     * Test service calculates absent days correctly
     */
    public function test_service_calculates_absent_days_correctly(): void
    {
        // Arrange
        $seller1 = Seller::factory()->create();
        $seller2 = Seller::factory()->create();
        $item = Item::factory()->create();

        // Seller 1: sales on dates 1 and 2
        Sale::factory()->create([
            'seller_id' => $seller1->id,
            'item_id' => $item->id,
            'date' => '2024-01-01',
        ]);
        Sale::factory()->create([
            'seller_id' => $seller1->id,
            'item_id' => $item->id,
            'date' => '2024-01-02',
        ]);

        // Seller 2: sales on all 3 dates
        Sale::factory()->create([
            'seller_id' => $seller2->id,
            'item_id' => $item->id,
            'date' => '2024-01-01',
        ]);
        Sale::factory()->create([
            'seller_id' => $seller2->id,
            'item_id' => $item->id,
            'date' => '2024-01-02',
        ]);
        Sale::factory()->create([
            'seller_id' => $seller2->id,
            'item_id' => $item->id,
            'date' => '2024-01-03',
        ]);

        // Act
        $result = $this->service->calculateAllSellerPerformance();

        // Assert
        $seller1Metrics = $result->firstWhere('id', $seller1->id);
        $seller2Metrics = $result->firstWhere('id', $seller2->id);

        $this->assertEquals(1, $seller1Metrics['absentDays']);
        $this->assertEquals(0, $seller2Metrics['absentDays']);
    }

    /**
     * Test service handles no sales gracefully
     */
    public function test_service_handles_sellers_with_no_sales(): void
    {
        // Arrange
        $seller1 = Seller::factory()->create();
        $seller2 = Seller::factory()->create();
        $item = Item::factory()->create();

        // Only seller 1 has sales
        Sale::factory()->create([
            'seller_id' => $seller1->id,
            'item_id' => $item->id,
        ]);

        // Act
        $result = $this->service->calculateAllSellerPerformance();

        // Assert
        $this->assertCount(2, $result);
        $seller2Metrics = $result->firstWhere('id', $seller2->id);
        $this->assertEquals(0, $seller2Metrics['totalSalesAmount']);
        $this->assertEquals(0, $seller2Metrics['daysWithSales']);
    }

    /**
     * Test performance scores are numeric and in reasonable range
     */
    public function test_performance_scores_are_numeric(): void
    {
        // Arrange
        $sellers = Seller::factory()->count(3)->create();
        $item = Item::factory()->create();

        foreach ($sellers as $seller) {
            Sale::factory()->create([
                'seller_id' => $seller->id,
                'item_id' => $item->id,
            ]);
        }

        // Act
        $result = $this->service->calculateAllSellerPerformance();

        // Assert
        foreach ($result as $metrics) {
            $this->assertIsNumeric($metrics['performanceScore']);
            $this->assertIsNumeric($metrics['volumeScore']);
            $this->assertIsNumeric($metrics['consistencyScore']);
            $this->assertGreaterThanOrEqual(0, $metrics['performanceScore']);
        }
    }

    /**
     * Test method returns seller metrics with all required keys
     */
    public function test_seller_metrics_contain_all_required_keys(): void
    {
        // Arrange
        $seller = Seller::factory()->create();
        $item = Item::factory()->create();
        Sale::factory()->create([
            'seller_id' => $seller->id,
            'item_id' => $item->id,
        ]);

        // Act
        $result = $this->service->calculateAllSellerPerformance();
        $metrics = $result[0];

        // Assert
        $requiredKeys = [
            'id',
            'name',
            'number',
            'totalSalesAmount',
            'ownerShare',
            'sellerShare',
            'daysWithSales',
            'absentDays',
            'totalDays',
            'presentDates',
            'absentDates',
            'volumeScore',
            'consistencyScore',
            'performanceScore',
        ];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $metrics, "Missing key: {$key}");
        }
    }
}
