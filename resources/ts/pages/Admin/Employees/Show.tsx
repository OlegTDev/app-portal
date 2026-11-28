import { Head } from '@inertiajs/react';
import { Card, Descriptions, Tag, Avatar, Typography, Space } from 'antd';
import {
  UserOutlined,
  EnvironmentOutlined,
  ApartmentOutlined,
  CalendarOutlined,
  IdcardOutlined,
} from '@ant-design/icons';
import AdminLayout from '@/layouts/AdminLayout';

const { Title, Text, Paragraph } = Typography;

interface Employee {
  id: number;
  username: string | null;
  full_name: string | null;
  position: string | null;
  rank: string | null;
  phone: string | null;
  phone_additional: string | null;
  office: string | null;
  department_name: string | null;
  email: string | null;
  organization_code: string | null;
  photo_path: string | null;
  is_active: boolean;
  ad_data: Record<string, unknown> | null;
  organizations: Array<{ code: string; name: string; short_name: string | null }>;
  created_at: string | null;
  updated_at: string | null;
}

interface EmployeeShowProps {
  employee: Employee;
}

export default function EmployeeShow({ employee }: EmployeeShowProps) {
  return (
    <AdminLayout>
      <Head title={`Сотрудник: ${employee.full_name || 'Профиль'}`} />

      <Space orientation="vertical" size="large" style={{ width: '100%' }}>
        {/* Header Card */}
        <Card>
          <Space style={{ width: '100%' }}>
            <Avatar
              size={80}
              icon={<UserOutlined />}
              src={employee.photo_path || undefined}
            />
            <div style={{ flex: 1 }}>
              <Title level={4} style={{ margin: 0 }}>{employee.full_name || 'Не указано'}</Title>
              {employee.position && (
                <Paragraph style={{ margin: '4px 0 0' }} type="secondary">
                  {employee.position}
                </Paragraph>
              )}
              {employee.rank && (
                <Paragraph style={{ margin: '4px 0 0' }} type="secondary">
                  {employee.rank}
                </Paragraph>
              )}
              <Space style={{ marginTop: 8 }}>
                <Tag color={employee.is_active ? 'green' : 'default'}>
                  {employee.is_active ? 'Активен' : 'Уволен'}
                </Tag>
                {employee.username && (
                  <Tag icon={<IdcardOutlined />}>@{employee.username}</Tag>
                )}
              </Space>
            </div>
          </Space>
        </Card>

        {/* Contact Info */}
        <Card title="Контактная информация">
          <Descriptions bordered column={1} size="small">
            {employee.email && (
              <Descriptions.Item label="Email">
                <a href={`mailto:${employee.email}`}>{employee.email}</a>
              </Descriptions.Item>
            )}
            {employee.phone && (
              <Descriptions.Item label="Телефон">
                <a href={`tel:${employee.phone}`}>{employee.phone}</a>
              </Descriptions.Item>
            )}
            {employee.phone_additional && (
              <Descriptions.Item label="Доп. телефон">
                <a href={`tel:${employee.phone_additional}`}>{employee.phone_additional}</a>
              </Descriptions.Item>
            )}
            {employee.office && (
              <Descriptions.Item label="Кабинет">
                <Space>
                  <EnvironmentOutlined style={{ color: '#8c8c8c' }} />
                  {employee.office}
                </Space>
              </Descriptions.Item>
            )}
            {employee.department_name && (
              <Descriptions.Item label="Отдел">
                <Space>
                  <ApartmentOutlined style={{ color: '#8c8c8c' }} />
                  {employee.department_name}
                </Space>
              </Descriptions.Item>
            )}
          </Descriptions>
        </Card>

        {/* Organizations */}
        {employee.organizations.length > 0 && (
          <Card title="Организации">
            <Space wrap>
              {employee.organizations.map((org) => (
                <Card key={org.code} size="small" style={{ width: 250 }}>
                  <Text strong>{org.name}</Text>
                  {org.short_name && (
                    <br />
                  )}
                  {org.short_name && (
                    <Text type="secondary">{org.short_name}</Text>
                  )}
                  <br />
                  <Text type="secondary">Код: {org.code}</Text>
                </Card>
              ))}
            </Space>
          </Card>
        )}

        {/* Metadata */}
        <Card title="Информация" size="small">
          <Descriptions bordered column={1} size="small">
            {employee.created_at && (
              <Descriptions.Item label="Создан">
                <Space>
                  <CalendarOutlined style={{ color: '#8c8c8c' }} />
                  {employee.created_at}
                </Space>
              </Descriptions.Item>
            )}
            {employee.updated_at && (
              <Descriptions.Item label="Обновлён">
                <Space>
                  <CalendarOutlined style={{ color: '#8c8c8c' }} />
                  {employee.updated_at}
                </Space>
              </Descriptions.Item>
            )}
          </Descriptions>
        </Card>
      </Space>
    </AdminLayout>
  );
}
