<?php
include "db.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) header("Location: login.php");

$me = $_SESSION['user_id'];
$status_message = null; 

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $group_name = trim($_POST['group_name']);
    $selected_users = $_POST['members'] ?? [];

    if (empty($group_name)) {
        $status_message = ['type' => 'error', 'text' => 'Nama grup tidak boleh kosong.'];
    } else {

        $stmt = $conn->prepare("INSERT INTO groupss (group_name, owner, created_date) VALUES (?, ?, CURDATE())");
        $stmt->bind_param("si", $group_name, $me);
        if ($stmt->execute()) {
            $group_id = $conn->insert_id;

            $stmt = $conn->prepare("INSERT INTO group_member (user_id, group_id, join_date) VALUES (?, ?, CURDATE())");
            $stmt->bind_param("ii", $me, $group_id);
            $stmt->execute();

            if (!empty($selected_users)) {
                $insert_member_stmt = $conn->prepare("INSERT INTO group_member (user_id, group_id, join_date) VALUES (?, ?, CURDATE())");
                foreach ($selected_users as $user_id) {

                    if ($user_id != $me) {
                        $insert_member_stmt->bind_param("ii", $user_id, $group_id);
                        $insert_member_stmt->execute();
                    }
                }
            }
            
            $_SESSION['status_message'] = ['type' => 'success', 'text' => 'Grup berhasil dibuat!'];
            header("Location: index.php");
            exit();
        } else {
            $status_message = ['type' => 'error', 'text' => 'Terjadi kesalahan saat membuat grup.'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Group</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="add-group-container">
        <div class="add-group-header">
            <a href="index.php" class="back-arrow">⬅</a>
            <h2>Create New Group</h2>
            <div></div>
        </div>

        <?php if ($status_message): ?>
            <div class="status-message <?= $status_message['type'] ?>">
                <?= $status_message['text'] ?>
            </div>
        <?php endif; ?>

        <form method="post" class="add-group-form">
            <div class="form-group">
                <label for="group_name">Group Name:</label>
                <input type="text" id="group_name" name="group_name" required class="add-group-input" placeholder="Input group name">
            </div>

            <div class="form-group">
                <label>Member:</label>
                <div class="members-list">
                    <?php
                    $stmt = $conn->prepare("SELECT user_id, fullname FROM user WHERE user_id != ?");
                    $stmt->bind_param("i", $me);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    while ($row = $res->fetch_assoc()) {
                        echo "<label class='add-group-checkbox-label'><input type='checkbox' name='members[]' value='{$row['user_id']}'> " . htmlspecialchars($row['fullname']) . "</label><br>";
                    }
                    ?>
                </div>
            </div>
            
            <button type="submit" class="add-group-button">Create</button>
        </form>
    </div>
</body>
</html>
