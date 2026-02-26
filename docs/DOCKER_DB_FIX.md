# Docker で DB 接続エラー（Access denied）が出たとき

`Access denied for user 'mobile_order_user'@'...'` が出る場合、MySQL コンテナ内でユーザーを作り直します。

## 方法1: コンテナ内でユーザーを再作成

```bash
# DB コンテナに入る
docker exec -it mobile_order_db mysql -uroot -proot

# MySQL で実行（パスワードは .env の DB_PASSWORD に合わせる）
CREATE USER IF NOT EXISTS 'mobile_order_user'@'%' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON mobile_order.* TO 'mobile_order_user'@'%';
FLUSH PRIVILEGES;
EXIT;
```

## 方法2: ローカルだけ root で接続する

`.env` で root を使う（Docker Compose の MYSQL_ROOT_PASSWORD=root に合わせる）:

```env
DB_USERNAME=root
DB_PASSWORD=root
```

本番では必ず別ユーザーを使い、root は使わないでください。

## 方法3: DB ボリュームごと作り直す

ユーザーが壊れている場合は、ボリュームを消してコンテナを起動し直すと初期状態になります（**データは消えます**）:

```bash
docker compose down -v
docker compose up -d
# その後 php artisan migrate
```
