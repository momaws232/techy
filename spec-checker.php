<?php
require_once 'config/database.php';
require_once 'config/functions.php';

$pageTitle = 'System Requirements Checker';
$error = '';
$success = '';

// Get all system requirements
try {
    $stmt = $conn->query("SELECT * FROM system_requirements ORDER BY name ASC");
    $requirements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error loading system requirements: ' . $e->getMessage();
}

// Get user's saved PC configurations if logged in
$userSpecs = [];
if (is_logged_in()) {
    try {
        $stmt = $conn->prepare("SELECT * FROM user_pc_specs WHERE user_id = ? ORDER BY name ASC");
        $stmt->execute([$_SESSION['user_id']]);
        $userSpecs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = 'Error loading your PC configurations: ' . $e->getMessage();
    }
}

// Check if form is submitted for PC spec comparison
$comparisonResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['compare'])) {
    $requirementId = (int)$_POST['requirement_id'];
    $cpu = trim($_POST['cpu']);
    $gpu = trim($_POST['gpu']);
    $ram = (int)$_POST['ram'];
    $storage = (int)$_POST['storage'];
    $os = trim($_POST['os']);
    
    // Simple validation
    if (empty($cpu) || empty($gpu) || $ram <= 0 || $storage <= 0 || empty($os)) {
        $error = 'All fields are required for comparison.';
    } else {
        // Get the selected system requirement
        try {
            $stmt = $conn->prepare("SELECT * FROM system_requirements WHERE id = ?");
            $stmt->execute([$requirementId]);
            $selectedRequirement = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($selectedRequirement) {
                // Perform the comparison
                $comparisonResult = [
                    'requirement' => $selectedRequirement,
                    'user_spec' => [
                        'cpu' => $cpu,
                        'gpu' => $gpu,
                        'ram' => $ram,
                        'storage' => $storage,
                        'os' => $os
                    ],
                    'cpu_min_met' => true, // This would need a real CPU comparison algorithm
                    'cpu_rec_met' => false, // Simplified for demo
                    'gpu_min_met' => true, // This would need a real GPU comparison algorithm
                    'gpu_rec_met' => false, // Simplified for demo
                    'ram_min_met' => $ram >= $selectedRequirement['min_ram'],
                    'ram_rec_met' => $ram >= $selectedRequirement['rec_ram'],
                    'storage_min_met' => $storage >= $selectedRequirement['min_storage'],
                    'storage_rec_met' => $storage >= $selectedRequirement['rec_storage'],
                    'os_met' => strpos(strtolower($selectedRequirement['os']), strtolower($os)) !== false
                ];
                
                // Overall assessment
                $comparisonResult['meets_minimum'] = 
                    $comparisonResult['cpu_min_met'] && 
                    $comparisonResult['gpu_min_met'] && 
                    $comparisonResult['ram_min_met'] && 
                    $comparisonResult['storage_min_met'] && 
                    $comparisonResult['os_met'];
                    
                $comparisonResult['meets_recommended'] = 
                    $comparisonResult['cpu_rec_met'] && 
                    $comparisonResult['gpu_rec_met'] && 
                    $comparisonResult['ram_rec_met'] && 
                    $comparisonResult['storage_rec_met'] && 
                    $comparisonResult['os_met'];
                
                // Save this configuration if user is logged in and requested to save
                if (is_logged_in() && isset($_POST['save_config']) && $_POST['save_config'] == 1) {
                    $configName = !empty($_POST['config_name']) ? trim($_POST['config_name']) : 'My PC';
                    $additionalInfo = !empty($_POST['additional_info']) ? trim($_POST['additional_info']) : '';
                    
                    $stmt = $conn->prepare("
                        INSERT INTO user_pc_specs 
                        (user_id, name, cpu, gpu, ram, storage, os, additional_info) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $_SESSION['user_id'],
                        $configName,
                        $cpu,
                        $gpu,
                        $ram,
                        $storage,
                        $os,
                        $additionalInfo
                    ]);
                    
                    $success = 'Your PC configuration has been saved!';
                    
                    // Refresh user specs
                    $stmt = $conn->prepare("SELECT * FROM user_pc_specs WHERE user_id = ? ORDER BY name ASC");
                    $stmt->execute([$_SESSION['user_id']]);
                    $userSpecs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            } else {
                $error = 'Selected system requirement not found.';
            }
        } catch (PDOException $e) {
            $error = 'Error performing comparison: ' . $e->getMessage();
        }
    }
}

/**
 * Calculate a performance score for a CPU based on its name
 * This is a simplified algorithm - a real implementation would use benchmark data
 */
function calculateCpuScore($cpuName) {
    $cpuLower = strtolower($cpuName);
    $score = 50; // Default middle score
    
    // CPU generation detection (newer = better)
    if (strpos($cpuLower, 'ryzen 9') !== false || strpos($cpuLower, 'i9-1') !== false) {
        $score += 40;
    } else if (strpos($cpuLower, 'ryzen 7') !== false || strpos($cpuLower, 'i7-1') !== false) {
        $score += 30;
    } else if (strpos($cpuLower, 'ryzen 5') !== false || strpos($cpuLower, 'i5-1') !== false) {
        $score += 20;
    } else if (strpos($cpuLower, 'i9') !== false) {
        $score += 35;
    } else if (strpos($cpuLower, 'i7') !== false) {
        $score += 25;
    } else if (strpos($cpuLower, 'i5') !== false) {
        $score += 15;
    } else if (strpos($cpuLower, 'i3') !== false || strpos($cpuLower, 'ryzen 3') !== false) {
        $score -= 10;
    }
    
    // Detect generation for Intel
    if (strpos($cpuLower, '14') !== false && strpos($cpuLower, 'i') !== false) {
        $score += 15;
    } else if (strpos($cpuLower, '13') !== false && strpos($cpuLower, 'i') !== false) {
        $score += 12;
    } else if (strpos($cpuLower, '12') !== false && strpos($cpuLower, 'i') !== false) {
        $score += 10;
    } else if (strpos($cpuLower, '11') !== false && strpos($cpuLower, 'i') !== false) {
        $score += 5;
    } else if (strpos($cpuLower, '10') !== false && strpos($cpuLower, 'i') !== false) {
        $score += 2;
    }
    
    // Detect generation for AMD
    if (strpos($cpuLower, '7') !== false && strpos($cpuLower, 'ryzen') !== false) {
        $score += 15;
    } else if (strpos($cpuLower, '5') !== false && strpos($cpuLower, 'ryzen') !== false) {
        $score += 12;
    } else if (strpos($cpuLower, '3') !== false && strpos($cpuLower, 'ryzen') !== false) {
        $score += 5;
    } else if (strpos($cpuLower, 'threadripper') !== false) {
        $score += 35;
    }
    
    // Detect specific high-end models
    if (strpos($cpuLower, 'x3d') !== false) {
        $score += 15;
    } else if (strpos($cpuLower, 'k') !== false || strpos($cpuLower, 'kf') !== false) {
        $score += 10;
    }
    
    // Cap the score between 0 and 100
    return max(0, min(100, $score));
}

/**
 * Calculate a performance score for a GPU based on its name
 * This is a simplified algorithm - a real implementation would use benchmark data
 */
function calculateGpuScore($gpuName) {
    $gpuLower = strtolower($gpuName);
    $score = 50; // Default middle score
    
    // Basic GPU detection
    if (strpos($gpuLower, 'rtx 40') !== false) {
        $score += 40;
    } else if (strpos($gpuLower, 'rtx 30') !== false || strpos($gpuLower, 'rx 6') !== false) {
        $score += 30;
    } else if (strpos($gpuLower, 'rtx 20') !== false || strpos($gpuLower, 'rx 5') !== false) {
        $score += 20;
    } else if (strpos($gpuLower, 'gtx 16') !== false) {
        $score += 10;
    } else if (strpos($gpuLower, 'gtx 10') !== false || strpos($gpuLower, 'rx 500') !== false) {
        $score += 0;
    } else if (strpos($gpuLower, 'gtx 9') !== false || strpos($gpuLower, 'rx 400') !== false) {
        $score -= 15;
    }
    
    // Detect high-end models
    if (strpos($gpuLower, 'rtx 4090') !== false || strpos($gpuLower, '7900 xtx') !== false) {
        $score += 20;
    } else if (strpos($gpuLower, 'rtx 4080') !== false || strpos($gpuLower, '7900 xt') !== false) {
        $score += 15;
    } else if (strpos($gpuLower, 'ti') !== false && strpos($gpuLower, 'rtx') !== false) {
        $score += 10;
    } else if (strpos($gpuLower, 'xt') !== false && strpos($gpuLower, 'rx') !== false) {
        $score += 8;
    }
    
    // Detect mid/low-end models
    if (strpos($gpuLower, '4060') !== false || strpos($gpuLower, '7600') !== false) {
        $score -= 5;
    } else if (strpos($gpuLower, '3050') !== false || strpos($gpuLower, '6500') !== false) {
        $score -= 15;
    }
    
    // Cap the score between 0 and 100
    return max(0, min(100, $score));
}

