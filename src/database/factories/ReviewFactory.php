<?php

namespace Database\Factories;

use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Review::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $services = ['ナチュラルエクステ', 'ボリュームエクステ + 眉ワックス', '眉毛ワックス脱毛', 'カラーエクステ', 'まつげ＋眉セット', '眉毛エクステ'];
        $ages = ['20代', '30代', '40代', '50代'];
        
        return [
            'name' => $this->faker->name('male') . '様',
            'age' => $this->faker->randomElement($ages),
            'rating' => $this->faker->numberBetween(4, 5),
            'comment' => $this->faker->realText(100),
            'service' => $this->faker->randomElement($services),
            'date' => $this->faker->date('Y年m月'),
        ];
    }
}