#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/app"
MARKER="$APP_DIR/.laravel-installed"

# ── helper: run command as `application` user ────────────────────────────────
run_as_app() {
    gosu application bash -c "$*"
}

# ══════════════════════════════════════════════════════════════════════════════
# 0. Ensure /app is writable by `application` (mounted volume fix)
# ══════════════════════════════════════════════════════════════════════════════
echo "▸ Fixing /app ownership …"
chown application:application "$APP_DIR"

# ══════════════════════════════════════════════════════════════════════════════
# 1. Create Laravel project (only once – when artisan is missing)
# ══════════════════════════════════════════════════════════════════════════════
if [ ! -f "$APP_DIR/artisan" ]; then
    echo "▸ Creating new Laravel project …"
    # Composer needs a writable HOME for cache; use application's home
    run_as_app "cd $APP_DIR && composer create-project laravel/laravel:^12.0 . --prefer-dist --no-interaction"
fi

# ══════════════════════════════════════════════════════════════════════════════
# 2. Install PHP dependencies (in case vendor/ was not mounted)
# ══════════════════════════════════════════════════════════════════════════════
if [ ! -d "$APP_DIR/vendor" ]; then
    echo "▸ Running composer install …"
    run_as_app "cd $APP_DIR && composer install --no-interaction --optimize-autoloader"
fi

# ══════════════════════════════════════════════════════════════════════════════
# 3. .env – copy from example if missing & generate key
# ══════════════════════════════════════════════════════════════════════════════
if [ ! -f "$APP_DIR/.env" ]; then
    echo "▸ Creating .env from .env.example …"
    run_as_app "cp $APP_DIR/.env.example $APP_DIR/.env"
    run_as_app "php $APP_DIR/artisan key:generate --no-interaction"
fi

# ══════════════════════════════════════════════════════════════════════════════
# 4. Storage & cache permissions (owned by application, group www-data)
# ══════════════════════════════════════════════════════════════════════════════
echo "▸ Setting storage & cache permissions …"
chown -R application:www-data "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

# ══════════════════════════════════════════════════════════════════════════════
# 5. Install Bootstrap 5 + Popper.js & build assets (only once)
# ══════════════════════════════════════════════════════════════════════════════
if [ ! -f "$MARKER" ]; then
    echo "▸ Installing npm dependencies (Bootstrap 5 + Popper) …"
    run_as_app "cd $APP_DIR && npm install"
    run_as_app "cd $APP_DIR && npm install bootstrap@^5 @popperjs/core"

    # ── Write resources/js/app.js with Bootstrap import ──────────────────────
    echo "▸ Configuring resources/js/app.js …"
    run_as_app "cat > $APP_DIR/resources/js/app.js << 'JSEOF'
// Bootstrap 5
import 'bootstrap';
JSEOF"

    # ── Write resources/css/app.css with Bootstrap import ────────────────────
    echo "▸ Configuring resources/css/app.css …"
    run_as_app "cat > $APP_DIR/resources/css/app.css << 'CSSEOF'
@import 'bootstrap/dist/css/bootstrap.min.css';
CSSEOF"

    # ── Ensure vite.config.js includes CSS ───────────────────────────────────
    echo "▸ Configuring vite.config.js …"
    run_as_app "cat > $APP_DIR/vite.config.js << 'VITEEOF'
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
});
VITEEOF"

    echo "▸ Building front-end assets …"
    run_as_app "cd $APP_DIR && npm run build"

    run_as_app "touch $MARKER"
fi

# ══════════════════════════════════════════════════════════════════════════════
# 6. Clear Laravel caches (safe on every boot)
# ══════════════════════════════════════════════════════════════════════════════
echo "▸ Clearing Laravel caches …"
run_as_app "php $APP_DIR/artisan cache:clear"
run_as_app "php $APP_DIR/artisan view:clear"
run_as_app "php $APP_DIR/artisan config:clear"

echo "✔ Laravel setup complete."
