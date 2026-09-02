import {
  PageContainer,
  ProForm,
  ProFormDigit,
  ProFormText,
} from '@ant-design/pro-components';
import { useIntl } from '@umijs/max';
import { App } from 'antd';
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
          await mailItem({ ...values, bound: Boolean(values.bound) });
          message.success(t('operations.itemGrants.success', '邮件已发送'));
        }}
      >
        <ProFormDigit
          name="item_id"
          label={t('gameData.item.id', '物品 ID')}
          min={1}
          fieldProps={{ precision: 0 }}
          rules={[{ required: true }]}
        />
        <ProFormDigit
          name="char_id"
          label={t('operations.itemGrants.charId', '角色 ID')}
          min={1}
          fieldProps={{ precision: 0 }}
          rules={[{ required: true }]}
        />
        <ProFormDigit
          name="amount"
          label={t('operations.itemGrants.amount', '数量')}
          min={1}
          max={30000}
          fieldProps={{ precision: 0 }}
          initialValue={1}
          rules={[{ required: true }]}
        />
        <ProFormText
          name="title"
          label={t('operations.itemGrants.titleField', '邮件标题')}
          initialValue={t('operations.itemGrants.defaultTitle', '物品发放')}
          rules={[{ required: true, max: 45 }]}
        />
        <ProFormText
          name="message"
          label={t('operations.itemGrants.message', '邮件内容')}
          initialValue={t(
            'operations.itemGrants.defaultMessage',
            '管理员向你发放了物品。',
          )}
          rules={[{ required: true, max: 500 }]}
        />
      </ProForm>
      ;
    </PageContainer>
  );
}
