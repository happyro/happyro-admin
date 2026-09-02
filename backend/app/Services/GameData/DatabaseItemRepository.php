<?php

namespace App\Services\GameData;

use App\Contracts\GameData\ItemRepository;
use App\Data\GameData\ItemQuery;
use App\Models\GameDataCatalog;
use App\Models\GameDataItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class DatabaseItemRepository implements ItemRepository
{
    public function search(ItemQuery $query): array
    {
        $catalogs = $this->catalogs($query->range, $query->clientVersion, $query->serverVersion);
        if ($catalogs->isEmpty()) {
            return ['data' => [], 'total' => 0];
        }

        $catalogIds = $catalogs->pluck('id');
        $ids = GameDataItem::query()
            ->whereIn('game_data_catalog_id', $catalogIds)
            ->when($query->query, fn (Builder $builder, string $value): Builder => $this->matching($builder, $catalogIds, $value))
            ->when($query->type, fn (Builder $builder, string $value): Builder => $this->matchingType($builder, $catalogIds, $value))
            ->select('item_id')
            ->distinct();
        $total = (clone $ids)->count('item_id');
        $pageIds = $ids->orderBy('item_id')->forPage($query->page, $query->perPage)->pluck('item_id');
        $records = GameDataItem::query()
            ->with('catalog:id,source,source_version')
            ->whereIn('game_data_catalog_id', $catalogIds)
            ->whereIn('item_id', $pageIds)
            ->get()
            ->groupBy('item_id');

        return [
            'data' => $pageIds->map(fn (int $itemId): array => $this->merge($records->get($itemId, collect())))->all(),
            'total' => $total,
        ];
    }

    public function find(int $itemId, string $range, string $clientVersion, string $serverVersion): ?array
    {
        $catalogs = $this->catalogs($range, $clientVersion, $serverVersion);
        $records = GameDataItem::query()
            ->with('catalog:id,source,source_version')
            ->whereIn('game_data_catalog_id', $catalogs->pluck('id'))
            ->where('item_id', $itemId)
            ->get();

        return $records->isEmpty() ? null : $this->merge($records);
    }

    public function versions(): array
    {
        $catalogs = GameDataCatalog::query()
            ->where('resource_type', 'items')
            ->where(fn (Builder $query): Builder => $query
                ->where(fn (Builder $client): Builder => $client->where('source', 'client')->where('ruleset', 'client'))
                ->orWhere(fn (Builder $server): Builder => $server->where('source', 'server')->where('ruleset', 'renewal')))
            ->orderByDesc('imported_at')
            ->get(['source', 'source_version'])
            ->groupBy('source');

        return [
            'client' => $catalogs->get('client', collect())->pluck('source_version')->unique()->values()->all(),
            'server' => $catalogs->get('server', collect())->pluck('source_version')->unique()->values()->all(),
        ];
    }

    /** @return Collection<int, GameDataCatalog> */
    private function catalogs(string $range, string $clientVersion, string $serverVersion): Collection
    {
        return GameDataCatalog::query()
            ->where('resource_type', 'items')
            ->where(function (Builder $query) use ($range, $clientVersion, $serverVersion): void {
                if ($range !== 'server') {
                    $query->orWhere(fn (Builder $client): Builder => $client
                        ->where('source', 'client')
                        ->where('ruleset', 'client')
                        ->where('source_version', $clientVersion));
                }
                if ($range !== 'client') {
                    $query->orWhere(fn (Builder $server): Builder => $server
                        ->where('source', 'server')
                        ->where('ruleset', 'renewal')
                        ->where('source_version', $serverVersion));
                }
            })
            ->get();
    }

    private function matching(Builder $builder, Collection $catalogIds, string $value): Builder
    {
        $escaped = addcslashes($value, '%_\\');

        return $builder->whereIn('item_id', GameDataItem::query()
            ->whereIn('game_data_catalog_id', $catalogIds)
            ->where(function (Builder $match) use ($escaped, $value): void {
                $match->where('name_zh_cn', 'like', "%{$escaped}%")
                    ->orWhere('name_en_us', 'like', "%{$escaped}%")
                    ->orWhere('aegis_name', 'like', "%{$escaped}%");
                if (ctype_digit($value)) {
                    $match->orWhere('item_id', (int) $value);
                }
            })
            ->select('item_id'));
    }

    private function matchingType(Builder $builder, Collection $catalogIds, string $type): Builder
    {
        return $builder->whereIn('item_id', GameDataItem::query()
            ->whereIn('game_data_catalog_id', $catalogIds)
            ->where('item_type', $type)
            ->select('item_id'));
    }

    /**
     * @param  Collection<int, GameDataItem>  $records
     * @return array<string, mixed>
     */
    private function merge(Collection $records): array
    {
        $client = $records->first(fn (GameDataItem $item): bool => $item->catalog->source === 'client');
        $server = $records->first(fn (GameDataItem $item): bool => $item->catalog->source === 'server');
        $primary = $client ?? $server;
        $payload = [...($server?->payload ?? []), ...($client?->payload ?? [])];

        return [
            'Id' => $primary->item_id,
            ...$payload,
            'AegisName' => $server?->aegis_name,
            'Type' => $server?->item_type,
            'names' => [
                'zh-CN' => $client?->name_zh_cn ?? $server?->name_zh_cn,
                'en-US' => $client?->name_en_us ?? $server?->name_en_us,
            ],
            'resourceName' => $client?->resource_name,
            'description' => $client?->description,
            'source' => $client && $server ? 'both' : ($client ? 'client' : 'server'),
            'clientResourceVersion' => $client?->catalog->source_version,
            'serverVersion' => $server?->catalog->source_version,
        ];
    }
}
