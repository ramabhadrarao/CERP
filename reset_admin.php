<?php
/**
 * reset_admin.php - Reset admin password
 * Use this if login is not working
 */

require_once 'config/database.php';

echo "🔧 Admin Password Reset Tool\n";
echo "============================\n\n";

try {
    $pdo = get_database_connection();
    
    // Check if admin exists
    $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE username = 'admin' OR email = 'admin@school.edu'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if (!$admin) {
        echo "❌ Admin user not found. Creating new admin...\n";
        
        // Create admin user
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, role_id, first_name, last_name, phone, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $admin_data = [
            'admin',
            'admin@school.edu',
            password_hash('admin123', PASSWORD_DEFAULT),
            1, // Super admin role
            'Super',
            'Administrator',
            '+91-9876543210',
            'active'
        ];
        
        if ($stmt->execute($admin_data)) {
            echo "✅ New admin user created!\n";
            echo "   Username: admin\n";
            echo "   Password: admin123\n";
            echo "   Email: admin@school.edu\n";
        } else {
            echo "❌ Failed to create admin user\n";
        }
        
    } else {
        echo "✅ Admin user found. Resetting password...\n";
        echo "   Current Username: {$admin['username']}\n";
        echo "   Current Email: {$admin['email']}\n";
        
        // Reset password
        $new_password_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, status = 'active' WHERE id = ?");
        
        if ($stmt->execute([$new_password_hash, $admin['id']])) {
            echo "✅ Password reset successfully!\n";
            echo "   New Password: admin123\n";
        } else {
            echo "❌ Failed to reset password\n";
        }
    }
    
    // Verify the reset worked
    echo "\n🧪 Testing new credentials...\n";
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE username = 'admin'");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result && password_verify('admin123', $result['password_hash'])) {
        echo "✅ Password verification successful!\n";
        echo "🚀 You can now login with: admin / admin123\n";
    } else {
        echo "❌ Password verification failed\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>