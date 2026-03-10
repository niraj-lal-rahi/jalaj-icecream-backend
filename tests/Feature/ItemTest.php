<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ItemTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test authenticated user can retrieve all items
     */
    public function test_authenticated_user_can_retrieve_all_items(): void
    {
        // Arrange
        Item::factory()->count(5)->create();

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/items');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('message', 'Items retrieved successfully');
    }

    /**
     * Test authenticated user can create item with valid data
     */
    public function test_authenticated_user_can_create_item_with_valid_data(): void
    {
        // Arrange
        $itemData = [
            'name' => 'Vanilla Ice Cream',
            'price' => 100,
            'order_by' => 1,
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/items', $itemData);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('message', 'Item created successfully');
        $this->assertDatabaseHas('items', $itemData);
    }

    /**
     * Test item creation fails with missing required fields
     */
    public function test_item_creation_fails_with_missing_required_fields(): void
    {
        // Arrange
        $itemData = [
            'name' => 'Vanilla Ice Cream',
            // Missing price and order_by
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/items', $itemData);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('message', 'Validation failed');
        $response->assertJsonPath('errors.price', ['The price field is required.']);
        $response->assertJsonPath('errors.order_by', ['The order by field is required.']);
    }

    /**
     * Test item creation fails with invalid price
     */
    public function test_item_creation_fails_with_invalid_price(): void
    {
        // Arrange
        $itemData = [
            'name' => 'Vanilla Ice Cream',
            'price' => -100, // Invalid: price must be >= 0
            'order_by' => 1,
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/items', $itemData);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('errors.price.0', 'The price must be at least 0.');
    }

    /**
     * Test authenticated user can retrieve single item
     */
    public function test_authenticated_user_can_retrieve_single_item(): void
    {
        // Arrange
        $item = Item::factory()->create(['name' => 'Chocolate Ice Cream']);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/items/{$item->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('data.id', $item->id);
        $response->assertJsonPath('data.name', 'Chocolate Ice Cream');
    }

    /**
     * Test retrieving non-existent item returns 404
     */
    public function test_retrieving_non_existent_item_returns_404(): void
    {
        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/items/99999');

        // Assert
        $response->assertStatus(404);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('message', 'Item with ID 99999 not found');
    }

    /**
     * Test authenticated user can update item with valid data
     */
    public function test_authenticated_user_can_update_item_with_valid_data(): void
    {
        // Arrange
        $item = Item::factory()->create(['name' => 'Old Name', 'price' => 50]);
        $updateData = [
            'name' => 'Updated Item Name',
            'price' => 150,
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/items/{$item->id}", $updateData);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('message', 'Item updated successfully');
        $response->assertJsonPath('data.name', 'Updated Item Name');
        $response->assertJsonPath('data.price', 150);
    }

    /**
     * Test item update fails with invalid data
     */
    public function test_item_update_fails_with_invalid_data(): void
    {
        // Arrange
        $item = Item::factory()->create();
        $updateData = [
            'price' => -50, // Invalid: price must be >= 0
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/items/{$item->id}", $updateData);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('errors.price.0', 'The price must be at least 0.');
    }

    /**
     * Test authenticated user can delete item
     */
    public function test_authenticated_user_can_delete_item(): void
    {
        // Arrange
        $item = Item::factory()->create(['name' => 'Item to Delete']);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/items/{$item->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('message', 'Item deleted successfully');
        $this->assertDatabaseMissing('items', ['id' => $item->id]);
    }

    /**
     * Test deleting non-existent item returns 404
     */
    public function test_deleting_non_existent_item_returns_404(): void
    {
        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/items/99999');

        // Assert
        $response->assertStatus(404);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('message', 'Item with ID 99999 not found');
    }

    /**
     * Test unauthenticated user cannot access items
     */
    public function test_unauthenticated_user_cannot_access_items(): void
    {
        // Act
        $response = $this->getJson('/api/items');

        // Assert
        $response->assertStatus(401);
    }

    /**
     * Test item name validation - max length
     */
    public function test_item_name_validation_max_length(): void
    {
        // Arrange
        $itemData = [
            'name' => str_repeat('a', 251), // Exceeds max:250
            'price' => 100,
            'order_by' => 1,
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/items', $itemData);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonPathExists('errors.name');
    }

    /**
     * Test order_by validation - must be integer
     */
    public function test_order_by_validation_must_be_integer(): void
    {
        // Arrange
        $itemData = [
            'name' => 'Test Item',
            'price' => 100,
            'order_by' => 'not_an_integer',
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/items', $itemData);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonPathExists('errors.order_by');
    }
}
