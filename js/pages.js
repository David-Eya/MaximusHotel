// Pages utility functions
const Pages = {
    basePath: '',
    
    // Initialize base path
    async init() {
        try {
            // Calculate base path from current location instead of API call
            // This works better for different directory structures
            const path = window.location.pathname;
            const pathParts = path.split('/').filter(p => p && !p.includes('.html'));
            // Remove 'resources' and 'views' from path to get project root
            const filteredParts = pathParts.filter(p => p !== 'resources' && p !== 'views');
            this.basePath = filteredParts.length > 0 ? '/' + filteredParts.join('/') + '/' : '/';
        } catch (error) {
            console.error('Error getting base path:', error);
            this.basePath = '/';
        }
    },
    
    // Get base path for assets
    getBasePath() {
        return this.basePath;
    },
    
        setActiveNav(currentPage) {
        const navItems = document.querySelectorAll('.mainmenu a, .mobile-menu a');
        navItems.forEach(item => {
            const href = item.getAttribute('href');
            if (href && href.includes(currentPage)) {
                item.parentElement.classList.add('active');
            } else {
                item.parentElement.classList.remove('active');
            }
        });
    },
    
    // Format currency
    formatCurrency(amount) {
        return '₱' + parseFloat(amount).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    },
    
    // Truncate text
    truncateText(text, maxLength) {
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    },
    
        updateAssetPaths() {
        const basePath = this.getBasePath();
        document.querySelectorAll('[data-base-path]').forEach(el => {
            const attr = el.getAttribute('data-base-path');
            if (attr) {
                el.setAttribute(attr, basePath + el.getAttribute('data-base-path-value'));
            }
        });
    }
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    Pages.init().then(() => {
        Pages.updateAssetPaths();
        const currentPage = window.location.pathname.split('/').pop() || 'index.html';
        Pages.setActiveNav(currentPage);
    });
});


