<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderControllerBugTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Este test documenta un bug crítico en el método paymentDoneOrder
     *
     * El bug está en la línea 28 del OrderController donde hay un
     * "return response()->json(["item" => $item]);" dentro del foreach
     * que hace que el método termine en la primera iteración del bucle,
     * impidiendo que se procesen múltiples items del carrito.
     */
    public function test_payment_done_order_bug_early_return_in_foreach()
    {
        // Crear categoría y múltiples productos
        $category = ProductCategory::factory()->create();
        $product1 = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 50.00,
            'stock' => 10
        ]);
        $product2 = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 30.00,
            'stock' => 5
        ]);

        $payload = [
            'cart' => [
                [
                    [
                        'id' => $product1->id,
                        'quantity' => 2
                    ]
                ],
                [
                    [
                        'id' => $product2->id,
                        'quantity' => 1
                    ]
                ]
            ],
            'userdata' => [
                'name' => 'Test User',
                'email' => 'test@example.com'
            ]
        ];

        $response = $this->postJson('/api/order/payment-done', $payload);

        // Bug: Solo procesa el primer item y retorna inmediatamente
        $response->assertStatus(200)
                ->assertJson([
                    'item' => [
                        [
                            'id' => $product1->id,
                            'quantity' => 2
                        ]
                    ]
                ]);

        // Con el bug actual, no se crean OrderItems ni se actualiza el stock
        $this->assertDatabaseMissing('order_items', [
            'product_id' => $product1->id
        ]);

        $this->assertDatabaseMissing('order_items', [
            'product_id' => $product2->id
        ]);

        // Los productos mantienen su stock original
        $this->assertDatabaseHas('products', [
            'id' => $product1->id,
            'stock' => 10 // Stock sin cambios debido al bug
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product2->id,
            'stock' => 5 // Stock sin cambios debido al bug
        ]);
    }

    /**
     * Test adicional para verificar que el order se crea pero permanece vacío
     */
    public function test_empty_order_created_due_to_bug()
    {
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 25.00,
            'stock' => 8
        ]);

        $payload = [
            'cart' => [
                [
                    [
                        'id' => $product->id,
                        'quantity' => 3
                    ]
                ]
            ],
            'userdata' => [
                'name' => 'Bug Test User',
                'email' => 'bugtest@example.com'
            ]
        ];

        $response = $this->postJson('/api/order/payment-done', $payload);

        // Se crea el usuario
        $this->assertDatabaseHas('users', [
            'email' => 'bugtest@example.com'
        ]);

        // Se crea una orden pero sin items debido al bug
        $this->assertDatabaseHas('orders', [
            'total_price' => 0 // Total_price se queda en 0 por el bug
        ]);

        // No se crean OrderItems
        $this->assertDatabaseCount('order_items', 0);
    }
}
