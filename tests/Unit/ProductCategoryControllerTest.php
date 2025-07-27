<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\ProductCategoryController;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ProductCategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected ProductCategoryController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new ProductCategoryController();
    }

    public function test_get_categories_returns_all_categories()
    {
        // Crear algunas categorías de prueba
        $category1 = ProductCategory::factory()->create(['name' => 'Electronics']);
        $category2 = ProductCategory::factory()->create(['name' => 'Clothing']);
        $category3 = ProductCategory::factory()->create(['name' => 'Books']);

        $request = Request::create('/api/category', 'GET');
        $response = $this->controller->getCategories($request);

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(3, $responseData);
        
        // Verificar que las categorías están en la respuesta
        $categoryNames = array_column($responseData, 'name');
        $this->assertContains('Electronics', $categoryNames);
        $this->assertContains('Clothing', $categoryNames);
        $this->assertContains('Books', $categoryNames);
    }

    public function test_get_categories_returns_empty_array_when_no_categories()
    {
        // No crear ninguna categoría
        $request = Request::create('/api/category', 'GET');
        $response = $this->controller->getCategories($request);

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertIsArray($responseData);
        $this->assertEmpty($responseData);
    }

    public function test_get_categories_returns_correct_json_structure()
    {
        $category = ProductCategory::factory()->create([
            'name' => 'Test Category'
        ]);

        $request = Request::create('/api/category', 'GET');
        $response = $this->controller->getCategories($request);

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $responseData);
        
        // Verificar la estructura del JSON
        $firstCategory = $responseData[0];
        $this->assertArrayHasKey('id', $firstCategory);
        $this->assertArrayHasKey('name', $firstCategory);
        $this->assertArrayHasKey('created_at', $firstCategory);
        $this->assertArrayHasKey('updated_at', $firstCategory);
        $this->assertArrayHasKey('deleted_at', $firstCategory);
        
        $this->assertEquals($category->id, $firstCategory['id']);
        $this->assertEquals('Test Category', $firstCategory['name']);
        $this->assertNull($firstCategory['deleted_at']); // No soft deleted
    }

    public function test_get_categories_returns_categories_in_creation_order()
    {
        // Crear categorías en un orden específico
        $category1 = ProductCategory::factory()->create(['name' => 'First Category']);
        sleep(1); // Asegurar diferentes timestamps
        $category2 = ProductCategory::factory()->create(['name' => 'Second Category']);
        sleep(1);
        $category3 = ProductCategory::factory()->create(['name' => 'Third Category']);

        $request = Request::create('/api/category', 'GET');
        $response = $this->controller->getCategories($request);

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(3, $responseData);
        
        // Verificar que están en el orden de ID (que corresponde al orden de creación)
        $this->assertEquals($category1->id, $responseData[0]['id']);
        $this->assertEquals($category2->id, $responseData[1]['id']);
        $this->assertEquals($category3->id, $responseData[2]['id']);
    }

    public function test_get_categories_handles_request_object_correctly()
    {
        ProductCategory::factory()->create(['name' => 'Sample Category']);

        // Probar con diferentes tipos de request
        $request1 = Request::create('/api/category', 'GET');
        $request2 = Request::create('/api/category', 'GET', ['param' => 'value']);

        $response1 = $this->controller->getCategories($request1);
        $response2 = $this->controller->getCategories($request2);

        // Ambas respuestas deberían ser idénticas ya que el método no usa parámetros
        $this->assertEquals($response1->getContent(), $response2->getContent());
        $this->assertEquals(200, $response1->getStatusCode());
        $this->assertEquals(200, $response2->getStatusCode());
    }

    public function test_get_categories_excludes_soft_deleted_categories()
    {
        // Crear categorías normales
        $activeCategory1 = ProductCategory::factory()->create(['name' => 'Active Category 1']);
        $activeCategory2 = ProductCategory::factory()->create(['name' => 'Active Category 2']);
        
        // Crear y soft-delete una categoría
        $deletedCategory = ProductCategory::factory()->create(['name' => 'Deleted Category']);
        $deletedCategory->delete(); // Soft delete

        $request = Request::create('/api/category', 'GET');
        $response = $this->controller->getCategories($request);

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(2, $responseData); // Solo las activas
        
        $categoryNames = array_column($responseData, 'name');
        $this->assertContains('Active Category 1', $categoryNames);
        $this->assertContains('Active Category 2', $categoryNames);
        $this->assertNotContains('Deleted Category', $categoryNames);
    }

    public function test_get_categories_returns_valid_json_response()
    {
        ProductCategory::factory()->create(['name' => 'JSON Test Category']);

        $request = Request::create('/api/category', 'GET');
        $response = $this->controller->getCategories($request);

        // Verificar que es una respuesta JSON válida
        $this->assertJson($response->getContent());
        
        // Verificar headers de contenido
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
    }

    public function test_get_categories_with_large_dataset()
    {
        // Crear un número mayor de categorías para probar performance
        $categoryCount = 50;
        ProductCategory::factory()->count($categoryCount)->create();

        $request = Request::create('/api/category', 'GET');
        $response = $this->controller->getCategories($request);

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount($categoryCount, $responseData);
        $this->assertIsArray($responseData);
    }

    public function test_get_categories_preserves_special_characters_in_names()
    {
        // Crear categorías con caracteres especiales
        ProductCategory::factory()->create(['name' => 'Electronics & Gadgets']);
        ProductCategory::factory()->create(['name' => 'Books/Magazines']);
        ProductCategory::factory()->create(['name' => 'Toys & Games (Kids)']);

        $request = Request::create('/api/category', 'GET');
        $response = $this->controller->getCategories($request);

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(3, $responseData);
        
        $categoryNames = array_column($responseData, 'name');
        $this->assertContains('Electronics & Gadgets', $categoryNames);
        $this->assertContains('Books/Magazines', $categoryNames);
        $this->assertContains('Toys & Games (Kids)', $categoryNames);
    }
}
