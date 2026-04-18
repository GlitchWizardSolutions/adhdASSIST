# ADHD Dashboard - Setup Instructions

## 🎯 Overview
A comprehensive ADHD-optimized task management dashboard with a focus on mental health, accessible design, and distraction-free task capture.

## 📋 Technology Stack
- **Backend**: PHP 8.x with PDO
- **Database**: MySQL 5.7+
- **Frontend**: Bootstrap 5.3.8, vanilla JavaScript, CSS Grid
- **Authentication**: Session-based with bcrypt password hashing

## 🚀 Quick Start

### 1. **Create Database**
Open your MySQL client and run these commands:

```sql
CREATE DATABASE IF NOT EXISTS adhd_dashboard;
USE adhd_dashboard;
```

Then import the schema:
```sql
-- Copy and paste contents of sql/schema.sql here
```

Or from command line:
```bash
mysql -u root -p adhd_dashboard < sql/schema.sql
```

### 2. **Configure Environment**
Copy `../private/.env.example` to `../private/.env` and update credentials if needed:

```bash
cp ../private/.env.example ../private/.env
```

Edit `../private/.env` with your database credentials (default is localhost, root, no password):
```
DB_HOST=localhost
DB_NAME=adhd_dashboard
DB_USER=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
```

**Important**: Keep `.env` in the `private/` directory (outside web root) for security. This file contains sensitive database credentials and should NEVER be in the public_html directory.

### 3. **Start Local Server**
Choose one:

**Option A: PHP Built-in Server** (Simplest)
```bash
cd public_html
php -S localhost:8000
```

**Option B: XAMPP/WAMP/LAMP** (Already configured for Apache)
- No extra command needed, already running
- URLs will include `/public_html/` path prefix

### 4. **Access Application (Environment-Aware URLs)**

**If using PHP built-in server (`php -S localhost:8000`):**
- **Home**: `http://localhost:8000/`
- **Register**: `http://localhost:8000/views/register.php`
- **Login**: `http://localhost:8000/views/login.php`
- **Dashboard**: `http://localhost:8000/views/dashboard.html` (requires login)
- **Setup Check**: `http://localhost:8000/setup-check-viewer.php`

**If using XAMPP/Apache (webroot is one level up):**
- **Home**: `http://localhost:3000/public_html/`
- **Register**: `http://localhost:3000/public_html/views/register.php`
- **Login**: `http://localhost:3000/public_html/views/login.php`
- **Dashboard**: `http://localhost:3000/public_html/views/dashboard.html` (requires login)
- **Setup Check**: `http://localhost:3000/public_html/setup-check-viewer.php`

**In Production (webroot IS public_html):**
- **Home**: `https://yourdomain.com/`
- **Register**: `https://yourdomain.com/views/register.php`
- **Login**: `https://yourdomain.com/views/login.php`
- **Dashboard**: `https://yourdomain.com/views/dashboard.html` (requires login)
- **Setup Check**: `https://yourdomain.com/setup-check-viewer.php` (hidden from production)

## 📁 Project Structure

```
adhd-dashboard/
├── private/                 # SENSITIVE DATA (outside web root)
│   ├── .env                # Database credentials (never commit!)
│   └── .env.example        # Template for .env
│
└── public_html/            # Web root (served by Apache/XAMPP)
    ├── .env.example        # Reference only (actual .env in private/)
    ├── .gitignore          # Git ignore rules
    ├── index.php           # Entry point (redirects to login/dashboard)
    │
    ├── lib/
    │   ├── config.php      # Configuration loader (reads from private/.env)
    │   ├── database.php    # PDO Singleton database connection
    │   └── auth.php        # Authentication class
    │
    ├── api/
    │   ├── config.php      # API utilities
    │   └── tasks/
    │       ├── create.php
    │       ├── read.php
    │       ├── update.php
    │       └── delete.php
    │
    ├── views/
    │   ├── login.php
    │   ├── register.php
    │   └── dashboard.html
    │
    ├── css/
    │   ├── adhd-theme.css
    │   └── adhd-dashboard.css
    │
    ├── js/
    │   └── dashboard.js
    │
    └── sql/
        └── schema.sql      # Database schema
```

