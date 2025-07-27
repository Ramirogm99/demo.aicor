<?php

namespace Tests\Feature;

use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCategoryControllerFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_categories_api_endpoint_returns_all_categories()
    {
        // Crear categorías de prueba
        $categories = ProductCategory::factory()->count(3)->create();

        $response = $this->getJson('/api/category');

        $response->assertStatus(200)
                ->assertJsonCount(3)
                ->assertJsonStructure([
                    '*' => [
                        'id',
                        'name',
                        'created_at',
                        'updated_at',
                        'deleted_at'
                    ]
                ]);

        // Verificar que todas las categorías están en la respuesta
        foreach ($categories as $category) {
            $response->assertJsonFragment([
                'id' => $category->id,
                'name' => $category->name
            ]);
        }
    }

    public function test_get_categories_api_endpoint_returns_empty_when_no_categories()
    {
        $response = $this->getJson('/api/category');

        $response->assertStatus(200)
                ->assertJson([]);
    }

    public function test_get_categories_api_endpoint_returns_correct_content_type()
    {
        ProductCategory::factory()->create();

        $response = $this->getJson('/api/category');

        $response->assertStatus(200)
                ->assertHeader('Content-Type', 'application/json');
    }

    public function test_get_categories_api_excludes_soft_deleted_categories()
    {
        // Crear categorías activas
        $activeCategory1 = ProductCategory::factory()->create(['name' => 'Active 1']);
        $activeCategory2 = ProductCategory::factory()->create(['name' => 'Active 2']);

        // Crear y soft-delete una categoría
        $deletedCategory = ProductCategory::factory()->create(['name' => 'Deleted']);
        $deletedCategory->delete();

        $response = $this->getJson('/api/category');

        $response->assertStatus(200)
                ->assertJsonCount(2)
                ->assertJsonFragment(['name' => 'Active 1'])
                ->assertJsonFragment(['name' => 'Active 2'])
                ->assertJsonMissing(['name' => 'Deleted']);
    }

    public function test_get_categories_api_handles_unicode_characters()
    {
        // Crear categorías con caracteres unicode
        ProductCategory::factory()->create(['name' => 'Electrónicos']);
        ProductCategory::factory()->create(['name' => 'Ropa & Accesorios']);
        ProductCategory::factory()->create(['name' => 'Libros en Español']);

        $response = $this->getJson('/api/category');

        $response->assertStatus(200)
                ->assertJsonCount(3)
                ->assertJsonFragment(['name' => 'Electrónicos'])
                ->assertJsonFragment(['name' => 'Ropa & Accesorios'])
                ->assertJsonFragment(['name' => 'Libros en Español']);
    }

    public function test_get_categories_api_returns_timestamps()
    {
        $category = ProductCategory::factory()->create(['name' => 'Test Category']);

        $response = $this->getJson('/api/category');

        $response->assertStatus(200)
                ->assertJsonCount(1);

        $responseData = $response->json();
        $categoryData = $responseData[0];

        $this->assertArrayHasKey('created_at', $categoryData);
        $this->assertArrayHasKey('updated_at', $categoryData);
        $this->assertNotNull($categoryData['created_at']);
        $this->assertNotNull($categoryData['updated_at']);

        // Verificar formato de timestamp
        $this->assertMatchesRegularExpression(
            '/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}Z/',
            $categoryData['created_at']
        );
    }

    public function test_get_categories_api_performance_with_many_categories()
    {
        // Crear muchas categorías para probar performance
        ProductCategory::factory()->count(100)->create();

        $startTime = microtime(true);
        $response = $this->getJson('/api/category');
        $endTime = microtime(true);

        $executionTime = ($endTime - $startTime) * 1000; // en milisegundos

        $response->assertStatus(200)
                ->assertJsonCount(100);

        // Verificar que la respuesta es razonablemente rápida (menos de 1 segundo)
        $this->assertLessThan(1000, $executionTime, 'API response took too long');
    }

    public function test_get_categories_api_maintains_data_integrity()
    {
        $category = ProductCategory::factory()->create([
            'name' => 'Test Category with Special Chars: @#$%^&*()'
        ]);

        $response = $this->getJson('/api/category');

        $response->assertStatus(200)
                ->assertJsonCount(1);

        $responseData = $response->json();
        $returnedCategory = $responseData[0];

        // Verificar que todos los datos se mantienen íntegros
        $this->assertEquals($category->id, $returnedCategory['id']);
        $this->assertEquals($category->name, $returnedCategory['name']);
        $this->assertEquals($category->created_at->toISOString(), $returnedCategory['created_at']);
        $this->assertEquals($category->updated_at->toISOString(), $returnedCategory['updated_at']);
        $this->assertNull($returnedCategory['deleted_at']);
    }

    public function test_get_categories_api_accepts_get_request_only()
    {
        ProductCategory::factory()->create();

        // GET debería funcionar
        $getResponse = $this->getJson('/api/category');
        $getResponse->assertStatus(200);

        // POST debería fallar
        $postResponse = $this->postJson('/api/category');
        $postResponse->assertStatus(405); // Method Not Allowed

        // PUT debería fallar
        $putResponse = $this->putJson('/api/category');
        $putResponse->assertStatus(405);

        // DELETE debería fallar
        $deleteResponse = $this->deleteJson('/api/category');
        $deleteResponse->assertStatus(405);
    }

    public function test_get_categories_api_ignores_query_parameters()
    {
        $category = ProductCategory::factory()->create(['name' => 'Test Category']);

        // Hacer peticiones con diferentes parámetros de query
        $response1 = $this->getJson('/api/category');
        $response2 = $this->getJson('/api/category?filter=something');
        $response3 = $this->getJson('/api/category?page=1&limit=10');

        // Todas deberían retornar el mismo resultado
        $response1->assertStatus(200);
        $response2->assertStatus(200);
        $response3->assertStatus(200);

        $this->assertEquals($response1->json(), $response2->json());
        $this->assertEquals($response1->json(), $response3->json());
    }

    public function test_get_categories_api_with_concurrent_requests()
    {
        ProductCategory::factory()->count(5)->create();

        // Simular múltiples requests concurrentes
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->getJson('/api/category');
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
}
