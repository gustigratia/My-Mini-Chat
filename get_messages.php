<?php
include "db.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$group_id = $_GET['group_id'] ?? null;
$current_user_id = $_GET['user_id'] ?? null; // Get the current user's ID from AJAX

if (!$group_id || !$current_user_id) {
    // It's better to log this error than expose it directly in production
    // error_log("Error: Group ID or User ID missing in get_messages.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT gc.message, gc.timestamp, gc.message_sender, u.fullname
    FROM group_chat gc
    JOIN user u ON gc.message_sender = u.user_id
    WHERE gc.group_id = ?
    ORDER BY gc.timestamp ASC
");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $isMe = ($row['message_sender'] == $current_user_id);
    $bubbleClass = $isMe ? "from-me" : "from-them";
    $sender = $isMe ? "Saya" : htmlspecialchars($row['fullname']); // Display "Saya" for current user
    $message = nl2br(htmlspecialchars($row['message'])); // Preserve line breaks
    $time = date("H:i", strtotime($row['timestamp'])); // Format time

    echo "
    <div class='chat-message'>
        <div class='bubble " . $bubbleClass . "'>
            <div class='sender'>$sender</div>
            <div class='text'>$message</div>
            <div class='timestamp'>$time</div>
        </div>
    </div>
    ";
}
?>
