/**
 * Admin API Client - Handles all admin API calls
 */
class AdminAPI {
    constructor() {
        // Load API base URL from config
        // Check if CONFIG is available (config.js should be loaded before this file)
        if (typeof CONFIG !== 'undefined' && CONFIG.getApiBaseUrl) {
            this.apiBase = CONFIG.getApiBaseUrl();
        } else {
            // Fallback: use relative path for local development
            this.apiBase = '/api';
        }
        
        console.log('Admin API Base URL:', this.apiBase);
    }

    /**
     * Get authorization header with token
     */
    getAuthHeaders() {
        const token = localStorage.getItem('auth_token');
        return {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
        };
    }

    /**
     * Get dashboard stats and recent bookings
     */
    async getDashboard(search = '', limit = 5, page = 1) {
        try {
            const response = await fetch(
                `${this.apiBase}/admin/dashboard?search=${encodeURIComponent(search)}&limit=${limit}&page=${page}`,
                {
                    method: 'GET',
                    headers: this.getAuthHeaders()
                }
            );
            return await response.json();
        } catch (error) {
            console.error('Error fetching dashboard:', error);
            return { success: false, message: 'Failed to fetch dashboard data' };
        }
    }

    /**
     * Get users list (Admin only)
     */
    async getUsers(search = '', limit = 10, page = 1) {
        try {
            const response = await fetch(
                `${this.apiBase}/admin/users?search=${encodeURIComponent(search)}&limit=${limit}&page=${page}`,
                {
                    method: 'GET',
                    headers: this.getAuthHeaders()
                }
            );
            return await response.json();
        } catch (error) {
            console.error('Error fetching users:', error);
            return { success: false, message: 'Failed to fetch users' };
        }
    }

    /**
     * Get client users (for booking - accessible by Admin and Incharge)
     */
    async getClients(search = '', limit = 1000, page = 1) {
        try {
            const response = await fetch(
                `${this.apiBase}/admin/clients?search=${encodeURIComponent(search)}&limit=${limit}&page=${page}`,
                {
                    method: 'GET',
                    headers: this.getAuthHeaders()
                }
            );
            return await response.json();
        } catch (error) {
            console.error('Error fetching clients:', error);
            return { success: false, message: 'Failed to fetch clients' };
        }
    }

    /**
     * Create user
     */
    async createUser(userData) {
        try {
            const response = await fetch(
                `${this.apiBase}/admin/users`,
                {
                    method: 'POST',
                    headers: this.getAuthHeaders(),
                    body: JSON.stringify(userData)
                }
            );
            return await response.json();
        } catch (error) {
            console.error('Error creating user:', error);
            return { success: false, message: 'Failed to create user' };
        }
    }

    /**
     * Update user
     */
    async updateUser(userData) {
        try {
            const userid = userData.userid;
            if (!userid) {
                return { success: false, message: 'User ID is required' };
            }
            
            const response = await fetch(
                `${this.apiBase}/admin/users/${userid}`,
                {
                    method: 'PUT',
                    headers: this.getAuthHeaders(),
                    body: JSON.stringify(userData)
                }
            );
            return await response.json();
        } catch (error) {
            console.error('Error updating user:', error);
            return { success: false, message: 'Failed to update user' };
        }
    }

    /**
     * Delete user
     */
    async deleteUser(userid) {
        try {
            const response = await fetch(
                `${this.apiBase}/admin/users/${userid}`,
                {
                    method: 'DELETE',
                    headers: this.getAuthHeaders()
                }
            );
            return await response.json();
        } catch (error) {
            console.error('Error deleting user:', error);
            return { success: false, message: 'Failed to delete user' };
        }
    }

    /**
     * Get bookings list
     */
    async getBookings(search = '', status = '', limit = 10, page = 1) {
        try {
            const params = new URLSearchParams({
                search: search,
                status: status,
                limit: limit.toString(),
                page: page.toString()
            });
            const response = await fetch(
                `${this.apiBase}/admin/bookings?${params.toString()}`,
                {
                    method: 'GET',
                    headers: this.getAuthHeaders()
                }
            );
            return await response.json();
        } catch (error) {
            console.error('Error fetching bookings:', error);
            return { success: false, message: 'Failed to fetch bookings' };
        }
    }

