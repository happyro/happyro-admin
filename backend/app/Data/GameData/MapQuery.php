<?php

namespace App\Data\GameData;

final readonly class MapQuery
{
    /**
     * @param  string|null  $map  Map code substring, for a dedicated column filter.
     * @param  string|null  $name  Localized name substring, for a dedicated column filter.
     * @param  string|null  $query  Matches the map code or the localized name, for a single search box.
     * @param  string|null  $onMap  Exact map code, for a "current map only" scope.
     */
    public function __construct(
        public bool $gameOnly,
        public bool $channelsEnabled,
        public ?string $map = null,
        public ?string $name = null,
        public ?string $query = null,
        public ?string $onMap = null,
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}
