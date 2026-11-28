import { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import {
  Table,
  Button,
  Card,
  Form,
  Input,
  Space,
  Typography,
  Modal,
  InputNumber,
  Select,
  Popconfirm,
  message,
} from 'antd';
import {
  PlusOutlined,
  EditOutlined,
  DeleteOutlined,
  SearchOutlined,
} from '@ant-design/icons';
import AdminLayout from '@/layouts/AdminLayout';

const { Title } = Typography;

interface Organization {
  code: string;
  name: string;
  short_name: string | null;
  parent_code: string | null;
  sort_order: number;
  description: string | null;
  children: Organization[];
}

interface OrganizationData {
  organizations: {
    data: Organization[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
  };
  tree: Organization[];
  parents: Array<{ label: string; value: string }>;
  filters: {
    organization_code?: string;
    is_active?: boolean;
    search?: string;
  };
}

export default function OrganizationsIndex({
  organizations,
  parents,
  filters = {},
}: OrganizationData) {
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingOrg, setEditingOrg] = useState<Organization | null>(null);
  const [searchText, setSearchText] = useState(filters.search || '');

  const { data, setData, post, put, delete: destroy, processing, errors } = useForm({
    code: '',
    name: '',
    short_name: '',
    parent_code: null as string | null,
    sort_order: 0,
    description: '',
    upload_images_path: '',
    ended_at: '',
  });

  const handleEdit = (org: Organization) => {
    setEditingOrg(org);
    setData({
      code: org.code,
      name: org.name,
      short_name: org.short_name || '',
      parent_code: org.parent_code,
      sort_order: org.sort_order,
      description: org.description || '',
      upload_images_path: '',
      ended_at: '',
    });
    setIsModalOpen(true);
  };

  const handleCreate = () => {
    setEditingOrg(null);
    setData({
      code: '',
      name: '',
      short_name: '',
      parent_code: null,
      sort_order: 0,
      description: '',
      upload_images_path: '',
      ended_at: '',
    });
    setIsModalOpen(true);
  };

  const handleSubmit = () => {
    if (!data.code.trim() || !data.name.trim()) return;

    if (editingOrg) {
      put(`/admin/organizations/${editingOrg.code}`, {
        onSuccess: () => {
          message.success('Организация обновлена');
          setIsModalOpen(false);
          setEditingOrg(null);
        },
      });
    } else {
      post('/admin/organizations', {
        onSuccess: () => {
          message.success('Организация создана');
          setIsModalOpen(false);
        },
      });
    }
  };

  const handleDelete = (code: string) => {
    destroy(`/admin/organizations/${code}`, {
      onSuccess: () => {
        message.success('Организация удалена');
      },
    });
  };

  const handleSearch = () => {
    const params = new URLSearchParams(window.location.search);
    if (searchText) params.set('search', searchText);
    else params.delete('search');
    window.location.search = params.toString();
  };

  const columns = [
    {
      title: 'Код',
      dataIndex: 'code',
      key: 'code',
      width: 100,
    },
    {
      title: 'Название',
      dataIndex: 'name',
      key: 'name',
    },
    {
      title: 'Краткое название',
      dataIndex: 'short_name',
      key: 'short_name',
    },
    {
      title: 'Порядок',
      dataIndex: 'sort_order',
      key: 'sort_order',
      width: 80,
    },
    {
      title: 'Действия',
      key: 'actions',
      width: 100,
      render: (_: unknown, record: Organization) => (
        <Space>
          <Button
            type="text"
            icon={<EditOutlined />}
            onClick={() => handleEdit(record)}
          />
          <Popconfirm
            title="Удалить организацию?"
            description="Это действие нельзя отменить."
            okButtonProps={{ danger: true }}
            onConfirm={() => handleDelete(record.code)}
          >
            <Button type="text" danger icon={<DeleteOutlined />} />
          </Popconfirm>
        </Space>
      ),
    },
  ];

  return (
    <AdminLayout>
      <Head title="Управление организациями" />

      <Space orientation="vertical" size="large" style={{ width: '100%' }}>
        <Title level={3}>Управление организациями</Title>

        <Card>
          <Space style={{ marginBottom: 16, width: '100%', justifyContent: 'space-between' }}>
            <Space>
              <Input
                placeholder="Поиск по названию или коду..."
                value={searchText}
                onChange={(e) => setSearchText(e.target.value)}
                onPressEnter={handleSearch}
                style={{ width: 300 }}
                prefix={<SearchOutlined />}
              />
              <Button type="primary" onClick={handleSearch}>
                Найти
              </Button>
            </Space>
            <Button
              type="primary"
              icon={<PlusOutlined />}
              onClick={handleCreate}
            >
              Добавить организацию
            </Button>
          </Space>

          <Table
            columns={columns}
            dataSource={organizations.data}
            rowKey="code"
            pagination={{
              current: organizations.current_page,
              total: organizations.total,
              pageSize: organizations.per_page,
              showSizeChanger: true,
              showTotal: (total) => `Всего: ${total}`,
              onChange: (page) => {
                const params = new URLSearchParams(window.location.search);
                params.set('page', page.toString());
                window.location.search = params.toString();
              },
            }}
          />
        </Card>
      </Space>

      <Modal
        title={editingOrg ? 'Редактировать организацию' : 'Добавить организацию'}
        open={isModalOpen}
        onOk={handleSubmit}
        onCancel={() => setIsModalOpen(false)}
        confirmLoading={processing}
        destroyOnHidden
      >
        <Form layout="vertical" style={{ marginTop: 20 }}>
          <Form.Item
            label="Код организации"
            validateStatus={errors.code ? 'error' : ''}
            help={errors.code}
          >
            <Input
              value={data.code}
              onChange={(e) => setData('code', e.target.value)}
              placeholder="Например: 8600"
              disabled={!!editingOrg}
            />
          </Form.Item>

          <Form.Item
            label="Название"
            validateStatus={errors.name ? 'error' : ''}
            help={errors.name}
          >
            <Input
              value={data.name}
              onChange={(e) => setData('name', e.target.value)}
              placeholder="Полное название организации"
            />
          </Form.Item>

          <Form.Item label="Краткое название">
            <Input
              value={data.short_name}
              onChange={(e) => setData('short_name', e.target.value)}
              placeholder="Краткое название"
            />
          </Form.Item>

          <Form.Item label="Родительская организация">
            <Select
              value={data.parent_code || undefined}
              onChange={(value) => setData('parent_code', value)}
              options={parents}
              allowClear
              placeholder="(корневая организация)"
            />
          </Form.Item>

          <Form.Item label="Порядок сортировки">
            <InputNumber
              value={data.sort_order}
              onChange={(value) => setData('sort_order', value || 0)}
              min={0}
              style={{ width: '100%' }}
            />
          </Form.Item>

          <Form.Item label="Описание">
            <Input.TextArea
              value={data.description}
              onChange={(e) => setData('description', e.target.value)}
              rows={3}
              placeholder="Историческая справка"
            />
          </Form.Item>
        </Form>
      </Modal>
    </AdminLayout>
  );
}