    /**
     * Update booking status
     */
    /**
     * Create booking (walk-in)
     */
    async createBooking(bookingData) {
        try {
            const response = await fetch(
                `${this.apiBase}/admin/bookings`,
                {
                    method: 'POST',
                    headers: this.getAuthHeaders(),
                    body: JSON.stringify(bookingData)
                }
            );
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('Error response:', errorText);
                return { success: false, message: 'Failed to create booking' };
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                console.error('Expected JSON but got:', contentType);
                return { success: false, message: 'Invalid response format' };
            }
            
            return await response.json();
        } catch (error) {
            console.error('Error creating booking:', error);
            return { success: false, message: 'Failed to create booking' };
        }
    }

    async updateBookingStatus(book_id, status) {
        try {
            const response = await fetch(
                `${this.apiBase}/admin/bookings/${book_id}/status`,
                {
                    method: 'PUT',
                    headers: this.getAuthHeaders(),
                    body: JSON.stringify({ status })
                }
            );
            return await response.json();
        } catch (error) {
            console.error('Error updating booking status:', error);
            return { success: false, message: 'Failed to update booking status' };
        }
    }

    /**
     * Get rooms list
     */
    /**
     * Get next available room ID
     */
    async getNextRoomId() {
        try {
            const response = await fetch(
                `${this.apiBase}/admin/rooms/next-id`,
                {
                    method: 'GET',
                    headers: this.getAuthHeaders()
                }
            );
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('Error response:', errorText);
                return { success: false, message: 'Failed to get next room ID' };
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                console.error('Expected JSON but got:', contentType);
                return { success: false, message: 'Invalid response format' };
            }
            
            return await response.json();
        } catch (error) {
            console.error('Error getting next room ID:', error);
            return { success: false, message: 'Failed to get next room ID' };
        }
    }

    async getRooms(search = '', limit = 10, page = 1, categoryId = '', status = '') {
        try {
            const params = new URLSearchParams({
                search: search,
                limit: limit.toString(),
                page: page.toString()
            });
            
            if (categoryId) {
                params.append('category_id', categoryId);
            }
            
            if (status) {
                params.append('status', status);
            }
            
            const response = await fetch(
                `${this.apiBase}/admin/rooms?${params.toString()}`,
                {
                    method: 'GET',
                    headers: this.getAuthHeaders()
                }
            );
            return await response.json();
        } catch (error) {
            console.error('Error fetching rooms:', error);
            return { success: false, message: 'Failed to fetch rooms' };
        }
    }

    /**
     * Get room categories (for dropdowns)
     */
    async getRoomCategories() {
        try {
            const response = await fetch(
                `${this.apiBase}/admin/room-categories`,
                {
                    method: 'GET',
                    headers: this.getAuthHeaders()
                }
            );
            return await response.json();
        } catch (error) {
            console.error('Error fetching room categories:', error);
            return { success: false, message: 'Failed to fetch room categories' };
        }
    }

    /**
     * Create room
     */
    async createRoom(roomData) {
        try {
            const response = await fetch(
                `${this.apiBase}/admin/rooms`,
                {
                    method: 'POST',
                    headers: this.getAuthHeaders(),
                    body: JSON.stringify(roomData)
                }
            );
            return await response.json();
        } catch (error) {
            console.error('Error creating room:', error);
            return { success: false, message: 'Failed to create room' };
        }
    }

    /**
     * Update room
     */
    async updateRoom(roomData) {
        try {
            const room_id = roomData.original_room_id || roomData.room_id;
            if (!room_id) {
                return { success: false, message: 'Room ID is required' };
            }
            
            const response = await fetch(
                `${this.apiBase}/admin/rooms/${room_id}`,
                {
                    method: 'PUT',
                    headers: this.getAuthHeaders(),
                    body: JSON.stringify(roomData)
                }
            );
            return await response.json();
        } catch (error) {
            console.error('Error updating room:', error);
            return { success: false, message: 'Failed to update room' };
        }
    }

    /**
     * Delete room
     */
    async deleteRoom(room_id) {
        try {
            const response = await fetch(
                `${this.apiBase}/admin/rooms/${room_id}`,
                {
                    method: 'DELETE',
                    headers: this.getAuthHeaders()
                }
            );
            return await response.json();
        } catch (error) {
            console.error('Error deleting room:', error);
            return { success: false, message: 'Failed to delete room' };
        }
    }

    /**
     * Get room categories list (for management)
     */
    async getRoomCategoriesList(search = '', limit = 10, page = 1) {
        try {
            const params = new URLSearchParams({
                search: search,
                limit: limit.toString(),
                page: page.toString()
            });
            const response = await fetch(
                `${this.apiBase}/admin/room-categories?${params.toString()}`,
                {
                    method: 'GET',
                    headers: this.getAuthHeaders()
                }
            );
            return await response.json();
        } catch (error) {
            console.error('Error fetching room categories list:', error);
            return { success: false, message: 'Failed to fetch room categories' };
        }
    }

    /**
     * Create room category (with file upload)
     */
    async createRoomCategory(formData) {
        try {
            const response = await fetch(
                `${this.apiBase}/admin/room-categories`,
                {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                        // Don't set Content-Type for FormData, browser will set it with boundary
                    },
                    body: formData
                }
            );
            
            // Check if response is OK
            if (!response.ok) {
                const errorText = await response.text();
                console.error('Create category error response:', errorText);
                return { success: false, message: `Server error: ${response.status} ${response.statusText}` };
            }
            
            // Check content type
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Non-JSON response:', text);
                return { success: false, message: 'Invalid response from server' };
            }
            
            return await response.json();
        } catch (error) {
            console.error('Error creating room category:', error);
            return { success: false, message: 'Failed to create room category: ' + error.message };
        }
    }

    /**
     * Update room category (with file upload)
     */
    async updateRoomCategory(formData) {
        try {
            const categoryId = formData.get('category_id');
            if (!categoryId) {
                return { success: false, message: 'Category ID is required' };
            }
            
            // Laravel requires _method=PUT for FormData with PUT requests
            formData.append('_method', 'PUT');
            
            const response = await fetch(
                `${this.apiBase}/admin/room-categories/${categoryId}`,
                {
                    method: 'POST', // Use POST with _method=PUT for FormData compatibility
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                        // Don't set Content-Type for FormData, browser will set it with boundary
                    },
                    body: formData
                }
            );
            return await response.json();
        } catch (error) {
            console.error('Error updating room category:', error);
            return { success: false, message: 'Failed to update room category' };
        }
    }

    /**
     * Delete room category
     */
    async deleteRoomCategory(category_id) {
        try {
            const response = await fetch(
                `${this.apiBase}/admin/room-categories/${category_id}`,
                {
                    method: 'DELETE',
                    headers: this.getAuthHeaders()
                }
            );
            return await response.json();
        } catch (error) {
            console.error('Error deleting room category:', error);
            return { success: false, message: 'Failed to delete room category' };
        }
    }
}

// Create global instance
const adminAPI = new AdminAPI();

