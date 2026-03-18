FROM webdevops/php-nginx:8.2

# ── system deps: Node.js 20 ──────────────────────────────────────────────────
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# ── Composer: Laravel installer (as application user) ────────────────────────
USER application
RUN composer global require laravel/installer
USER root

# ── provision script – runs on every container start ─────────────────────────
COPY docker/entrypoint.d/10-laravel-setup.sh /opt/docker/provision/entrypoint.d/10-laravel-setup.sh
RUN chmod +x /opt/docker/provision/entrypoint.d/10-laravel-setup.sh
