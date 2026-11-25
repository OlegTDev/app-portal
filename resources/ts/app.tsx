import 'antd/dist/reset.css';
import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

// Route helper for Inertia
type RouteParams = Record<string, string | number>;

window.route = (name: string, params?: RouteParams): string => {
  // Laravel Inertia provides route() via @inertiajs/react
  // This is a fallback — in production use Inertia's router
  const url = new URL(window.location.href);
  url.pathname = name === 'home' ? '/' : `/${name}`;
  if (params) {
    Object.keys(params).forEach((key) => {
      const value = params[key];
      if (typeof value === 'string' || typeof value === 'number') {
        url.pathname = url.pathname.replace(`:${key}`, String(value));
      }
    });
  }
  return url.toString();
};

void createInertiaApp({
  title: (title) => `${title} — App Portal`,
  resolve: (name) => {
    type PageModule = { default: React.ComponentType };
    const pages = import.meta.glob('./pages/**/*.tsx', { eager: true });
    const page = (pages[`./pages/${name}.tsx`] ?? pages[`./pages/Error/${name}.tsx`]) as PageModule | undefined;
    if (page === undefined) {
      throw new Error(`Page not found: ${name}`);
    }
    return page;
  },
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />);
  },
});