## 🔑 Key Features

### User Management
- **Registration**: Email, password (8+ chars), first/last name
- **Login**: Email and password authentication
- **Sessions**: 24-hour timeout with multi-device support
- **Security**: Bcrypt password hashing (cost 12), prepared statements

### Task Management (ADHD-Optimized)
- **Capture**: Brain dump mode with quick task entry
- **Organize**: 1-3-5 priority system (1 big, 3 medium, 5 small)
- **Track**: Status tracking (inbox, in-progress, waiting, completed, archived)
- **Deadlines**: Optional due dates with flexible scheduling

### API Endpoints

#### Tasks - Create
```
POST /api/tasks/create.php
Content-Type: application/json

{
  "title": "Task name",
  "description": "Optional details",
  "priority": 2,           // 1-5
  "due_date": "2025-01-15" // Optional
}

Response: { success: true, task: {...}, message: "Task created" }
```

#### Tasks - Read
```
GET /api/tasks/read.php?status=inbox&priority=urgent&sort=created_at&order=DESC

Query Params:
- status: inbox | in_progress | waiting | completed | archived
- priority: urgent | high | medium | low
- sort: created_at | due_date | priority | title
- order: ASC | DESC

Response: { success: true, tasks: [...], count: 5 }
```

#### Tasks - Update
```
PUT /api/tasks/update.php
Content-Type: application/json

{
  "task_id": 123,
  "title": "Updated title",
  "status": "in_progress",
  "priority": 3,
  "completed_date": "2025-01-14"
}

Response: { success: true, task: {...}, message: "Task updated" }
```

#### Tasks - Delete
```
DELETE /api/tasks/delete.php
Content-Type: application/json

{
  "task_id": 123
}

Response: { success: true, task_id: 123, message: "Task deleted" }
```

## 🎨 Design System

### Colors (WCAG 2.1 AA Compliant)
- **Yellow**: `#FFB300` (filled backgrounds, icons only - NOT text)
- **Orange**: `#FF9F43` (secondary actions)
- **Pink**: `#B8E0D2` (calm slots, borders)
- **Dark Text**: `#2D3A4E` (all readable content)
- **White**: `#FFFFFF` (backgrounds)

### Typography
- Font: Nunito Sans (warm, friendly)
- Base: 16px (accessibility minimum)
- Headings: Bold, larger sizes
- High contrast: 4.5:1+ WCAG AA compliant

### ADHD Principles
✅ Large, tappable buttons (48px+ minimum)
✅ Clear visual hierarchy with warm colors
✅ Brain dump mode for quick capture
✅ 1-3-5 priority system prevents overwhelm
✅ No notifications/distractions (distraction-free)
✅ Persistence without pressure (tasks stay until completed)
✅ Visual progress tracking

## 🔒 Configuration System (Environment-Aware)

The ADHD Dashboard uses an environment-aware configuration system similar to the BusyBee CMS. Configuration automatically adjusts based on whether you're running in development or production.

### Environment Detection

The system automatically detects your environment by checking:
1. **Hostname** - localhost/127.0.0.1 = Development, other domains = Production
2. **Filesystem** - XAMPP/htdocs paths = Development
3. **Default** - Production if unsure

### Configuration Architecture

```
/private/.env              ← Sensitive credentials (NOT web-accessible)
/public_html/lib/config.php ← Loads .env and provides environment-aware helpers
/public_html/lib/database.php ← Uses Config::get() for database connection
```

### How It Works

**1. Database Credentials** (Same file for both environments)
```
# /private/.env
DB_HOST=127.0.0.1:3307
DB_NAME=adhd_dashboard
DB_USER=root
DB_PASSWORD=
```

**2. Path Resolution** (Automatic per environment)
```php
// Development (localhost): URLs include /public_html/
// http://localhost:8000/public_html/api/tasks/read.php

// Production (custom domain): URLs don't include /public_html/
// https://mydomain.com/api/tasks/read.php
```

