<?php
require_once 'config/database.php';
require_once 'config/functions.php';

$pageTitle = 'Login';
$error = '';
$success = '';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean_input($_POST['username']);
    $password = $_POST['password']; // Will be verified with password_verify
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            // Fetch user from database
            $stmt = $conn->prepare("SELECT id, username, email, password, role, status FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Check user status
                if ($user['status'] === 'banned') {
                    $error = 'Your account has been banned. Please contact the administrator.';
                } elseif ($user['status'] === 'pending') {
                    // Check if verification token exists (indicating email verification is required)
                    $verifyStmt = $conn->prepare("SELECT verification_token FROM users WHERE id = ?");
                    $verifyStmt->execute([$user['id']]);
                    $verificationToken = $verifyStmt->fetchColumn();
                    
                    if ($verificationToken) {
                        $error = 'Your email address has not been verified. Please check your email for a verification link.';
                        // Provide option to resend verification email
                        $success = '<a href="resend-verification.php?user_id=' . $user['id'] . '">Resend verification email</a>';
                    } else {
                        $error = 'Your account is pending approval.';
                    }
                } else {
                    // Update last login time
                    $updateStmt = $conn->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                    $updateStmt->execute([$user['id']]);
                    
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    
                    // Set success message for notification card
                    $_SESSION['login_success'] = true;
                    
                    // Get user's profile image if available
                    $imgStmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
                    $imgStmt->execute([$user['id']]);
                    $_SESSION['profile_image'] = $imgStmt->fetchColumn();
                    
                    // Redirect to homepage
                    header('Location: index.php');
                    exit;
                }
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
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
                    <h2 class="mb-0">Login</h2>
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
                            <label for="username" class="form-label">Username or Email</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <p class="mb-0">Don't have an account? <a href="register.php">Register</a></p>
                    <p class="mb-0 mt-2"><a href="forgot-password.php">Forgot Password?</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
