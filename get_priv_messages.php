<?php
include "db.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) exit;
$me = $_SESSION['user_id'];

$party_id = $_GET['party_id'] ?? null;
if (!$party_id) exit;

$stmt = $conn->prepare("
    SELECT pc.*, u.fullname
    FROM priv_chat pc
    JOIN user u ON pc.message_sender = u.user_id
    WHERE pc.parties_id = ?
    ORDER BY pc.timestamp ASC
");
$stmt->bind_param("i", $party_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $isMe = $row['message_sender'] == $me;
    $bubbleClass = $isMe ? "from-me" : "from-them";
    $sender = $isMe ? "Saya" : htmlspecialchars($row['fullname']);
    $message = nl2br(htmlspecialchars($row['message']));
    $time = date("H:i", strtotime($row['timestamp']));

    echo "
    <div class='chat-message'>
        <div class='bubble $bubbleClass'>
            <div class='text'>$message</div>
            <div class='timestamp'>$time</div>
        </div>
    </div>
    ";
}
?>
