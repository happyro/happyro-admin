<?php

namespace App\Models;

use Database\Factories\GameDataItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameDataItem extends Model
{
    /** @use HasFactory<GameDataItemFactory> */
    use HasFactory;

    protected $fillable = [
        'game_data_catalog_id',
        'item_id',
        'name_zh_cn',
        'name_en_us',
        'aegis_name',
        'item_type',
        'resource_name',
        'description',
        'payload',
        'sync_token',
    ];

    public function catalog(): BelongsTo
    {
        return $this->belongsTo(GameDataCatalog::class, 'game_data_catalog_id');
    }

    protected function casts(): array
    {
        return [
            'item_id' => 'integer',
            'description' => 'array',
            'payload' => 'array',
        ];
    }
}
