# Maximus Hotel - Frontend

This is the frontend for Maximus Hotel, deployed on GitHub Pages.

## Setup

1. Update `js/config.js` with your backend API URL:
   ```javascript
   API_BASE_URL: 'https://hotelmaximus.bytevortexz.com/api'
   ```

2. Update all HTML files to include `config.js` before `auth.js` and `admin-api.js`:
   ```html
   <script src="/js/config.js"></script>
   <script src="/js/auth.js"></script>
   ```

3. Push to GitHub and enable GitHub Pages in repository settings.

## Deployment

1. Initialize git repository:
   ```bash
   git init
   git add .
   git commit -m "Initial commit"
   ```

2. Add remote and push:
   ```bash
   git remote add origin https://github.com/David-Eya/MaximusHotel.git
   git branch -M main
   git push -u origin main
   ```

3. Enable GitHub Pages in repository Settings → Pages
