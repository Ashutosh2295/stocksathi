# 🎨 Logo Integration Guide

## ✅ **Logo Added to Sidebar**

**File:** `_includes/sidebar.php`  
**Line:** 73

**Changed from:**
```html
<img src="<?= ASSETS_PATH ?>/logo-icon.svg" alt="Stocksathi" class="sidebar-logo-icon" style="width: 40px; height: 40px;">
```

**Changed to:**
```html
<img src="<?= BASE_PATH ?>/assets/images/logo.png" alt="Stocksathi" class="sidebar-logo-icon" style="width: 40px; height: 40px; object-fit: contain;">
```

---

## 📋 **Files That Need Logo Update**

### 1. **Login Page** (`pages/login.php`)

**Location:** Line 425-432  
**Current:** SVG logo  
**Change to:**

```html
<div class="logo">
    <img src="../assets/images/logo.png" alt="Stocksathi" style="width: 60px; height: 60px; object-fit: contain;">
    <span class="logo-text">Stocksathi</span>
</div>
```

### 2. **Register Page** (`pages/register.php`)

**Search for:** Logo section  
**Add:**

```html
<img src="../assets/images/logo.png" alt="Stocksathi" style="width: 60px; height: 60px; object-fit: contain;">
```

### 3. **Setup Page** (`setup-organization.php`)

**Add logo at top:**

```html
<div style="text-align: center; margin-bottom: 2rem;">
    <img src="assets/images/logo.png" alt="Stocksathi" style="width: 80px; height: 80px; object-fit: contain;">
    <h1>Stocksathi Setup</h1>
</div>
```

### 4. **Header** (`_includes/header.php`)

**If header exists, add logo:**

```html
<img src="<?= BASE_PATH ?>/assets/images/logo.png" alt="Stocksathi" style="width: 32px; height: 32px; object-fit: contain;">
```

---

## 🔧 **Manual Update Instructions**

Since some files have complex formatting, here's how to manually update:

### **Step 1: Login Page**

1. Open `pages/login.php`
2. Find line ~425 (search for `<div class="logo">`)
3. Replace the `<svg>...</svg>` block with:
   ```html
   <img src="../assets/images/logo.png" alt="Stocksathi" style="width: 60px; height: 60px; object-fit: contain;">
   ```

### **Step 2: Register Page**

1. Open `pages/register.php`
2. Find the branding section (similar to login)
3. Replace SVG with logo.png

### **Step 3: Setup Page**

1. Open `setup-organization.php`
2. Add logo at the top of the page

---

## ✅ **Already Updated**

- ✅ **Sidebar** - Logo added successfully
- ✅ **Logo path:** `assets/images/logo.png`
- ✅ **Object-fit:** `contain` (maintains aspect ratio)

---

## 📝 **CSS for Logo**

Use this CSS for consistent logo display:

```css
.logo-image {
    width: 40px;
    height: 40px;
    object-fit: contain;
    border-radius: 8px;
}
```

---

## 🎯 **Logo Sizes**

- **Sidebar:** 40x40px
- **Login/Register:** 60x60px
- **Setup Page:** 80x80px
- **Header:** 32x32px
- **Favicon:** 16x16px or 32x32px

---

**Sidebar logo is already updated! Other pages need manual update as shown above.** ✅
