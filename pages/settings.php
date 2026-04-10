<?php
/**
 * Settings Page
 * Organization and application configuration
 */

require_once __DIR__ . '/../_includes/session_guard.php';
require_once __DIR__ . '/../_includes/config.php';

// Require admin or super_admin role
$allowedRoles = ['super_admin', 'admin'];
if (!in_array(Session::getUserRole(), $allowedRoles)) {
    header('Location: ' . BASE_PATH . '/403.php');
    exit;
}

$db = Database::getInstance();
$orgIdPatch = isset($_SESSION['organization_id']) ? $_SESSION['organization_id'] : (class_exists('Session') ? Session::getOrganizationId() : null);
$orgFilter = $orgIdPatch ? " organization_id = " . intval($orgIdPatch) . " AND " : "";
$orgWhere = $orgIdPatch ? " WHERE organization_id = " . intval($orgIdPatch) . " " : "";
$organizationId = Session::getOrganizationId();
$userId = Session::getUserId();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'save_general' || $action === 'save_company' || $action === 'save_financial' || $action === 'save_notifications') {
            
            // Define expected fields for each section
            $fields = [];
            $group = '';
            
            if ($action === 'save_general') {
                $group = 'general';
                $fields = ['app_name', 'time_zone', 'date_format', 'currency', 'language'];
            } elseif ($action === 'save_company') {
                $group = 'company';
                $fields = ['company_name', 'gstin', 'pan', 'address', 'city', 'state', 'pincode', 'email', 'phone'];
            } elseif ($action === 'save_financial') {
                $group = 'financial';
                $fields = ['financial_year_start'];
            } elseif ($action === 'save_notifications') {
                $group = 'notifications';
                $fields = ['email_notifications', 'low_stock_alerts', 'expiry_alerts', 'sales_notifications', 'report_notifications', 'notification_email'];
                
                // Handle checkboxes (unchecked = not sent in POST)
                $_POST['email_notifications'] = isset($_POST['email_notifications']) ? '1' : '0';
                $_POST['low_stock_alerts'] = isset($_POST['low_stock_alerts']) ? '1' : '0';
                $_POST['expiry_alerts'] = isset($_POST['expiry_alerts']) ? '1' : '0';
                $_POST['sales_notifications'] = isset($_POST['sales_notifications']) ? '1' : '0';
                $_POST['report_notifications'] = isset($_POST['report_notifications']) ? '1' : '0';
            }
            
            $db->beginTransaction();
            
            foreach ($fields as $key) {
                if (isset($_POST[$key])) {
                    $value = $_POST[$key];
                    // Upsert setting
                    $query = "INSERT INTO organization_settings (organization_id, setting_key, setting_value, setting_group) 
                             VALUES (?, ?, ?, ?) 
                             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
                    $db->execute($query, [$organizationId, $key, $value, $group]);
                }
            }

            // Sync with organizations table
            if ($action === 'save_company') {
                $db->execute("UPDATE organizations SET 
                    name = ?, email = ?, phone = ?, address = ?, city = ?, state = ?, pincode = ?, gst_number = ?, pan_number = ? 
                    WHERE id = ?", 
                    [$_POST['company_name'], $_POST['email'], $_POST['phone'], $_POST['address'], $_POST['city'], $_POST['state'], $_POST['pincode'], $_POST['gstin'], $_POST['pan'], $organizationId]
                );
            }
            
            $db->commit();
            Session::setFlash(ucfirst($group) . ' settings saved successfully', 'success');
            
            // Stay on the same tab
            header("Location: " . $_SERVER['PHP_SELF'] . "#" . $group);
            exit;
        } else if ($action === 'save_theme') {
            $group = 'theme';
            $colorScheme = $_POST['theme_color_scheme'] ?? 'Blue';
            $mode = $_POST['theme_mode'] ?? 'light';
            
            $db->execute("INSERT INTO organization_settings (organization_id, setting_key, setting_value, setting_group) VALUES (?, 'theme_color_scheme', ?, 'theme') ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)", [$organizationId, $colorScheme]);
            $db->execute("INSERT INTO organization_settings (organization_id, setting_key, setting_value, setting_group) VALUES (?, 'theme_mode_preference', ?, 'theme') ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)", [$organizationId, $mode]);
            
            Session::setFlash('Theme settings saved successfully', 'success');
            header("Location: " . $_SERVER['PHP_SELF'] . "#theme");
            exit;
        }
    } catch (Exception $e) {
        $db->rollBack();
        Session::setFlash('Error saving settings: ' . $e->getMessage(), 'error');
    }
}

