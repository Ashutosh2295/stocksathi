// Authentication Module for Stocksathi

/**
 * Login with email and password
 * @param {string} email - User email
 * @param {string} password - User password
 * @returns {Promise} Firebase auth result
 */
async function login(email, password) {
    try {
        const userCredential = await firebaseAuth.signInWithEmailAndPassword(email, password);
        const user = userCredential.user;

        // Log activity
        await logActivity('login', 'User logged in', user.uid);

        // Store user session data
        localStorage.setItem('stocksathi_user', JSON.stringify({
            uid: user.uid,
            email: user.email,
            displayName: user.displayName || 'User',
            lastLogin: new Date().toISOString()
        }));

        console.log('✅ Login successful:', user.email);
        return { success: true, user };
    } catch (error) {
        console.error('❌ Login error:', error);
        let errorMessage = 'Login failed. Please try again.';

        switch (error.code) {
            case 'auth/user-not-found':
                errorMessage = 'No user found with this email.';
                break;
            case 'auth/wrong-password':
                errorMessage = 'Incorrect password.';
                break;
            case 'auth/invalid-email':
                errorMessage = 'Invalid email address.';
                break;
            case 'auth/user-disabled':
                errorMessage = 'This account has been disabled.';
                break;
            case 'auth/too-many-requests':
                errorMessage = 'Too many failed login attempts. Please try again later.';
                break;
        }

        return { success: false, error: errorMessage };
    }
}

/**
 * Logout current user
 * @returns {Promise} Firebase auth result
 */
async function logout() {
    try {
        const user = firebaseAuth.currentUser;

        if (user) {
            // Log activity before logout
            await logActivity('logout', 'User logged out', user.uid);
        }

        await firebaseAuth.signOut();

        // Clear local storage
        localStorage.removeItem('stocksathi_user');

        console.log('✅ Logout successful');

        // Redirect to login page
        window.location.href = getLoginPagePath();

        return { success: true };
    } catch (error) {
        console.error('❌ Logout error:', error);
        return { success: false, error: error.message };
    }
}

/**
 * Check if user is authenticated
 * @returns {boolean} Authentication status
 */
function isAuthenticated() {
    const user = firebaseAuth.currentUser;
    const storedUser = localStorage.getItem('stocksathi_user');
    return !!(user || storedUser);
}

/**
 * Get current user data
 * @returns {Object|null} User data or null
 */
function getCurrentUser() {
    const user = firebaseAuth.currentUser;
    if (user) {
        return {
            uid: user.uid,
            email: user.email,
            displayName: user.displayName || 'User',
            photoURL: user.photoURL
        };
    }

    // Fallback to localStorage
    const storedUser = localStorage.getItem('stocksathi_user');
    return storedUser ? JSON.parse(storedUser) : null;
}

/**
 * Protect routes - redirect to login if not authenticated
 */
function protectRoute() {
    // Skip protection for login and forgot-password pages
    const currentPath = window.location.pathname;
    if (currentPath.includes('login.html') || currentPath.includes('forgot-password.html')) {
        return;
    }

    // Check authentication state
    firebaseAuth.onAuthStateChanged((user) => {
        if (!user && !localStorage.getItem('stocksathi_user')) {
            console.warn('⚠️ User not authenticated, redirecting to login...');
            window.location.href = getLoginPagePath();
        } else if (user) {
            // Update stored user data
            localStorage.setItem('stocksathi_user', JSON.stringify({
                uid: user.uid,
                email: user.email,
                displayName: user.displayName || 'User',
                lastLogin: new Date().toISOString()
            }));

            // Update UI with user info
            updateUserDisplay(user);
        }
    });
}

/**
 * Update user display in header
 * @param {Object} user - User object
 */
function updateUserDisplay(user) {
    const userName = document.querySelector('.header-user-name');
    const userAvatar = document.querySelector('.header-user-avatar');

    if (userName) {
        userName.textContent = user.displayName || user.email.split('@')[0];
    }

    if (userAvatar && user.displayName) {
        const initials = user.displayName.split(' ')
            .map(n => n[0])
            .join('')
            .toUpperCase()
            .substring(0, 2);
        userAvatar.textContent = initials;
    }
}

/**
 * Get relative path to login page
 * @returns {string} Path to login.html
 */
function getLoginPagePath() {
    const currentPath = window.location.pathname;

    // If we're in the pages directory
    if (currentPath.includes('/pages/')) {
        return 'login.html';
    }

    // If we're in the root directory
    return 'pages/login.html';
}

/**
 * Log activity to Firestore
 * @param {string} type - Activity type
 * @param {string} description - Activity description
 * @param {string} userId - User ID
 */
async function logActivity(type, description, userId) {
    try {
        await firebaseDb.collection(firestoreCollections.activityLogs).add({
            type,
            description,
            userId,
            timestamp: firebase.firestore.FieldValue.serverTimestamp(),
            userAgent: navigator.userAgent,
            ip: 'N/A' // Would need backend service to get real IP
        });
    } catch (error) {
        console.error('Failed to log activity:', error);
    }
}

/**
 * Initialize authentication on page load
 */
function initAuth() {
    // Protect routes (except login pages)
    protectRoute();

    // Set up logout button listener
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            await logout();
        });
    }
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAuth);
} else {
    initAuth();
}

// Export functions for use in other modules
window.authModule = {
    login,
    logout,
    isAuthenticated,
    getCurrentUser,
    protectRoute,
    logActivity
};
