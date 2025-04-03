# 遺失物招領公佈欄系統

這是一個使用 PHP 和 MySQL 建立的遺失物管理系統。此系統幫助學校單位有效管理和查詢學生的遺失物品。亦可將此系統開放給家長瀏覽，查詢學生的遺失物品。


## 系統需求

- PHP 7.4 或更高版本
- MySQL 5.7 或更高版本
- Web 伺服器（如 Apache 或 Nginx）

## 安裝步驟

1. 下載專案壓縮檔。
2. 將檔案解壓縮到你的網頁伺服器根目錄下。
3. 在資料庫中建立一個新的資料庫。
4. 編輯 `config/db_config.php` 檔案，填入資料庫連接資訊。
- define('DB_HOST', 'server_host');//資料庫伺服器
- define('DB_USER', 'user_name');// 資料庫使用者名稱
- define('DB_PASS', 'password');// 資料庫密碼
- define('DB_NAME', 'database_name');// 資料庫名稱
5. 執行 `install/install.php` 以完成安裝。
6. 當看到Database created successfully、Items table created successfully、Tags table created successfully、Item_tags table created successfully訊息時，表示安裝完成。
7. 設定檔案權限（uploads目錄必須可寫入）。
8. 完成安裝後，刪除 `install` 目錄。
9. 前往根目錄 `index.php` 開始使用系統。


## 使用說明及特色
- 使用管理後台來新增或歸還遺失物。
- 直覺、簡易的操作介面
-  RWD 響應式網頁設計，用手機拍照、操作更順手


## 系統功能
### 瀏覽頁面 (index.php)
- 左側：自動輪播本週拾獲的遺失物照片
- 右側：顯示本週遺失物的標籤雲統計，標籤雲可查看所有該分類的遺失物
- 頁面底部：查詢本週、上週、上上週的遺失物以及管理後台
### 管理後台
1. 新增遺失物
   - 上傳遺失物照片
   - 設定物品名稱和拾獲日期
   - 選擇遺失物標籤

2. 物品歸還
   - 瀏覽所有未歸還的遺失物
   - 標記物品為已歸還狀態，該項物品就不會出現在遺失物清單

3. 標籤管理
   - 可新增物品標籤
   - 可刪除未使用的標籤


## 注意事項
- 系統會自動判斷每週一開始的新遺失物
- 已歸還的物品不會顯示在瀏覽頁面
- 標籤雲會即時更新統計數據
- 正在使用中的標籤無法刪除


