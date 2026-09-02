<?php

namespace Tests\Feature\GameData;

use App\Contracts\GameData\ItemAssetRepository;
use App\Models\GameDataCatalog;
use App\Models\GameDataItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

final class ListItemsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_item_api_returns_client_server_and_union_ranges(): void
    {
        $client = GameDataCatalog::factory()->create(['source' => 'client', 'ruleset' => 'client', 'source_version' => 'kro-20211105']);
        $server = GameDataCatalog::factory()->create(['source' => 'server', 'ruleset' => 'renewal', 'source_version' => 'server123']);
        GameDataItem::factory()->for($client, 'catalog')->create([
            'item_id' => 501,
            'name_zh_cn' => '客户端红药',
            'name_en_us' => 'Red Potion',
            'description' => ['恢复 HP'],
        ]);
        GameDataItem::factory()->for($client, 'catalog')->create(['item_id' => 502, 'name_zh_cn' => '客户端橙药', 'name_en_us' => 'Orange Potion']);
        GameDataItem::factory()->for($server, 'catalog')->create(['item_id' => 501, 'name_zh_cn' => '服务端红药', 'name_en_us' => 'Red Potion', 'item_type' => 'Healing']);
        GameDataItem::factory()->for($server, 'catalog')->create(['item_id' => 503, 'name_zh_cn' => '服务端黄药', 'name_en_us' => 'Yellow Potion', 'item_type' => 'Healing']);
        $this->withoutAssets();
        $this->actingAs($this->superAdmin());

        $this->getJson('/api/game-data/items?range=client&clientVersion=kro-20211105')->assertOk()->assertJsonPath('total', 2);
        $this->getJson('/api/game-data/items?range=server&serverVersion=server123')->assertOk()->assertJsonPath('total', 2);
        $this->getJson('/api/game-data/items?range=all&clientVersion=kro-20211105&serverVersion=server123')
            ->assertOk()
            ->assertJsonPath('total', 3)
            ->assertJsonPath('data.0.source', 'both')
            ->assertJsonPath('data.0.names.zh-CN', '客户端红药')
            ->assertJsonPath('data.0.description.0', '恢复 HP');
    }

    public function test_item_api_filters_across_merged_sources(): void
    {
        $client = GameDataCatalog::factory()->create(['source' => 'client', 'ruleset' => 'client', 'source_version' => 'client1']);
        $server = GameDataCatalog::factory()->create(['source' => 'server', 'ruleset' => 'renewal', 'source_version' => 'server1']);
        GameDataItem::factory()->for($client, 'catalog')->create(['item_id' => 501, 'name_zh_cn' => '特别红药', 'name_en_us' => 'Red Potion']);
        GameDataItem::factory()->for($server, 'catalog')->create(['item_id' => 501, 'name_zh_cn' => '红色药水', 'name_en_us' => 'Red Potion', 'item_type' => 'Healing']);
        $this->withoutAssets();
        $this->actingAs($this->superAdmin());

        $this->getJson('/api/game-data/items?range=all&clientVersion=client1&serverVersion=server1&query=特别&type=Healing')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.Id', 501);
    }

    public function test_item_api_requires_authentication_and_valid_range(): void
    {
        $this->get('/api/game-data/items')->assertUnauthorized();
        $this->getJson('/api/game-data/items')->assertUnauthorized();

        $this->actingAs($this->superAdmin())
            ->getJson('/api/game-data/items?range=invalid')
            ->assertUnprocessable();
    }

    private function withoutAssets(): void
    {
        $this->mock(ItemAssetRepository::class)
            ->shouldReceive('iconPath', 'illustrationPath')
            ->andReturnNull();
    }

    private function superAdmin(): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'super_admin'], ['label' => '超级管理员']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}
