<?php
include "db.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) header("Location: login.php"); 

$me = $_SESSION['user_id'];
$group_id = $_GET['group_id'] ?? null;

if (!$group_id) {
    echo "Group_id not found";
    exit;
}

$stmt = $conn->prepare("SELECT group_name, owner FROM groupss WHERE group_id = ?");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo "Group not found";
    exit;
}
$group = $result->fetch_assoc();
$group_name = htmlspecialchars($group['group_name']);
$group_owner_id = $group['owner'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['message']) && !empty(trim($_POST['message']))) {
        $msg = trim($_POST['message']);
        $stmt = $conn->prepare("INSERT INTO group_chat (group_id, message_sender, message, timestamp) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $group_id, $me, $msg);
        $stmt->execute();
        header("Location: group_chat.php?group_id=" . $group_id);
        exit;
    }

    if (isset($_POST['add_member'])) {
        $new_username = trim($_POST['new_username']);
        $stmt = $conn->prepare("SELECT user_id FROM user WHERE username = ?");
        $stmt->bind_param("s", $new_username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $message_type = 'error';
            $message_text = "User not found";
        } else {
            $user_to_add = $result->fetch_assoc()['user_id'];
            $check = $conn->prepare("SELECT * FROM group_member WHERE group_id = ? AND user_id = ?");
            $check->bind_param("ii", $group_id, $user_to_add);
            $check->execute();
            $check_result = $check->get_result();

            if ($check_result->num_rows > 0) {
                $message_type = 'warning';
                $message_text = "User is already in the group";
            } else {
                $stmt = $conn->prepare("INSERT INTO group_member (user_id, group_id, join_date) VALUES (?, ?, CURDATE())");
                $stmt->bind_param("ii", $user_to_add, $group_id);
                $stmt->execute();
                $message_type = 'success';
                $message_text = "User successfully added into group";
            }
        }
        $_SESSION['status_message'] = ['type' => $message_type, 'text' => $message_text];
        header("Location: group_chat.php?group_id=" . $group_id);
        exit;
    }

    if (isset($_POST['leave_group'])) {
        if ($group_owner_id == $me) {
            $message_type = 'error';
            $message_text = "Owner tidak bisa keluar dari grup.";
        } else {
            $stmt = $conn->prepare("DELETE FROM group_member WHERE group_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $group_id, $me);
            $stmt->execute();
            $_SESSION['status_message'] = ['type' => 'success', 'text' => 'Kamu telah keluar dari grup.'];
            header("Location: index.php");
            exit;
        }
        $_SESSION['status_message'] = ['type' => $message_type, 'text' => $message_text];
        header("Location: group_chat.php?group_id=" . $group_id);
        exit;
    }
}

$status_message = null;
if (isset($_SESSION['status_message'])) {
    $status_message = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Grup - <?= $group_name ?></title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <a href="index.php" class="back-arrow">⬅</a>
            <h2><?= $group_name ?></h2>
            <form method="POST" class="leave-group-form-header">
                <button type="submit" name="leave_group" class="header-button leave-group-button-header" title="Keluar dari Grup">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </form>
        </div>

        <?php if ($status_message): ?>
            <div class="status-message <?= $status_message['type'] ?>">
                <?= $status_message['text'] ?>
            </div>
        <?php endif; ?>

        <div class="group-actions">
            <form method="POST" class="add-member-form">
                <input type="text" name="new_username" placeholder="Username anggota baru" required class="form-input">
                <button type="submit" name="add_member" class="form-button add-button">Tambah</button>
            </form>
        </div>

        <div class="chat-box" id="chat-box">
            </div>

        <form method="POST" class="chat-form">
            <input type="text" name="message" required class="chat-input" placeholder="Ketik pesan...">
            <button type="submit" class="chat-submit">Kirim</button>
        </form>
    </div>

    <script>
        function loadMessages() {
            const xhr = new XMLHttpRequest();
            xhr.open("GET", "get_messages.php?group_id=<?= $group_id ?>&user_id=<?= $me ?>", true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const chatBox = document.getElementById("chat-box");
                    const isAtBottom = chatBox.scrollHeight - chatBox.scrollTop - chatBox.clientHeight < 50;

                    chatBox.innerHTML = xhr.responseText;

                    if (isAtBottom) {
                        chatBox.scrollTop = chatBox.scrollHeight;
                    }
                }
            };
            xhr.send();
        }

        loadMessages();

        setInterval(loadMessages, 2000);

        document.addEventListener('DOMContentLoaded', () => {
            const chatBox = document.getElementById("chat-box");
            chatBox.scrollTop = chatBox.scrollHeight;
        });

        const chatForm = document.querySelector('.chat-form');
        chatForm.addEventListener('submit', function() {
            setTimeout(() => {
                this.elements['message'].value = '';
            }, 100); 
        });

        const statusMessageDiv = document.querySelector('.status-message');
        if (statusMessageDiv) {
            setTimeout(() => {
                statusMessageDiv.style.opacity = '0';
                statusMessageDiv.style.height = '0';
                statusMessageDiv.style.padding = '0';
                statusMessageDiv.style.margin = '0';
            }, 5000);
        }
    </script>
</body>
</html>
