# Connections — zipni.ru (WordPress)

## Сайт
- **URL (тест):** https://wp.zipni.ru
- **URL (прод, после миграции):** https://zipni.ru
- **Git repo:** https://github.com/weselow/zipni-wp
- **Движок:** WordPress 6.9.1 + OpenLiteSpeed + LiteSpeed Cache + Redis
- **Coolify UUID:** kg4808wk8g4s08scwgkcosgw

## OpenLiteSpeed
- **OLS Version:** 1.8.5
- **PHP Version:** lsphp85
- **Admin Panel:** порт 7080 (только внутри контейнера, не проброшен)
- **Document root:** `/var/www/vhosts/localhost/html/`
- **Vhost config:** `/usr/local/lsws/conf/vhosts/localhost/vhconf.conf`
- **Listener:** порт 80 → шаблон docker → vhost localhost

## Контейнеры (Coolify)
- **Litespeed:** `litespeed-kg4808wk8g4s08scwgkcosgw-120216895755`
- **MySQL:** `mysql-kg4808wk8g4s08scwgkcosgw-120216886452`
- **Redis:** `redis-kg4808wk8g4s08scwgkcosgw-120216913865`

Имена контейнеров могут меняться при рестарте. Для поиска:
```bash
ssh root@coolify.jabc.loc "docker ps --format '{{.Names}}' | grep kg48"
```

## MariaDB
- **Host (внутри Docker):** `mysql-kg4808wk8g4s08scwgkcosgw-120216886452`
- **Database:** wordpress
- **User:** wordpress
- **Password:** ZipniWP2026Db
- **Root Password:** ZipniWP2026Root

Примечание: в `wp-config.php` dbhost = полное имя MySQL-контейнера (не просто `mysql`).

## WordPress Admin
- **User:** admin
- **Password:** ZipniWP2026Admin
- **Email:** 598746@gmail.com
- **Админка:** https://wp.zipni.ru/wp-admin/
- **Язык:** ru_RU
- **Пермалинки:** `/%postname%/`
- **Часовой пояс:** Europe/Moscow

## WP-CLI

WP-CLI доступен внутри контейнера litespeed по пути `/usr/bin/wp`.

### Подключение:
```bash
# 1. SSH на Coolify-сервер
ssh root@coolify.jabc.loc

# 2. Найти имя litespeed-контейнера
CONTAINER=$(docker ps --format '{{.Names}}' | grep litespeed | grep kg48)

# 3. Выполнить WP-CLI команду
docker exec $CONTAINER bash -c 'cd /var/www/vhosts/localhost/html && wp <command> --allow-root'
```

### Примеры:
```bash
# Статус WordPress
docker exec $CONTAINER bash -c 'cd /var/www/vhosts/localhost/html && wp core version --allow-root'

# Список плагинов
docker exec $CONTAINER bash -c 'cd /var/www/vhosts/localhost/html && wp plugin list --allow-root'

# Установить плагин
docker exec $CONTAINER bash -c 'cd /var/www/vhosts/localhost/html && wp plugin install yoast-seo --activate --allow-root'

# Проверка БД
docker exec $CONTAINER bash -c 'cd /var/www/vhosts/localhost/html && wp db check --allow-root'

# Обновить WordPress
docker exec $CONTAINER bash -c 'cd /var/www/vhosts/localhost/html && wp core update --allow-root'
```

### Важно:
- Всегда добавлять `--allow-root` (контейнер работает от root)
- Всегда `cd /var/www/vhosts/localhost/html` перед командой (там wp-config.php)
- Или использовать `--path=/var/www/vhosts/localhost/html`

## Coolify
- **SSH:** root@coolify.jabc.loc
- **API Token:** 4|xZXn2k2FOq9SXTu2a4X85H08RQKgCmUo6He4xzIl2756419c
- **API URL:** http://coolify.jabc.loc:8000/api/v1

## Volumes (persistence)
- `./sites` → `/var/www/vhosts/` — WordPress файлы, темы, плагины, uploads
- `./lsws/conf` → `/usr/local/lsws/conf` — конфиги OLS и vhosts
- `./data/db` → `/var/lib/mysql` — MariaDB данные
- `./redis/data` → `/data` — Redis данные
- `./logs` → `/usr/local/lsws/logs/` — логи OLS

## Связанные сервисы (из ai-photo-factory)
- **FastAPI App UUID:** t4sw8ko0gkgo00g0c04o4s00
- **MinIO S3 API:** https://s3.zipni.ru
- **Telegram Bot:** @zipni_bot
