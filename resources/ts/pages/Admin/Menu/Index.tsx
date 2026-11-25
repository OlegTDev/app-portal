import { useCallback, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import {
  Tree,
  Button,
  Card,
  Form,
  Input,
  Select,
  Switch,
  Space,
  Typography,
  message,
  Tag,
  Modal,
  InputNumber,
} from 'antd';
import {
  PlusOutlined,
  EditOutlined,
  DeleteOutlined,
} from '@ant-design/icons';

const { Title, Text } = Typography;

interface MenuItem {
    id: string;
    key: string;
    label: string;
    icon?: string | null;
    link?: string | null;
    target?: string | null;
    position: 'main' | 'left';
    sort_order: number;
    is_enabled: boolean;
    parent_id: string | null;
    children?: MenuItem[];
}

interface MenuIndexProps {
    mainMenu: MenuItem[];
    leftMenu: MenuItem[];
}

export default function MenuIndex({ mainMenu, leftMenu }: MenuIndexProps) {
  const [editingItem, setEditingItem] = useState<MenuItem | null>(null);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [activePosition, setActivePosition] = useState<'main' | 'left'>('main');

  const { data, setData, post, put, delete: destroy, processing, errors } = useForm({
    name: '',
    link: '',
    icon: '',
    target: '_self',
    position: 'main',
    parent_id: null as string | null,
    sort_order: 0,
    is_enabled: true,
  });

  const flattenMenuItems = useCallback((items: MenuItem[], parentId: string | null = null): MenuItem[] => {
    let flat: MenuItem[] = [];
    for (const item of items) {
      flat = [...flat, { ...item, parent_id: parentId }];
      if (item.children && item.children.length > 0) {
        flat = [...flat, ...flattenMenuItems(item.children, item.id)];
      }
    }
    return flat;
  }, []);

  const handleEdit = (item: MenuItem) => {
    setEditingItem(item);
    setData({
      name: item.label,
      link: item.link || '',
      icon: item.icon || '',
      target: item.target || '_self',
      position: item.position,
      parent_id: item.parent_id,
      sort_order: item.sort_order,
      is_enabled: item.is_enabled,
    });
    setIsModalOpen(true);
  };

  const handleCreate = (position: 'main' | 'left') => {
    setEditingItem(null);
    setData({
      name: '',
      link: '',
      icon: '',
      target: '_self',
      position,
      parent_id: null,
      sort_order: 0,
      is_enabled: true,
    });
    setIsModalOpen(true);
  };

  const handleSubmit = () => {
    if (!data.name.trim()) return;

    if (editingItem) {
      put(`/admin/menu/${editingItem.id}`, {
        onSuccess: () => {
          message.success('Пункт меню обновлён');
          setIsModalOpen(false);
          setEditingItem(null);
        },
      });
    } else {
      post('/admin/menu', {
        onSuccess: () => {
          message.success('Пункт меню создан');
          setIsModalOpen(false);
        },
      });
    }
  };

  const handleDelete = (id: string) => {
    Modal.confirm({
      title: 'Удалить пункт меню?',
      content: 'Это действие нельзя отменить.',
      okButtonProps: { danger: true },
      onOk: () => {
        destroy(`/admin/menu/${id}`, {
          onSuccess: () => message.success('Пункт меню удалён'),
        });
      },
    });
  };

  const treeData = mainMenu.length > 0 ? mainMenu : leftMenu;

  const treeProps = {
    defaultExpandedKeys: treeData.map((item) => item.key),
    treeData: treeData.map((item) => ({
      key: item.key,
      title: (
        <Space>
          <span>{item.icon && <span style={{ marginRight: 4 }}>{item.icon}</span>}</span>
          <span>{item.label}</span>
          <Tag color={item.is_enabled ? 'green' : 'default'}>
            {item.is_enabled ? 'Активен' : 'Неактивен'}
          </Tag>
          <Button
            type="text"
            size="small"
            icon={<EditOutlined />}
            onClick={(e) => {
              e.stopPropagation();
              handleEdit(item);
            }}
          />
          <Button
            type="text"
            size="small"
            danger
            icon={<DeleteOutlined />}
            onClick={(e) => {
              e.stopPropagation();
              handleDelete(item.id);
            }}
          />
        </Space>
      ),
      children: item.children,
    })),
  };

  const allItems = [...flattenMenuItems(mainMenu), ...flattenMenuItems(leftMenu)];

  const positionOptions = [
    { label: 'Главное меню', value: 'main' },
    { label: 'Левое меню', value: 'left' },
  ];

  const parentOptions = [
    { label: '(без родителя)', value: null },
    ...allItems.map((item) => ({
      label: item.label,
      value: item.id,
      disabled: editingItem?.id === item.id,
    })),
  ];

  return (
    <>
      <Head title="Управление меню" />

      <Space direction="vertical" size="large" style={{ width: '100%' }}>
        <Title level={3}>Управление меню</Title>

        <Space>
          <Button
            type={activePosition === 'main' ? 'primary' : 'default'}
            onClick={() => setActivePosition('main')}
          >
                        Главное меню
          </Button>
          <Button
            type={activePosition === 'left' ? 'primary' : 'default'}
            onClick={() => setActivePosition('left')}
          >
                        Левое меню
          </Button>
          <Button
            type="primary"
            icon={<PlusOutlined />}
            onClick={() => handleCreate(activePosition)}
          >
                        Добавить пункт
          </Button>
        </Space>

        <Card title={activePosition === 'main' ? 'Главное меню' : 'Левое меню'}>
          {treeData.length > 0 ? (
            <Tree {...treeProps} />
          ) : (
            <Text type="secondary">Пункты меню не найдены</Text>
          )}
        </Card>
      </Space>

      <Modal
        title={editingItem ? 'Редактировать пункт меню' : 'Добавить пункт меню'}
        open={isModalOpen}
        onOk={handleSubmit}
        onCancel={() => setIsModalOpen(false)}
        confirmLoading={processing}
        destroyOnClose
      >
        <Form layout="vertical" style={{ marginTop: 20 }}>
          <Form.Item
            label="Название"
            validateStatus={errors.name ? 'error' : ''}
            help={errors.name}
          >
            <Input
              value={data.name}
              onChange={(e) => setData('name', e.target.value)}
              placeholder="Введите название"
            />
          </Form.Item>

          <Form.Item label="Ссылка">
            <Input
              value={data.link}
              onChange={(e) => setData('link', e.target.value)}
              placeholder="/news или https://example.com"
            />
          </Form.Item>

          <Form.Item label="Иконка (Ant Design)">
            <Input
              value={data.icon}
              onChange={(e) => setData('icon', e.target.value)}
              placeholder="HomeOutlined"
            />
          </Form.Item>

          <Form.Item label="Цель открытия">
            <Select
              value={data.target}
              onChange={(value) => setData('target', value)}
              options={[
                { label: 'Текущая вкладка', value: '_self' },
                { label: 'Новая вкладка', value: '_blank' },
              ]}
            />
          </Form.Item>

          <Form.Item label="Позиция">
            <Select
              value={data.position}
              onChange={(value) => setData('position', value)}
              options={positionOptions}
            />
          </Form.Item>

          <Form.Item label="Родительский пункт">
            <Select
              value={data.parent_id || undefined}
              onChange={(value) => setData('parent_id', value)}
              options={parentOptions}
              allowClear
              placeholder="(корневой пункт)"
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

          <Form.Item label="Активен">
            <Switch
              checked={data.is_enabled}
              onChange={(checked) => setData('is_enabled', checked)}
            />
          </Form.Item>
        </Form>
      </Modal>
    </>
  );
}
