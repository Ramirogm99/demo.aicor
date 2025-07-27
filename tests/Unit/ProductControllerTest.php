<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\ProductController;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected ProductController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new ProductController();
    }

    public function test_get_products_returns_all_products_when_no_category_filter()
    {
        // Crear categorías y productos
        $category1 = ProductCategory::factory()->create();
        $category2 = ProductCategory::factory()->create();

        $product1 = Product::factory()->create(['category_id' => $category1->id, 'name' => 'Product 1']);
        $product2 = Product::factory()->create(['category_id' => $category2->id, 'name' => 'Product 2']);
        $product3 = Product::factory()->create(['category_id' => $category1->id, 'name' => 'Product 3']);

        // Request sin category
        $request = Request::create('/api/product', 'POST');
        $response = $this->controller->getProducts($request);

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(3, $responseData);

        $productNames = array_column($responseData, 'name');
        $this->assertContains('Product 1', $productNames);
        $this->assertContains('Product 2', $productNames);
        $this->assertContains('Product 3', $productNames);
    }

    public function test_get_products_returns_all_products_when_empty_category_array()
    {
        $category = ProductCategory::factory()->create();
        $product1 = Product::factory()->create(['category_id' => $category->id]);
        $product2 = Product::factory()->create(['category_id' => $category->id]);

        // Request con category como array vacío
        $request = Request::create('/api/product', 'POST', ['category' => []]);
        $response = $this->controller->getProducts($request);

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(2, $responseData);
    }

    public function test_get_products_filters_by_single_category()
    {
        $category1 = ProductCategory::factory()->create();
        $category2 = ProductCategory::factory()->create();

        $product1 = Product::factory()->create(['category_id' => $category1->id, 'name' => 'Product 1']);
        $product2 = Product::factory()->create(['category_id' => $category2->id, 'name' => 'Product 2']);
        $product3 = Product::factory()->create(['category_id' => $category1->id, 'name' => 'Product 3']);

        // Request con una sola categoría
        $request = Request::create('/api/product', 'POST', ['category' => [$category1->id]]);
        $response = $this->controller->getProducts($request);

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(2, $responseData);

        $productNames = array_column($responseData, 'name');
        $this->assertContains('Product 1', $productNames);
        $this->assertContains('Product 3', $productNames);
        $this->assertNotContains('Product 2', $productNames);
    }

    public function test_get_products_filters_by_multiple_categories()
    {
        $category1 = ProductCategory::factory()->create();
        $category2 = ProductCategory::factory()->create();
        $category3 = ProductCategory::factory()->create();

        $product1 = Product::factory()->create(['category_id' => $category1->id, 'name' => 'Product 1']);
        $product2 = Product::factory()->create(['category_id' => $category2->id, 'name' => 'Product 2']);
        $product3 = Product::factory()->create(['category_id' => $category3->id, 'name' => 'Product 3']);

        // Request con múltiples categorías
        $request = Request::create('/api/product', 'POST', [
            'category' => [$category1->id, $category2->id]
        ]);
        $response = $this->controller->getProducts($request);

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(2, $responseData);

        $productNames = array_column($responseData, 'name');
        $this->assertContains('Product 1', $productNames);
        $this->assertContains('Product 2', $productNames);
        $this->assertNotContains('Product 3', $productNames);
    }

    public function test_get_products_returns_empty_array_when_no_products_match_category()
    {
        $category1 = ProductCategory::factory()->create();
        $category2 = ProductCategory::factory()->create();

        // Solo crear productos en category1
        Product::factory()->create(['category_id' => $category1->id]);

        // Buscar productos en category2 (que no tiene productos)
        $request = Request::create('/api/product', 'POST', ['category' => [$category2->id]]);
        $response = $this->controller->getProducts($request);

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertIsArray($responseData);
        $this->assertEmpty($responseData);
    }

    public function test_get_products_returns_empty_array_when_no_products_exist()
    {
        // No crear productos
        $request = Request::create('/api/product', 'POST');
        $response = $this->controller->getProducts($request);

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertIsArray($responseData);
        $this->assertEmpty($responseData);
    }

    public function test_get_products_returns_correct_json_structure()
    {
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Test Product',
            'price' => 99.99,
            'description' => 'Test Description',
            'stock' => 10
        ]);

        $request = Request::create('/api/product', 'POST');
        $response = $this->controller->getProducts($request);

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $responseData);

        $productData = $responseData[0];
        $this->assertArrayHasKey('id', $productData);
        $this->assertArrayHasKey('name', $productData);
        $this->assertArrayHasKey('price', $productData);
        $this->assertArrayHasKey('description', $productData);
        $this->assertArrayHasKey('image', $productData);
        $this->assertArrayHasKey('stock', $productData);
        $this->assertArrayHasKey('category_id', $productData);
        $this->assertArrayHasKey('created_at', $productData);
        $this->assertArrayHasKey('updated_at', $productData);

        $this->assertEquals($product->id, $productData['id']);
        $this->assertEquals('Test Product', $productData['name']);
        $this->assertEquals(99.99, $productData['price']);
        $this->assertEquals('Test Description', $productData['description']);
        $this->assertEquals(10, $productData['stock']);
    }

    public function test_get_products_handles_non_existent_category_ids()
    {
        $category = ProductCategory::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);

        // Request con IDs de categoría que no existen
        $request = Request::create('/api/product', 'POST', [
            'category' => [9999, 8888]
        ]);
        $response = $this->controller->getProducts($request);

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertIsArray($responseData);
        $this->assertEmpty($responseData);
    }

    public function test_get_products_handles_mixed_valid_and_invalid_category_ids()
    {
        $category1 = ProductCategory::factory()->create();
        $category2 = ProductCategory::factory()->create();

        $product1 = Product::factory()->create(['category_id' => $category1->id, 'name' => 'Valid Product']);

        // Request con mix de IDs válidos e inválidos
        $request = Request::create('/api/product', 'POST', [
            'category' => [$category1->id, 9999, $category2->id]
        ]);
        $response = $this->controller->getProducts($request);

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $responseData);
        $this->assertEquals('Valid Product', $responseData[0]['name']);
    }

    public function test_get_products_preserves_product_data_integrity()
    {
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Special Product @#$%',
            'price' => 123.45,
            'description' => 'Description with special chars: & < > " \'',
            'stock' => 5
        ]);

        $request = Request::create('/api/product', 'POST');
        $response = $this->controller->getProducts($request);

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $productData = $responseData[0];

        $this->assertEquals('Special Product @#$%', $productData['name']);
        $this->assertEquals(123.45, $productData['price']);
        $this->assertEquals('Description with special chars: & < > " \'', $productData['description']);
        $this->assertEquals(5, $productData['stock']);
    }

    public function test_get_products_with_large_dataset()
    {
        $category1 = ProductCategory::factory()->create();
        $category2 = ProductCategory::factory()->create();

        // Crear muchos productos
        Product::factory()->count(50)->create(['category_id' => $category1->id]);
        Product::factory()->count(30)->create(['category_id' => $category2->id]);

        // Test sin filtro (todos los productos)
        $request1 = Request::create('/api/product', 'POST');
        $response1 = $this->controller->getProducts($request1);
        $responseData1 = json_decode($response1->getContent(), true);

        $this->assertEquals(200, $response1->getStatusCode());
        $this->assertCount(80, $responseData1);

        // Test con filtro de categoría
        $request2 = Request::create('/api/product', 'POST', ['category' => [$category1->id]]);
        $response2 = $this->controller->getProducts($request2);
        $responseData2 = json_decode($response2->getContent(), true);

        $this->assertEquals(200, $response2->getStatusCode());
        $this->assertCount(50, $responseData2);
    }

    public function test_get_products_category_parameter_edge_cases()
    {
        $category = ProductCategory::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);

        // Test con array que contiene valores válidos
        $request1 = Request::create('/api/product', 'POST', ['category' => [$category->id]]);
        $response1 = $this->controller->getProducts($request1);
        $this->assertEquals(200, $response1->getStatusCode());

        // Test con array que contiene ceros (IDs inválidos)
        $request2 = Request::create('/api/product', 'POST', ['category' => [0]]);
        $response2 = $this->controller->getProducts($request2);
        $this->assertEquals(200, $response2->getStatusCode());

        // Test con array vacío
        $request3 = Request::create('/api/product', 'POST', ['category' => []]);
        $response3 = $this->controller->getProducts($request3);
        $this->assertEquals(200, $response3->getStatusCode());

        // Nota: Los casos con string/null causan bug en el controlador
        // Ver ProductControllerBugTest para documentación de estos bugs
    }
}
