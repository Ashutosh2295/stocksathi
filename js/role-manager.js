/**
 * ROLE MANAGER - Handle role-based permissions and routing
 */

(function () {
    'use strict';

    const ROLES = {
        SUPER_ADMIN: 'super_admin',
        ADMIN: 'admin',
        SALES: 'sales',
        STAFF: 'staff'
    };

    const ROLE_DASHBOARDS = {
        [ROLES.SUPER_ADMIN]: '/super-admin/dashboard.html',
        [ROLES.ADMIN]: '/index.html',
        [ROLES.SALES]: '/sales/dashboard.html',
        [ROLES.STAFF]: '/sales/dashboard.html'
    };

    const ROLE_HIERARCHY = {
        [ROLES.SUPER_ADMIN]: 4,
        [ROLES.ADMIN]: 3,
        [ROLES.SALES]: 2,
        [ROLES.STAFF]: 1
    };

    /**
     * Get user role from Firestore
     */
    async function getUserRole(userId) {
        try {
            if (!window.firebaseDb) {
                throw new Error('Firebase not initialized');
            }

            const userDoc = await window.firebaseDb.collection('users').doc(userId).get();

            if (userDoc.exists) {
                const userData = userDoc.data();
                return userData.role || ROLES.ADMIN; // Default to ADMIN not SALES
            }

            // User document doesn't exist, create one
            console.log('Creating user document...');
            await window.firebaseDb.collection('users').doc(userId).set({
                email: window.firebaseAuth.currentUser.email,
                role: ROLES.ADMIN, // Default first user to admin
                createdAt: firebase.firestore.FieldValue.serverTimestamp()
            });

            return ROLES.ADMIN;

        } catch (error) {
            console.error('Error getting user role:', error);
            return ROLES.ADMIN; // Default to ADMIN, not SALES
        }
    }

    /**
     * Redirect user to appropriate dashboard based on role
     */
    function redirectToDashboard(role) {
        const dashboard = ROLE_DASHBOARDS[role] || ROLE_DASHBOARDS[ROLES.ADMIN];
        const currentPath = window.location.pathname;

        // Don't redirect if already on correct dashboard
        if (!currentPath.includes(dashboard)) {
            console.log(`Redirecting ${role} to ${dashboard}`);
            window.location.href = dashboard;
        }
    }

    /**
     * Check if user has permission to access current page
     */
    function checkPagePermission(userRole) {
        const currentPath = window.location.pathname.toLowerCase();

        // Super Admin restrictions
        if (currentPath.includes('/super-admin/')) {
            return userRole === ROLES.SUPER_ADMIN;
        }

        // Admin restrictions (includes settings, user management, etc.)
        if (currentPath.includes('/admin/') ||
            currentPath.includes('settings.html') ||
            currentPath.includes('users.html') ||
            currentPath.includes('roles.html')) {
            return userRole === ROLES.ADMIN || userRole === ROLES.SUPER_ADMIN;
        }

        // Sales restrictions (limited pages)
        if (userRole === ROLES.SALES || userRole === ROLES.STAFF) {
            const allowedPages = [
                'dashboard',
                'sales',
                'invoice',
                'customers',
                'products' // read-only
            ];

            return allowedPages.some(page => currentPath.includes(page));
        }

        // Admin and Super Admin can access everything else
        return true;
    }

    /**
     * Initialize role-based routing
     */
    async function initializeRoleSystem() {
        try {
            const user = window.firebaseAuth.currentUser;

            if (!user) {
                console.log('No user logged in');
                return;
            }

            // Get user role
            const role = await getUserRole(user.uid);
            console.log('✅ User role:', role);

            // Store role in session
            const userData = JSON.parse(localStorage.getItem('stocksathi_user') || '{}');
            userData.role = role;
            localStorage.setItem('stocksathi_user', JSON.stringify(userData));
            window.currentUserRole = role;

            // COMMENTED OUT: This was causing unwanted redirects to sales dashboard
            // Check page permission
            // if (!checkPagePermission(role)) {
            //     console.log('⛔ Access denied - redirecting to dashboard');
            //     redirectToDashboard(role);
            //     return;
            // }

            // Update UI based on role
            updateUIForRole(role);

        } catch (error) {
            console.error('❌ Role system error:', error);
        }
    }

    /**
     * Update UI elements based on user role
     */
    function updateUIForRole(role) {
        // Hide Super Admin menu items from non-super-admins
        if (role !== ROLES.SUPER_ADMIN) {
            const superAdminElements = document.querySelectorAll('[data-role="super_admin"]');
            superAdminElements.forEach(el => el.style.display = 'none');
        }

        // Hide Admin menu items from sales/staff
        if (role === ROLES.SALES || role === ROLES.STAFF) {
            const adminElements = document.querySelectorAll('[data-role="admin"]');
            adminElements.forEach(el => el.style.display = 'none');
        }

        // Add role indicator to header
        const roleIndicator = document.getElementById('userRole');
        if (roleIndicator) {
            const roleNames = {
                [ROLES.SUPER_ADMIN]: 'Super Admin',
                [ROLES.ADMIN]: 'Admin',
                [ROLES.SALES]: 'Sales',
                [ROLES.STAFF]: 'Staff'
            };
            roleIndicator.textContent = roleNames[role] || role;
        }
    }

    /**
     * Check if user can perform action
     */
    function canPerformAction(action, userRole = window.currentUserRole) {
        const permissions = {
            [ROLES.SUPER_ADMIN]: ['*'], // All permissions
            [ROLES.ADMIN]: [
                'product.create', 'product.edit', 'product.delete',
                'stock.in', 'stock.out', 'stock.adjust',
                'sales.create', 'sales.edit', 'sales.view_all',
                'user.create', 'user.edit',
                'settings.edit'
            ],
            [ROLES.SALES]: [
                'sales.create',
                'sales.view_own',
                'product.view'
            ],
            [ROLES.STAFF]: [
                'sales.create',
                'product.view'
            ]
        };

        const rolePermissions = permissions[userRole] || [];

        // Super admin has all permissions
        if (rolePermissions.includes('*')) return true;

        return rolePermissions.includes(action);
    }

    // Export functions
    window.roleManager = {
        ROLES,
        getUserRole,
        initializeRoleSystem,
        redirectToDashboard,
        checkPagePermission,
        canPerformAction,
        ROLE_HIERARCHY
    };

    // Auto-initialize when Firebase auth is ready
    if (window.firebaseAuth) {
        window.firebaseAuth.onAuthStateChanged(user => {
            if (user) {
                setTimeout(initializeRoleSystem, 500);
            }
        });
    }

    console.log('✅ Role Manager Loaded');

})();
