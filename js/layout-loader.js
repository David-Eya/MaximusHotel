const LayoutLoader = {
    basePath: '',
    
    async init() {
        this.basePath = '/';
        await this.loadLayouts();
    },
    
    getLayoutPath(layoutName) {
        return `/MaximusHotel/layouts/${layoutName}`;
    },
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
            
                        const headElements = doc.querySelectorAll('*');
            const currentHead = document.head;
            
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
                
                                const scripts = placeholder.querySelectorAll('script');
                scripts.forEach(oldScript => {
                    const newScript = document.createElement('script');
                    Array.from(oldScript.attributes).forEach(attr => {
                        newScript.setAttribute(attr.name, attr.value);
                    });
                    newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                });
                
                                if (layoutName === 'mobilenav.html' && typeof jQuery !== 'undefined') {
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
                let retries = 0;
        while (typeof auth === 'undefined' && retries < 10) {
            await new Promise(resolve => setTimeout(resolve, 100));
            retries++;
        }
        
        if (typeof auth === 'undefined') {
            console.warn('auth.js not loaded after retries, navigation update skipped');
            return;
        }
        
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
                                console.log('User authenticated:', result.user.usertype);
                if (profileNav) {
                    profileNav.style.display = '';
                    console.log('Profile nav shown');
                                        this.updateProfileImage(result.user);
                } else {
                    console.warn('navProfile element not found - will retry');
                    // Retry once more after a delay
                    setTimeout(() => {
                        const retryProfileNav = document.getElementById('navProfile');
                        if (retryProfileNav) {
                            retryProfileNav.style.display = '';
                            console.log('Profile nav shown on retry');
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
                                        this.updateProfileImage(result.user, true);
                } else {
                    console.warn('mobileProfile element not found - will retry');
                    // Retry once more after a delay
                    setTimeout(() => {
                        const retryMobileProfileNav = document.getElementById('mobileProfile');
                        if (retryMobileProfileNav) {
                            retryMobileProfileNav.style.display = '';
                            console.log('Mobile profile nav shown on retry');
                                                        this.updateProfileImage(result.user, true);
                        }
                    }, 500);
                }
                
                                const mobileProfileBottom = document.getElementById('mobileProfileBottom');
                if (mobileProfileBottom) {
                    mobileProfileBottom.style.display = 'block';
                    this.updateMobileProfileBottom(result.user);
                }
                if (mobileLoginNav) {
                    mobileLoginNav.style.display = 'none';
                    console.log('Mobile login nav hidden');
                }
                
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
                                window.location.href = '/MaximusHotel/login.html';
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
                                window.location.href = '/MaximusHotel/login.html';
                            }
                        });
                    });
                }
                
                const currentPage = window.location.pathname.split('/').pop() || 'index.html';
                if (currentPage === 'index.html') {
                    const user = result.user;
                    if (user.usertype === 'Admin') {
                        window.location.href = '/MaximusHotel/views/index.html';
                        return;
                    } else if (user.usertype === 'Incharge') {
                        window.location.href = '/MaximusHotel/views/index.html';
                        return;
                    }
                }
            } else {
                if (profileNav) profileNav.style.display = 'none';
                if (loginNav) loginNav.style.display = '';
                if (mobileProfileNav) mobileProfileNav.style.display = 'none';
                if (mobileLoginNav) mobileLoginNav.style.display = '';
                
                                const mobileProfileBottom = document.getElementById('mobileProfileBottom');
                if (mobileProfileBottom) {
                    mobileProfileBottom.style.display = 'none';
                }
            }
        } else {
            if (profileNav) profileNav.style.display = 'none';
            if (loginNav) loginNav.style.display = '';
            if (mobileProfileNav) mobileProfileNav.style.display = 'none';
            if (mobileLoginNav) mobileLoginNav.style.display = '';
            
                        const mobileProfileBottom = document.getElementById('mobileProfileBottom');
            if (mobileProfileBottom) {
                mobileProfileBottom.style.display = 'none';
            }
        }
    },
    
    updateProfileImage(userData, isMobile = false) {
        if (!userData) return;
        
        const imageId = isMobile ? 'mobileProfileImage' : 'navProfileImage';
        const iconId = isMobile ? 'mobileProfileIcon' : 'navProfileIcon';
        
        const profileImage = document.getElementById(imageId);
        const profileIcon = document.getElementById(iconId);
        
        if (profileImage && profileIcon) {
                        let imageUrl;
            if (typeof CONFIG !== 'undefined' && CONFIG.getProfileImageUrl) {
                imageUrl = CONFIG.getProfileImageUrl(userData.image);
            } else {
                                const backendUrl = 'https://hotelmaximus.bytevortexz.com';
                imageUrl = userData.image && userData.image.trim() 
                    ? `${backendUrl}/profile_img/${userData.image}`
                    : `${backendUrl}/profile_img/default.jpg`;
            }
            profileImage.src = imageUrl;
            profileImage.style.display = 'block';
            profileIcon.style.display = 'none';
            
                        profileImage.onerror = function() {
                this.style.display = 'none';
                if (profileIcon) profileIcon.style.display = 'inline-block';
            };
        }
    },
    
    updateMobileProfileBottom(userData) {
        if (!userData) return;
        
        const profileImage = document.getElementById('mobileProfileBottomImage');
        const profileIcon = document.getElementById('mobileProfileBottomIcon');
        const profileName = document.getElementById('mobileProfileBottomName');
        const profileEmail = document.getElementById('mobileProfileBottomEmail');
        
                if (profileImage && profileIcon) {
                        let imageUrl;
            if (typeof CONFIG !== 'undefined' && CONFIG.getProfileImageUrl) {
                imageUrl = CONFIG.getProfileImageUrl(userData.image);
            } else {
                                const backendUrl = 'https://hotelmaximus.bytevortexz.com';
                imageUrl = userData.image && userData.image.trim() 
                    ? `${backendUrl}/profile_img/${userData.image}`
                    : `${backendUrl}/profile_img/default.jpg`;
            }
            profileImage.src = imageUrl;
            profileImage.style.display = 'block';
            profileIcon.style.display = 'none';
            
                        profileImage.onerror = function() {
                this.style.display = 'none';
                if (profileIcon) profileIcon.style.display = 'block';
            };
        }
        
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
        
                await this.loadLayout('mobilenav.html', 'layout-mobilenav-placeholder');
        
                await this.loadLayout('navbar.html', 'layout-navbar-placeholder');
        
                await this.loadLayout('footer.html', 'layout-footer-placeholder');
        
                this.setActiveNav();
        
                        // If user just logged in, wait a bit longer and retry
        const justLoggedIn = sessionStorage.getItem('justLoggedIn');
        const delay = justLoggedIn ? 800 : 500;
        
        setTimeout(() => {
            this.updateNavigation().then(() => {
                if (justLoggedIn) {
                    sessionStorage.removeItem('justLoggedIn');
                }
            });
        }, delay);
    },
    
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

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        LayoutLoader.init();
    });
} else {
    LayoutLoader.init();
}

window.updateNavigation = () => {
    LayoutLoader.updateNavigation();
};

