/**
 * API Configuration for Production
 * Update API_BASE_URL with your Hostinger backend URL
 */

const CONFIG = {
    // Update this with your Hostinger backend URL
    API_BASE_URL: 'https://hotelmaximus.bytevortexz.com/api',
    BACKEND_BASE_URL: 'https://hotelmaximus.bytevortexz.com',
    
    getApiBaseUrl: function() {
        return this.API_BASE_URL;
    },
    
    getBackendBaseUrl: function() {
        return this.BACKEND_BASE_URL;
    },
    
    getProfileImageUrl: function(imageName) {
        if (!imageName || imageName.trim() === '' || imageName === 'default.jpg') {
            return `${this.BACKEND_BASE_URL}/profile_img/default.jpg`;
        }
        return `${this.BACKEND_BASE_URL}/profile_img/${imageName}`;
    },
    
    getRoomImageUrl: function(imageName) {
        if (!imageName || imageName.trim() === '' || imageName === 'default.jpg') {
            return `${this.BACKEND_BASE_URL}/img/room/default.jpg`;
        }
        return `${this.BACKEND_BASE_URL}/img/room/${imageName}`;
    }
};
