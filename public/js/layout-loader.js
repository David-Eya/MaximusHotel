/**
 * Layout Loader - Dynamically loads header, footer, and navigation layouts
 * This allows updates to layouts to be reflected across all pages
 */
const LayoutLoader = {
    basePath: '',
    
    /**
     * Initialize the layout loader
     */
    async init() {
        // Set base path to root (using absolute paths now)
        this.basePath = '/';
        
        // Load layouts
        await this.loadLayouts();
    },
    
    /**
     * Get the full path to a layout file
     */
    getLayoutPath(layoutName) {
        // Use absolute path from document root
        // In Laravel, the public folder is the document root, so layouts are at /layouts/
        return `/layouts/${layoutName}`;
    },
    
    /**
     * Load head content from header.html
     */
    async loadHead() {
        try {
            const layoutPath = this.getLayoutPath('header.html');
            const response = await fetch(layoutPath);
            
            if (!response.ok) {
                throw new Error(`Failed to load header: ${response.statusText}`);
            }
            
            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Get all elements from the parsed document (header.html contains just head elements)
            const headElements = doc.querySelectorAll('*');
            const currentHead = document.head;
            
            // Add head elements from layout (skip if already exists)
            headElements.forEach(element => {
                if (element.tagName === 'TITLE') {
                    const existingTitle = document.querySelector('title');
                    if (existingTitle) {
                        existingTitle.textContent = element.textContent;
                    } else {
                        currentHead.appendChild(element.cloneNode(true));
                    }
                } else if (element.tagName === 'META') {
                    const name = element.getAttribute('name') || element.getAttribute('property');
                    const existing = document.querySelector(`meta[${name ? 'name' : 'property'}="${name}"]`);
                    if (!existing) {
                        currentHead.appendChild(element.cloneNode(true));
                    }
                } else if (element.tagName === 'LINK') {
                    const rel = element.getAttribute('rel');
                    const href = element.getAttribute('href');
                    const existing = document.querySelector(`link[rel="${rel}"][href="${href}"]`);
                    if (!existing) {
                        currentHead.appendChild(element.cloneNode(true));
                    }
                } else if (element.tagName === 'SCRIPT') {
                    // For scripts, always add them
                    const newScript = document.createElement('script');
                    if (element.src) {
                        newScript.src = element.src;
                    } else {
                        newScript.textContent = element.textContent;
                    }
                    currentHead.appendChild(newScript);
                } else {
                    currentHead.appendChild(element.cloneNode(true));
                }
            });
            
            return true;
        } catch (error) {
            console.error('Error loading head:', error);
            return false;
        }
    },
    
    /**
     * Load a layout file and inject it into the page
     */
    async loadLayout(layoutName, placeholderId) {
        try {
            // Use simple relative path - browser will resolve it correctly
            const layoutPath = this.getLayoutPath(layoutName);
            
            console.log(`Loading layout from: ${layoutPath} (current path: ${window.location.pathname})`); // Debug
            const response = await fetch(layoutPath);
            
            if (!response.ok) {
                console.error(`Failed to load ${layoutName} from ${layoutPath}: ${response.status} ${response.statusText}`);
                throw new Error(`Failed to load ${layoutName}: ${response.statusText}`);
            }
            
            const html = await response.text();
            const placeholder = document.getElementById(placeholderId);
            
            if (placeholder) {
                placeholder.innerHTML = html;
                
                // Execute any scripts in the loaded HTML
                const scripts = placeholder.querySelectorAll('script');
                scripts.forEach(oldScript => {
                    const newScript = document.createElement('script');
                    Array.from(oldScript.attributes).forEach(attr => {
                        newScript.setAttribute(attr.name, attr.value);
                    });
                    newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                });
                
                // Re-initialize mobile menu after layout is loaded
                if (layoutName === 'mobilenav.html' && typeof jQuery !== 'undefined') {
                    // Re-initialize slicknav for mobile menu
                    setTimeout(() => {
                        if (jQuery('.mobile-menu').length && typeof jQuery.fn.slicknav !== 'undefined') {
                            jQuery('.mobile-menu').slicknav({
                                prependTo: '#mobile-menu-wrap',
                                allowParentLinks: true
                            });
                        }
                    }, 100);
                }
                
                return true;
            } else {
                console.warn(`Placeholder ${placeholderId} not found`);
                return false;
            }
        } catch (error) {
            console.error(`Error loading layout ${layoutName}:`, error);
            return false;
        }
    },
    
    /**
     * Update navigation based on authentication
     */
    async updateNavigation() {
        // Wait for auth to be available - retry if not ready
        let retries = 0;
        while (typeof auth === 'undefined' && retries < 10) {
            await new Promise(resolve => setTimeout(resolve, 100));
            retries++;
        }
        
        if (typeof auth === 'undefined') {
            console.warn('auth.js not loaded after retries, navigation update skipped');
            return;
        }
        
        // Wait for navigation elements to exist - they're loaded dynamically
        let elementRetries = 0;
        while ((!document.getElementById('navProfile') || !document.getElementById('mobileProfile')) && elementRetries < 20) {
            await new Promise(resolve => setTimeout(resolve, 100));
            elementRetries++;
        }
        
        const profileNav = document.getElementById('navProfile');
        const loginNav = document.getElementById('navLogin');
        const logoutLink = document.getElementById('navLogout');
        const mobileProfileNav = document.getElementById('mobileProfile');
        const mobileLoginNav = document.getElementById('mobileLogin');
        const mobileLogoutLink = document.getElementById('mobileLogout');
        
        // Debug: Check if elements exist
        console.log('Navigation update - Elements found:', {
            profileNav: !!profileNav,
            loginNav: !!loginNav,
            mobileProfileNav: !!mobileProfileNav,
            mobileLoginNav: !!mobileLoginNav,
            isAuthenticated: auth.isAuthenticated(),
            retries: elementRetries
        });
        
        if (auth.isAuthenticated()) {
            const result = await auth.verifyToken();
            if (result.success) {
                // User is authenticated
                console.log('User authenticated:', result.user.usertype);
                if (profileNav) {
                    profileNav.style.display = '';
                    console.log('Profile nav shown');
                    // Update profile image
                    this.updateProfileImage(result.user);
                } else {
                    console.warn('navProfile element not found - will retry');
                    // Retry once more after a delay
                    setTimeout(() => {
                        const retryProfileNav = document.getElementById('navProfile');
                        if (retryProfileNav) {
                            retryProfileNav.style.display = '';
                            console.log('Profile nav shown on retry');
                            // Update profile image on retry
                            this.updateProfileImage(result.user);
                        }
                    }, 500);
                }
                if (loginNav) {
                    loginNav.style.display = 'none';
                    console.log('Login nav hidden');
                }
                if (mobileProfileNav) {
                    mobileProfileNav.style.display = '';
                    console.log('Mobile profile nav shown');
                    // Update mobile profile image
                    this.updateProfileImage(result.user, true);
                } else {
                    console.warn('mobileProfile element not found - will retry');
                    // Retry once more after a delay
                    setTimeout(() => {
                        const retryMobileProfileNav = document.getElementById('mobileProfile');
                        if (retryMobileProfileNav) {
                            retryMobileProfileNav.style.display = '';
                            console.log('Mobile profile nav shown on retry');
                            // Update mobile profile image on retry
                            this.updateProfileImage(result.user, true);
                        }
                    }, 500);
                }
                
                // Show and update mobile profile bottom section
                const mobileProfileBottom = document.getElementById('mobileProfileBottom');
                if (mobileProfileBottom) {
                    mobileProfileBottom.style.display = 'block';
                    this.updateMobileProfileBottom(result.user);
                }
                if (mobileLoginNav) {
                    mobileLoginNav.style.display = 'none';
                    console.log('Mobile login nav hidden');
                }
                
                // Set up logout handlers
                if (logoutLink) {
                    logoutLink.addEventListener('click', async (e) => {
                        e.preventDefault();
                        await auth.logout();
                        Swal.fire({
                            icon: 'success',
                            title: 'Logout Successful',
                            text: 'You have been logged out successfully',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#084466',
                            allowOutsideClick: false,
                            timer: 2000,
                            timerProgressBar: true
                        }).then((result) => {
                            if (result.isConfirmed || result.dismiss === Swal.DismissReason.timer) {
                                window.location.href = 'login.html';
                            }
                        });
                    });
                }
                
                if (mobileLogoutLink) {
                    mobileLogoutLink.addEventListener('click', async (e) => {
                        e.preventDefault();
                        await auth.logout();
                        Swal.fire({
                            icon: 'success',
                            title: 'Logout Successful',
                            text: 'You have been logged out successfully',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#084466',
                            allowOutsideClick: false,
                            timer: 2000,
                            timerProgressBar: true
                        }).then((result) => {
                            if (result.isConfirmed || result.dismiss === Swal.DismissReason.timer) {
                                window.location.href = 'login.html';
                            }
                        });
                    });
                }
                
                // Redirect Admin/Incharge users to their dashboards (only on index.html)
                const currentPage = window.location.pathname.split('/').pop() || 'index.html';
                if (currentPage === 'index.html') {
                    const user = result.user;
                    if (user.usertype === 'Admin') {
                        window.location.href = 'admin/index.html';
                        return;
                    } else if (user.usertype === 'Incharge') {
                        window.location.href = 'incharge/index.html';
                        return;
                    }
                }
                // Client users can stay on the landing page
            } else {
                // Token invalid
                if (profileNav) profileNav.style.display = 'none';
                if (loginNav) loginNav.style.display = '';
                if (mobileProfileNav) mobileProfileNav.style.display = 'none';
                if (mobileLoginNav) mobileLoginNav.style.display = '';
                
                // Hide mobile profile bottom section
                const mobileProfileBottom = document.getElementById('mobileProfileBottom');
                if (mobileProfileBottom) {
                    mobileProfileBottom.style.display = 'none';
                }
            }
        } else {
            // Not authenticated
            if (profileNav) profileNav.style.display = 'none';
            if (loginNav) loginNav.style.display = '';
            if (mobileProfileNav) mobileProfileNav.style.display = 'none';
            if (mobileLoginNav) mobileLoginNav.style.display = '';
            
            // Hide mobile profile bottom section
            const mobileProfileBottom = document.getElementById('mobileProfileBottom');
            if (mobileProfileBottom) {
                mobileProfileBottom.style.display = 'none';
            }
        }
    },
    
    /**
     * Update profile image in navigation
     */
    updateProfileImage(userData, isMobile = false) {
        if (!userData) return;
        
        const imageId = isMobile ? 'mobileProfileImage' : 'navProfileImage';
        const iconId = isMobile ? 'mobileProfileIcon' : 'navProfileIcon';
        
        const profileImage = document.getElementById(imageId);
        const profileIcon = document.getElementById(iconId);
        
        if (profileImage && profileIcon) {
            if (userData.image && userData.image.trim()) {
                profileImage.src = `/profile_img/${userData.image}`;
                profileImage.style.display = 'block';
                profileIcon.style.display = 'none';
            } else {
                profileImage.src = '/profile_img/default.jpg';
                profileImage.style.display = 'block';
                profileIcon.style.display = 'none';
            }
            
            // Add error handler to fallback to icon if image fails to load
            profileImage.onerror = function() {
                this.style.display = 'none';
                if (profileIcon) profileIcon.style.display = 'inline-block';
            };
        }
    },
    
    /**
     * Update mobile profile bottom section
     */
    updateMobileProfileBottom(userData) {
        if (!userData) return;
        
        const profileImage = document.getElementById('mobileProfileBottomImage');
        const profileIcon = document.getElementById('mobileProfileBottomIcon');
        const profileName = document.getElementById('mobileProfileBottomName');
        const profileEmail = document.getElementById('mobileProfileBottomEmail');
        
        // Update profile image/icon
        if (profileImage && profileIcon) {
            if (userData.image && userData.image.trim()) {
                profileImage.src = `/profile_img/${userData.image}`;
                profileImage.style.display = 'block';
                profileIcon.style.display = 'none';
            } else {
                profileImage.src = '/profile_img/default.jpg';
                profileImage.style.display = 'block';
                profileIcon.style.display = 'none';
            }
            
            // Add error handler to fallback to icon if image fails to load
            profileImage.onerror = function() {
                this.style.display = 'none';
                if (profileIcon) profileIcon.style.display = 'block';
            };
        }
        
        // Update name and email
        if (profileName) {
            const fullName = `${userData.fname || ''} ${userData.lname || ''}`.trim() || 'User';
            profileName.textContent = fullName;
        }
        
        if (profileEmail) {
            profileEmail.textContent = userData.email || '';
        }
    },
    
    /**
     * Load all layouts
     */
    async loadLayouts() {
        // Note: Head content (CSS) should be in HTML file directly for proper rendering
        // Only load navigation and footer dynamically
        
        // Load mobile navigation
        await this.loadLayout('mobilenav.html', 'layout-mobilenav-placeholder');
        
        // Load navbar
        await this.loadLayout('navbar.html', 'layout-navbar-placeholder');
        
        // Load footer (includes scripts)
        await this.loadLayout('footer.html', 'layout-footer-placeholder');
        
        // Set active navigation item
        this.setActiveNav();
        
        // Update navigation based on authentication (after layouts are loaded)
        // Wait for DOM to be ready and scripts to execute
        // If user just logged in, wait a bit longer and retry
        const justLoggedIn = sessionStorage.getItem('justLoggedIn');
        const delay = justLoggedIn ? 800 : 500;
        
        setTimeout(() => {
            this.updateNavigation().then(() => {
                // Clear the flag after navigation is updated
                if (justLoggedIn) {
                    sessionStorage.removeItem('justLoggedIn');
                }
            });
        }, delay);
    },
    
    /**
     * Set active navigation item based on current page
     */
    setActiveNav() {
        const currentPage = window.location.pathname.split('/').pop() || 'index.html';
        const navItems = document.querySelectorAll('.mainmenu a, .mobile-menu a');
        
        navItems.forEach(item => {
            const href = item.getAttribute('href');
            if (href && (href === currentPage || href.includes(currentPage))) {
                item.parentElement.classList.add('active');
            } else {
                item.parentElement.classList.remove('active');
            }
        });
    }
};

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        LayoutLoader.init();
    });
} else {
    LayoutLoader.init();
}

// Expose updateNavigation globally so it can be called manually if needed
window.updateNavigation = () => {
    LayoutLoader.updateNavigation();
};
