<?php

namespace Tests\Feature\GameData;

use App\Contracts\GameData\ItemSnapshotReader;
use App\Data\GameData\ItemCatalogSnapshot;
use App\Services\GameData\ImportItemsService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

final class ImportItemsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_reimport_is_idempotent_and_removes_stale_items(): void
    {
        $reader = Mockery::mock(ItemSnapshotReader::class);
        $reader->expects('read')->times(2)->andReturn(
            $this->snapshot([501 => $this->item('红色药水', 'Red Potion'), 502 => $this->item('橙色药水', 'Orange Potion')]),
            $this->snapshot([501 => $this->item('红色药水（新）', 'Red Potion')], 'hash-2'),
        );
        $service = new ImportItemsService($reader, app('db'));

        $first = $service->import('snapshot.json');
        $second = $service->import('snapshot.json');

        $this->assertSame(2, $first['imported']);
        $this->assertSame(1, $second['imported']);
        $this->assertSame(1, $second['deleted']);
        $this->assertDatabaseCount('game_data_catalogs', 1);
        $this->assertDatabaseCount('game_data_items', 1);
        $this->assertDatabaseHas('game_data_items', ['item_id' => 501, 'name_zh_cn' => '红色药水（新）']);
        $this->assertDatabaseMissing('game_data_items', ['item_id' => 502]);
    }

    public function test_command_without_source_only_displays_usage(): void
    {
        $this->artisan('game-data:import-items', ['--no-color' => true])
            ->expectsOutputToContain('HappyRO 游戏资料导入')
            ->assertSuccessful();

        $this->assertDatabaseCount('game_data_catalogs', 0);
    }

    public function test_import_rejects_an_empty_snapshot(): void
    {
        $reader = Mockery::mock(ItemSnapshotReader::class);
        $reader->expects('read')->once()->andReturn($this->snapshot([]));

        $this->expectException(RuntimeException::class);
        (new ImportItemsService($reader, app('db')))->import('snapshot.json');
    }

    /** @param array<int, array<string, mixed>> $items */
    private function snapshot(array $items, string $hash = 'hash-1'): ItemCatalogSnapshot
    {
        return new ItemCatalogSnapshot('client', 'client', 'kro-20211105', $hash, [], $items);
    }

    /** @return array<string, mixed> */
    private function item(string $chinese, string $english): array
    {
        return [
            'names' => ['zh-CN' => $chinese, 'en-US' => $english],
            'identifiedResourceName' => 'potion',
            'identifiedDescriptionName' => ['^000088恢复 HP^000000', '^ffffff_^000000'],
        ];
    }
}
