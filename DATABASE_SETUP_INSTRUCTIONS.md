# School Management System Database Setup

## Step-by-Step Instructions

### 1. Open phpMyAdmin
- Go to `http://localhost/phpmyadmin` in your browser
- Login with your MySQL credentials (usually username: `root`, password: empty)

### 2. Create Database
- Click "New" in the left sidebar
- Enter database name: `schoollaravel`
- Click "Create"

### 3. Import Database Structure
- Select the `schoollaravel` database you just created
- Click on the "Import" tab
- Click "Choose File" and select `database_structure.sql`
- Click "Go" to execute

### 4. Import Sample Data
- Still in the `schoollaravel` database
- Click on the "Import" tab again
- Click "Choose File" and select `sample_data.sql`
- Click "Go" to execute

### 5. Verify Setup
After importing, you should see these tables:
- users (4 sample users)
- roles (4 roles)
- teachers (1 sample teacher)
- parents (1 sample parent)
- students (1 sample student)
- grades (1 sample grade)
- subjects (3 sample subjects)
- attendances (5 sample attendance records)
- And other supporting tables

### 6. Test Login Credentials
You can now use these credentials to login:

**Admin Account:**
- Email: admin@mail.com
- Password: codeastro.com

**Teacher Account:**
- Email: teacher@mail.com
- Password: codeastro.com

**Parent Account:**
- Email: parent@mail.com
- Password: codeastro.com

**Student Account:**
- Email: student@mail.com
- Password: codeastro.com

### 7. Start Laravel Server
After database setup, try to start your Laravel application:

```bash
php artisan serve
```

If you get PHP compatibility errors, try:
```bash
php -S localhost:8000 -t public
```

### 8. Access Your Application
Open your browser and go to:
- `http://localhost:8000` (if using artisan serve)
- `http://localhost/school_management/public` (if using XAMPP directly)

## Files Created:
1. `database_structure.sql` - Creates all database tables
2. `sample_data.sql` - Inserts sample data for testing
3. `DATABASE_SETUP_INSTRUCTIONS.md` - This instruction file

## Troubleshooting:
- If import fails, make sure you created the database first
- If you get foreign key errors, run `database_structure.sql` before `sample_data.sql`
- If login doesn't work, verify the users table has the sample data
- If Laravel still has PHP errors, consider downgrading to PHP 7.4 or 8.0
