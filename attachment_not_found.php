<?php
require_once 'config/functions.php';

// Get the requested file
$file = isset($_GET['file']) ? $_GET['file'] : '';
$is_admin = is_logged_in() && is_admin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attachment Not Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }
        .error-container {
            margin-top: 100px;
            text-align: center;
        }
        .error-icon {
            font-size: 5rem;
            color: #dc3545;
        }
        .error-file {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin: 20px auto;
            max-width: 80%;
            word-break: break-all;
            border: 1px solid #ced4da;
        }
        .help-section {
            margin-top: 30px;
            text-align: left;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-container">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h1 class="mt-4">Attachment Not Found</h1>
            <p class="lead">The requested attachment cannot be displayed because the file is missing.</p>
            
            <?php if (!empty($file)): ?>
                <div class="error-file">
                    <code><?= htmlspecialchars(basename($file)) ?></code>
                </div>
            <?php endif; ?>
            
            <div class="mt-4">
                <a href="javascript:history.back()" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Go Back
                </a>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-home"></i> Home
                </a>
            </div>
            
            <?php if ($is_admin): ?>
                <div class="help-section card mt-5">
                    <div class="card-header">
                        <h5>Admin Troubleshooting</h5>
                    </div>
                    <div class="card-body">
                        <p>Since you're an administrator, you can use these tools to fix attachment issues:</p>
                        <ol>
                            <li>Use the <a href="auto_fix_attachments.php">Auto-Fix Wizard</a> to automatically repair all attachment issues</li>
                            <li>Check for <a href="check_attachment_files.php">missing attachment files</a></li>
                            <li>Use the <a href="copy_attachment_files.php">Copy Files</a> tool to move files between directories</li>
                            <li><a href="debug_attachments.php">Debug all attachments</a> to see detailed information</li>
                        </ol>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
