# coachtech 勤怠管理アプリ

## アプリケーション名

coachtech 勤怠管理アプリ

## 概要

一般ユーザーが出勤、休憩、退勤を打刻し、自分の勤怠一覧や勤怠詳細を確認できるアプリケーションです。

管理者ユーザーは全ユーザーの勤怠確認、勤怠修正、スタッフ別勤怠確認、修正申請の承認を行えます。

## 環境構築

### 1. リポジトリをクローン

```bash
git clone git@github.com:yasuyasuikeikb-collab/coachtech-attendance.git
cd coachtech-attendance
```

### 2. Docker Compose用の環境変数を作成

```bash
cp .env.example .env
```

必要に応じて、現在のユーザーIDとグループIDを確認します。

```bash
id -u
id -g
```

`.env` の値を自分の環境に合わせます。

```env
UID=1000
GID=1000
```

### 3. Laravel用の環境変数を作成

```bash
cp src/.env.example src/.env
```

`src/.env` のDB設定は以下の通りです。

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass
```

### 4. Dockerコンテナを起動

```bash
docker compose up -d --build
```

### 5. PHPコンテナに入る

```bash
docker compose exec php bash
```

### 6. Composerパッケージをインストール

```bash
composer install
```

### 7. アプリケーションキーを作成

```bash
php artisan key:generate
```

### 8. マイグレーションとシーディングを実行

```bash
php artisan migrate:fresh --seed
```

## 使用技術

- PHP 8.1
- Laravel
- MySQL 8.0.26
- Nginx 1.21.1
- Docker / Docker Compose
- phpMyAdmin

## URL

| 画面 | URL |
| --- | --- |
| アプリ | http://localhost |
| phpMyAdmin | http://localhost:8080 |
| 一般ログイン | http://localhost/login |
| 管理者ログイン | http://localhost/admin/login |
| 勤怠登録 | http://localhost/attendance |

## ログイン情報

Seeder作成後に、以下のユーザーでログインできるようにします。

| 種別 | メールアドレス | パスワード |
| --- | --- | --- |
| 一般ユーザー1 | user1@example.com | password |
| 一般ユーザー2 | user2@example.com | password |
| 管理者ユーザー | user3@example.com | password |

## ER図

後で追加します。

## テーブル設計

後で追加します。

## テスト実行

```bash
docker compose exec php php artisan test
```

## 注意事項

- `.env` と `src/.env` はGit管理しません。
- `src/vendor` と `src/node_modules` はGit管理しません。
- `docker/mysql/data` はGit管理しません。
- 管理者ユーザーは `users.admin_status` が `true` のユーザーとして管理します。