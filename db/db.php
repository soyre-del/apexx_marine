<?php
$host = 'localhost';
$db   = 'apex_marine_test';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 2. Admin Credentials
    $email = 'admin@apexmarine.com';
    $password = 'admin123';
    
    // Encrypt the password securely[cite: 1]
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // 3. Insert into the database using a PDO prepared statement[cite: 1]
    $sql = "INSERT INTO users (full_name, email, password_hash, role, is_active) 
            VALUES ('System Administrator', :email, :password_hash, 'admin', 1)";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':email' => $email,
        ':password_hash' => $hashed_password
    ]);
    
    // 4. Success UI
    echo "<div style='font-family: monospace; background: #0d1b2a; color: #10b981; padding: 40px; text-align: center; height: 100vh;'>";
    echo "<h2>&gt; SUCCESS: MAIN FRAME OVERRIDDEN</h2>";
    echo "Admin account securely created.<br><br>";
    echo "<strong>Email:</strong> " . $email . "<br>";
    echo "<strong>Password:</strong> " . $password . "<br><br>";
    echo "<a href='login.php' style='color: #0ea5e9; text-decoration: none; border: 1px solid #0ea5e9; padding: 10px 20px; border-radius: 5px;'>Access Terminal &rarr;</a>";
    echo "</div>";

} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        echo "";
    } else {
        echo "<h3 style='color: red; font-family: monospace; text-align: center; padding: 40px; background: #0d1b2a;'>CRITICAL ERROR: " . $e->getMessage() . "</h3>";
    }
}
?>