**3. Config Helper Methods**
```php
// Get config value
Config::get('DB_HOST');           // Returns: 127.0.0.1:3307

// Get filesystem paths
Config::path('root');             // Returns: C:\xampp\htdocs\adhd-dashboard
Config::path('uploads');          // Returns: C:\xampp\htdocs\adhd-dashboard\public_html\uploads
Config::path('private');          // Returns: C:\xampp\htdocs\adhd-dashboard\private

// Get web URLs (environment-aware!)
Config::url('base');              // Dev: http://localhost:8000/public_html/
                                  // Prod: https://mydomain.com/

// Environment checks
Config::isDevelopment();           // Returns: true/false
Config::isProduction();            // Returns: true/false
```

### Why This Approach?

✅ **Security**: Credentials never in web root  
✅ **Simplicity**: Same .env file works in dev AND production  
✅ **Flexibility**: Paths adjust automatically  
✅ **Portable**: Works on any hosting/port/webroot structure  
✅ **Tested**: Pattern from proven BusyBee CMS multi-tenant system

### For Production Deployment

When deploying to production:

1. **Keep the same .env setup** - no changes needed!
2. **Update credentials if needed** (unlikely unless different MySQL server):
   ```
   DB_HOST=prod-mysql-server.com
   DB_NAME=adhd_dashboard_prod
   DB_USER=produser
   DB_PASSWORD=prodpassword
   ```
3. **System will auto-detect** production environment based on domain name
4. **All paths and URLs will automatically adjust** - no code changes needed!

## 📦 Database Tables

1. **users** - User accounts with email, hashed password
2. **tasks** - Main task table with status, priority, dates
3. **projects** - Project groupings
4. **project_tasks** - Many-to-many task-project mapping
5. **categories** - User-defined tags
6. **task_categories** - Many-to-many categorization
7. **reminders** - Notification scheduling
8. **invoices** - Invoice tracking
9. **invoice_items** - Invoice line items
10. **files** - File uploads/attachments
11. **sessions** - Session tokens and management
12. **audit_log** - Compliance logging
13. **user_settings** - User preferences
14. Plus indexes and foreign keys for performance

## 🧪 Testing

### Manual API Testing (using curl)

```bash
# Create task
curl -X POST http://localhost:8000/api/tasks/create.php \
  -H "Content-Type: application/json" \
  -d '{"title": "Test task", "priority": 2}' \
  -c cookies.txt

# Read tasks
curl -X GET "http://localhost:8000/api/tasks/read.php?status=inbox" \
  -b cookies.txt

# Update task
curl -X PUT http://localhost:8000/api/tasks/update.php \
  -H "Content-Type: application/json" \
  -d '{"task_id": 1, "status": "completed"}' \
  -b cookies.txt

# Delete task
curl -X DELETE http://localhost:8000/api/tasks/delete.php \
  -H "Content-Type: application/json" \
  -d '{"task_id": 1}' \
  -b cookies.txt
```

## 🛠️ Troubleshooting

### "Connection refused" error
- Check MySQL is running
- Verify credentials in `.env` match your MySQL setup
- Ensure database `adhd_dashboard` exists

### "Table doesn't exist" error
- Run `schema.sql` to create all tables
- Verify you're using the correct database name

### Login page not working
- Check `.env` file exists and has correct DB credentials
- Verify `lib/auth.php` and `lib/database.php` are in correct paths
- Check error logs: `php -l public_html/views/login.php`

### Frontend won't connect to API
- Check frontend is making requests to `/api/tasks/...` (absolute paths)
- Verify API files have `require_once '../lib/auth.php'`
- Check session cookies are being sent (PHPSESSID)

## 📞 Support

For issues or questions about:
- **Database schema**: See `sql/schema.sql`
- **API endpoints**: See `api/tasks/*.php` methods
- **Authentication**: See `lib/auth.php` class
- **Frontend integration**: See `js/dashboard.js`

---

**Last Updated**: January 2025
**Version**: 1.0.0 Beta
**Status**: Backend complete, frontend ready, awaiting database setup
