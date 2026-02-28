# Заметки по установке WordPress + OLS в Coolify

## Грабли и решения

### 1. Port 80 already allocated

**Проблема:** `docker-compose.yml` из upstream содержит `ports: 80:80, 443:443, 7080:7080`. В Coolify порт 80 уже занят reverse proxy (Traefik/Caddy). Деплой падает: `Bind for 0.0.0.0:80 failed: port is already allocated`.

**Решение:** Убрать `ports`, заменить на `expose: ["80"]`. Coolify сам проксирует трафик к контейнеру через внутреннюю Docker-сеть. Порты 443 и 7080 тоже не нужны — SSL терминирует Coolify, OLS Admin Panel доступна через `docker exec`.

### 2. Volume bin/container маппится в пустоту

**Проблема:** `./bin/container:/usr/local/bin` — Coolify клонирует репо в артефакт-директорию, но при запуске docker compose относительные пути (`./bin/container`) указывают на директорию в `/data/coolify/applications/{uuid}/`, где git checkout мог не сохранить эти файлы корректно. В результате `/usr/local/bin` в контейнере пуст, и стандартные скрипты установки (`appinstallctl.sh`, `domainctl.sh`, etc.) недоступны.

**Решение:** Не полагаться на скрипты из `bin/container/`. Устанавливать WordPress вручную через WP-CLI (`wp` уже встроен в образ `litespeedtech/openlitespeed`).

### 3. WordPress установлен не в тот document root

**Проблема:** OLS имеет два набора listener-ов:
- `Default` (порт **8088**) → vhost `Example` (document root `/usr/local/lsws/Example/html/`)
- `HTTP` (порт **80**) → шаблон `docker` → vhost `localhost` (document root `/var/www/vhosts/localhost/html/`)

Coolify проксирует на порт 80, поэтому срабатывает шаблон `docker` → vhost `localhost`. Если установить WordPress в `Example/html/` — он не будет виден через порт 80.

**Решение:** Устанавливать WordPress в `/var/www/vhosts/localhost/html/` — это document root для порта 80.

### 4. Отсутствует конфиг vhost для localhost

**Проблема:** Шаблон `docker` ссылается на `$SERVER_ROOT/conf/vhosts/$VH_NAME/vhconf.conf`, но директория `/usr/local/lsws/conf/vhosts/localhost/` не существует. OLS отдаёт 404.

**Решение:** Создать конфиг вручную:
```bash
docker exec <container> bash -c '
mkdir -p /usr/local/lsws/conf/vhosts/localhost
cat > /usr/local/lsws/conf/vhosts/localhost/vhconf.conf << "EOF"
docRoot /var/www/vhosts/localhost/html/
enableGzip 1

index {
  useServer 0
  indexFiles index.php, index.html
}

context / {
  allowBrowse 1
  location /var/www/vhosts/localhost/html/
  rewrite {
    RewriteFile .htaccess
  }
}

rewrite {
  enable 1
  autoLoadHtaccess 1
}
EOF
'
```

Затем перезапустить OLS: `/usr/local/lsws/bin/lswsctrl restart`

### 5. OLS default index.html перекрывает WordPress index.php

**Проблема:** При копировании файлов из `Example/html/` в `localhost/html/` скопировался и `index.html` (дефолтная страница OLS "Congratulations"). В конфиге OLS: `indexFiles index.html, index.php` — `index.html` имеет приоритет, поэтому отображается страница OLS вместо WordPress.

**Решение:** Удалить `index.html` и другие дефолтные файлы OLS из document root:
```bash
docker exec <container> rm /var/www/vhosts/localhost/html/index.html
```

### 6. container_name конфликтует с Coolify

**Проблема:** `container_name: litespeed` в docker-compose.yml конфликтует с именованием Coolify (он добавляет UUID-суффикс).

**Решение:** Убрать `container_name` из docker-compose.yml. Coolify сам назначает имена.

## Полная процедура установки WordPress в Coolify

