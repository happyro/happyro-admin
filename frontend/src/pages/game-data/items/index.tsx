import { EyeOutlined } from '@ant-design/icons';
import {
  PageContainer,
  type ProColumns,
  type ProFormInstance,
  ProTable,
} from '@ant-design/pro-components';
import { useAccess, useIntl } from '@umijs/max';
import { App, Button, Descriptions, Drawer, Tag } from 'antd';
import { useEffect, useMemo, useRef, useState } from 'react';
import ItemGrantModal from '@/components/ItemGrantModal';
import {
  type GameDataItem,
  getItem,
  type ItemQuery,
  itemName,
  listItems,
  listItemVersions,
} from '@/services/game-data/items';

type Versions = { client: string[]; server: string[] };
type Translate = (id: string, fallback: string) => string;

const ITEM_TYPES = [
  'Healing',
  'Usable',
  'DelayConsume',
  'Etc',
  'Armor',
  'Weapon',
  'Card',
  'PetEgg',
  'PetArmor',
  'Ammo',
  'ShadowGear',
  'Cash',
];

function itemTypeLabel(type: string | undefined, t: Translate): string {
  return type ? t(`gameData.item.type.${type.toLowerCase()}`, type) : '-';
}

function sourceLabel(source: GameDataItem['source'], t: Translate): string {
  return t(`gameData.item.source.${source}`, source);
}

export default function Items() {
  const intl = useIntl();
  const access = useAccess();
  const { message } = App.useApp();
  const formRef = useRef<ProFormInstance | undefined>(undefined);
  const [detail, setDetail] = useState<GameDataItem>();
  const [versions, setVersions] = useState<Versions>({
    client: [],
    server: [],
  });
  const t = (id: string, fallback: string) =>
    intl.formatMessage({ id, defaultMessage: fallback });
  const locale = intl.locale;

  useEffect(() => {
    listItemVersions()
      .then(({ data }) => {
        setVersions(data);
        formRef.current?.setFieldsValue({
          clientVersion: data.client[0],
          serverVersion: data.server[0],
        });
      })
      .catch(() =>
        message.error(
          intl.formatMessage({
            id: 'app.error.chunk.description.online',
            defaultMessage: '页面资料加载失败，请重新加载重试。',
          }),
        ),
      );
  }, []);

  const columns = useMemo<ProColumns<GameDataItem>[]>(
    () => [
      {
        title: t('gameData.item.icon', '图标'),
        dataIndex: 'illustration',
        search: false,
        width: 72,
        render: (_, row) => (
          <span
            style={{
              display: 'inline-flex',
              width: 48,
              height: 64,
              alignItems: 'center',
              justifyContent: 'center',
              background: '#fff',
              overflow: 'hidden',
            }}
          >
            <img
              src={row.illustration || row.icon}
              alt={itemName(row, locale)}
              width={48}
              height={64}
              style={{ objectFit: 'contain', display: 'block' }}
            />
          </span>
        ),
      },
      {
        title: t('gameData.item.id', '物品 ID'),
        dataIndex: 'Id',
        search: false,
        width: 100,
      },
      {
        title: t('gameData.item.name', '名称'),
        dataIndex: 'query',
        render: (_, row) => itemName(row, locale),
      },
      {
        title: t('gameData.item.aegisName', 'AegisName'),
        dataIndex: 'AegisName',
        search: false,
      },
      {
        title: t('gameData.item.type', '类型'),
        dataIndex: 'type',
        valueEnum: Object.fromEntries(
          ITEM_TYPES.map((type) => [type, itemTypeLabel(type, t)]),
        ),
        render: (_, row) => itemTypeLabel(row.Type, t),
      },
      {
        title: t('gameData.item.weight', '重量'),
        dataIndex: 'Weight',
        search: false,
      },
      {
        title: t('gameData.item.range', '数据范围'),
        dataIndex: 'range',
        hideInTable: true,
        initialValue: 'client',
        valueEnum: {
          client: t('gameData.item.range.client', '仅客户端'),
          server: t('gameData.item.range.server', '仅服务端'),
          all: t('gameData.item.range.all', '全部'),
        },
      },
      {
        title: t('gameData.item.clientVersion', '客户端资源'),
        dataIndex: 'clientVersion',
        hideInTable: true,
        valueEnum: Object.fromEntries(
          versions.client.map((version) => [version, version]),
        ),
      },
      {
        title: t('gameData.item.serverVersion', '服务端版本'),
        dataIndex: 'serverVersion',
        hideInTable: true,
        valueEnum: Object.fromEntries(
          versions.server.map((version) => [version, version.slice(0, 10)]),
        ),
      },
      {
        title: t('common.actions', '操作'),
        valueType: 'option',
        render: (_, row) => (
          <>
            {access.canGrantItems && (
              <ItemGrantModal
                itemId={row.Id}
                itemName={itemName(row, locale)}
              />
            )}
            <Button
              type="link"
              icon={<EyeOutlined />}
              onClick={async () => {
                const query = formRef.current?.getFieldsValue() as ItemQuery;
                setDetail((await getItem(row.Id, query)).data);
              }}
            >
              {t('common.detail', '详情')}
            </Button>
          </>
        ),
      },
    ],
    [access.canGrantItems, intl.locale, versions],
  );

  return (
    <PageContainer title={t('gameData.items.title', '物品图鉴')}>
      <ProTable<GameDataItem>
        formRef={formRef}
        rowKey="Id"
        columns={columns}
        search={{ defaultCollapsed: false }}
        request={async (params) => {
          const result = await listItems(params);
          return { data: result.data, total: result.total, success: true };
        }}
      />
      <Drawer
        title={
          detail
            ? itemName(detail, locale)
            : t('gameData.item.detail', '物品详情')
        }
        open={Boolean(detail)}
        onClose={() => setDetail(undefined)}
        size={520}
      >
        {detail && <ItemDetails item={detail} locale={locale} t={t} />}
      </Drawer>
    </PageContainer>
  );
}

function ItemDetails({
  item,
  locale,
  t,
}: {
  item: GameDataItem;
  locale: string;
  t: Translate;
}) {
  return (
    <>
      <img
        src={item.illustration || item.icon}
        alt={itemName(item, locale)}
        width={75}
        height={100}
        style={{
          objectFit: 'contain',
          display: 'block',
          margin: '0 auto 16px',
        }}
      />
      <Descriptions column={1} bordered size="small">
        <Descriptions.Item label={t('gameData.item.id', '物品 ID')}>
          {item.Id}
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.item.name', '名称')}>
          {itemName(item, locale)}
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.item.aegisName', 'AegisName')}>
          {item.AegisName || '-'}
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.item.type', '类型')}>
          <Tag>{itemTypeLabel(item.Type, t)}</Tag>
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.item.source', '数据来源')}>
          <Tag>{sourceLabel(item.source, t)}</Tag>
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.item.buy', '买入价')}>
          {item.Buy ?? '-'}
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.item.sell', '卖出价')}>
          {item.Sell ?? '-'}
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.item.description', '说明')}>
          {item.description?.length
            ? item.description.map((line) => <div key={line}>{line}</div>)
            : t('gameData.item.noDescription', '暂无说明')}
        </Descriptions.Item>
        <Descriptions.Item label={t('gameData.item.script', '服务器脚本')}>
          {item.Script || '-'}
        </Descriptions.Item>
      </Descriptions>
    </>
  );
}
