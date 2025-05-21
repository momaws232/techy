<?php
require_once 'config/database.php';
require_once 'config/functions.php';

$pageTitle = 'Resend Verification';
$error = '';
$success = '';

// Check if user ID is provided
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_GET['user_id'];

try {
    // Get user information
    $stmt = $conn->prepare("SELECT username, email, status, verification_token FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $error = 'User not found.';
    } elseif ($user['status'] !== 'pending') {
        $error = 'This account is already verified.';
    } else {
        // Generate a new verification token if none exists
        if (empty($user['verification_token'])) {
            $verificationToken = generate_token();
            
            $updateStmt = $conn->prepare("UPDATE users SET verification_token = ? WHERE id = ?");
            $updateStmt->execute([$verificationToken, $userId]);
        } else {
            $verificationToken = $user['verification_token'];
        }
        
        // Generate verification link
        $verificationLink = "https://" . $_SERVER['HTTP_HOST'] . "/verify.php?token=" . $verificationToken;
        
        // For demo purposes, display the link
        $success = 'A new verification email has been sent to ' . htmlspecialchars($user['email']) . '.<br>For demo purposes, here is the verification link: <a href="' . $verificationLink . '">Verify Account</a>';
        
        // In a real application, you would send an email with the verification link
        // send_verification_email($user['email'], $user['username'], $verificationLink);
    }
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

include 'templates/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h2 class="mb-0">Resend Verification Email</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    
                    <div class="text-center mt-3">
                        <p>Please check your email inbox for the verification link.</p>
                        <a href="login.php" class="btn btn-primary">Return to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?> 