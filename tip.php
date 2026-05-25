<?php
// ==========================================
// CONFIGURATION
// ==========================================
$botToken = "YOUR_TELEGRAM_BOT_TOKEN"; // Replace with your BotFather token
$dbFile = __DIR__ . '/tipbot.sqlite'; // SQLite database file

// Allowed Tokens Array
$allowedTokens = ['XPR', 'UBQT', 'UBQTX', 'NDAO', 'NDAOX', 'NDAOXPR', 'MESSAGE', 'CIPHER', 'NOTARY', 'SMART'];

// Donation Wallets
$donationWallets = [
    'ndao'      => 'nftitledao',
    'ubitquity' => 'ubitquity1'
];

// ==========================================
// DATABASE SETUP (SQLite via PDO)
// ==========================================
try {
    $pdo = new PDO("sqlite:" . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS users (
            telegram_handle TEXT PRIMARY KEY,
            xpr_name TEXT NOT NULL
        )
    ";
    $pdo->exec($createTableQuery);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    exit;
}

// ==========================================
// TELEGRAM WEBHOOK RECEIVER
// ==========================================
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update || !isset($update['message'])) {
    exit;
}

$message = $update['message'];
$chatId = $message['chat']['id'];
$text = isset($message['text']) ? trim($message['text']) : '';
$fromUser = isset($message['from']['username']) ? strtolower($message['from']['username']) : 'unknown_user';

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
// Normalize extra spaces
$text = preg_replace('/\s+/', ' ', $text);
$parts = explode(' ', $text);
$command = strtolower($parts[0]);

// 1. /register <myxprnetworkname>
if ($command === '/register') {
    if (count($parts) < 2) {
        sendMessage($chatId, "Usage: <code>/register &lt;your_xprnetwork_name&gt;</code>", $botToken);
        exit;
    }
    
    $xprName = preg_replace('/[^a-z1-5.]/', '', strtolower($parts[1])); 
    
    $stmt = $pdo->prepare("INSERT OR REPLACE INTO users (telegram_handle, xpr_name) VALUES (:tg, :xpr)");
    $stmt->execute([':tg' => $fromUser, ':xpr' => $xprName]);
    
    sendMessage($chatId, "✅ Successfully linked Telegram <b>@$fromUser</b> to XPR Network account: <b>$xprName</b>", $botToken);
} 

// 2. /tip @user <amount> <TOKEN>
elseif ($command === '/tip') {
    if (count($parts) < 4) {
        sendMessage($chatId, "Usage: <code>/tip @telegram_user &lt;amount&gt; &lt;TOKEN&gt;</code>\nExample: <code>/tip @satoshi 50 UBQT</code>", $botToken);
        exit;
    }
    
    $targetTelegram = strtolower(str_replace('@', '', $parts[1]));
    $amount = preg_replace('/[^0-9.]/', '', $parts[2]); // Ensure numerical
    $token = strtoupper($parts[3]);
    
    if (!in_array($token, $allowedTokens)) {
        sendMessage($chatId, "❌ Unsupported token. Allowed tokens: " . implode(', ', $allowedTokens), $botToken);
        exit;
    }
    
    if ($amount <= 0) {
        sendMessage($chatId, "❌ Amount must be greater than 0.", $botToken);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT xpr_name FROM users WHERE telegram_handle = :tg");
    $stmt->execute([':tg' => $targetTelegram]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) {
        sendMessage($chatId, "User <b>@$targetTelegram</b> hasn't registered their XPR name yet. Tell them to use /register!", $botToken);
        exit;
    }
    
    $targetXpr = $row['xpr_name'];
    $memo = urlencode("Tip from Telegram @$fromUser");
    
    $link = "https://webauth.com/transfer?to=$targetXpr&amount=$amount&symbol=$token&memo=$memo";
    
    $response = "Click the link below to sign your tip of <b>$amount $token</b> to @$targetTelegram ($targetXpr):\n\n";
    $response .= "<a href=\"$link\">🔒 Open WebAuth to Sign Tip</a>";
    
    sendMessage($chatId, $response, $botToken);
}

// 3. /donate <organization> <amount> <TOKEN>
elseif ($command === '/donate') {
    if (count($parts) < 4) {
        $orgs = implode(', ', array_keys($donationWallets));
        sendMessage($chatId, "Usage: <code>/donate &lt;organization&gt; &lt;amount&gt; &lt;TOKEN&gt;</code>\nOrgs: $orgs\nExample: <code>/donate ndao 100 NDAO</code>", $botToken);
        exit;
    }
    
    $org = strtolower($parts[1]);
    $amount = preg_replace('/[^0-9.]/', '', $parts[2]);
    $token = strtoupper($parts[3]);
    
    if (!array_key_exists($org, $donationWallets)) {
        sendMessage($chatId, "❌ Unknown organization. Available: " . implode(', ', array_keys($donationWallets)), $botToken);
        exit;
    }
    
    if (!in_array($token, $allowedTokens)) {
        sendMessage($chatId, "❌ Unsupported token. Allowed tokens: " . implode(', ', $allowedTokens), $botToken);
        exit;
    }
    
    $daoWallet = $donationWallets[$org];
    $memo = urlencode("Donation to $org");
    
    $link = "https://webauth.com/transfer?to=$daoWallet&amount=$amount&symbol=$token&memo=$memo";
    
    $response = "Support <b>" . strtoupper($org) . "</b> by donating <b>$amount $token</b> to <b>$daoWallet</b>:\n\n";
    $response .= "<a href=\"$link\">🔒 Sign Donation in WebAuth</a>";
    
    sendMessage($chatId, $response, $botToken);
}
?>
