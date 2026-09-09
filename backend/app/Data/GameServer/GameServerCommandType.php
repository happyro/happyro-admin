<?php

namespace App\Data\GameServer;

enum GameServerCommandType: string
{
    case CharacterSnapshot = 'character.snapshot';
    case CharacterProgressionUpdate = 'character.progression.update';
    case CharacterStatsUpdate = 'character.stats.update';
    case CharacterStatsReset = 'character.stats.reset';
    case CharacterSkillsReset = 'character.skills.reset';
    case CharacterVitalsRestore = 'character.vitals.restore';
    case CharacterInventoryItemGrant = 'character.inventory.item_grant';
    case MonsterSpawn = 'monster.spawn';
    case BattleConfigApply = 'battle_config.apply';
}
