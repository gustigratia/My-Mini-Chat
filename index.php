    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="refresh" content="2">
        <title>Mini-Chat Homepage</title>
        <link rel="stylesheet" href="styles.css"> 
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    </head>
    <body>
        <div class="homepage-container">
            <div class="homepage-header">
                <?php
                include "db.php";
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                if (!isset($_SESSION['user_id'])) header("Location: login.php");

                $user_id = $_SESSION['user_id'];
                echo "<h2>こんにちは, " . htmlspecialchars($_SESSION['fullname']) . "</h2>";
                ?>
            </div>

            <div class="homepage-content">
                <div class="section-card">
                    <h4>Private Chat</h4>
                    <div class="chat-links-list">
                        <?php
                        $stmt = $conn->prepare("
                            SELECT 
                                u.user_id, 
                                u.fullname, 
                                last_message_sub.message,  
                                last_message_sub.timestamp 
                            FROM user u
                            LEFT JOIN (
                                SELECT 
                                    p.parties_id,
                                    CASE 
                                        WHEN p.initiator = ? THEN p.recipient 
                                        ELSE p.initiator 
                                    END AS chat_partner_id,
                                    (SELECT message FROM priv_chat WHERE parties_id = p.parties_id ORDER BY timestamp DESC, chat_id DESC LIMIT 1) AS message,
                                    (SELECT timestamp FROM priv_chat WHERE parties_id = p.parties_id ORDER BY timestamp DESC, chat_id DESC LIMIT 1) AS timestamp
                                FROM parties p
                                WHERE (p.initiator = ? OR p.recipient = ?)
                            ) AS last_message_sub ON u.user_id = last_message_sub.chat_partner_id
                            WHERE u.user_id != ?
                            ORDER BY last_message_sub.timestamp DESC, u.fullname ASC
                        ");
                        $stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        while ($row = $result->fetch_assoc()) {
                            $last_msg = $row['message'] ? htmlspecialchars($row['message']) : '(No message yet)';
                            $last_time = $row['timestamp'] ? date('H:i', strtotime($row['timestamp'])) : '';
                            echo "<a class='chat-link-item private-chat-item' href='chat.php?user={$row['user_id']}'>
                                    <div>
                                        <strong>" . htmlspecialchars($row['fullname']) . "</strong>
                                        <span class='last-message'>{$last_msg}</span>
                                    </div>
                                    <span class='last-message-time'>{$last_time}</span>
                                </a>";
                        }
                        ?>
                    </div>
                </div>

                <div class="section-card">
                    <h4>Group Chat</h4>
                    <div class="group-actions-homepage">
                        <a href="add_group.php" class="button create-group-button">Create New</a>
                    </div>
                    <div class="chat-links-list">
                        <?php
                        $stmt = $conn->prepare("
                            SELECT 
                                g.group_id, 
                                g.group_name, 
                                gc.message, 
                                gc.timestamp,
                                u.fullname AS sender_fullname /* Added sender's fullname */
                            FROM groupss g
                            JOIN group_member gm ON g.group_id = gm.group_id
                            LEFT JOIN (
                                SELECT 
                                    group_id, 
                                    message, 
                                    timestamp,
                                    message_sender
                                FROM group_chat
                                WHERE (group_id, timestamp) IN (
                                    SELECT group_id, MAX(timestamp) FROM group_chat GROUP BY group_id
                                )
                            ) gc ON g.group_id = gc.group_id
                            LEFT JOIN user u ON gc.message_sender = u.user_id /* Join with user to get sender's name */
                            WHERE gm.user_id = ?
                            ORDER BY gc.timestamp DESC
                        ");
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $results = $stmt->get_result();

                        while ($row = $results->fetch_assoc()) {
                            $msg = $row['message'] ? htmlspecialchars($row['message']) : '(No message yet)';
                            $time = $row['timestamp'] ? date('H:i', strtotime($row['timestamp'])) : '';
                            $sender_name = $row['sender_fullname'] ? htmlspecialchars($row['sender_fullname']) . ': ' : ''; // Display sender name if available
                            
                            echo "<a class='chat-link-item group-chat-item' href='group_chat.php?group_id={$row['group_id']}'>
                                    <div>
                                        <strong>" . htmlspecialchars($row['group_name']) . "</strong>
                                        <span class='last-message'>{$sender_name}{$msg}</span>
                                    </div>
                                    <span class='last-message-time'>{$time}</span>
                                </a>";
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="homepage-footer">
                <a href="logout.php" class="button logout-button">Logout</a>
            </div>
        </div>
    </body>
    </html>
