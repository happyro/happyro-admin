<?php

namespace Tests\Feature\GameData;

use App\Contracts\GameData\MonsterAssetRepository;
use App\Models\GameDataCatalog;
use App\Models\GameMonster;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

final class ListMonstersTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_monster_api_searches_and_filters_catalog(): void
    {
        $catalog = GameDataCatalog::factory()->create([
            'resource_type' => 'monsters',
            'source_version' => config('happyro.game_data.default_server_version'),
        ]);
        GameMonster::factory()->create(['game_data_catalog_id' => $catalog->id, 'monster_id' => 1002, 'aegis_name' => 'PORING', 'name_zh_cn' => '波利']);
        GameMonster::factory()->create(['game_data_catalog_id' => $catalog->id, 'monster_id' => 2401, 'aegis_name' => 'G_PORING', 'name_zh_cn' => '波利']);
        GameMonster::factory()->create(['game_data_catalog_id' => $catalog->id, 'monster_id' => 1096, 'aegis_name' => 'ANGELING', 'name_zh_cn' => '天使波利', 'is_boss' => true]);
        GameMonster::factory()->create([
            'game_data_catalog_id' => $catalog->id, 'monster_id' => 1039, 'aegis_name' => 'BAPHOMET',
            'name_zh_cn' => '巴风特', 'race' => 'Demon', 'is_boss' => true,
            'payload' => ['MvpDrops' => [['Item' => 'Baphomet_Card', 'Rate' => 1]]],
        ]);
        $otherCatalog = GameDataCatalog::factory()->create(['resource_type' => 'monsters', 'source_version' => 'server2']);
        GameMonster::factory()->create(['game_data_catalog_id' => $otherCatalog->id, 'monster_id' => 2000]);
        $this->mock(MonsterAssetRepository::class)->shouldReceive('imagePath')->andReturnNull();
        $role = Role::query()->firstOrCreate(['name' => 'super_admin'], ['label' => '超级管理员']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->actingAs($user)->getJson('/api/game-data/monsters?query=波利')
            ->assertOk()->assertJsonPath('total', 3)
            ->assertJsonPath('data.0.Id', 1002)
            ->assertJsonPath('data.1.Id', 1096)
            ->assertJsonPath('data.2.Id', 2401);
        $this->getJson('/api/game-data/monsters?query=巴风&race=Demon&class=mvp')
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.Id', 1039);
        $this->getJson('/api/game-data/monsters?class=mini')
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.Id', 1096);
        $this->getJson('/api/game-data/monsters?class=normal&query=波利')
            ->assertOk()->assertJsonPath('total', 2);
        $this->getJson('/api/game-data/monsters/1039')
            ->assertOk()->assertJsonPath('data.names.zh-CN', '巴风特');
        $this->getJson('/api/game-data/monsters?serverVersion=server2')
            ->assertOk()->assertJsonPath('total', 4);
        $this->getJson('/api/game-data/monsters?class=invalid')
            ->assertUnprocessable();
    }

    public function test_monster_api_requires_authentication(): void
    {
        $this->getJson('/api/game-data/monsters')->assertUnauthorized();
    }
}
