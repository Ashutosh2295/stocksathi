# Chart Color Update Guide

## Quick Reference: Teal Color Palette

```javascript
// Teal Colors for Charts
const TEAL_COLORS = {
    primary: 'rgb(13, 148, 136)',      // #0d9488
    light: 'rgb(45, 212, 191)',        // #2dd4bf  
    lighter: 'rgb(94, 234, 212)',      // #5eead4
    lightest: 'rgb(153, 246, 228)',    // #99f6e4
    
    // With transparency
    primaryAlpha10: 'rgba(13, 148, 136, 0.1)',
    primaryAlpha15: 'rgba(13, 148, 136, 0.15)',
    primaryAlpha70: 'rgba(13, 148, 136, 0.7)',
};
```

## Files to Update

### 1. admin.php
**Location**: `pages/dashboards/admin.php`

**Line 730-731** - Sales Trend Chart:
```javascript
// OLD
borderColor: 'rgb(74, 111, 165)',
backgroundColor: 'rgba(74, 111, 165, 0.1)',

// NEW
borderColor: 'rgb(13, 148, 136)',
backgroundColor: 'rgba(13, 148, 136, 0.1)',
```

**Line 735** - Point colors:
```javascript
// OLD
pointBackgroundColor: 'rgb(74, 111, 165)',

// NEW
pointBackgroundColor: 'rgb(13, 148, 136)',
```

**Line 799-807** - Category Chart (Doughnut):
```javascript
// OLD
backgroundColor: [
    'rgb(107, 155, 199)',  // Primary soft blue
    'rgb(91, 141, 184)',   // Medium blue
    'rgb(123, 163, 199)',  // Light blue
    'rgb(107, 155, 199)',  // Soft Blue
    'rgb(249, 115, 22)',   // Orange
    'rgb(139, 92, 246)',   // Purple
    'rgb(236, 72, 153)'    // Pink
],

// NEW (Teal gradient palette)
backgroundColor: [
    'rgb(13, 148, 136)',   // Teal 600
    'rgb(20, 184, 166)',   // Teal 500
    'rgb(45, 212, 191)',   // Teal 400
    'rgb(94, 234, 212)',   // Teal 300
    'rgb(153, 246, 228)',  // Teal 200
    'rgb(204, 251, 241)',  // Teal 100
    'rgb(240, 253, 250)'   // Teal 50
],
```

---

### 2. accountant.php
**Location**: `pages/dashboards/accountant.php`

**Line 571** - Income bar:
```javascript
// OLD
backgroundColor: 'rgba(16, 185, 129, 0.7)',

// NEW
backgroundColor: 'rgba(13, 148, 136, 0.7)',
```

**Line 579** - Expense bar (keep red):
```javascript
// Keep as is - red for expenses is good
backgroundColor: 'rgba(239, 68, 68, 0.7)',
```

---

### 3. general.php
**Location**: `pages/dashboards/general.php`

**Line 273** - Sales chart background:
```javascript
// OLD
backgroundColor: 'rgba(66, 165, 245, 0.1)',

// NEW
backgroundColor: 'rgba(13, 148, 136, 0.1)',
```

**Line 277** - Point color:
```javascript
// OLD
pointBackgroundColor: '#1565C0',

// NEW
pointBackgroundColor: '#0d9488',
```

**Line 337-345** - Category colors (similar to admin.php):
```javascript
// NEW (Teal gradient)
backgroundColor: [
    'rgb(13, 148, 136)',
    'rgb(20, 184, 166)',
    'rgb(45, 212, 191)',
    'rgb(94, 234, 212)',
    'rgb(153, 246, 228)',
    'rgb(204, 251, 241)'
],
```

---

### 4. sales-executive.php
**Location**: `pages/dashboards/sales-executive.php`

**Line 796** - Chart background:
```javascript
// OLD
backgroundColor: 'rgba(74, 111, 165, 0.15)',

// NEW
backgroundColor: 'rgba(13, 148, 136, 0.15)',
```

