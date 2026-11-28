import { ConfigProvider, Layout, Menu, Typography, Avatar, Dropdown, Button } from 'antd';
import { useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import type { MenuProps } from 'antd';
import {
  UserOutlined,
  LogoutOutlined,
  MenuFoldOutlined,
  MenuUnfoldOutlined,
  DashboardOutlined,
  UnorderedListOutlined,
  TeamOutlined,
  ApartmentOutlined,
} from '@ant-design/icons';

const { Header, Sider, Content } = Layout;
const { Title, Text } = Typography;

interface AdminMenuItem {
    key: string;
    label: string;
    icon?: React.ReactNode;
}

interface AuthUser {
    id: string;
    username: string | null;
    full_name: string | null;
    photo_path: string | null;
    roles: string[];
    permissions: string[];
}

interface AdminLayoutProps {
    children: React.ReactNode;
}

export default function AdminLayout({ children }: AdminLayoutProps) {
  const [collapsed, setCollapsed] = useState(false);

  interface PageProps {
    auth?: {
      user: AuthUser | null;
    };
    flash?: {
      success?: string;
      error?: string;
    };
  }

  const pageProps = usePage().props as PageProps;
  const user = pageProps.auth?.user ?? null;

  const adminMenuItems: AdminMenuItem[] = [
    {
      key: '/admin/dashboard',
      label: 'Главная',
      icon: <DashboardOutlined />,
    },
    {
      key: '/admin/organizations',
      label: 'Организации',
      icon: <ApartmentOutlined />,
    },
    {
      key: '/admin/employees',
      label: 'Сотрудники',
      icon: <TeamOutlined />,
    },
    {
      key: '/admin/menu',
      label: 'Меню',
      icon: <UnorderedListOutlined />,
    },
  ];

  const userMenuItems: MenuProps['items'] = user ? [
    {
      key: 'profile',
      label: user.full_name || user.username || 'Профиль',
      icon: <UserOutlined />,
    },
    { type: 'divider' as const },
    {
      key: 'logout',
      label: 'Выйти',
      icon: <LogoutOutlined />,
      danger: true,
      onClick: () => {
        router.post(window.route('logout'));
      },
    },
  ] : [];

  // Determine active key from current URL
  const currentPageUrl = usePage().url.replace(/\?.*$/, '');
  let activeKey = '/admin/dashboard';
  for (const item of adminMenuItems) {
    if (currentPageUrl === item.key || currentPageUrl.startsWith(item.key + '/')) {
      activeKey = item.key;
      break;
    }
  }

  const handleMenuClick = (key: string) => {
    router.visit(key);
  };

  return (
    <ConfigProvider
      theme={{
        token: {
          borderRadius: 8,
          colorPrimary: '#1677ff',
        },
        cssVar: { key: 'antd' },
      }}
    >
      <Layout style={{ minHeight: '100vh' }}>
        <Sider
          collapsible
          collapsed={collapsed}
          onCollapse={(value) => setCollapsed(value)}
          width={220}
          style={{
            overflow: 'auto',
            height: '100vh',
            position: 'fixed',
            left: 0,
            top: 0,
            bottom: 0,
          }}
        >
          <div style={{ height: 32, margin: '16px 0', textAlign: 'center' }}>
            <Title level={4} style={{ margin: 0, color: '#fff' }}>
              {collapsed ? 'AP' : 'Админ-панель'}
            </Title>
          </div>
          <Menu
            theme="dark"
            mode="inline"
            items={adminMenuItems as MenuProps['items']}
            selectedKeys={[activeKey]}
            onClick={({ key }) => handleMenuClick(key)}
          />
        </Sider>
        <Layout
          style={{
            marginLeft: collapsed ? 80 : 220,
            transition: 'margin-left 0.2s',
          }}
        >
          <Header
            style={{
              padding: '0 24px',
              background: '#fff',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'space-between',
              position: 'sticky',
              top: 0,
              zIndex: 10,
              borderBottom: '1px solid #f0f0f0',
            }}
          >
            <Button
              type="text"
              icon={collapsed ? <MenuUnfoldOutlined /> : <MenuFoldOutlined />}
              onClick={() => setCollapsed(!collapsed)}
              style={{ fontSize: '16px', width: 64, height: 32 }}
            />
            <Title level={5} style={{ margin: 0, flex: 1, textAlign: 'center' }}>
              Корпоративный портал
            </Title>
            {user ? (
              <Dropdown menu={{ items: userMenuItems }} placement="bottomRight">
                <div style={{ cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 8 }}>
                  <Avatar
                    icon={<UserOutlined />}
                    src={user.photo_path || undefined}
                  />
                  <Text>{user.full_name || user.username}</Text>
                </div>
              </Dropdown>
            ) : (
              <Button type="primary" onClick={() => router.visit(window.route('login'))}>
                Войти
              </Button>
            )}
          </Header>
          <Content
            style={{
              margin: 24,
              padding: 24,
              minHeight: 280,
              background: '#f0f2f5',
              borderRadius: 8,
            }}
          >
            {children}
          </Content>
        </Layout>
      </Layout>
    </ConfigProvider>
  );
}
