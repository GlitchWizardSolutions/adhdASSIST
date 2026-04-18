# ADHD Dashboard Application - Comprehensive Specification

**Version:** 1.0  
**Date:** April 2, 2026  
**Status:** Ready for Review & Iteration  

---

## 1. PROJECT OVERVIEW

### 1.1 Purpose
The ADHD Dashboard is a web-based personal productivity and task management system specifically designed for individuals with ADHD. It combines Getting Things Done (GTD) methodology with ADHD-specific features to reduce cognitive load, provide visual feedback, and create a supportive, gamified experience that encourages consistent use.

### 1.2 Primary Goal
Provide individuals with ADHD tools that **replace cognitive function deficits with external organization and prioritization**, delivering only positive feedback and motivational reinforcement.

### 1.3 Core Audience
- Primary: Individuals with ADHD seeking personal task management
- Secondary: Family members (spouse, parents, children) assigned tasks by admin
- Tertiary: Admin/parent managing the system and user accounts

---

## 2. USER ROLES & PERMISSIONS

### 2.1 Role Definitions

| Role | Responsibilities | Access Level |
|------|------------------|--------------|
| **Developer** | System setup, email credentials, core configuration, change Admin account, perform Admin tasks if needed | Full access to all settings + all Admin functions + Developer settings hidden from other users |
| **Admin** | Creates user accounts, manages system, assigns tasks to other users, sets system preferences | Full access to own dashboard + all user management + task assignment features (Dashboard hidden from Admin, sees only user management + Admin settings) |
| **Regular User** | Personal ADHD dashboard, file management, task management | Access to own dashboard, file system, and assigned tasks only |

### 2.2 Key Permission Rules
- **Developer role:** Permanent, one per installation (you/Barbara), hidden from Admin UI to prevent accidental modification
- **Admin role:** Only one per installation; can be changed by Developer only
- Each user has their own isolated dashboard and file system
- Admin can create, edit, deactivate, or delete user accounts (Dev only for Dev account)
- Admin can assign tasks to other users; assignee receives notification but cannot reject (Admin is accountable)
- Admin can view completion status of all assigned tasks (to track delegation)
- Users can only be invited by Admin; no public registration
- Single application-wide email address (configured by Developer); each user receives emails to their own profile email
- PWA offline capability: Tasks captured offline sync when reconnected
- All users must be invited by Admin; no public registration

---

## 3. CORE MODULES & FEATURES

### 3.1 Authentication & User Management (from login-system)

#### 3.1.1 Login System
- **Login Options:**
  - Email address (primary identifier)
  - Username (optional secondary identifier)
  - Both email and username can be used interchangeably for login
- **Session Management:**
  - "Remember me" functionality for trusted devices
  - Automatic session timeout with graceful warning
  - HTTPOnly, Secure cookies for session tokens
  - PWA-compatible session persistence (syncs across tabs/windows)
- **Password Management:**
  - Password hashing via PHP password_hash() (bcrypt)
  - Password reset functionality (email link with expiring token)
  - Password strength requirements (enforced)
- **Email Management:**
  - Email verification on account creation (confirmation link)
  - Email change verification (user confirms new email before it becomes active)
  - Can log in with old email until new email verified, then new email only
- **Multi-Device Support:**
  - Sessions persist across devices
  - "Remember me" works for each device independently
  - Security: Can log out all other sessions (for security concerns)

#### 3.1.2 User Profile
- **Core Information:**
  - Email address (unique; requires verification on change)
  - Username (unique; optional secondary login method)
  - Full name
  - Profile picture (optional)
  
- **Location & Notifications:**
  - Timezone (auto-detects from browser; user can override)
  - Mobile carrier (optional; for SMS gateway: AT&T, Verizon, T-Mobile, Sprint, etc.)
  - Phone number (optional; for SMS notifications via carrier email gateway, e.g., 1234567890@txt.att.com)
  - Notification preferences:
    - Email notifications: On/Off per reminder type
    - In-app bell notifications: On/Off
    - SMS notifications: On/Off (requires carrier + phone number)
    - Quiet hours: Custom start/end times (default 9 PM - 8 AM)
    - Notification frequency: Immediate, Daily Digest, or per-item (user choice per reminder type)
  
- **Preferences & Settings:**
  - Theme/color palette (Ocean, Forest, Sunset, Minimalist)
  - Low-energy mode toggle (default filter for easy wins)
  - Task rescheduling preference: Automatic (to backlog) or Manual (choose date)
  - Daily habits reset time (default: midnight in user timezone)
  
- **Account Management:**
  - Change password (requires current password verification)
  - Enable/disable "Remember me" devices
  - Add SSH keys (if approved for future API access)
  - **Permanently delete account** (all data deleted, recoverable for 30 days if Developer restores)

#### 3.1.3 Admin Dashboard (Non-Intrusive Family Management)

**Design Philosophy:**
- Admin is a **facilitator, not a micromanager**
- Admin can assign tasks and see status, but the rest is not admin's business
- Admin should not be required to do anything; system works autonomously
- Interface is NOT family-oriented (single users should feel equally comfortable)
- Admin has BOTH user dashboard AND admin features (seamless switching)
- Only one Admin per installation (changeable by Developer only)

**Admin Dashboard Access:**
- On login, Admin sees a link to "Admin Dashboard" in main navigation
- Can toggle between personal dashboard (user experience) and admin dashboard
- Design: Clean, professional, minimal; no overwhelming amounts of data

---

##### 3.1.3.A Admin Overview Screen

**First Thing Admin Sees:**
- Quick status cards (at-a-glance, not information overload):
  - "Active Users: 4"
  - "Pending Invites: 1"
  - "Tasks Assigned (You): 3" with completion status pie/progress
  - "New Inactive User Alert" (optional; see 3.1.3.H)
- Navigation tabs/buttons: Users, Delegated Tasks, CRUD Templates, Configuration

**No reporting, no analytics, no "trends" — just key numbers for operational awareness**

---

##### 3.1.3.B User Management

**User List View:**
- Table showing: Username, Email, Full Name, Status (Active/Inactive/Pending Invite), Last Login Date, Edit button, Deactivate/Reactivate button
- Search by name or email
- Filter by status (Active, Inactive, Pending)
- Sort by: Last Login, Name, Status

**Invite New User:**
- Button: "Invite User"
- Form: Email address (required), Full Name (optional), send by [date] (optional reminder to follow up)
- On submit: Email with invitation link sent
  - Invitation link valid for 7 days
  - Link goes to: `/views/accept-invite.php?token=[unique_token]`
  - Invitee sets password, accepts terms
  - No public registration; all users must be invited
- Admin can view pending invites and resend if needed
- Once accepted: User appears as "Active" in list

**Edit User (Admin can update):**
- Name
- Email
- Timezone
- Mobile carrier + phone number (for SMS notifications)
- Account Status (Active/Inactive)
- Assigned CRUD template permissions (see 3.1.3.E)
- Theme/Color preference (if not user-configurable)
- Task auto-delete/archive settings (see 3.1.3.G)

**Reset Password (Admin can initiate):**
- Button on user edit page
- Sends email to user with "Reset Password" link
- User clicks link, sets new password
- No password reset required by Admin; user controls new password

**Deactivate User:**
- Prevents user from logging in
- User data remains (not deleted)
- Can reactivate anytime
- Reassign or soft-delete user's pending tasks (Admin decides during deactivation)

---

##### 3.1.3.C Task Delegation & Status Tracking

**Delegated Tasks View:**
- Tab showing all tasks Admin has assigned to others
- Table columns: Task Title, Assigned To, Due Date, Status, Last Update
- Statuses: Not Started, In Progress, Completed
- Filter by assignee, due date range, or status
- Click task to view full details + reassign or reschedule if needed

**Assign a Task:**
- Admin (in their own user dashboard) creates a task
- At bottom of task form: "Assign to:" dropdown (includes all users)
- On assign: Task immediately appears on assignee's dashboard
- Assignee receives notification (email + in-app bell)
- Task is **non-rejectable** (Admin is accountable)

**Admin Notifications on Assigned Tasks:**
- Configurable per Admin (in settings):
  - Notify when task completed
  - Notify when task overdue
  - Notify when task due in X days (e.g., 3 days before due)
- Notification delivery: Email + in-app bell (not SMS)
- Admin can snooze or mark as "acknowledged"

**Soft Reminders for Inactive Users (Optional, Low Priority):**
- If user hasn't logged in for X days AND has tasks due soon:
  - System sends notification to **both user and Admin**
  - User gets: "You have a task due in 3 days"
  - Admin gets optional notification: "[User Name] hasn't logged in, has task due [task name]"
  - Frequency: Once per overdue task (not spammy)
  - Purpose: Gentle nudge, not helicopter parenting
- Configurable: Admin can enable/disable in settings

---

##### 3.1.3.D System Settings (Configuration Management)

**Navigation: Admin Dashboard → Settings**

**Email & Notifications:**
- Sender name (how emails appear; default: "ADHD Dashboard")
- Task assignment notification enabled/disabled
- Completion notification enabled/disabled
- Overdue notification enabled/disabled
- Due-soon notification (days before)
- Inactive user reminders enabled/disabled + interval (X days)

**Default Task Templates:**
- Admin can create 2-3 default task templates for all users
- Examples: "Medication Refill", "Weekly Review", "Random Chore"
- On user account creation, these templates appear as buttons in user's quick-capture area
- Users can use or ignore templates (opt-in, not forced)

**System-Wide Categories & Tags:**
- Admin can create default categories/tags for all users to use (e.g., "Health", "Work", "House", "Family")
- Categories/tags appear in user's task creation form (optional/combo field)
- Users can also create personal categories/tags (only they see)
- Admin categories are suggestions, not requirements

**Theme/Color Scheme (Per User):**
- Admin can see/control each user's theme choice
- Global theme NOT settable by Admin (each user picks their own)
- Prevents Admin from changing everyone's colors (privacy + preference)

---

##### 3.1.3.E CRUD Template Management

**CRUD Templates Overview:**
- Admin can view all available templates (those listed in Section 3.5.2):
  - Pre-built: Medications, Bills, Recipes, Emergency Contacts, etc.
  - Custom: Links/Passwords, Products/Warranty, Activity Logs, etc.
  - User-created: Custom templates created by users

**Template Permissions:**
- Admin has a matrix view: Users × Templates
- For each user and each template, toggle: ✓ Can use / ✗ Cannot use
- Example:
  - User "Mom": Can use [Medications, Bills, Recipes, Emergency Contacts]
  - User "Teen": Can use [Bills, Recipes] (no medications, no emergency contacts)
  - All users: Can use [custom templates they create themselves]

**User-Created Templates:**
- Users create custom CRUD tables on their own
- These are always visible/usable to that user
- Admin cannot restrict them (personal CRUD tables)
- Admin can view a list of "User-Defined Templates" but not restrict access

**Data Isolation:**
- If a template is used by multiple users, each user only sees their own records
- Example: "Bill Tracker" used by Mom and Teen; Mom's bills are hidden from Teen
- No shared data views (prevents privacy issues)

**No Audit Logs:**
- Admin does not see who edited what CRUD records
- Focus: Self-management and privacy

---

##### 3.1.3.F Message Board / Communication Center (Future, Lowest Priority)

**Concept (Not implemented in Phase 1):**
- Optional announcement board for Admin to post messages to specific users or all users
- Examples: "System maintenance on Saturday", "New feature added", "Reminder about password change"
- Not SMS/email; users see on dashboard
- Low engagement expectation (not a social feature)
- Can be added later if deemed valuable

---

##### 3.1.3.G Data Management: Bulk Task Removal (Per User OR Admin)

**User-Level (User can configure in settings):**
- Toggle: "Auto-archive completed tasks"
- Options:
  - Never
  - After 30 days
  - After 3 months
  - After 1 year
- Recurring tasks: Move completed instances to archive after completion (don't store indefinitely)
- One-time tasks: Archive when completed if user preference set

**Admin-Level (Can manage across users):**
- Admin can manually bulk delete/archive old completed tasks:
  - Select user(s)
  - Date range (e.g., "Completed before April 1, 2026")
  - Task type (All / One-time only / Recurring only)
  - Confirmation before deletion: "This will permanently delete [X] completed tasks"
  - Logs what was deleted (but NOT audit logs of who did it) — just Admin's own action record

**Purpose:**
- Remove completed recurring tasks that clutter history
- Reduce database bloat
- Keep active task list focused on relevant work
- Especially important for long-term users with hundreds of old tasks

---

##### 3.1.3.H Admin Access to Developer Dashboard

**Developer Role Only (Hidden from Admin):**
- All Developer settings remain hidden
- Admin does NOT see Developer Dashboard link
- Developer can access all Admin functions + Developer settings

**What Developer Does (Not Admin):**
- System health & monitoring (database backups, cron jobs, error logs)
- Data export/import (bulk user data, database exports)
- Change Admin role to another user
- Edit email configuration (SMTP credentials)

---

##### 3.1.3.I Admin Can Do Everything Users Can

**Dual Capability:**
- Admin has both Admin Dashboard AND User Dashboard
- Can toggle between them seamlessly
- When viewing User Dashboard as Admin:
  - Uses the same 1-3-5 interface
  - Can create personal tasks
  - Can use all CRUD templates they have permission for
  - Can configure personal settings (theme, notifications, etc.)
- No feature parity gaps; Admin is a full user + manager

**Single User Scenario:**
- Single user with Admin role: Sees Admin Dashboard but it's minimally distracting
- Can do task management in User Dashboard
- Can configure templates/categories in Admin settings if desired
- Never feels like a "family tool" even if used solo

---

##### 3.1.3.J Admin Dashboard Visual Design

**Principles:**
- Clean, professional, minimal (not colorful or playful like user dashboard)
- Dark sidebar or neutral header with main content area
- Tables, not cards (efficient information scanning)
- Action buttons left-justified with icons (quick recognition)
- No gamification, animations, or visual rewards (admin is operational)
- Accessible: Keyboard navigation, high contrast

**Layout:**
- Left sidebar: Navigation (Users, Tasks, CRUD, Settings)
- Main area: Content (tables, forms, status cards)
- Top right: Admin name, Settings gear, Logout
- No notifications badge (info-pushing; Admin chooses when to check)

---

#### 3.1.4 Progressive Web App (PWA) & Offline Capability
- **PWA Features:**
  - Installable on home screen (iOS, Android, Desktop)
  - Works offline after first load (Service Worker caches assets)
  - Fast load times (pre-caching critical assets)
  - Native-like experience (full-screen, app icon in taskbar)
  
- **Offline Task Capture:**
  - Users can capture tasks while offline
  - Tasks stored in browser's IndexedDB (local storage)
  - When reconnected, tasks sync automatically to server
  - Sync indicator shows status (Syncing... / Synced ✓)
  - Conflict resolution: Server data takes precedence (last write wins)
  - Offline indicator shown when no connection (bottom banner: "Offline - changes will sync when connected")
  
- **What Works Offline:**
  - Quick Capture / Brain Dump (stored locally, syncs on reconnect)
  - View cached tasks (read-only; last synced version)
  - View cached 1-3-5 list
  - Mark tasks complete (logged locally, syncs on reconnect)
  - Toggle daily habits (stored locally, syncs on reconnect)
  
- **What Requires Connection:**
  - Real-time notifications (bell)
  - Sync of other users' changes (if shared tasks)
  - Email sending (reminders, reassignments)
  - CRUD data updates (stored online)

- **Service Worker & Cache Strategy:**
  - Cache-first for static assets (CSS, JS, images)
  - Network-first for API calls (always fetch fresh, fallback to cache)
  - Periodic background sync (when reconnected, push any pending updates)

---

#### 3.1.5 Email System & SMS Gateway
- **Email Configuration (Developer Sets):**
  - Single outgoing email address for all app notifications (e.g., adhd-dashboard@example.com)
  - SMTP server credentials (can use Gmail, custom SMTP, or PHP mail())
  - From display name (customizable per email, e.g., "ADHD Dashboard" or "Barbara Moore")
  - Reply-To address (optional; allows users to reply to certain emails)

- **Email Delivery:**
  - Each user receives emails to their own profile email address
  - Email template customization (per email type: reminders, notifications, task assignment, etc.)
  - Unsubscribe option in footer of all emails
  - Bounce handling (auto-disable email notifications if bouncing)

- **SMS Gateway (Optional Free Solution):**
  - Uses carrier email-to-SMS gateways (free tier; no API key needed)
  - User provides: Mobile phone number + carrier
  - Format: `{phone_number}@{carrier_gateway}`
  - Examples:
    - AT&T: `9876543210@txt.att.com`
    - Verizon: `9876543210@vtext.com`
    - T-Mobile: `9876543210@tmomail.net`
    - Sprint: `9876543210@messaging.sprintpcs.com`
    - Other carriers supported
  
- **SMS Notifications Use Cases:**
  - Task assignment (urgent delegated tasks)
  - Task overdue alert
  - Medication refill reminder (for health-critical items)
  - Fallback if email disabled
  
- **Fallback for Users Without Email/SMS:**
  - Relies on in-app notification bell only (always available)
  - No email or SMS sent
  - User must log in to see notifications

---

#### 3.1.6 Developer Setup Wizard & Initialization
- **First-Time Setup (Developer runs once):**
  - Step 1: Database credentials (host, user, password, database name)
  - Step 2: Create Developer account (email, password, full name)
  - Step 3: Email configuration (SMTP settings, from address)
  - Step 4: Create first Admin user (email, password, full name)
  - Step 5: System settings defaults (timezone, theme, quiet hours)
  - Step 6: Confirm all settings, initialize database
  
- **Setup Wizard Behavior:**
  - Accessible only if database is empty (no `users` table exists)
  - Creates all tables via migration script
  - Sets up initial configuration
  - After completion, redirects to login page
  - Developer account hidden from Admin dashboard (cannot be modified by Admin)
  - Cannot re-run wizard (Developer must restore backup to reset)

---

#### 3.1.7 Debug Logging (Developer-Controlled)
- **Logging Scope:**
  - Database queries (slow queries > 500ms emphasized)
  - API request/response details (for debugging integration issues)
  - Error stack traces
  - User session events (login, logout, timeout)
  - File upload/download activity
  - Email sends (success/failure, recipient, timestamp)
  
- **Debug Toggle:**
  - Developer can enable/disable debug logging from Settings page
  - When enabled: Verbose logs written to `/private/logs/debug.log`
  - When disabled: Only errors logged (minimal overhead)
  
- **Log Retention:**
  - Debug logs rotated weekly (debug.log → debug.log.1, etc.)
  - Keep 4 weeks of logs (oldest auto-deleted)
  - Admin cannot view logs (Developer only)
  - Errors always logged regardless of debug toggle (separate error.log)

---

#### 3.1.8 Account Deletion & Data Management
- **User Account Deletion:**
  - Users can request permanent deletion from profile settings
  - Requires password confirmation (security check)
  - Grace period: 30 days before permanent deletion
  - During grace period: Account deactivated (user can reactivate by logging in)
  - After grace period: All data permanently deleted (cannot be recovered)
  
- **Data Deleted:**
  - User profile, preferences, settings
  - All tasks (both created by user and assigned to user)
  - All uploaded files
  - All CRUD records
  - All pomodoro sessions
  - All activity/comments
  - Notifications related to user
  - BUT NOT: Tasks user created for others (stay in system; ownership becomes "System")
  
- **Developer Override:**
  - Developer can delete account immediately (no grace period)
  - Developer can restore account from backup (if needed within 30 days)

---

### 3.2 Main Dashboard (GTD Inbox & Processing)

#### 3.2.1 Dashboard Sections and Task Management

**A. Quick Capture / Brain Dump**
- Single-field text input for immediate task capture
- Character limit: 255 characters (enforced, shown count)
- Voice Input (Optional): English language only, uses browser Web Speech API
- Capture Behavior: Task immediately slides into Inbox with toast confirmation
- Reduced cognitive load: immediate visual + verbal feedback

**B. Today's 1-3-5 Prioritized Tasks**
- Display: 1 Most Important Task, 3 Medium Priority Tasks, 5 Low Priority Tasks (exactly 9 slots)
- Visual Indicators:
  - Color border by urgency (based on due date proximity):
    - **Bright Yellow**: Due today or overdue
    - **Bright Orange**: Due within 3 days
    - **Calming Pink/Turquoise**: Due within week or no deadline
  - No red colors (causes anxiety in ADHD users); all warm and inviting
  - Progress pie chart (auto-calculated real-time: completed ÷ total tasks = % fill)

**C. Task Cards & Interactions**
- **Each task displays:**
  - Checkbox (left): Mark task complete
  - Clock icon (left of checkbox): Quick access to Focus Mode timer
  - Task title (center): Main action area
  - Priority/due date indicators
  - Edit button (right): Opens task details modal

- **Clock Icon (Focus Mode Access):**
  - Clicking the clock opens Focus Mode timer with **task name pre-filled**
  - Timer duration:
    - **Default**: 25 minutes (Pomodoro standard)
    - **If task has estimated_duration**: Pre-fills timer with that duration (e.g., task says "20 min" → timer defaults to 20)
  - **UX Behavior**: Timer form auto-submits → goes directly to fullscreen focus experience (no extra click needed)
  - Rationale: Streamlined flow minimizes friction for context-switching with ADHD

- **Edit Button → Task Details Modal:**
  - Modal displays:
    - Task title (editable text field)
    - Description (editable textarea)
    - Category/Tags: User-defined tags for organization (optional multi-select)
    - Due Date: Date picker for scheduling/rescheduling
    - Estimated Duration: Time estimate in minutes (used to prefill Focus Mode timer)
    - Status: Current status display (for reference; updated via main actions)
    - Buttons: Save, Delete, Cancel

- **Task Rescheduling:**
  - Due date can be changed via task details modal date picker
  - System updates task immediately upon save
  - No auto-archiving; user has full control over scheduling
  - Overdue indicator shows on task cards (visual warning)

- **Categories/Tags (Task Organization):**
  - User-defined tags for projects, contexts, or grouping
  - Tags are optional; tasks can have 0 or multiple tags
  - Used for filtering/organization; tags do NOT affect 1-3-5 status
  - Tags created/managed in task details modal (autocomplete from existing tags)
  - Example tags: @Work, @Home, @Health, ProjectName, etc.

- **User Actions Available on Each Task:**
  - **Mark Complete**: Checkbox removes task from view, real-time pie chart updates, positive feedback animation
  - **Launch Focus Mode**: Clock icon opens timer with task name pre-filled + estimated duration (if set)
  - **Edit Details**: Opens modal to change title, description, due date, category/tags, estimated duration
  - **Delete**: With confirmation (removes task permanently)
  - **Reschedule**: Via task details modal date picker
  - **Note**: Drag-drop task organization is handled on separate Task Planner page (not on dashboard)

**D. Inbox / GTD Capture Queue**
- Display: List of all captured tasks not yet prioritized into 1-3-5 slots
- Sort/Filter: By date added, due date, category, priority
- Actions:
  - **Move to 1-3-5**: Via task details modal status change
  - **Assign Category**: Via task details modal tags field
  - **Archive**: Hide completed/irrelevant tasks
  - **Delete**: Permanently remove task
- Visual Indicator: Count badge on Inbox (updates real-time)

**E. Daily Habits / Routine Checklist**
- Management: Created/edited in Profile → Daily Habits settings
- Display: Morning, Afternoon, Evening routine sections (radio button per habit for single time slot)
- Interaction: Toggleable checkbox for each item
- Feedback: Positive animation on check, no negative feedback on uncheck
- Reset: Daily at user's midnight (timezone-aware)
- Cross-Device Sync: Real-time if connection available; at minimum on page refresh

**F. Focus Mode: Immersive Full-Screen Modal**
- Access: Clock icon on task card, or "Focus Mode" button in Quick Actions
- Layout:
  - **Left (70%)**: Task details panel with title, description, due date, estimated duration
  - **Right (30%)**: Circular progress ring timer with controls
- **Timer Features:**
  - Large centered circular SVG progress ring (diameter: 300px desktop, 200px mobile)
  - Digital MM:SS display inside ring
  - Color gradient animation (blue → orange → yellow as time passes)
  - Duration presets: 10, 20, 30, 45, 60, 90, 120 minutes
  - Custom duration input for non-standard times
  - Controls: PAUSE/RESUME, CANCEL buttons
  - **Smart Duration**: If task has estimated_duration, that preset is highlighted by default
  - **Auto-Start**: Timer launches directly (no intermediate click needed)
- **Pause & Cancel:**
  - Unlimited pauses allowed; paused time counts toward total
  - Cancel closes modal silently (no guilt messaging)
  - No analytics/logging of focus sessions (one-and-done task focus)
- **Purpose**: Minimize distractions; clear focus target + time awareness
- **ADHD Support**: Executive function aid for time management and focus

---

#### 3.2.2 Quick Actions Bar
- Persistent bar at top of dashboard with quick access buttons
- Buttons:
  - **Focus Mode** (25:00 timer shown, but label is "Focus Mode" now — duration configurable)
  - **Add Habit** (shortcut to habits modal)
  - **View Task Planner** (navigates to separate task-planner.php page)
  - Other quick actions as needed

---

### 3.3 Task Planner Page (Drag-and-Drop Organization)

#### 3.3.1 Purpose & Access
- Dedicated page for visual task organization using drag-and-drop
- Access: "View Task Planner" button in Quick Actions, or /views/task-planner.php
- Layout: Two-column or single-column (mobile)
  - **Left**: Available Tasks (filtered/sortable list)
  - **Right**: 1-3-5 Priority Slots with drop zones

#### 3.3.2 Features
- **Drag-Drop from Available → Priority Slots**: Move tasks into 1-3-5 organization
- **Swap Between Slots**: Rearrange priority or change urgency level
- **Task Filtering & Sorting**: By due date, priority, category
- **Color-Coded Sections**: Green (Urgent/1), Orange (Secondary/3), Teal (Calm/5)
- **Task Cards**: Show title, due date, category tags, estimated duration

---

### 3.4 Pomodoro Timer & Focus Mode (REVISED - No Analytics)

#### 3.4.1 Overview & Launch Points
- **Primary Purpose**: Distraction-free countdown timer integrated with task focus
- **Launch Points**:
  - Dashboard clock icon on task cards (pre-fills task name + estimated duration)
  - Dashboard "Focus Mode" button in Quick Actions
- **Launch Behavior**: Timer launches in **fullscreen immersive modal** (Focus Mode)
  - Fullscreen prevents context-switching
  - No minimize/badge; user either works focused or pauses/cancels
- **No Session Logging**: Focus sessions are NOT logged or tracked (tasks are one-and-done)
  - Rationale: ADHD users appreciate simplicity; logging overhead adds friction
  - Focus time is for individual productivity, not analytics

#### 3.4.2 Visual Design: Circular Progress Ring
- **Ring Layout**: Large centered SVG progress ring (300px desktop, 200px mobile)
- **Ring Structure**:
  - Outer ring: Animated stroke that drains as time counts down
  - Inner digital display: Large white text showing MM:SS (e.g., "25:00")
  - Secondary text: "[Task Name] - Focus" (if launched from task)
- **Color Gradient**:
  - Start (full): Theme accent color (e.g., #3498DB)
  - Mid (50% time): Soft orange (#FF9F43)
  - End (10% remaining): Warm yellow (#FFB300)
- **Ring Animation**: Smooth SVG stroke drain (500ms transitions, no stuttering)

#### 3.4.3 Duration & Presets
- **Default Duration**: 25 minutes (Pomodoro standard)
- **Smart Default**: If launched from task with estimated_duration, uses that value instead
- **Preset Buttons**: 10, 20, 30, 45, 60, 90, 120 minutes (quick selection)
- **Custom Duration**: Text input for non-standard times
- **Pre-Shown Duration**: Prefilled before launch if task has estimate; can be overridden

#### 3.4.4 Controls & Behavior
- **PAUSE Button**: Pause and resume timer; unlimited pauses (all time counts)
- **CANCEL Button**: Closes modal silently; no confirmation dialog
- **Mute/Sound Toggle**: Optional audio alert on completion
- **No Guilt Messaging**: Cancellation or incompletion shows no negative feedback
- **Rationale**: ADHD-friendly UX minimizes executive function burden

---

#### 3.4.5 Session History Scope
- **MVP Decision**: NO focus session analytics or logging
- **Rationale**: 
  - Most tasks are one-and-done; analytics overhead adds complexity
  - ADHD users benefit from simplicity, not gamification metrics
  - Future phase (if requested): Could add optional Focus Time widget showing weekly hours
- **What IS tracked** (for future): Task completion timestamps help show activity

---

## 4. USER INTERFACE & DESIGN
  - If timer started from task detail page: Auto-populate task association (user just clicks "Log Session")
  - If timer started from dashboard: Show task dropdown (user selects or leaves blank)
  - **User Choice**: Can manually select different task, or leave blank ("Unassociated focus time")
- **Logging Data Captured**:
  - Duration: Actual time on timer (if paused 10 min, counts all 10 min)
  - Task: Associated task (optional; can be null for general focus time)
  - Date/Time: Timestamp of completion
  - Paused: Boolean flag (was session paused?)
  - Planning Data: If enabled, user's focus intention + distraction backup plan stored (optional)

##### 3.3.4.B "Log This Session" UX
- **Completion Screen** (after timer hits 0:00 or user clicks Stop):
  - Large checkmark animation (matches Section 3.9.1.B)
  - Success message: "Great focused session!" or "You stayed focused for 25 minutes!"
  - Input field: "Assign to task:" (dropdown of recent/favorite tasks or "None")
  - **LOG SESSION** button (primary) or **SKIP** (secondary)
- **On "Log Session"**: Stores immediately; shows toast "Session logged! Keep it up!"
- **On "Skip"**: No data saved; returns to dashboard
- **Mobile UX**: Touch-friendly buttons (60x60px minimum)

---

#### 3.3.5 Audio Alert & Completion Feedback

##### 3.3.5.A Audio Alert (Single Sound)
- **Trigger**: Timer reaches 0:00
- **Sound**: Single chime (300-400ms duration; not repeating)
  - Uses same Sound Options from Section 3.9.1.E (Chime/Bell/Ding/Silent)
  - Default: Gentle Chime (8-bit, warm tone, 300ms)
  - User preference: Configurable in **User Profile → Notifications**
- **Playback**:
  - Respects browser/system volume settings
  - Respects phone silent mode (iOS) / vibration mode (Android PWA)
  - Preloaded on dashboard load for immediate playback

##### 3.3.5.B Fallback Visual Alert (If Audio Muted)
- **Trigger**: Timer completes AND (sound muted OR browser volume 0%)
- **Visual Feedback**:
  - Ring glows with bright accent color pulse (0.5 sec → 1.0 sec scale, back to normal)
  - Glow repeats 3x (total 3 seconds) if user hasn't interacted
  - Secondary: Notification bell badge shows "Timer Complete" (red dot appears)
  - Screen flash effect optional (only if user prefers high-contrast accessibility in settings)
- **Purpose**: Ensures mobile/headless users notice completion even if audio muted

##### 3.3.5.C Completion Messaging
- **Display Format**: 
  - Large checkmark animation (scales + fills with green, Section 3.9.1.B style)
  - Success message below (18px, bold, green text): Randomized from pool
- **Message Pool** (similar to Section 3.9.1.D):
  - "Great focused session!"
  - "You stayed focused for [duration]!" (dynamically inserts selected duration)
  - "Well done! [X] minutes of deep work!"
  - "Excellent focus time!"
  - "You're crushing it!"
  - "Keep that momentum going!"
- **Display Duration**: Message stays visible for 2.0 seconds, then fades to "Log Session?" screen
- **No Failure Message**: If timer expires without completion, system does NOT show "You didn't finish!" (ADHD-safe)

---

#### 3.3.6 Pause & Cancellation Behavior

##### 3.3.6.A Pause Mechanics
- **Pause Button**: Always visible below timer ring
- **On Pause**:
  - Ring animation pauses (ring stays at current position)
  - Digital timer freezes (e.g., "18:34")
  - Button text changes to "RESUME"
  - Pause does NOT pause the audio/sound (if enabled)
- **Pause Restrictions**: **Unlimited pauses allowed**; all paused time counts toward session log
  - Example: 30-minute timer with 5 minutes paused = logged as 30 minutes
  - Rationale: ADHD users appreciate flexibility without guilt
- **Resume**: Clicking RESUME unpauses timer; continues countdown from where it stopped

##### 3.3.6.B Cancellation Behavior
- **Cancel Button**: Secondary button (gray, bottom-right below Pause)
- **On Cancel**: 
  - Timer immediately stops (no confirmation modal)
  - Fullscreen modal closes
  - Returns user to dashboard or task detail page (from whence they launched)
  - **No data saved** (unless user logging before cancel means session logged already)
  - **No guilt messaging**: System says nothing; return is silent
- **Rationale**: ADHD users need frictionless exit (avoid "Are you sure?" paralysis)

---

#### 3.3.7 Session History & Analytics

##### 3.3.7.A Focus Time Stats (User Dashboard)
- **Widget Location**: Optional dashboard widget or "Reports" page submenu
- **Stat Display**:
  - **"Focus Time This Week"**: "12 hours 45 minutes" (shows total logged focus sessions)
  - **"Average Session Length"**: "23 minutes" (average of all sessions)
  - **"Sessions Completed"**: "X sessions" (count of logged sessions)
  - **"Most-Focused Task"**: "[Task Name] - 8 sessions" (task with most logged time)
  - **"Streaks"**: "5-day focus streak" (consecutive days with ≥1 logged session)

##### 3.3.7.B Filtering & Drill-Down
- **Date Filter**: 
  - Preset options (This Week, Last Week, This Month, Last Month, Custom Range)
  - Shows stats for selected period
- **Task Filter**:
  - Dropdown: Show sessions for specific task only
  - Or: Show sessions across all tasks (default)
- **Visual Display**: Bar chart or simple table showing:
  - Date | Task | Duration | Completed
  - Example: "Apr 2, 2026 | Write Spec | 25 min | ✓"

##### 3.3.7.C Session History Scope (MVP vs. Future)
- **MVP** (Phase 1): Stats only (total time, average, streaks); no detailed session list
- **Phase 2** (Future): Detailed session list + filtering by task/date + download CSV + graphs
- **Rationale**: Stats encourage tracking without overwhelming (ADHD principle: minimal cognitive load)

---

#### 3.3.8 Focus Mode Integration

##### 3.3.8.A Full-Screen Modal Behavior
- **Timer Launch Behavior**: Fullscreen modal (not minimizable, not backgroundable)
- **Layout**: Same as Focus Mode (Section 3.2.5):
  - Left 70%: Task details + description + attachments
  - Right 30%: Circular timer + pause/cancel buttons
- **No Mini-Badge**: Because timer is always fullscreen, user cannot "minimize and leave view", so mini-badge is unnecessary
- **Immersion**: Navigation, dashboard, other UI hidden; user focuses entirely on task + time
- **Exit**: Only Pause or Cancel; user cannot accidentally swipe back/navigate away

##### 3.3.8.B Task Details Display (During Timer)
- **If Task Associated**:
  - Task title (large, 24px)
  - Task description (scrollable if long)
  - Attachments: Thumbnails/links visible (read-only during timer)
  - Subtasks (if any): Checkbox list visible but not interactive during timer (prevents distraction)
  - Due date + priority badges (reminder, not interactable)
- **If No Task Associated**:
  - Simple message: "[User Name]'s Focus Time" or "Deep Work Session"
  - Planning data displayed if enabled (user's intended focus + distraction backup plan)

##### 3.3.8.C Completion & Post-Session
- **On Timer Completion**:
  - Ring pulses 3x (visual + audio alert, Section 3.3.5)
  - Checkmark animation plays (Section 3.9.1.B)
  - Success message shown ("Great focused session!")
  - Modal transitions to "Log Session?" screen (task dropdown + LOG / SKIP buttons)
  - User can log session while still in fullscreen, then dismissed to task detail or dashboard

---

#### 3.3.9 Accessibility & Performance

##### 3.3.9.A Accessibility Compliance
- **Keyboard Navigation**:
  - Tab to move between Pause, Cancel, Mute buttons
  - Enter/Space to activate
  - Escape to cancel (if user prefers emergency exit)
- **Screen Readers**:
  - Ring aria-label: "[MM:SS] remaining, press Space to pause"
  - Buttons labeled: "Pause Timer", "Cancel Timer", "Toggle Sound"
  - Completion message read aloud via aria-live="polite"
- **Visual Contrast**:
  - Ring gradient colors meet WCAG AA contrast (Section 7.1)
  - Text always has ≥4.5:1 contrast ratio vs. background
  - Digital MM:SS display: 48px bold white text on ring background (high contrast)
- **Reduced Motion**:
  - `prefers-reduced-motion` respected: Ring drain (opacity transition only, no movement)
  - Digital timer still updates every second (time information critical)
  - Pulse/glow effect replaced with opacity fade (still gives feedback, less jarring)

##### 3.3.9.B Performance & Battery
- **Animation Performance**:
  - SVG ring uses requestAnimationFrame (60fps target on mobile)
  - GPU-accelerated transforms (scale, opacity)
  - NO canvas animations (better mobile performance)
  - Max CPU: <2% during countdown
- **Battery Impact**:
  - Minimal (timer just ticks; no heavy rendering)
  - Screen stays on (consider: Dark mode helpful for battery on OLED phones)
  - After 10 min idle (paused forever), dim screen suggestion or timeout
- **Mobile Optimization**:
  - Works on Android 8+, iOS 13+
  - Respects battery saver modes (reduces animation frame rate slightly)
  - No browser tab background impact if user switches tabs (countdown continues)

##### 3.3.9.C Browser Compatibility
- ✅ Chrome/Edge 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Mobile Safari (iOS 13+)
- ✅ Chrome Android (6.0+)

##### 3.3.9.D Testing Criteria (QA, Phase 2)
- ✅ Timer countdown accurate to ±1 second
- ✅ Circular ring animation smooth, no stuttering
- ✅ Sound plays on completion (all 4 sound options tested)
- ✅ Pause/resume works multiple times without degradation
- ✅ Cancellation instant (no UI lag)
- ✅ Session logging captures accurate duration (with pauses)
- ✅ Task association optional (can log with or without task)
- ✅ Fullscreen modal immersive (no distractions visible)
- ✅ Completion message feels rewarding (user survey: 4/5+ satisfaction)
- ✅ Mobile experience smooth on mid-range phones (60fps target)

---

#### 3.3.10 Settings & User Preferences

**Profile Settings (User → Preferences → "Pomodoro Timer")**:
- **Sound Selection**: Chime / Bell / Ding / Silent (radio buttons)
- **Sound Volume**: 0-100% slider (default 60%)
- **Enable Focus Planning Modal**: Toggle (default OFF)
- **Dark Mode for Timer**: Toggle (optional; helps battery on OLED)
- **Auto-Log Sessions**: Toggle (if enabled, sessions auto-log without asking; default OFF)
- **Min Task Duration Suggestion**: Dropdown (10/15/20/25/30 min; default 25)

---

---

### 3.4 Task Management (GTD-Enhanced for ADHD)

#### 3.4.1 Task States & Workflows
```
Brain Dump → Inbox (Clarify) → Categorized (Organize) → Next Action → Active/Scheduled → Complete/Delete
```

**Task States:**
- **Inbox**: Newly captured, awaiting clarification + categorization
- **Backlog**: Processed but not yet scheduled; visible in Backlog/Rescheduling view
- **Scheduled**: Assigned to specific date; visible calendar view
- **Active**: Currently on 1-3-5 list OR in current filter view (1 Essential, Quick Wins, etc.)
- **Completed**: Finished (logged with timestamp); can be reopened to Active via task detail page
- **Deleted**: Removed permanently; cannot be undone (commitment to deletion)

**Task Attributes (Complete List):**
- Title (required, 255 chars)
- Description (optional, rich text)
- Due Date (optional; auto-fill available for templates)
- **Priority**: High / Medium / Low / Someday Maybe (4-level system; see 3.4.1.A)
- **Category**: User-created categories (independent of CRUD tables; single category per task)
- **Assigned User**: Self or admin-delegated to other user
- **Recurring Settings**: None / Daily (midnight) / Weekly (day+time) / Monthly (date+time) / Custom (3-day pattern, custom schedule)
  - Configurable per recurring task instance
  - Next instance date/time determined by recurrence rule
  - Calendar shows only current instance (not next until tomorrow/next week)
- **File Attachments**: Optional; stored in user's `/private/uploads/[user_id]/`
- **Estimated Duration**: Minutes (for Pomodoro preset suggestion); optional
- **Low Effort Flag**: Checkbox (true/false) for Easy Wins filter mode
- **Tags**: Optional; searchable
- **Recurring Next Date**: System-calculated based on recurrence rule (not user-editable)

##### 3.4.1.A Priority System (High/Medium/Low/Someday Maybe)

**Priority Levels & Meaning**:
1. **High** - Urgent, important, timeline-driven (due soon or high consequence)
   - Algorithm places in 1-3-5 slots
   - Side Quest: Can be included (breaks paralysis on high-stakes tasks)
2. **Medium** - Important but flexible deadline; mid-range priority
   - Algorithm places in middle slot of 1-3-5 (the "3")
3. **Low** - Nice-to-do, flexible, can be deferred
   - Algorithm places in bottom slots of 1-3-5 (the "5" and below)
4. **Someday Maybe** - GTD "Someday" tasks; defer indefinitely but keep recorded
   - Does NOT appear in 1-3-5 (Backlog only)
   - Primary input to Side Quest roulette (see Section 3.9.2)
   - User can promote Someday Maybe → High/Med/Low when ready
   - Useful for ADHD: capture ideas without pressure to act immediately

**Priority Display**:
- Task card shows priority color badge (High=Bright Orange, Medium=Soft Blue, Low=Cool Gray, Someday Maybe=Purple/Lavender)
- Sorting: Algorithm respects priority + due date combined (due date trumps priority if same day)

##### 3.4.1.B Task Category System

**Category Model**:
- User can create categories independently (not tied to CRUD tables)
- Examples: "Work", "Health", "Personal", "Finance", "Home", "Learning"
- Categories searchable + filterable in task list and dashboard
- **Single category per task** (no multi-category to reduce decision paralysis)
- Category displayed as dropdown on task creation/edit form
- Mandatory field (user must select category when creating task)

**Default Categories** (pre-populated):
- Work
- Health
- Personal
- Finance
- Home
- Urgent (system-reserved for quick-triage; tasks auto-assigned if no category selected)

**Category Management**:
- User can add/edit/delete custom categories in settings
- Categories can be color-coded (optional)
- Category appears on task card + filters in dashboard/list views

#### 3.4.2 Task Creation Paths

##### 3.4.2.A Quick Capture (Minimal Friction)
- **Entry**: Text input field on dashboard (Section 3.2.1.A)
- **Process**: Type task title → Press Enter
- **Auto-Population**:
  - State: Inbox
  - Category: User's most-recent category OR system prompts if first task
  - Due Date: None (user can edit later)
  - Priority: Medium (default)
  - All other fields: Empty
- **Post-Capture**: Task appears in Inbox; can be edited immediately or deferred
- **UX**: No form complexity; encourages quick brain dump (ADHD-friendly)

##### 3.4.2.B Full Task Form
- **Access**: Dashboard "New Task" button OR left sidebar "Create Task"
- **Form Fields** (in order):
  1. **Title** (required text, 255 chars; auto-focus)
  2. **Description** (optional rich text; supports markdown)
  3. **Category** (required dropdown; default to recent category)
  4. **Priority** (required; High/Medium/Low/Someday Maybe; default Medium)
  5. **Due Date** (optional date picker)
  6. **Recurring** (optional; None / Daily / Weekly / Monthly / Custom)
     - If selected: Show recurrence config fields (e.g., "Every Monday" or "Every 15th of month")
  7. **Estimated Duration** (optional minutes; used for Pomodoro preset)
  8. **Low Effort Flag** (optional checkbox; "This is a quick task (< 15 min)")
  9. **Assigned To** (dropdown; "Me" or other users if admin)
  10. **File Attachment** (optional; drag-drop or file picker)
  11. **Tags** (optional comma-separated; searchable)

**Form Behavior**:
- Submit button: "Create Task" (saves + returns to dashboard)
- Secondary button: "Create & New" (saves + resets form for next task)
- Cancel: Returns to dashboard without saving

##### 3.4.2.C Task Templates

**Pre-Defined Template Categories**:

1. **Daily Tasks Template** (ADHD-Specific, NOT Same as Habit Tracker)
   - **Purpose**: Recurring daily actions (shower, brush teeth, medication) that need daily completion
   - **Key Difference from Habit Tracker (Section 3.2.4)**: 
     - Daily Tasks: Do NOT accumulate/track completion count; fresh each day
     - Habit Tracker: DOES accumulate completion count (e.g., "4/5 days gym")
   - **Recurrence**: Daily at configurable time (AM/Mid-Day/Afternoon/PM; NOT specific minutes—ADHD time-blindness friendly)
   - **Behavior**: 
     - Task appears once per day at configured time
     - User completes or skips (no penalty for skip; system removes old instance silently, adds fresh instance next day)
     - No "3 days of incomplete" backlog (user won't see medication list from 3 days ago)
   - **Examples**: "Take Medication", "Shower", "Brush Teeth", "Eat Breakfast"
   - **Admin Setup**: Can pre-create for all users (e.g., "Family Medication: [User Name]")

2. **Medication Refill Template** (Admin-Configurable for All Users)
   - **Setup**: Admin pulls from Medication Tracker CRUD table (Section 3.5.2)
   - **Auto-Generate**: Task created when refill date approaches (configurable: 3 days before, 7 days before, etc.)
   - **Fields Auto-Filled**: 
     - Title: "Refill [Medication Name]"
     - Description: "Prescriber: [name], Pharmacy: [name], Phone: [phone]"
     - Due Date: Refill date from CRUD
     - Priority: High (medications critical)
     - Category: Health
   - **Reminder**: Linked to Section 3.8 (email + SMS reminders on due date)

3. **Bill Tracker Template** (Admin-Configurable for All Users)
   - **Setup**: Admin pre-defines recurring bills (e.g., Electric due 15th monthly, Internet due 1st)
   - **Auto-Generate**: Task created on schedule (e.g., 7 days before due date)
   - **Fields Auto-Filled**:
     - Title: "[Vendor] Bill - $[amount]"
     - Due Date: Bill due date
     - Priority: High (overdue penalties severe)
     - Category: Finance
     - Recurring: Monthly / Quarterly / Annually (per admin config)
   - **Completion**: Marks unpaid bills as done when user completes (no payment processing; just task tracking)

4. **Custom CRUD Template** (User-Created)
   - **Access**: When user creates custom CRUD table (Section 3.5), option to "Create Task Templates from this CRUD"
   - **Example**: User creates "Projects to Learn" CRUD → Can create template "Add [Project Name] to Learning Queue"
   - **Behavior**: Similar to above; allows CRUD entries to spawn tasks
   - **Advanced**: Can set task metadata (priority, category) as CRUD data

##### 3.4.2.D Recurring Task Configuration

**Recurrence Rules** (User-Selectable):
1. **None** - Single instance (default)
2. **Daily** - Every day at [AM/Mid-Day/Afternoon/PM]
3. **Weekly** - Every [Monday/Tuesday/etc.] at [time]
4. **Monthly** - Every [15th] at [time] OR Every [2nd Tuesday] at [time]
5. **Custom** - User defines pattern (e.g., "Every 3 days" or "Mondays and Thursdays")

**Recurrence Behavior** (User Preferences):
- **Next Instance Generation**: Configurable per task
  - Option A: "Generate on Completion" - Next instance appears immediately upon check-mark
  - Option B: "Generate Nightly" - All recurring tasks sync at midnight (batch process)
  - Option C: User preference (set once in profile; default "Generate on Completion")
- **Calendar Display**: Shows only CURRENT instance (not next instance until that date arrives)
  - Example: "Brush teeth" daily task shows April 2 instance today; April 3 instance not visible until April 3
- **Skipped Instances Handling** (on task detail page):
  - When user opens a recurring task that wasn't completed for 3+ days, system offers:
    - "Catch Up" - Shows all 3+ incomplete instances; user can complete each OR
    - "Start Fresh Today" - Deletes old instances; starts fresh with today's instance
  - User choice per viewing (no system default forcing "catch up" guilt)

---

#### 3.4.3 Dashboard View Modes (Steady Progress / Quick Wins / Essential Only / Reset & Recharge)

**Overview**: 
Instead of always showing 1-3-5 slots, user can select from 4 strategic modes based on energy level. Toggleable dashboard button: "View Mode: [Current Mode] ▼"

##### 3.4.3.A Steady Progress (Default, 1-3-5)
- **Trigger**: User selects (default on login)
- **Display**: Standard 1-3-5 task list (Section 3.2.1.B)
- **Task Eligibility**: High/Medium/Low priority (NOT Someday Maybe)
- **Algorithm**: Due date ASC + Priority ASC → populate 3 slots
- **UX**: "You've got this! Here's today's focus." (encouraging message)
- **Recommended For**: Normal energy days; balanced workload

##### 3.4.3.B Quick Wins (Low-Energy High-Engagement)
- **Trigger**: User clicks "Quick Wins" mode OR auto-enable if low-energy day configured
- **Display**: Only tasks < 15 min estimated duration OR marked as "Low Effort"
- **Task Eligibility**: High/Medium/Low priority (NOT Someday Maybe)
- **Algorithm**: Shows all qualified tasks (no slot limits); user picks whichever feels best
- **Card Styling**: Smaller cards, green badges ("⚡ Quick Win")
- **Message**: "You don't need to do it all. Pick one quick task and feel great!" (permission to do less)
- **Use Case**: ADHD low-energy days; medication side effects; sleep-deprived days
- **Engagement Goal**: Complete 1-3 quick tasks; build momentum
- **Available After Quick Wins**: If user completes task, system suggests next Quick Win (keeps engagement)

##### 3.4.3.C Essential Only (Crisis Mode, 1 Task)
- **Trigger**: User clicks "Essential Only" OR admin/user sets "bad day" date/time
- **Display**: Only 1 major task shown (largest card, centered)
- **Task Eligibility**: Only HIGH priority + due today + most-urgent
- **Algorithm**: Sort by (due date ASC, priority ASC) → take TOP 1
- **If no High-priority task today**: System picks Medium priority (one task only)
- **Card Styling**: Large, prominent, centered; encouraging message below
- **Message**: "[Task Title] is all you need to focus on today. You've got this!" (extreme permission structure)
- **Backlog Visibility**: All other tasks HIDDEN (not shown, not stressing user out)
- **Use Case**: ADHD crisis day; severe depression/anxiety; system overload
- **Engagement Goal**: Complete 1 critical task; preserve mental health; strategic reset
- **Exit**: User can switch to Quick Wins after completing Essential task (gentle escalation)

##### 3.4.3.D Reset & Recharge (Strategic Day Off)
- **Trigger**: User clicks "Reset & Recharge" OR admin marks day as "strategic reset"
- **Display**: NO work tasks (empty 1-3-5 area)
- **Message**: "Today is for rest and recovery. You've done great. All tasks can wait." (validating + supportive tone)
- **Task Handling**: All today's tasks MOVED to tomorrow/next available day (snooze all)
  - Recurring tasks skip today (next instance tomorrow)
  - One-time tasks defer to tomorrow
  - User sees: "[X] tasks moved to tomorrow to give you space today"
- **What IS Visible**: Only Daily Tasks (medication, self-care) shown (health maintenance allowed)
- **Card Styling**: Calming color scheme (greens, teals); gentle message
- **Alternative Activities**: Suggest (optional): "Consider: Walk, Meditate, Journal, Call a Friend, Watch Show" (replacement actions)
- **Use Case**: Strategic mental health day; overwhelmed ADHD breakpoints; burnout prevention
- **Engagement Goal**: Rest + maintain health tasks only; return refreshed tomorrow

##### 3.4.3.E Mode Persistence & User Preference
- **Session Persistence**: Selected mode stays until user changes (not reset on page refresh)
- **Default Mode**: Configurable in user settings (default: Steady Progress)
- **Auto-Enable**: Admin/user can set "auto-switch days" (e.g., "Every Friday: Quick Wins") — optional feature
- **Suggested Auto-Switch**: If user completes 0 tasks for 2 consecutive days, system gently suggests switching mode ("Trying Quick Wins today might help you feel better")

---

#### 3.4.4 Task Completion, Deletion & Reopening

##### 3.4.4.A Completion Flow
- **Trigger**: User checks task checkbox OR clicks "Complete" button
- **Animation**: Plays full Section 3.9.1 animation sequence (Checkmark + particles + success message + sound)
- **Post-Completion Behavior**:
  - **Non-Recurring Task**: Task moves to completed state; user can view/reopen via task detail page (Section 3.4.4.C)
  - **Recurring Task**: Next instance auto-generates per recurrence config (Section 3.4.2.D); completed instance moves to history
- **Deletion of Attached Files**: On completion, system asks: "Keep attached files or delete with task?" (user choice)
- **Recurring Daily Tasks**: Special handling—completed instance removed silently, fresh instance appears next day (no "history")

##### 3.4.4.B Permanent Deletion (No Archive)
- **User Action**: "Delete" button on task card OR task detail page
- **Confirmation Modal**: "Delete this task permanently? This cannot be undone." (warning user of irreversibility)
- **On Confirm**: Task deleted immediately from database
- **Behavior**: 
  - If task has attached files, user prompted: "Delete files too or keep in file system?"
  - Deleted task does NOT appear in reports/history (truly gone)
- **Rationale**: Simpler UX (no archive state confusion); GTD principle of decisive action; ADHD users prefer finality over clutter

##### 3.4.4.C Reopening Completed Tasks
- **Access**: Via task detail page; button: "Reopen Task" (if task completed but not deleted)
- **Behavior**: 
  - Task state changes from Completed → Active
  - Moves back to 1-3-5 list (if priority warrants) or Backlog
  - Timestamp preserved (admin can see "Completed at 2:15 PM, Reopened at 2:47 PM" in activity log)
  - Attachments re-associated (no data loss)
- **Time-Blindness Accommodation**: System does NOT display "Time Completed" to user
  - Internal: Records completion time for data purposes
  - Display: Shows only date (e.g., "Completed on April 2") or no timestamp at all
- **Recurring Task Reopen**: Reopening a recurring task's completed instance does NOT affect next instance (next one still generates on schedule)

##### 3.4.4.D "No Undo" Philosophy (ADHD-Safe Commitment)
- **Deletion**: Permanent (no undo after delete)
- **Completion**: Reversible via reopen (safety net; reduces fear of "wrong click")
- **Rationale**: ADHD users need both commitment (deletion = final) and safety (completion = reversible)

---

#### 3.4.5 Task Delegation & Bulk Reassignment

##### 3.4.5.A Admin Delegation Model
- **Assignee Control**: Only Admin can assign/reassign tasks (users cannot self-reassign)
- **Task Assignment Workflow**:
  1. Admin creates task OR edits existing task → "Assign To: [User] ▼"
  2. Task state pre-set to **Active** (assigned work skips Inbox→Backlog processing)
  3. Task moves to assigned user's task list immediately
  4. Assigned user receives notification (Section 3.4.5.B)
- **Reassignment**: Admin can move task between users anytime; re-notifies assignee
- **Accountability**: Admin dashboard shows "Tasks Assigned to [User]" with completion status tracking (Section 3.1.3)

##### 3.4.5.B Notification on Task Assignment (SMS + Email)
- **Primary Notification**: SMS (text message via email-to-SMS gateway)
  - Example: "Hi [User], [Admin] assigned you: 'Pay Electric Bill' - Due Apr 15. [Link to task]"
  - Length: ≤160 characters (accommodate SMS limits)
  - Timing: Sent immediately upon assignment
- **Secondary Notification**: Email (optional; contains full task details + linked context)
  - Subject: "[Admin Name] assigned you a task: [Task Title]"
  - Body: Full task description, due date, attachments, category, priority
  - Link: Direct to task detail page in dashboard
- **Admin Preference**: Both SMS + Email enabled (not fallback; SMS primary for faster visibility)
- **User Can Disable**: Toggleable in notification settings (but Admin recommends keeping SMS on for delegated work)

---

#### 3.4.6 Bulk Task Actions

##### 3.4.6.A Bulk Task Selection UI
- **Activation**: Checkbox in task card top-left corner (hidden until user hovers/focuses)
- **Select All**: Dashboard checkbox in header/toolbar: "Select All" (selects all visible tasks in current view)
- **Selected Counter**: Badge showing "3 tasks selected" + "Clear Selection" link
- **Action Buttons** (appear when ≥1 task selected):
  - Snooze
  - Move to Category
  - Delete All

##### 3.4.6.B Snooze Action (Flexible Defer)
- **Dialog**: "Snooze until when?"
- **Options**:
  - Presets: Tomorrow, Next Week, Next Month, Or... [Custom Date Picker]
  - Custom: User selects exact date/time via calendar
- **Behavior**: 
  - Task due date updated to selected date
  - Task moves to Backlog (if current instance already passed)
  - Task re-appears in 1-3-5/dashboard on new due date
  - If recurring task: Only current instance snoozed (next instance unaffected)
- **Use Case**: "I'm overwhelmed; move all non-urgent tasks to next week"
- **Time-Blindness**: Option to snooze by relative term ("In 2 weeks") vs. absolute date (reduces cognitive load)

##### 3.4.6.C Move to Category (Bulk Organize)
- **Dialog**: "Move selected tasks to category:"
- **Options**: Dropdown of all user's categories (e.g., Work, Health, Personal)
- **Behavior**: All selected tasks' category updated to selected option
- **Use Case**: "Recategorize all these tasks from 'Inbox' to 'Work'" (quick bulk organize)

##### 3.4.6.D Delete All (Destructive; Requires Confirmation)
- **Dialog**: "Delete [X] tasks permanently? This cannot be undone."
- **Confirmation Required**: User must click "Yes, Delete All"
- **Behavior**: All selected tasks deleted immediately; no archive
- **Use Case**: "Clean up completed tasks" or "Clear overwhelming backlog strategically"
- **Safety**: Double-confirmation modal prevents accidental wipe-out

##### 3.4.6.E Bulk Action Safety
- **Non-Destructive Actions**: Snooze + Move → No confirmation needed (reversible)
- **Destructive Actions**: Delete All → Double-confirmation modal required
- **Post-Action Feedback**: Toast message showing result ("3 tasks deleted" or "5 tasks moved to Health")

---

#### 3.4.7 Task Search & Filtering

**Search Functionality**:
- Global search bar (top of dashboard) searching task titles + descriptions + tags
- Results displayed as list; click to open task detail
- Filters (optional):
  - By Category (dropdown/checkboxes)
  - By Priority (checkbox: High/Med/Low/Someday Maybe)
  - By Status (checkbox: Active / Backlog / Completed)
  - By Due Date (date range selector)
  - By Assigned User (dropdown; admin view only)
  - By Tags (searchable tag cloud)
- **Advanced Search**: Optional URL-based filters for power users (e.g., `?priority=high&category=work&status=active`)

**Sorting Options**:
- Due Date (ascending, nearest first)
- Priority (High/Med/Low)
- Created Date (newest first)
- Alphabetical (A-Z)
- Most Recently Updated

---

---

### 3.5 CRUD Tables (Customizable Data Management)

#### 3.5.1 Purpose & Access Model

**Purpose**: Allow tracking of structured information behind secure firewall: medications, bank accounts, recipes, hobbies, contacts, etc. Each CRUD table is independently configured and secured.

**Access & Visibility**:
- **All CRUD tables behind login firewall** (no public access; all data private to authenticated users)
- **Admin Control**: Admin determines which CRUD tables users can see and access
- **Table Types**:
  1. **Admin-Created Shared Tables** (e.g., Medications, Bills, Emergency Contacts)
     - Visible to assigned users (configurable per user)
     - Read-only for non-admin members (cannot edit or delete)
     - Filterable by user (e.g., "Show Medications for [User Name]")
  2. **User-Created Personal Tables** (e.g., hobby collections, personal recipes)
     - Visible to creator only (unless admin shares)
     - Creator can add/edit/delete records
     - Optional: Admin can promote personal table to shared (with permission)
- **Child Protection**: Users cannot edit or view other users' records (e.g., children cannot access parent's medications; siblings isolated from each other)

**Admin Dashboard: CRUD Management**
- View all tables, users with access, last modified date
- Create new tables (shared or template-based)
- Edit table structure (add/remove fields, change field types)
- Manage permissions (assign users to tables)
- Monitor usage (which users accessed when)

---

#### 3.5.2 Pre-Defined Templates (Admin-Managed)

##### 3.5.2.A Emergency Contacts (Pre-Populated Default)
- **Auto-Create**: Creates automatically on system setup (cannot be deleted)
- **Purpose**: Critical contact information for ADHD users (especially children); accessible via dashboard quick-link
- **Fields**:
  - Contact Name (required; text)
  - Relationship (required; dropdown: Parent, Guardian, Doctor, Emergency, Trusted Adult, School)
  - Phone Number (required, formatted phone; clickable for mobile)
  - Email (optional; clickable email link)
  - Address (optional; text)
  - Notes (optional; textarea; e.g., "Available 24hr", "Authorized pickup person")
- **Dashboard Location**: Quick-link button "Emergency Contacts" (prominent, easy access)
- **Mobile**: Large phone buttons (tap to call immediately)
- **Privacy**: Hidden from children's visible CRUD list; only accessible via dashboard button (admin controls visibility)

##### 3.5.2.B Medication Tracker (Admin-Created, User-Filtered)
- **Initial Setup**: Admin creates once; populated with all household medications
- **Fields**:
  - Medication Name (required; text)
  - Dosage (required; text; e.g., "500mg")
  - Frequency (required; dropdown: Once daily, Twice daily, As needed, Every X hours, Custom)
  - Time(s) of Day (required; checkbox/multi-select: AM, Midday, Afternoon, Evening, PM, Bedtime)
  - Refill Due Date (required; date)
  - **Refill Reminder Date** (auto-calculated; 7 days before refill date)
    - Display: "Today is refill day!" label appears 1 week before Refill Due Date
    - Task created on this date: "[Medication Name] - Request Refill" (due today)
  - Prescriber (required; text; e.g., "Dr. Smith")
  - Pharmacy (required; text; e.g., "CVS 123 Main St")
  - Pharmacy Phone (required; phone; clickable)
  - Notes (optional; textarea; e.g., "Take with food", "Do not take with OJ")
  - Assigned To (required; dropdown; selects user)
  - Instructions (optional; textarea; special instructions)
  - Side Effects (optional; textarea; for tracking reactions)

**Medication List View**:
- **Visibility Toggle**: "Show medications for: [User Name] ▼" (filters by assigned user)
- **Each medication card shows**:
  - Name, Dosage, Frequency
  - Times displayed as badges: [AM] [Midday] [Afternoon] [Evening] [Bedtime]
  - Next refill date (e.g., "Refill: Apr 15") with color badge if today is refill reminder day (orange "TODAY IS REFILL DAY!")
  - Prescriber + Pharmacy phone (clickable)
- **Mobile**: Card layout with large phone button to pharmacy
- **Admin view**: Can edit dosage, frequency, refill date; delete medications
- **User view**: Read-only; can view details but not edit

##### 3.5.2.C Bill Tracker (Admin-Created)
- **Setup**: Admin enters recurring bills with schedules
- **Fields**:
  - Vendor (required; text; e.g., "Electric Company")
  - Amount (required; currency; formatted $XXX.XX)
  - Due Date (required; date; e.g., "15th of month")
  - Recurrence (required; dropdown: Monthly, Quarterly, Annually, Custom)
  - Account Used (optional; text; e.g., "Checking 1234")
  - Category (required; dropdown: Utilities, Insurance, Subscription, Rent, Medical, Other)
  - Notes (optional; textarea; e.g., "Auto-pay enabled", "Payment method AMEX")
  - Assigned To (required; dropdown; selects user responsible)
  - Status (required; radio: Pending / Paid)

**Bill Tracker Behavior**:
- **Auto-Task Creation**: X days before due date (configurable per bill; default 3 days), creates task "[Vendor] - $XXX due [Date]" (High priority, Finance category)
- **Status Workflow**: 
  - On due date: Bill marked "Pending" (status visible on card)
  - When task completed: Bill auto-updates to "Paid" (status changes on card)
  - Option: User clicks "Mark Paid" button directly on bill card (manual override)
- **Auto-Reset**: Recurring bills (monthly/quarterly/annually) auto-reset status to "Pending" on recurrence date
- **List View**: Shows vendor, amount, due date, status (green checkmark if Paid, orange flag if Pending)
- **Admin edit**: Can change amount, due date, recurrence
- **User view**: Read-only; can see status; cannot edit

##### 3.5.2.D Recipe Tracker (Admin-Created or User-Created)
- **Creation**: Admin can populate shared recipes; users can create personal recipe collections
- **Fields**:
  - Recipe Name (required; text)
  - **Photo/Image** (optional; file upload; supports JPG, PNG, GIF)
    - Purpose: User can snap photo of written recipe instead of typing
    - Size: Large display (mobile-friendly; 400x400px or larger)
    - "Upload photo or..." link accepts file or paste
  - **Recipe Content** (required; one of):
    - **Option A**: WYSIWYG Rich Text Editor (if available in vendor directory; markdown support)
    - **Option B**: Structured sections (Ingredients textarea + Instructions textarea)
    - **Option C**: Paste URL (attempts to extract recipe from website)
  - Ingredients (textarea; formatted as "Qty Unit Ingredient", one per line)
  - Instructions (textarea; auto-numbered steps OR user enters one per line)
  - Prep Time (optional; minutes)
  - Cook Time (optional; minutes)
  - Category (optional; dropdown: Breakfast, Lunch, Dinner, Desserts, Snacks, Sides, Beverages, Sauces)
  - Tags (optional; comma-separated; e.g., "gluten-free, low-sugar, kid-friendly")
  - Source URL (optional; clickable link to original recipe)
  - Servings (optional; number)
  - Notes (optional; textarea; dietary restrictions, substitutions, family notes)

**Recipe Card Display**:
- Large photo (tap to enlarge)
- Recipe name, prep/cook time, servings
- Ingredients list (scrollable)
- Instructions (numbered steps)
- Print button (generates printer-friendly format)
- Email button (sends recipe as PDF to user's email)
- Share button (if admin enabled sharing; generates shareable link)
- Edit/Delete buttons (creator only)

**Recipe List View**:
- Thumbnail photo + name + quick info (prep time, servings)
- Filter by category or search by name/tags
- Sort by name, recently added, or prep time

##### 3.5.2.E Links/Passwords Tracker (Admin-Created or User-Created)
- **Purpose**: Secure, centralized storage of frequently-needed links and login credentials with optional visual identification
- **Creation**: Admin can create shared tracker; users can create personal trackers for different account types (social media, banking, shopping, etc.)
- **Fields**:
  - Title (required; text; e.g., "Gmail Account", "Amazon Shopping")
  - **Icon/Image** (optional; file upload; supports JPG, PNG, GIF)
    - Purpose: Visual identifier quickly displayed in table and detail view
    - Size: Thumbnail display (mobile-friendly; 80x80px in table, larger in detail view)
    - Use case: Company logo, app icon, or custom image for quick visual scanning
  - Username (required; text; copyable button for easy access)
  - Password (required; text; masked display with "Show/Hide" toggle)
    - Security: Passwords stored encrypted; only accessible to authenticated user
    - Copyable button: One-click copy to clipboard (auto-clears after 30 seconds)
  - Email (optional; text; clickable mailto link)
  - URL (optional; text; clickable hyperlink to login page)
  - Description (optional; textarea; e.g., "Main family email account", "Personal shopping account")
  - Notes (optional; textarea; e.g., "Security questions: Favorite color [Blue], First pet [Fluffy]", "Recovery email: [email]")

**Links/Passwords List View** (Desktop):
- Card-based or table format showing: Icon, Title, URL (clickable), and action buttons
- Mask passwords column (only show when expanded/detail view)
- Each entry has "Copy Username" and "Copy Password" quick buttons
- Filter by title or category (if user tags them)
- Search functionality (searches title, URL, email)
- Color-coded badges for entry type (optional; e.g., blue for social, green for banking, red for security)

**Links/Passwords List View** (Mobile):
- Card layout with icon, title, URL
- Three-dot menu or swipe for edit/delete/copy actions
- Tap card to open detail view (shows all fields including password)
- One-tap copy buttons for username and password

**Detail View**:
- Icon displayed prominently at top
- All fields shown with labels
- "Copy" buttons next to Username and Password fields
- "Open URL" button (if URL exists; opens in new tab/browser)
- Edit/Delete buttons (creator only)
- Auto-lock: If tab inactive > 5 minutes, password field re-masks (security feature)

**Security Features**:
- Passwords stored with encryption (bcrypt or similar)
- Passwords masked by default; toggle to reveal
- Auto-clear clipboard after 30 seconds (after copying password)
- Activity log (optional): Timestamps of when credentials were accessed (admin view only)
- Browser autofill warning (optional): "Save password?" prompt notification (user controls whether browser remembers)

**Use Cases**:
- Social media login credentials (Facebook, Instagram, Twitter, TikTok)
- Banking/Finance (online banking, investment accounts, PayPal)
- Shopping (Amazon, eBay, retail accounts)
- Streaming services (Netflix, Spotify, Disney+)
- Work/Professional (email, project management tools)
- Shared family credentials (when admin creates shared tracker for all users)

##### 3.5.2.F Products/Warranty Tracker (Admin-Created or User-Created)
- **Purpose**: Centralized storage of product information, purchase details, and warranty/documentation for appliances, electronics, tools, and household items
- **Creation**: Admin can create shared tracker; users can create personal trackers for their possessions
- **Fields**:
  - Title (required; text; e.g., "Kitchen Refrigerator", "DeWalt Drill")
  - **Product Image** (optional; file upload; supports JPG, PNG, GIF)
    - Purpose: Photo of actual product for quick visual identification
    - Size: Larger display (mobile-friendly; 400x400px in detail view, thumbnail in table/list)
    - Use case: User can photograph product, serial number label, or product manual cover
  - Product Type (required; dropdown: Appliances, Electronics, Power Tools, Hand Tools, Furniture, HVAC, Plumbing, Other)
    - Purpose: Quick categorization and filtering across large collections
  - Description (optional; textarea; e.g., "Stainless steel French-door fridge, 25 cu ft, Model: RF28R7011SR")
  - Notes (optional; textarea; e.g., "Purchased from Best Buy, Service plan includes in-home repair", "Known issues: Ice maker needs cleaning monthly")
  - **Warranty Document** (optional; file upload; supports PDF, JPG, PNG, JPEG, DOCX)
    - Purpose: Scanned warranty card, coverage details, terms and conditions
    - Label: "Warranty Document" or display filename
    - Action: Download/view button
  - **Receipt/Invoice** (optional; file upload; supports PDF, JPG, PNG, JPEG, DOCX)
    - Purpose: Original purchase receipt with date, price, store information
    - Label: "Receipt/Invoice" or display filename
    - Action: Download/view button
    - Use case: Proof of purchase for warranty claims, price reference
  - **Instruction Manual/User Guide** (optional; file upload; supports PDF, JPG, PNG, JPEG, DOCX)
    - Purpose: Product manual for troubleshooting, setup, maintenance
    - Label: "User Guide" or "Instructions"
    - Action: Download/view button; if PDF, embedded viewer (optional)
    - Use case: Users forget how to use/reset appliances; quick access saves frustration
  - Purchase Date (optional; date)
    - Use case: Track warranty expiration, age of equipment
  - Serial Number (optional; text; copyable button)
    - Use case: Warranty claims, customer support, identification

**Products/Warranty List View** (Desktop):
- Card-based or table format showing: Product Image (thumbnail), Title, Product Type (badge/icon), and action buttons
- Sortable by: Purchase Date, Product Type, Title
- Filter by Product Type, or search by Title
- Quick action buttons: "View Details", "Edit", "Delete"
- Color-coded type badges (e.g., blue for Appliances, orange for Tools)

**Products/Warranty List View** (Mobile):
- Card layout with product image, title, product type badge
- Secondary info: Serial #, Purchase Date (if available)
- Three-dot menu or swipe for edit/delete actions
- Tap card to open detail view (shows all fields and document links)

**Detail View**:
- Product image displayed prominently (large, tap to enlarge)
- All fields displayed with clear labels
- Document links clearly visible: "Download Warranty", "Download Receipt", "Download Manual"
  - If document is image/PDF, show icon indicating file type
  - Click to download or open (image files open in lightbox, PDFs might open in viewer)
- Serial number with copy button
- Edit/Delete buttons (creator only)

**Use Cases**:
- Appliances (refrigerator, oven, dishwasher, washing machine, dryer)
- Electronics (TV, computer, monitor, printer, router, smart speakers)
- Power tools (drill, saw, sander, impact driver)
- Hand tools (screwdriver sets, wrenches, etc.)
- Furniture (couch, bed frame, desk)
- HVAC equipment (furnace, air conditioner, water heater)
- Home systems (security system, garage door opener)

**Integration with Tasks** (Advanced):
- Optional: Admin can configure "Maintenance Reminders" from Product Tracker
  - Example: "If Product Type = HVAC, create task 'Change HVAC filter' every 3 months"
  - Example: "If Product has Warranty Document, create task 'Review [Title] warranty coverage' on purchase anniversary"

##### 3.5.2.G Custom CRUD (Admin-Created or User-Created Templates)

**Pre-Built Template Options**:
- Book Tracker (Title, Author, Genre, Read Date, Rating, Notes)
- Contact List (Name, Phone, Email, Address, Relationship, Notes)
- Travel Planner (Destination, Dates, Budget, Attractions, Bookings, Notes)
- Project Tracker (Project Name, Status, Deadline, Owner, Tasks, Notes)
- Hobby Collection (Item, Category, Acquired Date, Value, Condition, Notes)
- Links/Passwords Tracker (Title, Icon/Image, Username, Password, Email, URL, Description, Notes) — *See 3.5.2.E*
- Products/Warranty Tracker (Title, Image, Product Type, Description, Notes, Warranty Document, Receipt, Manual, Purchase Date, Serial Number) — *See 3.5.2.F*
- Activity Log Tracker (Title, Description, Category, Status, with nested Log Entries; print/email enabled) — *See 3.5.2.H*
- [Expandable as organization needs grow]

**Custom Field Configuration**:
- Admin/user selects "Create blank CRUD table"
- Define table name + description
- Add fields (unlimited):
  1. **Field Types** (support these):
     - Text (short text, single line)
     - Textarea (multi-line text)
     - **URL** (clickable hyperlink)
     - **Phone** (formatted phone number)
     - Email (clickable email)
     - Number (integer or decimal)
     - **Currency** (formatted money, e.g., $XXX.XX)
     - Date (date picker)
     - Time (time picker)
     - Checkbox (true/false)
     - Dropdown (predefined list of options)
     - File Attachment (single file per field)
     - **Image** (dedicated image field; larger display than file attachment)
  2. **Field Properties**:
     - Label (display name)
     - Type (required)
     - Required (true/false)
     - Max Length (for text fields)
     - Default Value (optional pre-fill)
     - Placeholder text (optional hint text in input)
     - Help text/Tooltip (optional; explains field to user, accessibility)
     - Unique (require unique values per record, prevent duplicates)
     - Min/Max (numeric validation)
     - Pattern (regex validation; optional implementation Phase 2)

**Custom CRUD Creator Control**:
- Creator can lock/protect table structure (prevent accidental deletion)
- Creator can mark records as "locked" (prevent other users from deleting)
- Creator can allow editing (default) or read-only
- Creator can share (if admin permits) or keep personal

##### 3.5.2.H Activity Log Tracker (Admin-Created or User-Created)
- **Purpose**: Professional, chronological record-keeping for significant life events, health tracking, legal documentation, child development milestones, complaints/resolutions, or any situation requiring a documented timeline. Suitable for court cases, legal disputes, medical advocacy, and official records.
- **Creation**: Admin can create shared templates; users can create personal logs for different tracking purposes (taxes, medical, legal, developmental, etc.)
- **Structure**: Two-level hierarchy:
  1. **Parent Log Record** (main tracking container)
  2. **Log Entries** (sequential dated entries within the log)

**Parent Log Record Fields**:
- Title (required; text; e.g., "2026 Tax Filing", "Doctor Visits — Chronic Pain", "Home Repair Dispute")
- Description (required; textarea; purpose/context of this log)
- Category (optional; dropdown: Legal/Dispute, Medical/Health, Financial, Personal Milestone, Other)
- Created Date (auto-populated; creation date of log)
- Status (optional; dropdown: Active, Closed, Resolved, Pending)
- Notes (optional; textarea; overall log notes, summary, or status updates)

**Log Entries** (nested within parent record):
- **Entry Date & Time** (required; date + time picker; defaults to current date/time; user can backdate if documenting past event)
- **Entry Title** (required; text; e.g., "Tax Return Filed", "Received Rejection Letter", "Follow-up Phone Call")
- **Description** (required; textarea; e.g., "Filed electronically via TurboTax. Confirmation #12345. Estimated refund: $2,500")
- **Document Upload** (optional; file upload; supports PDF, JPG, PNG, JPEG, DOCX, XLSX)
  - Purpose: Attach rejection letters, receipts, emails, medical records, correspondence, photos of incident
  - Label: Displays filename + upload date
  - Multiple documents per entry allowed
  - Action: Download/view button; preview for images
- **Visibility/Significance Flag** (optional; checkbox: "Mark as Important" or "Highlight" — visually stands out in timeline)

**Log Display — Timeline View** (Primary):
- Chronological vertical timeline (newest-first or oldest-first toggle)
- Each entry displayed as card or item in timeline:
  - Entry date/time (prominent)
  - Entry title (bold, clear)
  - Description preview (first 2-3 lines; click to expand)
  - Document icons/attachments (if present)
  - Importance flag badge (if marked)
  - Edit/Delete buttons (entry-level)
- Smooth scroll through timeline
- Color-coding (optional): Important entries highlighted differently, categories color-coded

**Desktop Timeline View**:
- Left-side vertical line with date markers
- Entries displayed to right of timeline (alternating positions for visual interest)
- Hover to show edit/delete actions
- Large screen utilization: Full description visible; attachments displayed as thumbnails

**Mobile Timeline View**:
- Simplified vertical timeline (center-aligned or left-aligned)
- Full entry visible per scroll (card-based)
- Swipe left to see edit/delete actions
- Tap entry to expand details + attachments
- Pinch to zoom on images/documents

**Parent Log Detail View**:
- Log title, description, category, status at top
- "Add Entry" button (prominent; creates new log entry)
- Timeline of all entries below
- Action buttons: Print Entire Log, Email Log, Export as PDF, Archive/Close Log, Edit Log Details, Delete Log
- Last modified date shown (accessibility: "Last updated April 8, 2026")

**Creating/Editing Log Entries**:
- Modal or form page opens for new entry
- Auto-populated: Current date/time (user can change)
- Fields: Title, Description, Document upload(s)
- Save Entry button
- Cancel button

**Reminder & Notification Integration** (Advanced):
- Parent log can have optional reminder for follow-up
- Example: Tax log with reminder "Check refund status" set for 4 weeks after filing date
- System sends notification (email + in-app bell) on reminder date
- User can snooze reminder or mark as "Checked/Resolved"
- Multiple reminders per log supported (e.g., "4-week check", "8-week check", "3-month check")

**Export & Print Options**:
- **Print**:
  - Prints entire log in professional format (like a report)
  - Header: Log title, date range, category
  - Timeline formatted for readability on paper
  - Embedded documents shown as attachments list or condensed previews
  - Page breaks handled intelligently
  - Footer: Timestamps, page numbers
  - Option to print "Court-Suitable Format" (no colors; professional black/white; timestamp accuracy emphasized)
- **Email**:
  - Sends log as PDF attachment via user's configured email
  - Subject: "[Log Title] — Dated [Date Range]"
  - Option to include/exclude attachments in PDF (filesize consideration)
  - Body: Brief intro text + log summary + timeline
- **Export as PDF**:
  - User downloads as file
  - Same formatting as email PDF option
  - Filename: "[Log Title]_[Date].pdf"
- **Export as CSV** (optional):
  - Simple data export (one row per entry; title, date, description, document filename)
  - For data archival or migration

**Use Cases**:
- **Tax/Financial**: Document tax filing, correspondence with IRS, refund tracking, disputes, amendments
- **Legal/Court**: Record timeline of incidents, harassment, disputes, document exchanges for court case
- **Medical/Health**: Track doctor visits for specific condition, symptoms, test results, medication changes, specialist referrals
- **Insurance Claims**: Document incident date, correspondence, denial letters, appeals, resolution
- **Child Development**: Milestone tracking (first steps, first words, behavioral changes, achievements, achievements, appointment dates)
- **Home Repairs/Disputes**: Document damage, contractor correspondence, warranty claims, repair attempts, resolution
- **Complaints/Resolutions**: Track customer service complaints, support tickets, escalations, followups

**Security & Privacy**:
- All logs behind login firewall (no public access)
- Admin controls visibility per user (like other CRUD tables)
- Timestamps cannot be edited after entry creation (audit trail integrity)
- Optional: Admin can view activity log of who accessed what log entries (for legal compliance)
- Entries can be marked "locked" (no editing/deletion) to preserve evidence

**Professional Appearance**:
- Clean, professional typography (suitable for legal documents)
- Proper date/time formatting (not "4 days ago"; use explicit dates)
- Print-optimized design (no rounded corners, consistent spacing, professional colors)
- Professional color scheme: Navy, Gray, White (minimal, trustworthy appearance)
- Emphasis on readability and chronological clarity

---

#### 3.5.3 CRUD Interface & UX

##### 3.5.3.A List View (Desktop & Mobile)

**Desktop List View**:
- Table format with sortable columns
- Each column header sortable (click = sort ascending; click again = descending)
- Search bar (searches across all fields; title-only or full-text configurable)
- Filter toolbar (optional; filters for text/dropdown/checkbox/date fields)
- Action buttons per row: View, Edit, Delete
- Bulk actions (multi-select checkboxes): Delete All, Export Selected, Move to Category (if applicable)
- Pagination (show 20/50/100 records per page)

**Mobile List View** (Card-Based):
- Each record = card (similar to task card design)
- Card shows: Primary field (e.g., recipe name, contact name, medication name)
- Secondary info: 2-3 key fields (e.g., dosage, phone, date)
- Action buttons on card: tap card for detail, three-dot menu for edit/delete
- Swipe left on card: Delete (with confirmation)
- Swipe right on card: Favorite/Star (optional)
- Search bar at top (same as desktop)
- Filter buttons (tap to open filter sidebar)

##### 3.5.3.B Detail View (Full Record)
- All fields displayed in read-mode by default
- Edit button (pencil icon) converts detail view to edit form
- Prev/Next buttons (navigate between records)
- Delete button (with confirmation modal)
- Print button (if applicable; formats for printing)
- Back to list button

##### 3.5.3.C Create/Edit Form
- Dynamic form generated based on table structure
- Fields displayed in order defined by creator
- Form validation (required fields marked with *)
- Error messages (inline; e.g., "Phone must be valid format")
- Save button (saves + returns to list)
- Save & New button (saves + resets form for next record)
- Cancel button (returns without saving)
- Autosave (optional; every 30 seconds, save draft)

##### 3.5.3.D Sorting & Filtering

**Sortable Fields**:
- All numeric fields (numbers, currency, date, time)
- Text fields (alphabetical)
- Date/Time fields (chronological)

**Filterable Fields**:
- Text (contains, exact match)
- Email (contains)
- Phone (contains)
- **URL** (contains)
- Number (greater than, less than, equals, range)
- Currency (greater than, less than, equals, range)
- Date (before, after, equals, date range)
- Checkbox (true/false)
- Dropdown (specific value or none)
- File/Image (has file, no file)

**Multi-Filter Support**:
- User can combine filters (e.g., "Category = Breakfast AND Prep Time < 30 min")
- Filter results update instantly (no page refresh)
- Clear all filters button

##### 3.5.3.E Bulk Actions

**Multi-Select**:
- Checkbox in table header "Select All" (selects all records on current page or all records if small table)
- Checkboxes in each row (individual selection)
- Selection counter badge: "5 records selected"

**Bulk Action Buttons** (appear when ≥1 record selected):
- **Delete All**: Confirmation modal "Delete [X] records permanently?"
- **Export Selected**: Download as CSV (structured data export)
- **Export Selected as PDF**: Print-friendly format (if table supports)

**Bulk Safety**:
- Destructive actions (Delete All) require confirmation modal
- Export is non-destructive; no confirmation needed

---

#### 3.5.4 File Management Integration

##### 3.5.4.A File Attachment Storage
- **Quota per User**: 500MB total across all file attachments
- **Per-File Size Limit**: 50MB maximum per file
- **Storage Location**: `/private/uploads/[user_id]/crud_[table_name]/`
- **Auto-Organization**: Files automatically organized by CRUD table + task (if linked)
  - Example: `/private/uploads/123/crud_medications/` or `/private/uploads/123/task_456/`

##### 3.5.4.B File Type Whitelist
- **Allowed Formats**: PDF, JPG, PNG, GIF, DOCX, XLSX, PPTX, TXT, CSV, MP3, WAV
- **Blocked Formats**: EXE, BAT, COM, CMD, SCR, VBS, JS (security: prevent malware)
- **On Upload**: System validates MIME type (prevents masqueraded files)
- **Upload Error**: If file exceeds limit/wrong type, show friendly error ("File too large; max 50MB" or "File type not supported. Allowed: PDF, images, Office docs")

##### 3.5.4.C File Operations
- **Upload**: Drag-drop or file picker input; progress bar shown
- **Download**: Click filename in CRUD record; opens/downloads file
- **Delete**: Delete button on file field; confirmation modal
- **Replace**: Upload new file (overwrites old; old file deleted)
- **Preview**: For images, show thumbnail in CRUD form; click to enlarge

---

#### 3.5.5 CRUD & Task Integration (Automation)

##### 3.5.5.A Auto-Task Creation from CRUD Records

**Medication Refill Task** (Automatic):
- **Trigger**: Medication table, 7 days before "Refill Due Date"
- **Task Created**:
  - Title: "[Medication Name] - Request Refill"
  - Priority: High
  - Category: Health
  - Due Date: 7 days before refill date (when refill is REQUESTED)
  - Description: "Refill for [Dosage], [Frequency]. Prescriber: [Name], Pharmacy: [Pharmacy Name] [Phone]"
  - Assigned To: User assigned to medication
  - Status: Active
- **Link**: Task links back to CRUD record (user can click "View Medication" in task detail)

**Bill Payment Task** (Automatic):
- **Trigger**: Bill table, configurable days before "Due Date" (default 3 days)
- **Task Created**:
  - Title: "[Vendor] Bill - $XXX due [Date]"
  - Priority: High
  - Category: Finance
  - Due Date: N days before bill due date
  - Description: "Amount: $[Amount]. Account: [Account Used]. Notes: [Notes]"
  - Assigned To: User assigned to bill
  - Status: Active
- **On Task Completion**: Bill CRUD record auto-updates Status to "Paid"
- **Link**: Task links back to CRUD record

**Custom Recurring CRUD** (Optional, per table):
- Admin can configure: "Create task every [interval] if [condition]"
- Example: "If Hobby Collection item 'Condition = Needs Restoration', create task 'Restore [Item Name]'"
- Optional feature; only for power users

##### 3.5.5.B Task Completion → CRUD Status Update
- When task linked to CRUD record is marked complete:
  - **Bill Tracker**: Automatically update Status = Paid
  - **Other tables**: Optional (user/admin decides if applies)
- Reverse: If user marks CRUD Status manually (e.g., checks "Paid" on emergency), linked task auto-marks complete (if still active)

---

#### 3.5.6 CRUD & Reminders Integration (Advanced)

**Medication Reminders** (Section 3.8 integration):
- System auto-creates reminders for each medication's scheduled times (AM/Midday/Afternoon/Evening/Bedtime)
- Daily reminder sent to user (email + SMS) at configured time
- Label: "Time for [Medication Name]!"
- User can snooze reminder (15 min / 1 hour) via notification

**Bill Reminders** (Section 3.8 integration):
- Reminder sent on auto-task creation date (N days before due date)
- Label: "[Vendor] Bill due [Date] - $[Amount]"
- User can mark paid directly from reminder (if UI supports)

---

#### 3.5.7 CRUD Table Creation Workflow

##### 3.5.7.A Admin Creates Shared Table (System Admin)
1. **Navigate**: Dashboard → Admin Panel → CRUD Tables → "Create New Table"
2. **Step 1 - Choose Type**:
   - Use template (Emergency Contacts, Medications, Bills, Recipes, Custom Templates)
   - Create blank table
3. **Step 2 - Configure**:
   - If template: Review pre-configured fields, optionally modify
   - If blank: Define table name, description, add fields one-by-one
4. **Step 3 - Permissions**:
   - "Who can see this table?" → Checkboxes for each user (multi-select)
   - "Can users edit?" → Yes / No
   - "Can users delete records?" → Yes / No (default No for shared tables)
5. **Step 4 - Review & Create**:
   - Preview table layout
   - Create button → Table created, appears in admin list
6. **Populate (Optional)**:
   - Admin can add initial records (e.g., existing medications)
   - Or users add records once they access table

##### 3.5.7.B User Creates Personal Table
1. **Navigate**: Dashboard → "Create a Resource Table" menu item (new dashboard menu button)
2. **Step 1 - Choose**:
   - Use template (Book Tracker, Contact List, Travel Planner, etc.)
   - Create blank table
3. **Step 2 - Configure**:
   - Define table name + description
   - Add fields (same as admin process)
4. **Step 3 - Privacy**:
   - "Keep private" (default; only creator sees) OR
   - "I'll ask admin to share" (notes for admin permission request)
5. **Create**:
   - Table created in user's personal space
   - Appears in local "My Tables" section on dashboard
   - Admin can see it exists but cannot edit by default (creator owns it)

---

#### 3.5.8 CRUD Accessibility & Tooltips

**Field Type Tooltips** (hover or help icon):
- **Text**: "Single-line text field. Examples: Name, title, short description"
- **Textarea**: "Multi-line text. Use for longer content like notes, instructions"
- **URL**: "Clickable hyperlink. Examples: Website, GitHub repo, article link"
- **Phone**: "Formatted phone number. Clickable to call on mobile"
- **Email**: "Email address. Clickable to send email"
- **Number**: "Numeric value. Examples: Age, count, quantity"
- **Currency**: "Money amount. Automatically formatted with $ symbol"
- **Date**: "Calendar date picker. Examples: Birthday, due date, event date"
- **Time**: "Time of day. Examples: Appointment time, medication time"
- **Checkbox**: "True/false toggle. Examples: Is active? Completed?"
- **Dropdown**: "Select one option from predefined list"
- **File**: "Single file attachment. Upload documents, images, PDFs"
- **Image**: "Photo/image field. Displays as thumbnail in list view"

**Form Field Tooltips** (per field):
- Help icon next to field label (? icon)
- Click shows tooltip: "Field purpose and examples"
- Auto-populated from "Help text" entered by creator

**Mobile Accessibility**:
- All text fields support standard mobile input (number keyboard for numbers, email keyboard for email, etc.)
- Dropdowns use native mobile select (easier than custom dropdowns)
- File upload supports camera access on mobile (for photo capture)

---

#### 3.5.9 Empty States & Onboarding

##### 3.5.9.A Pre-Populated Emergency Contacts
- **On System Setup**: Emergency Contacts CRUD table automatically created
- **Pre-Populated Fields** (soft defaults; user can modify):
  - Row 1: "Mom" (Relationship: Parent) — phone/email blank, user fills in
  - Row 2: "Dad" (Relationship: Parent) — phone/email blank
  - Row 3: "School Office" (Relationship: School) — phone pre-filled if available
- **Display**: Always visible in dashboard "Emergency Contacts" quick-link
- **Message**: "Is information up to date? Update if needed" (prompts user to review annually)

##### 3.5.9.B "Create a Resource Table" Dashboard Button
- **Location**: Dashboard, left sidebar or top menu → "Create a Resource Table"
- **UX**: Opens modal "What would you like to track?"
  - **Option A**: Use a template (dropdown: Book Tracker, Contact List, Travel Planner, etc.)
  - **Option B**: Create blank (table name input + "Create" button)
  - **Help text**: "Tables let you organize any type of information. Admin can share with others."
- **Post-Creation**: Table appears in "My Tables" (personal) or assigned tables (if admin)

##### 3.5.9.C CRUD List View Empty State
- **If no records**: Large icon (folder icon) + message
  - "No [Table Name] yet. Create your first [Record Type]."
  - Button: "+ Create [Record Type]"
- **Example**: "No medications yet. Create your first medication." (+ [Create Medication] button)

---

#### 3.5.10 CRUD Export & Data Portability

##### 3.5.10.A Export to CSV
- **Trigger**: Select records (or "select all") → Bulk action "Export as CSV"
- **File**: Downloads `[table_name]_[date].csv` (e.g., `medications_2026-04-02.csv`)
- **Format**: Standard CSV with headers (field names) + data rows
- **Use**: Data portability, backup, external analysis, share with family/doctor

##### 3.5.10.B Export to PDF (Recipe Tracker Specific)
- **Trigger**: Recipe card → "Print" or "Export as PDF"
- **Format**: 
  - Photo (if exists; full width)
  - Recipe name, prep time, cook time, servings
  - Ingredients list (formatted)
  - Instructions (numbered steps)
  - Notes section
  - Source URL (footer)
- **Output**: `[recipe_name]_[date].pdf` (downloadable)

##### 3.5.10.C Email Export (Recipe Tracker Specific)
- **Trigger**: Recipe card → "Email"
- **Dialog**: "Send to: [User's email] ▼" (dropdown of saved emails)
- **Format**: Email with recipe formatted nicely + PDF attachment
- **Subject**: "[Recipe Name] from ADHD Dashboard"
- **Body**: Formatted recipe text (not just link; full content included)

---

#### 3.5.11 CRUD Performance & Scalability

**Database Optimization**:
- Each CRUD table = separate database table (for flexibility)
- Indexes on frequently-filtered columns (date, status, assigned user)
- Pagination: Default 20 records per page (configurable)
- Search: Full-text search on text fields (if database supports; otherwise substring match)

**Data Limits**:
- Max 100 CRUD tables per system (per admin instance)
- Max 10,000 records per table (soft limit; admin warned if approaching)
- Max 500MB total file storage per user

**Backup & Recovery**:
- CRUD data included in regular database backups (Section 9)
- Admin can export all CRUD data as JSON (for migration/disaster recovery)

---

---

### 3.6 File Management System (User-Isolated File Storage)

#### 3.6.1 Purpose & Architecture

**Purpose**: Centralized file storage for user-uploaded documents, images, recordings, and attachments (linked to tasks, CRUD records, or stored for personal reference).

**Storage Architecture**:
- **Location**: `/public_html/uploads/[user_id]/` (accessible for download; within GitHub-pushed public folder)
- **Key Difference from `/private/`**: Files in public_html are directly downloadable/viewable via HTTP (necessary for browser file preview, email links, sharing)
- **Security**: 
  - Only authenticated users can access their own user_id folder
  - URL-style access requires valid session cookie: `https://adhd-dashboard.local/uploads/[user_id]/[file_path]` → rejected if user not logged in or wrong user_id
  - Admin can access any user's files (for troubleshooting/backup)
- **File Integrity**: Original filenames preserved (no renaming; versioning not supported—latest version only)

---

#### 3.6.2 File Organization Structure (Hybrid Auto + Custom)

##### 3.6.2.A Auto-Organized System Folders

System automatically creates structured folders on first file upload:

```
/public_html/uploads/[user_id]/
├── _tasks/                      (Auto-folder for task attachments)
│   ├── task_[123]/              (Per-task subfolder; auto-named by task ID)
│   │   ├── recipe_photo.jpg
│   │   └── invoice.pdf
│   └── task_[456]/
│
├── _crud/                       (Auto-folder for CRUD record attachments)
│   ├── medications/             (Per-CRUD-table subfolder)
│   │   ├── medication_1_receipt.pdf
│   │   └── medication_2_label.jpg
│   ├── recipes/
│   │   ├── pasta_recipe.jpg
│   │   └── sauce_notes.txt
│   └── [custom_crud_1]/
│
├── General/                     (Unattached files; files uploaded without task/CRUD link)
│   ├── family_photo.jpg
│   ├── tax_docs.pdf
│   └── voice_memo.mp3
│
└── [custom_folder_1]/           (User-created custom folders; max 10 allowed)
    ├── Travel_Planning/
    │   ├── hotel_confirmation.pdf
    │   └── flight_itinerary.pdf
    └── [custom_subfolder]/      (Nested custom folders allowed)
```

**Folder Behavior**:
- System folders (_tasks, _crud, General) created automatically on first upload
- User cannot delete/rename system folders (but can organize files within General)
- User can create up to 10 custom folders (outside system folders)
- Custom folders can have nested subfolders (unlimited depth)

##### 3.6.2.B Quota Management

**User Storage Limits** (Section 3.5 reference):
- **Total per user**: 500MB
- **Per file**: 50MB maximum
- **Enforcement**: Real-time checks on upload; error if exceeds limit

**Quota Display** (User Dashboard):
- Storage widget: "Using 350MB of 500MB (70%)"
- Breakdown by category:
  - Tasks: 150MB
  - CRUD: 200MB
  - Personal (General): 0MB
- Click breakdown to see which folders/files consume most space

**Quota Warnings**:
- **80-100%**: Warning message "You're almost at storage limit. Consider deleting old files or download for backup."
- **100%**: Error message "Storage full. Upload failed. Delete files or request quota increase from admin."

**Admin Storage Dashboard**:
- See all users' storage usage (sortable by consumption)
- Can increase any user's quota (temporary or permanent)
- Can force-delete files older than X days (with user notification)
- Can see what file types consume most space (analytics)

---

#### 3.6.3 File Upload Interface

##### 3.6.3.A Desktop Upload

**Upload Methods** (Pick One or Both):
1. **Drag & Drop Area**:
   - Large drop zone ("Drag files here to upload")
   - Visual feedback (highlight drop zone when dragging file over it)
   - Display dragged file's name/size before releasing
2. **Browse Button** (File Picker):
   - "+ Upload Files" button
   - Opens native file picker (single or multiple files)
   - Standard file picker dialog

**Multi-File Upload** (Batch):
- User can select 5+ files at once (Ctrl+Click or Shift+Click)
- Uploads simultaneously (3-5 parallel uploads for speed)
- Each file shows individual progress bar
- Overall counter: "3 of 5 files uploaded"

**Upload Progress**:
- Progress bar per file (0-100%)
- File name displayed above progress bar
- Estimated time remaining (e.g., "~2 mins remaining")
- Upload speed shown (e.g., "1.5 MB/s")
- Cancel button (stop current upload mid-flight)

**Destination Folder Selection**:
- Before upload starts, user selects target folder (dropdown)
- Options: System folders (Tasks, CRUD tables if applicable) or custom folder
- If uploading from task detail page, defaults to that task's folder

##### 3.6.3.B Mobile Upload

**Upload Methods**:
1. **Camera Access**:
   - "📷 Take Photo" button
   - Opens device camera directly (no file picker needed)
   - Photo captured, preview shown, confirm upload
   - Auto-saves to appropriate folder (if task detail page: task folder; else: General)
2. **File Picker**:
   - "📁 Choose File" button
   - Opens device file picker (Photos, Files app, etc.)
   - User selects file + uploads
3. **Paste from Clipboard** (Advanced):
   - Long-press upload area → "Paste" option
   - If clipboard contains image/file, pastes directly
   - Useful for screenshots

**Mobile Progress**:
- Same progress bar as desktop
- Estimated time remaining
- Cancel button
- On completion: "File uploaded! Tap to view" confirmation

**Mobile Folder Selection**:
- If context clear (task detail, CRUD record), auto-selects folder
- Else: Quick prompt "Upload to: [General] ▼" (dropdown to select)

---

#### 3.6.4 File Operations (Right-Click Context Menu)

##### 3.6.4.A File Context Menu Actions

**Desktop** (Right-click on file):
```
├── 👁 Preview          → Opens file in lightbox/viewer
├── 📥 Download         → Download file to device
├── ✏ Rename            → Edit filename (preserves extension)
├── 🔗 Copy Link        → Copy shareable link to clipboard
├── ➡ Move              → "Move to folder:" dialog → select destination
├── 📋 Copy             → Duplicate file in same folder
├── 🔗 View Linked      → If attached to task/CRUD, show links
│                          [Attached to: "Pay Electric Bill" task]
│                          [Attached to: "Medications" CRUD record]
├── ⚠ Delete            → "Delete permanently? This cannot be undone." → Yes/No
└── ℹ Info              → Show file size, date modified, full path
```

**Mobile** (Long-press on file):
```
├── 👁 View
├── 📥 Download
├── ✏ Rename
├── ➡ Move
├── 🔗 Copy Link
├── ⚠ Delete
└── ℹ Info
```

##### 3.6.4.B File Operations Details

**Preview**:
- Opens in-browser modal/lightbox (non-destructive view)
- Close button (X) to dismiss
- For images with high res, zoom in/out buttons

**Download**:
- File downloaded to device's default download folder
- Preserves original filename + extension

**Rename**:
- Modal dialog: "New filename: [current_name] [Input field]"
- Preserves file extension (auto-appended if user doesn't include)
- Validation: No special characters (/ \ : * ? " < > |)
- Error if filename already exists in folder

**Copy Link**:
- If file is in folder marked as "shareable by admin", generates shareable link
- Format: `https://adhd-dashboard.local/file/[file_id]/[filename]`
- Link valid for X days (configurable by admin)

**Move**:
- Dialog: "Move to folder:" (dropdown of all accessible folders)
- Can move to custom folder, between task folders, etc.
- Confirmation: "Moving [filename] to [destination]"

**Copy** (Duplicate):
- Creates copy in same folder
- Filename: "[original]_copy.[ext]"
- If filename exists, tries "[original]_copy_2.[ext]", etc.

**View Linked**:
- If file attached to task/CRUD, shows list:
  - "Attached to: 'Pay Electric Bill' (task)" → Click to open task
  - "Attached to: 'Medications' (CRUD record)" → Click to open record

**Delete** (Permanent/No Trash):
- Confirmation modal: "Delete '[filename]' permanently? This cannot be undone."
- No recovery possible (no trash/recycle bin)
- **Alternative**: Suggest "Download first if you want to keep a backup"
- Post-delete: File removed from disk; freed storage shows in quota

**Info**:
- File size (formatted: "2.5 MB")
- Date modified (human-readable: "Apr 2, 2026, 3:45 PM")
- File type (extension: ".pdf")
- Full path in storage
- MD5 checksum (optional; for verification)

---

#### 3.6.5 Batch File Operations

##### 3.6.5.A Multi-Select Interface

**File List View**:
- Checkbox in top-left of each file card/row
- "Select All" checkbox in header (selects all visible files in current folder)
- Selection counter badge: "3 files selected" with "Clear" link

**Batch Action Buttons** (appear when ≥1 file selected):
- **Download as ZIP**
- **Move All** → "Move to folder:" dialog
- **Delete All** → Confirmation: "Delete [X] files permanently?"

##### 3.6.5.B Batch Download as ZIP

- Action: User selects multiple files → "Download as ZIP" button
- System creates ZIP file (in-memory, not stored permanently)
- ZIP filename: `[user_name]_files_[date].zip` (e.g., `john_files_2026-04-02.zip`)
- Compression: Standard ZIP format
- Download starts automatically (prompts browser download)
- Users can download entire folder as ZIP via folder context menu

##### 3.6.5.C Batch Move All

- Dialog: "Move [X] files to folder:" (dropdown of destinations)
- All selected files moved atomically (all succeed or all fail)
- Confirmation: "[X] files moved to [destination]"

##### 3.6.5.D Batch Delete All

- Confirmation modal: "Delete [X] files permanently? This cannot be undone."
- Users must confirm action (prevents accidental mass deletion)
- Post-delete: "[X] files deleted. [Y]MB storage freed"

---

#### 3.6.6 File Preview & Viewing

##### 3.6.6.A Image Preview (JPG, PNG, GIF)

**List View**:
- Thumbnail shown (200x200px max, lazy-loaded on scroll)
- Click thumbnail → opens lightbox

**Lightbox View**:
- Full-size image displayed (respects max 1000x1000px; scales down if larger)
- Image name + size shown below
- Navigation: Previous/Next buttons (cycle through folder's images)
- Zoom controls: + / - buttons (max 200% zoom)
- Download button (export original file)
- Close button (X)

**Mobile Preview**:
- Thumbnail grid (2 columns; auto-size)
- Tap thumbnail → Full-screen lightbox (swipe left/right to navigate)

##### 3.6.6.B PDF Preview (Embedded Viewer)

**List View**:
- Filename + size shown
- PDF icon (indicates file type)

**PDF Viewer**:
- Embedded via PDF.js or similar
- Page navigation (Previous / [Page X of Y] / Next)
- Zoom controls (Fit Page, Fit Width, +/-, 100%)
- Full-screen button
- Download original PDF button
- Print button (browser print, user can save as PDF again)

**Mobile PDF**:
- Auto-fits screen width (mobile-friendly)
- Single-page view (vertical scroll for long documents)
- Tap page number to jump to specific page

##### 3.6.6.C Text File Preview (TXT, CSV)

**List View**:
- Filename + size
- Preview first 100 characters in subtitle (e.g., "Notes on medication refills...")

**Text Viewer**:
- Content displayed inline (max 5000 lines; truncate with "... file too large, download to view all")
- Word-wrap enabled
- Font: Monospace (easier for technical content)
- Line numbers (optional; toggle)
- Download button

##### 3.6.6.D Audio Player (MP3, WAV)

**List View**:
- Audio icon + filename + duration (e.g., "voice_memo.mp3 (2:34)")

**Audio Player**:
- Native HTML5 audio player embedded
- Controls: Play, Pause, Progress bar (show current time / total duration), Volume slider
- Playback speed buttons (0.75x, 1.0x, 1.25x, 1.5x) — useful for reviewing notes
- Download button
- Mobile: Same player (tap to expand if needed)

---

#### 3.6.7 File Search & Filtering

##### 3.6.7.A Search Interface

**Search Bar** (Top of file manager):
- Text input: "Search files..." (placeholder)
- Scope options (radio buttons or dropdown):
  - Search current folder only
  - Search all folders (recursive)
- Search triggers on: Enter key or as-you-type (debounced 300ms)

##### 3.6.7.B Search Criteria

**Searchable Fields**:
- Filename (contains; case-insensitive)
- File type/extension (equal; e.g., ".pdf" returns all PDFs)
- Date range (modified within date range)

**Search Examples**:
- "recipe" → Returns all files with "recipe" in name
- "filetype:.pdf" → Returns all PDFs (advanced syntax)
- "date:last-week" → Returns files modified in last 7 days
- "size:>5mb" → Returns files larger than 5MB

**Search Results**:
- Display as list (inline in file manager; highlight matching files)
- Breakdown: "[X] results found in [Y] files"
- Click result → Jumps to file's location (reveals folder/file)

##### 3.6.7.C Filter Options

**Filter Toolbar** (Optional; toggles on/off):
- **File Type**: Checkboxes (Images, Documents, Audio, Text, Other)
- **Date Range**: Preset or date picker (Last Week, Last Month, Custom)
- **File Size**: Slider (1MB to 50MB) or presets (< 1MB, 1-10MB, > 10MB)
- **Apply Filters** button

**Filter Behavior**:
- Combines with search (search results + filter = narrowed set)
- Clear filters button
- Filters persist during session (reset on page refresh)

##### 3.6.7.D Sorting Options

**Sort Dropdown** (Default: Name):
- **Name** (A-Z, ascending)
- **Name** (Z-A, descending)
- **Date Modified** (Newest first)
- **Date Modified** (Oldest first)
- **File Size** (Largest first)
- **File Size** (Smallest first)
- **File Type** (By extension)

---

#### 3.6.8 File Sharing (Admin-Controlled, Folder-Level)

##### 3.6.8.A Admin Share Configuration

**Admin Dashboard → File Manager → Sharing**:
1. Select folder (dropdown): General, Tasks, CRUD tables, or custom
2. "Share with users:" (checkboxes or multi-select)
   - Select which users can access
   - Example: Check "Sarah", "Tommy" to share folder
3. Permission level (radio buttons):
   - **Read-Only**: Users can view, download, preview; cannot edit/delete/rename
   - **Read-Write**: Users can view, download, upload, rename, move, delete
4. Optional: Time-limited sharing (advanced):
   - "Share until: [Date]" (optional; default permanent)
   - Revoke button (immediate)
5. Apply

##### 3.6.8.B Shared Folder Display (User's File Manager)

**Shared Folders Section**:
- Separate section in file explorer: "Shared with Me" (collapsed by default)
- Each shared folder shows:
  - Folder name + "(Shared by Admin)" badge
  - Permission level indicator: 🔒 (read-only) or 🔓 (read-write)
  - Last modified date
  - File count preview (e.g., "12 files")

**Accessing Shared Files**:
- Click to expand → see files inside
- Right-click on file (respects permissions):
  - Read-only: Preview, Download, View Linked only
  - Read-write: All operations (Preview, Download, Rename, Move, Delete, Copy, View Linked)

##### 3.6.8.C Sharing Notifications & Revocation

**On Sharing/Revoke**:
- User receives email: "[Admin] shared 'Family Recipes' folder with read-only access"
- On revoke: "[Admin] revoked access to 'Family Recipes' folder"

**Admin Revoke**:
- Admin can revoke anytime
- Revoked user loses access immediately (cannot access via direct URL either)
- Admin can see share history (who shared what, when, duration)

---

#### 3.6.9 File Deletion & Storage Recovery

##### 3.6.9.A Permanent Deletion (No Trash/Recycle Bin)

**Delete Confirmation**:
- Modal: "Delete '[filename]' permanently? This cannot be undone."
- Suggest alternative: "Or download first as backup?"
- Buttons: [Download] [Delete] [Cancel]

**Post-Delete**:
- File removed from disk immediately
- Storage quota updated (freed space reflected in dashboard)
- No recovery option

**Use Case**: ADHD users often prefer finality over clutter; trash bins create "to-delete" backlog.

##### 3.6.9.B Download as Backup (Alternative to Delete)

**Before Delete** (Suggested):
- User can choose "Download" to save copy to their device
- Then delete from dashboard (space freed, backup on device)
- Workflow: Download → Delete → Peace of mind

**Bulk Backup**:
- Select multiple files → "Download as ZIP" (creates permanent backup on device)
- Then delete selected files (storage freed)

**Admin Cleanup**:
- Admin can force-delete files older than X days (with user notification)
- Notification email: "We're freeing up your storage. Files older than [date] will be deleted. Download them first if needed."
- 7-day warning before auto-deletion
- Admin provides optional download link (one-time ZIP of old files)

---

#### 3.6.10 Mobile File Manager

##### 3.6.10.A Mobile Navigation

**Bottom Navigation** (or Sidebar):
- Dashboard
- **Files** (icon: 📁, active section)
- Crud
- Tasks
- Profile
- [etc.]

**File Manager Mobile Layout**:
- Status bar: "Using 350MB of 500MB"
- Search bar (sticky at top)
- Current location (breadcrumb): `Files > General` or `Files > _tasks > task_123`
- Back button (navigate to parent folder)

##### 3.6.10.B Mobile File Display (Thumbnail Grid)

**Grid Layout**:
- 2 columns on mobile (one file per row-item taking 50% width)
- File thumbnail (if image: 150x150px; else: file type icon)
- Filename + size (e.g., "recipe_photo.jpg (1.2MB)")
- Last modified date (e.g., "Apr 2, 3:45 PM")

**File Card Actions**:
- Tap file → Preview in lightbox/viewer
- Long-press file → Context menu (see Q3 operations above)

##### 3.6.10.C Mobile Folder Navigation

**Breadcrumb Trail** (horizontal, scrollable):
- `Files > General > Travel` (clickable segments)
- Allows quick jump up levels

**Back Button**:
- Arrow ← back button in header
- Returns to parent folder

**Create Folder**:
- "+" button in header → "New folder" dialog → Enter name → Create

---

#### 3.6.11 File Integration with Tasks & CRUD

##### 3.6.11.A Auto-Folder Creation on Attachment

**On Task Attachment**:
- User adds file to task detail page
- Auto-creates folder: `/uploads/[user_id]/_tasks/task_[task_id]/`
- File stored there
- Task shows: "Attachments: [filename] (linked)"

**On CRUD Attachment**:
- User adds file to CRUD record
- Auto-creates folder: `/uploads/[user_id]/_crud/[table_name]/`
- File stored there
- CRUD record shows: "Attachments: [filename] (linked)"

##### 3.6.11.B File Detail View - Linked Information

**In File Manager Detail View**:
- Shows: "Linked to: [Task Name or CRUD Record Name]" with link
- Click link → Opens task or CRUD record in new tab
- Helps user understand file's purpose

##### 3.6.11.C Task/CRUD Deletion & File Handling

**On Task/CRUD Deletion**:
- Dialog: "Delete associated files too, or keep in file system?"
- Options:
  - [Delete Files] - Files removed from disk (space freed)
  - [Keep Files] - Files stay in file manager (linked reference removed)
  - [Cancel]
- Useful for ADHD users who want to delete task but preserve file reference

---

#### 3.6.12 File Type Support & Security

##### 3.6.12.A Supported File Types (Whitelist)

**Upload Allowed**:
- Images: JPG, JPEG, PNG, GIF, WebP
- Documents: PDF, DOCX, XLSX, PPTX, TXT, CSV
- Audio: MP3, WAV, M4A, OGG
- Archives: ZIP (for convenience; can upload ZIP to extract on device)
- Blocked: EXE, BAT, COM, CMD, SCR, VBS, JS (security risks)

**Upload Validation**:
- Check file extension
- Validate MIME type (prevent spoofed files; e.g., EXE renamed to .jpg)
- On violation: Error message "File type not supported. Allowed: [list]"

##### 3.6.12.B Security Scanning

**On Upload** (Optional; Phase 2 Enhancement):
- Run basic virus scan (if ClamAV available)
- Quarantine suspicious files (inform user)
- Document scan result (log)

**File Access Security**:
- URL access authenticated (session cookie required)
- Rate-limiting on repeated download attempts (prevent scraping)
- Audit log (track who downloaded which file, when)

---

#### 3.6.13 File Manager Performance

**Optimization**:
- Lazy-load thumbnails (only visible files in viewport loaded)
- Lazy-load file list (pagination: 20 files per page by default)
- Cache recently-viewed files (browser cache)
- Compress images on upload (optional; Phase 2)

**Mobile Optimization**:
- Lightweight thumbnails (150x150px max; optimized for network)
- Minimize API calls (batch operations where possible)
- Progressive loading (show skeleton placeholders while loading)

---

#### 3.6.14 Empty States & Onboarding

**Empty File Manager** (New user):
- Large icon (📁) + message: "No files yet. Upload to get started."
- Quick tips:
  - "Drag & drop files to upload"
  - "Attach files to tasks and CRUD records"
  - "Organize in custom folders (max 10)"
- Upload button

**Low Storage Warning** (at 80%):
- Prominent warning: "⚠️ You're at 80% storage. Clean up old files or download for backup."
- Suggestion: "Download as ZIP and delete old files"

---

---

### 3.7 Projects & Ticketing System (Project Portal with Resource Hub)

#### 3.7.1 Purpose & Real-World Use Cases

**Purpose**: Transform complex multi-step initiatives into organized, resource-rich centers where users access everything needed to complete a project. Think of projects as organizational containers, tickets as individual sub-items/missions requiring planning & resource gathering before execution.

**Real-World Examples**:
- **Project: "Veterinarian"** → Tickets: "Rover" (dog 1), "Fluffy" (dog 2)
  - Each ticket: Medical history, medication records, immunization forms, vet contact, appointment notes
- **Project: "Annual Doctor Visits"** → Tickets: "Pediatrician", "Dentist", "Eye Doctor"
  - Each ticket: Insurance card, medical history, provider contact, appointment prep checklist, forms
- **Project: "Home Repair"** → Tickets: "Roof Inspection", "HVAC Service"
  - Each ticket: Photos, contractor info, warranty docs, task checklist, timeline notes

**Key Difference from Tasks**:
- **Tasks**: Individual action items (1-3-5 daily focus; typically <1 hour execution)
- **Tickets**: Multi-step containers (days/weeks duration; planning + resource gathering + execution)
- **Relationship**: Tickets CONTAIN tasks (many-to-many: ticket can link to multiple tasks; task links to one ticket)

---

#### 3.7.2 Project & Ticket Creation Workflow

##### 3.7.2.A Project Creation

**Access**: Dashboard → "Projects" navigation item → "New Project" button (or "+")

**Creation Modal** (Single-step):
- **Project name** (required text; 255 chars)
  - Placeholder: "e.g., Veterinarian, Annual Doctor Visits"
- **Description** (optional textarea; 500 chars)
  - Placeholder: "Purpose, context, or notes for this project"
- **Buttons**: [Create] [Cancel]

**Post-Creation**:
- User lands on project view (empty state)
- Shows: "No tickets yet. Create your first ticket to get started."
- Button: "+ Add Ticket"
- Empty ticket list with tab headers (Review Due Soon, Open, Paused, Closed)

**Admin Project Creation**:
- Admin can pre-create shared projects (visible to assigned users)
- Admin Panel → Projects → Create → Same form + "Share with users:" checkboxes
- Shared projects appear in all assigned users' Projects dashboard

##### 3.7.2.B Ticket Creation

**Access**: 
- Within project: "+ Add Ticket" button
- OR Dashboard Projects tab → "New Ticket" button (then select project in modal)

**Creation Modal** (Multi-step or Single-step Form):

**Option A - Multi-Step** (Recommended for UX clarity):
1. **Step 1 - Project & Title**:
   - Project dropdown (if not in project already; pre-selected if clicked from project view)
   - Ticket title (required; 255 chars; e.g., "Rover's Annual Checkup")
   - [Next]
2. **Step 2 - Details**:
   - Status (radio buttons; default Open)
   - Priority (radio buttons; default Medium)
   - Review date (optional date picker)
   - [Next]
3. **Step 3 - Description**:
   - Description textarea (optional; 1000 chars; markdown support)
   - [Create]

**Post-Creation**:
- Ticket created, user lands on ticket detail view (empty resources)
- Shows: "No resources added yet. Add files, URLs, contacts, or notes."
- Button: "+ Add Resource"

**Quick Create** (Power users):
- Alt: Single-form (all fields visible) with [Create] button (skip steps)

---

#### 3.7.3 Ticket Status Workflow & Transitions

##### 3.7.3.A Status States

**Five Status States** (Independent of linked task completion):

1. **New** (Light Gray background)
   - Initial state on creation
   - Auto-transitions to Open on first user interaction

2. **Open** (Blue badge)
   - Active working ticket
   - User is gathering resources, planning, or executing

3. **Paused** (Orange badge)
   - Temporarily on hold (waiting for information, blocked, rescheduled)
   - Can resume anytime

4. **Closed** (Green badge with checkmark)
   - Completed
   - Can be reopened or archived
   - Triggers recurring ticket clone (if applicable)

5. **Archived** (Gray, faded)
   - Soft-deleted; hidden from normal views
   - Searchable in "Archived" section; recoverable

##### 3.7.3.B Status Transitions

**Allowed Transitions** (Display as radio buttons on ticket detail):
```
New ──→ Open
        ├─→ Paused  (manual toggle)
        ├─→ Closed  (confirmation: "Close ticket?")
        └─→ Reopen  (if closed/paused)

Paused ─→ Open   (toggle)
        ├─→ Closed (confirmation)
        └─→ Reopen

Closed ─→ Reopen (button; no confirmation)
        └─→ Archive (soft-delete; move to archive section)
```

**Transition Behavior**:
- **New → Open**: Auto on first ticket opening OR manual button "Start working"
- **Open ↔ Paused**: Toggle (instant; no confirmation)
- **Open/Paused → Closed**: Confirmation modal: "Close ticket '[Name]'? You can reopen anytime." [Close] [Cancel]
- **Closed → Reopen**: Button: "Reopen this ticket" (instant; no confirmation)
- **Any → Archive**: Button on closed ticket: "Archive this ticket" (instant)

**Status Change Notifications**:
- User sees toast: "Ticket status changed to [New Status]"
- Activity log records action: "[User] changed status to [Status]" + timestamp

**Independent of Tasks**: Closing ticket does NOT auto-close linked tasks; user manually completes tasks separately.

---

#### 3.7.4 Ticket Resource Hub (Multi-Type Attachment System)

##### 3.7.4.A Resource Types & Organization

**Resource Hub Layout** (Left column on desktop; stacked on mobile):

**Pinned Resources** (Always visible; max 5):
- User selects favorite/critical resources to pin
- Appear first in resource hub above unpinned
- Pin icon: Click to toggle pin status
- Reorder pinned (drag-to-reorder)

---

**Section 2: Files**
- Upload multiple files (drag-drop + browse)
- Display: Thumbnail (if image) + filename + size + actions (Preview, Download, Delete)
- Can delete individual files
- Right-click context menu on each file (Preview, Download, Delete, Move to [folder])

---

**Section 3: External URLs**
- "+ Add URL" button → Modal:
  - URL (required; https://......)
  - Display name (optional; default: domain name)
  - Category (optional dropdown: Reference, Provider, Form, Other)
- Display: Clickable link (blue underline) + category badge
- Edit: Double-click to edit
- Delete: X button

---

**Section 4: CRUD Records** (Embedded live data)
- "+ Link CRUD" button → Modal:
  - Select CRUD table (dropdown: Medications, Bill Tracker, Contacts, Recipes, or custom)
  - Select specific record (dropdown; shows records from selected table)
- Display: Record name + "View in CRUD" link + key fields (read-only preview)
- Dynamic: If CRUD record updated, changes reflect automatically in ticket
- Unlink: X button to remove CRUD link

---

**Section 5: Contacts** (Quick access to phone/email)
- "+ Add Contact" button → Modal:
  - Select from Contacts CRUD (if exists) OR manual entry:
    - Name (required)
    - Phone (optional; clickable on mobile)
    - Email (optional; clickable)
    - Relationship (optional dropdown: Doctor, Provider, Pharmacy, etc.)
- Display: Name + Phone (clickable 📞) + Email (clickable ✉️) + Relationship badge
- Useful for quick referral during execution

---

**Section 6: Notes** (Rich text field)
- Large textarea (rich text editor OR markdown support)
- Content auto-saves every 30 seconds (or manual save button)
- Edit inline (or click to expand editor)
- Useful for instructions, tips, reminders

---

##### 3.7.4.B Resource Management Actions

**Drag-to-Reorder** (Within each resource type):
- Click-drag resource card → reorder
- Resources stay in assigned sections (can't move files to URLs section, etc.)

**Pin Resource** (Mark favorite):
- Star icon (☆ = unpinned, ★ = pinned) on each resource
- Click to toggle
- Pinned resources float to top of hub (within "Pinned" section)
- Max 5 pinned (enforced; error if try to pin 6th)

**Archive Resource** (Hide, not delete):
- Alternative to delete (Phase 2): Archive button
- Archived resources move to "Archive" section (collapsed, expandable)
- Can restore anytime

**Print All Resources** (Generate PDF):
- Button: "Print Resources"
- Generates PDF with:
  - Ticket title + project name + status
  - All resources formatted nicely (files as thumbnails/links, URLs clickable, CRUD data in table, Contacts formatted, Notes as text)
  - Useful for taking to appointments

---

#### 3.7.5 Task Linking to Tickets

##### 3.7.5.A Bidirectional Linking

**Task → Ticket**:
- When creating/editing task: Optional dropdown "Assign to ticket:" [Ticket name ▼] (leave blank if not part of ticket)
- Task detail shows: "Linked to ticket: '[Ticket Name]'" (blue link; click to open ticket)
- Status: One task can link to ONE ticket only

**Ticket → Tasks**:
- Ticket shows: "Linked tasks: [X] tasks" (card display or expandable section)
- Click to expand inline list of linked tasks (task title, status, due date)
- OR link button: "+ Link Task" → Modal (select existing task or create new)
- Unlink: X button removes link (task still exists; just delinked)

**Task Completion ≠ Ticket Close**:
- Completing all linked tasks does NOT auto-close ticket
- Ticket shows soft notification: "All [X] linked tasks completed!" (encouragement)
- User must manually close ticket when ready (via status radio buttons)

---

#### 3.7.6 Recurring Tickets (Auto-Clone on Closure)

##### 3.7.6.A Recurring Setup

**On Ticket Detail**:
- Checkbox: "This ticket repeats:" (if checked):
  - Frequency dropdown: Weekly / Monthly / Quarterly / Annually / Custom
  - Additional details: "Every [Monday/15th/1st of month]"
  - Start date (optional; default today)
  - End date (optional; leave blank for indefinite)

**Database Storage**:
- `recurrence_rule` field (stores recurrence pattern)
- `recurrence_end_date` (nullable)

##### 3.7.6.B Clone Behavior (On Ticket Closure)

**Trigger**: When user closes ticket (status → Closed)

**System checks**: Does this ticket have recurrence_rule set?
- **If YES**: Auto-generate next instance
- **If NO**: Just close ticket normally

**Next Instance Creation**:
- Calculate next due date based on recurrence rule
- Create new ticket with:
  - **Same fields**:
    - Title (e.g., "Rover's Annual Checkup" → "Rover's Annual Checkup" [next year])
    - Description (copied)
    - Priority (copied)
    - Category (copied)
    - Resources (FILES/URLs/CRUD links/Contacts copied)
  - **Reset fields**:
    - Status: Open
    - Review date: Calculated based on recurrence (e.g., annual → next year same date)
    - Linked tasks: None (user re-links if needed)
    - Notes/Comments: None (fresh start)
  - **Linked**: Shows "Cloned from: '[Previous Ticket]'" link (for reference)

**User Notification**:
- Toast: "[Ticket Name] is recycled for next [month/year/quarter]. New ticket created."
- Email notification: Same message

**Example Workflow**:
1. Ticket "Annual Dental" (Pediatrician) closes Jan 15, 2026
2. Recurrence: Annually
3. System auto-creates new ticket "Annual Dental (Pediatrician)" with review date Jan 15, 2027
4. User gets notification; new ticket appears in Projects dashboard

##### 3.7.6.C Pause/Stop Recurrence

- Edit ticket → Uncheck "This ticket repeats:" (stops future clones)
- Already-closed tickets stay closed (no retroactive changes)

---

#### 3.7.7 Ticket Dashboard (Tabbed Queue System)

##### 3.7.7.A Tab Structure

**Four main tabs** (left to right):
1. **Review Due Soon** (within 7 days of review date; urgent focus)
2. **Open** (all Open tickets; most common workflow)
3. **Paused** (temporarily on hold; easy to resume)
4. **Closed / Archive** (completed; reference/archive)

**Tab Badges**: Show count (e.g., "Review Due Soon (3)", "Open (5)", "Paused (0)", "Closed (1)")

##### 3.7.7.B Card Layout (Per Ticket)

**Card Information** (Desktop):
```
┌──────────────────────────────────────────────────────┐
│ Project Name (small, light gray)                      │
│                                                        │
│ ⬤ Ticket Title (large, bold)    [Status Badge]      │
│                                                        │
│ Priority: ● Urgent(Yellow)  │  📝 3 tasks  │ Apr 2  │
│ Review: Apr 8 (if set)                               │
│                                                        │
│ "Rover's annual checkup with Dr. Smith" (preview)    │
│                                                        │
│ [View] [Edit] [✓ Complete] [Delete]                 │
└──────────────────────────────────────────────────────┘
```

**Card Elements** (in order):
1. **Project name** (small, light gray; top-left)
2. **Ticket title** (large, bold; clickable; opens detail)
3. **Status badge** (top-right; colored pill):
   - "New" (Light gray background, dark text)
   - "Open" (Blue background, white text)
   - "Paused" (Orange background, white text)
   - "Closed" (Green background with ✓, white text)
4. **Priority indicator** (small dot or icon; ADHD-friendly colors):
   - **Urgent** → **Bright Yellow (#FFB300)**
   - **High** → **Bright Orange (#FF9F43)**
   - **Medium** → **Calming Pink (#B8E0D2)**
   - **Low** → **Light Gray (#E8E8E8)**
5. **Task count**: "📝 [X] tasks" (0 if no linked tasks)
6. **Review date**: "📅 [Date]" (red if today/overdue; orange if within 3 days)
7. **Last modified**: Small text, right-aligned (e.g., "Modified Apr 2, 3:45 PM")

**Card Actions** (Hover to reveal or always visible):
- Click card → Opens ticket detail view
- Right-click context menu:
  - Edit
  - Change Status (Open/Paused/Close)
  - Archive
  - Duplicate (clone immediately; not scheduled like recurring)
  - Delete

**Mobile Card** (Stacked, 1 column):
- Same information, vertical layout
- Long-press for context menu
- Swipe-left for quick close/archive

##### 3.7.7.C Card Sorting

**Within each tab, sort by** (dropdown or settings):
- Review date (ascending; nearest first)
- Priority (Urgent → High → Medium → Low)
- Created date (newest first)
- Title (A-Z)

**Default sort**: By review date (Review Due Soon tab) or priority (Open tab)

---

#### 3.7.8 Ticket Detail View - Desktop Layout (Two-Column)

##### 3.7.8.A Left Column (Ticket Info & Resources)

**Header**:
- Ticket title (editable; click to edit, auto-saves)
- Project name (clickable; navigate to project)
- Status (radio buttons: New / Open / Paused / Closed / Archive)
- Priority (dropdown or radio buttons: Urgent / High / Medium / Low)
- Review date (date picker; click to edit)

**Description**:
- Rich text OR markdown (editable)
- Click "Edit" button to toggle edit mode
- Auto-saves on blur (or manual save)

**Resource Hub** (Collapsible sections; see 3.7.4 above):
- Pinned Resources
- Files
- URLs
- CRUD Records
- Contacts
- Notes

**Linked Tasks** (Expandable section):
- "Linked tasks: [X] tasks" heading
- Click to expand → inline list of tasks (task title, due date, status)
- Button: "+ Link Task" (modal to select existing task or create new)
- Unlink button (X) on each task

---

##### 3.7.8.B Right Column (Activity & Actions)

**Activity Log** (Reverse chronological; last 20 comments shown):
- Each comment shows: Author name, timestamp, comment text
- System actions: "[User] changed status to [Status]" (gray, smaller text)
- Timestamps: Human-readable (e.g., "Apr 2, 3:45 PM" or "2 hours ago")
- "Load more" button (if > 20 comments)

**Comment Form** (Sticky at bottom):
- Textarea: "Add a note..." (placeholder)
- Rich text editor OR plain text (toggle)
- Buttons: [Post] [Cancel]
- Post button posts comment + shows in activity log immediately
- Auto-clears textarea after post

**Quick Actions** (Fixed bottom of right column):
- Status dropdown (read current, click to change)
- Review date picker (inline)
- Edit button (pencil icon; enables full editing mode)
- Delete button (warning modal)

---

#### 3.7.9 Ticket Detail View - Mobile Layout (Single-Column Stack)

**Mobile Responsive** (Vertical stack; no two-column):

1. **Header Section**:
   - Ticket title + project name
   - Status badge + priority indicator (horizontal bar)
   - Edit button (pencil icon; opens full-screen edit form)

2. **Info Section**:
   - Description (collapsible if long; "Read more" button)
   - Review date (tap to edit calendar)

3. **Tasks Section** (Collapsible):
   - "Linked tasks: [X]" heading
   - Tap to expand task list
   - "+Add Task" button

4. **Resource Hub** (Collapsible per section):
   - Pinned resources (always visible; tap to expand)
   - Files (tap to expand list)
   - URLs (tap to expand list)
   - CRUD (tap to expand list)
   - Contacts (tap to expand list)
   - Notes (tap to edit)

5. **Activity Log** (Collapsible):
   - "Activity" heading
   - Tap to expand comment list (last 10 shown)
   - "Load more" button if > 10

6. **Bottom Action Bar** (Fixed/Sticky):
   - Status dropdown (tap to open radio buttons)
   - Close/Archive button (swipe-right or tap button)
   - Delete button (three-dot menu)

7. **Comment Form** (Sticky bottom above action bar):
   - Textarea: "Add a note..." 
   - [Post] button

---

#### 3.7.10 Ticket Search & Filtering

##### 3.7.10.A Search Interface

**Search Bar** (Top of Projects dashboard):
- Text input: "Search tickets..."
- Scope dropdown: [Current project ▼] OR [All projects]
- Search triggers on: Enter key or auto (debounced 300ms as-you-type)

**Results Display**:
- Inline list of matching tickets (title, project, status)
- Click result → Opens ticket detail
- "[X] results found"

##### 3.7.10.B Filter Toolbar

**Optional filter toggles** (click header to expand/collapse):
- **Status**: Checkboxes (New, Open, Paused, Closed) — select multiple
- **Priority**: Checkboxes (Urgent, High, Medium, Low) — select multiple
- **Project**: Dropdown (All projects OR select specific)
- **Review Date**: Preset radio buttons:
  - "Due today"
  - "Due within week"
  - "Overdue"
  - "Custom range" → date picker

**Filter Behavior**:
- Applied instantly; results update in real-time
- Combine with search (search + filters = narrowed set)
- "Clear all filters" button

##### 3.7.10.C Sorting Options

**Sort Dropdown** (default: Review date ascending):
- Review date (ascending; nearest first)
- Review date (descending; furthest first)
- Priority (Urgent → High → Medium → Low)
- Priority (Low → Medium → High → Urgent)
- Created date (newest first)
- Created date (oldest first)
- Title (A-Z alphabetical)
- Title (Z-A reverse alphabetical)

---

#### 3.7.11 Admin vs. User Permissions

##### 3.7.11.A User (Primary)

**Permissions**:
- Create personal projects (visible to self only)
- Create tickets within own projects
- Edit/delete own tickets (status, resources, notes)
- Link tasks to own tickets
- Add/remove resources
- Comment on own tickets
- View only own projects/tickets

**Restrictions**:
- Cannot delete shared projects (admin-controlled)
- Cannot edit shared tickets created by admin (unless admin grants read-write permission)
- Cannot see other users' projects/tickets (except shared ones)

##### 3.7.11.B Admin (Optional for System)

**Permissions**:
- Create shared projects (visible to assigned users)
- Pre-populate tickets with templates
- Create tickets in shared projects
- Edit/delete any ticket (admin override)
- View all users' tickets (if shared)
- Bulk-manage tickets (Phase 2)
- Manage permissions (assign users to shared projects)

**Admin Dashboard**:
- See all projects/tickets across system
- Manage sharing settings
- Monitor ticket status (reporting, Phase 2)

##### 3.7.11.C Shared Tickets (Admin-Created)

**Visibility**: Visible to users assigned to project (by admin)

**Edit Permissions** (Configurable by admin per shared project):
- **Read-only**: Users can view resources, notes, comments; cannot edit/delete
- **Read-write**: Users can edit resources, add notes, change status, delete

**Shared Indicator**: Badge on ticket card: "(Shared)" or "(Admin-Created)"

---

#### 3.7.12 Integration with Tasks & Reminders

##### 3.7.12.A Ticket Review Date Reminders

**Optional on ticket detail**:
- Checkbox: "Create reminder for review date"
- If checked: System creates reminder (Section 3.8) for review date
- Notification: Email + SMS (if enabled)
- Message: "[Ticket Name] - time to review"

**Recurring ticket reminders**: If ticket recurring, reminder also repeats (same frequency)

##### 3.7.12.B Task Completion Integration

**Linked tasks completion** (NOT automatic):
- When all linked tasks completed: Ticket shows soft notification: "All tasks complete! Ready to close?"
- User manually closes ticket when satisfied (via status radio buttons)
- Does NOT auto-close

**Task creation from ticket**:
- User can click "+ Link Task" to:
  - Select existing task from Inbox/Backlog (link it)
  - OR create new task (auto-linked to ticket)

---

#### 3.7.13 Ticket Deletion & Archival Strategy

##### 3.7.13.A Archive (Soft Delete; Recoverable)

**Trigger**: Ticket detail → Status "Closed" → Button "Archive this ticket"

**Behavior**:
- Ticket hidden from normal views (Review Due Soon, Open, Paused, Closed tabs)
- Moved to "Archived" section (separate tab or search filter)
- No data deleted; fully recoverable
- Shows: "Last modified [date]"
- Restore button: Click to restore to previous status

**Use case**: Completed tickets archived to reduce clutter; can search if needed for reference

##### 3.7.13.B Delete (Permanent; Irreversible)

**Trigger**: Ticket detail → Edit button → Delete button (or three-dot menu)

**Confirmation Modal**:
- "Delete ticket '[Name]' permanently?"
- "This cannot be undone. Resources will be lost."
- Warnings: Linked tasks NOT deleted (only ticket deleted; tasks remain in task system)
- Options: [Delete] [Cancel]

**Post-Delete**:
- Ticket removed from all views
- Linked tasks remain (but link removed)
- Resources deleted (files in resource hub removed; CRUD links severed)
- No recovery possible

**Use case**: Remove test tickets, mistaken entries, or unwanted tickets

---

#### 3.7.14 Phase 1 MVP Scope

**Included in Phase 1**:
✅ Projects & Tickets (CRUD: Create, Read, Update, Delete)
✅ Resource Hub (files, URLs, CRUD records, contacts, notes)
✅ Two-column desktop layout + mobile-responsive single-column
✅ Status workflow (New → Open → Paused → Closed → Archive)
✅ Task linking (one-to-many; tasks link to tickets)
✅ Recurring tickets (clone on closure)
✅ Dashboard tabs (Review Due Soon, Open, Paused, Closed)
✅ Comment/activity log
✅ Search & filtering (by title, status, priority, review date)
✅ Auto-increment review reminders (if enabled)

**Phase 2+ (Future Enhancements)**:
🔄 Bulk ticket operations (create, duplicate, delete multiple)
🔄 Ticket sharing with granular permissions (assign users per project)
🔄 Resource versioning (track file changes)
🔄 Ticket templates (pre-built structures for common project types)
🔄 Timeline visualization (Gantt chart, calendar view)
🔄 Ticket collaboration (multiple users editing simultaneously, real-time sync)
🔄 Ticket dependencies (tickets blocked by other tickets)

---

#### 3.7.15 Ticket Database Schema & Relationships

**Tables** (Minimal Phase 1):
- `projects` (id, user_id, title, description, created_at, updated_at)
- `tickets` (id, user_id, project_id, title, description, status, priority, review_date, recurrence_rule, created_at, updated_at, archived_at)
- `ticket_resources` (id, ticket_id, resource_type, resource_data, sort_order, pinned)
- `ticket_tasks` (id, ticket_id, task_id) — Many-to-many linking table
- `ticket_comments` (id, ticket_id, user_id, comment_text, created_at)

**Relationships**:
- Project has many Tickets
- Ticket has many Resources (files, URLs, CRUD records, contacts, notes)
- Ticket has many Tasks (many-to-many; via ticket_tasks junction table)
- Ticket has many Comments

---

#### 3.7.16 Accessibility & Mobile Optimization

**Keyboard Navigation** (Desktop):
- Tab through status radio buttons, buttons, clickable resources
- Enter to activate, Space to toggle radio buttons
- Escape to close modal/edit mode

**Screen Readers**:
- Ticket title announced on detail view
- Status changes announced ("Ticket status changed to Open")
- Activity log comments read in order
- Resource links descriptive (e.g., "Link to external URL: clinic.com")

**Mobile Optimization**:
- Touch-friendly buttons (min 60x60px)
- Collapsible sections (tap to expand/collapse)
- Swipe gestures (swipe-left to close/archive)
- Bottom action bar for common actions

---

---

### 3.8 Reminders & Notifications System

#### 3.8.1 Reminder Types & Creation

**Five Core Reminder Types:**

1. **Task Due Date Reminders** (Auto-Created)
   - Auto-created when user creates task with due date
   - Default: 1 day before + morning-of due date (8 AM user timezone)
   - User can customize per task (add/remove reminder days)

2. **Ticket Review Date Reminders** (Auto-Created)
   - Auto-created when ticket has review date set + "Create reminder" checkbox enabled
   - Default: 5 days before + morning-of review date
   - For recurring tickets: Reminder cycles with recurrence (e.g., annual ticket → annual reminder)

3. **Medication Refill Reminders** (CRUD Integration)
   - Auto-created from Medication Tracker CRUD records
   - Trigger: 7 days before refill due date
   - Frequency: Daily at 8 AM (user timezone) until dismissed or marked "Refilled"
   - Can snooze to reschedule

4. **Bill Payment Due Reminders** (CRUD Integration)
   - Auto-created from Bill Tracker CRUD records
   - Trigger: Configurable days before due date (default 3 days; admin-set system default)
   - Frequency: Daily at 8 AM until dismissed or marked "Paid"
   - Can snooze to reschedule

5. **Recurring Habit Reminders** (For Daily Habits)
   - Auto-created from Daily Habits (if user enables reminders)
   - Trigger: Daily at configurable time (default 8 AM user timezone)
   - Stops when habit marked complete OR after 24-hour reset window

**Manual Reminders** (User-Created):
- Users can create one-off reminders (e.g., "Call Mom on Thursday 3 PM")
- Modal: Date/time picker + reminder text + delivery channels
- Sends once at specified time

---

#### 3.8.2 Notification Channels & Delivery Modes

**Channel 1: In-App Bell Icon (Always Active)**
- Count badge (e.g., "3" unread reminders) on top navigation bar
- Clicking bell opens dropdown showing last 10 unread reminders (sorted by due date, nearest first)
- Each reminder shows:
  - Reminder text (e.g., "Medication refill: Amoxicillin")
  - Due date/time
  - Action buttons: "Snooze", "Dismiss", "Done"
  - Click reminder text to navigate to related item (task, ticket, CRUD record)
- "Mark as Read" grays out reminder (still visible in dropdown)
- "Dismiss" removes from dropdown + archives notification (still viewable in history)

**Channel 2: Email Notifications (Configurable)**
- Sent to user's profile email address
- Two delivery modes (user chooses per channel):
  - **Immediate**: Single email per reminder as it triggers (high frequency)
  - **Digest**: Batched daily email with all pending reminders (default; low frequency)
- Email subject: "[Digest] Your ADHD Dashboard Reminders (Apr 2, 2026)" or "[Reminder] [Item]: [Details]"
- Email template: Clean, scannable format with due dates, clickable links to items
- Respects quiet hours (9 PM - 8 AM user timezone; no emails during quiet hours)
- Urgent reminders (overdue bills, medication refills) can bypass quiet hours (user configurable)

**Channel 3: SMS Notifications (Optional, Requires Phone Number)**
- Sent to phone number + carrier (e.g., 1234567890@txt.att.com via email-to-SMS gateway)
- Two delivery modes (user chooses):
  - **Immediate**: Text message per reminder as it triggers (high frequency; limited to 160 chars)
  - **Digest**: Single daily digest SMS with summary (e.g., "You have 3 reminders due today. Check your dashboard.")
- Message template: SMS-friendly, concise (e.g., "ADHD: Medication refill - Amoxicillin. Reply DONE when ordered.")
- Respects quiet hours (no texts sent between 9 PM - 8 AM)
- Urgent reminders can bypass quiet hours (user configurable)
- SMS delivery validation: System tests SMS before enabling (user clicks "Send test SMS" → receives confirmation → enables/disables SMS reminders)

**Channel Defaults** (Configurable per user in Settings → Notification Preferences):
- Email: Digest mode (daily)
- SMS: Digest mode (daily, if phone number verified)
- In-App Bell: Always active (always immediate)

---

#### 3.8.3 Notification Configuration & Preferences

**User Settings** (Profile → Notification Preferences):

**Global Toggle Section**:
- Email notifications: [Toggle] (on/off for all reminders)
- SMS notifications: [Toggle] (on/off for all reminders; only if phone verified)
- In-app bell: [Checkbox] (always on; cannot disable)

**Delivery Mode Section** (Per Channel):
- **Email delivery mode**: Radio buttons
  - ⦿ Immediate (send each reminder as it triggers)
  - ⦿ Daily digest (batch all reminders into one email each morning at 8 AM)
- **SMS delivery mode**: Radio buttons (if phone verified)
  - ⦿ Immediate (send text per reminder)
  - ⦿ Daily digest (one summary SMS each morning)

**Quiet Hours Configuration**:
- Quiet hours enabled: [Checkbox] (default ON)
- Start time: [Time picker] (default 9:00 PM)
- End time: [Time picker] (default 8:00 AM)
- "Urgent reminders bypass quiet hours": [Checkbox] (default ON; medication & overdue bills always notify)

**Per-Reminder-Type Customization** (Advanced; expandable section):
- **Task Due Date Reminders**:
  - "Remind me [X] days before" (checkbox; default 1 day before + day-of)
  - "Disable task due reminders": [Checkbox]
- **Ticket Review Reminders**:
  - "Remind me [X] days before" (checkbox; default 5 days before + day-of)
  - "Disable ticket review reminders": [Checkbox]
- **Medication Refills**:
  - "Remind me [X] days before" (text input; default 7 days; system locked to 7 if admin-mandated)
- **Bill Payments**:
  - "Remind me [X] days before" (text input; default configurable by admin, e.g., 3 days)
- **Daily Habits/Recurring**:
  - "Enable habit reminders": [Checkbox] (default OFF)
  - "Habit reminder time": [Time picker] (default 8:00 AM user timezone)

**SMS Phone Validation**:
- Phone number field: [Input] (e.g., "1234567890")
- Carrier dropdown: [AT&T / Verizon / T-Mobile / Sprint / Other]
- "Send test SMS": [Button]
- On click: System sends "Test SMS from ADHD Dashboard. Reply CONFIRM." to phone
- User receives text + replies to confirm
- System marks phone verified; SMS notifications enabled
- If SMS fails or no reply within 5 minutes: Error message "SMS verification failed. Try again or use email instead."

---

#### 3.8.4 Reminder Workflow & Lifecycle

##### 3.8.4.A Reminder Creation

**Auto-Created** (System-Generated on Entity Creation):
1. User creates task with due date → System creates reminders for 1 day before + day-of
2. User creates ticket with review date + enables review reminder → System creates reminders for 5 days before + day-of
3. Admin creates/imports medication record with refill date → System creates recurring reminder 7 days before
4. Admin creates/imports bill record with due date → System creates reminder N days before (default 3)
5. User enables Daily Habit reminders → System creates daily recurring reminder at configured time

**Manually Created**:
- User clicks "+ Add Reminder" in main navigation
- Modal: Reminder text + date + time + channels (Email/SMS/In-App)
- [Create] button → Reminder stored, notification scheduled

**Database Fields** (Table: `reminders`):
```sql
CREATE TABLE reminders (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  reminder_type ENUM('task_due', 'ticket_review', 'medication_refill', 'bill_payment', 'daily_habit', 'manual'),
  related_item_id INT (FK to task/ticket/CRUD record; NULL for manual),
  reminder_text VARCHAR(500),
  reminder_due_time DATETIME NOT NULL (when to send),
  delivery_channels SET('email', 'sms', 'in_app') NOT NULL,
  reminder_status ENUM('pending', 'sent', 'snoozed', 'dismissed') DEFAULT 'pending',
  snoozed_until DATETIME NULL,
  dismissed_at DATETIME NULL,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  INDEX(user_id, reminder_due_time, reminder_status)
);
```

##### 3.8.4.B Reminder Triggering & Delivery

**Cron Job Architecture**:
- **Frequency**: Every 15 minutes (sufficient for morning 8 AM reminders; not resource-heavy)
- **Query**: `SELECT * FROM reminders WHERE reminder_status = 'pending' AND reminder_due_time BETWEEN NOW() AND NOW()+15min`
- **Processing** (Per reminder found):
  1. Check user's quiet hours (9 PM - 8 AM default, or user-custom)
  2. If within quiet hours AND NOT urgent (urgent flag = medication/overdue bill):
     - Snooze reminder until end of quiet hours (e.g., 8:01 AM)
     - Continue to next reminder
  3. If NOT in quiet hours OR urgent flag set:
     - Send to all enabled channels (email + SMS + in-app):
       - **Email**: If delivery_mode = 'immediate' OR (delivery_mode = 'digest' AND it's 8 AM user timezone)
       - **SMS**: If delivery_mode = 'immediate' OR (delivery_mode = 'digest' AND it's 8 AM user timezone)
       - **In-App**: Always send immediately; bell badge increments
     - Update reminder status to 'sent'
     - Log delivery attempt + success/failure

**Digest Email Assembly** (Daily at 8 AM user timezone):
- Cron job runs for each user at their 8 AM (convert server time to user timezone)
- Query all reminders with delivery_mode='digest' AND reminder_status='pending' AND reminder_due_time <= NOW()
- Batch into single email with all reminders
- Email template:
  ```
  Subject: [Digest] Your ADHD Dashboard Reminders (Apr 2, 2026)
  
  Hi [User Name],
  
  Today's reminders:
  
  📋 Tasks Due:
  - Task 1: Due today
  - Task 2: Due tomorrow
  
  🏥 Medications:
  - Amoxicillin: Order refill (due in 5 days)
  
  💰 Bills:
  - Electric Bill: Due in 2 days
  
  Other reminders:
  - [Ticket Review]: Veterinarian - time to review (due in 3 days)
  
  [View Dashboard] [Manage Preferences]
  
  —
  ADHD Dashboard
  ```
- Mark all included reminders as 'sent'

**Digest SMS Assembly** (Daily at 8 AM user timezone):
- Similar to digest email; single SMS per user with summary
- Character limit ~160 chars; abbreviated format
- Template: "ADHD: 3 reminders due today (2 meds, 1 bill). Check dashboard."

**In-App Notifications** (Real-time):
- Bell badge increments immediately when reminder triggered
- Reminder appears in dropdown (stays visible until dismissed)
- Toast notification: "[Reminder Type]: [Item Name]" (optional, can disable in settings)

---

#### 3.8.5 Reminder Snooze & Dismissal

**Snooze Options** (From bell dropdown or email/SMS action links):
- Quick buttons: "1 Hour", "3 Hours", "Tomorrow 8 AM", "3 Days"
- Custom snooze: "Snooze until..." → Date/time picker
- Action: Updates `snoozed_until`, re-schedules reminder to new trigger time
- Useful for ADHD users: "Not ready now, but remind me later"

**Dismiss Actions**:
- **Mark as Read** (Bell dropdown): Grays out reminder; still visible in dropdown
- **Dismiss** (Bell dropdown or email): Removes reminder from dropdown + archives (soft-delete to `dismissed_at`)
- **Done** (Bell dropdown, for recurring reminders like habits): Marks complete for today; regenerates tomorrow's reminder
- Dismissed reminders still viewable in "Notification History" (Settings → Notification History; optional feature, Phase 2)

---

#### 3.8.6 Medication Refill Reminders (Detailed Workflow)

**Use Case**: User has medication "Amoxicillin" in Medication Tracker with refill_date = Apr 9, 2026.

**Timeline**:
- **Apr 2 (7 days before)**: Reminder created; reminder_due_time = Apr 2, 8:00 AM
- **Apr 2, 8:00 AM**: Cron job triggers; reminder sent to all channels (email/SMS/in-app)
- **Apr 2 (user's action)**:
  - Option A: User clicks "Done" in in-app bell → Reminder dismissed
  - Option B: User clicks "Snooze 3 days" → Reminder rescheduled for Apr 5, 8:00 AM
  - Option C: User navigates to Medication Tracker, marks "Amoxicillin" as "Refilled" → Reminder auto-dismissed + cleared
- **Apr 3-8**: Daily at 8 AM, reminder repeats (if NOT dismissed) until user takes action
- **Apr 9 (refill date)**: Final reminder sent morning-of; if NOT marked refilled by midnight, becomes overdue (red badge on medication record)

**Admin Control** (System Default):
- Admin can set system-wide medication reminder frequency (default 7 days before; can be 3, 7, 14, 30 days)
- Users cannot override (system default enforced for safety; medication compliance critical)

---

#### 3.8.7 Bill Payment Due Reminders (Detailed Workflow)

**Use Case**: User has bill "Electric Bill" in Bill Tracker with due_date = Apr 5, 2026.

**Timeline** (Admin default: 3 days before):
- **Apr 2 (3 days before)**: Reminder created; reminder_due_time = Apr 2, 8:00 AM
- **Apr 2, 8:00 AM**: Reminder sent to all channels
- **Apr 2 (user's action)**:
  - Option A: User dismisses in bell dropdown
  - Option B: User snoozes for later
  - Option C: User navigates to Bill Tracker, marks bill as "Paid" → Corresponding task (if auto-generated) updated to "Completed" → Reminder auto-dismissed
- **Apr 3-4**: Daily reminders continue (if NOT dismissed)
- **Apr 5 (due date)**: Final reminder sent morning-of
- **Apr 6+ (overdue)**: If NOT marked paid by due date, bill record shows red "OVERDUE" badge; reminder escalates to urgent (SMS + email bypass quiet hours daily)

**Admin Configuration**:
- Admin sets default reminder days before bill due (configurable: 1, 3, 7, 14 days; default 3)
- Users can customize per bill (edit bill record → "Remind me X days before")

---

#### 3.8.8 Task Due Date Reminders (Detailed Workflow)

**Use Case**: User creates task "Dentist Appointment" with due_date = Apr 5, 2026.

**Default Reminders** (Auto-created):
- Reminder 1: Apr 4, 8:00 AM (1 day before)
- Reminder 2: Apr 5, 8:00 AM (day-of)

**Customization** (Task detail view):
- Checkboxes: "Remind me 1 day before" [✓], "Remind me day-of" [✓], "Custom reminder" [Text input: X days before]
- User can add multiple custom reminders (e.g., 3 days before, 1 day before, morning-of)
- User can disable all reminders if desired

**Workflow**:
- Apr 4, 8 AM: First reminder sent (email/SMS/in-app based on user settings)
- User dismisses or snoozes
- Apr 5, 8 AM: Day-of reminder sent
- Apr 5: User completes task (checkmark) → Reminders auto-dismissed (no further notifications)

---

#### 3.8.9 Ticket Review Date Reminders (Detailed Workflow)

**Use Case**: Ticket "Annual Dental (Pediatrician)" with review_date = Apr 15, 2026; "Create reminder for review date" enabled.

**Default Reminders** (Auto-created):
- Reminder 1: Apr 10, 8:00 AM (5 days before review date)
- Reminder 2: Apr 15, 8:00 AM (day-of review date)

**For Recurring Tickets** (Annual recurrence):
- If ticket recurs annually, reminders also cycle annually
- Next year: Reminders set for Apr 10 & 15 again

**Workflow**:
- Apr 10, 8 AM: First reminder sent
- User dismisses or snoozes (might open ticket to review resources, notes)
- Apr 15, 8 AM: Day-of reminder sent
- User closes ticket (status → Closed) → Reminders dismissed + next year's recurring ticket created with new reminders pre-scheduled

---

#### 3.8.10 Daily Habit Reminders (Recurring Daily)

**Use Case**: User has Daily Habit "Meditation" with reminder_enabled = true and reminder_time = 7:00 AM.

**Reminder Behavior**:
- Creates recurring reminder at 7:00 AM user timezone, every day
- Sent to all channels (email/SMS/in-app based on delivery mode)
- User marks habit complete (in daily habits section) → Reminder dismissed for today + auto-regenerates for tomorrow
- If user does NOT complete habit by next day at 7 AM, reminder carries over (becomes persistent; "2 days overdue" badge)
- User can snooze habit reminders (like task reminders)

**Admin Control**:
- Habit reminders are optional (user can disable habit reminders globally in Settings)
- If enabled, user sets reminder time (default 8 AM)
- Reminders respect quiet hours

---

#### 3.8.11 Task Assignment Notifications (Delegation)

**Trigger**: Admin assigns task to user (Section 3.4 delegation).

**Notification Details**:
- Email subject: "[Assigned Task] [Task Title] from [Admin Name]"
- Email body: "[Admin Name] assigned you: [Task Title]. Due: [Date]. Priority: [Level]."
- SMS (if enabled + urgent): "ADHD: [Task] assigned by [Admin] due [Date]. Check dashboard."
- In-app bell: "+1" badge; notification text: "[Admin Name] assigned: [Task Title]"
- No automatic dismissal; stays in notifications until user marks read/dismissed

**Accountability Link**:
- Admin can view "Assigned Tasks Dashboard" to see completion status of all tasks delegated to users
- Shows: Task name, assignee, due date, status (pending/completed), days overdue (if applicable)
- Optional escalation: If task 3+ days overdue, send admin reminder SMS + email

---

#### 3.8.12 Notification Bell Dropdown - Full UI Specification

**Bell Icon** (Top navigation bar):
- Located top-right, next to user profile icon
- Icon: 🔔 (bell shape)
- Badge: Shows unread count (e.g., "3" red badge)
- Click to open dropdown

**Dropdown Menu** (Anchored below bell icon; scrollable if > 10 reminders):
```
┌────────────────────────────────────┐
│ Notifications        [Close X]     │
├────────────────────────────────────┤
│ ✓ Medication Refill: Amoxicillin  │ (Sent 2h ago)
│   [Snooze ▼] [Dismiss] [Done]    │
│                                    │
│ 💰 Bill Due: Electric Bill       │ (Sent 1h ago)
│   Due in 2 days                    │
│   [Snooze ▼] [Dismiss] [Done]    │
│                                    │
│ 📋 Task Due: Dentist Appointment │ (Sent 30m ago)
│   Due today (7:00 PM)              │
│   [Snooze ▼] [Dismiss] [Done]    │
│                                    │
│ [Load more reminders...]          │
├────────────────────────────────────┤
│ [Settings] [Clear All Dismissed]  │
└────────────────────────────────────┘
```

**Snooze Dropdown** (Quick Menu):
```
[1 Hour]
[3 Hours]
[Tomorrow 8 AM]
[3 Days]
[Custom...] → Date/time picker modal
```

**Mobile Dropdown** (Vertical stack, full-width):
- Same content, stacked vertically
- Buttons: [Snooze ▼] [Dismiss] [Done] in column layout
- "Load more" paginated for performance

---

#### 3.8.13 Email Notification Template (HTML)

**Immediate Email** (Per-reminder):
```
Subject: [Reminder] [Item Type]: [Item Name]

Hi [User Name],

Your ADHD Dashboard reminder:

[REMINDER ICON] [Reminder Type]: [Item Name]
Details: [Due date/time], [Priority if applicable]

[View in Dashboard] (clickable link → auto-login token)

---
Questions? Manage your notification settings: [Link]
```

**Digest Email** (Daily batch):
```
Subject: [Digest] Your ADHD Dashboard Reminders (Apr 2, 2026)

Hi [User Name],

Your daily reminder digest:

📋 TASKS DUE TODAY (2 items):
  • Dentist Appointment - Today 7:00 PM
  • Follow-up email - Today 5:00 PM

🏥 MEDICATIONS (1 item):
  • Amoxicillin refill - Order by Apr 9

💰 BILLS (1 item):
  • Electric Bill - Due Apr 5 (in 3 days)

🎫 TICKET REVIEW (1 item):
  • Annual Dental (Pediatrician) - Review by Apr 15

[View Your Dashboard]

---
Manage your notification preferences: [Link]
Don't want daily digests? Adjust settings here: [Link]
```

---

#### 3.8.14 Accessibility & Mobile Optimization

**Keyboard Navigation** (Desktop):
- Tab through bell icon → dropdown items → snooze buttons
- Enter to activate snooze/dismiss/done
- Escape to close dropdown

**Screen Readers**:
- Bell icon announced as "Notifications [X] unread"
- Each reminder announced: "[Reminder Type]: [Item Name]. [Due date/time]. [Status: Sent 2 hours ago]."
- Action buttons: "Snooze reminder", "Dismiss reminder", "Mark as done"

**Mobile Optimization**:
- Bell icon touch-friendly (min 60x60px)
- Dropdown fullscreen or large modal (no small overlays)
- Buttons: Large, touch-friendly (min 48x48px)
- Swipe-close dropdown (swipe right to close)
- Long-press on reminder for context menu (Edit, Snooze, Dismiss, Move to history)

**Color & Contrast**:
- Bell badge: High contrast red background + white text
- Reminder cards: Standard background + dark text (AA accessibility standard)
- Action buttons: Clearly distinguished (blue snooze, red dismiss, gray done)
- Icons: Large, clear (emoji or SVG icons)

---

#### 3.8.15 Phase 1 MVP Scope

**Included in Phase 1**:
✅ Auto-created reminders (task due, ticket review, medication refill, bill due)
✅ In-app bell notifications (real-time)
✅ Email digests (daily batch)
✅ SMS digest (daily batch, if phone verified)
✅ Snooze options (1h, 3h, tomorrow, 3d, custom)
✅ Dismiss & archive
✅ Quiet hours (global + per-user override)
✅ Urgent reminder bypass (medication/overdue bills)
✅ Cron job architecture (15-min frequency)
✅ Full notification preferences UI
✅ Task assignment notification
✅ Mobile responsive

**Phase 2+ (Future)**:
🔄 Notification history & analytics (view all past reminders)
🔄 Push notifications (if PWA enabled)
🔄 Recurring habit daily reminders (daily habits feature completion)
🔄 Notification templates (admin-customizable messages)
🔄 Smart reminder grouping (combine similar reminders, e.g., "3 bills due this week")
🔄 Calendar integration (export reminders to Google Calendar/Outlook)
🔄 Escalation workflow (auto-escalate overdue bills to admin after 7 days)

---

---

### 3.9 Gamification & Positive Reinforcement

#### 3.9.1 Visual Elements
- **Progress Pie Charts**: On 1-3-5 list (completed ÷ total = fill %)
- **Completion Animations**: Multi-layer success feedback (see 3.9.1.A-F below)
- **Success Messages**: "Great job!", "You're crushing it!", "Nice work!", etc. (randomized, uplifting)
- **Optional Sounds**: Gentle chime on completion (mute-able)

##### 3.9.1.A Completion Animation Framework
On task completion (check mark clicked or "Complete" button pressed):
1. **Trigger Animation**: Simultaneous playback of:
   - Layer 1: Checkmark animation (scales and fills with green)
   - Layer 2: Particle effect (confetti OR gentle floating shapes)
   - Layer 3: Success message (randomized positive text)
   - Layer 4: Optional sound (soft chime, customizable)
2. **Duration**: Total animation sequence 2.5 seconds (all layers synchronized)
3. **Precedence**: Only one completion animation plays per task per completion event (prevents overlapping distractions)
4. **Target Location**: Animation originates from the task card's checkbox/complete button, expands outward

##### 3.9.1.B Checkmark Animation (Core Reward Element)
- **Initial state**: Empty checkbox or button
- **On trigger**: 
  - Checkbox scales 1.0 → 1.2 (expand) in 400ms, then 1.2 → 1.0 (settle) in 200ms (bounce effect)
  - Checkmark SVG draws L-R with stroke animation (200ms total)
  - Background color fills with subtle green (#27AE60) gradient over 300ms
- **Effect**: Satisfying, non-jarring confirmation that task locked in "completed" state
- **Visual Design**: Large, clear checkmark (40x40px minimum on mobile, 50x50px desktop)
- **Timing**: Completes by 600ms, giving way to next layers

##### 3.9.1.C Particle Effect Options (User Selectable in Settings)

**Option 1: Confetti Burst** (Default, ADHD-Engaging)
- **Trigger**: From task card center, burst outward
- **Particles**: 15-25 small shapes (circles, stars, squares in palette colors: yellow, orange, teal)
- **Duration**: 1.0-1.5 seconds (fade out by 1.5s)
- **Velocity**: Random 2-6 pixels/frame, gravity effect downward
- **Colors**: Match theme palette accent colors (never red); no harsh contrasts
- **Performance**: CSS animations (requestAnimationFrame for smooth 60fps)
- **Accessibility**: Respects prefers-reduced-motion (see 3.9.1.E)

**Option 2: Gentle Float-Up Stars** (Calming Alternative)
- **Trigger**: From task card, float upward
- **Particles**: 5-8 star shapes, semi-transparent
- **Duration**: 2.0 seconds (slower than confetti, more meditative)
- **Velocity**: Constant 1-2 pixels/frame upward
- **Colors**: Soft gradient (yellow to pale green)
- **Effect**: Less stimulating, good for users preferring calm completion feedback

**Option 3: Ripple Pulse** (Subtle, Professional)
- **Trigger**: Task card center expands outward as concentric circles
- **Circles**: 3-4 expanding rings
- **Duration**: 1.0 second
- **Colors**: Accent color (varies by theme) → fade to transparent
- **Effect**: Minimal celebration; supports professional/minimal aesthetics

**Option 4: Disabled (None)** (For sensory-sensitive users)
- No animation plays; only success message and optional sound remain
- Useful for users with animation sensitivity or preference for minimal UI

##### 3.9.1.D Success Messages (Positive-Only Rotation)
**Message Pool** (randomly selected):
- "Great job!" (affirming, simple)
- "You're crushing it!" (energetic, empowering)
- "Nice work!" (encouraging)
- "Way to go!" (enthusiastic)
- "Awesome!" (celebratory)
- "You did it!" (affirming completion)
- "Keep it up!" (motivational for streaks)
- "Excellent work!" (professional)
- "One down, [X] to go!" (progress-focused; shows remaining on 1-3-5)
- "That's a win!" (confidence-building)

**Display Format**:
- Font size: 18px (mobile), 24px (desktop)
- Position: Above task card, scrolls in from top (fade + slide animation over 300ms)
- Duration: Display for 1.5 seconds, then fade out over 500ms
- Color: Match success green (#27AE60) or soft gold (#F39C12) depending on theme
- Weight: Bold (font-weight 700) for impact
- Shadow: Subtle drop shadow for legibility over content

**Customization**:
- Admin/User can add custom success messages (up to 30 characters) in settings
- User can disable success messages entirely (keep animation + sound only)
- Frequency: Appears on every completion, never "missing" (full positive reinforcement)

##### 3.9.1.E Optional Completion Sound (Accessibility & Customization)

**Sound Options** (user-selectable in profile settings):
1. **Gentle Chime** (Default, 8-bit style, 300ms)
   - Warm, retro video game tone (not harsh)
   - Volume: 60% of system volume by default (user configurable 0-100%)
   - Plays over animation duration
   
2. **Bell Tone** (Softer, meditation-inspired, 400ms)
   - Clear, sustained tone; calming effect
   - Lower pitched than chime
   
3. **Uplifting Ding** (Higher octave, celebratory, 200ms sharp note)
   - Bright, affirming tone
   - Shorter duration for quick satisfaction
   
4. **Silence** (User-enabled)
   - No sound; visual feedback only
   - Useful for users working in shared spaces
   - Browser mute setting also respected globally

**Sound Implementation**:
- Audio files preloaded on dashboard load (minimal latency)
- Play via Web Audio API or HTML5 <audio> tag
- Respects browser mute/volume settings
- Volume reducer if system detects notification fatigue (after 10+ completions/day, volume drops to 40%)
- **Mobile**: Tests for notch handling; sound respects phone silent mode

##### 3.9.1.F Accessibility & Performance

**Reduced Motion Preference** (prefers-reduced-motion Media Query):
- When user/OS has `prefers-reduced-motion: reduce` set:
  - Checkmark animation plays at 1x speed (not reduced; still satisfying)
  - Particle effects fade/disappear without movement (opacity transition only)
  - Success message stays longer (3 seconds instead of 1.5)
  - Sound remains (if enabled; frequency-based tones OK for motor disabilities)
- **Rationale**: ADHD users need positive feedback; motion reduction shouldn't eliminate reward

**Performance Optimization**:
- All animations use CSS transforms (translateX, scale, opacity) for GPU acceleration
- Particle effects use requestAnimationFrame, max 60fps
- No canvas animations (better performance on mobile)
- Max 3 simultaneous animations on screen (prevents battery drain)
- Animations garbage-collected immediately after completion

**Testing Criteria**:
- ✅ Animation completes within 2.5 seconds total
- ✅ Keyboard accessibility: Can trigger via Space/Enter on task row
- ✅ Mobile smooth 60fps on mid-range phones (Android 8+, iPhone 7+)
- ✅ Battery impact negligible (no infinite loops, GPU-accelerated)
- ✅ Screen reader users hear success message read aloud if available (aria-live="polite" region)

##### 3.9.1.G Multi-Task Completion Scenarios

**Scenario A: Completing Single Task**
- Standard animation + message + sound (as above)

**Scenario B: Completing All 1-3-5 Tasks (All Win)**
- First two animations play normally
- Third task completion triggers **enhanced celebration**:
  - Larger confetti burst (30-40 particles vs. 15-25)
  - Extra success message: "All tasks done! Rest well!" (motivates completion, not more)
  - Sound plays twice (double chime) for extra celebratory effect
  - Pie chart animates to 100% filled with brief glow effect

**Scenario C: Bulk-Completing via Checkbox (Admin/Power User)**
- If user checks 3+ tasks simultaneously via admin panel:
  - Single unified animation sequence (prevents animation overload)
  - Counts completed: "You finished 3 tasks!" message
  - Single large confetti burst from center of completed list
  - Sound plays once for the batch

**Scenario D: Completing Task During Focus Mode**
- Animation plays fullscreen within Focus Mode modal (overlay on timer area)
- Does NOT close Focus Mode
- Message + sound same as single task
- User remains in Focus Mode to start next task

**Scenario E: Completing Recurring Task**
- Standard animation
- Upon completion, next instance is automatically cloned
- Brief notification below: "Next [Task Title] scheduled for [date]"
- No additional animation for the clone (would be overwhelming)

##### 3.9.1.H Animation Timing Specification
| Element | Start (ms) | Duration | End (ms) | Effect |
|---------|-----------|----------|---------|--------|
| Checkmark scale (expand) | 0 | 400 | 400 | 1.0 → 1.2 scale |
| Checkmark settle | 400 | 200 | 600 | 1.2 → 1.0 scale |
| Checkmark stroke draw | 100 | 200 | 300 | SVG stroke animation |
| Background fill | 0 | 300 | 300 | Transparent → green |
| Particles launch | 600 | 1200 | 1800 | Burst outward |
| Message fade in | 300 | 300 | 600 | Opacity 0 → 1 |
| Message display | 600 | 1500 | 2100 | Visible on screen |
| Message fade out | 2100 | 500 | 2600 | Opacity 1 → 0 |
| Sound play | 0 | 300-400 | ~400 | Starts immediately |

**Total Sequence Duration**: 2.5 seconds (animations complete; user can proceed to next task)

##### 3.9.1.I Animation Testing & Quality Assurance

**Browser Compatibility**:
- ✅ Chrome/Edge 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Mobile Safari (iOS 13+)
- ✅ Chrome Android (6.0+)

**Device Testing**:
- Desktop (1920x1080, 27" monitor)
- Laptop (1366x768, 13" screen)
- Tablet (iPad 9.7", Android tablet 10")
- Mobile (iPhone 12, Samsung Galaxy S10)

**Performance Benchmarks**:
- Animation frame rate must remain 60fps (no drops below 55fps)
- CPU usage < 5% during animation
- GPU acceleration required on mobile
- Battery drain < 1% per 100 completions
- Memory footprint of animation assets < 500KB total

**User Testing Feedback Criteria** (Phase 3 validation):
- Users report feeling "accomplished" after completion (survey: 4/5 or higher)
- Animation does NOT feel overwhelming or anxiety-inducing (0 complaints about stress)
- Users with ADHD report high engagement (target: 3+ tasks/day logged consistently)
- Accessibility testers confirm reducedmotion support works flawlessly
- No users disable animations (if they do, investigate why)

#### 3.9.2 Side Quests (Random Task Selector for Decision Paralysis)

##### 3.9.2.A Purpose & Mechanics

**Purpose**: Break decision paralysis by presenting a randomized "quest" (Someday Maybe task) to user. User can accept quest (move to active tasks), reroll (get different quest), or dismiss (try later).

**Real-World Example**: User has 20+ tasks in "Someday Maybe" backlog but can't decide what to work on. User clicks "Roll Quest" → System randomly selects "Organize garage shelves (1 hour estimated)" → User can accept, reroll, or skip.

##### 3.9.2.B Quest Selection Algorithm

**Trigger**: User clicks "+ Quest" button on dashboard (or accesses Side Quests feature)

**Filter Criteria**:
1. Source: Pull only from "Someday Maybe" priority tasks (user's backlog)
2. Exclude: Recurring tasks (avoid duplicates), already-linked-to-active tasks
3. Time-Based Filtering: 
   - User selects available time window: 15 minutes / 30 minutes / 1 hour / 2+ hours
   - Only show quests with estimated_time ≤ selected window
   - Estimated time stored on task; default 30 min if not specified

**Random Selection**:
- From filtered list, randomly select 1 quest
- Show quest card with:
  - Quest name/title
  - Estimated time (e.g., "30 minutes")
  - Category/label (if any)
  - Description preview (first 100 chars)
  - Urgency indicator (if due date set; light indicator, not alarming)

**Edge Cases**:
- If < 3 quests available in user's Someday Maybe: Show message "Not enough quests available. [Link to view Someday Maybe backlog]"
- If no time-matching quests: Show "No quests available in that time window. Try a longer window or explore your backlog."

##### 3.9.2.C Quest Actions (Accept, Reroll, Dismiss)

**Quest Card UI**:
```
┌─────────────────────────────────────┐
│ 🎯 QUEST CARD                       │
├─────────────────────────────────────┤
│ [Quest Icon/Emoji]                   │
│                                      │
│ "Organize garage shelves"            │
│ ⏱ 1 hour estimated                  │
│ 📁 Home Maintenance                  │
│                                      │
│ "Organize shelves in garage for..." │
│                                      │
│ [Accept Quest] [Reroll] [Dismiss]  │
└─────────────────────────────────────┘
```

**Accept Quest**:
- Button: "Accept Quest"
- Action: Move task from "Someday Maybe" to "Active Tasks" (or show on next day's 1-3-5 if today full)
- Confirmation: Toast "Quest accepted! Added to your active tasks."
- Navigation: Return to dashboard or show task detail (user preference)
- Trigger completion animation on next dashboard view (if user completes it)

**Reroll** (Unlimited; no penalty):
- Button: "Reroll"
- Action: Randomly select different quest from same filtered list
- Behavior: Show new quest card (can reroll infinitely)
- Tracking: System logs reroll count (for analytics; user can't see count to avoid shame)
- Message: After 3+ rerolls: "Feeling picky? You can always skip a quest and save it for later. [Browse Backlog]"

**Dismiss**:
- Button: "Dismiss"
- Action: Close quest popup; task stays in "Someday Maybe" (not lost)
- Can re-roll same task later (no consequences)

##### 3.9.2.D Quest Completion Flow

**When Quest Completed**:
- Task marked complete during active day (regular completion animation plays)
- System detects "This was a Side Quest!"
- Enhanced celebration triggers:
  1. Larger confetti burst (30-40 particles vs. 15-25)
  2. Special success message: "🎯 QUEST COMPLETE!" (in addition to standard message)
  3. Unlock temporary "Side Quest Master" badge (if 3+ quests completed this week)
  4. Optional sound: Double chime (extra celebratory tone)

**Badge "Side Quest Master"**:
- Unlock condition: Complete 3+ Side Quests in a single calendar week
- Display on dashboard: "🏆 This week's Side Quest Master!" (celebratory badge)
- Duration: Resets weekly (can earn again next week)
- Profile: Shows current week's badge if earned

##### 3.9.2.E Side Quests Database Schema

```sql
CREATE TABLE side_quests (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  task_id INT NOT NULL (FK to tasks),
  quest_offered_at TIMESTAMP,
  quest_accepted_at TIMESTAMP NULL,
  quest_completed_at TIMESTAMP NULL,
  reroll_count INT DEFAULT 0,
  dismissed_at TIMESTAMP NULL,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  INDEX(user_id, quest_accepted_at, task_id)
);
```

---

#### 3.9.3 Streaks (Consecutive Days of Activity)

##### 3.9.3.A Streak Types

**Two Independent Streaks**:

**Streak 1: Pomodoro Streak** 🔥
- Definition: Consecutive calendar days with ≥ 1 Pomodoro session completed
- Tracks: Days for which user started AND completed at least one Pomodoro session
- Reset: Breaks if user doesn't complete any Pomodoro on a given day

**Streak 2: Task Completion Streak** 📋
- Definition: Consecutive calendar days with ≥ 1 task completed
- Tracks: Any task marked complete (from 1-3-5, backlog, side quests)
- Reset: Breaks if user doesn't complete any task on a given day

##### 3.9.3.B Streak Calculation & Reset Rules

**Streak Calculation** (Nightly Cron or On-Demand):
```
FOR each streak type:
  Get user's last 30 days of activity (Pomodoro sessions OR tasks completed)
  Find longest consecutive day sequence where activity ≥ 1 per day
  Update streak_count, streak_start_date, streak_end_date in database
  If gap detected between yesterday and today, reset streak to 0 (or 1 if activity today)
```

**Reset Behavior**:
- Missed day breaks streak (no catch-up window)
- Clear decision: Simple logic, avoids ADHD "justification paralysis"
- Notification: On reset, show neutral message "Streak reset. New day, fresh start." (not punitive)

**Special Case: Timezone Boundary**:
- Cron runs at midnight user's timezone (not server timezone)
- Activity counted toward that day if completed before midnight user timezone

##### 3.9.3.C Streak Display & Motivation

**Dashboard Widget** (Pinned above 1-3-5 list):
```
┌──────────────────────────────────────┐
│ YOUR STREAKS                          │
├──────────────────────────────────────┤
│ 🔥 5 days - Pomodoro streak         │
│                                       │
│ 📋 2 days - Task completion streak   │
│                                       │
│ [View streak history]                │
└──────────────────────────────────────┘
```

**Milestone Messages** (Every 7 days):
- 7-day: "Incredible! 🎉 One week of consistency!"
- 14-day: "Amazing! 2 weeks — you're unstoppable!"
- 30-day: "Legendary! 🏆 30-day streak — keep going!"
- Message appears on dashboard once per milestone (dismissible)

**Never Show**:
- ❌ "You're [X] days away from 30-day achievement" (creates pressure)
- ❌ "You lost your 25-day streak" (causes shame/guilt)
- ❌ Countdown clocks or urgency (anxiety-inducing)

**Profile Page**:
- Shows current streaks + all-time best streaks (e.g., "Best Pomodoro streak: 12 days")
- Useful reference for user to reflect on achievement

##### 3.9.3.D Streak Database Schema

```sql
CREATE TABLE streaks (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  streak_type ENUM('pomodoro', 'task_completion'),
  current_count INT DEFAULT 0,
  streak_start_date DATE,
  streak_end_date DATE,
  best_count INT (all-time record),
  best_start_date DATE,
  best_end_date DATE,
  last_activity_date DATE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  UNIQUE(user_id, streak_type),
  INDEX(user_id, streak_type, current_count)
);
```

---

#### 3.9.4 Badges & Achievements (One-Time Milestones)

##### 3.9.4.A Badge Types

**Eight Milestone Badges** (One-time unlocks; non-repeating):

1. **🚀 First Task** — Unlock on: Complete first task ever
2. **🏃 First Pomodoro** — Unlock on: Complete first Pomodoro session
3. **🔥 Week Warrior** — Unlock on: Achieve 7-day Pomodoro streak
4. **📋 Task Master** — Unlock on: Achieve 7-day task completion streak
5. **💯 Century Club** — Unlock on: Complete 100 tasks (all-time)
6. **⏱️ Decade Runner** — Unlock on: Complete 10 Pomodoro sessions (all-time)
7. **🎯 Side Quest Master** — Unlock weekly if: Complete 3+ Side Quests in a calendar week (repeats weekly)
8. **🌟 Executive Function King** — Unlock on: Maintain BOTH 7-day streaks simultaneously (Pomodoro + Task)

##### 3.9.4.B Badge Unlock Conditions (Technical Implementation)

**Trigger**: After task completion or Pomodoro logged, system runs badge check:

```javascript
// Pseudocode: Badge checking logic
checkBadges(user_id) {
  // Check if already unlocked (badges are one-time, except Side Quest Master)
  let unlockedBadges = getUnlockedBadges(user_id);
  let newBadges = [];
  
  // Badge 1: First Task
  if (!hasUnlock('First Task') && getTasksCompleted(user_id) >= 1) {
    newBadges.push('First Task');
  }
  
  // Badge 2: First Pomodoro
  if (!hasUnlock('First Pomodoro') && getPomodorrosCompleted(user_id) >= 1) {
    newBadges.push('First Pomodoro');
  }
  
  // Badge 3: Week Warrior (Pomodoro streak)
  let pomodoroStreak = getCurrentStreak(user_id, 'pomodoro');
  if (!hasUnlock('Week Warrior') && pomodoroStreak >= 7) {
    newBadges.push('Week Warrior');
  }
  
  // Badge 4: Task Master (Task streak)
  let taskStreak = getCurrentStreak(user_id, 'task_completion');
  if (!hasUnlock('Task Master') && taskStreak >= 7) {
    newBadges.push('Task Master');
  }
  
  // Badge 5: Century Club
  if (!hasUnlock('Century Club') && getTasksCompleted(user_id) >= 100) {
    newBadges.push('Century Club');
  }
  
  // Badge 6: Decade Runner
  if (!hasUnlock('Decade Runner') && getPomodorrosCompleted(user_id) >= 10) {
    newBadges.push('Decade Runner');
  }
  
  // Badge 7: Side Quest Master (Weekly; can earn multiple times)
  let weeklyQuests = getQuestsCompletedThisWeek(user_id);
  if (weeklyQuests >= 3 && !hasWeeklyBadge('Side Quest Master', this_week)) {
    newBadges.push('Side Quest Master');
  }
  
  // Badge 8: Executive Function King (Both streaks ≥ 7)
  if (!hasUnlock('Executive Function King') && pomodoroStreak >= 7 && taskStreak >= 7) {
    newBadges.push('Executive Function King');
  }
  
  // Award new badges
  if (newBadges.length > 0) {
    awardBadges(user_id, newBadges);
    triggerBadgeUnlockAnimation(newBadges);
  }
}
```

##### 3.9.4.C Badge Display Locations

**Primary: Profile Page** ("Badges Unlocked" section):
```
┌───────────────────────────────────────────┐
│ BADGES UNLOCKED                           │
├───────────────────────────────────────────┤
│ 🚀 First Task        (Unlocked Apr 2)    │
│ 🔥 Week Warrior      (Unlocked Apr 9)    │
│ 🏆 Executive Function King (Unlocked Apr 16) │
│                                           │
│ Empty: No other badges yet                │
│ [Keep pushing!]                           │
└───────────────────────────────────────────┘
```

**Secondary: Dashboard Widget** (Optional; default OFF):
- User can enable "Show Recent Badges" widget in dashboard settings
- Shows: Last 2-3 badges earned (newest first)
- Example: "🎉 Just unlocked: Week Warrior!"
- Can dismiss or disable widget

**Tertiary: Badge Notification** (On unlock):
- Toast notification: "🎉 Badge Unlocked: [Badge Name]!"
- Optional animation: Badge icon appears with fanfare sound
- Appears for 3 seconds, dismissible

##### 3.9.4.D Badge Persistence & Achievement

**Badges are Permanent**:
- Once unlocked, badges never disappear
- Visible on profile forever
- Can't "lose" badges if streaks break
- Historical record of achievements (useful for reflection)

**Note**: 
- NO "Active Badges" system (wearing/deactivating badges)
- NO "badge collections" or "levels" (too complex for ADHD)
- Simple: Unlock → Display forever (minimalist)

##### 3.9.4.E Badge Database Schema

```sql
CREATE TABLE badges (
  id INT PRIMARY KEY AUTO_INCREMENT,
  badge_type VARCHAR(50) UNIQUE (first_task, first_pomodoro, week_warrior, etc.),
  badge_name VARCHAR(100),
  badge_description VARCHAR(500),
  unlock_icon VARCHAR(50) (emoji or icon file),
  created_at TIMESTAMP
);

CREATE TABLE user_badges (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  badge_id INT NOT NULL (FK to badges),
  unlocked_at TIMESTAMP,
  created_at TIMESTAMP,
  UNIQUE(user_id, badge_id),
  INDEX(user_id, unlocked_at)
);

-- For weekly Side Quest Master badge (can unlock multiple times)
CREATE TABLE user_badges_weekly (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  badge_id INT (FK to badges; 'Side Quest Master'),
  week_of YEAR_WEEK (e.g., 202614 for week 14 of 2026),
  unlocked_at TIMESTAMP,
  created_at TIMESTAMP,
  UNIQUE(user_id, week_of),
  INDEX(user_id, week_of)
);
```

---

#### 3.9.5 Daily Habits (Optional Separate Tracking System)

##### 3.9.5.A Purpose & Scope

**Purpose**: Track small, repeatable daily behaviors separate from task system. Examples: "Brush teeth", "Take medication", "Drink 8 glasses of water", "5-minute meditation".

**Key Difference from Tasks**:
- Tasks: Goal-oriented (complete to-do list)
- Habits: Consistency-oriented (build repetitive behavior)

**Frequency Options**:
- Daily (every day)
- Weekly (X days per week; e.g., "Gym 3x/week" = Mon/Wed/Fri)
- Custom (e.g., "Every other day")

##### 3.9.5.B Habit Dashboard Widget

**Location**: Dashboard, optional widget (default OFF; user enables in Settings → Dashboard Preferences)

**Display**:
```
┌─────────────────────────────────────┐
│ TODAY'S HABITS (X/Y complete)      │
├─────────────────────────────────────┤
│ ☐ Brush teeth                       │
│ ☑ Take medication                   │
│ ☐ 5-minute meditation               │
│ ☐ Gym                               │
│ ☑ Drink water                       │
│                                      │
│ 📊 This week: 8/15 habits (53%)     │
└─────────────────────────────────────┘
```

**Interaction**:
- Click checkbox to mark habit complete for the day
- Checkbox toggles immediately (no confirmation)
- Completion triggers small celebration animation (confetti or checkmark)
- Sound plays (if enabled)

##### 3.9.5.C Habit Frequency & Flexibility

**Daily**:
- Reset every day at user's configured reset time (default midnight)
- Simple checkbox list each day

**Weekly (e.g., "3x/week")**:
- User specifies which days (e.g., "Gym: Mon, Wed, Fri")
- On those days, habit appears in daily list
- On off-days, habit hidden (not shown as incomplete; reduces guilt)
- Weekly progress: "You've completed 3/3 gym sessions this week! ✅"

**Custom (e.g., "Every other day")**:
- Advanced option; stores recurrence rule
- System calculates which days habit applies
- Shows on applicable days only

##### 3.9.5.D Habit Reset & Missed Days

**Daily Reset** (Nightly at user's timezone midnight):
- All habit checkboxes reset to unchecked
- Previous day's habits archived (still viewable in history)
- Reset message: "New day! Ready to build your habits?"

**Missed Day Handling**:
- If user doesn't check habit by midnight: Habitat marked incomplete for that day
- No "catch-up" window (clean break; avoids decision paralysis)
- Neutral message on next day: "Habit reset. New day, fresh start." (not punitive; no shame)
- Weekly progress still tracked (e.g., "2/7 days completed this week")

##### 3.9.5.E Weekly Progress Tracking

**Weekly Summary** (Display on dashboard or dedicated page):
```
┌─────────────────────────────────────┐
│ THIS WEEK'S HABITS                  │
├─────────────────────────────────────┤
│ Brush teeth:    6/7 days ⭐⭐⭐⭐⭐⭐ (86%) │
│ Meditation:     3/7 days ⭐⭐⭐ (43%)  │
│ Gym (3x/week):  3/3 days ✅         │
│                                      │
│ 📈 Overall: 12/17 habits (71%)      │
│                                      │
│ [View history]                       │
└─────────────────────────────────────┘
```

**Message Strategy** (Positive-only):
- Focus on accomplishments: "You've brushed your teeth 6 out of 7 days!"
- Never shame: ❌ "You missed meditation 4 times"
- Encouragement: "Keep up the gym routine — 3 weeks in a row!"

##### 3.9.5.F Habit Reminders (Optional)

**Integration with Section 3.8 (Reminders)**:
- User can optionally enable reminders for habits
- Reminder time: User-configured (e.g., "Meditation at 7:00 AM")
- Delivery: Email, SMS, or in-app bell (same as other reminders)
- Message: "Time for your habit: [Habit Name]"

##### 3.9.5.G Habit Database Schema

```sql
CREATE TABLE habits (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  habit_name VARCHAR(100),
  habit_description VARCHAR(500),
  frequency ENUM('daily', 'weekly', 'custom'),
  weekly_days SET('mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun') (for weekly/custom)
  recurrence_rule VARCHAR(255) (for custom; e.g., "every 2 days"),
  reminder_enabled BOOLEAN DEFAULT FALSE,
  reminder_time TIME,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  INDEX(user_id, created_at)
);

CREATE TABLE habit_completions (
  id INT PRIMARY KEY AUTO_INCREMENT,
  habit_id INT NOT NULL (FK to habits),
  user_id INT NOT NULL,
  completion_date DATE,
  completed_at TIMESTAMP,
  created_at TIMESTAMP,
  UNIQUE(habit_id, completion_date),
  INDEX(user_id, completion_date, habit_id)
);
```

---

#### 3.9.6 Analytics & Reports (Positive-Only Success Metrics)

##### 3.9.6.A Success Dashboard (User-Initiated)

**Access**: Navigation → "Reports" OR Dashboard → "View Analytics" link (optional widget)

**Purpose**: User-curated view of positive achievements. Encouragement tool, never report card.

**Metrics Displayed** (All positive, additive):

```
┌─────────────────────────────────────────────────────┐
│ YOUR SUCCESS DASHBOARD (This Week)                  │
├─────────────────────────────────────────────────────┤
│                                                      │
│ 📊 QUICK STATS                                      │
│ ─────────────────────────────────────────────────   │
│ • 23 tasks completed this week                      │
│ • 12 Pomodoro sessions tracked (6 hours 15 min)    │
│ • 5-day Pomodoro streak (Current!)                 │
│ • 2-day task completion streak (Current!)          │
│                                                      │
│ 🏆 BADGES THIS MONTH                                │
│ ─────────────────────────────────────────────────   │
│ • Week Warrior (earned Apr 9)                       │
│ • Task Master (earned Apr 16)                       │
│                                                      │
│ 📈 CATEGORY BREAKDOWN (This Month)                  │
│ ─────────────────────────────────────────────────   │
│ • Work: 18 tasks                                    │
│ • Health: 12 tasks                                  │
│ • Personal: 8 tasks                                 │
│                                                      │
│ [Download Report] [Print] [Share (optional)]       │
└─────────────────────────────────────────────────────┘
```

##### 3.9.6.B Trend Charts (Optional, Phase 2)

**If User Clicks "View Trends"**:
- Chart 1: Tasks completed per week (line graph, last 12 weeks)
- Chart 2: Pomodoro sessions per week (bar graph, last 12 weeks)
- Chart 3: Streak history (current streaks, all-time best)
- Chart 4: Category distribution (pie chart, tasks by category)

**No negative metrics**: Never show "tasks not completed" or incomplete percentages.

##### 3.9.6.C Download & Print Reports

**User Can Generate PDF**:
- Title: "[User Name]'s ADHD Dashboard Achievements Report"
- Date range: Selectable (This week / This month / Custom range)
- Contents: Quick stats + badges unlocked + category breakdown
- Format: Clean, printable, one-page or multi-page
- Use case: User wants to show therapist, parent, or coach progress

**File Generated**:
- On-demand (no spam; user initiates)
- Filename: "ADHD_Report_[User]_[DateRange].pdf"
- Emailed to user or downloaded directly (user choice)

##### 3.9.6.D Admin Dashboard (Optional System-Wide Analytics)

**Admin Access**: Admin Panel → "System Analytics" (optional feature; Phase 2+)

**Admin Can See**:
- ✅ Total tasks logged by all users (anonymized aggregate)
- ✅ Total Pomodoro sessions (system-wide)
- ✅ Most common task categories (system-wide trend)
- ✅ User count + engagement (% of users with activity this week)

**Admin Cannot See**:
- ❌ Individual user task details
- ❌ Specific user analytics (privacy-respecting)
- ❌ User personal data

**Purpose**: System health check (is app being used?), not user surveillance.

##### 3.9.6.E Analytics Database Schema

```sql
CREATE TABLE analytics_snapshots (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  snapshot_date DATE,
  tasks_completed_today INT,
  tasks_completed_week INT,
  tasks_completed_month INT,
  pomodoro_sessions_today INT,
  pomodoro_sessions_week INT,
  pomodoro_sessions_month INT,
  pomodoro_minutes_today INT,
  pomodoro_minutes_week INT,
  pomodoro_minutes_month INT,
  current_pomodoro_streak INT,
  best_pomodoro_streak INT,
  current_task_streak INT,
  best_task_streak INT,
  badges_earned_count INT,
  created_at TIMESTAMP,
  INDEX(user_id, snapshot_date)
);

-- Category-specific stats
CREATE TABLE analytics_by_category (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  category VARCHAR(100),
  snapshot_date DATE,
  tasks_completed INT,
  pomodoro_sessions INT,
  pomodoro_minutes INT,
  created_at TIMESTAMP,
  INDEX(user_id, snapshot_date, category)
);
```

---

#### 3.9.7 Gamification Phase 1 MVP Scope

**Included in Phase 1**:
✅ Completion animations (checkmark, confetti, success messages, sounds)
✅ Side Quests (random task selection, reroll, accept, quest completion celebration)
✅ Streaks (Pomodoro + task completion, 7-day milestones, display on dashboard)
✅ Badges (8 milestone badges, one-time unlocks except Side Quest Master weekly)
✅ Daily Habits (optional widget, daily checklist, weekly progress, no negative messaging)
✅ Quick analytics (user-initiated success dashboard, positive metrics only)

**Phase 2+ (Future)**:
🔄 Trend charts (multi-week/month history visualization)
🔄 PDF report generation & download
🔄 Achievement sharing (optional; "share my streak on dashboard")
🔄 Admin system-wide analytics
🔄 Leaderboards (opt-in community comparison; Phase 3+)
🔄 Custom badges (admin creates per-system achievements)
🔄 Habit analytics (habit tracking detailed reports)

---

---

## 4. USER INTERFACE & DESIGN

### 4.1 Theme & Color Palettes

#### 4.1.1 Color Palette Requirements
- **Accessibility**: WCAG 2.1 AA or higher compliance on all text
- **No Light Text on Light Backgrounds**: Contrast ratio ≥ 4.5:1 for normal text
- **Calming, ADHD-Friendly**: Reduce visual stress while maintaining engagement

#### 4.1.2 Pre-Defined Palettes (User Selectable)
1. **Ocean** (Cool, serene)
   - Primary: Navy (#1B3A6B)
   - Secondary: Teal (#2A9D8F)
   - Accent: Coral (#E76F51)
   - Background: Light Gray Blue (#F0F4F8)

2. **Forest** (Grounding, calming)
   - Primary: Dark Green (#2D5016)
   - Secondary: Sage (#6B8E23)
   - Accent: Gold (#D4A574)
   - Background: Light Green Gray (#F1F5F1)

3. **Sunset** (Warm, energizing)
   - Primary: Deep Purple (#4A235A)
   - Secondary: Warm Orange (#E67E22)
   - Accent: Coral Pink (#F39C12)
   - Background: Light Cream (#FEF9F3)

4. **Minimalist** (High contrast, clear)
   - Primary: Deep Charcoal (#2C3E50)
   - Secondary: Slate (#7F8C8D)
   - Accent: Bright Blue (#3498DB)
   - Background: Off-White (#ECF0F1)

#### 4.1.3 Color Application (ADHD-Friendly: No Anxiety-Inducing Colors)
- **Primary:** Headers, navigation, main UI elements
- **Secondary:** Card borders, secondary buttons, dividers
- **Accent:** Call-to-action buttons, highlights
- **Urgency Colors (For Task Borders & Indicators):**
  - **BRIGHT YELLOW (#FFB300)**: Due today or overdue — **NOT RED** (red causes anxiety)
  - **BRIGHT ORANGE (#FF9F43)**: Due within 3 days — warm, inviting
  - **CALMING PINK/TURQUOISE (#B8E0D2 or soft teal)**: Due within week or no deadline — soothing
  - **Note:** All colors warm and inviting, never harsh or anxiety-inducing
- **Status Colors (General):**
  - **Success:** Green (#27AE60) — positive completion feedback
  - **In Progress:** Blue (#3498DB) — task is active/being worked on
  - **Disabled:** Light Gray (#BDC3C7) — unavailable state
- **Design Principle:** Reduce cognitive/emotional load; support ADHD executive functioning

---

### 4.2 Component Styling Approach & Framework

**Decision: Custom CSS Grid + CSS Custom Properties (No Bootstrap)**

**Rationale**:
- Maintain clean, maintainable code without framework bloat
- CSS variables enable easy theme switching (store theme in database; inject on page load)
- Custom grid for responsive design (mobile-first breakpoints: 380px / 576px / 768px / 992px / 1200px)
- Easier for you (PHP dev) to understand and modify; fewer dependencies

**CSS Architecture** (External files, not inline):
```
css/
├── main.css              # Base typography, global resets, CSS variables (ADHD color palette)
├── components.css        # Buttons, cards, forms, inputs, modals, badges
├── layout.css            # Grid system, navigation, responsive breakpoints, sidebar/header
├── animations.css        # Task completion animations, transitions, keyframes
├── timer.css             # Pomodoro timer visual (ring, fullscreen layout)
└── responsive.css        # Media queries for tablet/mobile adjustments (or inline in above files)
```

**CSS Custom Properties** (Theme Variables; set globally):
```css
:root {
  /* ADHD-Friendly Color Palette - Set per theme */
  --primary-color: [theme-dependent];
  --secondary-color: [theme-dependent];
  --accent-color: [theme-dependent];
  --background-color: [theme-dependent];
  
  /* Urgency Indicators (Consistent across all themes; never red) */
  --urgent-yellow: #FFB300;        /* Due today/overdue */
  --bright-orange: #FF9F43;         /* 3-day window */
  --calming-pink: #B8E0D2;          /* Future/calm color */
  --light-gray-neutral: #E8E8E8;    /* Low priority/disabled */
  
  /* Status Colors */
  --status-success: #27AE60;        /* Task complete */
  --status-in-progress: #3498DB;    /* Active task */
  --status-disabled: #BDC3C7;       /* Unavailable state */
  
  /* Typography */
  --font-family-base: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  --font-size-base: 16px;
  --line-height-base: 1.5;
  
  /* Spacing & Layout */
  --spacing-unit: 8px;              /* 8px grid */
  --border-radius-sm: 4px;
  --border-radius-md: 8px;
  --border-radius-lg: 12px;
  
  /* Shadows */
  --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.1);
  --shadow-md: 0 4px 8px rgba(0, 0, 0, 0.15);
  --shadow-lg: 0 8px 16px rgba(0, 0, 0, 0.2);
}
```

**Theme Implementation** (PHP/JavaScript):
1. Store user's theme preference in database: `user_preferences.selected_theme` (default: 'Ocean')
2. On page load, fetch user's theme from DB via PHP
3. In `<head>` PHP snippet: `<meta name="theme" content="<?= $userTheme ?>">`
4. JavaScript on load: `applyTheme(userTheme)` → Inject CSS custom property values for that theme
5. User changes theme in Settings → AJAX call to `/api/users.php?action=update_theme` → Database updated → Page reloads OR theme CSS re-injected via JS

---

### 4.3 Task Card Visual Design & Hierarchy

##### 4.3.1 Dashboard 1-3-5 Task Cards (Minimal, Action-Focused)

**Card Layout** (Desktop, 1 card per task):
```
┌─────────────────────────────────────────┐
│ [Due Date Badge] Task Title  [✓ Complete] │
│ Category  |  Priority dot  |  Ticket link  │
│ [HOVER: Description preview + extra opts] │
└─────────────────────────────────────────┘
```

**Components** (In order, visual weight):
1. **Due Date Badge** (Left-most; color-coded urgency):
   - Today: Bright Yellow background (#FFB300), black text, "TODAY" or clock icon
   - 1-3 days: Bright Orange background (#FF9F43), black text, "In X days"
   - Future: Calming Pink background (#B8E0D2), dark text, "Apr 5"
   - No deadline: Light gray background (#E8E8E8), dark text, empty/blank

2. **Task Title** (Large, bold, clickable):
   - Font-weight: Bold (700)
   - Font-size: 16px (base; scales up on desktop)
   - Truncate to 1 line with ellipsis if > 60 chars
   - Click to open task detail

3. **Priority Dot** (Small indicator, right of title):
   - Dot size: 8px
   - Urgent (High): Bright Yellow (#FFB300)
   - High: Bright Orange (#FF9F43)
   - Medium: Calming Pink (#B8E0D2)
   - Low: Light Gray (#E8E8E8)

4. **Complete Button** (Right-most; always accessible):
   - Checkbox or [✓] button
   - Size: 24x24px (clickable touch target)
   - Hover: Highlight background
   - Click → Trigger completion animation

5. **Category & Linked Ticket** (Sub-text row, smaller font):
   - Category badge: Light background, colored text
   - Separator: " | "
   - Linked ticket link: "Part of: [Ticket Name]" (if applicable)
   - Font-size: 12px
   - Color: Secondary/gray

**Mobile Card** (Stack vertically to fit narrow screen):
```
┌──────────────────────┐
│ [Badge] Task Title   │
│ [✓]                   │
│                       │
│ Category | Ticket     │
└──────────────────────┘
```

**Hover/Tap Expansion** (Desktop on hover; Mobile on long-press or tap):
- Show description preview (first 100 chars)
- Quick actions: [Snooze 1 day] [Move to...] [Delete]
- Context menu (desktop right-click OR mobile three-dot menu)

---

### 4.4 Focus Mode Visual Design (Fullscreen Pomodoro)

**Focus Mode Theme: Dark Mode (Reduced Eye Strain)**

**Layout** (Fullscreen, centered):
```
┌────────────────────────────────────────┐
│                                        │
│   [Close X]          Focus Mode        │
│                                        │
│        ⏱️ 25:00 (Large Timer Ring)    │
│                                        │
│   Round 1 of 4                         │
│   [Current Task: Task Title]           │
│                                        │
│   Completed Focus Time: 3h 45m         │
│                                        │
│                                        │
│   [Pause] [Extended Focus] [Finish]  │
│                                        │
│                                        │
└────────────────────────────────────────┘
```

**Colors (Focus Dark Mode)**:
- Background: Very dark (#1a1a1a) with slight gradient
- Timer ring: Accent color (from theme) glowing effect
- Text: White (#ffffff) with soft glow
- Buttons: Bright accent color (contrasts on dark background)

**Configuration** (Settings → Pomodoro Preferences; single location):
- Checkbox: "Dark mode during focus" [✓] (default ON)
- Toggleable: User can try light mode if preferred
- No UI duplication; setting stored in database

**Timer Ring Visual** (CSS SVG animation):
- Circular progress ring (circumference represents 25 minutes)
- Animated stroke-dasharray on each second elapsed
- Color: Accent color; glows on animation
- Large, readable (min 200px diameter on desktop)

---

### 4.5 Inbox vs. Backlog Visual Distinction

**Task System Organization**:

**Inbox Tab** (New/Unsorted Tasks):
- Visual prominence: Bright card backgrounds
- Icon: 📥 (inbox icon)
- Message: "Drag tasks to organize [into Backlog / into Daily / into Projects]"
- Cards have action buttons: [Assign to Daily] [Move to Backlog] [Quick Add to Project]
- Purpose: Clear visual urgency; user decides where each task lives

**Backlog Tab** (Someday Maybe / Future):
- Visual weight: Softer colors (grayed out or muted background)
- Icon: 📚 (backlog/library icon)
- Cards less visually prominent; faded opacity (0.7)
- Purpose: Future decisions; don't distract from current focus
- Cards have action buttons: [Promote to Daily] [View Details] [Delete]

**Tab Navigation** (Tasks page):
```
┌─────────────────────┬──────────────────┐
│ 📥 INBOX (3)       │ 📚 BACKLOG (12)   │
├─────────────────────┴──────────────────┤
│ [Bright, urgent task cards]            │
│                    OR                   │
│ [Faded, future task cards]             │
└────────────────────────────────────────┘
```

---

### 4.6 Form Design: Labels, Input Layout, Mobile

**Desktop Form Layout** (Labels above inputs):
```
Label (Required *)
[Input field - 80% width, clear focus state]
Inline helper text or validation message

Label
[Select dropdown]
```

**Input Field Styling**:
- Border: 1px solid secondary color
- Border-radius: 6px
- Padding: 12px 16px (inside input; comfortable touch targets)
- Font-size: 16px (prevents iOS zoom on input focus)
- Focus state: Border color changes to accent, shadow + glow effect
- Error state: Border color = error red (#E74C3C), error message inline below

**Error & Validation Messages**:
- Display inline below field (NOT in placeholder, NOT in tooltip/modal)
- Color: Error red (#E74C3C)
- Font-size: 12px
- Icon: Small ❌ indicator
- Example: "❌ Email address is invalid"

**Mobile Form Layout** (Full-width, single column):
- Input fields: 100% width (minus padding)
- Labels: Always above (not floating; too fragile for ADHD users who skip details)
- Helper text: Visible always (not on focus-only)
- Buttons: Full-width or side-by-side if space allows
- Keyboard: Dismiss on Enter or Confirm button

**Complex Inputs** (e.g., datetime, dropdowns):
- Datetime: Mobile uses native date/time picker (OS-native, accessible)
- Dropdowns: Always show all visible options on mobile (no search initially; can add if > 10 options)
- Multi-select: Checkboxes instead of dropdowns (clearer)

---

### 4.7 File Manager Visual Organization

**Desktop Layout** (Two-pane):
```
┌─────────────────────┬──────────────────────┐
│ Folder Tree (Left)  │ File Grid (Right)    │
│                      │                      │
│ 📁 General          │ [File1] [File2]      │
│ 📁 _tasks           │ [File3] [File4]      │
│ 📁 _crud            │ [File5] [File6]      │
│ 📁 Projects         │                      │
│ 📁 Personal         │                      │
└─────────────────────┴──────────────────────┘
```

**Mobile Layout** (Single-pane + breadcrumb):
```
┌────────────────────────────────────┐
│ [←] General > Projects  [⋯]        │ (Breadcrumb)
├────────────────────────────────────┤
│ [Folder1]   [Folder2]              │ (2-column grid)
│ [Folder3]   [File1]                │
│ [File2]     [File3]                │
└────────────────────────────────────┘
```

**Breadcrumb Navigation** (Mobile):
- Shows current path: `[← Back] General > Projects > Subfolder`
- [←] button goes up one level
- Each part clickable to jump to ancestor folder
- [⋯] menu for folder actions (rename, move, delete)

---

### 4.8 Icon Consistency & Library Strategy

**Icon Decision: Hybrid Approach**

| Use Case | Icon Type | Rationale |
|----------|-----------|-----------|
| **Gamification (Badges, Streaks, Quest Icons)** | Emoji | Fun, celebratory; matches ADHD engagement goal |
| **Navigation (Sidebar, Main Menu)** | SVG or text labels | Consistent, professional; pixel-perfect |
| **Status Indicators (Complete, In Progress, Disabled)** | SVG | Accessibility + consistency |
| **UI Controls (Buttons, Close, Menu)** | SVG (material-design style) | Clear, universally recognized |
| **Task Priority/Urgency** | Colored dots (CSS) | Minimalist; no image dependencies |
| **Category Labels** | Text + colored badge | Scalable, easy to customize |

**Implementation** (No external font library):
- SVG icons: Create simple SVG sprite sheet (one file with all icons; CSS background-size to show each)
- Emoji: Use Unicode directly in HTML (no external dependencies)
- Fallback: Text labels if icon fails to load (accessibility)

**Icon Color Strategy**:
- Icons inherit from text color by default (dark text on light background)
- Status icons: Use color variables (green for success, blue for progress, etc.)
- Always maintain contrast ratio ≥ 4.5:1 (WCAG AA)

---

### 4.9 Loading States & Skeleton Screens

**When to Use Each**:

**Skeleton Screens** (For list/grid views; faster perceived load):
- Task list loading: Show 5 fake task cards with gray placeholder boxes
- File grid loading: Show 8 fake file cards (thumbnail + name placeholder)
- Animation: Subtle shimmer effect (opacity fade 0.6 → 1.0 → 0.6 loop)
- Duration: Usually 300-800ms on dev machine; feels faster to user

**Spinner Overlays** (For modals/single-item loads):
- Centered spinner with label: "Loading..." or "Saving..."
- Semi-transparent background behind spinner
- Only for < 2 second waits (if longer, show progress steps)

**Button Disable State** (For form submissions):
- After user clicks submit: Disable button (change opacity 0.6, cursor: not-allowed)
- Show button loading state: "Saving..." or spinner inside button
- Re-enable on response (success or error)

**Implementation** (CSS + JavaScript):
```css
/* Skeleton shimmer animation */
@keyframes shimmer {
  0% { opacity: 0.6; }
  50% { opacity: 1; }
  100% { opacity: 0.6; }
}
.skeleton { animation: shimmer 1.5s infinite; background-color: var(--background-secondary); }
```

---

### 4.10 Accessibility: Color Mode Support (Dark Mode)

**Browser Preference Detection** (`prefers-color-scheme`):
- CSS media query: `@media (prefers-color-scheme: dark) { ... }`
- On first visit, detect user's OS dark mode preference
- Auto-select appropriate theme palette (desert-compatible dark theme)
- User can override in Settings → Appearance → "Light / Dark / Auto (follow system)"

**Theme Palettes (Light + Dark Versions)** (All 4 themes):

1. **Ocean** (Light & Dark)
   - Light: Navy primary, light gray blue background (existing)
   - Dark: Light teal primary, very dark background (#0a1520)

2. **Forest** (Light & Dark)
   - Light: Dark green primary, light green gray background (existing)
   - Dark: Light sage primary, very dark background (#0a1510)

3. **Sunset** (Light & Dark)
   - Light: Deep purple primary, light cream background (existing)
   - Dark: Coral accent primary, very dark background (#1a0f20)

4. **Minimalist** (Light & Dark)
   - Light: Charcoal primary, off-white background (existing)
   - Dark: Bright blue accent, very dark background (#0f0f0f)

**Dark Mode Implementation** (CSS):
```css
@media (prefers-color-scheme: dark) {
  :root {
    --background-color: #1a1a1a;
    --text-color: #f5f5f5;
    --secondary-color: #333;
    /* ... all other colors inverted or adjusted for dark ... */
  }
}
```

---

### 4.11 Keyboard Shortcuts & Discovery

**Core Keyboard Shortcuts** (Desktop only; optional, not required):

| Shortcut | Action | Where |
|----------|--------|-------|
| `?` | Show keyboard shortcuts help | Global (any page) |
| `Ctrl + K` | Open quick capture modal | Global |
| `Ctrl + /` | Focus search bar | Global |
| `G` then `D` | Go to Dashboard | Global (vim-style) |
| `G` then `T` | Go to Tasks | Global |
| `G` then `P` | Go to Projects | Global |
| `Enter` | Submit form | On form focus |
| `Esc` | Close modal/escape | On modal/popover |

**Discovery UI** (Help Modal):
- Trigger: User presses `?` on any page
- Modal shows: All available shortcuts + descriptions
- Message: "Keyboard shortcuts are optional. You don't need them to use the app." (accessibility-first)
- Closeable on Esc

**Implementation** (JavaScript; not invasive):
```javascript
// Listen for keyboard events; check if user wants shortcuts
document.addEventListener('keydown', (e) => {
  if (e.key === '?') showShortcutsModal();
  if (e.ctrlKey && e.key === 'k') openQuickCapture();
  // ... etc ...
});
```

---

### 4.12 Error & Success Message Display (Toast Notifications)

**Toast Notification System** (Unobtrusive feedback):

**Success Toast** (Green, auto-dismiss):
- Background: Success green (#27AE60)
- Text: White
- Icon: Checkmark ✓
- Message: "Task created!", "File uploaded!", etc.
- Duration: 3-4 seconds (auto-dismiss)
- Position: Bottom-right desktop, top-center mobile
- Animation: Slide-in from bottom/top (200ms)

**Error Toast** (Warm red, persistent):
- Background: Warm error red (#E74C3C; NOT bright red; reduces anxiety)
- Text: White
- Icon: ❌ Warning
- Message: Specific error (NOT generic "Error" message)
- Include retry button (if applicable)
- Duration: 5-6 seconds (longer than success; user can dismiss manually)
- Position: Bottom-right desktop, top-center mobile

**Info Toast** (Blue, auto-dismiss):
- Background: Info blue (#3498DB)
- Message: "Reminder: Task due tomorrow", etc.
- Duration: 4 seconds

**Implementation** (CSS + JavaScript; no external library):
```javascript
function showToast(message, type = 'info', duration = 4000) {
  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.textContent = message;
  document.body.appendChild(toast);
  
  setTimeout(() => toast.remove(), duration);
}

// Usage:
showToast('Task created!', 'success');
showToast('Error: File too large', 'error', 6000);
```

**Toast Stacking** (Multiple toasts):
- Never show > 3 toasts simultaneously (prevents overwhelming user)
- Stack vertically (bottom-right: 1st at bottom, 2nd 10px above, 3rd above that)
- New toasts push old ones up slightly

---

### 4.13 Settings Architecture (Single Source of Truth)

**Principle: Each setting lives in ONE place only (never duplicated across UI)**

**Settings Hierarchy** (All in Profile → Settings; never elsewhere):

```
Profile (User Icon) → Settings
├─ Account
│  ├─ Email address
│  ├─ Username
│  ├─ Password
│  └─ Account deletion
│
├─ Appearance
│  ├─ Theme selector (Light/Dark/Auto + Ocean/Forest/Sunset/Minimalist)
│  ├─ Font size (Default / Large / Extra Large) [Optional Phase 2]
│  └─ Dashboard widget preferences (Show Streaks, Show Recent Badges, etc.)
│
├─ Notifications
│  ├─ Email delivery mode (Immediate / Digest)
│  ├─ SMS phone number & verification
│  ├─ SMS delivery mode (Immediate / Digest) [if verified]
│  ├─ Quiet hours (start/end times)
│  ├─ Urgent reminders bypass quiet hours [checkbox]
│  └─ Per-reminder-type customization (Task Due, Medication, Bill, etc.)
│
├─ Pomodoro
│  ├─ Session duration (15 / 20 / 25 / 30 min)
│  ├─ Break duration (5 / 10 / 15 min)
│  ├─ Dark mode during focus [checkbox]
│  ├─ Sound enable/disable [checkbox]
│  └─ Sound type selector (Chime / Bell / Ding)
│
├─ Daily Habits
│  ├─ Enable daily habits widget [checkbox]
│  ├─ Reset time (Midnight / Custom time)
│  └─ Habit reminders enable [checkbox]
│
├─ Task Defaults
│  ├─ Default task priority (Medium / High / Low)
│  ├─ Default reminder days before due
│  └─ Rescheduling preference (Auto / Manual)
│
├─ Privacy & Data
│  ├─ Data export (generate dump)
│  ├─ Delete all data [confirm button]
│  └─ Login history [view-only]
│
└─ Developer
   └─ API keys [if applicable; Phase 2+]
```

**Single-Place Rule Examples**:
- ❌ Theme selector NOT in header navbar + NOT in top-right menu (confusing)
- ✅ Theme selector ONLY in Profile → Settings → Appearance
- ❌ Notification preferences NOT scattered across multiple pages
- ✅ All notification settings ONLY in Profile → Settings → Notifications
- ❌ Pomodoro settings NOT in timer page itself
- ✅ Pomodoro settings ONLY in Profile → Settings → Pomodoro

---

### 4.14 Responsive Breakpoints & Mobile-First Strategy

**Breakpoints** (Mobile-first; build for mobile first, then enhance):
- **Mobile** (0-575px): Single column, max-width 100% minus padding
- **Tablet** (576px-991px): 2-column grid where appropriate
- **Desktop** (992px+): Multi-column, sidebar + main content

**Font Sizing** (Scales based on viewport):
- Base: 16px (mobile) → 18px (desktop)
- Headings: 24px (h1 mobile) → 32px (h1 desktop)
- Small text: 12px (mobile) → 14px (desktop)

**Touch Targets** (Mobile):
- Minimum 44x44px for clickable elements
- Spacing between buttons: ≥ 8px (prevent accidental double-clicks)
- Long-press for context menus (instead of right-click)

**Viewport Meta Tag** (Ensure responsive rendering):
```html
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
```

---

### 4.15 Component Library Summary

**Reusable Components** (Build once, use everywhere; maintain in separate PHP files):

| Component | File | Usage |
|-----------|------|-------|
| Task Card | `components/task-card.php` | Dashboard, task list, side quest |
| Button | `components/button.php` | All pages (variants: primary, secondary, danger, disabled) |
| Badge | `components/badge.php` | Priority, category, status, streak count |
| Modal | `components/modal.php` | Confirmations, forms, details |
| Form Field | `components/form-field.php` | Inputs, labels, validation messages |
| Alert | `components/alert.php` | Success, error, warning messages (toast) |
| Breadcrumb | `components/breadcrumb.php` | Navigation hierarchy |
| Dropdown | `components/dropdown.php` | Navigation, quick actions |
| Progress Ring | `components/progress-ring.php` | Timer visual, progress tracking |

---

### 4.16 Phase 1 UI/Design Scope

**Included in Phase 1**:
✅ Color palettes (4 themes, light + dark versions, CSS variables)
✅ Responsive design (mobile-first, 3 breakpoints)
✅ Component library (task cards, forms, buttons, modals, toasts)
✅ Task card minimalist design (due badge, title, priority dot, complete button)
✅ Focus mode fullscreen (dark theme, timer ring, centered layout)
✅ Inbox vs. Backlog visual distinction (bright vs. faded)
✅ Form design (labels above, clear inputs, inline validation)
✅ File manager two-pane + mobile breadcrumb
✅ Icon strategy (hybrid: emoji + SVG)
✅ Loading states (skeleton screens + spinners)
✅ Keyboard shortcuts (7 core shortcuts, optional)
✅ Toast notifications (success/error/info, auto-dismiss)
✅ Settings architecture (single source of truth)
✅ Notification bell UI (dropdown, badges, quick actions)
✅ Accessibility (dark mode, color contrast, keyboard navigation)

**Phase 2+ (Future)**:
🔄 Custom font sizing adjustable in settings
🔄 High contrast mode (WCA AAA support)
🔄 Animated onboarding tutorial
🔄 Data export UI
🔄 User management dashboard (admin)
🔄 Advanced theme customization (user-defined color palettes)

---

---

## 5. TECHNICAL ARCHITECTURE

### 5.1 Technology Stack

| Layer | Technology | Rationale |
|-------|-----------|-----------|
| **Backend** | PHP 8.x (PDO) | Developer comfort; PDO ensures clean, maintainable database access |
| **Frontend** | HTML5, CSS3, Vanilla JavaScript (minimal frameworks) | Minimize dependencies; easier maintenance |
| **Database** | MySQL/MariaDB | Single unified database (adhd-dashboard) |
| **File Storage** | `/private/` directory | Outside public_html; secure, not version-controlled |
| **Email** | PHP mail + SMTP wrapper | Centralized via application config |
| **Optional** | Bootstrap 5 + Responsive Grid | Optional CSS framework for rapid mobile-responsive UI |

### 5.2 Project Directory Structure

```
adhd-dashboard/
├── private/                           # Outside public_html (not pushed to GitHub)
│   ├── config/
│   │   └── config.php                 # DB credentials, email settings, app constants
│   ├── uploads/                       # User file uploads
│   │   └── {user_id}/
│   └── logs/                          # Error and activity logs
│       ├── error.log
│       └── activity.log
│
├── public_html/                       # Pushed to GitHub
│   ├── index.php                      # Main entry point (routes to dashboard)
│   ├── .htaccess                      # URL rewriting for clean URLs
│   ├── api/                           # API endpoints (JSON responses)
│   │   ├── tasks.php                  # CRUD for tasks
│   │   ├── projects.php               # Projects management
│   │   ├── tickets.php                # Tickets & resources management
│   │   ├── reminders.php              # Reminder queries
│   │   ├── notifications.php          # Notification endpoints
│   │   ├── users.php                  # User management (admin only)
│   │   ├── crud.php                   # CRUD table operations
│   │   ├── files.php                  # File operations
│   │   └── auth.php                   # Login/logout
│   │
│   ├── views/                         # HTML templates
│   │   ├── layout/
│   │   │   ├── header.php             # Navigation, theme, constants
│   │   │   ├── sidebar.php            # Navigation menu
│   │   │   ├── footer.php             # Footer, scripts
│   │   │   └── notifications.php      # Notification bell component
│   │   ├── pages/
│   │   │   ├── dashboard.php          # Main dashboard
│   │   │   ├── tasks.php              # Task list/filter
│   │   │   ├── task-detail.php        # Single task view/edit
│   │   │   ├── inbox.php              # Inbox/backlog view
│   │   │   ├── timer.php              # Pomodoro timer
│   │   │   ├── projects.php           # Projects list (tabs: Review Soon, Open, Paused, Closed)
│   │   │   ├── project-detail.php     # Single project view (all tickets)
│   │   │   ├── ticket-detail.php      # Single ticket detail (left: info; right: notes)
│   │   │   ├── ticket-resources.php   # Manage ticket resources (files, URLs, CRUD links)
│   │   │   ├── crud-list.php          # CRUD table view
│   │   │   ├── crud-form.php          # CRUD form (add/edit)
│   │   │   ├── files.php              # File manager
│   │   │   ├── profile.php            # User profile/settings
│   │   │   ├── admin.php              # Admin panel
│   │   │   ├── login.php              # Login page
│   │   │   └── reports.php            # Success analytics (optional)
│   │   └── components/
│   │       ├── task-card.php          # Reusable task card
│   │       ├── task-form.php          # Task form component
│   │       ├── timer-display.php      # Timer visual component
│   │       ├── modal.php              # Generic modal template
│   │       └── alerts.php             # Success/error message component
│   │
│   ├── public/
│   │   ├── css/
│   │   │   ├── main.css               # Base styles, variables, theme
│   │   │   ├── components.css         # Button, card, form styles
│   │   │   ├── layout.css             # Grid, navigation, responsive
│   │   │   ├── timer.css              # Pomodoro timer styles
│   │   │   └── themes.css             # Palette overrides (optional CSS )
│   │   ├── js/
│   │   │   ├── main.js                # Global utils, event handlers
│   │   │   ├── dashboard.js           # Dashboard-specific logic
│   │   │   ├── tasks.js               # Task CRUD interactions
│   │   │   ├── timer.js               # Pomodoro timer logic
│   │   │   ├── notifications.js       # Notification bell logic
│   │   │   ├── modal.js               # Modal open/close
│   │   │   ├── forms.js               # Form validation, submission
│   │   │   └── date-utils.js          # Date/timezone helpers
│   │   ├── images/                    # Icons, logos
│   │   └── fonts/                     # Custom fonts (if any)
│   │
│   ├── lib/                           # Shared PHP libraries
│   │   ├── Database.php               # PDO wrapper class
│   │   ├── Auth.php                   # Authentication functions
│   │   ├── Task.php                   # Task class/functions
│   │   ├── Project.php                # Project class/functions
│   │   ├── Ticket.php                 # Ticket class/functions
│   │   ├── TicketResource.php         # Ticket resource management (files, URLs, CRUD)
│   │   ├── Reminder.php               # Reminder logic
│   │   ├── Notification.php           # Notification class
│   │   ├── CRUDManager.php            # Custom CRUD table handler
│   │   ├── FileManager.php            # File operations
│   │   ├── Email.php                  # Email sending wrapper
│   │   ├── Logger.php                 # Debug/activity logging
│   │   ├── Security.php               # Input validation, sanitization
│   │   ├── Response.php               # JSON response formatter
│   │   └── Utilities.php              # Helper functions
│   │
│   ├── migrations/                    # Database migrations (one-time setup)
│   │   └── 001_init_schema.php        # Initial schema creation
│   │
│   └── SPECIFICATION.md               # This file (in public_html for reference)
│
└── adhd-dashboard.code-workspace      # VS Code workspace file
```

---

### 5.3 Database Schema (High-Level)

#### 5.3.1 Core Tables
1. **users**
   - id, email (unique), password_hash, full_name, profile_picture, timezone, theme, created_at, updated_at, is_active, role

2. **tasks**
   - id, user_id (FK), title, description, due_date, priority, category, status (inbox/backlog/scheduled/active/completed/archived/deleted), recurring (null/daily/weekly/monthly), estimated_minutes, created_at, updated_at, archived_at, completed_at

3. **task_assignments**
   - id, task_id (FK), assigned_to_user_id (FK), assigned_by_user_id (FK), created_at

4. **task_files**
   - id, task_id (FK), file_path, original_filename, delete_on_completion, uploaded_at

5. **priorities_daily** (1-3-5 display for a specific date)
   - id, user_id (FK), task_id (FK), priority_slot (1-9), date, created_at

6. **reminders**
   - id, user_id (FK), related_item_id, related_item_type (task/medication/bill/custom), reminder_type (due_date/recurring/refill/custom), due_at, frequency, is_sent, sent_at, created_at

7. **notifications**
   - id, user_id (FK), title, message, type (task/reminder/assignment/file_share), related_item_id, is_read, created_at, read_at

8. **crud_tables**
   - id, user_id (FK), name, slug, description, icon, is_template (bool), is_shared (bool), created_at

9. **crud_fields**
   - id, crud_table_id (FK), field_name, field_type (text/number/email/date/checkbox/dropdown/file), field_label, is_required, field_options (JSON), sort_order, created_at

10. **crud_records**
    - id, crud_table_id (FK), user_id (FK), entry_data (JSON), created_at, updated_at

11. **projects**
    - id, user_id (FK), name, description, created_at

12. **tickets** (Project portal/sub-project)
    - id, user_id (FK), project_id (FK), title, description, status (new/open/paused/closed), priority (urgent/high/medium/low), category, review_date (nullable), created_at, updated_at, closed_at (nullable)

13. **ticket_resources**
    - id, ticket_id (FK), resource_type (file/url/crud_record/contact/note), resource_data (JSON: stores file path, URL, CRUD record ID, contact info, or note text), sort_order, created_at

14. **ticket_comments** (Activity log + notes)
    - id, ticket_id (FK), user_id (FK), comment_text, status_changed_from (nullable), status_changed_to (nullable), created_at

15. **ticket_tasks** (Linking tasks to tickets)
    - id, ticket_id (FK), task_id (FK), created_at

16. **pomodoro_sessions** (Optional; only if logging enabled)
    - id, user_id (FK), task_id (FK, nullable), duration_minutes, started_at, completed_at

17. **user_settings**
    - id, user_id (FK), setting_key (string), setting_value (JSON), created_at, updated_at

15. **activity_log** (Optional; for debugging)
    - id, user_id (FK), action, details (JSON), ip_address, created_at

---

### 5.4 Code Organization Principles

#### 5.4.1 Separation of Concerns
- **Views**: HTML templates only; no business logic
- **API**: JSON endpoints; handle validation, authorization, database operations
- **Libraries**: Reusable classes and functions; no direct I/O
- **Database**: Single PDO wrapper class; all queries go through it

#### 5.4.2 DRY (Don't Repeat Yourself)
- CSS variables for colors, spacing, fonts
- Reusable PHP functions for common operations
- Component-based views for repeated elements
- Configuration constants in `/private/config/config.php`

#### 5.4.3 No Hardcoded Values
- All configuration: `/private/config/config.php`
- All constants: Defined in config or as class constants
- All colors: CSS variables
- All text strings: EasyLocale or simple constants for future i18n

#### 5.4.4 Security Best Practices
- All database queries via prepared statements (PDO)
- Input sanitization via Security.php
- Output escaping in templates
- CSRF tokens on forms
- Password hashing via PHP password_hash()
- Session security (HTTPOnly, Secure flags)

---

### 5.5 API Endpoints (JSON)

All endpoints return JSON. Base path: `/public_html/api/`

| Method | Endpoint | Purpose | Auth Required |
|--------|----------|---------|---|
| POST | /auth/login | Login | No |
| POST | /auth/logout | Logout | Yes |
| GET | /tasks | List user's tasks | Yes |
| POST | /tasks | Create task | Yes |
| GET | /tasks/{id} | Get task details | Yes |
| PUT | /tasks/{id} | Update task | Yes |
| DELETE | /tasks/{id} | Delete task | Yes |
| POST | /tasks/{id}/complete | Mark complete | Yes |
| POST | /tasks/{id}/reschedule | Move to another date | Yes |
| GET | /priorities | Get today's 1-3-5 | Yes |
| POST | /priorities | Set 1-3-5 | Yes |
| GET | /reminders | Get pending reminders | Yes |
| POST | /reminders/{id}/mark-sent | Mark reminder as sent | Yes (Admin) |
| GET | /notifications | Get user's notifications | Yes |
| POST | /notifications/{id}/read | Mark notification as read | Yes |
| GET | /crud-tables | List CRUD tables | Yes |
| GET | /crud-tables/{id}/records | List records in table | Yes |
| POST | /crud-tables/{id}/records | Create record | Yes |
| PUT | /crud-records/{id} | Update record | Yes |
| DELETE | /crud-records/{id} | Delete record | Yes |
| GET | /projects | List all user's projects | Yes |
| POST | /projects | Create project | Yes |
| PUT | /projects/{id} | Update project | Yes |
| GET | /projects/{id}/tickets | List tickets in project | Yes |
| GET | /tickets | List user's tickets (all projects) | Yes |
| POST | /tickets | Create ticket | Yes |
| GET | /tickets/{id} | Get ticket details + resources | Yes |
| PUT | /tickets/{id} | Update ticket (status, priority, review date) | Yes |
| DELETE | /tickets/{id} | Delete/archive ticket | Yes |
| POST | /tickets/{id}/resources | Add resource to ticket | Yes |
| DELETE | /tickets/{id}/resources/{resource_id} | Remove resource | Yes |
| POST | /tickets/{id}/comments | Add comment/note to ticket | Yes |
| GET | /tickets/{id}/tasks | Get tasks linked to ticket | Yes |
| POST | /tickets/{id}/tasks | Link existing task to ticket | Yes |
| DELETE | /tickets/{id}/tasks/{task_id} | Unlink task from ticket | Yes |
| GET | /files | List user's files | Yes |
| POST | /files/upload | Upload file | Yes |
| DELETE | /files/{id} | Delete file | Yes |
| GET | /users | List all users (Admin only) | Yes (Admin) |
| POST | /users | Create user | Yes (Admin) |
| PUT | /users/{id} | Update user | Yes (Admin) |
| DELETE | /users/{id} | Deactivate user | Yes (Admin) |
| GET | /settings | Get system settings | Yes (Admin) |
| PUT | /settings | Update settings | Yes (Admin) |

---

### 5.6 API Response Format & Error Handling (JSON Contract)

**Standard JSON Response Format** (All endpoints):
```json
{
  "success": true,
  "data": { /* Response data or null */ },
  "error": null,
  "timestamp": "2026-04-02T14:30:00Z"
}
```

**Success Response** (HTTP 200 or 201):
```json
{
  "success": true,
  "data": {
    "id": 123,
    "title": "Task Title",
    "due_date": "2026-04-05"
  },
  "error": null,
  "timestamp": "2026-04-02T14:30:00Z"
}
```

**Error Response** (HTTP 400, 401, 404, 500):
```json
{
  "success": false,
  "data": null,
  "error": "Descriptive error message (never expose SQL/stack trace to client)",
  "timestamp": "2026-04-02T14:30:00Z"
}
```

**Error Logging vs. Client Response**:
- Client always sees generic error: "An error occurred. Please try again."
- Detailed exception logged to `/private/logs/error.log` with full stack trace
- Example: Database connection fails → Client receives `{ "success": false, "error": "An error occurred..." }` → Server logs full exception with line numbers

**HTTP Status Codes** (Semantic, paired with JSON):
| Scenario | HTTP Status | JSON Success | Error Message |
|----------|------------|-------------|--------|
| Create succeeded | 201 | true | null |
| Fetch succeeded | 200 | true | null |
| Update succeeded | 200 | true | null |
| Delete succeeded | 204 | - | - (no body) |
| Validation failed | 400 | false | "Invalid email format" |
| Not authenticated | 401 | false | "Not authenticated" |
| Not authorized | 403 | false | "Insufficient permissions" |
| Resource not found | 404 | false | "Task not found" |
| Server error | 500 | false | "An error occurred..." |

---

### 5.7 Authentication & Session Management (Session + CSRF)

**Session-Based Authentication** (Stateful; simpler for PHP):

**Login Flow**:
1. User submits POST `/api/auth/login` with email + password
2. Server validates credentials (via `password_verify()`)
3. Server sets PHP session: `$_SESSION['user_id'] = $user->id`
4. Server sends HTTP response with `Set-Cookie: PHPSESSID=xxx; HttpOnly; Secure; SameSite=Strict`
5. Browser automatically includes cookie in subsequent requests
6. API validates session on each request: `if (!isset($_SESSION['user_id'])) { return error 401 }`

**Session Configuration** (In `/private/config/config.php`):
```php
ini_set('session.cookie_httponly', 1);          // HttpOnly flag: JS cannot access cookie
ini_set('session.cookie_secure', 1);            // Secure flag: only HTTPS
ini_set('session.cookie_samesite', 'Strict');   // SameSite: prevent CSRF
ini_set('session.gc_maxlifetime', 86400);       // 24-hour session timeout
```

**CSRF Protection on AJAX**:
1. Server generates CSRF token on page load: `$token = bin2hex(random_bytes(32))`
2. Server stores token in session: `$_SESSION['csrf_token'] = $token`
3. JavaScript includes token in AJAX header: `X-CSRF-Token: [token]`
4. Server validates on POST/PUT/DELETE: `if ($_SERVER['HTTP_X_CSRF_TOKEN'] !== $_SESSION['csrf_token']) { return error 403 }`

**Logout Flow**:
1. User clicks logout → POST `/api/auth/logout`
2. Server destroys session: `session_destroy()`
3. Client redirects to login page

---

### 5.8 Database PDO Wrapper (Singleton Pattern for Clean Connection Management)

**Singleton Pattern Explanation** (Simple):
- Singleton = "Only one instance exists" (like having one database connection shared by whole app)
- Static methods = Functions attached to class (no instance needed; can't maintain state)
- Singleton is cleaner for DB: Can hold connection, track query count, prepare statements globally

**Database.php Implementation** (Singleton example):
```php
<?php
class Database {
  private static $instance = null;
  private $pdo;
  
  // Constructor is private; prevents `new Database()` from outside
  private function __construct() {
    $this->pdo = new PDO(
      "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
      DB_USER,
      DB_PASSWORD,
      [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      ]
    );
  }
  
  // Get singleton instance
  public static function getInstance() {
    if (self::$instance === null) {
      self::$instance = new self();
    }
    return self::$instance;
  }
  
  // Prepared query (safe from SQL injection)
  public function query($sql, $params = []) {
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
  }
  
  // Get single row
  public function getOne($sql, $params = []) {
    return $this->query($sql, $params)->fetch();
  }
  
  // Get all rows
  public function getAll($sql, $params = []) {
    return $this->query($sql, $params)->fetchAll();
  }
  
  // Execute (INSERT/UPDATE/DELETE)
  public function execute($sql, $params = []) {
    return $this->query($sql, $params)->rowCount();
  }
}
?>
```

**Usage Throughout App**:
```php
// Any file can use:
$db = Database::getInstance();

// Query:
$user = $db->getOne("SELECT * FROM users WHERE id = ?", [$user_id]);

// List:
$tasks = $db->getAll("SELECT * FROM tasks WHERE user_id = ? ORDER BY due_date", [$user_id]);

// Insert/Update/Delete:
$db->execute("INSERT INTO tasks (user_id, title) VALUES (?, ?)", [$user_id, $title]);
```

**Config File** (`/private/config/config.php`):
```php
<?php
// Database Configuration
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'adhd_dashboard');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? '');

// Email Configuration
define('EMAIL_FROM', $_ENV['EMAIL_FROM'] ?? 'noreply@adhd-dashboard.local');
define('EMAIL_FROM_NAME', 'ADHD Dashboard');
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'localhost');
define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? 587);
define('SMTP_USER', $_ENV['SMTP_USER'] ?? '');
define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD'] ?? '');

// App Configuration
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost:8000');
define('APP_DEBUG', $_ENV['APP_DEBUG'] ?? false);
define('SESSION_TIMEOUT', 86400); // 24 hours

// Security
define('BCRYPT_COST', 12); // password_hash() cost factor

// File Upload
define('MAX_FILE_SIZE_MB', 50);
define('UPLOAD_DIR', $_SERVER['DOCUMENT_ROOT'] . '/uploads/'); // Inside public_html
define('ALLOWED_FILE_TYPES', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx', 'txt']);
?>
```

---

### 5.9 File Management & Serving (Access Control via API)

**File Storage Location** (Inside `/public_html/uploads/`):
```
public_html/
└── uploads/
    ├── {user_id}/
    │   ├── General/
    │   ├── _tasks/
    │   │   └── task_{id}/
    │   ├── _crud/
    │   │   └── {crud_table_name}/
    │   └── Projects/
    └── {user_id_2}/
        └── ... (files for another user)
```

**Access Control** (Never expose file path directly):
1. User requests file: GET `/api/files/download/{file_id}`
2. Server validates: Does this user own this file? (Check `files.user_id`)
3. If valid: Stream file via PHP `readfile()` with proper headers
4. If invalid: Return 403 Forbidden

**File Download via API** (`/api/files/download.php`):
```php
<?php
// Validate user owns file
$file_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

$db = Database::getInstance();
$file = $db->getOne("SELECT * FROM files WHERE id = ? AND user_id = ?", [$file_id, $user_id]);

if (!$file) {
  http_response_code(404);
  exit('File not found');
}

$file_path = UPLOAD_DIR . $user_id . '/' . $file['file_path'];

if (!file_exists($file_path)) {
  http_response_code(404);
  exit('File not found');
}

// Stream file to browser
header('Content-Type: ' . mime_content_type($file_path));
header('Content-Disposition: attachment; filename="' . $file['original_filename'] . '"');
header('Content-Length: ' . filesize($file_path));

readfile($file_path);
exit;
?>
```

**File Upload Validation** (Server-side):
1. Check file size ≤ 50MB (per `/private/config/config.php`)
2. Check MIME type against whitelist (pdf, jpg, png, doc, etc.)
3. Sanitize filename (remove special chars): `preg_replace('/[^a-zA-Z0-9._-]/', '', $filename)`
4. Store with UUID to prevent name collisions: `/uploads/{user_id}/General/[uuid]_original_filename.pdf`
5. Store original filename in database for display

---

### 5.10 Timezone Handling (Browser Timezone, Client-Side Detection)

**Strategy: Leverage Browser Timezone (No Server Timezone Conversion)**

**Database Storage**:
- All `due_date`, `completed_at`, `created_at` timestamps stored as **date-only (YYYY-MM-DD)** for due dates, or **UTC datetime** for exact timestamps
- User's timezone preference stored in `users.timezone` (FYI; used for display format only)

**Client-Side Timezone Detection** (JavaScript on every page load):
```javascript
// Detect browser timezone
const userTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone; // e.g., "America/New_York"

// Send to server if not already set
fetch('/api/users/update-timezone', {
  method: 'PUT',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-Token': csrfToken
  },
  body: JSON.stringify({ timezone: userTimezone })
});
```

**Display Logic** (All dates shown in browser's LOCAL time):
- `due_date` (YYYY-MM-DD): Displayed as-is; browser interprets as local date
- `created_at` (UTC datetime): JavaScript converts to local datetime using `new Date(utc_string).toLocaleString()`
- Relative dates: "2 hours ago" calculated in browser timezone
- No server-side timezone conversion needed

**Example**:
```javascript
// Database stores: created_at = "2026-04-02T18:30:00Z" (UTC)
// Browser timezone: America/New_York (UTC-4)

const createdAt = new Date("2026-04-02T18:30:00Z");
console.log(createdAt.toLocaleString('en-US', { timeZone: 'America/New_York' }));
// Output: "4/2/2026, 2:30:00 PM" (local time)
```

**Rationale for ADHD Users**:
- No time-blindness confusion (always see local time, never UTC)
- No DST issues (browser handles DST automatically)
- Simpler logic: No server-side timezone database needed
- Users see what they expect (their local time)

---

### 5.11 Recurring Task/Ticket Generation (On-Demand Logic)

**When Recurring Task Completes** (Trigger: User marks task complete):

**Step 1: Check if Recurring**:
```php
$task = $db->getOne("SELECT * FROM tasks WHERE id = ?", [$task_id]);
if (!$task['recurring']) {
  // Non-recurring; just mark complete
  $db->execute("UPDATE tasks SET status = 'completed', completed_at = NOW() WHERE id = ?", [$task_id]);
  return;
}
```

**Step 2: Generate Next Instance**:
```php
// Parse recurrence rule
$rule = $task['recurring'];  // "daily", "weekly_mon_wed", "monthly_15", "annually"

// Calculate next due date
$nextDueDate = calculateNextDate($task['due_date'], $rule);

// Create new task
$newTask = [
  'user_id' => $task['user_id'],
  'title' => $task['title'],
  'description' => $task['description'],
  'due_date' => $nextDueDate,
  'priority' => $task['priority'],
  'category' => $task['category'],
  'recurring' => $rule,
  'estimated_minutes' => $task['estimated_minutes'],
  'status' => 'backlog'
];

$db->execute(
  "INSERT INTO tasks (user_id, title, description, due_date, priority, category, recurring, estimated_minutes, status) 
   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
  [$newTask['user_id'], $newTask['title'], $newTask['description'], $newTask['due_date'], 
   $newTask['priority'], $newTask['category'], $newTask['recurring'], $newTask['estimated_minutes'], $newTask['status']]
);

// Mark original as complete
$db->execute("UPDATE tasks SET status = 'completed', completed_at = NOW() WHERE id = ?", [$task_id]);
```

**Helper Function** (`calculateNextDate`):
```php
function calculateNextDate($currentDueDate, $rule) {
  $date = new DateTime($currentDueDate);
  
  if ($rule === 'daily') {
    $date->add(new DateInterval('P1D'));
  } elseif (strpos($rule, 'weekly') === 0) {
    $date->add(new DateInterval('P7D'));
  } elseif (strpos($rule, 'monthly') === 0) {
    $date->add(new DateInterval('P1M'));
  } elseif ($rule === 'annually') {
    $date->add(new DateInterval('P1Y'));
  }
  
  return $date->format('Y-m-d');
}
```

**Recurring Tickets** (Same logic; trigger on ticket closure):
- On ticket status → Closed, system checks `tickets.recurrence_rule`
- If set, generates next ticket with same resources, same title (appended with year if annual)
- Previous ticket linked: "Cloned from: [Previous Ticket]"

---

### 5.12 Logging & Monitoring (Structured Logs)

**Log Locations**:
```
private/logs/
├── error.log              # Exceptions, warnings, database errors
├── activity.log           # User actions (login, create task, delete file)
├── auth_failed.log        # Failed login attempts (security tracking)
└── email.log              # Email send success/fail
```

**Log Format** (Structured; easy to parse):
```
[TIMESTAMP] [LEVEL] [USER_ID] [ACTION] [DETAILS] [FILE:LINE]
2026-04-02T14:30:00Z ERROR 0 DATABASE "Connection failed" Database.php:45
2026-04-02T14:31:05Z INFO 123 LOGIN "User 123 logged in" auth.php:89
2026-04-02T14:31:45Z INFO 123 CREATE_TASK "Task 456 created" tasks.php:156
2026-04-02T14:32:10Z ERROR 0 AUTH_FAILED "Invalid password for user@example.com" auth.php:34
```

**PHP Logger Class** (`lib/Logger.php`):
```php
<?php
class Logger {
  public static function error($action, $details, $user_id = 0) {
    self::log('ERROR', $user_id, $action, $details);
  }
  
  public static function info($action, $details, $user_id = 0) {
    self::log('INFO', $user_id, $action, $details);
  }
  
  private static function log($level, $user_id, $action, $details) {
    $timestamp = date('Y-m-d\TH:i:s\Z');
    $file = debug_backtrace()[1]['file'];
    $line = debug_backtrace()[1]['line'];
    
    $message = "[{$timestamp}] [{$level}] [{$user_id}] [{$action}] \"{$details}\" {$file}:{$line}\n";
    
    error_log($message, 3, LOG_DIR . strtolower($level) . '.log');
  }
}
?>
```

**Sensitive Data NOT Logged**:
- ❌ Passwords (never logged)
- ❌ Email addresses (only in failed auth; not in success logs)
- ❌ File content (only filename, size)
- ❌ Credit card details (not stored; N/A for this app)

**DO Log**:
- ✅ Exceptions (with stack trace, but not to client)
- ✅ User actions (login, logout, task create/delete)
- ✅ Failed auth attempts (for security audit)
- ✅ Generated reminders (for monitoring cron jobs)

---

### 5.13 Database Migrations (One-Time Setup)

**Migration File** (`/public_html/migrations/001_init_schema.php`):
- Runs once during system setup
- Creates all tables with proper indexes, foreign keys, defaults
- Should be **idempotent** (safe to run multiple times; checks IF NOT EXISTS)

**Sample Migration**:
```php
<?php
$db = Database::getInstance();

// Create users table
$db->execute("
  CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255),
    profile_picture VARCHAR(255),
    timezone VARCHAR(50) DEFAULT 'UTC',
    theme VARCHAR(50) DEFAULT 'Ocean',
    role ENUM('developer', 'admin', 'user') DEFAULT 'user',
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX(email),
    INDEX(role)
  )
");

// Create tasks table
$db->execute("
  CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    due_date DATE,
    priority ENUM('Low', 'Medium', 'High', 'Urgent') DEFAULT 'Medium',
    category VARCHAR(100),
    status ENUM('inbox', 'backlog', 'scheduled', 'active', 'completed', 'archived', 'deleted') DEFAULT 'inbox',
    recurring VARCHAR(50),
    estimated_minutes INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    archived_at TIMESTAMP NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id),
    INDEX(user_id),
    INDEX(due_date),
    INDEX(status)
  )
");

// ... (all other tables)

echo "Migration completed successfully!";
?>
```

**Access**: Run once from CLI or via setup script: `php /public_html/migrations/001_init_schema.php`

---

### 5.14 Phase 1 Complete API Endpoint List

| Method | Endpoint | Params | Purpose | Returns |
|--------|----------|--------|---------|---------|
| POST | /api/auth/login | email, password | Login user | { success, user_id, full_name } |
| POST | /api/auth/logout | (none) | Destroy session | { success } |
| GET | /api/tasks | (none) | List user's tasks | { tasks: [...] } |
| POST | /api/tasks | title, due_date, priority, description | Create task | { id, task_data } |
| GET | /api/tasks/{id} | (none) | Get task details | { task_data } |
| PUT | /api/tasks/{id} | title, due_date, priority, description | Update task | { success } |
| DELETE | /api/tasks/{id} | (none) | Soft-delete task | { success } |
| POST | /api/tasks/{id}/complete | (none) | Mark task complete | { success, next_recurring_task_id } |
| POST | /api/tasks/{id}/reschedule | new_due_date | Reschedule task | { success } |
| GET | /api/dashboard/priorities | date (optional) | Get 1-3-5 for date | { slots: [1,3,5] with task data } |
| POST | /api/dashboard/priorities | task_ids (array) | Set 1-3-5 slots | { success } |
| GET | /api/notifications | (none) | Get user's notifications | { notifications: [...] } |
| POST | /api/notifications/{id}/read | (none) | Mark notification as read | { success } |
| POST | /api/files/upload | file (multipart) | Upload file | { file_id, file_name, size } |
| GET | /api/files/download/{id} | (none) | Download file (stream) | Binary file data |
| DELETE | /api/files/{id} | (none) | Delete file | { success } |
| GET | /api/users | (none) | List all users (Admin) | { users: [...] } |
| POST | /api/users | email, full_name | Create user (Admin) | { user_id } |
| PUT | /api/users/{id} | full_name, timezone, theme | Update user profile | { success } |
| PUT | /api/users/update-timezone | timezone | Update browser timezone | { success } |
| DELETE | /api/users/{id} | (none) | Deactivate user (Admin) | { success } |
| GET | /api/settings | (none) | Get system settings (Admin) | { settings: {...} } |
| PUT | /api/settings | key, value | Update settings (Admin) | { success } |

---

### 5.15 Phase 1 Technical Architecture Scope

**Included in Phase 1**:
✅ PHP 8.x with PDO (Singleton Database wrapper)
✅ Config file-based settings (no hardcoded values)
✅ JSON API responses (success/error, HTTP status codes)
✅ Session-based authentication + CSRF protection
✅ File upload/download with access control (stored in /public_html/uploads/)
✅ Browser timezone detection (client-side; no server conversion)
✅ Recurring task on-demand generation (on completion)
✅ Structured logging (error.log, activity.log, auth_failed.log)
✅ PDO Singleton wrapper class (Database.php)
✅ 23 core API endpoints (auth, tasks, dashboard, files, users, settings)
✅ Database migration script (001_init_schema.php)

**Phase 2+ (Future)**:
🔄 Database connection pooling optimization
🔄 Cron job infrastructure (for auto-generated recurring items if needed)
🔄 API rate limiting
🔄 Request/response validation schemas
🔄 Advanced logging with stack traces
🔄 Performance monitoring & metrics

---

### 6.1 Code Sources & Integration Strategy

| Source | What to Use | Integration Notes |
|--------|------------|-------------------|
| **login-system** | Auth logic, hashing, session management | Refactor to use PDO; adapt for single admin dashboard |
| **CRUD** | Database patterns, form handling, validation | Extract CRUD query logic; adapt for dynamic table definitions |
| **file-management** | File upload, download, directory structure | Use as-is, with updated file paths (/private/uploads/) |
| **lib** | Email sending, error handling, logging | Centralize into single config; adapt email to use app sender |
| **ticketing-system** | Ticket CRUD, comment/activity log, status management | Refactor for Projects → Tickets model; add resource hub; remove client-facing portions |
| **vendor** | PDF generation, third-party libraries | Keep as per composer.json; use as needed |
| **config** | Environment-aware configuration loading | Enhance for development/staging/production environments |

### 6.2 Refactoring Goals
- Convert all queries from MySQLi to PDO
- Consolidate CSS into single stylesheet
- Create unified database (one adhd-dashboard DB)
- Remove public registration and public-facing forms
- Adapt authentication to role-based system
- Extract reusable components into lib/

---

## 6. GREENFIELD BUILD STRATEGY (No Legacy Code Integration)

### 6.1 Build Approach
**Greenfield Build**: This is a fresh start application. The reference folder (`Unused Code for RESOURCES to COPY/`) contains patterns and examples from past projects but will be **deleted after Phase 1 completion**.

**What This Means**:
- ✅ Build all code fresh with PDO (no MySQLi anywhere)
- ✅ Can reference old code for patterns (e.g., "how was email sending structured?")
- ✅ Can extract/copy utilities from vendor/ if useful (e.g., PDF generation, OAuth logic)
- ✅ No backward compatibility concerns; no migrations from old system
- ✅ Clean slate = simpler architecture

### 6.2 Build from Scratch (Phase 1)
**What Gets Built Fresh**:
- `lib/Database.php` (PDO Singleton wrapper)
- `lib/Auth.php` (Email login, password hashing, sessions)
- `lib/Task.php` (Task CRUD)
- `lib/Email.php` (Unified email sending: verification, reminders, notifications)
- `lib/Security.php` (Input validation, sanitization, CSRF tokens)
- `lib/Logger.php` (Structured error + activity logging)
- `lib/FileManager.php` (File upload/download with access control)
- `lib/Reminder.php` (Reminder scheduling and dispatch)
- All API endpoints (JSON, no legacy code reuse)
- All view templates (fresh HTML + CSS + JS)

**What We Can Reference** (Not directly use):
- `login-system/` — Review for auth patterns (email verification, password reset logic)
- `file-management/` — Review for folder organization ideas
- `CRUD/` — Review for form validation patterns
- `ticketing-system/` — Review for comment/activity log patterns
- `lib/` — Review for email sending, error handling patterns
- `vendor/` — Use libraries if needed (e.g., `firebase/php-jwt` for tokens, `phpmailer` if we need advanced email)

**How to Use References**:
1. Read existing code to understand pattern
2. Rewrite in PDO + fresh architecture (not copy-paste)
3. Delete reference code folder when Phase 1 complete

### 6.3 Dependency Strategy
**Minimize Dependencies** (Keep code maintainable):
- Use PHP built-ins: `password_hash()`, `SESSION`, `PDO`, `DateTime`, `JSON functions`
- Avoid heavy frameworks (no Laravel, Symfony, Yii for now)
- Optional: Use composer for specific libraries if needed:
  - `phpmailer/phpmailer` (for advanced email sending)
  - `monolog/monolog` (for professional logging; optional—can use file-based logs instead)
  - `league/csv` (for CSV export; optional—can use fputcsv instead)

**Phase 1 Composer Dependencies** (Minimal):
```json
{
  "require": {
    "php": ">=8.0"
  },
  "require-dev": {
    "phpunit/phpunit": "^10.0"
  }
}
```

All other functionality built from scratch with PDO + vanilla PHP.

### 6.4 No Integration Needed
**This Phase 1 is completely independent:**
- Not reading from old databases
- Not using old authentication system
- Not importing old task data (users start fresh)
- Not using old file storage paths
- Clean database schema (no migration from MySQLi)

**After Phase 1**: Reference folder deleted; only fresh ADHD Dashboard code remains.

---

## 7. ACCESSIBILITY & WCAG 2.1 AA COMPLIANCE

**ADHD Consideration**: Clear, high-contrast UI and predictable keyboard navigation reduce cognitive load for users with executive dysfunction.

### 7.1 Color Contrast Requirements
**WCAG 2.1 AA Standard**: Minimum 4.5:1 contrast ratio (text to background)

**Our Color Palette Compliance**:
- Bright Yellow (#FFB300) text on white: ✅ 9:1 (pass)
- Bright Yellow (#FFB300) text on dark: ✅ 8:1 (pass)
- Bright Orange (#FF9F43) text on white: ✅ 5.5:1 (pass)
- Calming Pink (#B8E0D2) text on white: ✅ 4.8:1 (pass)
- Dark text (#333) on white: ✅ 12.6:1 (pass)

**Inaccessible Color Combinations** (Avoid):
- Calming Pink (#B8E0D2) on light gray background: ❌ Insufficient contrast
- Yellow text on light backgrounds: Use dark backgrounds or adjust saturation

**Implementation**:
- All text inputs have ≥ 4.5:1 contrast
- Focus states have ≥ 3:1 contrast with unfocused state
- Error messages in dark red (#C00) or dark orange (not light red)
- Warning messages in Bright Orange (#FF9F43) with dark text overlay

### 7.2 Keyboard Navigation
**Fully Keyboard Accessible**: All interactive elements operable via keyboard (no mouse required)

#### 7.2.1 Keyboard Navigation Flow

**Desktop Navigation**:
1. **Skip Link** (Top-left, hidden until focused via Tab):
   - Text: "Skip to main content"
   - Links directly to `<main id="main-content">`
   - Visible on focus (display: block; z-index: 1000)

2. **Tab Order** (Logical flow):
   - Navigation menu items (left-to-right order)
   - Main content area (top-to-bottom)
   - Fixed bottom action bar (last in tab order before cycling back)

3. **Focus Indicators**:
   - All focusable elements: 3px solid Bright Yellow (#FFB300) outline
   - Outline offset: 2px (visible gap between element border and outline)
   - Never removed (no `outline: none;` anywhere)

#### 7.2.2 Keyboard Shortcuts

**Task Management**:
- **Alt+N**: New task (opens capture modal)
- **Alt+S**: Search tasks (focuses search input)
- **Alt+F**: Focus mode toggle
- **Alt+T**: Open timer

**Navigation**:
- **Alt+1**: Dashboard
- **Alt+2**: Tasks
- **Alt+3**: Projects
- **Alt+4**: Profile
- **Alt+H**: Help (opens in-app help overlay)

**Form Controls**:
- **Tab**: Move to next field
- **Shift+Tab**: Move to previous field
- **Enter**: Submit form
- **Escape**: Cancel/close modal
- **Space**: Toggle checkbox or button activation

**Modals**:
- **Escape**: Close modal (same as Cancel button)
- **Tab**: Cycle through focusable elements within modal
- Focus trap: Tab loops within modal (doesn't escape to background)

#### 7.2.3 Screen Reader Support

**ARIA Labels & Descriptions**:

```html
<!-- Task item with screen reader context -->
<div class="task-item" role="article" aria-labelledby="task-title-123">
  <h3 id="task-title-123">Buy groceries</h3>
  <p class="task-due" aria-label="Due date: Tomorrow at 2pm">Due tomorrow at 2pm</p>
  <button aria-label="Mark task complete: Buy groceries" class="complete-btn">
    <span aria-hidden="true">✓</span>
  </button>
</div>

<!-- Icon-only button needs label -->
<button aria-label="Delete task" class="delete-icon">
  <span aria-hidden="true">🗑️</span>
</button>

<!-- Form input with label -->
<label for="task-title">Task title *</label>
<input id="task-title" type="text" required aria-required="true" />

<!-- Select dropdown with grouped options -->
<select aria-label="Filter tasks by priority">
  <optgroup label="Priority Levels">
    <option value="urgent">Urgent (Red)</option>
    <option value="high">High (Orange)</option>
  </optgroup>
</select>

<!-- Loading state announcement -->
<div role="status" aria-live="polite" aria-label="Loading tasks">
  Loading... Please wait.
</div>

<!-- Form validation error -->
<input type="email" aria-invalid="true" aria-describedby="email-error" />
<span id="email-error" class="error-message">Invalid email address. Please check and try again.</span>
```

**Screen Reader Text** (Hidden visually, read aloud):
- All icon buttons have `aria-label`
- Status changes announced via `aria-live="polite"` (e.g., "Task marked complete")
- Form errors linked via `aria-describedby`
- Empty states announced ("No tasks in this view. Add a new task to get started.")

#### 7.2.4 Semantic HTML5

**Best Practices** (Replace `<div>` generics where possible):
- `<button>` for clickable actions (NOT `<div onclick>`)
- `<a href="">` for navigation (NOT `<button>` for links)
- `<nav>` for navigation sections
- `<main>` for primary content (id="main-content" for skip link)
- `<header>`, `<footer>`, `<article>`, `<section>` for structure
- `<form>` for forms (NOT `<div class="form">`)
- `<label for="id">` paired with `<input id="id">`
- `<fieldset>` for grouped form controls (e.g., radio buttons)
- Heading hierarchy: `<h1>` once per page, then `<h2>`, `<h3>` in order (no skipping levels)

### 7.3 Mobile & Touch Accessibility

**Touch Target Size** (WCAG 2.1 AAA standard: 44x44px minimum):
- All buttons: ≥ 44x44 pixels
- Links: ≥ 44x44 pixels
- Form inputs: ≥ 44px height
- Touch spacing: ≥ 8px margin between touch targets (no overlapping hit zones)

**Mobile Screen Readers**:
- VoiceOver (iOS): Built-in; test with iPhone
- TalkBack (Android): Built-in; test with Android device
- Ensure all touch inputs are announced clearly

### 7.4 Forms Accessibility

#### 7.4.1 Form Structure & Labels

```html
<!-- Good: Label associated with input -->
<label for="task-title">Task title (required)</label>
<input 
  id="task-title" 
  name="title" 
  type="text" 
  required 
  aria-required="true"
  placeholder="e.g., Buy groceries"
>

<!-- Good: Grouped radio buttons -->
<fieldset>
  <legend>Task priority *</legend>
  <input type="radio" id="urgent" name="priority" value="urgent" aria-labelledby="urgent-label" required />
  <label id="urgent-label" for="urgent">Urgent (Due today)</label>
  
  <input type="radio" id="high" name="priority" value="high" aria-labelledby="high-label" />
  <label id="high-label" for="high">High (Due this week)</label>
</fieldset>

<!-- Good: Error state with description -->
<label for="email">Email address *</label>
<input 
  id="email" 
  type="email" 
  required 
  aria-required="true"
  aria-invalid="true"
  aria-describedby="email-error"
>
<span id="email-error" role="alert" class="error-message">
  Invalid email format. Please use format: user@example.com
</span>
```

#### 7.4.2 Form Validation

**Accessibility Best Practices**:
1. **Validation messages**:
   - Associated with field via `aria-describedby`
   - Role="alert" for immediate notification
   - Clear, plain language ("Email is required" not "Error: null value")

2. **Error summary** (for multi-field forms):
   - List at top of form before submission
   - Each error links to problematic field (anchor jump on click)
   - Announcement: "Form has 2 errors. Please review below."

3. **Success feedback**:
   - Visual checkmark + color highlight
   - Announcement: "Task created successfully"
   - Confirmation message in toast (not auto-dismissed immediately)

### 7.5 Images & Media Accessibility

**Alt Text**:
- **Informative images**: Alt describes content ("Dashboard showing 3 tasks in 1-3-5 layout")
- **Decorative images**: `alt=""` and `aria-hidden="true"` (screen reader skips)
- **Icons with text**: Icon is `aria-hidden`; text provides meaning
- **Icons without text**: Icon has `aria-label` ("Mark as complete")

**Media**:
- Video: Captions (burned-in or CC option)
- Audio: Transcript provided
- Animated GIFs: Reduced motion alternative (static image)

### 7.6 Responsive Text & Zoom

**Text Sizing**:
- Minimum font size: 14px (body text); 12px (labels okay if high contrast)
- Heading hierarchy: H1 > H2 > H3 (no skipping levels)
- Line height: ≥ 1.5x font size (loose spacing for readability)
- Line length: ≤ 80 characters per line (avoid long single-line text blocks)

**Zoom Support**:
- Apply viewport: `<meta name="viewport" content="width=device-width, initial-scale=1.0">`
- Allow user zoom: Never use `user-scalable=no`
- Content reflows at 200% zoom (no horizontal scrollbar required)
- Text resizing: Increase text to 200% without breaking layout

**Reduced Motion**:
```css
@media (prefers-reduced-motion: reduce) {
  * { animation: none !important; transition: none !important; }
}
```
- Animations disabled for users with motion sensitivity (ADHD + vestibular issues)
- Alternative: Static disclosure (collapse buttons still work)

### 7.7 Testing & Compliance

#### 7.7.1 Automated Testing
1. **Lighthouse** (Chrome DevTools):
   - Run audit on all main pages
   - Target: Accessibility score ≥ 90
   - Check for missing alt text, color contrast, ARIA
   - Run before every release

2. **axe DevTools** (Browser extension):
   - Manual check desktop version
   - Detailed violation report
   - Target: 0 critical violations

3. **WAVE** (WebAIM tool):
   - Visual feedback on accessibility issues
   - Quick overview of contrast, ARIA, structure

#### 7.7.2 Manual Testing
1. **Keyboard-only navigation**:
   - Disable mouse; use Tab, Shift+Tab, Enter, Escape, Arrows
   - Test all main flows: Login → Dashboard → Create Task → Complete Task
   - Verify focus visible at all times
   - Verify skip link works

2. **Screen Reader Testing**:
   - **NVDA** (Windows; free):
     - Skim all headings (H key)
     - Read form labels (F key)
     - Navigate buttons (B key)
     - Verify announcement of dynamic content
   - **JAWS** (Windows; paid; optional):
     - Similar testing; slightly different verbosity
   - **VoiceOver** (Mac/iOS; built-in):
     - Test iOS version if needed

3. **Color Contrast**:
   - Use WebAIM Contrast Checker tool
   - Test all text-background combinations
   - Verify ≥ 4.5:1 ratio

4. **Mobile Accessibility**:
   - Test on iOS (VoiceOver) and Android (TalkBack)
   - Verify touch targets ≥ 44x44px
   - Verify text readable at 200% zoom

#### 7.7.3 Pre-Launch Accessibility Audit
**Before Production**:
1. Run Lighthouse on all main pages (≥90 score)
2. Manual keyboard testing (all flows)
3. Screen reader testing (NVDA + VoiceOver)
4. Mobile testing (iOS + Android)
5. Color contrast verification (all combinations)
6. Document any known limitations + workarounds

**Ongoing Monitoring**:
- Test every new feature before merge
- Run Lighthouse weekly on main branch
- Monitor for accessibility regressions

### 7.8 ADHD-Specific Accessibility

**Executive Dysfunction Considerations**:
1. **Clear visual hierarchy**: Large headings, bold action buttons (easy to find)
2. **Minimal motion**: Fade-ins instead of bounces; reduced animations (less distracting)
3. **Predictable interactions**: Forms always in same location; buttons always same size/color
4. **High contrast**: Dark text on light; bright accent colors (above minimum contrast)
5. **No auto-play**: Music/video requires user click (audio sensitivity)
6. **Clear labeling**: Every input explicitly labeled (no placeholder-only inputs)
7. **Focus indicators**: Always visible yellow outline (never hidden on focus)
8. **Tab order**: Logical flow matching visual layout (reduces cognitive map effort)

---

## 8. DATA HANDLING & PRIVACY

### 8.1 Sensitive Data Protection

#### 8.1.1 Password Security
**Standard**: bcrypt hashing (PHP's `password_hash()` function)

```php
// When user registers/resets password
$passwordHash = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12]);
// Store in database: users.password = $passwordHash

// When user logs in
if (password_verify($inputPassword, $storedHash)) {
  // Login success
}
```

**Why bcrypt?**
- Salted by default (no rainbow table attacks)
- Slow by design (brute force resistant; Cost=12 = 250ms per hash)
- Automatically upgrades if PHP updates (future-proof)
- Never encrypt passwords (hashing is one-way; correct behavior)

**Password Reset Flow** (Secure):
1. User enters email → System generates random 32-char token
2. Token stored in `password_resets` table with expiry (30 min)
3. Reset link: `/reset-password?token=[token]`
4. User sets new password → Token deleted
5. No email sent with temporary password (user generates their own)

#### 8.1.2 Sensitive Database Fields
**Encrypted at Rest**:
- Bank account numbers (in CRUD tables if enabled)
  - AES-256 encryption using `openssl_encrypt()`
  - Key stored in `/private/config/config.php` (never in version control)
- Social Security Numbers (if CRUD)
  - AES-256 encryption
  - Minimum length validation (9 digits)
- Phone numbers (if storing for SMS reminders):
  - E.164 format: +1-555-555-5555
  - Encrypted at rest if in compliance scope

**Personal Identifiable Information (PII)** (Logged carefully):
- User email: Never logged plaintext in activity log
- User name: Logged as "User#[id]" in activity log (not full name)
- IP address: Logged optionally (admin setting); never in error log
- Browser fingerprint: Not collected

#### 8.1.3 Third-Party Data Sharing
**No Sharing** (Phase 1):
- User data remains on self-hosted server only
- No Google Analytics, Facebook Pixel, third-party trackers
- No email marketing platforms (using self-managed SMTP only)
- No cloud backup to AWS/Azure/Google (unless explicitly enabled by admin)

**Phase 2+** (Future; out of scope):
- If cloud backup enabled: Encrypted before transmission
- If Slack integration added: Only shared if user explicitly sends to Slack
- If API access added: Only shared via OAuth with user consent

### 8.2 Session & Authentication Security

#### 8.2.1 Session Management
**Session Storage** (Server-side; PHP default):
- Session data stored in `/tmp/` (PHP's session storage)
- Session ID (PHPSESSID cookie):
  - HttpOnly flag: ✅ (JavaScript cannot access; prevents XSS token theft)
  - Secure flag: ✅ (HTTPS only; prevents MITM attacks)
  - SameSite: Strict (prevents CSRF; sent only on same-site requests)
  - Lifetime: 30 min idle, 4 hours max (admin configurable)

**Session Fixation Prevention**:
- Regenerate session on login: `session_regenerate_id(true);`
- Invalidate old session immediately after regeneration
- On logout: `session_destroy();` followed by `unset($_SESSION);`

#### 8.2.2 CSRF Token Protection
**Implementation**:

```php
// On login: Generate token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // 64-char hex string

// In all forms:
<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />

// In all AJAX requests:
// Header: X-CSRF-Token: [token]
fetch('/api/tasks', {
  method: 'POST',
  headers: { 'X-CSRF-Token': document.querySelector('[name="csrf_token"]').value },
  body: JSON.stringify({ title: 'New task' })
});

// On server: Validate token on every POST/PUT/DELETE
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
  http_response_code(403); // Forbidden
  exit('CSRF token invalid');
}
```

**Token Lifetime**: Refreshed per session (30 min idle)

#### 8.2.3 Two-Factor Authentication (Phase 2+)
Out of scope for Phase 1; future consideration for admin accounts.

### 8.3 Data Retention & User Deletion

#### 8.3.1 Task & File Retention
**Completed Tasks**:
- Default: Archive indefinitely (accessible in archive filter)
- User can: Permanently delete (hard delete from database)
- On delete: Associated files moved to trash or deleted per user preference

**Incomplete Tasks** (Auto-management):
- Daily tasks that aren't completed:
  - Admin setting: "Auto-delete incomplete daily tasks after 1 week"
  - If enabled: Task marked deleted after 7 days incomplete
  - If disabled: Tasks remain in backlog indefinitely (user must manually delete or reschedule)
- Rescheduled tasks: User manually sets new due date (no auto-rescheduling for incomplete)

**Files**:
- User-uploaded files: Stored in `/public_html/uploads/[user_id]/`
- Delete file → Physically remove from filesystem + database log
- Delete task → Delete associated files OR archive them (user choice in task delete dialog)
- User deletion → All files deleted from filesystem

#### 8.3.2 Activity Logs & Audit Trail
**Retention Policy**:
- Event logs (activity_log table): Retain 90 days (admin configurable)
- Error logs (error.log): Retain 30 days (rotated; old files deleted)
- Auth logs (auth_failed.log): Retain 60 days (intrusion detection)
- After retention: Logs deleted permanently (or archived to S3 if enabled)

**What's Logged** (Never log passwords/tokens):
- ✅ User ID, action (create/update/delete), timestamp, record_id, change_summary
- ❌ Password values, API tokens, CSRF tokens, email content, file contents
- ⚠️ IP address (only if admin enables for security audit)

#### 8.3.3 User Account Deletion (Right to be Forgotten)
**Soft Delete Process**:
1. User requests deletion in profile → Settings → Account → Delete Account (warning shown)
2. System flags `users.deleted_at = now()` (soft delete)
3. User account becomes inaccessible; login fails
4. Data retained for 30-day grace period (user can reactivate via support email)

**Hard Delete Process** (After 30 days):
1. Automated job runs daily: Delete accounts where `deleted_at < now() - 30 days`
2. Hard delete removes:
   - User account from `users` table
   - All tasks, projects, tickets, reminders
   - All files from `/public_html/uploads/[user_id]/`
   - All activity log entries for that user
   - CRUD table records owned by user
3. Retained (for legal/financial purposes; depersonalized):
   - Delegated tasks (reassigned to other users)
   - Shared resources (if other users have access)
   - Invoice data (if payment exemption table exists)

**Export Before Deletion**:
- Before hard delete, email user link to download data export
- Export format: ZIP containing:
  - `tasks.json` (all tasks + attachments)
  - `projects.json` (all projects)
  - `files.csv` (file metadata; files not included due to size)
  - `activity.csv` (user's activity log)

### 8.4 Privacy Compliance

#### 8.4.1 GDPR Compliance (If EU users)
**Data Minimal Principles**:
- Collect only necessary data (name, email, password hash)
- No third-party tracking (no Google Analytics, Facebook Pixel)
- User consent: "I agree to privacy policy" checkbox on signup
- Privacy policy linked in footer (kept updated)

**User Rights**:
- Right to access: Export data (download JSON/CSV)
- Right to erasure: Delete account triggers hard delete after 30 days
- Right to portability: Data export in standard formats (JSON, CSV)
- Right to restrict: User can disable SMS notifications, email digests

**Data Processing Agreement** (If hosting is SaaS):
- Include DPA in terms of service
- No third-party data sharing (explicit policy)
- Incident notification: Notify users within 72 hours of breach

#### 8.4.2 CCPA Compliance (If California users)
Similar to GDPR; additionally:
- Disclose data collection in privacy policy
- Allow users to opt-out of "sale" of data (we don't sell; but include clause)
- Honor "Do Not Track" browser signals (optional; we don't track anyway)

#### 8.4.3 Local Data Residency (If required)
- Data stored on single, on-premises server
- No backup to cloud (unless explicitly configured)
- Geographic location: [Specify where server hosted]
- No data transfer to other countries

### 8.5 Incident Response & Breach Notification

#### 8.5.1 Incident Detection
**Monitoring**:
- Error logs monitored for unusual patterns (500 errors, SQL-like input)
- Failed auth attempts logged (5+ in 10 min = suspicious)
- File upload anomalies detected (suspected malware, size exceeds limit)

#### 8.5.2 Breach Notification
**If data breach occurs**:
1. Immediately contain: Shut down affected service, block attacker access
2. Investigate: Determine scope (which data? which users? how long exposed?)
3. Notify users within 72 hours:
   - Email to registered addresses
   - Subject: "ADHD Dashboard - Security Incident Notification"
   - Content: What happened, what data affected, what we did, what users should do
4. Notify regulators: If required by GDPR/CCPA (EU/CA)
5. Public transparency: Post incident summary on website

**Example Message**:
> We discovered unauthorized access to the file storage system on [DATE]. The attacker had access to file names and metadata (NOT file contents due to encryption). Affected users: [COUNT]. We have secured the system and rotated credentials. Please change your password. If you have questions, email security@adhd-dashboard.com.

### 8.6 Privacy Policy & Terms of Service
**Required Documents** (Linked in footer):
- Privacy Policy: Explains data collection, use, retention, user rights
- Terms of Service: Usage rules, limitations, liability disclaimers
- Cookie Policy: What cookies we use (session cookies only; no tracking)

**Updates**:
- Changes flagged to existing users (email notification)
- Users must accept updated terms on next login
- Changelog: Publicly visible what changed and why

---

## 9. DEPLOYMENT & ENVIRONMENT

### 9.1 Supported Hosting Environments

#### 9.1.1 Requirements
**Minimum Server Specs**:
- PHP 8.0+ (recommended 8.2+)
- MySQL 5.7+ or MariaDB 10.3+
- 1GB RAM (2GB recommended for growth)
- 10GB disk space (elastic; grows with user files)
- HTTPS/SSL required (HTTP not acceptable)
- Outbound email (SMTP or mail function)
- Outbound SMS (Twilio or similar; optional for Phase 1; skipped if SMS disabled)

**Hosting Options Tested** (Phase 1):
- Self-hosted VPS (DigitalOcean, Linode, Vultr, Hetzner)
- Shared hosting (if PDO + SSH available)
- Docker container (optional; not required for Phase 1)

**NOT Supported**:
- Heroku (ephemeral filesystem; not suitable for file uploads)
- Firebase/Serverless (requires Node.js backend; we use PHP)
- AWS Lambda (no persistent file storage without S3)

### 9.2 Directory Structure on Production Server

```
Server Filesystem:
├── /var/www/adhd-dashboard/        (Application root)
│   │
│   ├── private/                    (Manual upload; NOT in GitHub)
│   │   ├── config/
│   │   │   └── config.php          (DB creds, email, API keys, feature flags)
│   │   ├── uploads/                (User files for internal storage)
│   │   │   ├── [user_id]/          (One folder per user)
│   │   │   │   ├── _tasks/         (Task-associated files)
│   │   │   │   ├── _crud/          (CRUD table files)
│   │   │   │   └── General/        (User's custom folders)
│   │   │   └── temp/               (Temporary upload staging)
│   │   └── logs/
│   │       ├── error.log           (PHP fatal errors, exceptions)
│   │       ├── activity.log        (User actions: create, update, delete)
│   │       └── auth_failed.log     (Failed logins, suspicious activity)
│   │
│   └── public_html/                (GitHub main branch; pulls here)
│       ├── api/                    (API endpoints: *.php)
│       ├── views/                  (PHP templates: *.php)
│       ├── public/
│       │   ├── css/                (Stylesheets: *.css)
│       │   ├── js/                 (JavaScript: *.js)
│       │   ├── fonts/              (Web fonts)
│       │   └── uploads/            [SYMLINK to ../../private/uploads]
│       ├── lib/                    (PHP classes: *.php)
│       ├── migrations/             (Database migrations: *.php)
│       ├── index.php               (Entry point / router)
│       ├── .htaccess               (Apache config; Route API requests)
│       └── composer.json           (PHP dependencies; optional)
│
└── /var/log/adhd-dashboard/        (System-level logs; optional)
    ├── access.log                  (Web server access)
    └── error.log                   (Web server errors)
```

**Symlink for File Access**:
```bash
# On production server (one-time setup):
ln -s ../../private/uploads /var/www/adhd-dashboard/public_html/uploads
```
This allows `/uploads/` to be web-accessible while files stored securely outside root.

### 9.3 Environment Configuration

#### 9.3.1 Config File Structure
**File**: `/private/config/config.php` (NEVER committed to GitHub)

```php
<?php
// Database Configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: 3306);
define('DB_NAME', getenv('DB_NAME') ?: 'adhd_dashboard');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Email Configuration
define('EMAIL_DRIVER', getenv('EMAIL_DRIVER') ?: 'mail'); // 'mail' or 'smtp'
define('SMTP_HOST', getenv('SMTP_HOST') ?: '');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('EMAIL_FROM', getenv('EMAIL_FROM') ?: 'noreply@adhd-dashboard.local');
define('EMAIL_FROM_NAME', getenv('EMAIL_FROM_NAME') ?: 'ADHD Dashboard');

// SMS Configuration (Optional)
define('SMS_ENABLED', getenv('SMS_ENABLED') ?: false);
define('TWILIO_ACCOUNT_SID', getenv('TWILIO_ACCOUNT_SID') ?: '');
define('TWILIO_AUTH_TOKEN', getenv('TWILIO_AUTH_TOKEN') ?: '');
define('TWILIO_PHONE', getenv('TWILIO_PHONE') ?: '');

// File Paths
define('UPLOADS_DIR', getenv('UPLOADS_DIR') ?: '/var/www/adhd-dashboard/private/uploads');
define('LOGS_DIR', getenv('LOGS_DIR') ?: '/var/www/adhd-dashboard/private/logs');

// App Settings
define('APP_ENV', getenv('APP_ENV') ?: 'development'); // 'development', 'staging', 'production'
define('APP_DEBUG', getenv('APP_DEBUG') ?: (APP_ENV === 'production' ? false : true));
define('APP_URL', getenv('APP_URL') ?: 'http://localhost');
define('SESSION_LIFETIME', 1800); // 30 minutes in seconds

// Security
define('ENCRYPTION_KEY', getenv('ENCRYPTION_KEY') ?: ''); // 32-char key for AES-256
define('CSRF_TOKEN_LIFETIME', 3600); // 1 hour

// Timezone
define('APP_TIMEZONE', getenv('APP_TIMEZONE') ?: 'UTC');
```

**Environment Variables** (Set via `.env` or server config):
```bash
# .env file (or export in docker-compose, systemd service, etc.)
DB_HOST=localhost
DB_NAME=adhd_dashboard
DB_USER=adhd_user
DB_PASS=[STRONG_PASSWORD]
EMAIL_DRIVER=smtp
SMTP_HOST=mail.example.com
SMTP_PORT=587
SMTP_USER=app@example.com
SMTP_PASS=[STRONG_PASSWORD]
APP_ENV=production
APP_URL=https://adhd-dashboard.example.com
ENCRYPTION_KEY=[32_CHAR_HEX_STRING]
```

**Security**: 
- `/private/config/` has permissions 700 (owner only)
- `config.php` has permissions 600 (owner read/write only)
- Never commit `.env` or `config.php` to GitHub

#### 9.3.2 Environment-Specific Settings

**Development**:
- `APP_ENV=development`
- `APP_DEBUG=true` (verbose error messages; stack traces shown)
- Database: Local MySQL
- Email: Test account or Mailtrap (real emails NOT sent)
- Logging: DEBUG + INFO + ERROR (verbose)

**Staging**:
- `APP_ENV=staging`
- `APP_DEBUG=true` (errors logged; shown in logs, not browser)
- Database: Production replica (real data samples)
- Email: Real SMTP with test email address
- Logging: INFO + ERROR (less verbose)

**Production**:
- `APP_ENV=production`
- `APP_DEBUG=false` (errors logged only; generic message shown to users)
- Database: Production MySQL
- Email: Real SMTP with production mailbox
- Logging: ERROR + CRITICAL only (minimal logging)
- Cache: Enabled (if implemented)

### 9.4 Initial Deployment Steps

#### 9.4.1 Server Preparation (One-time)
```bash
# 1. Create application root
sudo mkdir -p /var/www/adhd-dashboard
cd /var/www/adhd-dashboard

# 2. Clone repository
git clone https://github.com/[org]/adhd-dashboard.git public_html

# 3. Create private directories
mkdir -p private/config private/uploads private/logs
chmod 700 private/

# 4. Create user files directory structure
mkdir -p private/uploads/temp
chmod 755 private/uploads

# 5. Web server user ownership
sudo chown -R www-data:www-data /var/www/adhd-dashboard
chmod 755 public_html

# 6. Symlink for file access (as documented above)
ln -s ../../private/uploads public_html/uploads

# 7. Create config file (MANUALLY or via secure script)
# Never `git clone` this file; create manually or via CI/CD with secrets

# 8. Set permissions on config
chmod 600 private/config/config.php
```

#### 9.4.2 Database Initialization
```bash
# 1. Create database and user
mysql -u root -p << EOF
CREATE DATABASE adhd_dashboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'adhd_user'@'localhost' IDENTIFIED BY '[STRONG_PASSWORD]';
GRANT ALL PRIVILEGES ON adhd_dashboard.* TO 'adhd_user'@'localhost';
FLUSH PRIVILEGES;
EOF

# 2. Run migrations
php public_html/migrations/001_init_schema.php
# This creates all 15+ tables in adhd_dashboard

# 3. Verify tables
mysql -u adhd_user -p adhd_dashboard -e "SHOW TABLES;"
```

#### 9.4.3 Web Server Configuration

**Apache (.htaccess in public_html)**:
```apache
# Enable mod_rewrite
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /

  # Remove .php extension for cleaner URLs
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule ^(.*)$ index.php?path=$1 [QSA,L]

  # Redirect HTTP to HTTPS
  RewriteCond %{HTTPS} off
  RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>

# Security headers
<IfModule mod_headers.c>
  Header set X-Frame-Options "SAMEORIGIN"
  Header set X-Content-Type-Options "nosniff"
  Header set X-XSS-Protection "1; mode=block"
  Header set Referrer-Policy "strict-origin-when-cross-origin"
  Header set Permissions-Policy "geolocation=(), microphone=(), camera=()"
</IfModule>

# Prevent access to sensitive directories
<FilesMatch "\.(env|config|log)$">
  Deny from all
</FilesMatch>

<Directory "private">
  Deny from all
</Directory>
```

**Nginx (nginx.conf)**:
```nginx
server {
    listen 443 ssl http2;
    server_name adhd-dashboard.example.com;

    ssl_certificate /etc/letsencrypt/live/adhd-dashboard.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/adhd-dashboard.example.com/privkey.pem;

    root /var/www/adhd-dashboard/public_html;
    index index.php;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # URL rewriting
    location / {
        try_files $uri $uri/ /index.php?path=$uri&$args;
    }

    # PHP processing
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny access to sensitive directories
    location ~ /private { deny all; }
    location ~ /migrations { deny all; }
}

# Redirect HTTP to HTTPS
server {
    listen 80;
    server_name adhd-dashboard.example.com;
    return 301 https://$server_name$request_uri;
}
```

#### 9.4.4 SSL/TLS Certificate
**Using Let's Encrypt (Free)** (Recommended):
```bash
# Install Certbot
sudo apt-get install certbot python3-certbot-apache  # or -nginx

# Generate certificate
sudo certbot certonly --apache -d adhd-dashboard.example.com
# OR
sudo certbot certonly --nginx -d adhd-dashboard.example.com

# Auto-renew (runs automatically)
sudo systemctl enable certbot.timer
sudo systemctl start certbot.timer
```

**Certificate Renewal**:
- Let's Encrypt certs expire after 90 days
- Certbot auto-renews 30 days before expiration
- Systemd timer runs renewal check daily

### 9.5 Deployment Pipeline (CI/CD)

#### 9.5.1 GitHub Actions (Recommended for Phase 1)
**File**: `.github/workflows/deploy.yml`

```yaml
name: Deploy to Production

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2

      # Test PHP syntax
      - name: Lint PHP
        run: find . -name "*.php" -exec php -l {} \;

      # Run unit tests (if added later)
      # - name: Run Tests
      #   run: vendor/bin/phpunit

      # Deploy to production server
      - name: Deploy via SSH
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.HOST }}
          username: ${{ secrets.USERNAME }}
          key: ${{ secrets.SSH_KEY }}
          script: |
            cd /var/www/adhd-dashboard/public_html
            git pull origin main
            # Run any additional deployment commands (migrations, cache clear, etc.)
```

**GitHub Secrets** (Set in repository settings):
- `HOST`: Production server IP/hostname
- `USERNAME`: SSH user (e.g., deploy user, not root)
- `SSH_KEY`: Private SSH key for authentication

**Deployment Flow**:
1. Developer pushes to `main` branch
2. GitHub Actions triggered automatically
3. PHP linting runs (fast syntax check)
4. If pass: SSH deploys to production server
5. Git pull updates code
6. If database changes: Migrations run automatically
7. Site immediately reflects changes (no downtime if no schema changes)

#### 9.5.2 Manual Deployment (If no CI/CD)
```bash
# On production server
cd /var/www/adhd-dashboard/public_html
git pull origin main

# If database changes made:
php migrations/001_init_schema.php

# Clear any caches (if implemented)
# Restart services if needed
sudo systemctl restart php-fpm  # if using PHP-FPM
```

### 9.6 Monitoring & Maintenance

#### 9.6.1 Error Monitoring
**Log Files** (Checked regularly):
```bash
# Tail errors in real-time
tail -f /var/www/adhd-dashboard/private/logs/error.log

# Search for patterns
grep "Exception" /var/www/adhd-dashboard/private/logs/error.log | tail -20

# Check failed auth attempts
tail /var/www/adhd-dashboard/private/logs/auth_failed.log
```

**Log Rotation** (To prevent disk fill):
```bash
# File: /etc/logrotate.d/adhd-dashboard
/var/www/adhd-dashboard/private/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    notifempty
    missingok
    postrotate
        systemctl reload php-fpm > /dev/null 2>&1 || true
    endscript
}
```

#### 9.6.2 Database Backups
**Automated Backup** (Daily):
```bash
# File: /usr/local/bin/backup-adhd-db.sh
#!/bin/bash
BACKUP_DIR="/var/backups/adhd-dashboard"
DB_NAME="adhd_dashboard"
DB_USER="adhd_user"
DB_PASS="[PASSWORD]"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/adhd_$DATE.sql.gz

# Keep only last 30 days of backups
find $BACKUP_DIR -name "adhd_*.sql.gz" -mtime +30 -delete

echo "Backup completed: $BACKUP_DIR/adhd_$DATE.sql.gz"
```

**Cron Schedule**:
```bash
# Run backup daily at 2 AM
0 2 * * * /usr/local/bin/backup-adhd-db.sh
```

**Restore from Backup**:
```bash
gunzip < /var/backups/adhd-dashboard/adhd_20240115_020000.sql.gz | mysql -u adhd_user -p adhd_dashboard
```

#### 9.6.3 Health Checks
**Monitoring Uptime**:
- Use external service (e.g., UptimeRobot, Cronitor) to ping `/api/health` endpoint
- Endpoint returns: `{ "status": "ok", "timestamp": "2024-01-15T12:00:00Z" }`
- Alert admin if endpoint unreachable for > 5 min

**Disk Space**:
```bash
# Monitor disk usage (run daily via cron)
df -h /var/www/adhd-dashboard | grep -vE '^Filesystem|tmpfs'
# Alert if > 80% used
```

### 9.7 Rollback Strategy

**If Deployment Breaks Production**:
```bash
# 1. Rollback code to previous commit
cd /var/www/adhd-dashboard/public_html
git revert HEAD --no-edit  # OR git reset --hard [previous_commit]
git push origin main

# 2. If database schema changed, rollback database
# ONLY if your migration is reversible; i.e., include DOWN migration
php migrations/[specific_migration]_down.php

# 3. Verify site works
curl https://adhd-dashboard.example.com/api/health

# 4. Notify admin, review logs
tail -50 /var/www/adhd-dashboard/private/logs/error.log
```

**Prevention**:
- Test thoroughly in staging before production push
- Use feature branches; merge after review
- Have rollback plan documented before critical deploys

---

## 10. FUTURE ENHANCEMENTS (Out of Scope for Phase 1)

### Phase 2 Features (Planned)
- **Dark mode**: Additional CSS palette (coming after Phase 1 user feedback)
- **OAuth Integration**: Google/Microsoft login (simplify onboarding; Phase 2)
- **Calendar Integration**: Sync tasks to Google Calendar / Apple Calendar (read-only initially)
- **Mobile App**: iOS + Android native apps (Phase 2+; PWA alternative in Phase 1)
- **AI Suggestions**: Task prioritization based on past completions (ML model integration; Phase 2+)
- **Advanced Reporting**: Custom dashboard charts, trend analysis, productivity insights
- **Shared workspaces**: Team collaboration; assign tasks to team members (Phase 2)

### Phase 3 Features (Wishlist)
- **Multi-language support**: Internationalization (i18n); Spanish, French, German, Japanese
- **Voice input**: Create tasks via voice commands / voice reminders
- **Integrations**: Slack, Teams, Asana, Monday.com API connections
- **Custom themes**: User-defined color palettes (beyond pre-built themes)
- **Advanced analytics**: Burndown charts, cumulative flow, cycle time analysis

### Phase 4 Features (Backlog)
- **Offline mode**: Use service worker for offline task creation (sync on reconnect)
- **PDF export**: Generate weekly/monthly reports as PDF with charts
- **Webhook support**: Allow third-party integrations via webhooks
- **API rate limiting**: Expose public API to third-party developers
- **Advanced delegation**: Task approval workflows, manager oversight

---

## 11. ROLLOUT PHASES

### Phase 1: MVP (Core ADHD Features) — 8-10 Weeks
**Goal**: Launch minimum viable product with core GTD + ADHD-specific features

**Deliverables**:
- ✅ Dashboard (capture, 1-3-5, inbox, habits, focus mode)
- ✅ Task management (4-mode view, completion, delegation, rescheduling)
- ✅ Pomodoro timer (focus, logging, preset times)
- ✅ Reminders (email digest, in-app notifications)
- ✅ File management (organize by task/CRUD, auto-folders)
- ✅ Projects & tickets (resource hub, status workflow)
- ✅ CRUD tables (custom records, emergency contacts)
- ✅ Gamification (animations, streaks, badges)
- ✅ Authentication (email-based login, password reset)
- ✅ Admin settings (email, timezone, feature toggles)

**Success Metrics**:
- User signup: First 50 beta testers
- Daily active users: ≥ 30
- Task completion rate: ≥ 70% (tasks marked complete per week)
- Login retention: ≥ 60% (return within 7 days)

**Budget**: 300-400 developer hours (equivalent to 7-10 weeks @ 40h/week)

### Phase 2: Enhancements & Collaboration (8-10 Weeks; Months 3-4)
**Goal**: Add team features, dark mode, OAuth, calendar sync

**Deliverables**:
- Dark mode (CSS variants, user preference toggle)
- OAuth login (Google, Microsoft; optional classic email login)
- Calendar sync (Google Calendar read-only)
- Shared workspaces (team task assignment, permissions)
- Advanced reminders (SMS via Twilio if desired; push notifications)
- Email digest batching (weekly digest option)
- Mobile app PWA improvements (offline support, install prompt)

**Budget**: 200-250 developer hours

### Phase 3: Automation & Analytics (6-8 Weeks; Months 5-6)
**Goal**: AI suggestions, reporting, insights

**Deliverables**:
- Task priority suggestions (ML model trained on user data)
- Analytics dashboard (weekly/monthly completions, trends)
- Advanced filtering (saved searches, favorite views)
- Custom reports (PDF export with charts)
- Multi-language support (i18n framework, Spanish + French)

**Budget**: 150-200 developer hours

### Phase 4: Scaling & Marketplace (TBD)
**Goal**: Open ecosystem, advanced integrations

**Deliverables**:
- Public API (for developers to build integrations)
- Webhook support (trigger external actions from tasks)
- Integration marketplace (Slack, Teams, Asana, etc.)
- Advanced delegation workflows (task approval, manager oversight)

**Budget**: 200+ developer hours (ongoing)

---

## 12. NON-FUNCTIONAL REQUIREMENTS

### 12.1 Performance

**Desktop Performance** (Target; measure via Lighthouse):
- Page load: ≤ 2 seconds (first contentful paint)
- Time to interactive: ≤ 3.5 seconds
- Lighthouse performance score: ≥ 90

**Mobile Performance**:
- Page load: ≤ 3 seconds (first contentful paint)
- Time to interactive: ≤ 5 seconds
- Lighthouse performance score: ≥ 80

**API Response Times**:
- Read endpoints (GET): ≤ 200ms (average; 500ms 95th percentile)
- Write endpoints (POST/PUT): ≤ 500ms (including database commit)
- Heavy queries (large task list, file list): ≤ 1000ms

**Optimization Strategies**:
- Minified CSS/JS (production build)
- Gzip compression on server
- Browser cache headers (1 year for assets; 5 min for API)
- Database indexing on frequently searched columns (user_id, due_date, status)

### 12.2 Availability & Uptime

**Target Uptime**: 99.5% (≤ 3.6 hours downtime/month)
- Measured via external monitoring (e.g., UptimeRobot)
- Excludes planned maintenance (30-min windows announced 48h in advance)

**Planned Maintenance**: 
- Weekly: Wednesday 2-3 AM (off-peak; minimal users)
- Security patches: Within 24 hours of discovery

### 12.3 Data Integrity

**Database Consistency**:
- ACID transactions for critical operations (task completion, file upload)
- Foreign key constraints (prevent orphaned records)
- Backup + restore tested monthly (verify recovery works)

**Backup Strategy**:
- Daily incremental backups (off-site storage recommended)
- Monthly full backup verification (test restore process)
- Retention: 30 days of daily backups; 12 months of monthly archives

### 12.4 Security

**Encryption**:
- HTTPS/TLS 1.3+ (end-to-end encryption in transit)
- At-rest encryption for sensitive fields (passwords [always], bank info [optional])
- Session cookies: Secure + HttpOnly + SameSite=Strict

**Attack Prevention**:
- SQL injection: Parameterized queries (PDO prepared statements)
- XSS: HTML escaping on output; Content Security Policy headers
- CSRF: Token validation on all state-changing requests
- Brute force: Rate limiting on login (3 attempts per 5 minutes per IP)
- File upload: Validate MIME type + extension; scan for malware (phase 2)

**Authentication**:
- Password requirements: ≥ 8 characters, mixed case, number/symbol
- Password hashing: bcrypt with cost=12 (250+ms per hash)
- Session timeout: 30 minutes idle; max 4 hours active

### 12.5 Scalability

**Data Model**:
- Designed for ≤ 1M tasks per user (reasonable ceiling)
- Designed for ≤ 10GB user files per user (configurable quota)
- Queries optimized for ≤ 10K tasks in database (with indexing)

**Database Scaling**:
- Phase 1: Single MySQL server (sufficient for 1000s of users)
- Phase 2+: Consider read replicas for analytics queries (if traffic > 10K users)
- Long-term: Sharding by user_id (if required for 100K+ users)

**API Scaling**:
- Stateless PHP (easily deployed to multiple servers)
- Load balancer distributes requests (if needed)
- Redis for session storage (optional; advanced phase)

### 12.6 Compatibility

**Web Browsers** (Target Support):
- Chrome 90+ (2021+)
- Firefox 88+ (2021+)
- Safari 14+ (2020+)
- Edge 90+ (2021+)
- NOT supported: IE 11 (end of life; not worth support effort)

**Operating Systems**:
- Windows 10/11
- macOS 10.15+
- Linux (any modern distro)
- iOS 14+ (via responsive design + PWA)
- Android 10+ (via responsive design + PWA)

**Device Sizes**:
- Desktop: 1920x1080 (and up)
- Tablet: 768x1024 (iPad-sized)
- Mobile: 375x667 (iPhone-sized; minimum)
- Ultra-wide: 2560x1440 (ultrawide monitors)

---

## 13. PRE-LAUNCH REVIEW CHECKLIST

### Specification Sign-Off
- [ ] Project Overview approved (Sections 1-2)
- [ ] Authentication & Dashboard approved (Sections 3.1-3.2)
- [ ] All features approved (Sections 3.3-3.9)
- [ ] UI/Design approved (Section 4)
- [ ] Technical Architecture approved (Section 5)
- [ ] Greenfield build approach approved (Section 6)
- [ ] Accessibility requirements approved (Section 7)
- [ ] Privacy & data handling approved (Section 8)
- [ ] Deployment process approved (Section 9)

### Development Completeness
- [ ] All 23 Phase 1 API endpoints implemented
- [ ] All 15+ database tables created + migrations tested
- [ ] Authentication system working (login, logout, session management)
- [ ] Dashboard fully functional (capture, 1-3-5, inbox, habits)
- [ ] Task CRUD fully functional (create, read, update, delete, complete, reschedule)
- [ ] File management working (upload, download, organize, delete)
- [ ] Pomodoro timer functional (timer, preset times, logging)
- [ ] Reminders working (email digest, in-app notifications)
- [ ] Gamification implemented (streaks, badges, animations)
- [ ] CRUD tables working (create table, add records, edit, delete)
- [ ] Projects & tickets functional (creation, status workflow, resource hub)
- [ ] Admin settings working (feature toggles, email config, timezone)

### Code Quality
- [ ] PHP linting passes (no syntax errors)
- [ ] JavaScript linting passes (no console errors in dev)
- [ ] CSS validates (no malformed rules)
- [ ] No hardcoded secrets in code (all in config file)
- [ ] Error handling in place (try-catch, fallback UI)
- [ ] Input validation on all forms (server-side + client-side)
- [ ] SQL injection prevention verified (prepared statements only)
- [ ] XSS prevention verified (HTML escaping on output)
- [ ] CSRF protection verified (tokens on all forms)

### Testing
- [ ] Manual smoke testing completed (all major flows)
- [ ] Browser compatibility verified (Chrome, Firefox, Safari, Edge)
- [ ] Mobile responsive testing passed (375px to 2560px widths)
- [ ] Accessibility audit passed (Lighthouse ≥ 90)
- [ ] Keyboard navigation tested (Tab, Escape, Enter, Arrows)
- [ ] Screen reader testing passed (NVDA or VoiceOver)
- [ ] Performance targets met (load <2s, Lighthouse ≥90)
- [ ] Error handling tested (404, 500, network timeout scenarios)

### Security Audit
- [ ] Passwords hashed with bcrypt (verified in database)
- [ ] Session cookies have HttpOnly flag
- [ ] HTTPS enforcement in place (HTTP redirects to HTTPS)
- [ ] CSP headers set (mitigates XSS)
- [ ] Rate limiting tested (login brute force prevented)
- [ ] File upload validation working (MIME type check)
- [ ] Database backups tested (restore process verified)

### Deployment Readiness
- [ ] Production server set up (PHP 8.0+, MySQL 5.7+)
- [ ] Database created + schema migrated
- [ ] config.php created with environment variables
- [ ] Logs directory with 700 permissions
- [ ] Uploads directory with 755 permissions
- [ ] HTTPS/SSL certificate valid
- [ ] Web server (.htaccess or nginx.conf) configured
- [ ] Email sending tested (test email received)
- [ ] Backup automation in place (daily backups running)
- [ ] Monitoring set up (error logs, health checks)
- [ ] Rollback plan documented

### Documentation
- [ ] SPECIFICATION.md complete + up to date
- [ ] README.md with setup instructions
- [ ] API documentation (endpoint list, parameters, responses)
- [ ] Database schema documented (tables, columns, relationships)
- [ ] Deployment guide written (step-by-step instructions)
- [ ] Troubleshooting guide created (common errors + solutions)
- [ ] Privacy policy written + linked in footer
- [ ] Terms of service written + linked in footer

### User Onboarding
- [ ] First-run wizard or tutorial (optional; can be added phase 2)
- [ ] Help documentation accessible (in-app help overlay)
- [ ] Email templates tested (verification, password reset, reminder)
- [ ] Welcome email sent on signup
- [ ] Support contact info provided (email, contact form)

### Go/No-Go Decision
- [ ] All checklist items completed ✅ OR
- [ ] Known issues documented + accepted (if any)
- [ ] Project lead + Product owner sign-off
- [ ] Launch date confirmed
- [ ] Rollback plan communicated to team

---

## 14. OUTSTANDING QUESTIONS & CLARIFICATIONS

### Resolved Questions
1. ✅ **File Storage Location**: Public HTML (for API streaming) vs. Private? → PUBLIC_HTML/UPLOADS
2. ✅ **Database Wrapper Pattern**: Singleton vs. Static class? → SINGLETON (cleaner state management)
3. ✅ **Timezone Handling**: Server UTC vs. Browser local? → BROWSER LOCAL (simpler, more intuitive)
4. ✅ **Email Delivery**: Immediate vs. Digest? → USER CHOICE PER CHANNEL (Email digest/immediate; SMS digest/immediate)
5. ✅ **Backward Compatibility**: Use existing code or fresh build? → GREENFIELD BUILD (no legacy code; reference folder deleted after)
6. ✅ **Password Reset Flow**: Temporary password or user-generated? → USER GENERATES (via secure reset link; no insecure temp password)
7. ✅ **Task Completion Animation**: Duration and effect? → 400ms bounce + particles → 1500ms total
8. ✅ **Reminder Channels**: Email only vs. SMS only vs. Both? → EMAIL (REQUIRED) + SMS (OPTIONAL; Phase 1 skipped if not implementing)
9. ✅ **Admin Features**: Power user admin vs. limited admin? → ADMIN SEES ALL USERS; DELEGATES TASKS; SETS FEATURES
10. ✅ **Deletion Philosophy**: Soft delete (trash) vs. hard delete (permanent)? → HARD DELETE (permanent; ADHD users prefer finality)

### Potential Future Questions (For After Phase 1 Beta)
1. Should daily digest be weekly option instead? (User feedback on email frequency)
2. Should we add time zone per-user or per-task? (Complicated for Phase 1; can defer)
3. Should recurring tasks repeat on same time or on completion? (Currently on completion; can revisit)
4. Should admin be able to export all user data to CSV? (Data privacy concern; defer to Phase 2)
5. Should we implement two-factor auth for admin accounts? (Security best practice; Phase 2)
6. Should completed tasks show in archive immediately or after 24 hours? (Deferring to Phase 1 user testing)
7. Should we email admins when a user signs up? (Onboarding clarity; defer to Phase 1)
8. Should file upload have file type restrictions? (Currently all files allowed; can whitelist in Phase 2)
9. Should CRUD tables be exportable as CSV? (Useful for reviews; Phase 2 feature)
10. Should we support single sign-on (SSO) for organizations? (Enterprise feature; Phase 3+)

---

## ENDPOINT SPECIFICATION SUMMARY

### Phase 1 API Endpoints (23 Total)

**Authentication** (2):
- POST `/api/auth/login` — Email + password → Session created
- POST `/api/auth/logout` — Destroy session

**Tasks** (7):
- GET `/api/tasks` — Fetch user's tasks (with filters/sort)
- GET `/api/tasks/[id]` — Fetch single task detail
- POST `/api/tasks` — Create new task
- PUT `/api/tasks/[id]` — Update task
- DELETE `/api/tasks/[id]` — Delete task
- PUT `/api/tasks/[id]/complete` — Mark complete (trigger recurrence)
- PUT `/api/tasks/[id]/reschedule` — Reschedule task to new date

**Dashboard** (2):
- GET `/api/dashboard/priorities` — Fetch 1-3-5 daily priorities
- PUT `/api/dashboard/priorities` — Set 1-3-5 for today

**Notifications** (2):
- GET `/api/notifications` — Fetch user's in-app notifications
- PUT `/api/notifications/[id]/read` — Mark notification as read

**Files** (3):
- POST `/api/files/upload` — Upload file (associates with task/CRUD)
- GET `/api/files/[id]/download` — Download file (with access control)
- DELETE `/api/files/[id]` — Delete file

**Users** (3):
- GET `/api/users` — List all users (admin only)
- POST `/api/users` — Create user (admin only)
- PUT `/api/users/[id]` — Update user (admin or self)
- DELETE `/api/users/[id]` — Delete user (admin only)
- PUT `/api/users/[id]/timezone` — Set user timezone (self)

**Settings** (2):
- GET `/api/settings` — Fetch settings (admin only)
- PUT `/api/settings` — Update settings (admin only)

**Health** (1):
- GET `/api/health` — System health check (for monitoring)

---

## DATABASE SCHEMA SUMMARY (Phase 1; 15+ Tables)

### Core Tables
1. `users` — User accounts (id, email, password_hash, name, timezone, created_at)
2. `sessions` — PHP sessions (session_id, user_id, data, expires_at)
3. `tasks` — All tasks (id, user_id, title, description, status, priority, due_date, created_at)
4. `priorities_daily` — User's daily 1-3-5 (id, user_id, date, priority_1_id, priority_3_ids, priority_5_ids)
5. `task_files` — Files associated with tasks (id, task_id, user_id, file_path, filename, created_at)
6. `task_assignments` — Delegated tasks (id, task_id, assigned_to_user_id, assigned_by_user_id, status)

### Reminders & Notifications
7. `reminders` — Scheduled reminders (id, user_id, task_id, trigger_date, reminder_type, status, created_at)
8. `notifications` — In-app notifications (id, user_id, type, message, read, created_at)

### Projects & Tickets
9. `projects` — Projects (id, user_id, name, color, created_at)
10. `tickets` — Tickets/issues (id, project_id, title, status, priority, review_date, created_at)
11. `ticket_resources` — Resource hub items (id, ticket_id, type [file/url/crud/contact], resource_id, created_at)
12. `ticket_comments` — Ticket comments (id, ticket_id, user_id, comment, created_at)

### CRUD Tables
13. `crud_tables` — Custom CRUD table definitions (id, user_id, table_name, created_at)
14. `crud_fields` — Custom fields per table (id, table_id, field_name, field_type, created_at)
15. `crud_records` — Records per custom table (id, table_id, data_json, created_at)

### Admin & Settings
16. `user_settings` — Feature toggles + config per user (id, user_id, setting_key, setting_value)
17. `activity_log` — Audit trail (id, user_id, action, record_id, record_type, change_summary, created_at)

---

## CONCLUSION & PHASE 1 READINESS

**Specification Status**: ✅ COMPLETE & APPROVED (14 Sections + 23 Endpoints + 17 Tables)

**Build Readiness**: Team is ready to begin Phase 1 implementation immediately. All design decisions finalized, no blockers.

**Expected Timeline**: 8-10 weeks (300-400 developer hours)

**Success Metrics**:
- 50+ beta testers signed up
- 70%+ daily task completion rate
- 60%+ weekly retention rate
- Lighthouse accessibility ≥ 90
- Zero critical security issues

---

**Specification Written By**: [Your Name]  
**Approved By**: [Product Owner]  
**Date Completed**: January 2024  
**Last Updated**: [Current Date]
  - Create projects (categories: e.g., "Veterinarian", "Home Repair", "Annual Doctor Visits")
  - Create tickets under projects (sub-items: e.g., "Rover", "Fluffy", "Roof Inspection")
  - Ticket detail view (two-column layout: left info + resources, right notes + activity log)
  - Ticket status: New, Open, Paused, Closed (independent from tasks)
  - Ticket priority: Urgent, High, Medium, Low
  - Review date (optional reminder for when to revisit)
  - Tabbed dashboard: Review Due Soon | Open | Paused | Closed
  - Link tasks to tickets (many-to-one)

- **Ticket Resource Hub** (Flexible attachments)
  - Attach files (upload, download, preview, delete)
  - Store external URLs (clickable reference links)
  - Embed CRUD records (e.g., "View Current Medications", "Show Insurance Card on File")
  - Embed contact information (name, phone, email, address)
  - Rich text notes/instructions
  - Reorder resources (drag-to-sort)
  - Pin important resources to top
  - Print all resources as PDF

- **Ticket Activity Log**
  - Timestamped comments/notes
  - Status change history (with "changed from X to Y" tracking)
  - Auto-log who created ticket and when
  - Edit notes (optional; track edit history)

- **CRUD Tables (Customizable Data Management)**
  - Pre-defined templates with quick actions:
    - **Medication Tracker** (Name, Dosage, Frequency, Refill Due, Prescriber, Pharmacy)
    - **Banking/Financial Info** (Account Type, Institution, Last 4, Card Number, Expiration, CVV, Zipcode, Phone) — encrypted in DB
    - **Bill Tracker** (Vendor, Amount, Due Date, Category, Status) — recurring option
    - **Recipe Tracker** (Ingredients, Instructions, Times, Category, URL, Photo)
  - Custom CRUD tables (user-defined fields: text, number, email, date, checkbox, dropdown, file)
  - List view (sortable, searchable, filterable)
  - Detail view (full record editing)
  - Bulk actions (mark multiple, export to PDF/CSV)
  - File attachments per record
  - Quick link buttons on dashboard ("View My Medications", "Show Credit Cards", etc.)
  - Shared CRUD data (certain tables shared with other users, read-only or read-write)

- **File Management System**
  - User-isolated file storage (/private/uploads/{user_id}/)
  - Directory tree view or flat list
  - Upload, download, delete, rename, move files
  - Create folders for organization
  - Attachment to tasks or CRUD records (user choice: delete with parent or keep)
  - File sharing with other users (read-only or read-write)

- **Reminders & Notifications (Multi-Channel)**
  - Reminder types: Due Date, Recurring, Medication Refill, Bill Due, Custom
  - Notification channels: In-app bell + email digest or immediate
  - Quiet hours: No notifications 9 PM - 8 AM (user-configurable)
  - Reminders settable on tasks, CRUD records, recurring items
  - Email sent from application (with user name in display)
  - In-app notification bell with dropdown (last 5-10 notifications)
  - Bell badge shows count of unread reminders
  - Mark as read, dismiss, navigate from notification

- **Backend Enhancements**
  - Project and Ticket classes
  - TicketResource management (files, URLs, CRUD links)
  - CRUD field definition and dynamic form generation
  - Reminder scheduling and email sending
  - File upload validation, encryption for sensitive data

- **UI/UX Enhancements**
  - Ticket card design (title, project name, status, priority, review date, task count)
  - Resource cards (files with icon/name, URLs, CRUD links, contacts)
  - CRUD list as table with quick edit, detail pop-out
  - File tree view or card layout
  - Toast notifications for actions (file uploaded, reminder set, etc.)
  - Print-friendly page styling (for CRUD records, 1-3-5 list, project details)

**Deliverables:**
- 5 additional database tables (projects, tickets, ticket_resources, ticket_tasks, ticket_comments)
- Projects & Tickets fully functional with resource hub
- CRUD system with pre-defined + custom templates
- File management system operational
- Reminders & notifications sending
- 20+ new API endpoints (projects, tickets, resources, CRUD, files, reminders)

**Testing:** Projects & Tickets dashboard tabs, resource attachment flow, CRUD record creation, file upload/download, reminder delivery (email + in-app)

---

### Phase 3: Gamification & Pomodoro - Engagement & Tracking
**Goal:** Add motivational features, visual feedback, and productivity tracking (positive-only).

**Features:**
- **Pomodoro Timer (Standalone, Impressive Visual)**
  - Large, friendly countdown display (analog/digital hybrid style)
  - Quick preset buttons: 10, 20, 30, 45, 60, 90, 120 minutes
  - Start, Pause, Resume, Cancel buttons
  - Optional floating mini-timer badge (persistent while running)
  - Audio alert on completion (multiple sounds; mute-able)
  - Optional "Log This Session" on completion
  - Session logging to optional `/pomodoro_sessions` table (if user chooses)
  - Link session to task (if started from task detail)
  - Success feedback: Confetti animation, encouraging message

- **Gamification Elements**
  - **Side Quests** (GTD Next-Action randomizer)
    - View Next Actions organized by category
    - Virtual dice roll to assign random action
    - User selects "Accept Quest" to start
    - Reduces decision paralysis, adds novelty
  
  - **Streaks** (Optional, user-enabled)
    - Consecutive days of Pomodoro sessions logged
    - Display as simple counter (no "failure" on missed days, just resets)
    - Can track consecutive days of completed daily habits
  
  - **Badges** (One-time achievements, optional)
    - First task completed, First 7-day streak, 100 Pomodoro sessions, etc.
    - Display in profile or dashboard widget
    - Never mandatory; purely motivational

- **Habit Tracker (User-Created, Optional)**
  - User can create tracked habits (e.g., "Gym 3x/week", "Brush teeth 5x/week")
  - Daily checkboxes
  - Weekly goal tracker (pie chart showing progress: "You've completed 4/5 this week!")
  - Shows achievement, never failure ("You did it once—great!")
  - Reset on schedule (weekly/daily)

- **Success Analytics Dashboard (Optional, User-Initiated)**
  - Pomodoro session log (query completed sessions, show trends)
  - Display as: "You've completed X sessions!" "Average session: X minutes!"
  - Completed tasks by category (bar chart)
  - Weekly task completion count
  - No percentages, grades, or "you missed X tasks"
  - Never shown automatically; user must navigate to Reports page

- **Admin Panel (Enhanced)**
  - User management dashboard (list users, create, edit, deactivate)
  - System settings:
    - Email configuration (SMTP/mail settings)
    - Theme palette settings (read-only; default themes provided)
    - Recurring task preferences (auto-reschedule or manual)
    - Data retention settings (archive or delete old tasks)
    - Notification settings (summary frequency, quiet hours default)
  - Activity log viewer (optional; for debugging)
  - Backup/export functionality

- **Backend Enhancements**
  - Pomodoro session logging
  - Badge/streak logic
  - Habit tracker CRUD
  - Analytics query functions (positive-only data)
  - Admin settings storage and retrieval

- **UI/UX Enhancements**
  - Timer display page (dedicated focus mode)
  - Side Quest modal with dice animation
  - Streak counter widget on dashboard
  - Badge display (profile or dashboard)
  - Habit tracker widget (daily checklist with weekly progress)
  - Success feedback animations (confetti, checkmarks, positive messages)
  - Admin panel dashboard with cards for each section
  - Print-friendly success report

**Deliverables:**
- Pomodoro timer fully functional and impressive-looking
- Gamification elements (Side Quests, Streaks, optional Badges)
- Habit tracker working
- Analytics & reports (positive-only)
- Admin dashboard operational
- 8+ new tables (pomodoro_sessions, habit_trackers, habit_entries, badges_earned, etc.)
- 15+ new API endpoints

**Testing:** Timer countdown accuracy, Side Quest randomization, Streak reset logic, Habit tracker weekly reset, Analytics data accuracy (no negative feedback), Admin settings persist

---

### Phase 4: Polish, Accessibility, & Scale
**Goal:** Production-ready application with full accessibility, performance optimization, documentation.

**Features:**
- **Accessibility (WCAG 2.1 AA Compliance)**
  - Full keyboard navigation (no mouse required)
  - Screen reader testing (NVDA, JAWS compatibility)
  - ARIA labels on all interactive elements
  - Alt text on all images and icons
  - Form labels properly associated
  - Focus indicators visible on all interactive elements
  - Skip navigation link at top of page
  - Color contrast verification (≥ 4.5:1 for all text)
  - Page language declaration

- **Performance Optimization**
  - Database indexing (frequently queried columns)
  - CSS/JS minification
  - Image optimization
  - Lazy loading for off-screen content
  - Caching strategies (browser caching, optional Redis)
  - Query optimization (avoid N+1 problems)
  - Page load time target: < 2s desktop, < 3s mobile
  - API response time target: < 500ms

- **Mobile App (Optional Future)**
  - Responsive web app (PWA) with offline capability (Phase 4+ only if desired)
  - Or recommend progressive enhancement for existing web app

- **Documentation & User Guides**
  - Admin setup guide (how to create users, configure settings)
  - User quick-start guide (how to add first task, create 1-3-5, etc.)
  - FAQ for common questions
  - Video tutorials (optional)
  - API documentation (for future integrations)

- **Testing & QA**
  - Automated testing (unit tests for core functions)
  - Manual testing across all devices
  - Accessibility audit (axe DevTools, Lighthouse)
  - Performance testing (load testing with simulated users)
  - Security testing (OWASP top 10 checks)

- **Deployment & Monitoring**
  - Production environment setup guide
  - Automated backup system (database + /uploads/ directory)
  - Error monitoring & alerting
  - Uptime monitoring
  - Log aggregation

- **Optional Enhancements (Phase 4+)**
  - Dark mode (additional color palettes)
  - Two-factor authentication
  - API for third-party integrations
  - Calendar view integration (Google Calendar, Outlook)
  - Advanced reporting & exports
  - Multi-language support (internationalization)

**Deliverables:**
- Fully accessible application (AA compliance verified)
- Performance benchmarks met
- Complete documentation
- Deployment guide
- Monitoring/alerting setup
- Optional: PWA offline capability

**Testing:** Full accessibility audit, performance load testing, cross-browser/device testing, security penetration testing (basic)