/**
 * Estimate the price of a CPU based on its model
 * This is simplified - ideally would use an API for real-time pricing
 */
function getCpuPrice($cpu) {
    $cpuLower = strtolower($cpu);
    
    // Intel CPUs pricing
    if (strpos($cpuLower, 'i9') !== false) {
        if (strpos($cpuLower, '14') !== false) return 589;
        if (strpos($cpuLower, '13') !== false) return 549;
        if (strpos($cpuLower, '12') !== false) return 479;
        if (strpos($cpuLower, '11') !== false) return 439;
        if (strpos($cpuLower, '10') !== false) return 399;
        return 379;
    } elseif (strpos($cpuLower, 'i7') !== false) {
        if (strpos($cpuLower, '14') !== false) return 409;
        if (strpos($cpuLower, '13') !== false) return 389;
        if (strpos($cpuLower, '12') !== false) return 369;
        if (strpos($cpuLower, '11') !== false) return 349;
        if (strpos($cpuLower, '10') !== false) return 329;
        return 299;
    } elseif (strpos($cpuLower, 'i5') !== false) {
        if (strpos($cpuLower, '14') !== false) return 289;
        if (strpos($cpuLower, '13') !== false) return 269;
        if (strpos($cpuLower, '12') !== false) return 249;
        if (strpos($cpuLower, '11') !== false) return 229;
        if (strpos($cpuLower, '10') !== false) return 199;
        return 179;
    } elseif (strpos($cpuLower, 'i3') !== false) {
        if (strpos($cpuLower, '14') !== false || strpos($cpuLower, '13') !== false) return 149;
        if (strpos($cpuLower, '12') !== false || strpos($cpuLower, '11') !== false) return 129;
        if (strpos($cpuLower, '10') !== false) return 109;
        return 89;
    }
    
    // AMD Ryzen CPUs pricing
    if (strpos($cpuLower, 'ryzen 9') !== false) {
        if (strpos($cpuLower, '7') !== false) return 549;
        if (strpos($cpuLower, '5') !== false) return 449;
        if (strpos($cpuLower, '3') !== false) return 389;
        return 349;
    } elseif (strpos($cpuLower, 'ryzen 7') !== false) {
        if (strpos($cpuLower, '7') !== false) return 389;
        if (strpos($cpuLower, '5') !== false) return 329;
        if (strpos($cpuLower, '3') !== false) return 289;
        return 249;
    } elseif (strpos($cpuLower, 'ryzen 5') !== false) {
        if (strpos($cpuLower, '7') !== false) return 229;
        if (strpos($cpuLower, '5') !== false) return 199;
        if (strpos($cpuLower, '3') !== false) return 179;
        return 159;
    } elseif (strpos($cpuLower, 'ryzen 3') !== false) {
        if (strpos($cpuLower, '5') !== false || strpos($cpuLower, '3') !== false) return 129;
        return 99;
    }
    
    return 200; // Default price if no match
}

/**
 * Estimate the price of a GPU based on its model
 * This is simplified - ideally would use an API for real-time pricing
 */
function getGpuPrice($gpu) {
    $gpuLower = strtolower($gpu);
    
    // NVIDIA RTX 40 series pricing
    if (strpos($gpuLower, 'rtx 4090') !== false) return 1599;
    if (strpos($gpuLower, 'rtx 4080 super') !== false) return 999;
    if (strpos($gpuLower, 'rtx 4080') !== false) return 899;
    if (strpos($gpuLower, 'rtx 4070 ti super') !== false) return 799;
    if (strpos($gpuLower, 'rtx 4070 ti') !== false) return 749;
    if (strpos($gpuLower, 'rtx 4070 super') !== false) return 599;
    if (strpos($gpuLower, 'rtx 4070') !== false) return 549;
    if (strpos($gpuLower, 'rtx 4060 ti') !== false) return 399;
    if (strpos($gpuLower, 'rtx 4060') !== false) return 299;
    
    // NVIDIA RTX 30 series pricing
    if (strpos($gpuLower, 'rtx 3090 ti') !== false) return 999;
    if (strpos($gpuLower, 'rtx 3090') !== false) return 799;
    if (strpos($gpuLower, 'rtx 3080 ti') !== false) return 699;
    if (strpos($gpuLower, 'rtx 3080') !== false) return 599;
    if (strpos($gpuLower, 'rtx 3070 ti') !== false) return 499;
    if (strpos($gpuLower, 'rtx 3070') !== false) return 449;
    if (strpos($gpuLower, 'rtx 3060 ti') !== false) return 399;
    if (strpos($gpuLower, 'rtx 3060') !== false) return 299;
    if (strpos($gpuLower, 'rtx 3050') !== false) return 249;
    
    // NVIDIA RTX 20 series pricing
    if (strpos($gpuLower, 'rtx 2080 ti') !== false) return 499;
    if (strpos($gpuLower, 'rtx 2080 super') !== false) return 449;
    if (strpos($gpuLower, 'rtx 2080') !== false) return 399;
    if (strpos($gpuLower, 'rtx 2070 super') !== false) return 349;
    if (strpos($gpuLower, 'rtx 2070') !== false) return 329;
    if (strpos($gpuLower, 'rtx 2060 super') !== false) return 299;
    if (strpos($gpuLower, 'rtx 2060') !== false) return 249;
    
    // NVIDIA GTX 16 series pricing
    if (strpos($gpuLower, 'gtx 1660 ti') !== false) return 219;
    if (strpos($gpuLower, 'gtx 1660 super') !== false) return 199;
    if (strpos($gpuLower, 'gtx 1660') !== false) return 179;
    if (strpos($gpuLower, 'gtx 1650 super') !== false) return 159;
    if (strpos($gpuLower, 'gtx 1650') !== false) return 149;
    
    // AMD RX 7000 series pricing
    if (strpos($gpuLower, 'rx 7900 xtx') !== false) return 999;
    if (strpos($gpuLower, 'rx 7900 xt') !== false) return 799;
    if (strpos($gpuLower, 'rx 7800 xt') !== false) return 499;
    if (strpos($gpuLower, 'rx 7700 xt') !== false) return 449;
    if (strpos($gpuLower, 'rx 7600') !== false) return 269;
    
    // AMD RX 6000 series pricing
    if (strpos($gpuLower, 'rx 6950 xt') !== false) return 699;
    if (strpos($gpuLower, 'rx 6900 xt') !== false) return 599;
    if (strpos($gpuLower, 'rx 6800 xt') !== false) return 499;
    if (strpos($gpuLower, 'rx 6800') !== false) return 429;
    if (strpos($gpuLower, 'rx 6700 xt') !== false) return 379;
    if (strpos($gpuLower, 'rx 6700') !== false) return 329;
    if (strpos($gpuLower, 'rx 6600 xt') !== false) return 299;
    if (strpos($gpuLower, 'rx 6600') !== false) return 249;
    if (strpos($gpuLower, 'rx 6500 xt') !== false) return 179;
    
    return 300; // Default price if no match
}

// Handle loading saved configuration
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['load_config']) && is_logged_in()) {
    $configId = (int)$_GET['load_config'];
    
    try {
        $stmt = $conn->prepare("SELECT * FROM user_pc_specs WHERE id = ? AND user_id = ?");
        $stmt->execute([$configId, $_SESSION['user_id']]);
        $loadedConfig = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$loadedConfig) {
            $error = 'Selected configuration not found or does not belong to you.';
        }
    } catch (PDOException $e) {
        $error = 'Error loading configuration: ' . $e->getMessage();
    }
}

