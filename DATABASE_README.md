# ADLOR Database Setup Guide

## Overview
The ADLOR (Attendance and Data Logging with QR) system uses a MySQL database with 4 main tables to manage student attendance through QR code scanning.

## Database Configuration

### Local Development (XAMPP)
The system is now configured to use a local MySQL database:
- **Host:** localhost
- **Username:** root
- **Password:** (empty)
- **Database:** adlor_db

### Database Structure

#### 1. `official_students` Table
Stores the official list of students (uploaded via CSV by admin)
```sql
- id (INT, AUTO_INCREMENT, PRIMARY KEY)
- student_id (VARCHAR(50), UNIQUE) - Student ID number
- full_name (VARCHAR(255)) - Student's full name
- course (VARCHAR(100)) - Course (e.g., BSIT, BSCS)
- section (VARCHAR(50)) - Section (e.g., IT-3A, CS-2B)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

#### 2. `students` Table
Stores registered students with login credentials
```sql
- id (INT, AUTO_INCREMENT, PRIMARY KEY)
- student_id (VARCHAR(50), UNIQUE) - Links to official_students
- full_name (VARCHAR(255)) - Student's full name
- course (VARCHAR(100)) - Course
- section (VARCHAR(50)) - Section
- password (VARCHAR(255)) - Hashed password
- photo (VARCHAR(500)) - Path to uploaded photo
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

#### 3. `events` Table
Stores events/activities for attendance tracking
```sql
- id (INT, AUTO_INCREMENT, PRIMARY KEY)
- title (VARCHAR(255)) - Event title
- description (TEXT) - Event description
- start_datetime (DATETIME) - Event start time
- end_datetime (DATETIME) - Event end time
- assigned_sections (TEXT) - Comma-separated list of sections
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

#### 4. `attendance` Table
Stores attendance records with time in/out
```sql
- id (INT, AUTO_INCREMENT, PRIMARY KEY)
- student_id (VARCHAR(50)) - Links to students table
- event_id (INT) - Links to events table
- time_in (TIMESTAMP) - When student scanned in
- time_out (TIMESTAMP, NULL) - When student scanned out
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
- UNIQUE constraint on (student_id, event_id)
```

## Setup Instructions

### 1. Initial Database Setup
Run the database setup script:
```bash
php setup_database.php
```
This will:
- Create the database if it doesn't exist
- Create all required tables
- Verify the setup

### 2. Add Sample Data (Optional)
Add sample students and events for testing:
```bash
php add_sample_data.php
```
This adds:
- 8 sample students (IDs: 202300001 to 202300008)
- 4 sample events

### 3. Database Administration
Use the admin interface to manage data:
```
http://localhost/ken/database_admin.php
```

## File Structure

### Database Files
- `db_connect.php` - Database connection configuration
- `setup_database.php` - Database setup script
- `setup_database.sql` - SQL schema (reference only)
- `add_sample_data.php` - Sample data insertion
- `database_admin.php` - Web-based database administration

### Application Files
- `admin/upload_students.php` - Upload official students via CSV
- `student_register.php` - Student registration
- `student_login.php` - Student login
- `sbo/create_event.php` - Create events
- `scan_qr.php` - QR code scanning for attendance
- `generate_qr.php` - Generate QR codes for students

## Usage Workflow

1. **Admin uploads official students** via CSV using `admin/upload_students.php`
2. **Students register** using their official student ID at `student_register.php`
3. **SBO creates events** using `sbo/create_event.php`
4. **Students generate QR codes** for specific events
5. **Attendance is recorded** by scanning QR codes at `scan_qr.php`

## Sample Data

### Sample Students
- 202300001 - John Doe (BSIT, IT-3A)
- 202300002 - Jane Smith (BSCS, CS-2B)
- 202300003 - Mike Johnson (BSIT, IT-3A)
- 202300004 - Sarah Wilson (BSCS, CS-2B)
- 202300005 - Alex Brown (BSIT, IT-3B)
- 202300006 - Emily Davis (BSCS, CS-3A)
- 202300007 - Chris Lee (BSIT, IT-2A)
- 202300008 - Maria Garcia (BSCS, CS-2A)

### Sample Events
- Orientation Program (Jan 15, 2025)
- Tech Seminar (Jan 20, 2025)
- Career Fair (Jan 25, 2025)
- Programming Contest (Feb 1, 2025)

## Troubleshooting

### Connection Issues
- Ensure XAMPP MySQL service is running
- Check if port 3306 is available
- Verify database credentials in `db_connect.php`

### Permission Issues
- Ensure PHP has write permissions for uploads folder
- Check file permissions for QR code generation

### Data Issues
- Use `database_admin.php` to view and manage data
- Check foreign key constraints when deleting records
- Verify CSV format when uploading students

## Security Notes
- Change default admin password in `admin/upload_students.php`
- Use strong passwords for student accounts
- Consider adding SSL/HTTPS for production
- Regularly backup the database

## Backup and Restore
```bash
# Backup
mysqldump -u root -p adlor_db > adlor_backup.sql

# Restore
mysql -u root -p adlor_db < adlor_backup.sql
```
