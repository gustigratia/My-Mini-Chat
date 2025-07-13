<?php
include "db.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) header("Location: login.php");

$me = $_SESSION['user_id'];
$you = $_GET['user'] ?? null;

if (!$you) {
    echo "User not found.";
    exit;
}

$stmt = $conn->prepare("SELECT fullname FROM user WHERE user_id = ?");
$stmt->bind_param("i", $you);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $target_fullname = $row['fullname'];
} else {
    echo "User not found.";
    exit;
}

$stmt = $conn->prepare("SELECT parties_id FROM parties WHERE (initiator = ? AND recipient = ?) OR (initiator = ? AND recipient = ?)");
$stmt->bind_param("iiii", $me, $you, $you, $me);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $party_id = $row['parties_id'];
} else {
    $stmt = $conn->prepare("INSERT INTO parties (initiator, recipient, started_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("ii", $me, $you);
    $stmt->execute();
    $party_id = $conn->insert_id;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roomchat <?= htmlspecialchars($target_fullname) ?></title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <a href="index.php" class="back-arrow">⬅</a>
            <h2><?= htmlspecialchars($target_fullname) ?></h2>
            <div></div>
        </div>

        <div class="chat-box" id="chat-box">
            </div>

        <form method="POST" class="chat-form">
            <input type="text" name="message" required class="chat-input" placeholder="Type your message...">
            <button type="submit" class="chat-submit">Send</button>
        </form>

    </div>

    <script>
        function loadMessages() {
            const xhr = new XMLHttpRequest();
            xhr.open("GET", "get_priv_messages.php?party_id=<?= $party_id ?>&user_id=<?= $me ?>", true); // Pass user_id to get_priv_messages
            xhr.onload = function () {
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
    </script>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $msg = trim($_POST['message']);
        if (!empty($msg)) {
            $stmt = $conn->prepare("INSERT INTO priv_chat (parties_id, message_sender, timestamp, message) VALUES (?, ?, NOW(), ?)");
            $stmt->bind_param("iis", $party_id, $me, $msg);
            $stmt->execute();
        }

        header("Location: chat.php?user=$you");
        exit;
    }
    ?>
</body>
</html>