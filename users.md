# ADLOR System User Credentials

## 🔐 Login Information

### 👨‍💼 Admin Users
| Email | Password | Role | Access Level |
|-------|----------|------|--------------|
| admin@adlor.edu | admin123456 | System Administrator | Full System Access |
| superadmin@adlor.edu | superadmin123 | Super Administrator | Complete Database Access |
| system@adlor.edu | adl0rsecure2025 | System Manager | System Management |

### 👥 SBO (Student Body Organization) Users
| Email | Password | Name | Position | Access Level |
|-------|----------|------|----------|--------------|
| sbo.president@school.edu | sbo123456 | SBO President | President | Event Management |
| sbo.secretary@school.edu | sbo123456 | SBO Secretary | Secretary | Event Management |
| sbo.events@school.edu | sbo123456 | Events Coordinator | Events Coordinator | Event Management |

## 🎓 Student Access
Students use their **Student ID** to register and login:
- **Format**: XX-XXXXX (e.g., 23-11797, 24-10413)
- **Registration**: Students must register first using their Student ID
- **Login**: After registration, students can login with Student ID and password

## 🔑 Default Passwords
- **Admin Default**: admin123456, superadmin123, adl0rsecure2025
- **SBO Default**: sbo123456 (all SBO accounts)
- **Students**: Set during registration process

## 📋 Access Levels

### 🔧 Admin Access
- ✅ Full system administration
- ✅ Database management
- ✅ Student management
- ✅ SBO user management (add/delete SBO accounts)
- ✅ Academic data management
- ✅ System settings
- ✅ User management

### 👥 SBO Access
- ✅ Event creation and management
- ✅ Attendance tracking
- ✅ Student attendance reports
- ✅ Event attendance downloads
- ✅ Profile management
- ❌ System administration
- ❌ Database access

### 🎓 Student Access
- ✅ Personal dashboard
- ✅ QR code generation
- ✅ Attendance history
- ✅ Event information
- ✅ Profile management
- ❌ Administrative functions
- ❌ Other students' data

## 🛡️ Security Notes
- Change default passwords in production
- Use strong passwords for admin accounts
- Regular password updates recommended
- Monitor login activities
- Implement session timeouts

## 📱 QR Scanner Access
- **No login required** for QR scanning
- **Direct access** to scan_qr.php
- **Temporary sessions** for scanning only

## 🔄 Password Reset
Contact system administrator for password resets:
- **Admin**: Contact super administrator
- **SBO**: Contact admin@adlor.edu or system@adlor.edu
- **Students**: Contact SBO or admin

## 📊 Usage Statistics
- **Total Admin Users**: 3 (System Admin, Super Admin, System Manager)
- **Total SBO Users**: 3 (President, Secretary, Events Coordinator)
- **Student Registration**: Dynamic (based on CSV imports)
- **Active Sessions**: Monitored in real-time

## 🌐 Access URLs
- **Admin Login**: `admin/login.php`
- **SBO Login**: `sbo/login.php`
- **Student Login**: `student_login.php`
- **QR Scanner**: `scan_qr.php` (no login required)

---
*Last Updated: 2025-01-06*
*ADLOR - Attendance Data Logging and Organizing Records*