**Line 801** - Point color:
```javascript
// OLD
pointBackgroundColor: 'rgb(74, 111, 165)',

// NEW
pointBackgroundColor: 'rgb(13, 148, 136)',
```

---

### 5. store-manager.php
**Location**: `pages/dashboards/store-manager.php`

**Line 638** - Chart color:
```javascript
// OLD
backgroundColor: 'rgba(74, 111, 165, 0.7)',

// NEW
backgroundColor: 'rgba(13, 148, 136, 0.7)',
```

---

## Additional Gradient Colors to Update

### Dashboard Header Gradients

In **admin.php** (and similar files), update the header gradient:

**Line 241** (admin.php):
```css
/* OLD */
background: linear-gradient(135deg, #2E4A73 0%, #4A6FA5 50%, #5B7FA8 100%);

/* NEW */
background: linear-gradient(135deg, #115e59 0%, #0d9488 50%, #14b8a6 100%);
```

**Line 244** (shadow):
```css
/* OLD */
box-shadow: 0 8px 32px rgba(46, 74, 115, 0.3);

/* NEW */
box-shadow: 0 8px 32px rgba(13, 148, 136, 0.3);
```

**Line 252** (card shadow):
```css
/* OLD */
box-shadow: 0 2px 8px rgba(46, 74, 115, 0.04), 0 1px 3px rgba(0, 0, 0, 0.06);

/* NEW */
box-shadow: 0 2px 8px rgba(13, 148, 136, 0.04), 0 1px 3px rgba(0, 0, 0, 0.06);
```

---

## Search & Replace Guide

Use your code editor's find & replace feature:

### RGB Values:
1. Find: `rgb(74, 111, 165)` → Replace: `rgb(13, 148, 136)`
2. Find: `rgba(74, 111, 165,` → Replace: `rgba(13, 148, 136,`
3. Find: `rgb(46, 74, 115)` → Replace: `rgb(13, 148, 136)`
4. Find: `rgba(46, 74, 115,` → Replace: `rgba(13, 148, 136,`

### Hex Values:
1. Find: `#1565C0` → Replace: `#0d9488`
2. Find: `#4A6FA5` → Replace: `#14b8a6`
3. Find: `#2E4A73` → Replace: `#115e59`
4. Find: `#42A5F5` → Replace: `#2dd4bf`

---

## Testing Checklist

After updating, test each dashboard:

- [ ] **Super Admin Dashboard** - Charts show teal colors
- [ ] **Admin Dashboard** - Charts show teal colors
- [ ] **Sales Executive Dashboard** - Charts show teal colors
- [ ] **Accountant Dashboard** - Income chart is teal, expense is red
- [ ] **Store Manager Dashboard** - Charts show teal colors
- [ ] **General Dashboard** - Charts show teal colors

---

## Quick Fix Script

If you want to do this automatically, run this PowerShell script in the `pages/dashboards` directory:

```powershell
# Update all dashboard files
$files = Get-ChildItem -Path "." -Filter "*.php"

foreach ($file in $files) {
    $content = Get-Content $file.FullName -Raw
    
    # Replace RGB values
    $content = $content -replace 'rgb\(74, 111, 165\)', 'rgb(13, 148, 136)'
    $content = $content -replace 'rgba\(74, 111, 165,', 'rgba(13, 148, 136,'
    $content = $content -replace 'rgb\(46, 74, 115\)', 'rgb(13, 148, 136)'
    $content = $content -replace 'rgba\(46, 74, 115,', 'rgba(13, 148, 136,'
    
    # Replace Hex values
    $content = $content -replace '#1565C0', '#0d9488'
    $content = $content -replace '#4A6FA5', '#14b8a6'
    $content = $content -replace '#2E4A73', '#115e59'
    $content = $content -replace '#42A5F5', '#2dd4bf'
    
    # Save the file
    Set-Content -Path $file.FullName -Value $content
    Write-Host "Updated: $($file.Name)" -ForegroundColor Green
}

Write-Host "`nAll dashboard files updated with teal colors!" -ForegroundColor Cyan
```

---

## Done!

After these changes, all your charts and dashboard elements will use the beautiful teal color scheme! 🎨✨
