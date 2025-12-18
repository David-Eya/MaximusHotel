# Cordova Setup Guide for Maximus Hotel Frontend

## Prerequisites
1. Install Node.js (https://nodejs.org/)
2. Install Cordova CLI: `npm install -g cordova`
3. Install Android Studio (for Android APK)
4. Set up Android SDK and environment variables

## Step 1: Create Cordova Project

```bash
cd "C:\Users\David Eya\AndroidStudioProjects"
cordova create maximushotel-app com.maximushotel.app "Maximus Hotel"
cd maximushotel-app
```

## Step 2: Add Android Platform

```bash
cordova platform add android
```

## Step 3: Copy Frontend Files

Copy all files from `maximushotel-frontend` folder to `www` folder in your Cordova project:

```bash
# From your current location, copy files
xcopy /E /I "C:\xampp\htdocs\maximushotelLaravel\maximushotel-frontend\*" "C:\Users\David Eya\AndroidStudioProjects\maximushotel-app\www\"
```

## Step 4: Fix Path Issues

The frontend uses absolute paths like `/MaximusHotel/` which won't work in Cordova. You need to:

1. Replace all `/MaximusHotel/` with relative paths like `./` or just remove the prefix
2. Update `index.html` and other HTML files to use relative paths
3. Update CSS and JS file references

## Step 5: Install Required Plugins

```bash
cordova plugin add cordova-plugin-whitelist
cordova plugin add cordova-plugin-network-information
cordova plugin add cordova-plugin-statusbar
cordova plugin add cordova-plugin-splashscreen
```

## Step 6: Configure config.xml

Edit `config.xml` in the root of your Cordova project. **IMPORTANT**: Remove or comment out icon references if you don't have icon files yet:

```xml
<?xml version='1.0' encoding='utf-8'?>
<widget id="com.maximushotel.app" version="1.0.0" xmlns="http://www.w3.org/ns/widgets" xmlns:cdv="http://cordova.apache.org/ns/1.0">
    <name>Maximus Hotel</name>
    <description>Maximus Hotel Booking App</description>
    <author email="dev@maximushotel.com" href="https://maximushotel.com">Maximus Hotel</author>
    <content src="index.html" />
    <access origin="*" />
    <allow-intent href="http://*/*" />
    <allow-intent href="https://*/*" />
    <allow-intent href="tel:*" />
    <allow-intent href="sms:*" />
    <allow-intent href="mailto:*" />
    <allow-intent href="geo:*" />
    
    <platform name="android">
        <allow-intent href="market:*" />
        <!-- Icon removed - will use default Android icon. Add later if needed -->
        <!-- <icon src="www/img/favicon.png" /> -->
    </platform>
    
    <preference name="DisallowOverscroll" value="true" />
    <preference name="android-minSdkVersion" value="22" />
    <preference name="BackupWebStorage" value="none" />
    <!-- Splash screen preferences removed - add later if you have splash screens -->
</widget>
```

**Note**: If you get an error about missing icon files, remove all `<icon>` tags from the `<platform name="android">` section. You can add custom icons later.

## Step 7: Build APK

```bash
# Debug build
cordova build android

# Release build (requires signing)
cordova build android --release
```

The APK will be in: `platforms\android\app\build\outputs\apk\debug\` or `release\`

## Important Notes

1. **Path Changes Required**: All absolute paths (`/MaximusHotel/`) must be changed to relative paths
2. **API Configuration**: The API URL in `config.js` should work as-is since it's a remote server
3. **CORS**: Ensure your backend allows requests from `file://` protocol (Cordova uses file://)
4. **Testing**: Use `cordova run android` to test on connected device or emulator

## Troubleshooting

- If images don't load: Check path references in HTML/CSS
- If API calls fail: Check CORS settings on backend
- If build fails: Ensure Android SDK is properly configured

