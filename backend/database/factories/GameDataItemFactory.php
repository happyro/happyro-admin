<?php

namespace Database\Factories;

use App\Models\GameDataCatalog;
use App\Models\GameDataItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameDataItem>
 */
class GameDataItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_data_catalog_id' => GameDataCatalog::factory(),
            'item_id' => fake()->unique()->numberBetween(1, 1_000_000),
            'name_zh_cn' => '测试物品',
            'name_en_us' => fake()->words(2, true),
            'aegis_name' => fake()->unique()->regexify('[A-Z][A-Za-z_]{12}'),
            'item_type' => 'Etc',
            'resource_name' => null,
            'description' => null,
            'payload' => [],
            'sync_token' => fake()->uuid(),
        ];
    }
}
