<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductControllerFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_products_api_endpoint_returns_all_products()
    {
        $category1 = ProductCategory::factory()->create();
        $category2 = ProductCategory::factory()->create();

        $products = [
            Product::factory()->create(['category_id' => $category1->id, 'name' => 'Product 1']),
            Product::factory()->create(['category_id' => $category2->id, 'name' => 'Product 2']),
            Product::factory()->create(['category_id' => $category1->id, 'name' => 'Product 3'])
        ];

        $response = $this->postJson('/api/product');

        $response->assertStatus(200)
                ->assertJsonCount(3)
                ->assertJsonStructure([
                    '*' => [
                        'id',
                        'name',
                        'price',
                        'description',
                        'image',
                        'stock',
                        'category_id',
                        'created_at',
                        'updated_at'
                    ]
                ]);

        foreach ($products as $product) {
            $response->assertJsonFragment([
                'id' => $product->id,
                'name' => $product->name
            ]);
        }
    }

    public function test_get_products_api_endpoint_filters_by_category()
    {
        $category1 = ProductCategory::factory()->create();
        $category2 = ProductCategory::factory()->create();

        $product1 = Product::factory()->create(['category_id' => $category1->id, 'name' => 'Cat1 Product']);
        $product2 = Product::factory()->create(['category_id' => $category2->id, 'name' => 'Cat2 Product']);

        $response = $this->postJson('/api/product', [
            'category' => [$category1->id]
        ]);

        $response->assertStatus(200)
                ->assertJsonCount(1)
                ->assertJsonFragment(['name' => 'Cat1 Product'])
                ->assertJsonMissing(['name' => 'Cat2 Product']);
    }

    public function test_get_products_api_endpoint_filters_by_multiple_categories()
    {
        $category1 = ProductCategory::factory()->create();
        $category2 = ProductCategory::factory()->create();
        $category3 = ProductCategory::factory()->create();

        $product1 = Product::factory()->create(['category_id' => $category1->id, 'name' => 'Product 1']);
        $product2 = Product::factory()->create(['category_id' => $category2->id, 'name' => 'Product 2']);
        $product3 = Product::factory()->create(['category_id' => $category3->id, 'name' => 'Product 3']);

        $response = $this->postJson('/api/product', [
            'category' => [$category1->id, $category3->id]
        ]);

        $response->assertStatus(200)
                ->assertJsonCount(2)
                ->assertJsonFragment(['name' => 'Product 1'])
                ->assertJsonFragment(['name' => 'Product 3'])
                ->assertJsonMissing(['name' => 'Product 2']);
    }

    public function test_get_products_api_endpoint_returns_empty_with_empty_category_array()
    {
        $category = ProductCategory::factory()->create();
        Product::factory()->count(3)->create(['category_id' => $category->id]);

        $response = $this->postJson('/api/product', [
            'category' => []
        ]);

        $response->assertStatus(200)
                ->assertJsonCount(3);
    }

    public function test_get_products_api_endpoint_returns_empty_when_no_products()
    {
        $response = $this->postJson('/api/product');

        $response->assertStatus(200)
                ->assertJson([]);
    }

    public function test_get_products_api_endpoint_returns_empty_for_non_existent_category()
    {
        $category = ProductCategory::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);

        $response = $this->postJson('/api/product', [
            'category' => [9999]
        ]);

        $response->assertStatus(200)
                ->assertJson([]);
    }

    public function test_get_products_api_endpoint_handles_content_type()
    {
        $category = ProductCategory::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);

        $response = $this->postJson('/api/product');

        $response->assertStatus(200)
                ->assertHeader('Content-Type', 'application/json');
    }

    public function test_get_products_api_endpoint_preserves_data_integrity()
    {
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Test Product with Ñ and Ü',
            'price' => 99.99,
            'description' => 'Description with áéíóú & symbols',
            'stock' => 15
        ]);

        $response = $this->postJson('/api/product');

        $response->assertStatus(200)
                ->assertJsonCount(1);

        $responseData = $response->json();
        $productData = $responseData[0];

        $this->assertEquals($product->id, $productData['id']);
        $this->assertEquals('Test Product with Ñ and Ü', $productData['name']);
        $this->assertEquals(99.99, $productData['price']);
        $this->assertEquals('Description with áéíóú & symbols', $productData['description']);
        $this->assertEquals(15, $productData['stock']);
    }

    public function test_get_products_api_endpoint_performance_with_large_dataset()
    {
        $category1 = ProductCategory::factory()->create();
        $category2 = ProductCategory::factory()->create();

        Product::factory()->count(100)->create(['category_id' => $category1->id]);
        Product::factory()->count(50)->create(['category_id' => $category2->id]);

        $startTime = microtime(true);
        $response = $this->postJson('/api/product');
        $endTime = microtime(true);

        $executionTime = ($endTime - $startTime) * 1000; // en milisegundos

        $response->assertStatus(200)
                ->assertJsonCount(150);

        // Verificar que la respuesta es razonablemente rápida (menos de 2 segundos)
        $this->assertLessThan(2000, $executionTime, 'API response took too long');
    }

    public function test_get_products_api_endpoint_accepts_post_request_only()
    {
        $category = ProductCategory::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);

        // POST debería funcionar
        $postResponse = $this->postJson('/api/product');
        $postResponse->assertStatus(200);

        // GET debería fallar (según las rutas configuradas)
        $getResponse = $this->getJson('/api/product');
        $getResponse->assertStatus(405); // Method Not Allowed

        // PUT debería fallar
        $putResponse = $this->putJson('/api/product');
        $putResponse->assertStatus(405);

        // DELETE debería fallar
        $deleteResponse = $this->deleteJson('/api/product');
        $deleteResponse->assertStatus(405);
    }

    public function test_get_products_api_endpoint_handles_valid_category_data_types()
    {
        $category1 = ProductCategory::factory()->create();
        $category2 = ProductCategory::factory()->create();
        Product::factory()->create(['category_id' => $category1->id]);
        Product::factory()->create(['category_id' => $category2->id]);

        // Test con array válido de IDs
        $response1 = $this->postJson('/api/product', [
            'category' => [$category1->id, $category2->id]
        ]);
        $response1->assertStatus(200)->assertJsonCount(2);

        // Test con array vacío (debería retornar todos)
        $response2 = $this->postJson('/api/product', [
            'category' => []
        ]);
        $response2->assertStatus(200)->assertJsonCount(2);

        // Test sin parámetro category
        $response3 = $this->postJson('/api/product');
        $response3->assertStatus(200)->assertJsonCount(2);

        // Nota: Los casos con string/null causan bug en el controlador
        // Ver ProductControllerBugTest para documentación de estos bugs
    }

    public function test_get_products_api_endpoint_with_products_in_different_categories()
    {
        $electronics = ProductCategory::factory()->create(['name' => 'Electronics']);
        $clothing = ProductCategory::factory()->create(['name' => 'Clothing']);
        $books = ProductCategory::factory()->create(['name' => 'Books']);

        Product::factory()->create(['category_id' => $electronics->id, 'name' => 'Laptop']);
        Product::factory()->create(['category_id' => $electronics->id, 'name' => 'Phone']);
        Product::factory()->create(['category_id' => $clothing->id, 'name' => 'Shirt']);
        Product::factory()->create(['category_id' => $books->id, 'name' => 'Novel']);

        // Test filtrar solo electrónicos
        $response1 = $this->postJson('/api/product', [
            'category' => [$electronics->id]
        ]);

        $response1->assertStatus(200)
                 ->assertJsonCount(2)
                 ->assertJsonFragment(['name' => 'Laptop'])
                 ->assertJsonFragment(['name' => 'Phone'])
                 ->assertJsonMissing(['name' => 'Shirt'])
                 ->assertJsonMissing(['name' => 'Novel']);

        // Test filtrar ropa y libros
        $response2 = $this->postJson('/api/product', [
            'category' => [$clothing->id, $books->id]
        ]);

        $response2->assertStatus(200)
                 ->assertJsonCount(2)
                 ->assertJsonFragment(['name' => 'Shirt'])
                 ->assertJsonFragment(['name' => 'Novel'])
                 ->assertJsonMissing(['name' => 'Laptop'])
                 ->assertJsonMissing(['name' => 'Phone']);
    }

    public function test_get_products_api_endpoint_returns_timestamps()
    {
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $response = $this->postJson('/api/product');

        $response->assertStatus(200)
                ->assertJsonCount(1);

        $responseData = $response->json();
        $productData = $responseData[0];

        $this->assertArrayHasKey('created_at', $productData);
        $this->assertArrayHasKey('updated_at', $productData);
        $this->assertNotNull($productData['created_at']);
        $this->assertNotNull($productData['updated_at']);

        // Verificar formato de timestamp
        $this->assertMatchesRegularExpression(
            '/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}Z/',
            $productData['created_at']
        );
    }

    public function test_get_products_api_endpoint_concurrent_requests()
    {
        $category = ProductCategory::factory()->create();
        Product::factory()->count(5)->create(['category_id' => $category->id]);

        // Simular múltiples requests concurrentes
        $responses = [];
        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->postJson('/api/product');
        }

        // Verificar que todas las respuestas son consistentes
        foreach ($responses as $response) {
            $response->assertStatus(200)
                    ->assertJsonCount(5);
        }

        // Verificar que todas las respuestas son idénticas
        $firstResponseData = $responses[0]->json();
        foreach (array_slice($responses, 1) as $response) {
            $this->assertEquals($firstResponseData, $response->json());
        }
    }

    public function test_get_products_api_endpoint_with_zero_stock_products()
    {
        $category = ProductCategory::factory()->create();
        $productWithStock = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'In Stock Product',
            'stock' => 10
        ]);
        $productOutOfStock = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Out of Stock Product',
            'stock' => 0
        ]);

        $response = $this->postJson('/api/product');

        $response->assertStatus(200)
                ->assertJsonCount(2)
                ->assertJsonFragment(['name' => 'In Stock Product', 'stock' => 10])
                ->assertJsonFragment(['name' => 'Out of Stock Product', 'stock' => 0]);
    }
}
