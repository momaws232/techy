<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// Check if session is already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$message = '';
$error = '';

// Get site title from settings
$siteTitle = get_setting($pdo, 'siteTitle');

// Fetch current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $fullname = trim($_POST['fullname'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    try {
        // Begin transaction
        $conn->beginTransaction();

        // Check if bio column exists in users table
        $columnCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'bio'");
        $bioColumnExists = ($columnCheck && $columnCheck->rowCount() > 0);
        
        // Check if fullname column exists in users table
        $nameCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'fullname'");
        $fullnameColumnExists = ($nameCheck && $nameCheck->rowCount() > 0);
        
        // Build SQL query based on existing columns
        $sql = "UPDATE users SET email = ?";
        $params = [$email];
        
        if ($fullnameColumnExists) {
            $sql .= ", fullname = ?";
            $params[] = $fullname;
        }
        
        if ($bioColumnExists) {
            $sql .= ", bio = ?";
            $params[] = $bio;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $userId;
        
        // Update basic info
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        // If user wants to change password
        if (!empty($currentPassword)) {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $storedHash = $stmt->fetchColumn();

            if (password_verify($currentPassword, $storedHash)) {
                if (!empty($newPassword) && $newPassword === $confirmPassword) {
                    // Password requirements check
                    if (strlen($newPassword) >= 8) {
                        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $stmt->execute([$passwordHash, $userId]);
                    } else {
                        throw new Exception("Password must be at least 8 characters long.");
                    }
                } else if (!empty($newPassword)) {
                    throw new Exception("New passwords do not match.");
                }
            } else {
                throw new Exception("Current password is incorrect.");
            }
        }

        // Handle avatar upload
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $maxSize = 2 * 1024 * 1024; // 2MB
            
            if (!in_array($_FILES['avatar']['type'], $allowedTypes)) {
                throw new Exception("Invalid file type. Only JPEG, PNG and GIF are allowed.");
            }
            
            if ($_FILES['avatar']['size'] > $maxSize) {
                throw new Exception("File is too large. Maximum size is 2MB.");
            }
            
            $uploadDir = 'uploads/avatars/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $filename = $userId . '_' . time() . '_' . $_FILES['avatar']['name'];
            $destination = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $destination)) {
                // Check if avatar column exists
                $avatarCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'avatar'");
                $avatarColumnExists = ($avatarCheck && $avatarCheck->rowCount() > 0);
                
                // Check if profile_image column exists (alternative column name)
                $profileImageCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_image'");
                $profileImageColumnExists = ($profileImageCheck && $profileImageCheck->rowCount() > 0);
                
                if ($avatarColumnExists) {
                    $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                    $stmt->execute([$destination, $userId]);
                } else if ($profileImageColumnExists) {
                    $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                    $stmt->execute([$destination, $userId]);
                } else {
                    throw new Exception("Cannot save image: profile image column not found in database.");
                }
            } else {
                throw new Exception("Failed to upload file.");
            }
        }

        $conn->commit();
        $message = "Profile updated successfully!";
        
        // Set the profile update success notification in session
        $_SESSION['profile_updated'] = true;
        
        // Redirect to homepage to show the notification
        header('Location: index.php');
        exit;
        
        // Refresh user data after update
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
    } catch (Exception $e) {
        $conn->rollBack();
        $error = $e->getMessage();
    }
}

include 'templates/header.php';

// Check if bio exists in the database (to display the field or not)
$bioColumnCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'bio'");
$bioExists = ($bioColumnCheck && $bioColumnCheck->rowCount() > 0);

// Check if fullname exists in the database (to display the field or not)
$fullnameColumnCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'fullname'");
$fullnameExists = ($fullnameColumnCheck && $fullnameColumnCheck->rowCount() > 0);

// Check if avatar or profile_image exists
$avatarCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'avatar'");
$avatarExists = ($avatarCheck && $avatarCheck->rowCount() > 0);

$profileImageCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_image'");
$profileImageExists = ($profileImageCheck && $profileImageCheck->rowCount() > 0);

// Determine which image field is available (if any)
$imageFieldExists = ($avatarExists || $profileImageExists);
$imageFieldName = $avatarExists ? 'avatar' : ($profileImageExists ? 'profile_image' : '');
?>

<div class="card">
    <div class="card-header">
        <h2>Edit Profile</h2>
    </div>
    <div class="card-body">
        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                <div class="form-text">Username cannot be changed.</div>
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
            
            <?php if ($fullnameExists): ?>
            <div class="mb-3">
                <label for="fullname" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="fullname" name="fullname" value="<?= htmlspecialchars($user['fullname'] ?? '') ?>">
            </div>
            <?php endif; ?>
            
            <?php if ($bioExists): ?>
            <div class="mb-3">
                <label for="bio" class="form-label">Bio</label>
                <textarea class="form-control" id="bio" name="bio" rows="3"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
            </div>
            <?php endif; ?>
            
            <?php if ($imageFieldExists): ?>
            <div class="mb-3">
                <label for="avatar" class="form-label">Profile Picture</label>
                <?php if (!empty($user[$imageFieldName])): ?>
                    <div class="mb-2">
                        <img src="<?= htmlspecialchars($user[$imageFieldName]) ?>" alt="Current avatar" class="img-thumbnail" style="max-width: 150px">
                    </div>
                <?php endif; ?>
                <input type="file" class="form-control" id="avatar" name="avatar">
                <div class="form-text">Max file size: 2MB. Allowed formats: JPEG, PNG, GIF</div>
            </div>
            <?php endif; ?>
            
            <hr>
            <h4>Change Password</h4>
            <p class="text-muted">Leave blank if you don't want to change your password</p>
            
            <div class="mb-3">
                <label for="current_password" class="form-label">Current Password</label>
                <input type="password" class="form-control" id="current_password" name="current_password">
            </div>
            
            <div class="mb-3">
                <label for="new_password" class="form-label">New Password</label>
                <input type="password" class="form-control" id="new_password" name="new_password">
            </div>
            
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm New Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="profile.php" class="btn btn-secondary me-md-2">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
