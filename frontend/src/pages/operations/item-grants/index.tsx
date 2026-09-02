import { PageContainer, ProForm } from '@ant-design/pro-components';
import { useIntl } from '@umijs/max';
import { App } from 'antd';
import ItemGrantFormFields from '@/components/ItemGrantFormFields';
import { mailItem } from '@/services/operations/item-grants';

export default function ItemGrants() {
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
        <ItemGrantFormFields />
      </ProForm>
    </PageContainer>
  );
}
