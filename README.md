# Student Routine Organizer - Web Application

## 📋 Project Overview

**Student Routine Organizer** is a comprehensive web application designed to help students manage and improve their daily routines. This is a **team project** built using **3-tier architecture** with **PHP** and **MySQL**.

## 👥 Team Structure

This project is developed by **4 team members**, each responsible for one module, plus an **Admin** utility area. All modules are available and functional:

| Module | Developer | Status |
|--------|-----------|---------|
| 🏃‍♂️ **Exercise Tracker** | Jooyee | ✅ Complete |
| 📖 **Diary Journal** | JunHui | ✅ Complete |
| 💰 **Money Tracker** | Wilson | ✅ Complete |
| ✅ **Habit Tracker** | Ng Xue En | ✅ Complete |
| 🛠️ **Admin** | Wilson | ✅ Complete |

## 🎯 Module: Exercise Tracker

For the **Exercise Tracker**, which includes:
### ✅ Features Implemented
- **Create** workout records with type, date, duration, calories, optional time and notes
- **Read** history with responsive cards and details
- **Update/Delete** existing exercise records with confirmation
- **Filter & Search** by date range, type, duration, calories; clear/summary of active filters
- **Sort** by date, duration, calories, or type
- **Statistics**: totals (workouts, minutes, calories) and 7‑day summary; average duration

## 🎯 Module: Money Tracker

For the **Money Tracker**, which includes:
### ✅ Features Implemented
- **Create** income/expense transactions with category and description
- **Read** paginated transaction history with styled cards
- **Update/Delete** transactions with ownership checks
- **Filters** by month, type (income/expense), and category
- **Summaries**: overall totals (income, expenses, balance) and current month breakdown
- **Badges** and color‑coded amounts for quick scanning

## 🎯 Module: Admin

For the **Admin**, which includes:
### ✅ Features Implemented
- **Admin Authentication** with dedicated login/logout
- **Dashboard** overview for administrative actions
- **User Management**: view/manage users (`admin/users.php`)
- **Settings** management (`admin/settings.php`)
- **Backup** utility (`admin/backup.php`)
- **Error Review** page for logged errors (`admin/errors.php`, integrates with `logs/error.log`)

## 🎯 Module: Diary Journal

For the **Diary Journal Module**, which includes:
### ✅ Features Implemented
- **User Registration & Authentication** (shared)
- **Create** new diary entries with title, date, mood, and rich-text content
- **Read** entries list with search/filter component and calendar view
- **Update** existing entries with pre-populated forms
- **Delete** entries with confirmation and security checks
- **Mood tracking** with multiple mood options and badges
- **History & sorting** by date (newest first)

## 🎯 Module: Habit Tracker

For the **Habit Tracker Module**, which includes:
### ✅ Features Implemented
- **User Registration & Authentication** (shared across all modules)
- **Create** new habits with name, description, category, frequency, type (regular/timer), and start date
- **Read** all habits grouped by category with progress visualization
- **Update** habits with pre-populated forms for easy editing
- **Delete** habits with confirmation, removing associated logs as well
- **Daily Logging** with Mark/Unmark Today action to track completion
- **Streak Tracking** showing continuous days completed
- **Weekly Progress Bar** with percentage indicator for last 7 days
- **Timer Functionality** for duration-based habits, with auto-log when stopped
- **History Log** of past completions with details and status
- **Search & Filter** habits by name, category, and status with collapsible filter bar

 

### 🔧 Technical Implementation
- **3-Tier Architecture**
  - **Presentation Layer**: HTML/CSS/JavaScript frontend
  - **Business Logic Layer**: PHP processing and validation
  - **Data Layer**: MySQL database with relationships
- **Security Features**
  - Password hashing with PHP's `password_hash()`
  - SQL injection prevention with prepared statements (PDO/MySQLi)
  - Advanced session management, login attempt tracking, remember-me cookies
  - Data validation and sanitization
- **Password Reset**: Token-based flow with `password_reset_tokens` table (see `PASSWORD_RESET_SETUP.md`)
- **Responsive Design** with modern UI/UX

## 📁 Project Structure

