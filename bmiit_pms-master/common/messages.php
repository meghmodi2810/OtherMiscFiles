<?php
/**
 * MESSAGING / CHAT SYSTEM (All Roles)
 * 
 * PURPOSE:
 * Real-time messaging interface enabling direct communication between students-faculty, faculty-admin, and group discussions with message history and file sharing.
 * 
 * CORE FUNCTIONALITY:
 * 1. Display conversation list sidebar: recent chats with unread message count badges
 * 2. Main chat window: selected conversation thread with messages in chronological order
 * 3. Send text messages with character limit (max 2000 characters per message)
 * 4. File attachments: images (JPG/PNG <5MB), documents (PDF/DOCX <10MB), share links
 * 5. Real-time message delivery: AJAX polling every 2 seconds or WebSocket for instant updates
 * 6. Message read receipts: show checkmark when recipient reads message
 * 7. Typing indicators: "Dr. Smith is typing..." shown in real-time
 * 8. Search messages: find text within conversation history
 * 9. Role-specific access control:
 *    - Students: chat with assigned faculty guide, group members (group chat)
 *    - Faculty: chat with assigned student groups, other faculty, admin
 *    - Admin: chat with all users (support role)
 * 10. Group chat: dedicated thread for each project group, all members can participate
 * 11. Message notifications: email sent if user offline for >30 minutes
 * 12. Delete messages: sender can delete within 5 minutes, shows "Message deleted" placeholder
 * 13. Archive conversations: hide old chats from list without deleting
 * 14. Export chat history: download conversation as PDF for record-keeping
 * 
 * UI/UX REQUIREMENTS: WhatsApp-like interface with 3-column layout (contacts, conversation list, chat window), message bubbles (sent=blue right-aligned, received=gray left-aligned), timestamp below each message, emoji picker for reactions, file preview thumbnails, infinite scroll for message history (load older messages on scroll up), mobile-responsive with slide-out contact list, user online status indicators (green dot), last seen timestamp
 * 
 * SECURITY: Verify sender-recipient relationship (students only chat with assigned faculty), sanitize message content (prevent XSS), validate file uploads (MIME type, size), encrypt sensitive messages (optional), prepared statements for all DB operations, rate limiting (max 100 messages per user per hour)
 * 
 * DATABASE SCHEMA NEEDED:
 * CREATE TABLE messages (
 *   message_id INT AUTO_INCREMENT PRIMARY KEY,
 *   sender_id INT NOT NULL,
 *   receiver_id INT NOT NULL,
 *   conversation_id VARCHAR(50) NOT NULL,
 *   message_text TEXT,
 *   attachment_url VARCHAR(500),
 *   attachment_type VARCHAR(20),
 *   message_status ENUM('sent','delivered','read','deleted') DEFAULT 'sent',
 *   sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 *   read_at TIMESTAMP NULL,
 *   is_group_message TINYINT(1) DEFAULT 0,
 *   group_id INT NULL,
 *   FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
 *   FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
 *   INDEX idx_conversation (conversation_id, sent_at DESC)
 * );
 * 
 * CREATE TABLE conversations (
 *   conversation_id VARCHAR(50) PRIMARY KEY,
 *   participant1_id INT NOT NULL,
 *   participant2_id INT NULL,
 *   conversation_type ENUM('direct','group') DEFAULT 'direct',
 *   group_id INT NULL,
 *   last_message_at TIMESTAMP,
 *   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 *   FOREIGN KEY (participant1_id) REFERENCES users(id),
 *   FOREIGN KEY (group_id) REFERENCES groups(group_id)
 * );
 * 
 * STATUS: 🔴 NOT IMPLEMENTED
 */
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$role = $_SESSION['user_role'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Messages - BMIIT PMS</title>
    <link rel="icon" type="image/ico" href="/bmiit_pms/assets/bmiitfavicol.ico">
    <link rel="stylesheet" href="/bmiit_pms/css/dashboard.css">
    <style>
        .chat-container { display: grid; grid-template-columns: 300px 1fr; height: calc(100vh - 150px); background: white; }
        .conversation-list { border-right: 1px solid #ddd; overflow-y: auto; }
        .chat-window { display: flex; flex-direction: column; }
        .messages-area { flex: 1; overflow-y: auto; padding: 20px; background: #f5f5f5; }
        .message-input { padding: 15px; border-top: 1px solid #ddd; display: flex; gap: 10px; }
    </style>
</head>
<body>
    <?php
    if ($role == 'admin') include 'admin/admin_header_menu.php';
    elseif ($role == 'faculty') include 'faculty/faculty_header_menu.php';
    elseif ($role == 'student') include 'student/student_header_menu.php';
    ?>
    <div class="main">
        <header class="topbar">
            <h1>🔴 Messages</h1>
            <form action="logout.php" method="post" class="logout-form">
                <input type="submit" name="logout" value="Logout">
            </form>
        </header>
        <div style="padding: 20px;">
            <div style="background: #e7f3ff; padding: 15px; margin-bottom: 20px; border-left: 4px solid #2196F3;">
                <strong>⚠️ PLACEHOLDER:</strong> Messaging system not implemented. Will include real-time chat, file sharing, group discussions, read receipts, and notifications.
            </div>
            
            <div class="chat-container">
                <div class="conversation-list" style="padding: 15px;">
                    <h3 style="margin: 0 0 15px 0;">Conversations</h3>
                    <div style="padding: 20px; text-align: center; color: #999;">
                        No conversations yet
                    </div>
                </div>
                <div class="chat-window">
                    <div class="messages-area">
                        <div style="text-align: center; color: #999; margin-top: 50px;">
                            <h3>Select a conversation to start chatting</h3>
                        </div>
                    </div>
                    <div class="message-input">
                        <input type="text" placeholder="Type a message..." style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 20px;" disabled>
                        <button style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 20px;" disabled>Send</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
