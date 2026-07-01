# 🚀 COVIDCare - Railway Setup Guide

## ⚠️ Current Issue
Your app is running but **database is not configured** yet.

## ✅ Fix: Add MySQL Database to Railway

### Option 1: Use Railway's MySQL Database Plugin (Recommended)
1. Go to [Railway Dashboard](https://railway.app)
2. Click your **COVIDCare** project
3. Click the **"+"** button → **"Database"** → **"MySQL"**
4. Railway will automatically create a database and set environment variables
5. **Click "Redeploy"** to restart with database connected

### Option 2: Connect Your Own MySQL Database
1. Go to Railway Dashboard
2. Click your **COVIDCare** project
3. Go to **Variables** tab
4. Add these variables:
   ```
   MYSQLHOST=your-database-host
   MYSQLUSER=your-database-user
   MYSQLPASSWORD=your-database-password
   MYSQLDATABASE=covidcare
   MYSQLPORT=3306
   ```
5. Click **"Redeploy"**

## 🔍 Check Setup Status

Visit: **`https://your-railway-url.com/health.php`**

You should see something like:
```json
{
  "status": "OK",
  "environment": {
    "MYSQLHOST": "✓ Set",
    "MYSQLUSER": "✓ Set",
    "MYSQLPASSWORD": "✓ Set",
    "MYSQLDATABASE": "✓ Set"
  },
  "database": "Connected ✓",
  "database_status": "connected"
}
```

## 📊 Initialize Database Schema

After connecting the database, you need to import your schema:

### Using Railway's Database UI:
1. Click **MySQL** database in your Railway project
2. Go to the **"Data"** tab
3. Click **"Import"** and upload `chk_db.sql`

### Or use command line:
```bash
mysql -h MYSQLHOST -u MYSQLUSER -p MYSQLDATABASE < chk_db.sql
```

## 🔗 Access Your App

- **Home**: `https://your-railway-url.com/`
- **Login**: `https://your-railway-url.com/login_register.php`
- **Health Check**: `https://your-railway-url.com/health.php`

## 🚨 If You Still See Errors

1. **Check `/health.php`** - It shows exactly what's missing
2. **View Railway Logs:**
   - Click your deployment
   - Go to **Logs** tab
   - Look for database connection errors
3. **Redeploy:**
   - Go to **Deployments** tab
   - Click "Redeploy" on the latest deployment

## 📝 Files Used in Deployment

- `Procfile` - Start command
- `Dockerfile` - Docker image configuration
- `start.sh` - Startup script
- `db_conn.php` - Database connection handler
- `health.php` - Status check endpoint
- `railway.json` - Railway-specific config

## 💾 Backup Your Data

Before deploying to production, make backups of your database!

---

**Need help?** Check `/health.php` endpoint for detailed diagnostics!