// Handle deleting saved configuration
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete_config']) && is_logged_in()) {
    $configId = (int)$_GET['delete_config'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM user_pc_specs WHERE id = ? AND user_id = ?");
        $stmt->execute([$configId, $_SESSION['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            $success = 'Configuration deleted successfully.';
            
            // Refresh user specs
            $stmt = $conn->prepare("SELECT * FROM user_pc_specs WHERE user_id = ? ORDER BY name ASC");
            $stmt->execute([$_SESSION['user_id']]);
            $userSpecs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error = 'Failed to delete configuration or it does not exist.';
        }
    } catch (PDOException $e) {
        $error = 'Error deleting configuration: ' . $e->getMessage();
    }
}

include 'templates/header.php';
?>

<div class="container mt-4">
    <h1 class="mb-4">Tech Specs Center</h1>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs mb-4" id="specsTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="requirements-tab" data-bs-toggle="tab" data-bs-target="#requirements" type="button" role="tab" aria-controls="requirements" aria-selected="true">
                <i class="fas fa-check-circle me-1"></i> System Requirements Checker
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="analyzer-tab" data-bs-toggle="tab" data-bs-target="#analyzer" type="button" role="tab" aria-controls="analyzer" aria-selected="false">
                <i class="fas fa-chart-bar me-1"></i> PC Specs Analyzer
            </button>
        </li>
    </ul>
    
    <!-- Tab Content -->
    <div class="tab-content" id="specsTabsContent">
        <!-- Requirements Checker Tab (Original Content) -->
        <div class="tab-pane fade show active" id="requirements" role="tabpanel" aria-labelledby="requirements-tab">
            <div class="row"></div>
                <div class="col-lg-8">
                    <!-- Spec Checker Form -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h2>Check Your PC Specs</h2>
                        </div>
                        <div class="card-body">
                            <?php if (empty($requirements)): ?>
                                <div class="alert alert-info">No system requirements available to check against.</div>
                            <?php else: ?>
                                <form method="post" action="">
                                    <div class="mb-3">
                                        <label for="requirement_id" class="form-label">Select Software/Game</label>
                                        <select class="form-select" id="requirement_id" name="requirement_id" required>
                                            <?php foreach ($requirements as $req): ?>
                                                <option value="<?= $req['id'] ?>"><?= htmlspecialchars($req['name']) ?> (<?= htmlspecialchars($req['category']) ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <h4 class="mb-3">Enter Your PC Specs</h4>
                                    
                                    <div class="mb-3">
                                        <label for="cpu" class="form-label">CPU (Processor)</label>
                                        <div class="search-container">
                                            <input type="text" class="form-control search-input" id="cpu" name="cpu" 
                                                value="<?= isset($loadedConfig) ? htmlspecialchars($loadedConfig['cpu']) : '' ?>" required>
                                            <div class="dropdown-list" id="cpuDropdown"></div>
                                        </div>
                                        <div class="form-text">Example: Intel Core i7-10700K or AMD Ryzen 7 5800X</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="gpu" class="form-label">GPU (Graphics Card)</label>
                                        <div class="search-container">
                                            <input type="text" class="form-control search-input" id="gpu" name="gpu" 
                                                value="<?= isset($loadedConfig) ? htmlspecialchars($loadedConfig['gpu']) : '' ?>" required>
                                            <div class="dropdown-list" id="gpuDropdown"></div>
                                        </div>
                                        <div class="form-text">Example: NVIDIA RTX 3070 or AMD Radeon RX 6800</div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="ram" class="form-label">RAM (GB)</label>
                                                <input type="number" class="form-control" id="ram" name="ram" min="1" 
                                                    value="<?= isset($loadedConfig) ? htmlspecialchars($loadedConfig['ram']) : '16' ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="storage" class="form-label">Storage (GB)</label>
                                                <input type="number" class="form-control" id="storage" name="storage" min="1" 
                                                    value="<?= isset($loadedConfig) ? htmlspecialchars($loadedConfig['storage']) : '512' ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="os" class="form-label">Operating System</label>
                                        <input type="text" class="form-control" id="os" name="os" 
                                            value="<?= isset($loadedConfig) ? htmlspecialchars($loadedConfig['os']) : 'Windows 10' ?>" required>
                                        <div class="form-text">Example: Windows 10 64-bit or macOS Monterey</div>
                                    </div>
                                    
                                    <?php if (is_logged_in()): ?>
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="save_config" name="save_config" value="1">
                                            <label class="form-check-label" for="save_config">Save this configuration</label>
                                        </div>
                                        
                                        <div class="mb-3" id="config_name_container" style="display: none;">
                                            <label for="config_name" class="form-label">Configuration Name</label>
                                            <input type="text" class="form-control" id="config_name" name="config_name" 
                                                value="<?= isset($loadedConfig) ? htmlspecialchars($loadedConfig['name']) : 'My PC' ?>">
                                        </div>
                                        
                                        <div class="mb-3" id="additional_info_container" style="display: none;">
                                            <label for="additional_info" class="form-label">Additional Information</label>
                                            <textarea class="form-control" id="additional_info" name="additional_info" rows="2"><?= isset($loadedConfig) ? htmlspecialchars($loadedConfig['additional_info']) : '' ?></textarea>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <button type="submit" name="compare" class="btn btn-primary">Check Compatibility</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Comparison Result -->
                    <?php if ($comparisonResult): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h2>Compatibility Results for <?= htmlspecialchars($comparisonResult['requirement']['name']) ?></h2>
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <h4 class="mb-3">Overall Compatibility</h4>
                                    <?php if ($comparisonResult['meets_recommended']): ?>
                                        <div class="alert alert-success">
                                            <i class="fas fa-check-circle me-2"></i> Your PC meets or exceeds the recommended requirements!
                                        </div>
                                    <?php elseif ($comparisonResult['meets_minimum']): ?>
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle me-2"></i> Your PC meets the minimum requirements but falls short of the recommended specifications.
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-danger">
                                            <i class="fas fa-times-circle me-2"></i> Your PC does not meet the minimum requirements.
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Component</th>
                                            <th>Your PC</th>
                                            <th>Minimum Req.</th>
                                            <th>Recommended Req.</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>CPU</td>
                                            <td><?= htmlspecialchars($comparisonResult['user_spec']['cpu']) ?></td>
                                            <td><?= htmlspecialchars($comparisonResult['requirement']['min_cpu']) ?></td>
                                            <td><?= htmlspecialchars($comparisonResult['requirement']['rec_cpu']) ?></td>
                                            <td>
                                                <?php if ($comparisonResult['cpu_rec_met']): ?>
                                                    <span class="badge bg-success">Exceeds</span>
                                                <?php elseif ($comparisonResult['cpu_min_met']): ?>
                                                    <span class="badge bg-warning">Meets Minimum</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Below Minimum</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>GPU</td>
                                            <td><?= htmlspecialchars($comparisonResult['user_spec']['gpu']) ?></td>
                                            <td><?= htmlspecialchars($comparisonResult['requirement']['min_gpu']) ?></td>
                                            <td><?= htmlspecialchars($comparisonResult['requirement']['rec_gpu']) ?></td>
                                            <td>
                                                <?php if ($comparisonResult['gpu_rec_met']): ?>
                                                    <span class="badge bg-success">Exceeds</span>
                                                <?php elseif ($comparisonResult['gpu_min_met']): ?>
                                                    <span class="badge bg-warning">Meets Minimum</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Below Minimum</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>RAM</td>
                                            <td><?= htmlspecialchars($comparisonResult['user_spec']['ram']) ?> GB</td>
                                            <td><?= htmlspecialchars($comparisonResult['requirement']['min_ram']) ?> GB</td>
                                            <td><?= htmlspecialchars($comparisonResult['requirement']['rec_ram']) ?> GB</td>
                                            <td>
                                                <?php if ($comparisonResult['ram_rec_met']): ?>
                                                    <span class="badge bg-success">Exceeds</span>
                                                <?php elseif ($comparisonResult['ram_min_met']): ?>
                                                    <span class="badge bg-warning">Meets Minimum</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Below Minimum</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Storage</td>
                                            <td><?= htmlspecialchars($comparisonResult['user_spec']['storage']) ?> GB</td>
                                            <td><?= htmlspecialchars($comparisonResult['requirement']['min_storage']) ?> GB</td>
                                            <td><?= htmlspecialchars($comparisonResult['requirement']['rec_storage']) ?> GB</td>
                                            <td>
                                                <?php if ($comparisonResult['storage_rec_met']): ?>
                                                    <span class="badge bg-success">Exceeds</span>
                                                <?php elseif ($comparisonResult['storage_min_met']): ?>
                                                    <span class="badge bg-warning">Meets Minimum</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Below Minimum</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>OS</td>
                                            <td><?= htmlspecialchars($comparisonResult['user_spec']['os']) ?></td>
                                            <td colspan="2"><?= htmlspecialchars($comparisonResult['requirement']['os']) ?></td>
                                            <td>
                                                <?php if ($comparisonResult['os_met']): ?>
                                                    <span class="badge bg-success">Compatible</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Not Compatible</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                
                                <?php if (!empty($comparisonResult['requirement']['additional_notes'])): ?>
                                    <div class="mt-3">
                                        <h5>Additional Notes:</h5>
                                        <p><?= htmlspecialchars($comparisonResult['requirement']['additional_notes']) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-lg-4">
                    <!-- Saved Configurations -->
                    <?php if (is_logged_in()): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h3>Your Saved PC Configurations</h3>
                            </div>
                            <div class="card-body">
                                <?php if (empty($userSpecs)): ?>
                                    <p>You haven't saved any PC configurations yet.</p>
                                <?php else: ?>
                                    <div class="list-group">
                                        <?php foreach ($userSpecs as $spec): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <h5 class="mb-1"><?= htmlspecialchars($spec['name']) ?></h5>
                                                    <div>
                                                        <a href="spec-checker.php?load_config=<?= $spec['id'] ?>" class="btn btn-sm btn-outline-primary">Load</a>
                                                        <a href="spec-checker.php?delete_config=<?= $spec['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this configuration?')">Delete</a>
                                                    </div>
                                                </div>
                                                <p class="mb-1">CPU: <?= htmlspecialchars($spec['cpu']) ?></p>
                                                <p class="mb-1">GPU: <?= htmlspecialchars($spec['gpu']) ?></p>
                                                <p class="mb-1">RAM: <?= htmlspecialchars($spec['ram']) ?> GB</p>
                                                <p class="mb-1">Storage: <?= htmlspecialchars($spec['storage']) ?> GB</p>
                                                <p class="mb-0">OS: <?= htmlspecialchars($spec['os']) ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h3>Save Your PC Configurations</h3>
                            </div>
                            <div class="card-body">
                                <p>Log in to save your PC configurations for future comparisons.</p>
                                <a href="login.php" class="btn btn-primary">Log In</a>
                                <a href="register.php" class="btn btn-outline-primary">Register</a>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- System Requirements List -->
                    <div class="card">
                        <div class="card-header">
                            <h3>Available System Requirements</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($requirements)): ?>
                                <p>No system requirements available.</p>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($requirements as $req): ?>
                                        <div class="list-group-item">
                                            <h5 class="mb-1"><?= htmlspecialchars($req['name']) ?></h5>
                                            <p class="mb-1"><span class="badge bg-info"><?= htmlspecialchars($req['category']) ?></span></p>
                                            <p class="small">Min RAM: <?= $req['min_ram'] ?> GB | Rec RAM: <?= $req['rec_ram'] ?> GB</p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Analyzer Tab -->
        <div class="tab-pane fade" id="analyzer" role="tabpanel" aria-labelledby="analyzer-tab">
            <div class="card">
                <div class="card-header">
                    <h2>PC Specs Analyzer</h2>
                </div>
                <div class="card-body">
                    <p>Input your current PC specifications and get bottleneck analysis and upgrade recommendations.</p>
                    
                    <form id="specsForm" class="mb-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cpu" class="form-label">CPU Model</label>
                                <div class="search-container">
                                    <input type="text" id="cpu" class="form-control search-input" placeholder="Search CPU model..." required>
                                    <div class="dropdown-list" id="cpuDropdown"></div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="gpu" class="form-label">GPU Model</label>
                                <div class="search-container">
                                    <input type="text" id="gpu" class="form-control search-input" placeholder="Search GPU model..." required>
                                    <div class="dropdown-list" id="gpuDropdown"></div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="ram" class="form-label">RAM</label>
                                <select id="ram" class="form-select" required>
                                    <option value="">Select RAM amount</option>
                                    <option value="4">4GB</option>
                                    <option value="8">8GB</option>
                                    <option value="16">16GB</option>
                                    <option value="32">32GB</option>
                                    <option value="64">64GB</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="ramSpeed" class="form-label">RAM Speed</label>
                                <input type="text" id="ramSpeed" class="form-control" placeholder="E.g., 3200MHz">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="storage" class="form-label">Primary Storage Type</label>
                                <select id="storage" class="form-select" required>
                                    <option value="">Select storage type</option>
                                    <option value="hdd">HDD</option>
                                    <option value="sata-ssd">SATA SSD</option>
                                    <option value="nvme">NVMe SSD</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="storageSize" class="form-label">Storage Size</label>
                                <select id="storageSize" class="form-select" required>
                                    <option value="">Select size</option>
                                    <option value="250">250GB</option>
                                    <option value="500">500GB</option>
                                    <option value="1000">1TB</option>
                                    <option value="2000">2TB</option>
                                    <option value="4000">4TB</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="motherboard" class="form-label">Motherboard (Optional)</label>
                                <input type="text" id="motherboard" class="form-control" placeholder="E.g., MSI B550 Tomahawk">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="psu" class="form-label">Power Supply (Optional)</label>
                                <input type="text" id="psu" class="form-control" placeholder="E.g., 650W Gold">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="primaryUse" class="form-label">Primary Use Case</label>
                                <select id="primaryUse" class="form-select" required>
                                    <option value="">Select use case</option>
                                    <option value="gaming">Gaming</option>
                                    <option value="videoEditing">Video Editing</option>
                                    <option value="3dRendering">3D Rendering</option>
                                    <option value="officeWork">Office Work</option>
                                    <option value="streaming">Streaming</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="budget" class="form-label">Upgrade Budget (Optional)</label>
                                <input type="number" id="budget" class="form-control" placeholder="E.g., 500">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Analyze My Specs</button>
                    </form>
                    
                    <div id="results" style="display: none;">
                        <h3>Analysis Results</h3>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h4>System Overview</h4>
                            </div>
                            <div class="card-body" id="systemOverview"></div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h4>Bottleneck Analysis</h4>
                            </div>
                            <div class="card-body" id="bottleneckAnalysis"></div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <h4>Upgrade Recommendations</h4>
                            </div>
                            <div class="card-body" id="upgradeRecommendations"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tabs if they exist
    const specsTabs = document.getElementById('specsTabs');
    if (specsTabs) {
        const tabElements = specsTabs.querySelectorAll('[data-bs-toggle="tab"]');
        tabElements.forEach(tab => {
            tab.addEventListener('shown.bs.tab', function(e) {
                // Reinitialize dropdowns when a tab is shown
                initializeDropdowns();
            });
        });
    }

    // Initialize dropdowns on page load
    initializeDropdowns();
    
    const saveConfigCheckbox = document.getElementById('save_config');
    const configNameContainer = document.getElementById('config_name_container');
    const additionalInfoContainer = document.getElementById('additional_info_container');
    
    if (saveConfigCheckbox) {
        saveConfigCheckbox.addEventListener('change', function() {
            if (this.checked) {
                configNameContainer.style.display = 'block';
                additionalInfoContainer.style.display = 'block';
            } else {
                configNameContainer.style.display = 'none';
                additionalInfoContainer.style.display = 'none';
            }
        });
    }
    
    // PC Specs Analyzer form submission
    const specsForm = document.getElementById('specsForm');
    if (specsForm) {
        specsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            analyzeSpecs();
        });
    }
});