```bash
# 1. Найти имя контейнера
CONTAINER=$(docker ps --format '{{.Names}}' | grep litespeed | grep kg48)

# 2. Создать директорию сайта
docker exec $CONTAINER mkdir -p /var/www/vhosts/localhost/html

# 3. Скачать WordPress
docker exec $CONTAINER bash -c 'cd /var/www/vhosts/localhost/html && wp core download --allow-root --locale=ru_RU'

# 4. Создать wp-config.php (dbhost = имя mysql-контейнера из docker-compose)
MYSQL_CONTAINER=$(docker ps --format '{{.Names}}' | grep mysql | grep kg48)
docker exec $CONTAINER bash -c "cd /var/www/vhosts/localhost/html && wp config create --allow-root --dbname=wordpress --dbuser=wordpress --dbpass=ZipniWP2026Db --dbhost=$MYSQL_CONTAINER --locale=ru_RU"

# 5. Установить WordPress
docker exec $CONTAINER bash -c 'cd /var/www/vhosts/localhost/html && wp core install --allow-root --url=https://wp.zipni.ru --title=Zipni --admin_user=admin --admin_password=ZipniWP2026Admin --admin_email=598746@gmail.com'

# 6. Создать конфиг vhost
docker exec $CONTAINER bash -c 'mkdir -p /usr/local/lsws/conf/vhosts/localhost && cat > /usr/local/lsws/conf/vhosts/localhost/vhconf.conf << "EOF"
docRoot /var/www/vhosts/localhost/html/
enableGzip 1

index {
  useServer 0
  indexFiles index.php, index.html
}

context / {
  allowBrowse 1
  location /var/www/vhosts/localhost/html/
  rewrite {
    RewriteFile .htaccess
  }
}

rewrite {
  enable 1
  autoLoadHtaccess 1
}
EOF'

# 7. Удалить дефолтные файлы OLS
docker exec $CONTAINER bash -c 'cd /var/www/vhosts/localhost/html && rm -f index.html error404.html phpinfo.php upload.html upload.php && rm -rf css img blocked protected'

# 8. Права
docker exec $CONTAINER chown -R nobody:nogroup /var/www/vhosts/localhost/html/

# 9. Перезапустить OLS
docker exec $CONTAINER /usr/local/lsws/bin/lswsctrl restart

# 10. Настройки WordPress
docker exec $CONTAINER bash -c 'cd /var/www/vhosts/localhost/html && wp rewrite structure "/%postname%/" --allow-root && wp option update timezone_string Europe/Moscow --allow-root'

# 11. .htaccess (WordPress не записывает автоматически, нужно вручную)
echo "IyBCRUdJTiBXb3JkUHJlc3MKUmV3cml0ZUVuZ2luZSBPbgpSZXdyaXRlUnVsZSAuKiAtIFtFPUhUVFBfQVVUSE9SSVpBVElPTjole0hUVFA6QXV0aG9yaXphdGlvbn1dClJld3JpdGVCYXNlIC8KUmV3cml0ZVJ1bGUgXmluZGV4XC5waHAkIC0gW0xdClJld3JpdGVDb25kICV7UkVRVUVTVF9GSUxFTkFNRX0gIS1mClJld3JpdGVDb25kICV7UkVRVUVTVF9GSUxFTkFNRX0gIS1kClJld3JpdGVSdWxlIC4gL2luZGV4LnBocCBbTF0KIyBFTkQgV29yZFByZXNzCg==" | docker exec -i $CONTAINER bash -c "base64 -d > /var/www/vhosts/localhost/html/.htaccess"

# 12. Перезапустить OLS для подхвата rewrite
docker exec $CONTAINER /usr/local/lsws/bin/lswsctrl restart
```

### 7. DB_HOST — использовать имя сервиса, не контейнера

**Проблема:** При создании `wp-config.php` через WP-CLI использовали `--dbhost=mysql-kg4808wk8g4s08scwgkcosgw-120216886452` (полное имя контейнера). После редеплоя контейнер получает новое имя с другим суффиксом → WordPress не может подключиться к БД.

**Решение:** Использовать имя сервиса из `docker-compose.yml` — просто `mysql`. Docker Compose DNS резолвит его в IP текущего контейнера.

```bash
# Правильно:
wp config create --dbhost=mysql ...

# Неправильно:
wp config create --dbhost=mysql-kg4808wk8g4s08scwgkcosgw-120216886452 ...
```

Если уже сломалось — исправить в wp-config.php:
```bash
docker exec $CONTAINER sed -i "s/define( 'DB_HOST', '.*' );/define( 'DB_HOST', 'mysql' );/" /var/www/vhosts/localhost/html/wp-config.php
```

### 8. .htaccess пустой — Pretty Permalinks не работают

**Проблема:** После `wp rewrite structure "/%postname%/"` WordPress НЕ записывает правила в `.htaccess` (файл остаётся пустым с комментарием `# .htaccess`). Запросы на pretty URL'ы (`/hello-world/`) возвращают 404 от LiteSpeed.

**Причина:** WordPress не может записать файл (права) или `wp rewrite flush` обновляет только БД, не файл.

**Решение:** Записать .htaccess вручную. ВАЖНО: `<IfModule mod_rewrite.c>` обёртку можно оставить, но OLS корректно обрабатывает и без неё:
```
# BEGIN WordPress
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
# END WordPress
```

Также добавлены inline rewrite rules в `vhconf.conf` как fallback. После правки — `lswsctrl restart`.

### 9. Bash escaping при записи .htaccess через SSH

**Проблема:** `!-f` и `!-d` в RewriteCond через цепочку `ssh → docker exec → bash` экранируются в `\!-f`, что ломает правила.

**Решение:** Использовать base64: закодировать файл локально, передать через pipe `base64 -d > .htaccess`.

## Persistence

- `/var/www/vhosts/` маппится в volume `./sites` → данные переживают рестарт контейнера
- `/usr/local/lsws/conf/vhosts/localhost/vhconf.conf` — **НЕ в volume**, будет утерян при пересоздании контейнера. Решение: добавить volume mount `./lsws/conf/vhosts:/usr/local/lsws/conf/vhosts` в docker-compose.yml, или сохранить конфиг в `./lsws/conf/` (уже маппится)
- MariaDB данные в volume `./data/db`
- Redis данные в volume `./redis/data`
