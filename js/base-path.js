/**
 * Dynamic Base Path Detection for GitHub Pages
 * This script automatically detects the correct base path for assets
 */
(function() {
    // Get the current pathname
    const pathname = window.location.pathname;
    
    // Check if we're on GitHub Pages (pathname contains repository name)
    // For GitHub Pages: https://username.github.io/repository-name/
    const githubPagesMatch = pathname.match(/^\/([^\/]+)\//);
    
    if (githubPagesMatch) {
        // We're on GitHub Pages, set base to repository root
        const repoName = githubPagesMatch[1];
        const base = document.createElement('base');
        base.href = '/' + repoName + '/';
        
        // Insert base tag at the beginning of head (before other elements)
        const head = document.getElementsByTagName('head')[0];
        if (head.firstChild) {
            head.insertBefore(base, head.firstChild);
        } else {
            head.appendChild(base);
        }
    }
    // If not on GitHub Pages (localhost), base tag won't be set
    // and absolute paths will work as normal
})();