// Comprehensive CPU database
const cpuList = [
    // Intel Core 14th Gen
    'Intel Core i9-14900K', 'Intel Core i9-14900KF', 'Intel Core i9-14900', 
    'Intel Core i7-14700K', 'Intel Core i7-14700KF', 'Intel Core i7-14700',
    'Intel Core i5-14600K', 'Intel Core i5-14600KF', 'Intel Core i5-14500', 'Intel Core i5-14400',
    
    // Intel Core 13th Gen
    'Intel Core i9-13900K', 'Intel Core i9-13900KF', 'Intel Core i9-13900KS', 'Intel Core i9-13900',
    'Intel Core i7-13700K', 'Intel Core i7-13700KF', 'Intel Core i7-13700', 'Intel Core i7-13700F',
    'Intel Core i5-13600K', 'Intel Core i5-13600KF', 'Intel Core i5-13500', 'Intel Core i5-13400',
    'Intel Core i3-13100', 'Intel Core i3-13100F',
    
    // Intel Core 12th Gen
    'Intel Core i9-12900K', 'Intel Core i9-12900KF', 'Intel Core i9-12900KS', 'Intel Core i9-12900',
    'Intel Core i7-12700K', 'Intel Core i7-12700KF', 'Intel Core i7-12700', 'Intel Core i7-12700F',
    'Intel Core i5-12600K', 'Intel Core i5-12600KF', 'Intel Core i5-12500', 'Intel Core i5-12400', 'Intel Core i5-12400F',
    'Intel Core i3-12300', 'Intel Core i3-12100', 'Intel Core i3-12100F',
    
    // Intel Core 11th Gen
    'Intel Core i9-11900K', 'Intel Core i9-11900KF', 'Intel Core i9-11900', 'Intel Core i9-11900F',
    'Intel Core i7-11700K', 'Intel Core i7-11700KF', 'Intel Core i7-11700', 'Intel Core i7-11700F',
    'Intel Core i5-11600K', 'Intel Core i5-11600KF', 'Intel Core i5-11500', 'Intel Core i5-11400', 'Intel Core i5-11400F',
    'Intel Core i3-11300', 'Intel Core i3-11100', 'Intel Core i3-11100F',
    
    // Intel Core 10th Gen
    'Intel Core i9-10900K', 'Intel Core i9-10900KF', 'Intel Core i9-10900', 'Intel Core i9-10900F',
    'Intel Core i7-10700K', 'Intel Core i7-10700KF', 'Intel Core i7-10700', 'Intel Core i7-10700F',
    'Intel Core i5-10600K', 'Intel Core i5-10600KF', 'Intel Core i5-10500', 'Intel Core i5-10400', 'Intel Core i5-10400F',
    'Intel Core i3-10300', 'Intel Core i3-10100', 'Intel Core i3-10100F',
    
    // Intel Core 9th Gen
    'Intel Core i9-9900K', 'Intel Core i9-9900KF', 'Intel Core i9-9900', 'Intel Core i9-9900F',
    'Intel Core i7-9700K', 'Intel Core i7-9700KF', 'Intel Core i7-9700', 'Intel Core i7-9700F',
    'Intel Core i5-9600K', 'Intel Core i5-9600KF', 'Intel Core i5-9500', 'Intel Core i5-9400', 'Intel Core i5-9400F',
    'Intel Core i3-9300', 'Intel Core i3-9100', 'Intel Core i3-9100F',
    
    // Intel Core 8th Gen
    'Intel Core i9-8950HK', 'Intel Core i7-8700K', 'Intel Core i7-8700', 'Intel Core i7-8086K',
    'Intel Core i5-8600K', 'Intel Core i5-8600', 'Intel Core i5-8500', 'Intel Core i5-8400',
    'Intel Core i3-8350K', 'Intel Core i3-8300', 'Intel Core i3-8100',
    
    // Intel Core 7th Gen
    'Intel Core i7-7700K', 'Intel Core i7-7700', 'Intel Core i5-7600K', 'Intel Core i5-7600',
    'Intel Core i5-7500', 'Intel Core i5-7400', 'Intel Core i3-7350K', 'Intel Core i3-7320',
    'Intel Core i3-7300', 'Intel Core i3-7100',
    
    // Intel Core 6th Gen
    'Intel Core i7-6700K', 'Intel Core i7-6700', 'Intel Core i5-6600K', 'Intel Core i5-6600',
    'Intel Core i5-6500', 'Intel Core i5-6400', 'Intel Core i3-6300', 'Intel Core i3-6100',
    
    // AMD Ryzen 7000 Series
    'AMD Ryzen 9 7950X3D', 'AMD Ryzen 9 7950X', 'AMD Ryzen 9 7900X3D', 'AMD Ryzen 9 7900X',
    'AMD Ryzen 9 7900', 'AMD Ryzen 7 7800X3D', 'AMD Ryzen 7 7700X', 'AMD Ryzen 7 7700',
    'AMD Ryzen 5 7600X', 'AMD Ryzen 5 7600', 'AMD Ryzen 5 7500F',
    
    // AMD Ryzen 5000 Series
    'AMD Ryzen 9 5950X', 'AMD Ryzen 9 5900X', 'AMD Ryzen 7 5800X3D', 'AMD Ryzen 7 5800X',
    'AMD Ryzen 7 5800', 'AMD Ryzen 7 5700X', 'AMD Ryzen 7 5700G', 'AMD Ryzen 5 5600X',
    'AMD Ryzen 5 5600G', 'AMD Ryzen 5 5600', 'AMD Ryzen 5 5500',
    
    // AMD Ryzen 3000 Series
    'AMD Ryzen 9 3950X', 'AMD Ryzen 9 3900XT', 'AMD Ryzen 9 3900X', 'AMD Ryzen 7 3800XT',
    'AMD Ryzen 7 3800X', 'AMD Ryzen 7 3700X', 'AMD Ryzen 5 3600XT', 'AMD Ryzen 5 3600X',
    'AMD Ryzen 5 3600', 'AMD Ryzen 5 3500X', 'AMD Ryzen 5 3500', 'AMD Ryzen 3 3300X',
    'AMD Ryzen 3 3100',
    
    // AMD Ryzen 2000 Series
    'AMD Ryzen 7 2700X', 'AMD Ryzen 7 2700', 'AMD Ryzen 5 2600X', 'AMD Ryzen 5 2600',
    'AMD Ryzen 5 2500X', 'AMD Ryzen 5 2500', 'AMD Ryzen 3 2300X', 'AMD Ryzen 3 2300',
    
    // AMD Ryzen 1000 Series
    'AMD Ryzen 7 1800X', 'AMD Ryzen 7 1700X', 'AMD Ryzen 7 1700', 'AMD Ryzen 5 1600X',
    'AMD Ryzen 5 1600', 'AMD Ryzen 5 1500X', 'AMD Ryzen 5 1500', 'AMD Ryzen 3 1300X',
    'AMD Ryzen 3 1200',
    
    // AMD Threadripper
    'AMD Ryzen Threadripper 7980X', 'AMD Ryzen Threadripper 7970X', 'AMD Ryzen Threadripper 7960X',
    'AMD Ryzen Threadripper PRO 5995WX', 'AMD Ryzen Threadripper PRO 5975WX', 'AMD Ryzen Threadripper PRO 5965WX',
    'AMD Ryzen Threadripper 3990X', 'AMD Ryzen Threadripper 3970X', 'AMD Ryzen Threadripper 3960X',
    'AMD Ryzen Threadripper 2990WX', 'AMD Ryzen Threadripper 2970WX', 'AMD Ryzen Threadripper 2950X', 'AMD Ryzen Threadripper 2920X',
    'AMD Ryzen Threadripper 1950X', 'AMD Ryzen Threadripper 1920X', 'AMD Ryzen Threadripper 1900X',
    
    // AMD FX Series
    'AMD FX-9590', 'AMD FX-9370', 'AMD FX-8350', 'AMD FX-8320', 'AMD FX-6350',
    'AMD FX-6300', 'AMD FX-4350', 'AMD FX-4300'
];

