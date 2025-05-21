<?php
require_once 'config/database.php';
require_once 'config/functions.php';

$pageTitle = 'Register';
$error = '';
$success = '';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

// Check if email verification is required
$requireEmailVerification = get_setting($conn, 'requireEmailVerification') === 'yes';

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean_input($_POST['username']);
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            // Check if username or email already exists
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            $exists = $stmt->fetchColumn();
            
            if ($exists) {
                $error = 'Username or email already exists.';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Set status based on email verification requirement
                $status = $requireEmailVerification ? 'pending' : 'active';
                
                // Create verification token if email verification is required
                $verification_token = $requireEmailVerification ? generate_token() : null;
                
                // Insert new user
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, status, verification_token) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$username, $email, $hashed_password, $status, $verification_token]);
                
                if ($requireEmailVerification) {
                    // Send verification email (placeholder - implement actual email sending)
                    $verification_link = "https://" . $_SERVER['HTTP_HOST'] . "/verify.php?token=" . $verification_token;
                    
                    // For demo purposes, we'll just display the link
                    $success = 'Registration successful! Please check your email to verify your account.<br>Verification link (for demo): <a href="' . $verification_link . '">Verify Account</a>';
                    
                    // In a real application, you would send an email with the verification link
                    // send_verification_email($email, $username, $verification_link);
                } else {
                    $success = 'Registration successful! You can now log in.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Registration failed: ' . $e->getMessage();
        }
    }
}

include 'templates/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h2 class="mb-0">Register</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                            <?php if ($requireEmailVerification): ?>
                                <div class="form-text text-info">You'll need to verify your email address before logging in.</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text">Password must be at least 8 characters long.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">I agree to the <a href="terms.php">Terms and Conditions</a></label>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Register</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <p class="mb-0">Already have an account? <a href="login.php">Login</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
