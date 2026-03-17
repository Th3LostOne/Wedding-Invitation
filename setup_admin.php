<?php
// ============================================================
//  setup_admin.php — Run ONCE to create the first admin user.
//  DELETE this file after use.
// ============================================================

// Restrict to localhost only
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    http_response_code(403);
    die('Forbidden');
}

require "db.php";

$username = 'admin';
$password = 'wedding2026';   // Change this before running

// Refuse to overwrite an existing admin
$check = $conn->query("SELECT COUNT(*) as cnt FROM admin_users");
if ($check && $check->fetch_assoc()['cnt'] > 0) {
    die('Admin user already exists. Delete this file.');
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)");
$stmt->bind_param("ss", $username, $hash);

if ($stmt->execute()) {
    echo "<p style='font-family:sans-serif;padding:20px;'>
        Admin created.<br>
        Username: <strong>admin</strong><br>
        Password: <strong>wedding2026</strong><br><br>
        <strong style='color:red'>Delete this file immediately!</strong>
    </p>";
} else {
    echo "Error: " . $conn->error;
}
$stmt->close();
?>
