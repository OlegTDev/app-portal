import { Head, useForm, usePage } from '@inertiajs/react';
import { Button, Form, Input, Card, Typography, Alert } from 'antd';
import { UserOutlined, LockOutlined } from '@ant-design/icons';

const { Title, Text } = Typography;

export default function Login() {
    const flash = usePage().props.flash || {};

    const { data, setData, post, processing, errors: formErrors } = useForm({
        username: '',
        password: '',
    });

    const handleSubmit = (values: { username: string; password: string }) => {
        post('/login');
    };

    return (
        <>
            <Head title="Вход" />

            <div
                style={{
                    minHeight: '100vh',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                }}
            >
                <Card
                    style={{
                        width: 400,
                        borderRadius: 12,
                        boxShadow: '0 8px 32px rgba(0,0,0,0.1)',
                    }}
                >
                    <div style={{ textAlign: 'center', marginBottom: 32 }}>
                        <Title level={3} style={{ margin: 0 }}>
                            Корпоративный портал
                        </Title>
                        <Text type="secondary">Войдите в свой аккаунт</Text>
                    </div>

                    {flash.error && (
                        <Alert
                            message={flash.error}
                            type="error"
                            showIcon
                            style={{ marginBottom: 24 }}
                        />
                    )}

                    {flash.success && (
                        <Alert
                            message={flash.success}
                            type="success"
                            showIcon
                            style={{ marginBottom: 24 }}
                        />
                    )}

                    <Form
                        onFinish={handleSubmit}
                        layout="vertical"
                        size="large"
                    >
                        <Form.Item
                            label="Логин"
                            name="username"
                            validateStatus={formErrors.username ? 'error' : ''}
                            help={formErrors.username}
                        >
                            <Input
                                prefix={<UserOutlined />}
                                placeholder="Введите логин"
                                value={data.username}
                                onChange={(e) => setData('username', e.target.value)}
                            />
                        </Form.Item>

                        <Form.Item
                            label="Пароль"
                            name="password"
                            validateStatus={formErrors.password ? 'error' : ''}
                            help={formErrors.password}
                        >
                            <Input.Password
                                prefix={<LockOutlined />}
                                placeholder="Введите пароль"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                            />
                        </Form.Item>

                        <Form.Item style={{ marginBottom: 0 }}>
                            <Button
                                type="primary"
                                htmlType="submit"
                                loading={processing}
                                block
                                size="large"
                            >
                                Войти
                            </Button>
                        </Form.Item>
                    </Form>
                </Card>
            </div>
        </>
    );
}