// Check flash message
$flash = Session::getFlash();
$message = $flash['message'] ?? '';
$messageType = $flash['type'] ?? '';

// Load Settings
$settings = [];
if ($organizationId) {
    $rows = $db->query("SELECT setting_key, setting_value FROM organization_settings WHERE organization_id = ?", [$organizationId]);
    foreach ($rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Helper to get setting value
function get_setting($key, $default = '', $settings = []) {
    return $settings[$key] ?? $default;
}

// Get organization details to merge with settings and act as fallback
$orgDetails = [];
if ($organizationId) {
    $orgDetails = $db->queryOne("SELECT * FROM organizations WHERE id = ?", [$organizationId]);
    if ($orgDetails) {
        $settings['company_name'] = get_setting('company_name', $orgDetails['name'] ?? '', $settings);
        $settings['email'] = get_setting('email', $orgDetails['email'] ?? '', $settings);
        $settings['phone'] = get_setting('phone', $orgDetails['phone'] ?? '', $settings);
        $settings['address'] = get_setting('address', $orgDetails['address'] ?? '', $settings);
        $settings['city'] = get_setting('city', $orgDetails['city'] ?? '', $settings);
        $settings['state'] = get_setting('state', $orgDetails['state'] ?? '', $settings);
        $settings['pincode'] = get_setting('pincode', $orgDetails['pincode'] ?? '', $settings);
        $settings['gstin'] = get_setting('gstin', $orgDetails['gst_number'] ?? '', $settings);
        $settings['pan'] = get_setting('pan', $orgDetails['pan_number'] ?? '', $settings);
    }
}

// Compute financial year dynamically
$fyStart = get_setting('financial_year_start', '04-01', $settings);
$currMonthDay = date('m-d');
$currYear = (int)date('Y');
if ($currMonthDay < $fyStart) {
    if (strpos($fyStart, '-01') !== false) {
        $fyStr = ($currYear - 1) . '-' . $currYear;
    } else {
        $fyStr = ($currYear - 1) . '-' . $currYear;
    }
} else {
    $fyStr = $currYear . '-' . ($currYear + 1);
}
if ($fyStart === '01-01') $fyStr = $currYear; // If Jan 1st, it's just the current year usually
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Stocksathi</title>
    <link rel="stylesheet" href="<?= CSS_PATH ?>/design-system.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/components.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/layout.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/nav-dropdown.css">
    <!-- Theme Manager - Load early to apply theme before page renders -->
    <script src="<?= BASE_PATH ?>/js/theme-manager.js"></script>
    <script src="<?= BASE_PATH ?>/js/theme-manager.js"></script>
    <script>
        // Sync DB Theme into localStorage to override strictly client-side settings
        <?php 
        $dbThemeScheme = get_setting('theme_color_scheme', '', $settings);
        $dbThemeMode = get_setting('theme_mode_preference', '', $settings);
        if ($dbThemeScheme): ?>
            localStorage.setItem('themeColorScheme', '<?= htmlspecialchars($dbThemeScheme) ?>');
        <?php endif; ?>
        <?php if ($dbThemeMode): ?>
            localStorage.setItem('themeModePreference', '<?= htmlspecialchars($dbThemeMode) ?>');
        <?php endif; ?>
        
        // Tab Persistence
        document.addEventListener('DOMContentLoaded', function() {
            const hash = window.location.hash.substring(1);
            if (hash) {
                const tab = document.querySelector(`.tab[data-tab="${hash}"]`);
                if (tab) {
                    tab.click();
                }
            }
        });
    </script>
</head>

<body>
    <div class="app-container">
        <?php include __DIR__ . '/../_includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/../_includes/header.php'; ?>
            
            <main class="content">
                <div class="content-header">
                    <nav class="breadcrumb">
                        <a href="<?= BASE_PATH ?>/index.php" class="breadcrumb-item">Home</a>
                        <span class="breadcrumb-separator">/</span>
                        <span class="breadcrumb-item active">Settings</span>
                    </nav>
                    <h1 class="content-title">Settings</h1>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?> mb-6">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <div class="tabs mb-6">
                    <button class="tab active" data-tab="general">General</button>
                    <button class="tab" data-tab="company">Company Info</button>
                    <button class="tab" data-tab="financial">Financial Year</button>
                    <button class="tab" data-tab="notifications">Notifications</button>
                    <button class="tab" data-tab="theme">Theme</button>
                </div>

                <!-- General Settings -->
                <div class="tab-content" id="general">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">General Settings</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="save_general">
                                <div class="form-group">
                                    <label class="form-label">Application Name</label>
                                    <input type="text" name="app_name" class="form-control" value="<?= htmlspecialchars(get_setting('app_name', 'Stocksathi', $settings)) ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Time Zone</label>
                                    <select name="time_zone" class="form-control">
                                        <option value="Asia/Kolkata" <?= get_setting('time_zone', 'Asia/Kolkata', $settings) === 'Asia/Kolkata' ? 'selected' : '' ?>>Asia/Kolkata (IST)</option>
                                        <option value="Asia/Dubai" <?= get_setting('time_zone', '', $settings) === 'Asia/Dubai' ? 'selected' : '' ?>>Asia/Dubai (GST)</option>
                                        <option value="America/New_York" <?= get_setting('time_zone', '', $settings) === 'America/New_York' ? 'selected' : '' ?>>America/New_York (EST)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Date Format</label>
                                    <select name="date_format" class="form-control">
                                        <option value="d/m/Y" <?= get_setting('date_format', 'd/m/Y', $settings) === 'd/m/Y' ? 'selected' : '' ?>>DD/MM/YYYY</option>
                                        <option value="m/d/Y" <?= get_setting('date_format', '', $settings) === 'm/d/Y' ? 'selected' : '' ?>>MM/DD/YYYY</option>
                                        <option value="Y-m-d" <?= get_setting('date_format', '', $settings) === 'Y-m-d' ? 'selected' : '' ?>>YYYY-MM-DD</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Currency</label>
                                    <select name="currency" class="form-control">
                                        <option value="INR" <?= get_setting('currency', 'INR', $settings) === 'INR' ? 'selected' : '' ?>>INR (₹)</option>
                                        <option value="USD" <?= get_setting('currency', '', $settings) === 'USD' ? 'selected' : '' ?>>USD ($)</option>
                                        <option value="EUR" <?= get_setting('currency', '', $settings) === 'EUR' ? 'selected' : '' ?>>EUR (€)</option>
                                        <option value="GBP" <?= get_setting('currency', '', $settings) === 'GBP' ? 'selected' : '' ?>>GBP (£)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Language</label>
                                    <select name="language" class="form-control">
                                        <option value="en" <?= get_setting('language', 'en', $settings) === 'en' ? 'selected' : '' ?>>English</option>
                                        <option value="hi" <?= get_setting('language', '', $settings) === 'hi' ? 'selected' : '' ?>>Hindi</option>
                                        <option value="mr" <?= get_setting('language', '', $settings) === 'mr' ? 'selected' : '' ?>>Marathi</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Company Info -->
                <div class="tab-content hidden" id="company">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Company Information</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="save_company">
                                <div class="form-group">
                                    <label class="form-label required">Company Name</label>
                                    <input type="text" name="company_name" class="form-control" placeholder="Enter company name" value="<?= htmlspecialchars(get_setting('company_name', '', $settings)) ?>" required>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="form-group">
                                        <label class="form-label">GSTIN</label>
                                        <input type="text" name="gstin" class="form-control" placeholder="GST Number" value="<?= htmlspecialchars(get_setting('gstin', '', $settings)) ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">PAN</label>
                                        <input type="text" name="pan" class="form-control" placeholder="PAN Number" value="<?= htmlspecialchars(get_setting('pan', '', $settings)) ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Address</label>
                                    <textarea name="address" class="form-control" rows="3" placeholder="Company address"><?= htmlspecialchars(get_setting('address', '', $settings)) ?></textarea>
                                </div>
                                <div class="grid grid-cols-3 gap-4">
                                    <div class="form-group">
                                        <label class="form-label">City</label>
                                        <input type="text" name="city" class="form-control" placeholder="City" value="<?= htmlspecialchars(get_setting('city', '', $settings)) ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">State</label>
                                        <input type="text" name="state" class="form-control" placeholder="State" value="<?= htmlspecialchars(get_setting('state', '', $settings)) ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">PIN Code</label>
                                        <input type="text" name="pincode" class="form-control" placeholder="PIN" value="<?= htmlspecialchars(get_setting('pincode', '', $settings)) ?>">
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="form-group">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" placeholder="company@example.com" value="<?= htmlspecialchars(get_setting('email', '', $settings)) ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Phone</label>
                                        <input type="tel" name="phone" class="form-control" placeholder="+91 XXXXX XXXXX" value="<?= htmlspecialchars(get_setting('phone', '', $settings)) ?>">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Financial Year -->
                <div class="tab-content hidden" id="financial">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Financial Year Configuration</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="save_financial">
                                <div class="form-group">
                                    <label class="form-label">Financial Year Start</label>
                                    <select name="financial_year_start" class="form-control">
                                        <option value="04-01" <?= get_setting('financial_year_start', '04-01', $settings) === '04-01' ? 'selected' : '' ?>>April 1st (India)</option>
                                        <option value="01-01" <?= get_setting('financial_year_start', '', $settings) === '01-01' ? 'selected' : '' ?>>January 1st</option>
                                        <option value="07-01" <?= get_setting('financial_year_start', '', $settings) === '07-01' ? 'selected' : '' ?>>July 1st</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Current Financial Year</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($fyStr) ?>" readonly>
                                </div>
                                <div class="alert alert-info">
                                    <strong>Note:</strong> Changing financial year settings may affect reporting. Please
                                    consult with your accountant before making changes.
                                </div>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Notifications -->
                <div class="tab-content hidden" id="notifications">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Notification Preferences</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="save_notifications">
                                <div class="form-check">
                                    <input type="checkbox" name="email_notifications" id="emailNotifications" class="form-check-input" <?= get_setting('email_notifications', '1', $settings) === '1' ? 'checked' : '' ?>>
                                    <label for="emailNotifications" class="form-check-label">Enable Email Notifications</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="low_stock_alerts" id="lowStockAlerts" class="form-check-input" <?= get_setting('low_stock_alerts', '1', $settings) === '1' ? 'checked' : '' ?>>
                                    <label for="lowStockAlerts" class="form-check-label">Low Stock Alerts</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="expiry_alerts" id="expiryAlerts" class="form-check-input" <?= get_setting('expiry_alerts', '1', $settings) === '1' ? 'checked' : '' ?>>
                                    <label for="expiryAlerts" class="form-check-label">Product Expiry Alerts</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="sales_notifications" id="salesNotif" class="form-check-input" <?= get_setting('sales_notifications', '1', $settings) === '1' ? 'checked' : '' ?>>
                                    <label for="salesNotif" class="form-check-label">Sales & Invoice Notifications</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="report_notifications" id="reportNotif" class="form-check-input" <?= get_setting('report_notifications', '0', $settings) === '1' ? 'checked' : '' ?>>
                                    <label for="reportNotif" class="form-check-label">Weekly Performance Reports</label>
                                </div>
                                <div class="form-group mt-6">
                                    <label class="form-label">Notification Email</label>
                                    <input type="email" name="notification_email" class="form-control" value="<?= htmlspecialchars(get_setting('notification_email', 'admin@company.com', $settings)) ?>">
                                </div>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Theme -->
                <div class="tab-content hidden" id="theme">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Theme Settings</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="save_theme">
                                <input type="hidden" name="theme_mode" id="themeModeInput" value="<?= htmlspecialchars(get_setting('theme_mode_preference', 'light', $settings)) ?>">
                                
                                <div class="form-group">
                                    <label class="form-label">Color Scheme</label>
                                    <select name="theme_color_scheme" id="colorSchemeSelect" class="form-control" onchange="previewColorScheme(this.value)">
                                                <option value="Teal (Default)" <?= get_setting('theme_color_scheme', 'Blue', $settings) === 'Teal (Default)' ? 'selected' : '' ?>>Teal (Default)</option>
                                                <option value="Blue" <?= get_setting('theme_color_scheme', 'Blue', $settings) === 'Blue' ? 'selected' : '' ?>>Blue</option>
                                                <option value="Green" <?= get_setting('theme_color_scheme', 'Blue', $settings) === 'Green' ? 'selected' : '' ?>>Green</option>
                                                <option value="Purple" <?= get_setting('theme_color_scheme', 'Blue', $settings) === 'Purple' ? 'selected' : '' ?>>Purple</option>
                                    </select>
                                <small style="color: var(--text-secondary); font-size: 12px; margin-top: 4px; display: block;">
                                    Select a color scheme to change the primary accent colors throughout the application
                                </small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Display Mode</label>
                                <div class="grid grid-cols-3 gap-4 mt-3" id="themeModeCards">
                                    <div class="theme-mode-card card" data-mode="light" style="cursor: pointer; background: white; border: 1px solid #E5E9F0; transition: all 0.2s;" onclick="previewMode('light')">
                                        <div class="card-body text-center p-4">
                                            <div style="font-size: 2rem; margin-bottom: var(--space-2);">☀️</div>
                                            <div class="font-medium">Light</div>
                                            <div style="font-size: 11px; color: var(--text-secondary); margin-top: 4px;">Always light</div>
                                        </div>
                                    </div>
                                    <div class="theme-mode-card card" data-mode="dark" style="cursor: pointer; background: #1E293B; border: 1px solid #334155; transition: all 0.2s;" onclick="previewMode('dark')">
                                        <div class="card-body text-center p-4" style="color: #F8FAFC;">
                                            <div style="font-size: 2rem; margin-bottom: var(--space-2);">🌙</div>
                                            <div class="font-medium" style="color: #F8FAFC;">Dark</div>
                                            <div style="font-size: 11px; color: #CBD5E1; margin-top: 4px;">Always dark</div>
                                        </div>
                                    </div>
                                    <div class="theme-mode-card card" data-mode="auto" style="cursor: pointer; background: white; border: 1px solid #E5E9F0; transition: all 0.2s;" onclick="previewMode('auto')">
                                        <div class="card-body text-center p-4">
                                            <div style="font-size: 2rem; margin-bottom: var(--space-2);">🔄</div>
                                            <div class="font-medium">Auto</div>
                                            <div style="font-size: 11px; color: var(--text-secondary); margin-top: 4px;">System preference</div>
                                        </div>
                                    </div>
                                </div>
                                <small style="color: var(--text-secondary); font-size: 12px; margin-top: 8px; display: block;">
                                    Choose how the application should appear. Auto mode follows your system's dark/light preference.
                                </small>
                            </div>
                            <button type="submit" class="btn btn-primary" onclick="saveThemeFrontendState()">Save Changes</button>
                            </form>
                            
                            <div id="themeSaveMessage" style="display: none; margin-top: 12px; padding: 12px; background: #D1FAE5; color: #059669; border-radius: 8px; font-size: 14px;">
                                ✓ Theme settings saved successfully!
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <!-- Scripts -->
    <script src="<?= BASE_PATH ?>/js/api-client.js"></script>
    <script src="<?= BASE_PATH ?>/js/app.js"></script>
    
    <script>
        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const targetTab = this.getAttribute('data-tab');
                    
                    // Remove active class from all tabs
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(tc => {
                        tc.classList.add('hidden');
                        tc.classList.remove('active');
                    });
                    
                    // Add active class to clicked tab
                    this.classList.add('active');
                    const targetContent = document.getElementById(targetTab);
                    if (targetContent) {
                        targetContent.classList.remove('hidden');
                        targetContent.classList.add('active');
                    }
                });
            });
            
            // Load saved theme settings
            loadThemeSettings();
            
            // Theme mode selection is now handled by onclick in HTML
        });
        
        // Preview color scheme (without saving)
        function previewColorScheme(colorScheme) {
            if (window.themeManager) {
                window.themeManager.applyColorScheme(colorScheme);
            } else {
                applyThemeFallback(colorScheme, localStorage.getItem('themeModePreference') || 'light');
            }
        }
        
        // Preview mode (without saving)
        function previewMode(mode) {
            // Update card selection
            const themeModeCards = document.querySelectorAll('.theme-mode-card');
            themeModeCards.forEach(card => {
                const cardMode = card.dataset.mode;
                card.style.border = cardMode === 'dark' ? '1px solid #334155' : '1px solid #E5E9F0';
                card.style.boxShadow = 'none';
                if (cardMode === 'dark') {
                    card.style.background = '#1E293B';
                    const cardBody = card.querySelector('.card-body');
                    if (cardBody) {
                        cardBody.style.color = '#F8FAFC';
                        const text = cardBody.querySelector('.font-medium');
                        if (text) text.style.color = '#F8FAFC';
                    }
                } else {
                    card.style.background = 'white';
                    const cardBody = card.querySelector('.card-body');
                    if (cardBody) {
                        cardBody.style.color = '';
                        const text = cardBody.querySelector('.font-medium');
                        if (text) text.style.color = '';
                    }
                }
            });
            
            // Highlight selected card
            const selectedCard = document.querySelector(`.theme-mode-card[data-mode="${mode}"]`);
            if (selectedCard) {
                const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim();
                selectedCard.style.border = '2px solid ' + primaryColor;
                selectedCard.style.boxShadow = '0 4px 12px rgba(74, 111, 165, 0.25)';
                if (mode === 'dark') {
                    selectedCard.style.background = '#334155';
                    const cardBody = selectedCard.querySelector('.card-body');
                    if (cardBody) {
                        cardBody.style.color = '#F8FAFC';
                        const text = cardBody.querySelector('.font-medium');
                        if (text) text.style.color = '#F8FAFC';
                    }
                } else if (mode === 'light') {
                    selectedCard.style.background = '#F0F9FF';
                } else {
                    selectedCard.style.background = '#FEF3C7';
                }
            }
            
            // Apply mode preview
            if (window.themeManager) {
                window.themeManager.applyMode(mode);
            } else {
                applyThemeFallback(localStorage.getItem('themeColorScheme') || 'Blue', mode);
            }
        }
        
        // Fallback theme application
        function applyThemeFallback(colorScheme, mode) {
            const root = document.documentElement;
            const schemes = {
                'Teal (Default)': { 
                    primary: '#0F766E', 
                    primaryDark: '#115E59',
                    primaryLight: '#14B8A6',
                    primaryLighter: '#CCFBF1'
                },
                'Blue': { 
                    primary: '#4A6FA5', 
                    primaryDark: '#2E4A73',
                    primaryLight: '#6B8FC7',
                    primaryLighter: '#E8EDF5'
                },
                'Green': { 
                    primary: '#059669', 
                    primaryDark: '#047857',
                    primaryLight: '#10B981',
                    primaryLighter: '#D1FAE5'
                },
                'Purple': { 
                    primary: '#7C3AED', 
                    primaryDark: '#6D28D9',
                    primaryLight: '#8B5CF6',
                    primaryLighter: '#EDE9FE'
                }
            };
            
            const scheme = schemes[colorScheme] || schemes['Blue'];
            root.style.setProperty('--color-primary', scheme.primary);
            root.style.setProperty('--color-primary-dark', scheme.primaryDark);
            root.style.setProperty('--color-primary-light', scheme.primaryLight);
            root.style.setProperty('--color-primary-lighter', scheme.primaryLighter);
            root.style.setProperty('--color-primary-hover', scheme.primaryDark);
            root.style.setProperty('--color-info', scheme.primary);
            root.style.setProperty('--color-info-light', scheme.primaryLighter);
            root.style.setProperty('--border-focus', scheme.primary);
            
            // Apply mode
            if (mode === 'auto') {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                mode = prefersDark ? 'dark' : 'light';
            }
            
            if (mode === 'dark') {
                document.body.classList.add('dark-mode');
                root.style.setProperty('--bg-body', '#0F172A');
                root.style.setProperty('--bg-sidebar', '#1E293B');
                root.style.setProperty('--bg-card', '#1E293B');
                root.style.setProperty('--bg-header', '#1E293B');
                root.style.setProperty('--bg-hover', '#334155');
                root.style.setProperty('--text-primary', '#F8FAFC');
                root.style.setProperty('--text-secondary', '#CBD5E1');
                root.style.setProperty('--border-light', '#334155');
            } else {
                document.body.classList.remove('dark-mode');
                root.style.setProperty('--bg-body', '#F8FAFC');
                root.style.setProperty('--bg-sidebar', '#F1F5F9');
                root.style.setProperty('--bg-card', '#FFFFFF');
                root.style.setProperty('--bg-header', '#FFFFFF');
                root.style.setProperty('--bg-hover', '#F1F5F9');
                root.style.setProperty('--text-primary', '#0F172A');
                root.style.setProperty('--text-secondary', '#475569');
                root.style.setProperty('--border-light', '#E5E7EB');
            }
        }
        
        // Load saved theme settings
        function loadThemeSettings() {
            const savedColorScheme = localStorage.getItem('themeColorScheme') || 'Blue';
            const savedMode = localStorage.getItem('themeModePreference') || 'light';
            
            // Set color scheme dropdown
            const colorSchemeSelect = document.getElementById('colorSchemeSelect');
            if (colorSchemeSelect) {
                colorSchemeSelect.value = savedColorScheme;
            }
            
            // Select mode card using previewMode function
            previewMode(savedMode);
        }
        
        // Sync JS mode with hidden form input
        function setFormThemeMode(mode) {
             const input = document.getElementById('themeModeInput');
             if (input) input.value = mode;
        }
        
        // Update form inputs prior to submit if needed
        function saveThemeFrontendState() {
             const colorSchemeSelect = document.getElementById('colorSchemeSelect');
             const colorScheme = colorSchemeSelect ? colorSchemeSelect.value : 'Blue';
             const selectedModeCard = document.querySelector('.theme-mode-card[style*="border: 2px"]');
             const mode = selectedModeCard ? selectedModeCard.dataset.mode : 'light';
             
             setFormThemeMode(mode);
             
             // Apply temporarily via manager before refresh completes the save
             if (window.themeManager) {
                 window.themeManager.applyColorScheme(colorScheme);
                 window.themeManager.applyMode(mode);
             } else {
                 localStorage.setItem('themeColorScheme', colorScheme);
                 localStorage.setItem('themeModePreference', mode);
             }
        }
        
        // Ensure previewMode function sets the form input
        const originalPreviewMode = previewMode;
        previewMode = function(mode) {
            originalPreviewMode(mode);
            setFormThemeMode(mode);
        }
        
        // Save theme settings wrapper completely replaced by form sumbission handle.
        // Keep to not break missing references
        function saveThemeSettings() {
             saveThemeFrontendState();
        }
    </script>
</body>

</html>