# Self-contained environment for GaMerZ File Explorer — nginx + PHP-FPM. Mirrors a typical
# production stack (nice-URL rewrites, static denies, and the CSP the inline PDF/media embeds
# rely on). The app is procedural PHP with no runtime dependencies, so this image only adds a
# web server and the exif extension the image viewer uses.
#
#   Build:                     docker build -t gfe .
#   Run (bind-mount the app):  docker run --rm -p 8080:80 -v "$PWD":/var/www/html gfe
#   Browse:                    http://localhost:8080
#
# The bind-mount serves your working tree live (edit + refresh, no rebuild). Point config.php's
# GFE_ROOT_DIR/GFE_DIR at /var/www/html and GFE_ROOT_URL/GFE_URL at http://localhost:8080.
FROM php:8.5-fpm-alpine

RUN apk add --no-cache nginx \
    && docker-php-ext-install exif \
    && mkdir -p /run/nginx

RUN cat > /etc/nginx/http.d/default.conf <<'NGINX'
server {
    listen 80 default_server;
    server_name localhost;
    root /var/www/html;
    index index.php;

    # CDN assets (Bootstrap/Font Awesome/highlight.js), object-src for the inline PDF <object>,
    # and media-src for <video>/<audio>.
    add_header Content-Security-Policy "default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; font-src 'self' https://cdnjs.cloudflare.com; object-src 'self'; media-src 'self'; frame-ancestors 'self'" always;

    # Deny tooling / metadata files (defense in depth — the listing hides them, but nginx must
    # not serve them). Denied by exact name so browsable content files (.json/.md/...) are
    # unaffected; the CLI-only tests directory is blocked wholesale.
    location ~ /\.(?!well-known\/) { deny all; }
    location ~* ^/(?:composer\.(?:json|lock)|phpstan\.neon\.dist|phpcs\.xml\.dist|phpunit\.xml\.dist|AGENTS\.md|CLAUDE\.md|Dockerfile)$ { deny all; }
    location ^~ /tests/ { deny all; }

    location / {
        try_files $uri $uri/ /index.php;
    }
    rewrite ^/browse/(.+[^/])/?$ /index.php?dir=$1 last;
    rewrite ^/viewing/(.+[^/])/?$ /view.php?file=$1 last;
    rewrite ^/download/(.+[^/])/?$ /view.php?file=$1&dl=1 last;

    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
NGINX

EXPOSE 80

# Start PHP-FPM in the background, then nginx in the foreground as PID 1.
CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]
