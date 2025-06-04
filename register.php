<?php
include "db.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$status_message = null; 

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname = $_POST['fullname'];
    $username = $_POST['username'];
    $email = $_POST['email'];   // Added email
    $address = $_POST['address']; // Added address
    $password = $_POST['password'];

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("SELECT * FROM user WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->fetch_assoc()) {
        $status_message = ['type' => 'error', 'text' => 'Username sudah digunakan. Silakan pilih username lain.'];
    } else {
        $stmt = $conn->prepare("INSERT INTO user (fullname, username, email, address, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $fullname, $username, $email, $address, $hashed_password);
        if ($stmt->execute()) {
            $status_message = ['type' => 'success', 'text' => 'Registrasi berhasil. Silakan <a href="login.php">Login di sini</a>.'];
        } else {
            $status_message = ['type' => 'error', 'text' => 'Terjadi kesalahan saat registrasi.'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Mini-chat</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h2>Create New Account</h2>
        </div>

        <?php if ($status_message): ?>
            <div class="status-message <?= $status_message['type'] ?>">
                <?= $status_message['text'] ?>
            </div>
        <?php endif; ?>

        <form method="post" class="register-form">
            <input name="fullname" type="text" placeholder="Full Name" required class="register-input">
            <input name="email" type="email" placeholder="Email@address.com" required class="register-input">
            <input name="address" type="text" placeholder="Address" required class="register-input">
            <input name="username" type="text" placeholder="Username" required class="register-input">
            <input name="password" type="password" placeholder="Password" required class="register-input">
            <button type="submit" class="register-button">Register</button>
        </form>

        <div class="register-footer">
            <p>Already have an account? <a href="login.php">Login</a></p>
        </div>
    </div>
</body>
</html>
