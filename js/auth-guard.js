/**
* AUTH GUARD - Protect Pages from Unauthorized Access
* Include this on all protected pages (not login/register)
*/

(function () {
    'use strict';

    // Check if user is authenticated
    function checkAuth() {
        return new Promise((resolve, reject) => {
            // Check if Firebase is initialized
            if (!window.firebaseAuth) {
                console.warn('⚠️ Firebase Auth not initialized yet, waiting...');
                setTimeout(() => {
                    if (window.firebaseAuth) {
                        resolve(checkAuthState());
                    } else {
                        reject(new Error('Firebase Auth failed to initialize'));
                    }
                }, 1000);
                return;
            }

            resolve(checkAuthState());
        });
    }

    function checkAuthState() {
        return new Promise((resolve) => {
            window.firebaseAuth.onAuthStateChanged((user) => {
                if (user) {
                    console.log('✅ User authenticated:', user.email);

                    // Update session storage
                    const userData = {
                        uid: user.uid,
                        email: user.email,
                        displayName: user.displayName || user.email.split('@')[0],
                        emailVerified: user.emailVerified
                    };

                    localStorage.setItem('stocksathi_user', JSON.stringify(userData));
                    window.currentUser = userData;

                    resolve(true);
                } else {
                    console.log('❌ No user authenticated');
                    resolve(false);
                }
            });
        });
    }

    // Redirect to login if not authenticated
    async function guardPage() {
        try {
            const isAuthenticated = await checkAuth();

            if (!isAuthenticated) {
                console.log('🔒 Redirecting to login...');
                // Allow a moment for any pending auth state changes
                setTimeout(() => {
                    // Check if we're already on a login page
                    if (!window.location.pathname.includes('login.html') &&
                        !window.location.pathname.includes('register.html')) {

                        // Determine correct path based on current location
                        const currentPath = window.location.pathname;
                        const isInPagesDir = currentPath.includes('/pages/');
                        const loginPath = isInPagesDir ? 'login.html' : 'pages/login.html';

                        console.log('🔀 Redirecting to:', loginPath);
                        window.location.href = loginPath;
                    }
                }, 100);
                return false;
            }

            return true;
        } catch (error) {
            console.error('❌ Auth guard error:', error);
            return false;
        }
    }

    // Check if we're on a public page
    const currentPage = window.location.pathname;
    const publicPages = ['/login.html', '/register.html', '/forgot-password.html'];
    const isPublicPage = publicPages.some(page => currentPage.includes(page));

    if (!isPublicPage) {
        // Protect this page
        console.log('🔒 Auth Guard Active');
        guardPage().then(authenticated => {
            if (authenticated) {
                console.log('✅ Page access granted');

                // Update user display in header if element exists
                const userNameEl = document.getElementById('headerUserName');
                if (userNameEl && window.currentUser) {
                    userNameEl.textContent = window.currentUser.displayName;
                }
            }
        });
    }

    // Export guard function for manual use
    window.authGuard = {
        check: guardPage,
        getCurrentUser: () => window.currentUser
    };

})();

console.log('✅ Auth Guard Loaded');
