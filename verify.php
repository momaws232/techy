<?php
require_once 'config/database.php';
require_once 'config/functions.php';

$pageTitle = 'Verify Account';
$error = '';
$success = '';

// Process verification token
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = clean_input($_GET['token']);
    
    try {
        // Check if token exists and is valid
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE verification_token = ? AND status = 'pending'");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Update user status to active and clear the token
            $stmt = $conn->prepare("UPDATE users SET status = 'active', verification_token = NULL WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            $success = 'Your account has been verified! You can now log in.';
        } else {
            $error = 'Invalid or expired verification token.';
        }
    } catch (PDOException $e) {
        $error = 'Verification failed: ' . $e->getMessage();
    }
} else {
    $error = 'No verification token provided.';
}

include 'templates/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h2 class="mb-0">Account Verification</h2>
                </div>
                <div class="card-body text-center">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                        <i class="fas fa-check-circle text-success" style="font-size: 4rem; margin: 1rem 0;"></i>
                        <h4>Verification Complete</h4>
                        <p>Your account has been successfully verified.</p>
                        <div class="mt-4">
                            <a href="login.php" class="btn btn-primary">Login to Your Account</a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($success) && empty($error)): ?>
                        <div class="text-center my-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-3">Verifying your account...</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center">
                    <p class="mb-0">
                        <?php if (!empty($error)): ?>
                            <a href="login.php">Return to Login</a>
                        <?php else: ?>
                            <a href="index.php">Return to Homepage</a>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?> 