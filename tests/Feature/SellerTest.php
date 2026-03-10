<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SellerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test authenticated user can retrieve all sellers
     */
    public function test_authenticated_user_can_retrieve_all_sellers(): void
    {
        // Arrange
        Seller::factory()->count(5)->create();

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/sellers');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('message', 'Sellers retrieved successfully');
    }

    /**
     * Test authenticated user can create seller with valid data
     */
    public function test_authenticated_user_can_create_seller_with_valid_data(): void
    {
        // Arrange
        $sellerData = [
            'name' => 'John Doe Ice Cream Vendor',
            'number' => '9876543210',
            'address' => '123 Main Street, City, State',
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sellers', $sellerData);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('message', 'Seller created successfully');
        $this->assertDatabaseHas('sellers', $sellerData);
    }

    /**
     * Test seller creation fails with missing required fields
     */
    public function test_seller_creation_fails_with_missing_required_fields(): void
    {
        // Arrange
        $sellerData = [
            'name' => 'John Doe',
            // Missing number and address
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sellers', $sellerData);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonPath('status', false);
        $response->assertJsonPathExists('errors.number');
        $response->assertJsonPathExists('errors.address');
    }

    /**
     * Test seller creation fails with name exceeding max length
     */
    public function test_seller_creation_fails_with_name_exceeding_max_length(): void
    {
        // Arrange
        $sellerData = [
            'name' => str_repeat('a', 256), // Exceeds max:255
            'number' => '9876543210',
            'address' => 'Test Address',
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sellers', $sellerData);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('errors.name.0', 'The name may not be greater than 255 characters.');
    }

    /**
     * Test seller creation fails with phone number exceeding max length
     */
    public function test_seller_creation_fails_with_phone_exceeding_max_length(): void
    {
        // Arrange
        $sellerData = [
            'name' => 'John Doe',
            'number' => str_repeat('1', 21), // Exceeds max:20
            'address' => 'Test Address',
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sellers', $sellerData);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('errors.number.0', 'The number may not be greater than 20 characters.');
    }

    /**
     * Test authenticated user can retrieve single seller
     */
    public function test_authenticated_user_can_retrieve_single_seller(): void
    {
        // Arrange
        $seller = Seller::factory()->create(['name' => 'Test Seller']);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/sellers/{$seller->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('data.id', $seller->id);
        $response->assertJsonPath('data.name', 'Test Seller');
    }

    /**
     * Test retrieving non-existent seller returns 404
     */
    public function test_retrieving_non_existent_seller_returns_404(): void
    {
        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/sellers/99999');

        // Assert
        $response->assertStatus(404);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('message', 'Seller with ID 99999 not found');
    }

    /**
     * Test authenticated user can update seller with valid data
     */
    public function test_authenticated_user_can_update_seller_with_valid_data(): void
    {
        // Arrange
        $seller = Seller::factory()->create([
            'name' => 'Old Name',
            'number' => '1234567890',
        ]);

        $updateData = [
            'name' => 'Updated Seller Name',
            'number' => '9876543210',
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/sellers/{$seller->id}", $updateData);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('message', 'Seller updated successfully');
        $response->assertJsonPath('data.name', 'Updated Seller Name');
        $response->assertJsonPath('data.number', '9876543210');
    }

    /**
     * Test seller update with partial data
     */
    public function test_seller_update_with_partial_data(): void
    {
        // Arrange
        $seller = Seller::factory()->create([
            'name' => 'Original Name',
            'number' => '1234567890',
            'address' => 'Original Address',
        ]);

        $updateData = [
            'name' => 'Updated Name', // Only update name
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/sellers/{$seller->id}", $updateData);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'Updated Name');
        $response->assertJsonPath('data.number', '1234567890'); // Should remain unchanged
    }

    /**
     * Test seller update fails with invalid data
     */
    public function test_seller_update_fails_with_invalid_data(): void
    {
        // Arrange
        $seller = Seller::factory()->create();
        $updateData = [
            'number' => str_repeat('1', 21), // Exceeds max:20
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/sellers/{$seller->id}", $updateData);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonPath('status', false);
        $response->assertJsonPathExists('errors.number');
    }

    /**
     * Test authenticated user can delete seller
     */
    public function test_authenticated_user_can_delete_seller(): void
    {
        // Arrange
        $seller = Seller::factory()->create(['name' => 'Seller to Delete']);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/sellers/{$seller->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('message', 'Seller deleted successfully');
        $this->assertDatabaseMissing('sellers', ['id' => $seller->id]);
    }

    /**
     * Test deleting non-existent seller returns 404
     */
    public function test_deleting_non_existent_seller_returns_404(): void
    {
        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/sellers/99999');

        // Assert
        $response->assertStatus(404);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('message', 'Seller with ID 99999 not found');
    }

    /**
     * Test unauthenticated user cannot access sellers
     */
    public function test_unauthenticated_user_cannot_access_sellers(): void
    {
        // Act
        $response = $this->getJson('/api/sellers');

        // Assert
        $response->assertStatus(401);
    }

    /**
     * Test seller name validation - string type
     */
    public function test_seller_name_validation_string_type(): void
    {
        // Arrange
        $sellerData = [
            'name' => 12345, // Should be string
            'number' => '9876543210',
            'address' => 'Test Address',
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/sellers', $sellerData);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonPathExists('errors.name');
    }

    /**
     * Test seller includes related documents in response
     */
    public function test_seller_includes_related_documents_in_response(): void
    {
        // Arrange
        $seller = Seller::factory()->create();

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/sellers/{$seller->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'id',
                'name',
                'number',
                'address',
                'documents',
            ],
        ]);
    }
}
