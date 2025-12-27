.PHONY: build up upd down stop re migrate seed bash install test fix swaggger swaggger-open

build:
	docker-compose build

# コンテナを起動（フォアグラウンド）
up:
	docker-compose up

# コンテナをバックグラウンドで起動
upd:
	docker-compose up -d

# コンテナを停止して削除
down:
	docker-compose down

# コンテナを停止
stop:
	docker-compose stop

# コンテナを再起動
re: down upd

# マイグレーションを実行
migrate:
	docker-compose exec app php artisan migrate

# シーダーを実行
seed:
	docker-compose exec app php artisan db:seed

# アプリケーションコンテナに入る
bash:
	docker-compose exec app bash

# 初回セットアップ（Composer、NPMインストール、キー生成、マイグレーション）
install:
	docker-compose exec app composer install
	docker-compose exec app cp .env.example .env
	docker-compose exec app php artisan key:generate
	docker-compose exec app php artisan migrate
	docker-compose exec app npm install
	docker-compose exec app npm run build

# テストを実行
test:
	docker-compose exec app php artisan test

# コードフォーマットを実行
fix:
	docker-compose exec app ./vendor/bin/pint

# Swaggerドキュメントを生成
swaggger:
	docker-compose exec app php artisan l5-swagger:generate

# Swaggerドキュメントを生成してブラウザで開く
swaggger-open: swaggger
	open http://localhost:8000/api/documentation
