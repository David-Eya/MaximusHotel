/**
 * Script to fix absolute paths for Cordova
 * Run with: node fix-paths-for-cordova.js
 * 
 * This script replaces absolute paths (/MaximusHotel/) with relative paths (./)
 * to make the frontend compatible with Cordova
 */

const fs = require('fs');
const path = require('path');

const extensions = ['.html', '.css', '.js'];
const basePath = __dirname;

function fixPathsInFile(filePath) {
    try {
        let content = fs.readFileSync(filePath, 'utf8');
        let modified = false;
        
        // Replace /MaximusHotel/ with ./
        const originalContent = content;
        content = content.replace(/\/MaximusHotel\//g, './');
        
        if (content !== originalContent) {
            fs.writeFileSync(filePath, content, 'utf8');
            modified = true;
            console.log(`Fixed: ${path.relative(basePath, filePath)}`);
        }
        
        return modified;
    } catch (error) {
        console.error(`Error processing ${filePath}:`, error.message);
        return false;
    }
}

function processDirectory(dirPath) {
    const files = fs.readdirSync(dirPath);
    
    files.forEach(file => {
        const filePath = path.join(dirPath, file);
        const stat = fs.statSync(filePath);
        
        if (stat.isDirectory()) {
            // Skip node_modules, .git, and other common directories
            if (!['node_modules', '.git', 'platforms', 'plugins', 'www'].includes(file)) {
                processDirectory(filePath);
            }
        } else if (stat.isFile()) {
            const ext = path.extname(file).toLowerCase();
            if (extensions.includes(ext)) {
                fixPathsInFile(filePath);
            }
        }
    });
}

console.log('Starting path fix for Cordova compatibility...');
console.log('Replacing /MaximusHotel/ with ./ in all HTML, CSS, and JS files...\n');

processDirectory(basePath);

console.log('\nDone! All paths have been fixed for Cordova.');
console.log('\nNote: Review the changes and test thoroughly before building the APK.');