// Comprehensive GPU database
const gpuList = [
    // NVIDIA RTX 40 Series
    'NVIDIA GeForce RTX 4090', 'NVIDIA GeForce RTX 4080 Super', 'NVIDIA GeForce RTX 4080',
    'NVIDIA GeForce RTX 4070 Ti Super', 'NVIDIA GeForce RTX 4070 Ti', 'NVIDIA GeForce RTX 4070 Super', 'NVIDIA GeForce RTX 4070',
    'NVIDIA GeForce RTX 4060 Ti', 'NVIDIA GeForce RTX 4060',
    
    // NVIDIA RTX 30 Series
    'NVIDIA GeForce RTX 3090 Ti', 'NVIDIA GeForce RTX 3090', 'NVIDIA GeForce RTX 3080 Ti',
    'NVIDIA GeForce RTX 3080 12GB', 'NVIDIA GeForce RTX 3080', 'NVIDIA GeForce RTX 3070 Ti', 'NVIDIA GeForce RTX 3070',
    'NVIDIA GeForce RTX 3060 Ti', 'NVIDIA GeForce RTX 3060 12GB', 'NVIDIA GeForce RTX 3060', 'NVIDIA GeForce RTX 3050',
    
    // NVIDIA RTX 20 Series
    'NVIDIA GeForce RTX 2080 Ti', 'NVIDIA GeForce RTX 2080 Super', 'NVIDIA GeForce RTX 2080',
    'NVIDIA GeForce RTX 2070 Super', 'NVIDIA GeForce RTX 2070', 'NVIDIA GeForce RTX 2060 Super',
    'NVIDIA GeForce RTX 2060', 'NVIDIA GeForce RTX 2050',
    
    // NVIDIA GTX 16 Series
    'NVIDIA GeForce GTX 1660 Ti', 'NVIDIA GeForce GTX 1660 Super', 'NVIDIA GeForce GTX 1660',
    'NVIDIA GeForce GTX 1650 Super', 'NVIDIA GeForce GTX 1650', 'NVIDIA GeForce GTX 1630',
    
    // NVIDIA GTX 10 Series
    'NVIDIA GeForce GTX 1080 Ti', 'NVIDIA GeForce GTX 1080', 'NVIDIA GeForce GTX 1070 Ti',
    'NVIDIA GeForce GTX 1070', 'NVIDIA GeForce GTX 1060 6GB', 'NVIDIA GeForce GTX 1060 3GB',
    'NVIDIA GeForce GTX 1050 Ti', 'NVIDIA GeForce GTX 1050',
    
    // NVIDIA GTX 900 Series
    'NVIDIA GeForce GTX 980 Ti', 'NVIDIA GeForce GTX 980', 'NVIDIA GeForce GTX 970',
    'NVIDIA GeForce GTX 960', 'NVIDIA GeForce GTX 950',
    
    // AMD Radeon RX 7000 Series
    'AMD Radeon RX 7900 XTX', 'AMD Radeon RX 7900 XT', 'AMD Radeon RX 7800 XT',
    'AMD Radeon RX 7700 XT', 'AMD Radeon RX 7600',
    
    // AMD Radeon RX 6000 Series
    'AMD Radeon RX 6950 XT', 'AMD Radeon RX 6900 XT', 'AMD Radeon RX 6800 XT',
    'AMD Radeon RX 6800', 'AMD Radeon RX 6750 XT', 'AMD Radeon RX 6700 XT', 'AMD Radeon RX 6700',
    'AMD Radeon RX 6650 XT', 'AMD Radeon RX 6600 XT', 'AMD Radeon RX 6600',
    'AMD Radeon RX 6500 XT', 'AMD Radeon RX 6400',
    
    // AMD Radeon RX 5000 Series
    'AMD Radeon RX 5700 XT', 'AMD Radeon RX 5700', 'AMD Radeon RX 5600 XT',
    'AMD Radeon RX 5600', 'AMD Radeon RX 5500 XT', 'AMD Radeon RX 5500',
    
    // AMD Radeon RX 500 Series
    'AMD Radeon RX 590', 'AMD Radeon RX 580 8GB', 'AMD Radeon RX 580 4GB',
    'AMD Radeon RX 570', 'AMD Radeon RX 560', 'AMD Radeon RX 550',
    
    // AMD Radeon RX 400 Series
    'AMD Radeon RX 480', 'AMD Radeon RX 470', 'AMD Radeon RX 460',
    
    // AMD Radeon R9/R7/R5 Series
    'AMD Radeon R9 Fury X', 'AMD Radeon R9 Fury', 'AMD Radeon R9 Nano', 
    'AMD Radeon R9 390X', 'AMD Radeon R9 390', 'AMD Radeon R9 380X', 'AMD Radeon R9 380',
    'AMD Radeon R9 290X', 'AMD Radeon R9 290', 'AMD Radeon R9 285', 'AMD Radeon R9 280X', 'AMD Radeon R9 280',
    'AMD Radeon R7 370', 'AMD Radeon R7 360', 'AMD Radeon R7 260X',
    'AMD Radeon R5 340'
];

