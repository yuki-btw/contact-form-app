<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'first_name' => $this->faker->lastName(),
            'last_name' => $this->faker->firstName(),
            'gender' => $this->faker->numberBetween(1, 3),
            'email' => $this->faker->safeEmail(),
            'tel' => $this->faker->numerify($this->faker->randomElement(['0#########', '0##########'])),
            'address' => $this->faker->prefecture().$this->faker->city().$this->faker->streetAddress(),
            'building' => $this->faker->optional()->secondaryAddress(),
            'detail' => $this->faker->realText(100),
        ];
    }
}
