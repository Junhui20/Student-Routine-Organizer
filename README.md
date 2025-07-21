# Student Routine Organizer - Web Application

## 📋 Project Overview

**Student Routine Organizer** is a comprehensive web application designed to help students manage and improve their daily routines. This is a **team project** built using **3-tier architecture** with **PHP** and **MySQL**.

## 👥 Team Structure

This project is developed by **4 team members**, each responsible for one module:

| Module | Developer | Status |
|--------|-----------|---------|
| 🏃‍♂️ **Exercise Tracker** | Team Member 1 | In Development |
| 📖 **Diary Journal** | **You** | ✅ **Complete** |
| 💰 **Money Tracker** | Team Member 2 | In Development |
| ✅ **Habit Tracker** | Team Member 3 | In Development |

## 🎯 Your Module: Diary Journal

You are responsible for the **Diary Journal Module**, which includes:

### ✅ Features Implemented
- **User Registration & Authentication** (shared across all modules)
- **Create** new diary entries with title, date, mood, and content
- **Read** all diary entries with proper formatting and sorting
- **Update** existing entries with pre-populated forms
- **Delete** entries with confirmation and security checks
- **Mood Tracking** with 8 different mood options
- **Entry History** sorted by date (newest first)
- **User Statistics** showing total entries and weekly count

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
├── exercise/                 # Team Member 1's module (placeholder)
├── money/                    # Team Member 2's module (placeholder)
├── habits/                   # Team Member 3's module (placeholder)
├── css/
│   └── style.css             # Shared styling
├── index.php                 # Main dashboard
└── database_schema.sql       # Database setup
```

## 🗃️ Database Schema

### Tables You're Using:

#### `users` (Shared across all modules)
```sql
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### `diary_entries` (Your module)
```sql
CREATE TABLE diary_entries (
    entry_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    mood VARCHAR(20) NOT NULL,
    entry_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
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

## 🤝 Integration with Team

Your **Diary Journal Module** is designed to integrate seamlessly with the other team modules:

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

**Your Diary Journal Module is complete and ready for demonstration!** 🎉 