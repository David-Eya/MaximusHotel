/**
 * Admin Authentication - Verify admin access and handle redirects
 * Note: This file must be loaded after ../public/js/auth.js
 */
class AdminAuth {
    constructor() {
        // Auth class should be available from public/js/auth.js
        if (typeof Auth === 'undefined') {
            console.error('Auth class not found. Make sure public/js/auth.js is loaded before this script.');
            return;
        }
        this.auth = new Auth();
    }

    /**
     * Verify admin access and redirect if not authorized
     */
    async verifyAdminAccess() {
        const token = this.auth.getToken();
        const userData = this.auth.getUser(); // Use getUser() instead of getUserData()

        if (!token || !userData) {
            // No token or user data, redirect to login
            window.location.href = '/login.html';
            return false;
        }

        // Verify token is still valid and user is Admin or Incharge
        const verified = await this.auth.verifyToken();
        if (!verified || !verified.success || !verified.user || 
            (verified.user.usertype !== 'Admin' && verified.user.usertype !== 'Incharge')) {
            this.auth.removeToken();
            Swal.fire({
                icon: 'error',
                title: 'Access Denied',
                text: 'Admin or Incharge access required. Please login with appropriate credentials.',
                confirmButtonColor: '#084466'
            }).then(() => {
                window.location.href = '/login.html';
            });
            return false;
        }

        return true;
    }

    /**
     * Initialize admin auth check
     */
    async init() {
        return await this.verifyAdminAccess();
    }
}

// Auto-check on page load
const adminAuth = new AdminAuth();
document.addEventListener('DOMContentLoaded', async () => {
    const hasAccess = await adminAuth.init();
    if (!hasAccess) {
        // Stop execution if no access
        return;
    }
    // Continue with page initialization
    if (typeof window.onAdminReady === 'function') {
        window.onAdminReady();
    }
});


