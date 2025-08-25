# Student Routine Organizer - Web Application

## 📋 Project Overview

**Student Routine Organizer** is a comprehensive web application designed to help students manage and improve their daily routines. This is a **team project** built using **3-tier architecture** with **PHP** and **MySQL**.

## 👥 Team Structure

This project is developed by **4 team members**, each responsible for one module:

| Module | Developer | Status |
|--------|-----------|---------|
| 🏃‍♂️ **Exercise Tracker** | Team Member 1 | In Development |
| 📖 **Diary Journal** | **Hui** | ✅ **Need Check** |
| 💰 **Money Tracker** | Team Member 2 | In Development |
| ✅ **Habit Tracker** | Ng Xue En | ✅ **Need Check** |

## 🎯 Module: Diary Journal

For the **Diary Journal Module**, which includes:

### ✅ Features Implemented
- **User Registration & Authentication** (shared across all modules)
- **Create** new diary entries with title, date, mood, and content
- **Read** all diary entries with proper formatting and sorting
- **Update** existing entries with pre-populated forms
- **Delete** entries with confirmation and security checks
- **Mood Tracking** with 8 different mood options
- **Entry History** sorted by date (newest first)
- **User Statistics** showing total entries and weekly count

## 🎯 Module: Habit Trcaker

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
- **3-Tier Architecture**:
  - **Presentation Layer**: HTML/CSS/JavaScript frontend
  - **Business Logic Layer**: PHP processing and validation
  - **Data Layer**: MySQL database with relationships
- **Security Features**:
  - Password hashing with PHP's `password_hash()`
  - SQL injection prevention with prepared statements
  - User session management
  - Data validation and sanitization
- **Responsive Design** with modern UI/UX

## 📁 Project Structure

```
student-routine-organizer/
├── config/
│   └── database.php           # Database connection (shared)
├── includes/
│   ├── header.php            # Common header with navigation
│   └── footer.php            # Common footer
├── auth/                     # Authentication system (shared)
│   ├── login.php             # User login
│   ├── register.php          # User registration
│   └── logout.php            # Logout functionality
├── diary/                    # YOUR MODULE
│   ├── index.php             # View entries (READ)
│   ├── add_entry.php         # Add entry (CREATE)
│   ├── edit_entry.php        # Edit entry (UPDATE)
│   └── delete_entry.php      # Delete entry (DELETE)
├── exercises/                # Team Member 1's module (placeholder)
│   ├── index.php             # View workout activities history (READ)
│   ├── add_exercise.php      # Add workout activities (CREATE)
│   ├── edit_exercise.php     # Edit workout activities record (UPDATE)
│   └── delete_exercise.php   # Delete workout activities record (DELETE)
├── money/                    # Team Member 2's module (placeholder)
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
├── index.php                 # Main dashboard
└── database_schema.sql       # Database setup
```

## 🚀 Setup Instructions

### Prerequisites
- XAMPP (Apache + MySQL + PHP)
- Web browser

### Installation Steps
1. **Start XAMPP**: Launch Apache and MySQL services
2. **Create Database**: 
   - Open phpMyAdmin (`http://localhost/phpmyadmin`)
   - Run the SQL from `database_schema.sql`
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
- Session-based authentication
- User data isolation (users only see their own data)
- Input validation and sanitization
- Secure password requirements (minimum 6 characters)

## 📊 Assignment Requirements Met

✅ **3-Tier Architecture**: Presentation, Business Logic, Data layers  
✅ **CRUD Operations**: Create, Read, Update, Delete functionality  
✅ **User Authentication**: Registration, login, logout system  
✅ **Database Connectivity**: MySQL with proper relationships  
✅ **PHP & MySQL**: Server-side processing and data storage  
✅ **Web Application**: Accessible via web browser  
✅ **Module Specific Features**: Diary journaling with mood tracking  
✅ **Module Specific Features**: Exercise Tracker with workout activities tracking  
✅ **Module Specific Features**: Habit Tracker with progress tracking 

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
