<?php

namespace Tests\Unit\GameData;

use App\Data\GameData\MapQuery;
use App\Services\GameData\WorldDataService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class WorldDataServiceTest extends TestCase
{
    public function test_empty_map_placeholder_is_not_exposed_as_an_image(): void
    {
        $maps = collect($this->service()->maps($this->mapQuery(gameOnly: false, perPage: 5000))['data'])->keyBy('map');

        $this->assertSame('terrain', $maps->get('alb_ship')['image_kind']);
        $this->assertNotNull($maps->get('alb_ship')['image']);
        $this->assertNotNull($maps->get('prontera')['image']);
        $this->assertNotNull($maps->get('new_1-1'));
        $this->assertNotNull($maps->get('1@nyd'));
    }

    public function test_well_known_towns_are_ordered_first(): void
    {
        $maps = array_column($this->service()->maps($this->mapQuery(perPage: 3))['data'], 'map');

        $this->assertSame(['prontera', 'prt_fild08', 'izlude'], $maps);
    }

    public function test_maps_are_paginated_and_filtered_on_the_server(): void
    {
        $service = $this->service();

        $all = $service->maps($this->mapQuery(perPage: 100));
        $page = $service->maps($this->mapQuery(page: 2, perPage: 10));
        $this->assertCount(10, $page['data']);
        $this->assertSame($all['total'], $page['total']);
        $this->assertSame(array_slice(array_column($all['data'], 'map'), 10, 10), array_column($page['data'], 'map'));

        $filtered = $service->maps($this->mapQuery(map: 'prt_fild', perPage: 100));
        $this->assertGreaterThan(0, $filtered['total']);
        foreach ($filtered['data'] as $row) {
            $this->assertStringContainsString('prt_fild', $row['map']);
        }
    }

    public function test_reads_the_server_map_index(): void
    {
        $root = storage_path('framework/testing/world-data');
        File::deleteDirectory($root);
        File::ensureDirectoryExists($root);
        File::put($root.'/map_index.txt', "prontera 0\n// comment\nprt_fild01\nnew_1-1\n1@nyd\n");
        config(['happyro.game_data.map_index_path' => $root.'/map_index.txt']);

        $maps = array_column($this->service()->maps($this->mapQuery(gameOnly: false, perPage: 100))['data'], 'map');

        sort($maps);
        $this->assertSame(['1@nyd', 'new_1-1', 'prontera', 'prt_fild01'], $maps);

        File::deleteDirectory($root);
    }

    public function test_the_merged_catalog_is_rebuilt_when_the_map_index_changes(): void
    {
        $root = storage_path('framework/testing/world-data-cache');
        File::deleteDirectory($root);
        File::ensureDirectoryExists($root);
        $index = $root.'/map_index.txt';
        File::put($index, "prontera 0\n");
        config(['happyro.game_data.map_index_path' => $index]);
        Cache::flush();

        $this->assertSame(['prontera'], array_column($this->service()->maps($this->mapQuery(gameOnly: false))['data'], 'map'));

        File::put($index, "prontera 0\ngeffen 1\n");
        touch($index, time() + 5);

        $this->assertSame(
            ['prontera', 'geffen'],
            array_column($this->service()->maps($this->mapQuery(gameOnly: false))['data'], 'map'),
        );

        File::deleteDirectory($root);
    }

    private function service(): WorldDataService
    {
        return app(WorldDataService::class);
    }

    private function mapQuery(
        bool $gameOnly = true,
        ?string $map = null,
        int $page = 1,
        int $perPage = 20,
    ): MapQuery {
        return new MapQuery(
            gameOnly: $gameOnly,
            channelsEnabled: false,
            map: $map,
            page: $page,
            perPage: $perPage,
        );
    }
}
