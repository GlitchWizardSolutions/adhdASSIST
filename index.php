<?php
/**
 * ADHD Dashboard - Entry Point (DO NOT USE DIRECTLY)
 * 
 * This file should only be accessed through /public_html/index.php
 * If you're seeing this, access your app through the proper entry point:
 * http://localhost:3000/public_html/adhdASSIST/
 * 
 * DO NOT PUT REDIRECT LOGIC HERE - it causes loops
 * Redirects are only handled at /public_html/index.php
 */

// If this file is accessed directly, something went wrong
http_response_code(403);
echo "Access Denied - Please access the application through the main entry point.";
exit;
