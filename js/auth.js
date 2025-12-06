class Auth {
    constructor() {
        this.tokenKey = 'auth_token';
        this.userKey = 'user_data';
        
        if (typeof CONFIG !== 'undefined' && CONFIG.getApiBaseUrl) {
            this.apiBase = CONFIG.getApiBaseUrl();
        } else {
            this.apiBase = 'https://hotelmaximus.bytevortexz.com/api';
            console.warn('CONFIG not found, using default Hostinger API URL');
        }
        
        console.log('API Base URL:', this.apiBase);
    }

    hashPassword(password) {
        if (typeof CryptoJS !== 'undefined') {
            return CryptoJS.SHA256(password).toString();
        } else {
            console.error('CryptoJS not loaded. Password will be sent unhashed.');
            return password;
        }
    }

    async testApiAccess() {
        try {
            const testUrl = this.apiBase + '/auth/verify';
            console.log('Testing API access to:', testUrl);
        } catch (error) {
            console.error('API test error:', error);
        }
    }

    // Get stored token
    getToken() {
        return localStorage.getItem(this.tokenKey);
    }

        setToken(token) {
        localStorage.setItem(this.tokenKey, token);
    }

    // Remove token
    removeToken() {
        localStorage.removeItem(this.tokenKey);
        localStorage.removeItem(this.userKey);
    }

    // Get user data
    getUser() {
        const userData = localStorage.getItem(this.userKey);
        return userData ? JSON.parse(userData) : null;
    }

        setUser(user) {
        localStorage.setItem(this.userKey, JSON.stringify(user));
    }

        isAuthenticated() {
        return this.getToken() !== null;
    }

    // Login
    async login(email, password) {
        try {
            const hashedPassword = this.hashPassword(password);
            const response = await fetch(`${this.apiBase}/auth/login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email, password: hashedPassword })
            });

            const data = await response.json();

            if (data.success) {
                // jsonResponse wraps data in a 'data' field
                const responseData = data.data || data;
                this.setToken(responseData.token);
                this.setUser(responseData.user);
                return { success: true, user: responseData.user, token: responseData.token };
            } else {
                return { success: false, message: data.message };
            }
        } catch (error) {
            console.error('Login error:', error);
            return { success: false, message: 'Network error. Please try again.' };
        }
    }

    // Logout
    async logout() {
        const token = this.getToken();
        
        if (token) {
            try {
                await fetch(`${this.apiBase}/auth/logout`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    }
                });
            } catch (error) {
                console.error('Logout error:', error);
            }
        }
        
        this.removeToken();
    }

    // Register (requires OTP)
    async register(fname, lname, username, email, password, otp) {
        try {
            const hashedPassword = this.hashPassword(password);
            const response = await fetch(`${this.apiBase}/auth/register`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ fname, lname, username, email, password: hashedPassword, otp })
            });

            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Register error:', error);
            return { success: false, message: 'Network error. Please try again.' };
        }
    }

    // Verify token
    async verifyToken() {
        const token = this.getToken();
        
        if (!token) {
            return { success: false, message: 'No token found' };
        }

        try {
            const verifyUrl = `${this.apiBase}/auth/verify`;
            console.log('=== Token Verification Debug ===');
            console.log('API Base URL:', this.apiBase);
            console.log('Full Verify URL:', verifyUrl);
            console.log('Current page:', window.location.href);
            
            const response = await fetch(verifyUrl, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });

            console.log('Response status:', response.status);
            console.log('Response URL (after redirects):', response.url);

            if (!response.ok) {
                const errorText = await response.text().catch(() => 'Could not read error response');
                console.error(`API Error: ${response.status} ${response.statusText}`);
                console.error('Error response body:', errorText.substring(0, 500));
                console.error('Requested URL was:', verifyUrl);
                this.removeToken();
                return { success: false, message: `API Error: ${response.status} ${response.statusText}. Check console for URL details.` };
            }

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Non-JSON response:', text.substring(0, 200));
                this.removeToken();
                return { success: false, message: 'Invalid response from server' };
            }

            const data = await response.json();

            if (data.success) {
                // jsonResponse wraps data in a 'data' field
                const responseData = data.data || data;
                this.setUser(responseData.user);
                return { success: true, user: responseData.user };
            } else {
                this.removeToken();
                return { success: false, message: data.message };
            }
        } catch (error) {
            console.error('Token verification error:', error);
            console.error('API Base URL was:', this.apiBase);
            this.removeToken();
            return { success: false, message: 'Token verification failed: ' + error.message };
        }
    }

    // Make authenticated API call
    async apiCall(endpoint, options = {}) {
        const token = this.getToken();
        
        if (!token) {
            throw new Error('No authentication token found');
        }

                const isFormData = options.body instanceof FormData;
        
        const defaultOptions = {
            headers: {
                'Authorization': `Bearer ${token}`,
                ...(isFormData ? {} : { 'Content-Type': 'application/json' }),
                ...options.headers
            }
        };

        // Remove leading slash from endpoint if present to avoid double slashes
        const cleanEndpoint = endpoint.startsWith('/') ? endpoint.substring(1) : endpoint;
                const apiBase = this.apiBase.endsWith('/') ? this.apiBase.slice(0, -1) : this.apiBase;
        const url = `${apiBase}/${cleanEndpoint}`;
        
        console.log('API Call - apiBase:', apiBase, 'endpoint:', cleanEndpoint, 'full URL:', url);

        const response = await fetch(url, {
            ...options,
            headers: defaultOptions.headers
        });

        return response;
    }
}

// Create global auth instance
const auth = new Auth();


