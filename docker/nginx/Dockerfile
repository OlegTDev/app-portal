FROM nginx:alpine

# Копируем nginx-конфиг
COPY nginx.conf /etc/nginx/nginx.conf

# Создаем рабочую директорию
RUN mkdir -p /var/www/public && \
    chown -R nginx:nginx /var/www && \
    rm /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
