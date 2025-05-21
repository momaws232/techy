<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

try {
    echo "Creating system requirements table...\n";
    
    // Check if the table already exists
    $stmt = $conn->query("SHOW TABLES LIKE 'system_requirements'");
    
    if ($stmt->rowCount() == 0) {
        // Table doesn't exist, so create it
        $sql = "CREATE TABLE system_requirements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            category VARCHAR(100) NOT NULL,
            min_cpu VARCHAR(255) NOT NULL,
            rec_cpu VARCHAR(255) NOT NULL,
            min_gpu VARCHAR(255) NOT NULL,
            rec_gpu VARCHAR(255) NOT NULL,
            min_ram INT NOT NULL,
            rec_ram INT NOT NULL,
            min_storage INT NOT NULL,
            rec_storage INT NOT NULL,
            os VARCHAR(255) NOT NULL,
            additional_notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $conn->exec($sql);
        
        // Insert some sample data
        $sampleData = [
            [
                'Cyberpunk 2077', 'Game', 
                'Intel Core i5-3570K or AMD FX-8310', 'Intel Core i7-4790 or AMD Ryzen 3 3200G',
                'NVIDIA GTX 780 or AMD Radeon RX 470', 'NVIDIA RTX 2060 or AMD Radeon RX 5700 XT',
                8, 12, 70, 70, 'Windows 10 64-bit',
                'DirectX 12 compatible system required'
            ],
            [
                'Adobe Premiere Pro 2023', 'Software', 
                'Intel 6th Gen or AMD equivalent', 'Intel 7th Gen or AMD equivalent',
                'NVIDIA GTX 1050 or AMD Radeon RX 560', 'NVIDIA RTX 2060 or AMD Radeon Pro 5500M',
                8, 16, 8, 16, 'Windows 10 64-bit or macOS Monterey',
                'SSD storage recommended for media cache'
            ],
            [
                'Call of Duty: Modern Warfare', 'Game', 
                'Intel Core i3-4340 or AMD FX-6300', 'Intel Core i5-2500K or AMD Ryzen R5 1600X',
                'NVIDIA GTX 670 or AMD Radeon HD 7950', 'NVIDIA GTX 970 or AMD Radeon R9 390',
                8, 12, 175, 175, 'Windows 10 64-bit',
                'DirectX 12 compatible system with latest drivers'
            ]
        ];
        
        $stmt = $conn->prepare("INSERT INTO system_requirements 
            (name, category, min_cpu, rec_cpu, min_gpu, rec_gpu, min_ram, rec_ram, min_storage, rec_storage, os, additional_notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($sampleData as $data) {
            $stmt->execute($data);
        }
        
        echo "Created system_requirements table and inserted sample data successfully.\n";
    } else {
        echo "The system_requirements table already exists.\n";
    }
    
    // Check if the user_pc_specs table already exists
    $stmt = $conn->query("SHOW TABLES LIKE 'user_pc_specs'");
    
    if ($stmt->rowCount() == 0) {
        // Table doesn't exist, so create it
        $sql = "CREATE TABLE user_pc_specs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            cpu VARCHAR(255) NOT NULL,
            gpu VARCHAR(255) NOT NULL,
            ram INT NOT NULL,
            storage INT NOT NULL,
            os VARCHAR(255) NOT NULL,
            additional_info TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        $conn->exec($sql);
        echo "Created user_pc_specs table successfully.\n";
    } else {
        echo "The user_pc_specs table already exists.\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 