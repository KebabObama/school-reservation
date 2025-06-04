/**
 * Authentication Manager for Token-based Security
 * Handles token storage, validation, and automatic refresh
 */
class AuthManager {
    constructor() {
        this.token = null;
        this.csrfToken = null;
        this.refreshTimer = null;
        this.init();
    }

    /**
     * Initialize the auth manager
     */
    init() {
        this.loadTokenFromStorage();
        this.setupTokenRefresh();
    }

    /**
     * Load token from localStorage
     */
    loadTokenFromStorage() {
        const tokenData = localStorage.getItem('auth_token');
        const csrfToken = localStorage.getItem('csrf_token');
        
        if (tokenData) {
            try {
                const parsed = JSON.parse(tokenData);
                if (parsed.expires_at && new Date(parsed.expires_at) > new Date()) {
                    this.token = parsed.token;
                    this.csrfToken = csrfToken;
                } else {
                    this.clearTokens();
                }
            } catch (e) {
                this.clearTokens();
            }
        }
    }

    /**
     * Save token to localStorage
     */
    saveTokenToStorage(tokenData, csrfToken) {
        localStorage.setItem('auth_token', JSON.stringify(tokenData));
        localStorage.setItem('csrf_token', csrfToken);
        this.token = tokenData.token;
        this.csrfToken = csrfToken;
    }

    /**
     * Clear tokens from storage
     */
    clearTokens() {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('csrf_token');
        this.token = null;
        this.csrfToken = null;
        if (this.refreshTimer) {
            clearTimeout(this.refreshTimer);
            this.refreshTimer = null;
        }
    }

    /**
     * Check if user is authenticated
     */
    isAuthenticated() {
        return this.token !== null;
    }

    /**
     * Get authentication headers for API requests
     */
    getAuthHeaders() {
        const headers = {};
        if (this.token) {
            headers['Authorization'] = `Bearer ${this.token}`;
        }
        if (this.csrfToken) {
            headers['X-CSRF-Token'] = this.csrfToken;
        }
        return headers;
    }

    /**
     * Login with email and password
     */
    async login(email, password) {
        try {
            const response = await fetch('/api/auth/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email, password })
            });

            const data = await response.json();

            if (data.success) {
                this.saveTokenToStorage({
                    token: data.token,
                    expires_at: data.expires_at,
                    expires_in: data.expires_in
                }, data.csrf_token);
                
                this.setupTokenRefresh();
                return { success: true, data };
            } else {
                return { success: false, error: data.error, remaining_attempts: data.remaining_attempts };
            }
        } catch (error) {
            return { success: false, error: 'Network error: ' + error.message };
        }
    }

    /**
     * Logout
     */
    async logout() {
        try {
            if (this.token) {
                await fetch('/api/auth/logout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        ...this.getAuthHeaders()
                    },
                    body: JSON.stringify({ token: this.token })
                });
            }
        } catch (error) {
            console.warn('Logout request failed:', error);
        } finally {
            this.clearTokens();
            // Reload page to clear any cached data
            window.location.reload();
        }
    }

    /**
     * Refresh authentication token
     */
    async refreshToken() {
        if (!this.token) {
            return false;
        }

        try {
            const response = await fetch('/api/auth/refresh.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    ...this.getAuthHeaders()
                }
            });

            const data = await response.json();

            if (data.success) {
                this.saveTokenToStorage({
                    token: data.token,
                    expires_at: data.expires_at,
                    expires_in: data.expires_in
                }, data.csrf_token);
                
                this.setupTokenRefresh();
                return true;
            } else {
                this.clearTokens();
                return false;
            }
        } catch (error) {
            console.error('Token refresh failed:', error);
            this.clearTokens();
            return false;
        }
    }

    /**
     * Setup automatic token refresh
     */
    setupTokenRefresh() {
        if (this.refreshTimer) {
            clearTimeout(this.refreshTimer);
        }

        const tokenData = localStorage.getItem('auth_token');
        if (!tokenData) return;

        try {
            const parsed = JSON.parse(tokenData);
            const expiresAt = new Date(parsed.expires_at);
            const now = new Date();
            const timeUntilExpiry = expiresAt.getTime() - now.getTime();
            
            // Refresh token 5 minutes before expiry
            const refreshTime = Math.max(0, timeUntilExpiry - (5 * 60 * 1000));
            
            this.refreshTimer = setTimeout(() => {
                this.refreshToken();
            }, refreshTime);
        } catch (e) {
            console.error('Error setting up token refresh:', e);
        }
    }

    /**
     * Make authenticated API request
     */
    async apiRequest(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                ...this.getAuthHeaders()
            }
        };

        const mergedOptions = {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...(options.headers || {})
            }
        };

        try {
            const response = await fetch(url, mergedOptions);
            
            // If token is invalid, try to refresh once
            if (response.status === 401 && this.token) {
                const refreshed = await this.refreshToken();
                if (refreshed) {
                    // Retry the request with new token
                    mergedOptions.headers = {
                        ...mergedOptions.headers,
                        ...this.getAuthHeaders()
                    };
                    return await fetch(url, mergedOptions);
                } else {
                    // Refresh failed, redirect to login
                    this.clearTokens();
                    window.location.reload();
                    return response;
                }
            }

            return response;
        } catch (error) {
            console.error('API request failed:', error);
            throw error;
        }
    }
}

// Create global instance
window.authManager = new AuthManager();

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AuthManager;
}
