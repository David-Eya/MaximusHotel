/**
 * API Configuration
 * 
 * This file contains the API base URL configuration.
 * Update this file when deploying to production.
 * 
 * For local development: use '/api' (relative path)
 * For production: use full URL like 'https://yourdomain.com/api'
 */

const CONFIG = {
    // API Base URL
    // For local development (Laravel serves frontend and backend together):
    API_BASE_URL: '/api',
    
    // For production (frontend on GitHub Pages, backend on Hostinger):
    // Production backend URL
    PRODUCTION_API_URL: 'https://hotelmaximus.bytevortexz.com/api',
    
    // Environment detection
    isProduction: window.location.hostname !== 'localhost' && 
                  window.location.hostname !== '127.0.0.1' &&
                  !window.location.hostname.includes('localhost') &&
                  window.location.hostname !== 'hotelmaximus.bytevortexz.com',
    
    // Auto-detect production API URL if needed
    getApiBaseUrl: function() {
        // If already set to full URL, use it
        if (this.API_BASE_URL.startsWith('http://') || this.API_BASE_URL.startsWith('https://')) {
            return this.API_BASE_URL;
        }
        
        // For production (GitHub Pages), use full URL
        if (this.isProduction) {
            return this.PRODUCTION_API_URL;
        }
        
        // For local development, use relative path
        return this.API_BASE_URL;
    }
};

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CONFIG;
}

