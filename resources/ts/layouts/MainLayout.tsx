import { ConfigProvider, Layout, Menu, Typography, Avatar, Dropdown, Button } from 'antd';
import { useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import type { MenuProps } from 'antd';
import {
    UserOutlined,
    LogoutOutlined,
    MenuFoldOutlined,
    MenuUnfoldOutlined,
} from '@ant-design/icons';

declare global {
    interface Window {
        route: (name: string, params?: any) => string;
    }
}

const { Header, Sider, Content } = Layout;
const { Title, Text } = Typography;

interface MenuItem {
    key: string;
    label: React.ReactNode;
    icon?: React.ReactNode;
    children?: MenuItem[];
}

interface AuthUser {
    id: string;
    username: string | null;
    full_name: string | null;
    photo_path: string | null;
    roles: string[];
    permissions: string[];
}

interface PortalLayoutProps {
    children: React.ReactNode;
    menu?: {
        main: MenuItem[];
        left: MenuItem[];
    };
}

export default function PortalLayout({ children, menu }: PortalLayoutProps) {
    const [collapsed, setCollapsed] = useState(false);
    const pageProps = usePage().props as Record<string, any>;
    const user = pageProps.auth?.user as AuthUser | null;

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

    return (
        <ConfigProvider
            theme={{
                token: {
                    borderRadius: 8,
                    colorPrimary: '#1677ff',
                },
                cssVar: true,
                hashed: false,
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
                    <div style={{ height: 32, margin: 16, textAlign: 'center' }}>
                        <Title level={4} style={{ margin: 0, color: '#fff' }}>
                            {collapsed ? 'AP' : 'App Portal'}
                        </Title>
                    </div>
                    {menu?.left && menu.left.length > 0 && (
                        <Menu
                            theme="dark"
                            mode="inline"
                            items={menu.left as MenuProps['items']}
                            defaultSelectedKeys={['/']}
                        />
                    )}
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
                        {menu?.main && menu.main.length > 0 && (
                            <Menu
                                mode="horizontal"
                                items={menu.main as MenuProps['items']}
                                style={{ flex: 1, maxWidth: 600, border: 'none' }}
                            />
                        )}
                        <Title level={5} style={{ margin: 0, flexShrink: 0 }}>
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
