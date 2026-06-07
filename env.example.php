<?php
/**
 * Example Environment Configuration
 * 
 * INSTRUCTIONS FOR DEPLOYMENT:
 * 1. Copy this file to `env.php`
 * 2. Update the values below with your actual production database credentials
 * 3. Set APP_ENV to 'production' to hide PHP errors and secure the app
 */

return [
    // Application Environment (development / production)
    'APP_ENV' => 'development',

    // Database Configuration
    'DB_HOST' => 'localhost',
    'DB_USER' => 'root',
    'DB_PASS' => '',
    'DB_NAME' => 'amlo_dashboard',
    'DB_PORT' => '3306',
    
    // Application Base URL (optional, useful for absolute links if needed later)
    // 'BASE_URL' => 'https://yourdomain.com/amlo_dashboard',
];
