<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Item;
use App\Models\Sale;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SaleTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Seller $seller;
    protected Item $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->seller = Seller::factory()->create();
        $this->item = Item::factory()->create();
    }

    /**
     * Test authenticated user can retrieve all sales
     */
    public function test_authenticated_user_can_retrieve_all_sales(): void
    {
        // Arrange
        Sale::factory()->count(5)->create([
            'seller_id' => $this->seller->id,
            'item_id' => $this->item->id,
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/sales');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('message', 'Sales retrieved successfully');
    }

    /**
     * Test authenticated user can create sale with valid data
     */
    public function test_authenticated_user_can_create_sale_with_valid_data(): void
    {
        // Arrange
        $saleData = [
            'seller_id' => $this->seller->id,
            'item_id' => $this->item->id,
            'pick' => 10,
            'date' => now()->toDateString(),
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sales', $saleData);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('message', 'Sale created successfully');
        $this->assertDatabaseHas('sales', [
            'seller_id' => $this->seller->id,
            'item_id' => $this->item->id,
            'pick' => 10,
        ]);
    }

    /**
     * Test sale creation fails with missing required fields
     */
    public function test_sale_creation_fails_with_missing_required_fields(): void
    {
        // Arrange
        $saleData = [
            'seller_id' => $this->seller->id,
            // Missing item_id, pick, and date
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sales', $saleData);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonPath('status', false);
        $response->assertJsonPathExists('errors.item_id');
        $response->assertJsonPathExists('errors.pick');
        $response->assertJsonPathExists('errors.date');
    }

    /**
     * Test sale creation fails with non-existent seller
     */
    public function test_sale_creation_fails_with_non_existent_seller(): void
    {
        // Arrange
        $saleData = [
            'seller_id' => 99999, // Non-existent seller
            'item_id' => $this->item->id,
            'pick' => 10,
            'date' => now()->toDateString(),
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sales', $saleData);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('errors.seller_id.0', 'The selected seller_id is invalid.');
    }

    /**
     * Test sale creation fails with non-existent item
     */
    public function test_sale_creation_fails_with_non_existent_item(): void
    {
        // Arrange
        $saleData = [
            'seller_id' => $this->seller->id,
            'item_id' => 99999, // Non-existent item
            'pick' => 10,
            'date' => now()->toDateString(),
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sales', $saleData);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('errors.item_id.0', 'The selected item_id is invalid.');
    }

    /**
     * Test sale creation fails when duplicate sale already exists
     */
    public function test_sale_creation_fails_when_duplicate_exists(): void
    {
        // Arrange
        $date = now()->toDateString();
        Sale::factory()->create([
            'seller_id' => $this->seller->id,
            'item_id' => $this->item->id,
            'date' => $date,
        ]);

        $saleData = [
            'seller_id' => $this->seller->id,
            'item_id' => $this->item->id,
            'pick' => 10,
            'date' => $date,
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sales', $saleData);

        // Assert
        $response->assertStatus(409);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('message', 'Sale already exists for this seller on this date for this item');
    }

    /**
     * Test authenticated user can retrieve single sale
     */
    public function test_authenticated_user_can_retrieve_single_sale(): void
    {
        // Arrange
        $sale = Sale::factory()->create([
            'seller_id' => $this->seller->id,
            'item_id' => $this->item->id,
            'pick' => 15,
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/sales/{$sale->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('data.id', $sale->id);
        $response->assertJsonPath('data.pick', 15);
    }

    /**
     * Test retrieving non-existent sale returns 404
     */
    public function test_retrieving_non_existent_sale_returns_404(): void
    {
        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/sales/99999');

        // Assert
        $response->assertStatus(404);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('message', 'Sale with ID 99999 not found');
    }

    /**
     * Test authenticated user can update sale with valid data
     */
    public function test_authenticated_user_can_update_sale_with_valid_data(): void
    {
        // Arrange
        $sale = Sale::factory()->create([
            'seller_id' => $this->seller->id,
            'item_id' => $this->item->id,
            'pick' => 10,
            'returned' => 0,
        ]);

        $updateData = [
            'pick' => 20,
            'returned' => 5,
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/sales/{$sale->id}", $updateData);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('message', 'Sale updated successfully');
        $response->assertJsonPath('data.pick', 20);
        $response->assertJsonPath('data.returned', 5);
    }

    /**
     * Test sale update fails with invalid data
     */
    public function test_sale_update_fails_with_invalid_data(): void
    {
        // Arrange
        $sale = Sale::factory()->create([
            'seller_id' => $this->seller->id,
            'item_id' => $this->item->id,
        ]);

        $updateData = [
            'pick' => -10, // Invalid: must be >= 0
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/sales/{$sale->id}", $updateData);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonPathExists('errors.pick');
    }

    /**
     * Test authenticated user can delete sale
     */
    public function test_authenticated_user_can_delete_sale(): void
    {
        // Arrange
        $sale = Sale::factory()->create([
            'seller_id' => $this->seller->id,
            'item_id' => $this->item->id,
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/sales/{$sale->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('message', 'Sale deleted successfully');
        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
    }

    /**
     * Test deleting non-existent sale returns 404
     */
    public function test_deleting_non_existent_sale_returns_404(): void
    {
        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/sales/99999');

        // Assert
        $response->assertStatus(404);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('message', 'Sale with ID 99999 not found');
    }

    /**
     * Test unauthenticated user cannot access sales
     */
    public function test_unauthenticated_user_cannot_access_sales(): void
    {
        // Act
        $response = $this->getJson('/api/sales');

        // Assert
        $response->assertStatus(401);
    }

    /**
     * Test pick quantity validation
     */
    public function test_pick_quantity_validation(): void
    {
        // Arrange
        $saleData = [
            'seller_id' => $this->seller->id,
            'item_id' => $this->item->id,
            'pick' => 'invalid', // Should be integer
            'date' => now()->toDateString(),
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sales', $saleData);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonPathExists('errors.pick');
    }

    /**
     * Test sale listing can be filtered by seller_id
     */
    public function test_sale_listing_can_be_filtered_by_seller_id(): void
    {
        // Arrange
        $seller2 = Seller::factory()->create();
        Sale::factory()->count(3)->create(['seller_id' => $this->seller->id]);
        Sale::factory()->count(2)->create(['seller_id' => $seller2->id]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/sales?seller_id={$this->seller->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
    }
}
