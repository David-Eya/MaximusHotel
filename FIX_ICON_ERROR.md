# Fix: Source path does not exist: res/icon/android/hdpi.png

## Quick Fix (Remove Icon References)

Edit your `config.xml` file in the Cordova project root and remove or comment out the icon references:

### Option 1: Remove Icon Lines (Simplest)

Remove these lines from `config.xml`:
```xml
<icon density="ldpi" src="res/icon/android/ldpi.png" />
<icon density="mdpi" src="res/icon/android/mdpi.png" />
<icon density="hdpi" src="res/icon/android/hdpi.png" />
<icon density="xhdpi" src="res/icon/android/xhdpi.png" />
<icon density="xxhdpi" src="res/icon/android/xxhdpi.png" />
<icon density="xxxhdpi" src="res/icon/android/xxxhdpi.png" />
```

### Option 2: Use Default Icon (Recommended)

Replace the icon section with a single default icon:

```xml
<platform name="android">
    <allow-intent href="market:*" />
    <icon src="www/img/favicon.png" />
</platform>
```

### Option 3: Create Minimal config.xml

Here's a minimal `config.xml` that will work:

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
    
    <platform name="android">
        <allow-intent href="market:*" />
        <icon src="www/img/favicon.png" />
    </platform>
    
    <preference name="DisallowOverscroll" value="true" />
    <preference name="android-minSdkVersion" value="22" />
    <preference name="BackupWebStorage" value="none" />
</widget>
```

## Steps to Fix

1. Open `config.xml` in your Cordova project root:
   ```
   C:\Users\David Eya\AndroidStudioProjects\maximushotel-app\config.xml
   ```

2. Remove or replace the icon lines as shown above

3. Save the file

4. Try building again:
   ```bash
   cordova build android
   ```

## Alternative: Create Icon Files Later

You can build without icons now and add them later. The app will use a default Android icon, which is fine for testing.

## After Fixing

Once you fix the config.xml, run:
```bash
cordova build android
```

The build should proceed without the icon error.





