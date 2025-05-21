<?php
/**
 * Validator Class
 *
 * Provides methods for sanitizing and validating user input.
 */
class Validator {
    /**
     * Sanitize a string for use as a URL slug
     * 
     * @param string $input The input string
     * @param int $maxLength Maximum length of the slug
     * @return string Sanitized slug
     */
    public static function sanitizeSlug($input, $maxLength = 50) {
        if (empty($input)) return '';
        
        // Convert to lowercase and replace non-alphanumeric with hyphens
        $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $input));
        
        // Remove leading/trailing hyphens and limit length
        $slug = trim($slug, '-');
        
        if (strlen($slug) > $maxLength) {
            $slug = substr($slug, 0, $maxLength);
            // Make sure we don't cut in the middle of a multi-byte character
            $slug = trim($slug, '-');
        }
        
        return $slug;
    }
    
    /**
     * Sanitize text input
     * 
     * @param string $input The input string
     * @return string Sanitized string
     */
    public static function sanitizeText($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate forum data
     * 
     * @param array $data Forum data
     * @return array Array of error messages
     */
    public static function validateForumData($data) {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors[] = 'Forum name is required';
        } elseif (strlen($data['name']) < 3) {
            $errors[] = 'Forum name must be at least 3 characters';
        } elseif (strlen($data['name']) > 100) {
            $errors[] = 'Forum name must be less than 100 characters';
        }
        
        if (empty($data['category'])) {
            $errors[] = 'Category is required';
        } elseif (strlen($data['category']) > 100) {
            $errors[] = 'Category must be less than 100 characters';
        }
        
        if (empty($data['description'])) {
            $errors[] = 'Description is required';
        }
        
        return $errors;
    }
    
    /**
     * Validate user data
     * 
     * @param array $data User data
     * @param bool $isRegistration Whether this is for registration (require password)
     * @return array Array of error messages
     */
    public static function validateUserData($data, $isRegistration = true) {
        $errors = [];
        
        // Username validation
        if (empty($data['username'])) {
            $errors[] = 'Username is required';
        } elseif (strlen($data['username']) < 3) {
            $errors[] = 'Username must be at least 3 characters';
        } elseif (strlen($data['username']) > 30) {
            $errors[] = 'Username must be less than 30 characters';
        } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $data['username'])) {
            $errors[] = 'Username can only contain letters, numbers, underscores and hyphens';
        }
        
        // Email validation
        if (empty($data['email'])) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        // Password validation (only for registration or if password is present)
        if ($isRegistration || !empty($data['password'])) {
            if (empty($data['password'])) {
                $errors[] = 'Password is required';
            } elseif (strlen($data['password']) < 8) {
                $errors[] = 'Password must be at least 8 characters';
            }
            
            if ($isRegistration && isset($data['password_confirm'])) {
                if ($data['password'] !== $data['password_confirm']) {
                    $errors[] = 'Passwords do not match';
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate post data
     * 
     * @param array $data Post data
     * @return array Array of error messages
     */
    public static function validatePostData($data) {
        $errors = [];
        
        if (empty($data['content'])) {
            $errors[] = 'Post content is required';
        } elseif (strlen($data['content']) < 10) {
            $errors[] = 'Post content must be at least 10 characters';
        }
        
        return $errors;
    }
    
    /**
     * Validate topic data
     * 
     * @param array $data Topic data
     * @return array Array of error messages
     */
    public static function validateTopicData($data) {
        $errors = [];
        
        if (empty($data['title'])) {
            $errors[] = 'Topic title is required';
        } elseif (strlen($data['title']) < 5) {
            $errors[] = 'Topic title must be at least 5 characters';
        } elseif (strlen($data['title']) > 200) {
            $errors[] = 'Topic title must be less than 200 characters';
        }
        
        if (empty($data['content'])) {
            $errors[] = 'Topic content is required';
        } elseif (strlen($data['content']) < 10) {
            $errors[] = 'Topic content must be at least 10 characters';
        }
        
        if (empty($data['forum_id'])) {
            $errors[] = 'Forum is required';
        }
        
        return $errors;
    }
    
    /**
     * Check if a string contains profanity (to be used with profanity_filters table)
     * 
     * @param string $content The content to check
     * @param PDO $pdo PDO database connection
     * @return array ['hasProfanity' => bool, 'filteredContent' => string]
     */
    public static function checkProfanity($content, $pdo) {
        $result = [
            'hasProfanity' => false,
            'filteredContent' => $content
        ];
        
        try {
            // Get all profanity filters
            $stmt = $pdo->query("SELECT word, replacement FROM profanity_filters");
            $filters = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            if (!empty($filters)) {
                $words = array_keys($filters);
                $replacements = array_values($filters);
                
                // Check if any profanity words are in the content (case-insensitive)
                $pattern = '/\b(' . implode('|', array_map(function($word) {
                    return preg_quote($word, '/');
                }, $words)) . ')\b/i';
                
                if (preg_match($pattern, $content)) {
                    $result['hasProfanity'] = true;
                }
                
                // Filter the content regardless
                $result['filteredContent'] = preg_replace($pattern, $replacements, $content);
            }
        } catch (PDOException $e) {
            // If there's an error, just return the original content
            // Typically this happens if the profanity_filters table doesn't exist
        }
        
        return $result;
    }
} 