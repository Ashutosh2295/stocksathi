# Stocksathi - Login & Registration System

A modern, premium login and registration system for a stock trading web application built with PHP and MySQL.

## Features

- ✅ Modern, premium UI design (inspired by Zerodha/Groww)
- ✅ Clean split-screen layout with branding section
- ✅ Responsive design (mobile-friendly)
- ✅ Secure password hashing
- ✅ Session management
- ✅ Remember me functionality
- ✅ Password visibility toggle
- ✅ Form validation
- ✅ Error/success messaging
- ✅ Professional teal/emerald color scheme

## Installation

### 1. Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server

### 2. Database Setup

1. Create a MySQL database:
   ```sql
   CREATE DATABASE stocksathi;
   ```

2. Import the database schema:
   ```bash
   mysql -u root -p stocksathi < database_setup.sql
   ```
   
   Or manually run the SQL commands from `database_setup.sql`

### 3. Configuration

Edit `config.php` to match your database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'stocksathi');
```

### 4. File Structure

```
login/
├── index.php              # Login page
├── register.php           # Registration page
├── dashboard.php          # Dashboard (after login)
├── auth.php               # Authentication logic
├── config.php             # Database configuration
├── style.css              # Main stylesheet
├── database_setup.sql     # Database schema
└── README.md              # This file
```

### 5. Web Server Setup

**Using PHP Built-in Server (Development):**
```bash
php -S localhost:8000
```
Then visit: `http://localhost:8000`

**Using Apache:**
- Place files in `htdocs` or your web root
- Ensure mod_rewrite is enabled (if using .htaccess)

**Using XAMPP/WAMP:**
- Place files in `htdocs` folder
- Access via `http://localhost/login/`

## Usage

1. **Registration:**
   - Visit `register.php` or click "Sign up" on login page
   - Fill in name, email, and password (min. 8 characters)
   - Click "Create Account"

2. **Login:**
   - Visit `index.php`
   - Enter email and password
   - Optionally check "Remember me"
   - Click "Login"

3. **Dashboard:**
   - After successful login, you'll be redirected to `dashboard.php`
   - Click "Logout" to end your session

## Design Features

- **Color Scheme:**
  - Primary: Teal (#0F766E)
  - Accent: Emerald Green (#10B981)
  - Background: Dark gradient (#020617 → #0F172A)

- **Typography:**
  - Font: Inter (Google Fonts)
  - Clean, readable sans-serif

- **Layout:**
  - Split-screen on desktop
  - Single column on mobile
  - Centered form cards with rounded corners

- **UX Details:**
  - Smooth animations
  - Focus states with teal borders
  - Hover effects on buttons
  - Auto-hiding alert messages
  - Password visibility toggle

## Security Features

- Password hashing using PHP `password_hash()` (bcrypt)
- Prepared statements (SQL injection prevention)
- Session-based authentication
- Input sanitization
- XSS protection with `htmlspecialchars()`

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers

## Customization

### Changing Colors
Edit CSS variables in `style.css`:
```css
:root {
    --primary-teal: #0F766E;
    --accent-emerald: #10B981;
    /* ... */
}
```

### Adding More Fields
1. Add input fields to `register.php` form
2. Update `auth.php` registration handler
3. Add columns to database table

## License

This project is open source and available for use.

## Support

For issues or questions, please refer to the code comments or PHP/MySQL documentation.

---

**Note:** This is a UI/authentication system. Additional features like password reset, email verification, and two-factor authentication should be added for production use.