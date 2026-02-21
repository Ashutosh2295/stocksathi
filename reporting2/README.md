# Reporting2 - Inventory Reporting System

A modern, clean inventory reporting and management system built with HTML, CSS, and JavaScript.

## 🎨 Design System

This project uses the **exact same color scheme** as the main Stocksathi application:

### Color Palette

- **Primary (Teal)**: `#0d9488`
- **Primary Dark**: `#0f766e`
- **Primary Light**: `#5eead4`
- **Primary Lighter**: `#ccfbf1`
- **Secondary (Blue)**: `#3b82f6`
- **Success**: `#10b981`
- **Warning**: `#f59e0b`
- **Danger**: `#ef4444`
- **Info**: `#06b6d4`

### Typography

- **Font Family**: Inter (Google Fonts)
- **Font Sizes**: xs (12px), sm (14px), base (16px), lg (18px), xl (20px), 2xl (24px), 3xl (30px)

## 📁 Project Structure

```
reporting2/
├── css/
│   ├── design-system.css    # Design tokens and utilities
│   ├── components.css        # Buttons, forms, cards, badges
│   └── layout.css           # Sidebar, header, layout
├── login.php                # Login page
├── register.php             # Registration page
├── dashboard.php            # Main dashboard
├── products.php             # Product management
├── stock.php               # Stock management
├── index.php               # Entry point (redirects to login)
└── README.md               # This file
```

## ✨ Features

### Working Features

1. **Authentication System**
   - ✅ Login page with email/password
   - ✅ Registration page with validation
   - ✅ Password strength indicator
   - ✅ Session management
   - ✅ Remember me functionality

2. **Dashboard**
   - ✅ Statistics cards (Products, Stock, Alerts, Value)
   - ✅ Sales overview chart (Line chart)
   - ✅ Stock distribution chart (Doughnut chart)
   - ✅ Recent activity table

3. **Product Management**
   - ✅ View all products
   - ✅ Add new products
   - ✅ Edit existing products
   - ✅ Delete products
   - ✅ Search and filter products
   - ✅ Category filtering

4. **Stock Management**
   - ✅ View stock levels
   - ✅ Add stock
   - ✅ Remove stock
   - ✅ Stock status indicators
   - ✅ Low stock alerts
   - ✅ Search and filter stock items

### UI Components

- Modern sidebar navigation
- Clean header with user menu
- Responsive stat cards
- Interactive charts (Chart.js)
- Data tables with hover effects
- Modal dialogs
- Form controls with validation
- Badges and status indicators
- Action buttons with icons

## 🚀 Getting Started

### Prerequisites

- XAMPP or any web server with PHP support
- Modern web browser

### Installation

1. Copy the `reporting2` folder to your `htdocs` directory:
   ```
   c:\xampp_new\htdocs\stocksathi\reporting2\
   ```

2. Start your XAMPP Apache server

3. Access the application:
   ```
   http://localhost/stocksathi/reporting2/
   ```

4. You'll be redirected to the login page

### First Time Use

1. Click "Create Account" on the login page
2. Fill in the registration form:
   - First Name
   - Last Name
   - Email
   - Company Name
   - Password (with strength indicator)
   - Confirm Password
3. Accept terms and conditions
4. Click "Create Account"
5. You'll be redirected to login
6. Login with your credentials
7. Start managing products and stock!

## 📊 Data Storage

Currently, the application uses **sessionStorage** and **localStorage** for data persistence:

- User authentication state: `sessionStorage`
- User profile data: `sessionStorage`
- Remember me: `localStorage`
- Products data: In-memory JavaScript arrays
- Stock data: In-memory JavaScript arrays

### Future Enhancement

To make data persistent across sessions, you can:
1. Integrate with a backend API
2. Use a database (MySQL, PostgreSQL)
3. Implement localStorage for offline functionality

## 🎯 Working Modules

| Module | Status | Features |
|--------|--------|----------|
| Login | ✅ Working | Email/password, remember me |
| Registration | ✅ Working | Full validation, password strength |
| Dashboard | ✅ Working | Stats, charts, activity table |
| Products | ✅ Working | CRUD operations, search, filter |
| Stock Management | ✅ Working | Add/remove stock, status tracking |

## 🎨 Design Features

- **Consistent Color Scheme**: Uses exact Stocksathi colors
- **Modern UI**: Clean, professional interface
- **Responsive Design**: Works on desktop and mobile
- **Smooth Animations**: Transitions and hover effects
- **Icon Integration**: Font Awesome icons throughout
- **Chart Visualization**: Chart.js for data visualization

## 🔐 Security Notes

**Important**: This is a frontend-only demo. For production use:

1. Implement proper backend authentication
2. Use secure password hashing (bcrypt, argon2)
3. Add CSRF protection
4. Implement proper session management
5. Add input sanitization
6. Use HTTPS
7. Add rate limiting
8. Implement proper authorization

## 📝 Customization

### Changing Colors

Edit `css/design-system.css` and modify the CSS variables in the `:root` section:

```css
:root {
  --color-primary: #0d9488;  /* Change this */
  --color-secondary: #3b82f6; /* Change this */
  /* ... etc */
}
```

### Adding New Pages

1. Create a new PHP file
2. Copy the structure from `dashboard.php`
3. Update the sidebar navigation
4. Add your custom content

## 🐛 Known Limitations

- No backend integration (frontend only)
- Data is not persistent (resets on page refresh)
- No real authentication (session-based only)
- No database connection
- No file upload functionality

## 📞 Support

For issues or questions, refer to the main Stocksathi documentation.

## 📄 License

This is a demo application. Use at your own discretion.

---

**Built with ❤️ using the Stocksathi Design System**
