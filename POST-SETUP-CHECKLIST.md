# ADHD Dashboard - Post-Setup Checklist

Use this checklist after you've created the database to verify everything works.

## Prerequisites ✓
- [ ] Database `adhd_dashboard` created
- [ ] Schema imported from `sql/schema.sql`
- [ ] `.env` file configured with your DB credentials
- [ ] PHP server running (`php -S localhost:8000` or XAMPP/WAMP)

---

## Verification Phase (Do First)

### 1. Run Setup Checker ✓

**For PHP built-in server** (`php -S localhost:8000`):
```
http://localhost:8000/setup-check-viewer.php
http://localhost:8000/setup-check.php (JSON)
```

**For XAMPP/Apache** (webroot one level up):
```
http://localhost:3000/public_html/setup-check-viewer.php
http://localhost:3000/public_html/setup-check.php (JSON)
```

Expected output:
- ✓ PHP version 7.4+
- ✓ All extensions loaded (pdo, pdo_mysql, json, session, mbstring, filter)
- ✓ Environment detected (Development or Production)
- ✓ Configuration loaded from `/private/.env`
- ✓ All files present (db, auth, API, views, assets)
- ✓ Paths and URLs resolved correctly (environment-aware)
- ✓ Database connection successful
- ✓ URLs generated correctly
- ✓ Database connection successful
- ✓ 15+ tables created
- ✓ Directory permissions writable

**If ANY check fails**, don't proceed - review README.md troubleshooting section.

---

## Authentication Testing

### 2. Test Registration ✓
1. Go to: `http://localhost:8000/views/register.php`
2. Fill in form:
   - First Name: `Test`
   - Last Name: `User`
   - Email: `test@example.com`
   - Password: `TestPassword123`
   - Confirm: `TestPassword123`
3. Click "Create Account"
4. Should redirect to login page with message

**Check database:**
```sql
SELECT * FROM users WHERE email = 'test@example.com';
```

Should show your new user (password hashed, not plain text).

### 3. Test Login ✓
1. Go to: `http://localhost:8000/views/login.php`
2. Email: `test@example.com`
3. Password: `TestPassword123`
4. Click "Login"
5. Should redirect to dashboard

**Browser Check:**
- Check cookies: DevTools → Application → Cookies
- Should see `PHPSESSID` cookie

### 4. Test Session Persistence ✓
1. While logged in, refresh the page
2. Should still be logged in (session works)

### 5. Test Logout ✓
1. In dashboard, click logout button (if implemented)
2. Should redirect to login page
3. Try accessing dashboard directly - should redirect to login

---

## API Testing

### 6. Create Task via API ✓

**Using curl:**
```bash
curl -X POST http://localhost:8000/api/tasks/create.php \
  -H "Content-Type: application/json" \
  -d '{"title":"Test Task","description":"Testing API","priority":2}' \
  -c cookies.txt
```

**Expected response:**
```json
{
  "success": true,
  "task": {
    "id": 1,
    "user_id": 1,
    "title": "Test Task",
    "description": "Testing API",
    "status": "inbox",
    "priority": 2,
    "created_at": "2025-01-15 10:30:00",
    "updated_at": "2025-01-15 10:30:00"
  },
  "message": "Task created successfully"
}
```

**Check database:**
```sql
SELECT * FROM tasks WHERE user_id = 1;
```

### 7. Read Tasks via API ✓

```bash
curl -X GET "http://localhost:8000/api/tasks/read.php?status=inbox" \
  -b cookies.txt
```

**Expected response:**
```json
{
  "success": true,
  "tasks": [
    {
      "id": 1,
      "title": "Test Task",
      ...
    }
  ],
  "count": 1
}
```

### 8. Update Task via API ✓

```bash
curl -X PUT http://localhost:8000/api/tasks/update.php \
  -H "Content-Type: application/json" \
  -d '{"task_id":1,"status":"completed"}' \
  -b cookies.txt
```

**Expected response:**
```json
{
  "success": true,
  "task": {
    "id": 1,
    "status": "completed",
    ...
  },
  "message": "Task updated successfully"
}
```

**Check database:**
```sql
SELECT status FROM tasks WHERE id = 1;
```

Should show `completed`.

### 9. Delete Task via API ✓

