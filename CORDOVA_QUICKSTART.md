# Quick Start: Convert to Cordova APK

## Quick Steps

### 1. Install Cordova (if not installed)
```bash
npm install -g cordova
```

### 2. Create New Cordova Project
```bash
cd "C:\Users\David Eya\AndroidStudioProjects"
cordova create maximushotel-app com.maximushotel.app "Maximus Hotel"
cd maximushotel-app
```

### 3. Add Android Platform
```bash
cordova platform add android
```

### 4. Copy Your Frontend Files
**Option A: Manual Copy**
- Copy all files from `maximushotel-frontend` folder
- Paste into `www` folder of your Cordova project
- Replace existing files if prompted

**Option B: Using Command (from maximushotel-frontend folder)**
```bash
xcopy /E /I /Y * "C:\Users\David Eya\AndroidStudioProjects\maximushotel-app\www\"
```

### 5. Fix Paths (IMPORTANT!)
Your HTML files use absolute paths like `/MaximusHotel/css/style.css` which won't work in Cordova.

**Manual Fix:**
- Open each HTML file
- Find `/MaximusHotel/` and replace with `./` or just remove it
- Example: `/MaximusHotel/css/style.css` → `./css/style.css` or `css/style.css`

**Or use the script:**
```bash
cd "C:\xampp\htdocs\maximushotelLaravel\maximushotel-frontend"
node fix-paths-for-cordova.js
```
Then copy the fixed files to Cordova www folder.

### 6. Update config.xml
Edit `config.xml` in your Cordova project root:

```xml
<widget id="com.maximushotel.app" version="1.0.0">
    <name>Maximus Hotel</name>
    <description>Hotel Booking App</description>
    <content src="index.html" />
    <access origin="*" />
    <allow-intent href="https://*/*" />
    <allow-intent href="http://*/*" />
    
    <platform name="android">
        <allow-intent href="market:*" />
    </platform>
</widget>
```

### 7. Install Required Plugins
```bash
cordova plugin add cordova-plugin-whitelist
cordova plugin add cordova-plugin-network-information
```

### 8. Build APK
```bash
# Debug APK (for testing)
cordova build android

# Release APK (for distribution - requires signing)
cordova build android --release
```

### 9. Find Your APK
After building, find your APK at:
- Debug: `platforms\android\app\build\outputs\apk\debug\app-debug.apk`
- Release: `platforms\android\app\build\outputs\apk\release\app-release-unsigned.apk`

## Important Notes

### Path Issues
- **Before Cordova**: `/MaximusHotel/css/style.css`
- **After Cordova**: `./css/style.css` or `css/style.css`

### API Configuration
Your `config.js` already uses absolute URLs (`https://hotelmaximus.bytevortexz.com/api`), which is perfect for Cordova - no changes needed!

### Testing
```bash
# Run on connected device/emulator
cordova run android

# Or build and install manually
cordova build android
# Then install the APK from platforms\android\app\build\outputs\apk\debug\
```

### Common Issues

1. **Images not loading**: Check that paths are relative, not absolute
2. **API calls failing**: Ensure backend CORS allows requests from `file://` protocol
3. **Build errors**: Make sure Android SDK is installed and ANDROID_HOME is set

## File Structure After Setup

```
maximushotel-app/
├── config.xml          (Cordova config)
├── www/                (Your frontend files go here)
│   ├── index.html
│   ├── login.html
│   ├── css/
│   ├── js/
│   ├── img/
│   └── ...
├── platforms/
│   └── android/        (Android build files)
└── plugins/            (Cordova plugins)
```

## Next Steps

1. Test the app: `cordova run android`
2. Fix any remaining path issues
3. Test all features (login, booking, etc.)
4. Build release APK when ready
5. Sign the APK for Google Play Store (if needed)





