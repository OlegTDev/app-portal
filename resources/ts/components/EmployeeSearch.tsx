import { useState, useCallback, useEffect } from 'react';
import { AutoComplete, Avatar, Typography, Space, Drawer, Button } from 'antd';
import {
  UserOutlined,
  PhoneOutlined,
  MailOutlined,
  EnvironmentOutlined,
  ApartmentOutlined,
  CloseOutlined,
} from '@ant-design/icons';
import useDebounce from '@/hooks/useDebounce';

const { Text, Paragraph } = Typography;

export interface Employee {
  id: string;
  full_name: string;
  position: string | null;
  phone: string | null;
  phone_additional: string | null;
  office: string | null;
  email: string | null;
  department_name: string | null;
  organization_code: string | null;
  photo_path: string | null;
}

interface EmployeeSearchProps {
  onSelect?: (employee: Employee) => void;
  placeholder?: string;
  minLength?: number;
  resultLimit?: number;
}

interface SearchResponse {
  id: string;
  full_name: string;
  position: string | null;
  phone: string | null;
  phone_additional: string | null;
  office: string | null;
  email: string | null;
  department_name: string | null;
  organization_code: string | null;
  photo_path: string | null;
}

export default function EmployeeSearch({
  onSelect,
  placeholder = 'Поиск сотрудника...',
  minLength = 2,
  resultLimit = 20,
}: EmployeeSearchProps) {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<Employee[]>([]);
  const [loading, setLoading] = useState(false);
  const [drawerOpen, setDrawerOpen] = useState(false);
  const [selectedEmployee, setSelectedEmployee] = useState<Employee | null>(null);

  const debouncedQuery = useDebounce(query, 300);

  const searchEmployees = useCallback(
    async (searchQuery: string) => {
      if (searchQuery.length < minLength) {
        setResults([]);
        return;
      }

      setLoading(true);
      try {
        const response = await fetch(
          `/api/employees/search?q=${encodeURIComponent(searchQuery)}&_=${Date.now()}`,
          {
            headers: {
              Accept: 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
            },
          }
        );

        if (!response.ok) {
          setResults([]);
          return;
        }

        const data = (await response.json()) as SearchResponse[];
        setResults(
          data.slice(0, resultLimit).map((emp) => ({
            id: emp.id,
            full_name: emp.full_name,
            position: emp.position,
            phone: emp.phone,
            phone_additional: emp.phone_additional,
            office: emp.office,
            email: emp.email,
            department_name: emp.department_name,
            organization_code: emp.organization_code,
            photo_path: emp.photo_path,
          }))
        );
      } catch {
        setResults([]);
      } finally {
        setLoading(false);
      }
    },
    [minLength, resultLimit]
  );

  useEffect(() => {
    void searchEmployees(debouncedQuery);
  }, [debouncedQuery, searchEmployees]);

  const handleSelect = useCallback(
    (employee: Employee) => {
      setSelectedEmployee(employee);
      setDrawerOpen(true);
      onSelect?.(employee);
    },
    [onSelect]
  );

  const autoCompleteOptions = results.map((emp) => ({
    key: emp.id,
    value: emp.id,
    label: (
      <Space
        style={{ width: '100%', cursor: 'pointer' }}
        onClick={() => handleSelect(emp)}
      >
        <Avatar size="small" icon={<UserOutlined />} src={emp.photo_path || undefined} />
        <div style={{ flex: 1, overflow: 'hidden' }}>
          <Text strong>{emp.full_name}</Text>
          {emp.position && (
            <Paragraph style={{ margin: 0, fontSize: 12 }} type="secondary" ellipsis={{ rows: 1 }}>
              {emp.position}
            </Paragraph>
          )}
        </div>
      </Space>
    ),
  }));

  return (
    <>
      <AutoComplete
        value={query}
        options={autoCompleteOptions}
        style={{ width: '100%' }}
        onSearch={(val) => setQuery(val)}
        onChange={(val) => setQuery(val)}
        placeholder={placeholder}
        notFoundContent={loading ? <Text type="secondary">Поиск...</Text> : undefined}
        size="large"
        allowClear
        filterOption={false}
        popupMatchSelectWidth={false}
        popupStyle={{ minWidth: 320, maxWidth: 480 }}
      />

      <Drawer
        title="Сотрудник"
        placement="right"
        width={420}
        open={drawerOpen}
        onClose={() => setDrawerOpen(false)}
        extra={
          <Button
            type="text"
            icon={<CloseOutlined />}
            onClick={() => setDrawerOpen(false)}
          />
        }
      >
        {selectedEmployee && (
          <Space direction="vertical" size="large" style={{ width: '100%' }}>
            <Space style={{ width: '100%' }}>
              <Avatar size={80} icon={<UserOutlined />} src={selectedEmployee.photo_path || undefined} />
              <div>
                <Text strong style={{ fontSize: 18 }}>{selectedEmployee.full_name}</Text>
                {selectedEmployee.position && (
                  <Paragraph style={{ margin: '4px 0 0' }} type="secondary">
                    {selectedEmployee.position}
                  </Paragraph>
                )}
                {selectedEmployee.department_name && (
                  <Space style={{ marginTop: 4 }} size={4}>
                    <ApartmentOutlined style={{ color: '#8c8c8c' }} />
                    <Text type="secondary" style={{ fontSize: 13 }}>{selectedEmployee.department_name}</Text>
                  </Space>
                )}
              </div>
            </Space>

            <Space direction="vertical" size="middle" style={{ width: '100%' }}>
              {selectedEmployee.email && (
                <Space>
                  <MailOutlined style={{ color: '#8c8c8c' }} />
                  <Text>{selectedEmployee.email}</Text>
                </Space>
              )}
              {selectedEmployee.phone && (
                <Space>
                  <PhoneOutlined style={{ color: '#8c8c8c' }} />
                  <Text>{selectedEmployee.phone}</Text>
                </Space>
              )}
              {selectedEmployee.phone_additional && (
                <Space>
                  <PhoneOutlined style={{ color: '#8c8c8c' }} />
                  <Text type="secondary">Доп.: {selectedEmployee.phone_additional}</Text>
                </Space>
              )}
              {selectedEmployee.office && (
                <Space>
                  <EnvironmentOutlined style={{ color: '#8c8c8c' }} />
                  <Text>Кабинет: {selectedEmployee.office}</Text>
                </Space>
              )}
              {selectedEmployee.organization_code && (
                <Space>
                  <ApartmentOutlined style={{ color: '#8c8c8c' }} />
                  <Text type="secondary">Код организации: {selectedEmployee.organization_code}</Text>
                </Space>
              )}
            </Space>
          </Space>
        )}
      </Drawer>
    </>
  );
}