```bash
curl -X DELETE http://localhost:8000/api/tasks/delete.php \
  -H "Content-Type: application/json" \
  -d '{"task_id":1}' \
  -b cookies.txt
```

**Expected response:**
```json
{
  "success": true,
  "task_id": 1,
  "message": "Task deleted successfully"
}
```

**Check database:**
```sql
SELECT COUNT(*) FROM tasks WHERE id = 1;
```

Should return 0.

---

## Frontend Testing

### 10. Dashboard Access ✓
1. Go to: `http://localhost:8000/views/dashboard.html`
2. Should see ADHD dashboard with:
   - Capture section (input + submit)
   - Priority sections (1 big, 3 medium, 5 small)
   - Completed section
3. Try entering and submitting a task
4. Refresh page - task should still be there (localStorage or API, depending on integration)

### 11. Dashboard Functionality ✓
- [ ] **Capture works**: Can enter text and submit
- [ ] **Organize works**: Can see tasks in priority slots
- [ ] **Complete works**: Can check off completed tasks
- [ ] **Persistence works**: Refresh page, tasks remain (currently localStorage)

---

## Integration Phase (Next Steps)

### 12. Connect API to Frontend
Modify `js/dashboard.js` to use API instead of localStorage:

**Replace:**
```javascript
localStorage.setItem('tasks', JSON.stringify(tasks));
localStorage.getItem('tasks');
```

**With:**
```javascript
// Save to API
fetch('/api/tasks/create.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ title, description, priority })
})

// Load from API
fetch('/api/tasks/read.php?status=inbox')
```

See QUICKSTART.md for complete examples.

### 13. Test End-to-End
1. Create task in dashboard
2. Refresh page - task should persist
3. Move task between priority slots - check updates in DB
4. Mark task as complete - update in DB
5. Delete task - removed from DB

---

## Security Verification

### 14. SQL Injection Protection ✓
Try creating task with malicious input:
```bash
curl -X POST http://localhost:8000/api/tasks/create.php \
  -H "Content-Type: application/json" \
  -d '{"title":"Test\"; DROP TABLE tasks; --","priority":1}'
```

Should create a normal task, not drop the table.

### 15. Password Security ✓
```sql
SELECT password FROM users LIMIT 1;
```

Should show hashed value like: `$2y$12$...` (bcrypt), NOT plain text.

### 16. Session Security ✓
```sql
SELECT * FROM sessions;
```

Should show tokens, expiry times, but NOT passwords.

---

## Performance Check

### 17. Database Indexes ✓
```bash
mysql> SHOW INDEXES FROM tasks;
```

Should show indexes on:
- `user_id` (for quick lookup by user)
- `status` (for filtering)
- `created_at` (for sorting)

### 18. Query Speed ✓
Reading 1000 tasks should complete in <1 second:
```bash
curl -s http://localhost:8000/api/tasks/read.php?status=inbox | head -c 100
```

Completes quickly? ✓

---

## Final Checklist

- [ ] Setup checker shows all green
- [ ] Registration works
- [ ] Login works
- [ ] Sessions persist
- [ ] API creates tasks
- [ ] API reads tasks
- [ ] API updates tasks
- [ ] API deletes tasks
- [ ] Dashboard displays
- [ ] Dashboard can create tasks
- [ ] SQL injection is blocked
- [ ] Passwords are hashed
- [ ] Database queries are fast

## Status
- [ ] **All checks passing?** → You're ready for frontend integration ✅
- [ ] **Some checks failing?** → Review README.md troubleshooting
- [ ] **Everything working?** → Next: API integration with dashboard.js

---

## Debugging Commands

### View Database Structure
```sql
DESCRIBE tasks;
DESCRIBE users;
DESCRIBE sessions;
```

### Check User Settings
```sql
SELECT * FROM user_settings WHERE user_id = 1;
```

### View Audit Log
```sql
SELECT * FROM audit_log ORDER BY created_at DESC LIMIT 10;
```

### Clear Test Data
```sql
DELETE FROM tasks WHERE user_id = 1;
DELETE FROM users WHERE email = 'test@example.com';
```

---

**Last Updated**: January 2025  
**Estimated Time**: 30-45 minutes for full verification  
**Success Criteria**: All items checked ✓
