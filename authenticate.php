<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Bad request protocol"]);
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "Credentials required"]);
    exit;
}

// Prepared statement to avoid SQL injection
$stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($user =$result->fetch_assoc()) {
    // Check hashed password OR plaintext fallback
    if (password_verify($password,$user['password']) || $password ===$user['password']) {
        
        // Auto-rehash to secure bcrypt if it was plaintext
        if ($password ===$user['password']) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $update =$conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->bind_param("si", $newHash,$user['id']);
            $update->execute();$update->close();
        }

        $_SESSION['resq_user'] =$user['username'];
        $_SESSION['resq_role'] =$user['role'];
        echo json_encode(["status" => "success", "message" => "Authorized"]);
        exit;
    }
}
// if ($user = $result->fetch_assoc()) {
//     if (password_verify($password, $user['password'])) {
//         $_SESSION['resq_user'] = $user['username'];
//         $_SESSION['resq_role'] = $user['role'];
//         echo json_encode(["status" => "success", "message" => "Authorized"]);
//         exit;
//     }
// }

echo json_encode(["status" => "error", "message" => "Invalid operator credentials"]);
$stmt->close();
$conn->close();
?>