<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $counterCategory = \App\Models\ProductCategory::count();
        // Ensure category_id is within the range of existing categories
        $categoryId = $counterCategory > 0 ? $this->faker->numberBetween(1, $counterCategory) : null;
        return [
            "name" => $this->faker->word(),
            "description" => $this->faker->sentence(),
            "image" => 'product1.jpg',
            "price" => $this->faker->randomFloat(2, 1, 100),
            "stock" => $this->faker->numberBetween(1, 100),
            "category_id" => $categoryId,
        ];
    }
}