// Initialize all dropdown search inputs on the page
function initializeDropdowns() {
    const allCpuInputs = document.querySelectorAll('input[id="cpu"]');
    const allGpuInputs = document.querySelectorAll('input[id="gpu"]');
    
    allCpuInputs.forEach(input => {
        initializeDropdownSearch(input, cpuList);
    });
    
    allGpuInputs.forEach(input => {
        initializeDropdownSearch(input, gpuList);
    });
}

// Initialize dropdown search for a specific input element
function initializeDropdownSearch(input, dataList) {
    if (!input) return;
    
    // Find or create dropdown
    let dropdown = input.parentNode.querySelector('.dropdown-list');
    if (!dropdown) {
        dropdown = document.createElement('div');
        dropdown.className = 'dropdown-list';
        input.parentNode.appendChild(dropdown);
    }
    
    // Clear any existing event listeners
    const newInput = input.cloneNode(true);
    input.parentNode.replaceChild(newInput, input);
    
    // Add event listeners
    newInput.addEventListener('focus', function() {
        if (this.value.length > 0) {
            filterDropdown(this, dataList, dropdown);
        }
    });
    
    newInput.addEventListener('input', function() {
        filterDropdown(this, dataList, dropdown);
    });
    
    newInput.addEventListener('keydown', function(e) {
        handleDropdownKeyNav(e, dropdown);
    });
    
    // Hide dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target !== newInput && !dropdown.contains(e.target)) {
            dropdown.classList.remove('show');
        }
    });
}

// Handle keyboard navigation for dropdowns
function handleDropdownKeyNav(e, dropdown) {
    if (!dropdown.classList.contains('show')) return;
    
    const items = dropdown.querySelectorAll('.dropdown-item');
    if (items.length === 0) return;
    
    const activeItem = dropdown.querySelector('.dropdown-item.active');
    let activeIndex = -1;
    
    // Find the current active item index
    if (activeItem) {
        for (let i = 0; i < items.length; i++) {
            if (items[i] === activeItem) {
                activeIndex = i;
                break;
            }
        }
    }
    
    switch (e.key) {
        case 'ArrowDown':
            e.preventDefault();
            if (activeIndex < items.length - 1) {
                if (activeItem) activeItem.classList.remove('active');
                items[activeIndex + 1].classList.add('active');
                ensureVisible(items[activeIndex + 1], dropdown);
            } else {
                if (activeItem) activeItem.classList.remove('active');
                items[0].classList.add('active');
                ensureVisible(items[0], dropdown);
            }
            break;
            
        case 'ArrowUp':
            e.preventDefault();
            if (activeIndex > 0) {
                if (activeItem) activeItem.classList.remove('active');
                items[activeIndex - 1].classList.add('active');
                ensureVisible(items[activeIndex - 1], dropdown);
            } else {
                if (activeItem) activeItem.classList.remove('active');
                items[items.length - 1].classList.add('active');
                ensureVisible(items[items.length - 1], dropdown);
            }
            break;
            
        case 'Enter':
            if (activeItem) {
                e.preventDefault();
                const input = e.target;
                input.value = activeItem.textContent.replace(/^\s+|\s+$/g, ''); // Remove extra whitespace
                dropdown.classList.remove('show');
            }
            break;
            
        case 'Escape':
            dropdown.classList.remove('show');
            break;
    }
}

// Ensure the active dropdown item is visible by scrolling if necessary
function ensureVisible(element, container) {
    const containerTop = container.scrollTop;
    const containerBottom = containerTop + container.clientHeight;
    
    const elementTop = element.offsetTop;
    const elementBottom = elementTop + element.clientHeight;
    
    if (elementTop < containerTop) {
        container.scrollTop = elementTop;
    } else if (elementBottom > containerBottom) {
        container.scrollTop = elementBottom - container.clientHeight;
    }
}

