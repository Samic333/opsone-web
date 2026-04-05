<?php
/**
 * Web Authentication Middleware
 * Ensures user is logged in for web routes
 */
class WebAuthMiddleware {
    public function handle(): void {
        if (empty($_SESSION['user'])) {
            redirect('/login');
        }
    }
}
