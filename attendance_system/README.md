# Attendance Management System

A complete, modern, and feature-rich attendance management system built with PHP, CSS, and JavaScript. Perfect for schools, colleges, and organizations to efficiently track and manage attendance.

## 🌟 Features

### Core Features
- **User Authentication** - Secure login/logout system with session management
- **People Management** - Add, edit, and manage students/employees with detailed profiles
- **Attendance Marking** - Multiple ways to mark attendance (bulk and quick mark)
- **Comprehensive Reports** - Generate detailed attendance reports and analytics
- **Department Management** - Organize people by departments
- **Real-time Updates** - Live attendance updates and notifications

### Advanced Features
- **Responsive Design** - Works perfectly on desktop, tablet, and mobile devices
- **Interactive Charts** - Visual analytics using Chart.js
- **Data Export** - Export reports to CSV format
- **Search & Filter** - Advanced filtering and search capabilities
- **Status Tracking** - Multiple attendance statuses (Present, Absent, Late, Half Day)
- **Time Tracking** - Record check-in and check-out times
- **Notes System** - Add notes to attendance records

### UI/UX Features
- **Modern Design** - Clean, professional, and aesthetic interface
- **Dark/Light Theme Support** - CSS variables for easy theme customization
- **Smooth Animations** - Engaging micro-interactions and transitions
- **Form Validation** - Real-time client-side and server-side validation
- **Loading States** - Beautiful loading spinners and progress indicators
- **Mobile-First** - Optimized for mobile devices

## 📋 Requirements

### System Requirements
- **Web Server** - Apache/Nginx
- **PHP** - Version 7.4 or higher
- **Database** - MySQL 5.7+ or MariaDB 10.2+
- **Browser** - Modern browser with JavaScript enabled

### PHP Extensions
- PDO
- PDO_MySQL
- Session
- JSON

## 🚀 Installation Guide

### Step 1: Download and Setup
1. Download or clone the project files
2. Extract to your web server directory (e.g., `/var/www/html/` or `htdocs/`)
3. Ensure the web server has read/write permissions

### Step 2: Database Configuration
1. Open `config/database.php`
2. Update database credentials:
```php
private $host = 'localhost';        // Database host
private $db_name = 'attendance_system'; // Database name
private $username = 'your_username';     // Database username
private $password = 'your_password';     // Database password
```

### Step 3: Database Setup
1. Create a MySQL database named `attendance_system`
2. Run the setup script by visiting: `http://your-domain/attendance_system/config/setup.php`
3. This will create all necessary tables and insert default data

### Step 4: Access the System
1. Visit: `http://your-domain/attendance_system/`
2. Login with default credentials:
   - **Username:** admin
   - **Password:** admin123

### Step 5: Configuration (Optional)
- Change default admin password after first login
- Update company/organization name in settings
- Configure working hours and late thresholds

## 🗄️ Database Schema

### Tables Overview
- **users** - System users (admin, teachers, HR)
- **people** - Students/employees information
- **attendance** - Daily attendance records
- **departments** - Organization departments
- **leave_requests** - Leave applications (future feature)
- **settings** - System configuration

### Key Relationships
- People → Attendance (One-to-Many)
- Users → Attendance (created_by)
- Departments → People (department wise grouping)

## 📱 Usage Guide

### 1. Managing People
**Add New Person:**
1. Go to "People Management"
2. Click "Add New Person"
3. Fill in required details (Employee ID, Name)
4. Optionally add email, phone, department, position
5. Click "Add Person"

**Edit Person:**
1. Go to "People Management"
2. Find the person using search/filters
3. Click edit icon
4. Update information and save

### 2. Marking Attendance

**Quick Mark (Recommended for daily use):**
1. Go to "Attendance" → "Quick Mark"
2. See all people in card view
3. Click Present/Late/Absent for each person
4. Attendance is marked instantly

**Bulk Mark (Good for historical data):**
1. Go to "Attendance" → "Bulk Mark"
2. Select date and department (optional)
3. Check boxes for people to mark attendance
4. Set status, times, and notes
5. Click "Save Attendance"

### 3. Viewing Reports

**Department Summary:**
- Overview of attendance by department
- Attendance percentages and counts
- Visual charts for better understanding

**Daily Attendance:**
- Day-wise attendance tracking
- Trends and patterns over time
- Line charts showing attendance trends

**Individual Reports:**
- Detailed attendance history for specific person
- Status breakdown with charts
- Check-in/out times and notes

**Late Arrivals:**
- List of all late arrivals
- Filterable by date and department
- Useful for HR management

### 4. Export and Analytics
- All reports can be exported to CSV
- Interactive charts for visual analysis
- Real-time dashboard updates
- Mobile-friendly report viewing

## 🎨 Customization

### Themes and Colors
The system uses CSS variables for easy customization. Edit `assets/css/style.css`:

