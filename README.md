# Halu 3C ERP 系統

## 專案簡介
簡要介紹專案的功能和目標。

## 系統需求
- PHP ^8.2
- Composer 2.8.4
- Node.js 18.20

## 安裝步驟

1. 安裝相依套件
    ```bash
    composer install
    npm install
    ```

2. 設定環境變數
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

3. 設定資料庫連線
    ```bash
    DB_CONNECTION=sqlite
    # DB_HOST=127.0.0.1
    # DB_PORT=3306
    # DB_DATABASE=laravel
    # DB_USERNAME=root
    # DB_PASSWORD=
    ```

4. 初始化資料庫並建立使用者
    ```bash
    php artisan migrate
    php artisan make:user
    ```

5. 啟動 PHP 開發伺服器
    ```bash
    php artisan serve
    ```

6. 啟動前端開發伺服器
    ```bash
    npm run dev
    ```

## 部屬步驟

1. 安裝相依套件
    ```bash
    composer install --optimize-autoloader --no-dev
    npm install
    ```

2. 生成應用程式金鑰
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

3. 設定資料庫連線
    ```bash
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=laravel
    DB_USERNAME=root
    DB_PASSWORD=
    ```

4. 初始化資料庫並建立使用者
    ```bash
    php artisan migrate
    php artisan make:user
    ```

5. 編譯前端資源
    ```bash
    npm run build
    ```

6. 設定伺服器
    - 確保伺服器指向 `public` 目錄
    - 設定適當的權限

## 貢獻

歡迎提交問題和請求，或是發送 Pull Request。

## 授權

此專案採用 MIT 授權。
