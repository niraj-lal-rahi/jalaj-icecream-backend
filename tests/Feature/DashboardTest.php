<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Item;
use App\Models\Sale;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test authenticated user can retrieve dashboard metrics
     */
    public function test_authenticated_user_can_retrieve_dashboard_metrics(): void
    {
        // Arrange
        $seller = Seller::factory()->create();
        $item = Item::factory()->create(['price' => 100]);
        Sale::factory()->create([
            'seller_id' => $seller->id,
            'item_id' => $item->id,
            'pick' => 10,
            'returned' => 0,
            'date' => now()->toDateString(),
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('message', 'Dashboard data retrieved successfully');
    }

    /**
     * Test dashboard contains all required metrics
     */
    public function test_dashboard_contains_all_required_metrics(): void
    {
        // Arrange
        $seller = Seller::factory()->create();
        $item = Item::factory()->create();
        Sale::factory()->create([
            'seller_id' => $seller->id,
            'item_id' => $item->id,
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'todayTotal',
                'yesterdayTotal',
                'yesterdayOwnerShare',
                'yesterdaySellerShare',
                'monthlyTotal',
                'grandTotal',
                'ownerEarning',
                'sellerEarning',
                'redFlagCount',
                'sellerCount',
                'itemCount',
                'transactionCount',
                'daysWithSales',
            ]
        ]);
    }

    /**
     * Test today's sales total is calculated correctly
     */
    public function test_todays_sales_total_is_calculated_correctly(): void
    {
        // Arrange
        $seller = Seller::factory()->create();
        $item = Item::factory()->create(['price' => 100]);

        // Create today's sales: 10 + 5 = 1500 total
        Sale::factory()->create([
            'seller_id' => $seller->id,
            'item_id' => $item->id,
            'pick' => 10,
            'returned' => 0,
            'date' => now()->toDateString(),
        ]);
        Sale::factory()->create([
            'seller_id' => $seller->id,
            'item_id' => $item->id,
            'pick' => 5,
            'returned' => 0,
            'date' => now()->toDateString(),
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard');

        // Assert
        $response->assertJsonPath('data.todayTotal', 1500);
    }

    /**
     * Test yesterday's sales total is calculated correctly
     */
    public function test_yesterdays_sales_total_is_calculated_correctly(): void
    {
        // Arrange
        $seller = Seller::factory()->create();
        $item = Item::factory()->create(['price' => 100]);

        Sale::factory()->create([
            'seller_id' => $seller->id,
            'item_id' => $item->id,
            'pick' => 8,
            'returned' => 0,
            'date' => now()->subDay()->toDateString(),
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard');

        // Assert
        $response->assertJsonPath('data.yesterdayTotal', 800);
    }

    /**
     * Test returned units are correctly deducted from sales total
     */
    public function test_returned_units_are_deducted_from_total(): void
    {
        // Arrange
        $seller = Seller::factory()->create();
        $item = Item::factory()->create(['price' => 100]);

        // Pick 10, returned 3 = 700 total
        Sale::factory()->create([
            'seller_id' => $seller->id,
            'item_id' => $item->id,
            'pick' => 10,
            'returned' => 3,
            'date' => now()->toDateString(),
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard');

        // Assert
        $response->assertJsonPath('data.todayTotal', 700);
    }

    /**
     * Test custom price overrides item price in calculation
     */
    public function test_custom_price_overrides_item_price(): void
    {
        // Arrange
        $seller = Seller::factory()->create();
        $item = Item::factory()->create(['price' => 100]);

        Sale::factory()->create([
            'seller_id' => $seller->id,
            'item_id' => $item->id,
            'pick' => 10,
            'returned' => 0,
            'custom_price' => 200, // Override default price
            'date' => now()->toDateString(),
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard');

        // Assert
        $response->assertJsonPath('data.todayTotal', 2000); // 10 * 200, not 10 * 100
    }

    /**
     * Test grand total includes all time sales
     */
    public function test_grand_total_includes_all_sales(): void
    {
        // Arrange
        $seller = Seller::factory()->create();
        $item = Item::factory()->create(['price' => 100]);

        Sale::factory()->create([
            'seller_id' => $seller->id,
            'item_id' => $item->id,
            'pick' => 5,
            'returned' => 0,
            'date' => '2024-01-01',
        ]);
        Sale::factory()->create([
            'seller_id' => $seller->id,
            'item_id' => $item->id,
            'pick' => 10,
            'returned' => 0,
            'date' => '2024-02-01',
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard');

        // Assert
        $response->assertJsonPath('data.grandTotal', 1500); // 500 + 1000
    }

    /**
     * Test owner and seller earnings are calculated correctly
     */
    public function test_owner_and_seller_earnings_are_calculated_correctly(): void
    {
        // Arrange
        $seller = Seller::factory()->create();
        $item = Item::factory()->create(['price' => 100]);

        Sale::factory()->create([
            'seller_id' => $seller->id,
            'item_id' => $item->id,
            'pick' => 10,
            'returned' => 0,
            'date' => '2024-01-01',
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard');

        // Assert
        $response->assertJsonPath('data.grandTotal', 1000);
        $response->assertJsonPath('data.ownerEarning', 600); // 60% of 1000
        $response->assertJsonPath('data.sellerEarning', 400); // 40% of 1000
    }

    /**
     * Test yesterday's owner and seller shares
     */
    public function test_yesterdays_owner_and_seller_shares(): void
    {
        // Arrange
        $seller = Seller::factory()->create();
        $item = Item::factory()->create(['price' => 100]);

        Sale::factory()->create([
            'seller_id' => $seller->id,
            'item_id' => $item->id,
            'pick' => 10,
            'returned' => 0,
            'date' => now()->subDay()->toDateString(),
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard');

        // Assert
        $response->assertJsonPath('data.yesterdayTotal', 1000);
        $response->assertJsonPath('data.yesterdayOwnerShare', 600);
        $response->assertJsonPath('data.yesterdaySellerShare', 400);
    }

    /**
     * Test red flag count is accurate
     */
    public function test_red_flag_count_is_accurate(): void
    {
        // Arrange
        $seller = Seller::factory()->create();
        $item = Item::factory()->create();

        Sale::factory()->create([
            'seller_id' => $seller->id,
            'item_id' => $item->id,
            'red_flag' => true,
        ]);
        Sale::factory()->create([
            'seller_id' => $seller->id,
            'item_id' => $item->id,
            'red_flag' => true,
        ]);
        Sale::factory()->create([
            'seller_id' => $seller->id,
            'item_id' => $item->id,
            'red_flag' => false,
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard');

        // Assert
        $response->assertJsonPath('data.redFlagCount', 2);
    }

    /**
     * Test seller count is accurate
     */
    public function test_seller_count_is_accurate(): void
    {
        // Arrange
        Seller::factory()->count(5)->create();

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard');

        // Assert
        $response->assertJsonPath('data.sellerCount', 5);
    }

    /**
     * Test item count is accurate
     */
    public function test_item_count_is_accurate(): void
    {
        // Arrange
        Item::factory()->count(8)->create();

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard');

        // Assert
        $response->assertJsonPath('data.itemCount', 8);
    }

    /**
     * Test days with sales is accurate
     */
    public function test_days_with_sales_is_accurate(): void
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
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard');

        // Assert
        $response->assertJsonPath('data.daysWithSales', 3);
    }

    /**
     * Test unauthenticated user cannot access dashboard
     */
    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        // Act
        $response = $this->getJson('/api/dashboard');

        // Assert
        $response->assertStatus(401);
    }

    /**
     * Test dashboard works with no sales data
     */
    public function test_dashboard_works_with_no_sales(): void
    {
        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.todayTotal', 0);
        $response->assertJsonPath('data.grandTotal', 0);
        $response->assertJsonPath('data.ownerEarning', 0);
        $response->assertJsonPath('data.sellerEarning', 0);
    }

    /**
     * Test monthly sales are filtered by current month
     */
    public function test_monthly_sales_are_filtered_by_current_month(): void
    {
        // Arrange
        $seller = Seller::factory()->create();
        $item = Item::factory()->create(['price' => 100]);

        // Current month sale
        Sale::factory()->create([
            'seller_id' => $seller->id,
            'item_id' => $item->id,
            'pick' => 10,
            'returned' => 0,
            'date' => now()->toDateString(),
        ]);

        // Previous month sale
        Sale::factory()->create([
            'seller_id' => $seller->id,
            'item_id' => $item->id,
            'pick' => 10,
            'returned' => 0,
            'date' => now()->subMonth()->toDateString(),
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard');

        // Assert - monthlyTotal should only include current month
        $response->assertJsonPath('data.monthlyTotal', 1000);
    }

    /**
     * Test red flag sales endpoint
     */
    public function test_authenticated_user_can_retrieve_red_flag_sales(): void
    {
        // Arrange
        $seller = Seller::factory()->create();
        $item = Item::factory()->create();

        Sale::factory()->create([
            'seller_id' => $seller->id,
            'item_id' => $item->id,
            'red_flag' => true,
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard/red-flags');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
    }

    /**
     * Test red flag sales includes seller and item details
     */
    public function test_red_flag_sales_includes_seller_and_item_details(): void
    {
        // Arrange
        $seller = Seller::factory()->create(['name' => 'Test Seller']);
        $item = Item::factory()->create(['name' => 'Test Item']);

        Sale::factory()->create([
            'seller_id' => $seller->id,
            'item_id' => $item->id,
            'red_flag' => true,
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/dashboard/red-flags');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
    }
}
