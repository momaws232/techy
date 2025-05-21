<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if cropped image data was submitted
    if (isset($_POST['cropped_image'])) {
        $croppedImageData = $_POST['cropped_image'];
        
        // Get the base-64 encoded image data
        $imageData = explode(',', $croppedImageData)[1];
        $decodedImage = base64_decode($imageData);
        
        // Create uploads directory if it doesn't exist
        $uploadsDir = 'uploads/profile_images';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }
        
        // Generate unique filename
        $filename = $userId . '_' . time() . '.png';
        $uploadPath = $uploadsDir . '/' . $filename;
        
        // Save the cropped image
        if (file_put_contents($uploadPath, $decodedImage)) {
            try {
                // Update user profile in database
                $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                $stmt->execute([$uploadPath, $userId]);
                
                $success = 'Profile image uploaded and cropped successfully!';
                
                // Redirect back to profile page
                $_SESSION['profile_updated'] = true;
                header('Location: profile.php');
                exit;
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        } else {
            $error = 'Failed to save cropped image. Please try again.';
        }
    }
    // Original file upload handling for fallback
    else if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxFileSize = 2 * 1024 * 1024; // 2MB
        
        $file = $_FILES['profile_image'];
        
        // Validate file type
        if (!in_array($file['type'], $allowedTypes)) {
            $error = 'Invalid file type. Only JPG, PNG and GIF images are allowed.';
        } 
        // Validate file size
        else if ($file['size'] > $maxFileSize) {
            $error = 'File is too large. Maximum size is 2MB.';
        }
        else {
            // Create uploads directory if it doesn't exist
            $uploadsDir = 'uploads/profile_images';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }
            
            // Generate unique filename
            $filename = $userId . '_' . time() . '_' . basename($file['name']);
            $uploadPath = $uploadsDir . '/' . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                try {
                    // Update user profile in database
                    $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                    $stmt->execute([$uploadPath, $userId]);
                    
                    $success = 'Profile image uploaded successfully!';
                    
                    // Redirect back to profile page
                    $_SESSION['profile_updated'] = true;
                    header('Location: profile.php');
                    exit;
                } catch (PDOException $e) {
                    $error = 'Database error: ' . $e->getMessage();
                }
            } else {
                $error = 'Failed to upload file. Please try again.';
            }
        }
    } else {
        $error = 'Please select a file to upload.';
    }
}

// Get page title
$pageTitle = 'Upload Profile Image';
include 'templates/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h1>Upload Profile Image</h1>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                    
                    <div id="upload-container">
                        <form action="" method="post" enctype="multipart/form-data" id="file-form">
                            <div class="mb-3">
                                <label for="profile_image" class="form-label">Select Image</label>
                                <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*" required>
                                <div class="form-text">Max file size: 2MB. Allowed formats: JPG, PNG, GIF</div>
                            </div>
                            
                            <div class="mb-3">
                                <button type="button" id="select-image-btn" class="btn btn-primary">Select Image</button>
                                <a href="profile.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                    
                    <div id="cropper-container" style="display: none;">
                        <div class="mb-3">
                            <div class="img-container" style="max-height: 400px;">
                                <img id="image-to-crop" src="" alt="Picture to crop" style="max-width: 100%;">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <form id="crop-form" action="" method="post">
                                <input type="hidden" name="cropped_image" id="cropped_image">
                                <button type="button" id="crop-btn" class="btn btn-success">Crop & Upload</button>
                                <button type="button" id="cancel-crop-btn" class="btn btn-secondary">Cancel</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Cropper.js library -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const fileInput = document.getElementById('profile_image');
    const selectBtn = document.getElementById('select-image-btn');
    const uploadContainer = document.getElementById('upload-container');
    const cropperContainer = document.getElementById('cropper-container');
    const imageToCrop = document.getElementById('image-to-crop');
    const cropBtn = document.getElementById('crop-btn');
    const cancelCropBtn = document.getElementById('cancel-crop-btn');
    const croppedImageInput = document.getElementById('cropped_image');
    const cropForm = document.getElementById('crop-form');
    
    let cropper;
    
    // Initialize file input trigger
    selectBtn.addEventListener('click', function() {
        fileInput.click();
    });
    
    // File input change handling
    fileInput.addEventListener('change', function(e) {
        if (e.target.files.length) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                // Display the image for cropping
                uploadContainer.style.display = 'none';
                cropperContainer.style.display = 'block';
                imageToCrop.src = e.target.result;
                
                // Initialize cropper
                if (cropper) {
                    cropper.destroy();
                }
                
                cropper = new Cropper(imageToCrop, {
                    aspectRatio: 1, // Square crop for Instagram-like appearance
                    viewMode: 1,    // Restrict the crop box to not exceed the size of the canvas
                    guides: true,
                    center: true,
                    background: false,
                    autoCropArea: 0.8,
                    responsive: true
                });
            };
            
            reader.readAsDataURL(e.target.files[0]);
        }
    });
    
    // Crop button handling
    cropBtn.addEventListener('click', function() {
        if (!cropper) return;
        
        // Get the cropped canvas
        const canvas = cropper.getCroppedCanvas({
            width: 300,  // Set desired width
            height: 300, // Set desired height
            fillColor: '#fff',
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high'
        });
        
        // Convert canvas to base64 string
        const croppedImageData = canvas.toDataURL('image/png');
        croppedImageInput.value = croppedImageData;
        
        // Submit the form with cropped image
        cropForm.submit();
    });
    
    // Cancel button handling
    cancelCropBtn.addEventListener('click', function() {
        cropperContainer.style.display = 'none';
        uploadContainer.style.display = 'block';
        fileInput.value = '';
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
    });
});
</script>

<?php include 'templates/footer.php'; ?>