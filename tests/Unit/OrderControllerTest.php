<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\OrderController;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected OrderController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new OrderController();
    }

    public function test_user_order_creates_new_user_when_not_exists()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com'
        ];

        // Llamar al método privado usando reflexión
        $method = new \ReflectionMethod(OrderController::class, 'userOrder');
        $method->setAccessible(true);

        $user = $method->invoke($this->controller, $userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);
    }

    public function test_user_order_returns_existing_user()
    {
        // Crear un usuario existente
        $existingUser = User::factory()->create([
            'name' => 'Existing User',
            'email' => 'existing@example.com'
        ]);

        $userData = [
            'name' => 'Different Name',
            'email' => 'existing@example.com'
        ];

        // Llamar al método privado usando reflexión
        $method = new \ReflectionMethod(OrderController::class, 'userOrder');
        $method->setAccessible(true);

        $user = $method->invoke($this->controller, $userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($existingUser->id, $user->id);
        $this->assertEquals('Existing User', $user->name); // Mantiene el nombre original
        $this->assertEquals('existing@example.com', $user->email);
    }

    public function test_payment_done_order_validation_fails_with_empty_cart()
    {
        $request = Request::create('/api/orders', 'POST', [
            'cart' => [],
            'userdata' => ['name' => 'Test', 'email' => 'test@example.com']
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->controller->paymentDoneOrder($request);
    }

    public function test_payment_done_order_validation_fails_without_userdata()
    {
        $request = Request::create('/api/orders', 'POST', [
            'cart' => [['id' => 1, 'quantity' => 2]]
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->controller->paymentDoneOrder($request);
    }

    public function test_get_older_orders_returns_user_orders()
    {
        // Crear usuario y productos
        $user = User::factory()->create(['email' => 'test@example.com']);
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        // Crear orden
        $order = new Order();
        $order->user_id = $user->id;
        $order->total_price = 100;
        $order->save();

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 50
        ]);

        $request = Request::create('/api/orders', 'GET', [
            'email' => 'test@example.com'
        ]);

        $response = $this->controller->getOlderOrders($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $responseData);
        $this->assertEquals($order->id, $responseData[0]['id']);
        $this->assertEquals($user->id, $responseData[0]['user_id']);
        $this->assertArrayHasKey('order_items', $responseData[0]);
    }

    public function test_get_older_orders_with_nonexistent_user_fails()
    {
        $request = Request::create('/api/orders', 'GET', [
            'email' => 'nonexistent@example.com'
        ]);

        // Esto debería generar un error porque el usuario no existe
        // y se intenta acceder a la propiedad 'id' de null
        $this->expectException(\ErrorException::class);
        $this->controller->getOlderOrders($request);
    }
}