function analyzeSpecs() {
    // Direct element selection for each input
    const cpuInput = document.querySelector('#analyzer #cpu');
    const gpuInput = document.querySelector('#analyzer #gpu');
    const ramSelect = document.querySelector('#analyzer #ram');
    const ramSpeedInput = document.querySelector('#analyzer #ramSpeed');
    const storageSelect = document.querySelector('#analyzer #storage');
    const storageSizeSelect = document.querySelector('#analyzer #storageSize');
    const primaryUseSelect = document.querySelector('#analyzer #primaryUse');
    const budgetInput = document.querySelector('#analyzer #budget');
    
    // Debug what we found
    console.log("Form elements found:", {
        cpu: cpuInput,
        gpu: gpuInput,
        ram: ramSelect,
        ramSpeed: ramSpeedInput,
        storage: storageSelect,
        storageSize: storageSizeSelect,
        primaryUse: primaryUseSelect,
        budget: budgetInput
    });
    
    // Get values safely with fallbacks
    const cpu = cpuInput && cpuInput.value ? cpuInput.value.trim() : '';
    const gpu = gpuInput && gpuInput.value ? gpuInput.value.trim() : '';
    const ram = ramSelect && ramSelect.value ? parseInt(ramSelect.value) : null;
    const ramSpeed = ramSpeedInput && ramSpeedInput.value ? ramSpeedInput.value.trim() : '';
    const storage = storageSelect && storageSelect.value ? storageSelect.value : '';
    const storageSize = storageSizeSelect && storageSizeSelect.value ? parseInt(storageSizeSelect.value) : null;
    const primaryUse = primaryUseSelect && primaryUseSelect.value ? primaryUseSelect.value : '';
    const budget = budgetInput && budgetInput.value ? budgetInput.value.trim() : '';
    
    // Debug the values
    console.log("Values extracted:", { cpu, gpu, ram, ramSpeed, storage, storageSize, primaryUse, budget });
    
    // Show results section
    document.getElementById('results').style.display = 'block';
    
    // Display system overview with values exactly as entered
    const systemOverview = document.getElementById('systemOverview');
    systemOverview.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <strong>CPU:</strong> ${cpu || '<span class="text-muted">Not specified</span>'}
                </div>
                <div class="mb-3">
                    <strong>GPU:</strong> ${gpu || '<span class="text-muted">Not specified</span>'}
                </div>
                <div class="mb-3">
                    <strong>RAM:</strong> ${ram ? `${ram}GB${ramSpeed ? ` @ ${ramSpeed}` : ''}` : '<span class="text-muted">Not specified</span>'}
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <strong>Storage:</strong> ${storageSize ? `${storageSize}GB ${getStorageTypeText(storage)}` : '<span class="text-muted">Not specified</span>'}
                </div>
                <div class="mb-3">
                    <strong>Primary Use:</strong> ${primaryUse ? getPrimaryUseText(primaryUse) : '<span class="text-muted">Not specified</span>'}
                </div>
                ${budget ? `
                <div class="mb-3">
                    <strong>Upgrade Budget:</strong> $${budget}
                </div>` : ''}
            </div>
        </div>
    `;
    
    // Perform bottleneck analysis
    const bottlenecks = detectBottlenecks(cpu, gpu, ram, ramSpeed, storage, primaryUse);
    
    // Display bottleneck analysis
    const bottleneckAnalysis = document.getElementById('bottleneckAnalysis');
    bottleneckAnalysis.innerHTML = '';
    
    if (bottlenecks.length === 0) {
        bottleneckAnalysis.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i> No significant bottlenecks detected. Your system appears well-balanced for your use case.</div>';
    } else {
        bottlenecks.forEach(bottleneck => {
            const severityClass = bottleneck.severity === 'high' ? 'danger' : 
                               bottleneck.severity === 'medium' ? 'warning' : 'info';
            
            const bottleneckDiv = document.createElement('div');
            bottleneckDiv.className = `alert alert-${severityClass} mb-3`;
            bottleneckDiv.innerHTML = `
                <h5><i class="fas fa-exclamation-triangle me-2"></i> ${bottleneck.component} Bottleneck - ${bottleneck.severity.toUpperCase()}</h5>
                <p class="mb-0">${bottleneck.description}</p>
            `;
            bottleneckAnalysis.appendChild(bottleneckDiv);
        });
    }
    
    // Generate upgrade recommendations
    const recommendations = generateRecommendations(bottlenecks, primaryUse, budget);
    
    // Display upgrade recommendations
    const upgradeRecommendations = document.getElementById('upgradeRecommendations');
    upgradeRecommendations.innerHTML = '';
    
    recommendations.forEach(rec => {
        const recDiv = document.createElement('div');
        recDiv.className = 'card mb-3';
        recDiv.innerHTML = `
            <div class="card-header bg-light">
                <h5 class="mb-0">${rec.title}</h5>
            </div>
            <div class="card-body">
                <p>${rec.description}</p>
                ${rec.examples ? `<p><strong>Suggested options:</strong> ${rec.examples}</p>` : ''}
                ${rec.estimatedCost ? `<p><strong>Estimated cost:</strong> $${rec.estimatedCost}</p>` : ''}
            </div>
        `;
        upgradeRecommendations.appendChild(recDiv);
    });
    
    // Scroll to results
    document.getElementById('results').scrollIntoView({
        behavior: 'smooth'
    });
}

function getStorageTypeText(storage) {
    switch(storage) {
        case 'hdd': return 'Hard Disk Drive';
        case 'sata-ssd': return 'SATA SSD';
        case 'nvme': return 'NVMe SSD';
        default: return storage;
    }
}

function getPrimaryUseText(use) {
    switch(use) {
        case 'gaming': return 'Gaming';
        case 'videoEditing': return 'Video Editing';
        case '3dRendering': return '3D Rendering';
        case 'officeWork': return 'Office Work';
        case 'streaming': return 'Streaming';
        default: return use;
    }
}

function detectBottlenecks(cpu, gpu, ram, ramSpeed, storage, primaryUse) {
    const bottlenecks = [];
    const cpuLower = cpu.toLowerCase();
    const gpuLower = gpu.toLowerCase();
    
    // CPU bottleneck detection
    if ((primaryUse === 'gaming' && (cpuLower.includes('i3') || cpuLower.includes('ryzen 3') || 
        cpuLower.includes('fx') || cpuLower.includes('pentium'))) ||
        (primaryUse === 'videoEditing' && (cpuLower.includes('i3') || cpuLower.includes('i5') || 
        cpuLower.includes('ryzen 3') || cpuLower.includes('ryzen 5')))) {
        bottlenecks.push({
            component: 'CPU',
            severity: 'high',
            description: 'Your CPU may struggle with your primary workload, potentially limiting the performance of other components.'
        });
    } else if (cpuLower.includes('i5') && (gpuLower.includes('rtx 30') || gpuLower.includes('rtx 40') || 
              gpuLower.includes('rx 6') || gpuLower.includes('rx 7'))) {
        bottlenecks.push({
            component: 'CPU',
            severity: 'medium',
            description: 'Your CPU might be limiting the full potential of your high-end GPU in some CPU-intensive games or applications.'
        });
    }
    
    // GPU bottleneck detection
    if ((primaryUse === 'gaming' && (gpuLower.includes('gtx 10') || gpuLower.includes('gtx 9') || 
        gpuLower.includes('rx 5') || gpuLower.includes('rx 4'))) ||
        (primaryUse === '3dRendering' && !gpuLower.includes('rtx') && !gpuLower.includes('rx 6'))) {
        bottlenecks.push({
            component: 'GPU',
            severity: 'medium',
            description: 'Your GPU may be underpowered for your use case, limiting overall system performance in graphics-intensive tasks.'
        });
    }
    
    // RAM bottleneck detection
    if ((primaryUse === 'gaming' && ram < 16) || 
        (primaryUse === 'videoEditing' && ram < 32) || 
        (primaryUse === '3dRendering' && ram < 32)) {
        bottlenecks.push({
            component: 'RAM',
            severity: ram <= 8 ? 'high' : 'medium',
            description: `${ram}GB of RAM is insufficient for ${getPrimaryUseText(primaryUse)}. This can cause significant slowdowns and stuttering.`
        });
    }
    
    // RAM Speed bottleneck for Ryzen systems
    if (ramSpeed && cpuLower.includes('ryzen') && parseInt(ramSpeed) < 3000) {
        bottlenecks.push({
            component: 'RAM Speed',
            severity: 'medium',
            description: 'Ryzen CPUs perform significantly better with faster RAM. Your current RAM speed may be limiting CPU performance.'
        });
    }
    
    // Storage bottleneck detection
    if (primaryUse !== 'officeWork' && storage === 'hdd') {
        bottlenecks.push({
            component: 'Storage',
            severity: 'high',
            description: 'Using an HDD as primary storage results in slow boot times, application loading, and file transfers, creating a significant performance bottleneck.'
        });
    } else if (primaryUse === 'videoEditing' && storage !== 'nvme') {
        bottlenecks.push({
            component: 'Storage',
            severity: 'medium',
            description: 'Video editing benefits greatly from the faster read/write speeds of NVMe storage. Your current storage may be limiting performance when working with large media files.'
        });
    }
    
    return bottlenecks;
}

function generateRecommendations(bottlenecks, primaryUse, budget) {
    const recommendations = [];
    const hasBudget = budget && !isNaN(parseInt(budget));
    const budgetNum = hasBudget ? parseInt(budget) : 0;
    
    // Process each bottleneck
    bottlenecks.forEach(bottleneck => {
        switch(bottleneck.component) {
            case 'CPU':
                if (primaryUse === 'gaming') {
                    recommendations.push({
                        title: 'CPU Upgrade',
                        description: 'A faster CPU would improve your gaming experience, especially for CPU-intensive games.',
                        examples: hasBudget && budgetNum < 300 ? 
                            'AMD Ryzen 5 5600 (<a href="https://www.amazon.com/AMD-5600-12-Thread-Unlocked-Processor/dp/B09VCHR1VH/" target="_blank">link</a>), Intel Core i5-12400F (<a href="https://www.amazon.com/Intel-i5-12400F-Desktop-Processor-Graphics/dp/B09NPJDPVG/" target="_blank">link</a>)' : 
                            'AMD Ryzen 7 5800X3D (<a href="https://www.amazon.com/AMD-Ryzen-5800X3D-16-Thread-Processor/dp/B09VCJ2SHD/" target="_blank">link</a>), Intel Core i7-13700K (<a href="https://www.amazon.com/Intel-i7-13700K-Desktop-Processor-P-cores/dp/B0BCF57FL5/" target="_blank">link</a>)',
                        estimatedCost: hasBudget && budgetNum < 300 ? '180-220' : '320-450'
                    });
                } else if (primaryUse === 'videoEditing' || primaryUse === '3dRendering') {
                    recommendations.push({
                        title: 'CPU Upgrade for Content Creation',
                        description: 'Your current CPU may be limiting your render times and workflow efficiency.',
                        examples: hasBudget && budgetNum < 400 ? 
                            'AMD Ryzen 7 7700X (<a href="https://www.amazon.com/AMD-7700X-Desktop-Processor-Technology/dp/B0BBHHT8LY/" target="_blank">link</a>), Intel Core i7-13700 (<a href="https://www.amazon.com/Intel-i7-13700-Desktop-Processor-P-cores/dp/B0BCF4BBND/" target="_blank">link</a>)' : 
                            'AMD Ryzen 9 7950X (<a href="https://www.amazon.com/AMD-7950X-Desktop-Processor-Technology/dp/B0BBHD5D8Y/" target="_blank">link</a>), Intel Core i9-14900K (<a href="https://www.amazon.com/Intel-i9-14900K-Desktop-Processor-P-cores/dp/B0CF8B96F5/" target="_blank">link</a>)',
                        estimatedCost: hasBudget && budgetNum < 400 ? '340-400' : '550-650'
                    });
                }
                break;
                
            case 'GPU':
                if (primaryUse === 'gaming') {
                    recommendations.push({
                        title: 'GPU Upgrade',
                        description: 'A more powerful GPU would significantly improve your gaming performance and allow for higher graphics settings.',
                        examples: hasBudget && budgetNum < 500 ? 
                            'NVIDIA RTX 4060 (<a href="https://www.amazon.com/MSI-GeForce-RTX-4060-8G/dp/B0C7J664QQ/" target="_blank">link</a>), AMD RX 7600 (<a href="https://www.amazon.com/MSI-RX-7600-MECH-2X/dp/B0C5KNNXDZ/" target="_blank">link</a>)' : 
                            'NVIDIA RTX 4070 (<a href="https://www.amazon.com/MSI-GeForce-RTX-4070-Gaming/dp/B0BZHVQ5QH/" target="_blank">link</a>), AMD RX 7800 XT (<a href="https://www.amazon.com/XFX-Speedster-MERC310-Graphics-RX-78XME-CMDA/dp/B0CHJK9JGF/" target="_blank">link</a>)',
                        estimatedCost: hasBudget && budgetNum < 500 ? '280-330' : '550-650'
                    });
                } else if (primaryUse === '3dRendering' || primaryUse === 'videoEditing') {
                    recommendations.push({
                        title: 'Professional GPU Upgrade',
                        description: 'A more capable GPU would significantly reduce render times and improve viewport performance.',
                        examples: hasBudget && budgetNum < 800 ? 
                            'NVIDIA RTX 4070 (<a href="https://www.amazon.com/MSI-GeForce-RTX-4070-Gaming/dp/B0BZHVQ5QH/" target="_blank">link</a>), AMD RX 7800 XT (<a href="https://www.amazon.com/XFX-Speedster-MERC310-Graphics-RX-78XME-CMDA/dp/B0CHJK9JGF/" target="_blank">link</a>)' : 
                            'NVIDIA RTX 4080 (<a href="https://www.amazon.com/MSI-GeForce-RTX-4080-16G/dp/B0BJFRT43X/" target="_blank">link</a>), NVIDIA RTX 4090 (<a href="https://www.amazon.com/MSI-GeForce-RTX-4090-24G/dp/B0BG92PQ8P/" target="_blank">link</a>)',
                        estimatedCost: hasBudget && budgetNum < 800 ? '550-650' : '1200-1800'
                    });
                }
                break;
                
            case 'RAM':
                if (bottleneck.severity === 'high') {
                    recommendations.push({
                        title: 'Critical RAM Upgrade',
                        description: 'Your system has insufficient RAM for your workload, causing significant performance issues.',
                        examples: primaryUse === 'gaming' ? 
                            'Corsair Vengeance 32GB DDR4 3200MHz (<a href="https://www.amazon.com/Corsair-Vengeance-3200MHz-Desktop-Memory/dp/B07RW6Z692/" target="_blank">link</a>), G.Skill Ripjaws V 16GB DDR4 3600MHz (<a href="https://www.amazon.com/G-Skill-RipJaws-PC4-28800-CL16-19-19-39-F4-3600C16D-16GVKC/dp/B07X8DVDZZ/" target="_blank">link</a>)' : 
                            'Corsair Vengeance 64GB DDR4 3200MHz (<a href="https://www.amazon.com/Corsair-Vengeance-2x32GB-PC4-25600-CMK64GX4M2E3200C16/dp/B08GSBTLB6/" target="_blank">link</a>), Crucial 32GB DDR5 5600MHz (<a href="https://www.amazon.com/Crucial-5600MHz-Desktop-Memory-CT2K16G56C46U5/dp/B0BCBMRFPY/" target="_blank">link</a>)',
                        estimatedCost: primaryUse === 'gaming' ? '60-130' : '160-300'
                    });
                } else {
                    recommendations.push({
                        title: 'RAM Upgrade',
                        description: 'More RAM would improve multitasking and overall system responsiveness.',
                        examples: 'G.Skill Ripjaws 32GB DDR4 3600MHz (<a href="https://www.amazon.com/G-SKILL-Ripjaws-PC4-28800-CL16-19-19-39-F4-3600C16D-32GVKC/dp/B07Z45XB3G/" target="_blank">link</a>), Kingston Fury 32GB DDR5 5200MHz (<a href="https://www.amazon.com/Kingston-5200MHz-Desktop-Memory-KF552C40BBK2-32/dp/B09NCLCDBB/" target="_blank">link</a>)',
                        estimatedCost: '90-160'
                    });
                }
                break;
                
            case 'RAM Speed':
                recommendations.push({
                    title: 'Faster RAM for Ryzen',
                    description: 'AMD Ryzen CPUs benefit significantly from faster RAM. Upgrading to 3600MHz or faster RAM would improve CPU performance.',
                    examples: 'G.Skill Ripjaws V 16GB DDR4 3600MHz CL16 (<a href="https://www.amazon.com/G-Skill-RipJaws-PC4-28800-CL16-19-19-39-F4-3600C16D-16GVKC/dp/B07X8DVDZZ/" target="_blank">link</a>), Crucial Ballistix 32GB DDR4 3600MHz (<a href="https://www.amazon.com/Crucial-Ballistix-Desktop-Gaming-BL2K16G36C16U4B/dp/B083TRRT16/" target="_blank">link</a>)',
                    estimatedCost: '80-160'
                });
                break;
                
            case 'Storage':
                if (bottleneck.severity === 'high') {
                    recommendations.push({
                        title: 'Essential SSD Upgrade',
                        description: 'Replace your HDD with an SSD for dramatically improved system responsiveness and load times.',
                        examples: 'Samsung 970 EVO Plus 1TB NVMe (<a href="https://www.amazon.com/Samsung-970-EVO-Plus-MZ-V7S1T0B/dp/B07MFZY2F2/" target="_blank">link</a>), WD Blue SN570 1TB NVMe (<a href="https://www.amazon.com/Western-Digital-Blue-SN570-Internal/dp/B09HKGGPLR/" target="_blank">link</a>), Crucial MX500 1TB SATA SSD (<a href="https://www.amazon.com/Crucial-MX500-NAND-SATA-Internal/dp/B078211KBB/" target="_blank">link</a>)',
                        estimatedCost: '75-130'
                    });
                } else {
                    recommendations.push({
                        title: 'Storage Upgrade',
                        description: 'An NVMe SSD would significantly improve loading times for your content creation workflows.',
                        examples: 'Samsung 980 Pro 2TB (<a href="https://www.amazon.com/SAMSUNG-PCIe-Internal-Gaming-MZ-V8P2T0B/dp/B08RK2SR23/" target="_blank">link</a>), WD Black SN850X 2TB (<a href="https://www.amazon.com/WD_BLACK-SN850X-Internal-Gaming-Solid/dp/B0B7C9TGDK/" target="_blank">link</a>)',
                        estimatedCost: '150-220'
                    });
                }
                break;
        }
    });
    
    // If no bottlenecks but budget is available, suggest general improvements
    if (bottlenecks.length === 0 && hasBudget) {
        recommendations.push({
            title: "Quality of Life Upgrades",
            description: "Your system is well-balanced, but here are some optional upgrades that could enhance your experience:",
            examples: `A better CPU cooler like the Noctua NH-D15 (<a href="https://www.amazon.com/Noctua-NH-D15-Premium-Cooler-NF-A15/dp/B00L7UZMAK/" target="_blank">link</a>) or ARCTIC Liquid Freezer II 280 (<a href="https://www.amazon.com/ARCTIC-Liquid-Freezer-All-One/dp/B07WNJCVNW/" target="_blank">link</a>), additional storage, or a higher refresh rate monitor.`,
            estimatedCost: "90-250 depending on component"
        });
    }
    
    // Add general advice if no specific recommendations were made
    if (recommendations.length === 0) {
        recommendations.push({
            title: "System Assessment",
            description: "Based on the information provided, your system should handle your workload adequately. For more detailed analysis, consider running benchmarks specific to your use case and comparing with expected performance metrics."
        });
    }
    
    return recommendations;
}

// Function to filter and display dropdown items
function filterDropdown(input, list, dropdown) {
    const filter = input.value.toLowerCase();
    dropdown.innerHTML = '';
    const filteredList = list.filter(item => item.toLowerCase().includes(filter));
    
    // Limit to first 20 results for better performance
    filteredList.slice(0, 20).forEach(item => {
        const div = document.createElement('div');
        div.className = 'dropdown-item';
        if (item.toLowerCase() === input.value.toLowerCase()) {
            div.classList.add('selected');
        }
        
        // Highlight the matching text
        let itemText = item;
        if (filter) {
            const startIndex = item.toLowerCase().indexOf(filter);
            if (startIndex >= 0) {
                const endIndex = startIndex + filter.length;
                itemText = item.substring(0, startIndex) + 
                           '<strong>' + item.substring(startIndex, endIndex) + '</strong>' + 
                           item.substring(endIndex);
            }
        }
        
        div.innerHTML = itemText;
        div.addEventListener('click', () => {
            input.value = item;
            dropdown.classList.remove('show');
        });
        dropdown.appendChild(div);
    });
    
    if (filteredList.length > 0) {
        dropdown.classList.add('show');
        
        // Show count if there are more results than displayed
        if (filteredList.length > 20) {
            const countDiv = document.createElement('div');
            countDiv.className = 'dropdown-count';
            countDiv.textContent = `Showing 20 of ${filteredList.length} results`;
            dropdown.appendChild(countDiv);
        }
    } else {
        dropdown.classList.remove('show');
    }
}
</script>

<style>
/* PC Specs Analyzer Specific Styles */
.search-container {
    position: relative;
    width: 100%;
}
.dropdown-list {
    position: absolute;
    width: 100%;
    max-height: 200px;
    overflow-y: auto;
    background: white;
    border: 1px solid #ddd;
    border-radius: 0 0 4px 4px;
    z-index: 1000;
    display: none;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    top: 100%;
    left: 0;
}
.dropdown-list.show {
    display: block !important;
}
.dropdown-item {
    padding: 10px 12px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
}
.dropdown-item:hover {
    background-color: #f0f0f0;
}
.dropdown-item.selected {
    background-color: #e0f0ff;
}

.dark-theme .dropdown-list {
    background: #333;
    border-color: #555;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
}
.dark-theme .dropdown-item {
    border-bottom: 1px solid #444;
}
.dark-theme .dropdown-item:hover {
    background-color: #444;
}
.dark-theme .dropdown-item.selected {
    background-color: #2c3e50;
}

.dropdown-count {
    padding: 8px 12px;
    text-align: center;
    font-size: 0.8rem;
    color: #777;
    border-top: 1px solid #eee;
    background-color: #f9f9f9;
}

.dark-theme .dropdown-count {
    color: #aaa;
    border-top: 1px solid #444;
    background-color: #333;
}

.dropdown-item strong {
    font-weight: bold;
    color: #0d6efd;
}

.dark-theme .dropdown-item strong {
    color: #6ea8fe;
}

.dropdown-item.active {
    background-color: #e9ecef;
    color: #000;
}

.dark-theme .dropdown-item.active {
    background-color: #495057;
    color: #fff;
}
</style>

<?php include 'templates/footer.php'; ?>