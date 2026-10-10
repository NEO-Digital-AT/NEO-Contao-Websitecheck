#!/bin/bash
# First start: install Contao Managed Edition + this bundle, migrate, admin, seed.
set -euo pipefail
export COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_NO_INTERACTION=1 COMPOSER_MEMORY_LIMIT=-1

cd /var/www/html

if [ ! -f composer.json ]; then
    echo ">>> Installing Contao ${CONTAO_VERSION} …"
    composer create-project "contao/managed-edition:${CONTAO_VERSION}" . --no-install --no-scripts
    composer config repositories.neo path /bundle
    composer config allow-plugins.contao-components/installer true
    composer config allow-plugins.contao/manager-plugin true
    composer config allow-plugins.php-http/discovery true
    composer require "neo-digital/contao-websitecheck-bundle:*@dev" --no-update
    composer update --prefer-dist --no-progress

    cat > .env.local <<ENV
APP_ENV=prod
APP_SECRET=$(php -r 'echo bin2hex(random_bytes(32));')
DATABASE_URL=mysql://contao:contao@db:3306/contao?serverVersion=mariadb-10.11.0&charset=utf8mb4
MAILER_DSN=smtp://mailpit:1025
TRUSTED_PROXIES=127.0.0.1
ENV

    mkdir -p config
    cat > config/config.yaml <<YAML
neo_websitecheck:
    pagespeed:
        api_key: '%env(NEO_WEBSITECHECK_PSI_KEY)%'
    ai:
        base_url: '%env(NEO_WEBSITECHECK_AI_BASE_URL)%'
        api_key: '%env(NEO_WEBSITECHECK_AI_KEY)%'
        model: '%env(NEO_WEBSITECHECK_AI_MODEL)%'
    mail:
        from_email: '%env(ADMIN_EMAIL)%'
        from_name: 'Test Agency'
        notify_email: '%env(NEO_WEBSITECHECK_NOTIFY)%'
    brand:
        name: 'Test Agency'
        owner_name: 'Admin'
        website_url: 'http://localhost:8080'
    pdf:
        engine: '%env(PDF_ENGINE)%'
        gotenberg_url: 'http://gotenberg:3000'
YAML
fi

echo ">>> Waiting for the database …"
until php -r 'new PDO("mysql:host=db;dbname=contao", "contao", "contao");' 2>/dev/null; do sleep 2; done

echo ">>> Migrating …"
php vendor/bin/contao-console contao:migrate --no-interaction --no-backup

if ! php vendor/bin/contao-console contao:user:list --format=json 2>/dev/null | grep -q '"admin"'; then
    echo ">>> Creating admin user …"
    php vendor/bin/contao-console contao:user:create --username=admin --name="Admin" --email="${ADMIN_EMAIL}" --language="${SITE_LANGUAGE}" --password="${ADMIN_PASSWORD}" --admin
fi

echo ">>> Seeding start page …"
php /usr/local/share/neo-seed.php "mysql:host=db;dbname=contao;charset=utf8mb4" contao contao "${SITE_LANGUAGE}" "${ADMIN_EMAIL}"

if [ "$(php /usr/local/share/neo-seed.php --count "mysql:host=db;dbname=contao;charset=utf8mb4" contao contao)" = "0" ]; then
    echo ">>> Creating a demo check (PDF + e-mails to Mailpit) …"
    php vendor/bin/contao-console neo:websitecheck:demo --locale="${SITE_LANGUAGE}" --send || true
fi

php vendor/bin/contao-console cache:clear --no-warmup >/dev/null || true
chown -R www-data:www-data /var/www/html

exec "$@"
