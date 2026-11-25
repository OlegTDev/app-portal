import 'antd/dist/reset.css';
import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

// Route helper for Inertia
window.route = (name: string, params?: any): string => {
  // Laravel Inertia provides route() via @inertiajs/react
  // This is a fallback — in production use Inertia's router
  const url = new URL(window.location.href);
  url.pathname = name === 'home' ? '/' : `/${name}`;
  if (params) {
    Object.keys(params).forEach(key => {
      url.pathname = url.pathname.replace(`:${key}`, params[key]);
    });
  }
  return url.toString();
};

createInertiaApp({
  title: (title) => `${title} — App Portal`,
  resolve: (name) => {
    const pages = import.meta.glob('./pages/**/*.tsx', { eager: true });
    let page = pages[`./pages/${name}.tsx`];
    if (page === undefined) {
      page = pages[`./pages/Error/${name}.tsx`];
    }
    return page;
  },
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />);
  },
});
