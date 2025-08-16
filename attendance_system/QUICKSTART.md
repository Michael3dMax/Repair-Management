# Quick Start Guide - Attendance Management System

Get up and running with the Attendance Management System in just 5 minutes!

## ⚡ Quick Setup (5 minutes)

### 1. Prerequisites Check
- ✅ Web server (Apache/Nginx) running
- ✅ PHP 7.4+ installed
- ✅ MySQL/MariaDB running
- ✅ Modern browser

### 2. Database Setup (2 minutes)
```sql
-- Create database
CREATE DATABASE attendance_system;

-- Create user (optional)
CREATE USER 'attendance_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON attendance_system.* TO 'attendance_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3. File Setup (1 minute)
1. Download/extract files to web directory
2. Edit `config/database.php`:
```php
private $host = 'localhost';
private $db_name = 'attendance_system';
private $username = 'attendance_user';  // or 'root'
private $password = 'secure_password';  // or your password
```

### 4. Auto Installation (1 minute)
Visit: `http://your-domain/attendance_system/config/setup.php`

This automatically:
- Creates all database tables
- Inserts default admin user
- Sets up initial configuration

### 5. First Login (30 seconds)
1. Go to: `http://your-domain/attendance_system/`
2. Login with:
   - **Username:** `admin`
   - **Password:** `admin123`

🎉 **You're ready to go!**

## 🚀 First Steps

### Add Your First Person
1. Click "People Management"
2. Click "Add New Person" 
3. Fill in:
   - Employee ID: `EMP001`
   - First Name: `John`
   - Last Name: `Doe`
   - Department: `IT`
4. Click "Add Person"

### Mark First Attendance
1. Click "Attendance" → "Quick Mark"
2. Find your person card
3. Click "Present"
4. ✅ Attendance marked!

### View Your First Report
1. Click "Reports"
2. Select "Department Summary"
3. Click "Generate Report"
4. 📊 See your attendance data!

## 🎯 Daily Workflow

### Morning Routine (2 minutes)
1. Login to system
2. Go to "Quick Mark"
3. Mark attendance for all present people
4. Done!

### End of Day (30 seconds)
1. Check dashboard for statistics
2. Review any late arrivals
3. Export reports if needed

## 📱 Mobile Quick Start

The system works perfectly on mobile:
1. Bookmark the login page
2. Use "Quick Mark" for easy attendance
3. Swipe through person cards
4. Tap to mark attendance

## 🔧 Essential Settings

### Change Admin Password
1. Click your username → Profile
2. Change password from default
3. Save changes

### Set Working Hours
1. Go to Settings
2. Set start time (e.g., 9:00 AM)
3. Set end time (e.g., 5:00 PM)
4. Set late threshold (e.g., 15 minutes)

### Add Departments
When adding people, simply type new department names - they'll be created automatically!

## 💡 Pro Tips

### Bulk Add People
- Use the CSV import feature (coming soon)
- Or add multiple people quickly using the form

### Quick Attendance Marking
- Use keyboard shortcuts (space to mark present)
- Filter by department for faster marking
- Use bulk mark for historical data

### Reports & Analytics
- Export reports for HR records
- Use date ranges for monthly reports
- Share individual reports with employees

## 🆘 Quick Troubleshooting

**Can't login?**
- Check database connection
- Verify credentials: admin/admin123
- Clear browser cache

**Attendance not saving?**
- Check browser console for errors
- Verify database connection
- Ensure JavaScript is enabled

**Mobile layout issues?**
- Clear mobile browser cache
- Ensure modern browser
- Check internet connection

## 📞 Getting Help

**Need help?** Check:
1. Full README.md for detailed docs
2. Browser console for error messages
3. Database logs for connection issues

**Quick Commands:**
```bash
# Check PHP version
php -v

# Check MySQL connection
mysql -u username -p

# Check web server status
sudo systemctl status apache2  # or nginx
```

## 🎯 Next Steps

Once you're comfortable with basics:
1. Add more people and departments
2. Explore different report types
3. Customize the appearance
4. Set up automated backups
5. Configure email notifications (future feature)

---

**🚀 Ready to manage attendance efficiently!**

*Need more help? Check the full README.md*