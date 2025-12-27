FROM php:8.2-fpm

# システムの依存関係をインストール
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    nodejs \
    npm

# PHP拡張機能をインストール
RUN docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath gd

# Composerをインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 作業ディレクトリを設定
WORKDIR /var/www

# 既存のアプリケーションファイルをコピー
COPY . /var/www

# 権限を設定
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage \
    && chmod -R 775 /var/www/bootstrap/cache

# ユーザーをwww-dataに切り替え
USER www-data

# ポート9000を公開
EXPOSE 9000

CMD ["php-fpm"]
