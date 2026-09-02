<?php

namespace App\Services\GameData;

use App\Contracts\GameData\ItemAssetRepository;
use RuntimeException;

final class LocalItemAssetRepository implements ItemAssetRepository
{
    private ?array $iconMap = null;

    public function __construct(
        private readonly string $iconMapPath,
        private readonly string $resourceRoot,
        private readonly string $iconRelativeRoot,
        private readonly string $illustrationRelativeRoot,
    ) {}

    public function iconPath(int $itemId): ?string
    {
        return $this->resourcePath($itemId, $this->iconRelativeRoot);
    }

    public function illustrationPath(int $itemId): ?string
    {
        return $this->resourcePath($itemId, $this->illustrationRelativeRoot);
    }

    private function resourcePath(int $itemId, string $relativeRoot): ?string
    {
        $resourceName = $this->iconMap()[(string) $itemId] ?? null;
        if (! is_string($resourceName) || $resourceName === '') {
            return null;
        }
        $root = realpath($this->resourceRoot);
        $path = realpath($this->resourceRoot.DIRECTORY_SEPARATOR.$relativeRoot.DIRECTORY_SEPARATOR.$resourceName.'.bmp');

        return $path && $root && str_starts_with($path, $root.DIRECTORY_SEPARATOR) && is_file($path) ? $path : null;
    }

    private function iconMap(): array
    {
        return $this->iconMap ??= $this->readItems($this->iconMapPath, 'Item icon map');
    }

    private function readItems(string $path, string $label): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("{$label} not found: {$path}");
        }
        $payload = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        return is_array($payload['items'] ?? null) ? $payload['items'] : [];
    }
}
