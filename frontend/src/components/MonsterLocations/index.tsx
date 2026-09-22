import { useAccess, useIntl } from '@umijs/max';
import { Empty, Space } from 'antd';
import CharacterNavigationModal from '@/components/CharacterNavigationModal';
import { mapMappings } from '@/data/game/maps';
import spawnCatalog from '@/data/game/monster-spawns.json';

type Spawn = { map: string; x: number; y: number; count: number };
const spawns: Record<string, Spawn[]> = spawnCatalog.monsters;

export default function MonsterLocations({ monsterId }: { monsterId: number }) {
  const intl = useIntl();
  const access = useAccess();
  const locations = spawns[String(monsterId)] ?? [];
  return (
    <section aria-label="出现地图">
      <h4>出现地图</h4>
      {locations.length ? (
        <Space orientation="vertical" style={{ width: '100%' }}>
          {locations.map((spawn) => {
            const name = mapMappings[spawn.map]
              ? intl.formatMessage({
                  id: mapMappings[spawn.map],
                  defaultMessage: spawn.map,
                })
              : spawn.map;
            return (
              <div key={spawn.map}>
                <Space wrap>
                  <span>
                    {name} · {spawn.map}
                  </span>
                  <span>{spawn.count} 只</span>
                  {access.canGameControl && access.canViewPlayers && (
                    <CharacterNavigationModal
                      map={spawn.map}
                      mapName={name}
                      name={name}
                      x={spawn.x}
                      y={spawn.y}
                    />
                  )}
                </Space>
              </div>
            );
          })}
        </Space>
      ) : (
        <Empty
          image={Empty.PRESENTED_IMAGE_SIMPLE}
          description="暂无常驻刷新地图"
        />
      )}
    </section>
  );
}