```
student-routine-organizer/
├── admin/
│   ├── dashboard.php         # Admin dashboard (user management/settings)
│   └── users.php             # Admin: users list
├── config/
│   └── database.php          # Database connection (shared)
├── includes/
│   ├── header.php            # Common header with navigation
│   ├── footer.php            # Common footer
│   ├── SessionManager.php    # Advanced session handling
│   ├── CookieManager.php     # Remember-me cookie management
│   ├── ErrorHandler.php      # Centralized error logging
│   └── PasswordResetHandler.php # Password reset logic
├── auth/
│   ├── login.php             # User login
│   ├── register.php          # User registration
│   ├── logout.php            # Logout
│   ├── forgot_password.php   # Start password reset
│   └── reset_password.php    # Complete password reset
├── diary/
│   ├── index.php             # View entries (READ)
│   ├── add_entry.php         # Add entry (CREATE)
│   ├── edit_entry.php        # Edit entry (UPDATE)
│   └── delete_entry.php      # Delete entry (DELETE)
├── exercises/                # Team Member 1's module (placeholder)
│   ├── index.php             # View workout activities history (READ)
│   ├── add_exercise.php      # Add workout activities (CREATE)
│   ├── edit_exercise.php     # Edit workout activities record (UPDATE)
│   └── delete_exercise.php   # Delete workout activities record (DELETE)
├── money/
│   ├── index.php             # Finance dashboard + filters + pagination
│   ├── add_transaction.php   # Add income/expense
│   └── edit_transaction.php  # Edit transaction
├── habits/                   # Habit module 
│   ├── index.php             # View habit dashboard (READ)
│   ├── add_habit.php         # Add habit (CREATE)
│   ├── edit_habit.php        # Edit habit (UPDATE)
│   ├── delete_habit.php      # Delete habit (DELETE)
│   ├── toggle_today.php      # Toggle (mark/unmark) a habit for today
│   ├── habit_detail.php      # Display details for a single habit
│   └── habit_fucntions.php   # Core db & authorisation
├── css/
│   └── style.css             # Shared styling
├── logs/
│   └── error.log             # Error logs (+ password reset email log)
├── index.php                 # Main dashboard
├── PASSWORD_RESET_SETUP.md   # Password reset setup and docs
└── database_schema_clean.sql # Database schema
```

## 🚀 Setup Instructions

### Prerequisites
- XAMPP (Apache + MySQL + PHP)
- Web browser

### Installation Steps
1. **Start XAMPP**: Launch Apache and MySQL services
2. **Create Database**: 
   - Open phpMyAdmin (`http://localhost/phpmyadmin`)
   - Run the SQL from `student_routine_db.sql`
   - If using password reset, ensure the `password_reset_tokens` table exists (see `PASSWORD_RESET_SETUP.md`)
3. **Access Application**: Visit `http://localhost/student-routine-organizer/`

### Test Your Module
1. Register a new user account
2. Login with your credentials
3. Navigate to Diary Journal module
4. Test all CRUD operations:
   - Add new diary entries
   - View entries list
   - Edit existing entries
   - Delete entries
5. Test mood tracking and date functionality

## 🎨 UI Features

- **Modern Design** with gradient styling
- **Responsive Layout** for mobile and desktop
- **Font Awesome Icons** throughout the interface
- **Interactive Elements** with hover effects
- **Success/Error Messages** for user feedback
- **Character Counters** and form validation
- **Auto-expanding textareas** for better UX

## 🔒 Security Measures

- Password hashing with PHP's built-in functions
- Prepared statements to prevent SQL injection
- Session-based authentication with rotation and timeouts
- User data isolation (users only see their own data)
- Input validation and sanitization
- Secure password requirements (registration: min 6; reset: strong policy)
- Remember-me cookies with secure handling
- Password reset tokens with expiration and one-time use

## 📊 Assignment Requirements Met

✅ **3-Tier Architecture**: Presentation, Business Logic, Data layers  
✅ **CRUD Operations**: Create, Read, Update, Delete functionality  
✅ **User Authentication**: Registration, login, logout system  
✅ **Database Connectivity**: MySQL with proper relationships  
✅ **PHP & MySQL**: Server-side processing and data storage  
✅ **Web Application**: Accessible via web browser  
✅ **Module Specific Features**: Diary journaling with mood tracking and calendar view  
✅ **Module Specific Features**: Exercise Tracker with filters, notes, and stats  
✅ **Module Specific Features**: Money Tracker with summaries, filters, and pagination  
✅ **Module Specific Features**: Habit Tracker with streaks, timers, and history 

## 🤝 Integration with Team

**Diary Journal Module** is designed to integrate seamlessly with the other team modules:

- **Shared Authentication**: All modules use the same user login system
- **Consistent UI/UX**: Same styling and navigation across modules
- **Database Structure**: Follows same naming conventions and relationships
- **Security Standards**: Implements same security measures

## 📝 Notes for Team Integration

When other team members complete their modules, they should:
1. Use the existing `users` table for authentication
2. Follow the same file structure pattern (`module_name/index.php`, etc.)
3. Use the shared CSS classes and styling
4. Include navigation links in `includes/header.php`
5. Add their module statistics to the dashboard in `index.php`

## 🏆 Demonstration Points

When presenting your module, highlight:
1. **Complete CRUD functionality** with all operations working
2. **User authentication** and data security
3. **3-tier architecture** implementation
4. **Database relationships** and proper queries
5. **Professional UI/UX** with responsive design
6. **Input validation** and error handling
7. **Mood tracking feature** specific to diary journaling
8. **Team integration** readiness

---
