<?php

namespace Tests\Unit\GameData;

use App\Contracts\GameData\ItemAssetRepository;
use Tests\TestCase;

final class LocalItemAssetRepositoryTest extends TestCase
{
    public function test_resolves_an_existing_client_icon(): void
    {
        /** @var ItemAssetRepository $assets */
        $assets = app(ItemAssetRepository::class);

        $this->assertNotNull($assets->iconPath(670));
    }
}
