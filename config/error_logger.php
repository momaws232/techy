<?php
/**
 * Error Logger
 * 
 * Provides functions for logging errors and events
 */

// Define log levels
define('LOG_LEVEL_DEBUG', 10);
define('LOG_LEVEL_INFO', 20);
define('LOG_LEVEL_WARNING', 30);
define('LOG_LEVEL_ERROR', 40);
define('LOG_LEVEL_CRITICAL', 50);

// Set the minimum log level (everything at this level or higher will be logged)
define('LOG_LEVEL_MINIMUM', LOG_LEVEL_INFO);

// Log directory
define('LOG_DIRECTORY', __DIR__ . '/../logs/');

// Ensure log directory exists
if (!is_dir(LOG_DIRECTORY)) {
    mkdir(LOG_DIRECTORY, 0755, true);
}

/**
 * Log a message to a file
 * 
 * @param string $message The message to log
 * @param int $level The log level
 * @param string $category The log category (used for filename)
 * @param array $context Additional context data
 * @return bool Whether logging was successful
 */
function log_message($message, $level = LOG_LEVEL_INFO, $category = 'application', $context = []) {
    // Skip if below minimum log level
    if ($level < LOG_LEVEL_MINIMUM) {
        return false;
    }
    
    // Get level name for display
    $levelNames = [
        LOG_LEVEL_DEBUG => 'DEBUG',
        LOG_LEVEL_INFO => 'INFO',
        LOG_LEVEL_WARNING => 'WARNING',
        LOG_LEVEL_ERROR => 'ERROR',
        LOG_LEVEL_CRITICAL => 'CRITICAL'
    ];
    $levelName = $levelNames[$level] ?? 'UNKNOWN';
    
    // Format timestamp
    $timestamp = date('Y-m-d H:i:s');
    
    // Format message
    $logMessage = "[$timestamp] [$levelName] $message";
    
    // Add context information if available
    if (!empty($context)) {
        $contextData = '';
        foreach ($context as $key => $value) {
            // Serialize value if it's an array or object
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            }
            $contextData .= " $key: $value;";
        }
        $logMessage .= " |$contextData";
    }
    
    // Add line ending
    $logMessage .= PHP_EOL;
    
    // Determine log file
    $logFile = LOG_DIRECTORY . $category . '.log';
    
    // Write to log file
    return error_log($logMessage, 3, $logFile);
}

/**
 * Log an error message
 * 
 * @param string $message The error message
 * @param string $file The file where the error occurred
 * @param int $line The line where the error occurred
 * @param array $context Additional context data
 * @return bool Whether logging was successful
 */
function log_error($message, $file = '', $line = '', $context = []) {
    // Add file and line to context if provided
    if ($file) {
        $context['file'] = $file;
    }
    if ($line) {
        $context['line'] = $line;
    }
    
    return log_message($message, LOG_LEVEL_ERROR, 'error', $context);
}

/**
 * Log a warning message
 * 
 * @param string $message The warning message
 * @param array $context Additional context data
 * @return bool Whether logging was successful
 */
function log_warning($message, $context = []) {
    return log_message($message, LOG_LEVEL_WARNING, 'application', $context);
}

/**
 * Log user activity
 * 
 * @param string $action The action performed (login, logout, post, etc.)
 * @param int $userId The user ID
 * @param array $context Additional context data
 * @return bool Whether logging was successful
 */
function log_user_activity($action, $userId, $context = []) {
    $context['user_id'] = $userId;
    return log_message("User $userId: $action", LOG_LEVEL_INFO, 'user_activity', $context);
}

/**
 * Log a database query error
 * 
 * @param string $query The SQL query
 * @param string $error The error message
 * @param array $params Query parameters
 * @return bool Whether logging was successful
 */
function log_db_error($query, $error, $params = []) {
    $context = [
        'query' => $query,
        'parameters' => $params
    ];
    
    return log_message("Database error: $error", LOG_LEVEL_ERROR, 'database', $context);
}

/**
 * Log a security event
 * 
 * @param string $event The security event description
 * @param array $context Additional context data
 * @return bool Whether logging was successful
 */
function log_security_event($event, $context = []) {
    return log_message($event, LOG_LEVEL_WARNING, 'security', $context);
}

/**
 * Set up custom error handler to log PHP errors
 */
function custom_error_handler($errno, $errstr, $errfile, $errline) {
    $levels = [
        E_ERROR => 'ERROR',
        E_WARNING => 'WARNING',
        E_PARSE => 'PARSE',
        E_NOTICE => 'NOTICE',
        E_CORE_ERROR => 'CORE_ERROR',
        E_CORE_WARNING => 'CORE_WARNING',
        E_COMPILE_ERROR => 'COMPILE_ERROR',
        E_COMPILE_WARNING => 'COMPILE_WARNING',
        E_USER_ERROR => 'USER_ERROR',
        E_USER_WARNING => 'USER_WARNING',
        E_USER_NOTICE => 'USER_NOTICE',
        E_STRICT => 'STRICT',
        E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
        E_DEPRECATED => 'DEPRECATED',
        E_USER_DEPRECATED => 'USER_DEPRECATED'
    ];
    
    $level = $levels[$errno] ?? 'UNKNOWN';
    
    log_error("PHP $level: $errstr", $errfile, $errline);
    
    // Don't execute PHP's internal error handler
    return true;
}

// Set the custom error handler
set_error_handler('custom_error_handler', E_ALL);

/**
 * Set up exception handler
 */
function custom_exception_handler($exception) {
    log_error(
        "Uncaught Exception: " . $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        ['trace' => $exception->getTraceAsString()]
    );
}

// Set the custom exception handler
set_exception_handler('custom_exception_handler'); 