/**
 * Admin API Client - Handles all admin API calls
 */
class AdminAPI {
    constructor() {
        // Use Laravel API base URL
        this.apiBase = '/api';
        
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
     * Get users list
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
     * Create user
     */
    async createUser(userData) {
        try {
            const response = await fetch(
                `${this.apiBase}/admin.php?action=create_user`,
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
            const response = await fetch(
                `${this.apiBase}/admin.php?action=update_user`,
                {
                    method: 'POST',
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
                `${this.apiBase}/admin.php?action=delete_user&userid=${userid}`,
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
                action: 'bookings',
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
    async updateBookingStatus(book_id, status) {
        try {
            const response = await fetch(
                `${this.apiBase}/admin.php?action=update_booking_status`,
                {
                    method: 'POST',
                    headers: this.getAuthHeaders(),
                    body: JSON.stringify({ book_id, status })
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
    async getRooms(search = '', limit = 10, page = 1, categoryId = '', status = '') {
        try {
            const params = new URLSearchParams({
                action: 'rooms',
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
                `${this.apiBase}/admin/bookings?${params.toString()}`,
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
                `${this.apiBase}/admin.php?action=create_room`,
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
            const response = await fetch(
                `${this.apiBase}/admin.php?action=update_room`,
                {
                    method: 'POST',
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
                `${this.apiBase}/admin.php?action=delete_room&room_id=${encodeURIComponent(room_id)}`,
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
            const response = await fetch(
                `${this.apiBase}/admin.php?action=room_categories_list&search=${encodeURIComponent(search)}&limit=${limit}&page=${page}`,
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
                `${this.apiBase}/admin.php?action=create_room_category`,
                {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                        // Don't set Content-Type for FormData, browser will set it with boundary
                    },
                    body: formData
                }
            );
            return await response.json();
        } catch (error) {
            console.error('Error creating room category:', error);
            return { success: false, message: 'Failed to create room category' };
        }
    }

    /**
     * Update room category (with file upload)
     */
    async updateRoomCategory(formData) {
        try {
            const response = await fetch(
                `${this.apiBase}/admin.php?action=update_room_category`,
                {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                        // Don't set Content-Type for FormData
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
                `${this.apiBase}/admin.php?action=delete_room_category&category_id=${category_id}`,
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

