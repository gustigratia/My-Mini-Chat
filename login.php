<?php
include "db.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$status_message = null; 

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST['username'];
    $password = $_POST['password']; 

    $stmt = $conn->prepare("SELECT * FROM user WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['fullname'] = $user['fullname'];
            header("Location: index.php");
            exit();
        } else {
            $status_message = ['type' => 'error', 'text' => 'Password salah.'];
        }
    } else {
        $status_message = ['type' => 'error', 'text' => 'Username tidak ditemukan.'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <title>Mini-chat Login</title>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2>Welcome</h2>
        </div>

        <?php if ($status_message): ?>
            <div class="status-message <?= $status_message['type'] ?>">
                <?= $status_message['text'] ?>
            </div>
        <?php endif; ?>

        <form method="post" class="login-form">
            <input name="username" type="text" placeholder="Username" required class="login-input">
            <input name="password" type="password" placeholder="Password" required class="login-input">
            <button type="submit" class="login-button">Login</button>
        </form>
        <div class="login-footer">
            <p>Need an account? <a href="register.php">Sign Up</a></p>
        </div>
    </div>
</body>
</html>
