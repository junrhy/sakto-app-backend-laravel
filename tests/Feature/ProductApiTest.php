<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class ProductApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $clientIdentifier = 'test-client-123';

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_list_products_for_a_client()
    {
        // Create some test products
        Product::factory()->count(3)->create([
            'client_identifier' => $this->clientIdentifier
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/products?client_identifier={$this->clientIdentifier}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'price',
                        'category',
                        'type',
                        'status',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'current_page',
                'per_page',
                'total'
            ]);
    }

    /** @test */
    public function it_requires_client_identifier_to_list_products()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/products');

        $response->assertStatus(400)
            ->assertJson(['error' => 'Client identifier is required']);
    }

    /** @test */
    public function it_can_create_a_physical_product()
    {
        $productData = [
            'name' => 'Test Physical Product',
            'description' => 'A test physical product',
            'price' => 99.99,
            'category' => 'Electronics',
            'type' => 'physical',
            'sku' => 'TEST-001',
            'stock_quantity' => 50,
            'weight' => 1.5,
            'dimensions' => '10x5x2 cm',
            'status' => 'published',
            'tags' => ['test', 'electronics'],
            'client_identifier' => $this->clientIdentifier
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/products', $productData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'name',
                'description',
                'price',
                'category',
                'type',
                'sku',
                'stock_quantity',
                'weight',
                'dimensions',
                'status',
                'tags',
                'created_at',
                'updated_at'
            ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Test Physical Product',
            'type' => 'physical',
            'sku' => 'TEST-001',
            'stock_quantity' => 50
        ]);
    }

    /** @test */
    public function it_can_create_a_digital_product()
    {
        $productData = [
            'name' => 'Test Digital Product',
            'description' => 'A test digital product',
            'price' => 29.99,
            'category' => 'Software',
            'type' => 'digital',
            'file_url' => 'https://example.com/file.pdf',
            'thumbnail_url' => 'https://example.com/thumbnail.jpg',
            'status' => 'published',
            'tags' => ['software', 'digital'],
            'client_identifier' => $this->clientIdentifier
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/products', $productData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'name',
                'description',
                'price',
                'category',
                'type',
                'file_url',
                'thumbnail_url',
                'status',
                'tags',
                'created_at',
                'updated_at'
            ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Test Digital Product',
            'type' => 'digital',
            'file_url' => 'https://example.com/file.pdf'
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_product()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/products', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'description', 'price', 'category', 'type', 'status', 'client_identifier']);
    }

    /** @test */
    public function it_can_show_a_product()
    {
        $product = Product::factory()->create([
            'client_identifier' => $this->clientIdentifier
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $product->id,
                'name' => $product->name
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_product()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/products/999');

        $response->assertStatus(404)
            ->assertJson(['error' => 'Product not found']);
    }

    /** @test */
    public function it_can_update_a_product()
    {
        $product = Product::factory()->create([
            'client_identifier' => $this->clientIdentifier
        ]);

        $updateData = [
            'name' => 'Updated Product Name',
            'price' => 149.99,
            'status' => 'archived'
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/products/{$product->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $product->id,
                'name' => 'Updated Product Name',
                'price' => '149.99',
                'status' => 'archived'
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product Name',
            'price' => 149.99,
            'status' => 'archived'
        ]);
    }

    /** @test */
    public function it_can_update_stock_for_physical_product()
    {
        $product = Product::factory()->create([
            'type' => 'physical',
            'stock_quantity' => 10,
            'client_identifier' => $this->clientIdentifier
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/products/{$product->id}/stock", [
                'stock_quantity' => 25
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $product->id,
                'stock_quantity' => 25
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 25
        ]);
    }

    /** @test */
    public function it_cannot_update_stock_for_digital_product()
    {
        $product = Product::factory()->create([
            'type' => 'digital',
            'client_identifier' => $this->clientIdentifier
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/products/{$product->id}/stock", [
                'stock_quantity' => 25
            ]);

        $response->assertStatus(400)
            ->assertJson(['error' => 'Stock can only be updated for physical products']);
    }

    /** @test */
    public function it_can_delete_a_product()
    {
        $product = Product::factory()->create([
            'client_identifier' => $this->clientIdentifier
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Product deleted successfully']);

        $this->assertDatabaseMissing('products', [
            'id' => $product->id
        ]);
    }

    /** @test */
    public function it_can_get_product_categories()
    {
        Product::factory()->count(3)->create([
            'category' => 'Electronics',
            'client_identifier' => $this->clientIdentifier
        ]);

        Product::factory()->count(2)->create([
            'category' => 'Books',
            'client_identifier' => $this->clientIdentifier
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/products/categories?client_identifier={$this->clientIdentifier}");

        $response->assertStatus(200)
            ->assertJson(['Electronics', 'Books']);
    }

    /** @test */
    public function it_can_get_product_settings()
    {
        Product::factory()->count(2)->create([
            'type' => 'physical',
            'status' => 'published',
            'client_identifier' => $this->clientIdentifier
        ]);

        Product::factory()->create([
            'type' => 'digital',
            'status' => 'draft',
            'client_identifier' => $this->clientIdentifier
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/products/settings?client_identifier={$this->clientIdentifier}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_products',
                'by_type' => [
                    'physical',
                    'digital',
                    'service',
                    'subscription'
                ],
                'by_status' => [
                    'draft',
                    'published',
                    'archived',
                    'inactive'
                ],
                'stock_status' => [
                    'in_stock',
                    'low_stock',
                    'out_of_stock'
                ],
                'categories'
            ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/products');

        $response->assertStatus(401);
    }
} 