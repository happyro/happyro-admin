import { EyeOutlined } from '@ant-design/icons';
import {
  PageContainer,
  type ProColumns,
  type ProFormInstance,
  ProTable,
} from '@ant-design/pro-components';
import { useIntl } from '@umijs/max';
import { App, Button, Descriptions, Drawer, Empty, Space, Tag } from 'antd';
import { useEffect, useMemo, useRef, useState } from 'react';
import {
  MONSTER_ELEMENTS,
  MONSTER_RACES,
  MONSTER_SIZES,
} from '@/data/game/monster-types';
import {
  type GameDataMonster,
  getMonster,
  listMonsters,
  listMonsterVersions,
  monsterName,
} from '@/services/game-data/monsters';

type Translate = (id: string, fallback: string) => string;
const typeLabel = (group: string, value: string, t: Translate) =>
  t(`gameData.monster.${group}.${value.toLowerCase()}`, value);

export default function Monsters() {
  const intl = useIntl();
  const { message } = App.useApp();
  const formRef = useRef<ProFormInstance | undefined>(undefined);
  const [detail, setDetail] = useState<GameDataMonster>();
  const [versions, setVersions] = useState<string[]>([]);
  const t = (id: string, fallback: string) =>
    intl.formatMessage({ id, defaultMessage: fallback });
  const locale = intl.locale;
  useEffect(() => {
    listMonsterVersions()
      .then(({ data }) => {
        setVersions(data);
        formRef.current?.setFieldValue('serverVersion', data[0]);
      })
      .catch(() =>
        message.error(t('gameData.monster.loadFailed', '魔物资料加载失败')),
      );
  }, []);
  const columns = useMemo<ProColumns<GameDataMonster>[]>(
    () => [
      {
        title: t('gameData.monster.image', '形象'),
        dataIndex: 'image',
        search: false,
        width: 96,
        render: (_, row) => <MonsterImage monster={row} locale={locale} />,
      },
      {
        title: t('gameData.monster.id', '魔物 ID'),
        dataIndex: 'Id',
        search: false,
        width: 100,
      },
      {
        title: t('gameData.monster.name', '名称'),
        dataIndex: 'query',
        render: (_, row) => monsterName(row, locale),
      },
      { title: 'AegisName', dataIndex: 'AegisName', search: false },
      {
        title: t('gameData.monster.level', '等级'),
        dataIndex: 'Level',
        search: false,
        width: 80,
      },
      { title: 'HP', dataIndex: 'Hp', search: false, width: 100 },
      {
        title: t('gameData.monster.race', '种族'),
        dataIndex: 'race',
        valueEnum: Object.fromEntries(
          MONSTER_RACES.map((value) => [value, typeLabel('race', value, t)]),
        ),
        render: (_, row) => typeLabel('race', row.Race, t),
      },
      {
        title: t('gameData.monster.element', '属性'),
        dataIndex: 'element',
        valueEnum: Object.fromEntries(
          MONSTER_ELEMENTS.map((value) => [
            value,
            typeLabel('element', value, t),
          ]),
        ),
        render: (_, row) =>
          `${typeLabel('element', row.Element, t)} ${row.ElementLevel}`,
      },
      {
        title: t('gameData.monster.size', '体型'),
        dataIndex: 'size',
        hideInTable: true,
        valueEnum: Object.fromEntries(
          MONSTER_SIZES.map((value) => [value, typeLabel('size', value, t)]),
        ),
      },
      {
        title: t('gameData.monster.boss', '首领'),
        dataIndex: 'boss',
        hideInTable: true,
        valueType: 'select',
        valueEnum: { 1: t('common.yes', '是'), 0: t('common.no', '否') },
      },
      {
        title: t('gameData.item.serverVersion', '服务端版本'),
        dataIndex: 'serverVersion',
        hideInTable: true,
        valueEnum: Object.fromEntries(
          versions.map((version) => [version, version.slice(0, 10)]),
        ),
      },
      {
        title: t('common.actions', '操作'),
        valueType: 'option',
        width: 90,
        render: (_, row) => (
          <Button
            type="link"
            icon={<EyeOutlined />}
            onClick={async () =>
              setDetail(
                (
                  await getMonster(
                    row.Id,
                    formRef.current?.getFieldValue('serverVersion'),
                  )
                ).data,
              )
            }
          >
            {t('common.detail', '详情')}
          </Button>
        ),
      },
    ],
    [intl.locale, versions],
  );
  return (
    <PageContainer title={t('gameData.monsters.title', '魔物图鉴')}>
      <ProTable<GameDataMonster>
        formRef={formRef}
        rowKey="Id"
        columns={columns}
        search={{ defaultCollapsed: false }}
        request={async (params) => ({
          ...(await listMonsters(params)),
          success: true,
        })}
      />
      <Drawer
        title={
          detail
            ? monsterName(detail, locale)
            : t('gameData.monster.detail', '魔物详情')
        }
        open={Boolean(detail)}
        onClose={() => setDetail(undefined)}
        size={560}
      >
        {detail && <MonsterDetails monster={detail} locale={locale} t={t} />}
      </Drawer>
    </PageContainer>
  );
}

