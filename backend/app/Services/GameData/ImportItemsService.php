<?php

namespace App\Services\GameData;

use App\Contracts\GameData\ItemSnapshotReader;
use App\Data\GameData\ItemCatalogSnapshot;
use App\Models\GameDataCatalog;
use App\Models\GameDataItem;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

final class ImportItemsService
{
    public function __construct(
        private readonly ItemSnapshotReader $snapshots,
        private readonly DatabaseManager $database,
    ) {}

    /** @return array{source: string, ruleset: string, version: string, imported: int, deleted: int} */
    public function import(string $path): array
    {
        $snapshot = $this->snapshots->read($path);

        return $this->database->transaction(fn (): array => $this->persist($snapshot));
    }

    /** @return array{source: string, ruleset: string, version: string, imported: int, deleted: int} */
    private function persist(ItemCatalogSnapshot $snapshot): array
    {
        if ($snapshot->items === []) {
            throw new RuntimeException('Item snapshot must not be empty');
        }

        $catalog = GameDataCatalog::query()->firstOrCreate([
            'resource_type' => 'items',
            'source' => $snapshot->source,
            'ruleset' => $snapshot->ruleset,
            'source_version' => $snapshot->version,
        ], [
            'content_hash' => $snapshot->contentHash,
            'record_count' => 0,
            'source_metadata' => $snapshot->metadata,
            'imported_at' => now(),
        ]);
        $syncToken = (string) Str::uuid();
        $now = now();

        foreach (array_chunk($snapshot->items, 500, true) as $items) {
            $rows = [];
            foreach ($items as $itemId => $item) {
                $rows[] = $this->row($catalog->id, (string) $itemId, $item, $syncToken, $now);
            }
            GameDataItem::query()->upsert(
                $rows,
                ['game_data_catalog_id', 'item_id'],
                ['name_zh_cn', 'name_en_us', 'aegis_name', 'item_type', 'resource_name', 'description', 'payload', 'sync_token', 'updated_at'],
            );
        }

        $deleted = GameDataItem::query()
            ->where('game_data_catalog_id', $catalog->id)
            ->where('sync_token', '!=', $syncToken)
            ->delete();
        $catalog->update([
            'content_hash' => $snapshot->contentHash,
            'record_count' => count($snapshot->items),
            'source_metadata' => $snapshot->metadata,
            'imported_at' => $now,
        ]);

        return [
            'source' => $snapshot->source,
            'ruleset' => $snapshot->ruleset,
            'version' => $snapshot->version,
            'imported' => count($snapshot->items),
            'deleted' => $deleted,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function row(int $catalogId, string $itemId, array $item, string $syncToken, mixed $now): array
    {
        if (! ctype_digit($itemId) || ! is_array($item['names'] ?? null)) {
            throw new RuntimeException("Invalid item snapshot entry: {$itemId}");
        }
        $chineseName = $item['names']['zh-CN'] ?? null;
        $englishName = $item['names']['en-US'] ?? null;
        if (! is_string($chineseName) || $chineseName === '' || ! is_string($englishName) || $englishName === '') {
            throw new RuntimeException("Item snapshot names are incomplete: {$itemId}");
        }

        return [
            'game_data_catalog_id' => $catalogId,
            'item_id' => (int) $itemId,
            'name_zh_cn' => $chineseName,
            'name_en_us' => $englishName,
            'aegis_name' => is_string($item['AegisName'] ?? null) ? $item['AegisName'] : null,
            'item_type' => is_string($item['Type'] ?? null) ? $item['Type'] : null,
            'resource_name' => is_string($item['identifiedResourceName'] ?? null) ? $item['identifiedResourceName'] : null,
            'description' => $this->json($this->description($item['identifiedDescriptionName'] ?? null)),
            'payload' => $this->json(Arr::except($item, ['names', 'identifiedResourceName', 'identifiedDescriptionName'])),
            'sync_token' => $syncToken,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function json(mixed $value): ?string
    {
        return $value === null ? null : json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    /** @return list<string>|null */
    private function description(mixed $value): ?array
    {
        if (! is_array($value)) {
            return null;
        }
        $lines = array_values(array_filter(array_map(function (mixed $line): string {
            if (! is_string($line)) {
                return '';
            }
            $plain = preg_replace('/\^[0-9a-fA-F]{6}/', '', $line) ?? '';

            return trim($plain) === '_' ? '' : trim($plain);
        }, $value)));

        return $lines === [] ? null : $lines;
    }
}
