import { PageContainer, ProForm } from '@ant-design/pro-components';
import { useIntl } from '@umijs/max';
import { App, Image } from 'antd';
import { useState } from 'react';
import ItemGrantFormFields from '@/components/ItemGrantFormFields';
import { mailItem } from '@/services/operations/item-grants';

export default function ItemGrants() {
  const [selectedItemId, setSelectedItemId] = useState<number>();
  const intl = useIntl();
  const { message } = App.useApp();
  const t = (id: string, fallback: string) =>
    intl.formatMessage({ id, defaultMessage: fallback });

  return (
    <PageContainer title={t('operations.itemGrants.title', '发放物品')}>
      <ProForm
        onFinish={async (values) => {
          await mailItem({
            item_id: Number(values.item_id),
            char_id: Number(values.char_id),
            amount: Number(values.amount),
            title: String(values.title),
            message: String(values.message),
            bound: Boolean(values.bound),
          });
          message.success(t('operations.itemGrants.success', '邮件已发送'));
          return true;
        }}
      >
        {selectedItemId !== undefined && (
          <div
            style={{
              display: 'flex',
              justifyContent: 'center',
              minHeight: 100,
              marginBottom: 16,
            }}
          >
            <Image
              src={`/api/game-data/items/${selectedItemId}/illustration`}
              fallback={`/api/game-data/items/${selectedItemId}/icon`}
              alt={t('operations.itemGrants.item', '物品')}
              width={75}
              height={100}
              preview
              styles={{ image: { objectFit: 'contain' } }}
            />
          </div>
        )}
        <ItemGrantFormFields onItemChange={setSelectedItemId} />
      </ProForm>
    </PageContainer>
  );
}
