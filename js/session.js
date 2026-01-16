/**
 * session.js
 *
 * This script handles client-side session management and page routing.
 *
 * - If a logged-in user visits the login page, they are redirected to their dashboard.
 * - If a logged-out user tries to access a protected dashboard page, they are redirected to the login page.
 * - On authenticated pages, it dispatches a 'session-checked' event for app.js to load user data.
 */

document.addEventListener('DOMContentLoaded', () => {
    // Define application paths dynamically based on current location
    const basePath = document.querySelector('base')?.href?.replace(/\/$/, '') || window.location.origin;
    const loginPagePath = `${basePath}/index.php`;
    const userDashboardPath = `${basePath}/dashboard.php`;
    const adminDashboardPath = `${basePath}/admin/dashboard.php`;

    const currentPath = window.location.pathname;
    const isLoginPage = currentPath.endsWith('/') || currentPath.endsWith('index.php');
    const isProtectedPage = currentPath.endsWith('dashboard.php') || currentPath.includes('/admin/');

    /**
     * Checks the session status with the backend and performs necessary redirects.
     */
    async function checkSessionAndRoute() {
        try {
            const response = await fetch(`${basePath}/api/auth.php?action=check_session`, {
                method: 'POST',
                credentials: 'include', // Important: This tells the browser to send cookies with the request.
                headers: { 'Content-Type': 'application/json' }
            });

            if (!response.ok) {
                console.error('Session check failed:', response.statusText);
                if (isProtectedPage) window.location.href = loginPagePath;
                return;
            }

            const session = await response.json();

            if (session.logged_in) {
                // User is logged in.
                if (isLoginPage) {
                    // If on the login page, redirect to the correct dashboard.
                    const redirectPath = session.user_type === 'admin' ? adminDashboardPath : userDashboardPath;
                    window.location.href = redirectPath;
                } else {
                    // On a protected page, dispatch event for app.js to load data.
                    const event = new CustomEvent('session-checked', { detail: session });
                    document.dispatchEvent(event);
                }
            } else {
                // User is not logged in.
                if (isProtectedPage) {
                    // If on a protected page, redirect to the login page.
                    window.location.href = loginPagePath;
                }
            }

        } catch (error) {
            console.error('Error during session check:', error);
            if (isProtectedPage) window.location.href = loginPagePath;
        }
    }

    checkSessionAndRoute();
});