```css
:root {
    --primary-color: #4f46e5;      /* Change primary color */
    --success-color: #10b981;      /* Success messages */
    --warning-color: #f59e0b;      /* Warning messages */
    --error-color: #ef4444;        /* Error messages */
    /* ... more variables */
}
```

### Adding New Features
The system is built with modularity in mind:
- **PHP Classes** - Database operations in `config/database.php`
- **CSS Components** - Reusable components in `assets/css/style.css`
- **JavaScript Modules** - Interactive features in `assets/js/main.js`

### Language Localization
To add multi-language support:
1. Create language files in `includes/lang/`
2. Add translation functions
3. Update templates with translation calls

## 🔧 Configuration Options

### System Settings
Access via Admin → Settings:
- **Working Hours** - Set start/end times
- **Late Threshold** - Minutes after which marked as late
- **Company Name** - Organization branding
- **Date Formats** - Customize date display

### Security Settings
- **Session Timeout** - Configure in `includes/session.php`
- **Password Policy** - Update validation in `assets/js/main.js`
- **User Roles** - Extend role system in database

## 🛡️ Security Features

- **SQL Injection Protection** - Prepared statements used throughout
- **XSS Prevention** - HTML escaping for all outputs
- **CSRF Protection** - Form token validation
- **Session Security** - Secure session handling
- **Input Validation** - Client and server-side validation
- **Password Hashing** - Secure password storage with PHP's password_hash()

## 📱 Mobile Responsiveness

The system is fully responsive and works on:
- **Desktop** - Full feature access with optimal layout
- **Tablet** - Adapted layouts for touch interaction
- **Mobile** - Simplified navigation with swipe gestures
- **Touch Devices** - Optimized button sizes and spacing

## 🐛 Troubleshooting

### Common Issues

**Database Connection Error:**
- Check database credentials in `config/database.php`
- Ensure MySQL service is running
- Verify database exists and user has permissions

**Page Not Found (404):**
- Check web server configuration
- Ensure mod_rewrite is enabled (Apache)
- Verify file permissions

**Attendance Not Saving:**
- Check browser console for JavaScript errors
- Verify database connection
- Ensure user has proper permissions

**Login Issues:**
- Clear browser cache and cookies
- Check if sessions are working
- Verify user credentials in database

### Debug Mode
Enable debug mode by adding to top of any PHP file:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 🔄 Backup and Maintenance

### Database Backup
Regular backup is recommended:
```bash
mysqldump -u username -p attendance_system > backup_$(date +%Y%m%d).sql
```

### File Backup
Backup the entire application directory:
```bash
tar -czf attendance_system_backup_$(date +%Y%m%d).tar.gz attendance_system/
```

### Updates and Maintenance
- Regular database cleanup for old records
- Monitor disk space usage
- Keep PHP and MySQL updated
- Review access logs periodically

## 🚀 Performance Optimization

### Database Optimization
- Add indexes on frequently queried columns
- Archive old attendance records
- Optimize queries with EXPLAIN

### Web Server Optimization
- Enable GZIP compression
- Set up browser caching headers
- Optimize CSS/JS files
- Use CDN for static assets

### PHP Optimization
- Enable OPcache
- Optimize PHP settings
- Use connection pooling
- Implement caching where appropriate

## 🤝 Contributing

We welcome contributions! To contribute:
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

### Development Guidelines
- Follow PSR coding standards
- Write clear commit messages
- Add comments for complex logic
- Test on multiple browsers
- Ensure mobile compatibility

## 📄 License

This project is licensed under the MIT License. See the LICENSE file for details.

## 🆘 Support

For support and questions:
- **Documentation** - Check this README first
- **Issues** - Report bugs and feature requests
- **Community** - Join our community forums
- **Email** - Contact support team

## 🔮 Future Enhancements

Planned features for future releases:
- **Leave Management** - Complete leave request system
- **Email Notifications** - Automated attendance notifications
- **Biometric Integration** - Fingerprint/face recognition
- **Mobile App** - Native mobile applications
- **API Development** - REST API for third-party integrations
- **Advanced Analytics** - Machine learning insights
- **Multi-tenancy** - Support for multiple organizations
- **Backup Automation** - Automated backup scheduling

## 📊 System Architecture

### Frontend Architecture
- **HTML5** - Semantic markup for accessibility
- **CSS3** - Modern styling with Flexbox/Grid
- **JavaScript** - Vanilla JS for better performance
- **Progressive Enhancement** - Works without JavaScript

### Backend Architecture
- **PHP** - Server-side logic and database operations
- **MySQL** - Relational database for data storage
- **Session Management** - Secure user authentication
- **MVC Pattern** - Organized code structure

### Security Architecture
- **Input Sanitization** - All inputs are sanitized
- **Output Encoding** - All outputs are properly encoded
- **Authentication** - Secure login system
- **Authorization** - Role-based access control

---

**Built with ❤️ for efficient attendance management**

*Last updated: December 2024*