function MonsterImage({
  monster,
  locale,
}: {
  monster: GameDataMonster;
  locale: string;
}) {
  return (
    <span
      style={{
        display: 'inline-flex',
        width: 72,
        height: 72,
        alignItems: 'center',
        justifyContent: 'center',
      }}
    >
      {monster.image ? (
        <img
          src={monster.image}
          alt={monsterName(monster, locale)}
          width={72}
          height={72}
          style={{ objectFit: 'contain', imageRendering: 'pixelated' }}
        />
      ) : (
        <Empty
          image={Empty.PRESENTED_IMAGE_SIMPLE}
          description={false}
          styles={{ image: { height: 32, margin: 0 } }}
        />
      )}
    </span>
  );
}

function MonsterDetails({
  monster,
  locale,
  t,
}: {
  monster: GameDataMonster;
  locale: string;
  t: Translate;
}) {
  const stats = ['Str', 'Agi', 'Vit', 'Int', 'Dex', 'Luk'] as const;
  return (
    <Space orientation="vertical" size={16} style={{ width: '100%' }}>
      <div style={{ textAlign: 'center' }}>
        <MonsterImage monster={monster} locale={locale} />
      </div>
      <Descriptions bordered size="small" column={2}>
        <Descriptions.Item label={t('gameData.monster.id', '魔物 ID')}>
          {monster.Id}
        </Descriptions.Item>
        <Descriptions.Item label="AegisName">
          {monster.AegisName}
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.monster.level', '等级')}>
          {monster.Level}
        </Descriptions.Item>
        <Descriptions.Item label="HP">
          {monster.Hp.toLocaleString()}
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.monster.race', '种族')}>
          {typeLabel('race', monster.Race, t)}
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.monster.element', '属性')}>
          {typeLabel('element', monster.Element, t)} {monster.ElementLevel}
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.monster.size', '体型')}>
          {typeLabel('size', monster.Size, t)}
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.monster.class', '级别')}>
          <Tag color={monster.isBoss ? 'gold' : undefined}>
            {monster.isBoss
              ? t('gameData.monster.boss', '首领')
              : t('gameData.monster.normal', '普通')}
          </Tag>
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.monster.baseExp', '基础经验')}>
          {monster.BaseExp ?? 0}
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.monster.jobExp', '职业经验')}>
          {monster.JobExp ?? 0}
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.monster.attack', '攻击')}>
          {monster.Attack ?? 0} - {monster.Attack2 ?? monster.Attack ?? 0}
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.monster.defense', '防御')}>
          {monster.Defense ?? 0} / {monster.MagicDefense ?? 0}
        </Descriptions.Item>
        {stats.map((stat) => (
          <Descriptions.Item key={stat} label={stat}>
            {monster[stat] ?? 1}
          </Descriptions.Item>
        ))}
        <Descriptions.Item
          span={2}
          label={t('gameData.monster.drops', '掉落物品')}
        >
          <DropList drops={monster.Drops} t={t} />
        </Descriptions.Item>
        {monster.MvpDrops && (
          <Descriptions.Item
            span={2}
            label={t('gameData.monster.mvpDrops', 'MVP 奖励')}
          >
            <DropList drops={monster.MvpDrops} t={t} />
          </Descriptions.Item>
        )}
      </Descriptions>
    </Space>
  );
}

function DropList({
  drops,
  t,
}: {
  drops?: { Item: string; Rate: number }[];
  t: Translate;
}) {
  return drops?.length ? (
    <Space wrap>
      {drops.map((drop) => (
        <Tag key={`${drop.Item}-${drop.Rate}`}>
          {drop.Item} · {(drop.Rate / 100).toFixed(2)}%
        </Tag>
      ))}
    </Space>
  ) : (
    t('gameData.monster.noDrops', '无掉落资料')
  );
}
