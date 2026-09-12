import { PageContainer } from '@ant-design/pro-components';
import { Link, useModel } from '@umijs/max';
import { Card, Typography } from 'antd';
import './Welcome.css';

const entries = [
  {
    href: '/game-data/items',
    title: '游戏资料',
    description: '查找物品、魔物、NPC 与地图，了解游戏内容。',
    permission: 'game-data.view',
  },
  {
    href: '/players/accounts',
    title: '用户管理',
    description: '查看玩家账号、游戏角色和登录记录。',
    permission: 'players.view',
  },
  {
    href: '/operations/item-grants',
    title: '运营发放',
    description: '选择目标角色发放物品或 Zeny，并查询发放记录。',
    permission: 'operations.item-grant',
  },
  {
    href: '/settings/game-settings',
    title: '游戏设置',
    description: '调整游戏规则与倍率，查看配置修改记录。',
    permission: 'settings.manage',
  },
];

export default function Welcome() {
  const { initialState } = useModel('@@initialState');
  const user = initialState?.currentUser;
  const permissions = user?.permissions ?? [];
  const availableEntries = entries.filter(
    (entry) =>
      permissions.includes('*') || permissions.includes(entry.permission),
  );

  return (
    <PageContainer
      title={<span className="welcome-page-title">HappyRO 管理后台</span>}
    >
      <div className="welcome-page">
        <Card title="欢迎回来">
          <Typography.Paragraph type="secondary">
            从游戏资料到玩家服务，在这里完成 HappyRO 的日常管理。
          </Typography.Paragraph>
          <Typography.Paragraph>
            查询资料与玩家信息，选择目标角色执行运营操作，或按需调整游戏设置。
          </Typography.Paragraph>
        </Card>
        <Card title="常用入口">
          {availableEntries.length ? (
            <div className="welcome-entry-grid">
              {availableEntries.map((entry) => (
                <Link
                  key={entry.href}
                  to={entry.href}
                  className="welcome-entry"
                >
                  <Card hoverable size="small">
                    <Typography.Title level={5}>{entry.title}</Typography.Title>
                    <Typography.Paragraph type="secondary">
                      {entry.description}
                    </Typography.Paragraph>
                  </Card>
                </Link>
              ))}
            </div>
          ) : (
            <Typography.Paragraph type="secondary">
              当前账号暂无业务访问权限，请联系管理员分配角色。
            </Typography.Paragraph>
          )}
        </Card>
      </div>
    </PageContainer>
  );
}
