# Connections — zipni.ru (WordPress)

## Сайт
- **URL (тест):** https://wp.zipni.ru
- **URL (прод, после миграции):** https://zipni.ru
- **Git repo:** https://github.com/weselow/zipni-wp
- **Движок:** WordPress + OpenLiteSpeed + LiteSpeed Cache + Redis
- **Coolify UUID:** kg4808wk8g4s08scwgkcosgw

## OpenLiteSpeed
- **OLS Version:** 1.8.5
- **PHP Version:** lsphp85
- **Admin Panel:** порт 7080 (внутри контейнера)

## MariaDB
- **Host (внутри Docker):** mysql
- **Database:** wordpress
- **User:** wordpress
- **Password:** ZipniWP2026Db
- **Root Password:** ZipniWP2026Root

## WordPress Admin (будет задан при установке)
- **User:** admin
- **Password:** (задать при первой установке)
- **Email:** 598746@gmail.com

## WP-CLI
- Доступен внутри контейнера litespeed
- `docker exec -it <container> wp --allow-root ...`
- Через Coolify: `ssh root@coolify.jabc.loc`, затем `docker exec`

## Coolify
- **SSH:** root@coolify.jabc.loc
- **API Token:** 4|xZXn2k2FOq9SXTu2a4X85H08RQKgCmUo6He4xzIl2756419c
- **API URL:** http://coolify.jabc.loc:8000/api/v1

## Связанные сервисы (из ai-photo-factory)
- **FastAPI App UUID:** t4sw8ko0gkgo00g0c04o4s00
- **MinIO S3 API:** https://s3.zipni.ru
- **Telegram Bot:** @zipni_bot
