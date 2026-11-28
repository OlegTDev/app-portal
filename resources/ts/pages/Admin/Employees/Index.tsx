import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import {
  Table,
  Button,
  Card,
  Space,
  Typography,
  Tag,
  Input,
  Select,
  Avatar,
} from 'antd';
import {
  SearchOutlined,
  UserOutlined,
  PhoneOutlined,
  EnvironmentOutlined,
} from '@ant-design/icons';
import AdminLayout from '@/layouts/AdminLayout';

const { Title, Text } = Typography;

interface Employee {
  id: number;
  username: string | null;
  full_name: string | null;
  position: string | null;
  phone: string | null;
  phone_additional: string | null;
  office: string | null;
  department_name: string | null;
  organization_code: string | null;
  photo_path: string | null;
  is_active: boolean;
  organizations: Array<{ code: string; name: string }>;
  created_at: string | null;
}

interface Organization {
  code: string;
  name: string;
}

interface EmployeesIndexProps {
  employees: {
    data: Employee[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
  };
  organizations: Organization[];
  filters: {
    organization_code?: string;
    is_active?: boolean;
    search?: string;
  };
}

export default function EmployeesIndex({
  employees,
  organizations,
  filters = {},
}: EmployeesIndexProps) {
  const [searchText, setSearchText] = useState(filters.search || '');
  const [orgFilter, setOrgFilter] = useState(filters.organization_code);
  const [activeFilter, setActiveFilter] = useState(filters.is_active);

  const handleSearch = () => {
    const params = new URLSearchParams(window.location.search);
    if (searchText) params.set('search', searchText);
    else params.delete('search');
    if (orgFilter) params.set('organization_code', orgFilter);
    else params.delete('organization_code');
    if (activeFilter !== undefined && activeFilter !== null) {
      params.set('is_active', String(activeFilter));
    } else {
      params.delete('is_active');
    }
    params.set('page', '1');
    window.location.search = params.toString();
  };

  const columns = [
    {
      title: 'Сотрудник',
      key: 'employee',
      render: (_: unknown, record: Employee) => (
        <Space>
          <Avatar
            size="small"
            icon={<UserOutlined />}
            src={record.photo_path || undefined}
          />
          <div>
            <Link href={`/admin/employees/${record.id}`}>
              <Text strong>{record.full_name || '—'}</Text>
            </Link>
            {record.username && (
              <Text type="secondary" style={{ fontSize: 12, marginLeft: 8 }}>
                @{record.username}
              </Text>
            )}
          </div>
        </Space>
      ),
    },
    {
      title: 'Должность',
      dataIndex: 'position',
      key: 'position',
      render: (pos: string | null) => pos || '—',
    },
    {
      title: 'Отдел',
      dataIndex: 'department_name',
      key: 'department_name',
      render: (dept: string | null) => dept || '—',
    },
    {
      title: 'Организация',
      dataIndex: 'organizations',
      key: 'organizations',
      render: (orgs: Array<{ code: string; name: string }>) =>
        orgs.map((org) => (
          <Tag key={org.code}>{org.name}</Tag>
        )),
    },
    {
      title: 'Контакты',
      key: 'contacts',
      render: (_: unknown, record: Employee) => (
        <Space direction="vertical" size={2}>
          {record.phone && (
            <Space>
              <PhoneOutlined style={{ color: '#8c8c8c', fontSize: 12 }} />
              <Text type="secondary" style={{ fontSize: 12 }}>{record.phone}</Text>
            </Space>
          )}
          {record.office && (
            <Space>
              <EnvironmentOutlined style={{ color: '#8c8c8c', fontSize: 12 }} />
              <Text type="secondary" style={{ fontSize: 12 }}>Каб. {record.office}</Text>
            </Space>
          )}
        </Space>
      ),
    },
    {
      title: 'Статус',
      dataIndex: 'is_active',
      key: 'is_active',
      width: 100,
      render: (isActive: boolean) => (
        <Tag color={isActive ? 'green' : 'default'}>
          {isActive ? 'Активен' : 'Уволен'}
        </Tag>
      ),
    },
  ];

  return (
    <AdminLayout>
      <Head title="Сотрудники" />

      <Space orientation="vertical" size="large" style={{ width: '100%' }}>
        <Title level={3}>Сотрудники</Title>

        <Card>
          <Space style={{ marginBottom: 16, width: '100%', justifyContent: 'space-between' }}>
            <Space>
              <Input
                placeholder="Поиск по ФИО, должности, телефону..."
                value={searchText}
                onChange={(e) => setSearchText(e.target.value)}
                onPressEnter={handleSearch}
                style={{ width: 300 }}
                prefix={<SearchOutlined />}
              />
              <Select
                placeholder="Организация"
                value={orgFilter}
                onChange={(value) => setOrgFilter(value)}
                allowClear
                style={{ width: 200 }}
                options={organizations.map((org) => ({
                  label: org.name,
                  value: org.code,
                }))}
              />
              <Select
                placeholder="Статус"
                value={activeFilter}
                onChange={(value) => setActiveFilter(value)}
                allowClear
                style={{ width: 120 }}
                options={[
                  { label: 'Активен', value: true },
                  { label: 'Уволен', value: false },
                ]}
              />
              <Button type="primary" onClick={handleSearch}>
                Найти
              </Button>
            </Space>
          </Space>

          <Table
            columns={columns}
            dataSource={employees.data}
            rowKey="id"
            pagination={{
              current: employees.current_page,
              total: employees.total,
              pageSize: employees.per_page,
              showSizeChanger: true,
              showTotal: (total) => `Всего: ${total}`,
              onChange: (page) => {
                const params = new URLSearchParams(window.location.search);
                params.set('page', page.toString());
                window.location.search = params.toString();
              },
            }}
            locale={{ emptyText: 'Сотрудники не найдены' }}
          />
        </Card>
      </Space>
    </AdminLayout>
  );
}
