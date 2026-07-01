# Railway Deployment Guide

## Steps to Deploy on Railway

### 1. Prerequisites
- GitHub account with your code pushed
- Railway account (sign up at railway.app)

### 2. Connect to Railway
1. Go to [railway.app](https://railway.app)
2. Sign in with GitHub
3. Click "New Project"
4. Select "Deploy from GitHub repo"
5. Select your repository

### 3. Configure Environment Variables
In Railway dashboard, set these variables:
```
MYSQLHOST=your-mysql-host
MYSQLUSER=your-mysql-username
MYSQLPASSWORD=your-mysql-password
MYSQLDATABASE=covidcare
MYSQLPORT=3306
PORT=8080
```

### 4. Add MySQL Database (Optional)
You can add a MySQL database plugin from Railway:
1. Click "New" → "Database" → "MySQL"
2. Railway will auto-populate the connection variables

### 5. Deploy
- Push to GitHub (the repo you connected)
- Railway will automatically deploy

### 6. View Your App
- Railway will provide you with a public URL
- Your app will be live!

## Debugging

### Check Application Health
Visit: `https://your-railway-url.com/health.php`

This will show:
- PHP version
- Environment variables
- Database connection status

### View Deploy Logs
1. Go to Railway dashboard
2. Click your deployment
3. Check the "Logs" tab to see errors

## Project Structure
- `Procfile` - Specifies how to start the app
- `Dockerfile` - Docker configuration for building the image
- `railway.json` - Railway-specific configuration
- `.railwayignore` - Files to exclude from deployment
- `.env.example` - Example environment variables
- `start.sh` - Startup script with better error handling
- `health.php` - Health check endpoint for debugging
- `db_conn.php` - Database connection with fallback values

## Database Migration
Upload your database schema using the `chk_db.sql` file:
```bash
mysql -h MYSQLHOST -u MYSQLUSER -p MYSQLDATABASE < chk_db.sql
```

Or use Railway's database management interface.

## Troubleshooting

### "Application failed to respond"
1. Check `/health.php` endpoint
2. View Railway logs
3. Ensure `PORT` environment variable is set (should be auto-detected)
4. Verify database credentials if using MySQL

### Database Connection Errors
- The app now logs errors instead of crashing
- Check health.php for database status
- Verify MYSQLHOST, MYSQLUSER, MYSQLPASSWORD are correct

