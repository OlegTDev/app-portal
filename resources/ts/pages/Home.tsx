import { Head, router } from '@inertiajs/react';
import { Card, Space, Typography } from 'antd';
import { FileTextOutlined, TeamOutlined, PhoneOutlined, TrophyOutlined } from '@ant-design/icons';
import PortalLayout from '@/layouts/MainLayout';

const { Title, Paragraph } = Typography;

interface HomeProps {
    recentNews: {
        id: string;
        title: string;
        summary: string;
        published_at: string;
    }[];
}

export default function Home({ recentNews }: HomeProps) {
  return (
    <PortalLayout>
      <Head title="Главная" />

      <Title level={2}>Добро пожаловать в корпоративный портал!</Title>
      <Paragraph type="secondary">
                Единое пространство для сотрудников организации
      </Paragraph>

      <Space direction="vertical" size="large" style={{ width: '100%' }}>
        <Space wrap size="middle">
          <Card
            hoverable
            onClick={() => router.visit('/news')}
            style={{ width: 220 }}
            cover={
              <div style={{ height: 120, background: '#e6f0ff', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                <FileTextOutlined style={{ fontSize: 48, color: '#1677ff' }} />
              </div>
            }
          >
            <Card.Meta title="Новости" description="Последние события" />
          </Card>

          <Card
            hoverable
            onClick={() => router.visit('/departments')}
            style={{ width: 220 }}
            cover={
              <div style={{ height: 120, background: '#f6ffed', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                <TeamOutlined style={{ fontSize: 48, color: '#52c41a' }} />
              </div>
            }
          >
            <Card.Meta title="Отделы" description="Оргструктура" />
          </Card>

          <Card
            hoverable
            onClick={() => router.visit('/phonebook')}
            style={{ width: 220 }}
            cover={
              <div style={{ height: 120, background: '#fff7e6', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                <PhoneOutlined style={{ fontSize: 48, color: '#faad14' }} />
              </div>
            }
          >
            <Card.Meta title="Телефонный справочник" description="Контакты сотрудников" />
          </Card>

          <Card
            hoverable
            onClick={() => router.visit('/tests')}
            style={{ width: 220 }}
            cover={
              <div style={{ height: 120, background: '#fff1f0', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                <TrophyOutlined style={{ fontSize: 48, color: '#ff4d4f' }} />
              </div>
            }
          >
            <Card.Meta title="Тестирование" description="Проверка знаний" />
          </Card>
        </Space>

        {recentNews.length > 0 && (
          <>
            <Title level={4}>Последние новости</Title>
            {recentNews.map((news) => (
              <Card
                key={news.id}
                hoverable
                onClick={() => router.visit(`/news/${news.id}`)}
              >
                <Title level={5}>{news.title}</Title>
                <Paragraph type="secondary" ellipsis={{ rows: 2 }}>
                  {news.summary}
                </Paragraph>
                <Paragraph type="secondary" style={{ fontSize: 12 }}>
                  {new Date(news.published_at).toLocaleDateString('ru-RU')}
                </Paragraph>
              </Card>
            ))}
          </>
        )}
      </Space>
    </PortalLayout>
  );
}
