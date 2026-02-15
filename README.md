# ERP Demo System

一個使用 **Vue 3 + PHP + PostgreSQL (Supabase)** 的免費 ERP 系統。

## 🏗️ 技術架構

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Vue 3     │────▶│  PHP API    │────▶│  Supabase   │
│  (Vercel)   │     │  (Railway)  │     │ (PostgreSQL)│
└─────────────┘     └─────────────┘     └─────────────┘
      $0                  $0                  $0
```

## 📁 項目結構

```
erp-demo/
├── backend/          # PHP API
│   ├── api.php      # 主 API 文件
│   └── Dockerfile
├── frontend/         # Vue 3 前端
│   ├── src/
│   │   ├── views/   # 頁面組件
│   │   ├── router/  # 路由配置
│   │   └── composables/ # API 封裝
│   ├── package.json
│   └── Dockerfile
├── database/         # 數據庫結構
│   └── schema.sql   # PostgreSQL 表結構
└── docker-compose.yml
```

## 🚀 快速開始

### 1. 設置 Supabase 數據庫

1. 到 https://supabase.com 註冊並創建專案
2. 打開 **SQL Editor**
3. 貼上 `database/schema.sql` 的內容並執行
4. 獲取 Database Connection String

### 2. 配置 PHP 後端

編輯 `backend/api.php`：
```php
define('DB_PASS', '你的密碼'); // 修改這行
```

### 3. 本地開發

使用 Docker Compose：
```bash
cd erp-demo

# 啟動所有服務
docker-compose up -d

# 或只啟動前端和後端（使用雲端 Supabase）
docker-compose up -d backend frontend
```

訪問：
- 前端：http://localhost:3000
- 後端 API：http://localhost:8080/api/health

### 4. 部署到生產環境

#### 前端（Vercel）
```bash
cd frontend
npm install -g vercel
vercel
```

#### 後端（Railway）
```bash
# 安裝 Railway CLI
npm install -g @railway/cli

# 登入並部署
cd backend
railway login
railway init
railway up
```

## 📊 功能模塊

- ✅ **Dashboard** - 統計概覽
- ✅ **Products** - 產品管理
- ✅ **Customers** - 客戶管理
- ✅ **Orders** - 訂單管理

## 🔌 API 端點

| 方法 | 端點 | 說明 |
|------|------|------|
| GET | `/api/health` | 健康檢查 |
| GET | `/api/products` | 獲取產品列表 |
| POST | `/api/products` | 創建產品 |
| GET | `/api/customers` | 獲取客戶列表 |
| POST | `/api/customers` | 創建客戶 |
| GET | `/api/orders` | 獲取訂單列表 |
| POST | `/api/orders` | 創建訂單 |

## 💰 成本

全部使用免費方案：
- **Supabase**: 500MB 數據庫 + 2GB 傳輸
- **Railway**: $5/月額度（通常夠用）
- **Vercel**: 無限帶寬 + 100GB

**總計：$0/月**

## 📝 注意事項

1. **請立即修改密碼！** 不要在代碼中硬編碼密碼
2. 生產環境請使用環境變數
3. 定期備份 Supabase 數據

## 🔧 環境變數

生產環境建議使用：

```bash
# .env 文件
DB_HOST=your-project.supabase.co
DB_PASS=your-password
SUPABASE_URL=https://your-project.supabase.co
SUPABASE_KEY=your-anon-key
```

## 📚 相關文檔

- [Vue 3](https://vuejs.org/)
- [Vuetify 3](https://vuetifyjs.com/)
- [Supabase](https://supabase.com/docs)
- [PHP PDO PostgreSQL](https://www.php.net/manual/en/ref.pdo-pgsql.php)

---

祝你開發順利！🎉
