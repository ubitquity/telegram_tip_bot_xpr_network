<?php
// ==========================================
// CONFIGURATION
// ==========================================
$botToken = "YOUR_TELEGRAM_BOT_TOKEN"; // Replace with your BotFather token
$daoWallet = "nftitledao"; // The DAO wallet for donations
$dbFile = __DIR__ . '/tipbot.sqlite'; // SQLite database file

// ==========================================
// DATABASE SETUP (SQLite via PDO)
// ==========================================
try {
    // Connect to SQLite database (creates the file if it doesn't exist)
    $pdo = new PDO("sqlite:" . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create the users table if it doesn't exist
    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS users (
            telegram_handle TEXT PRIMARY KEY,
            xpr_name TEXT NOT NULL
        )
    ";
    $pdo->exec($createTableQuery);
} catch (PDOException $e) {
    // If the DB fails to connect/create, exit silently to not break the webhook
    error_log("Database Error: " . $e->getMessage());
    exit;
}

// ==========================================
// TELEGRAM WEBHOOK RECEIVER
// ==========================================
// Read incoming JSON payload from Telegram
$content = file_get_contents("php://input");
$update = json_decode($content, true);

// Exit if no valid message is found
if (!$update || !isset($update['message'])) {
    exit;
}

$message = $update['message'];
$chatId = $message['chat']['id'];
$text = isset($message['text']) ? $message['text'] : '';
$fromUser = isset($message['from']['username']) ? strtolower($message['from']['username']) : 'unknown_user';

// Helper function to send messages back to Telegram
function sendMessage($chatId, $text, $botToken) {
    $url = "https://api.telegram.org/bot$botToken/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}

// ==========================================
// COMMAND ROUTING
// ==========================================
$parts = explode(' ', $text);
$command = strtolower($parts[0]);

// 1. /register <xpr_name> -> Links Telegram handle to WebAuth name
if ($command === '/register') {
    if (count($parts) < 2) {
        sendMessage($chatId, "Usage: <code>/register &lt;your_webauth_name&gt;</code>", $botToken);
        exit;
    }
    
    // Clean the input (XPR names are a-z, 1-5, and dots)
    $xprName = preg_replace('/[^a-z1-5.]/', '', strtolower($parts[1])); 
    
    // Insert or Update the user in the SQLite database
    $stmt = $pdo->prepare("INSERT OR REPLACE INTO users (telegram_handle, xpr_name) VALUES (:tg, :xpr)");
    $stmt->execute([':tg' => $fromUser, ':xpr' => $xprName]);
    
    sendMessage($chatId, "✅ Successfully linked Telegram <b>@$fromUser</b> to WebAuth account: <b>$xprName</b>", $botToken);
} 

// 2. /tip @user <amount> -> Generates a tip link
elseif ($command === '/tip') {
    if (count($parts) < 3) {
        sendMessage($chatId, "Usage: <code>/tip @telegram_user &lt;amount&gt;</code>", $botToken);
        exit;
    }
    
    $targetTelegram = strtolower(str_replace('@', '', $parts[1]));
    
    // XPR requires exactly 4 decimal places (e.g., 10.0000)
    $amount = number_format((float)$parts[2], 4, '.', '');
    
    // Retrieve the target user's XPR name from the SQLite database
    $stmt = $pdo->prepare("SELECT xpr_name FROM users WHERE telegram_handle = :tg");
    $stmt->execute([':tg' => $targetTelegram]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) {
        sendMessage($chatId, "User <b>@$targetTelegram</b> hasn't registered their XPR name yet. Tell them to use /register!", $botToken);
        exit;
    }
    
    $targetXpr = $row['xpr_name'];
    $memo = urlencode("Tip from Telegram @$fromUser");
    
    // Generate WebAuth Native Link
    $link = "https://webauth.com/transfer?to=$targetXpr&amount=$amount&symbol=XPR&memo=$memo";
    
    $response = "Click the link below to sign your tip of <b>$amount XPR</b> to @$targetTelegram ($targetXpr):\n\n";
    $response .= "<a href=\"$link\">🔒 Open WebAuth to Sign Tip</a>";
    
    sendMessage($chatId, $response, $botToken);
}

// 3. /donate <amount> -> Generates a donation link directly to nftitledao
elseif ($command === '/donate') {
    if (count($parts) < 2) {
        sendMessage($chatId, "Usage: <code>/donate &lt;amount&gt;</code>", $botToken);
        exit;
    }
    
    $amount = number_format((float)$parts[1], 4, '.', '');
    $memo = urlencode("Donation to NFTitle DAO");
    
    // Generate WebAuth Native Link targeting nftitledao
    $link = "https://webauth.com/transfer?to=$daoWallet&amount=$amount&symbol=XPR&memo=$memo";
    
    $response = "Support the DAO by donating <b>$amount XPR</b> to <b>$daoWallet</b>:\n\n";
    $response .= "<a href=\"$link\">🔒 Sign Donation in WebAuth</a>";
    
    sendMessage($chatId, $response, $botToken);
}
?>
