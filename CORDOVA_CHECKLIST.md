# Cordova Conversion Checklist

Use this checklist to ensure your frontend is ready for Cordova.

## Pre-Conversion

- [ ] Backup your frontend files
- [ ] Install Node.js and Cordova CLI
- [ ] Install Android Studio and SDK
- [ ] Set up Android environment variables (ANDROID_HOME)

## Path Fixes Required

### HTML Files
- [ ] `index.html` - Fix all `/MaximusHotel/` paths
- [ ] `login.html` - Fix all `/MaximusHotel/` paths
- [ ] `rooms.html` - Fix all `/MaximusHotel/` paths
- [ ] `room-details.html` - Fix all `/MaximusHotel/` paths
- [ ] `profile.html` - Fix all `/MaximusHotel/` paths
- [ ] `myreservation.html` - Fix all `/MaximusHotel/` paths
- [ ] `about-us.html` - Fix all `/MaximusHotel/` paths
- [ ] `contact.html` - Fix all `/MaximusHotel/` paths
- [ ] All files in `layouts/` folder - Fix paths
- [ ] All files in `views/` folder - Fix paths

### CSS Files
- [ ] Check `css/style.css` for absolute paths
- [ ] Check `css/style2.css` for absolute paths
- [ ] Check all CSS files for image references

### JavaScript Files
- [ ] `js/config.js` - Already uses absolute URLs (API) - ✅ Good!
- [ ] `js/auth.js` - Check for any path references
- [ ] `js/main.js` - Check for any path references
- [ ] All other JS files - Review for path issues

## Cordova Setup

- [ ] Create Cordova project
- [ ] Add Android platform
- [ ] Copy all frontend files to `www/` folder
- [ ] Install required plugins (whitelist, network)
- [ ] Configure `config.xml`
- [ ] Set app icon and splash screen (optional)

## Testing

- [ ] Test on Android emulator: `cordova run android`
- [ ] Test on physical device
- [ ] Verify login functionality
- [ ] Verify API calls work
- [ ] Verify images load correctly
- [ ] Verify navigation between pages
- [ ] Test booking/reservation flow
- [ ] Test profile features

## Build

- [ ] Build debug APK: `cordova build android`
- [ ] Test debug APK on device
- [ ] Build release APK: `cordova build android --release`
- [ ] Sign release APK (if needed for Play Store)

## Common Path Patterns to Fix

### Before (Web)
```html
<link rel="stylesheet" href="/MaximusHotel/css/style.css">
<script src="/MaximusHotel/js/main.js"></script>
<img src="/MaximusHotel/img/logo.png">
```

### After (Cordova)
```html
<link rel="stylesheet" href="./css/style.css">
<script src="./js/main.js"></script>
<img src="./img/logo.png">
```

## API Configuration

Your `config.js` is already configured correctly:
```javascript
API_BASE_URL: 'https://hotelmaximus.bytevortexz.com/api'
```
✅ No changes needed - remote URLs work fine in Cordova!

## Backend CORS Settings

Make sure your Laravel backend allows requests from:
- `file://` protocol (Cordova uses this)
- Your app's origin

Update `config/cors.php` in Laravel:
```php
'allowed_origins' => ['*'], // Or specific origins
'allowed_origins_patterns' => [],
'allowed_headers' => ['*'],
'allowed_methods' => ['*'],
```

## Notes

- CDN resources (Bootstrap, Font Awesome, etc.) will work as-is
- Local assets (CSS, JS, images) need relative paths
- API calls to remote servers work fine
- localStorage works the same in Cordova
- Make sure to test on actual devices, not just emulator





