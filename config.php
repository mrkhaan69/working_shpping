<?php
session_start();

// Database Credentials
$db_host = 'localhost';
$db_name = 'hamconje_finalship';
$db_user = 'hamconje_finalship';
$db_pass = 'Adamjee@@69';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Language Logic
$lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en');
$_SESSION['lang'] = $lang;

$translations = [
    'en' => [
        'title' => 'China to Iran Shipping',
        'sender_form' => 'Sender Registration',
        'carrier_form' => 'Carrier Registration',
        'track_parcel' => 'Track Parcel',
        'name' => 'Name',
        'phone' => 'Phone',
        'email' => 'Email',
        'submit' => 'Submit',
        'tracking_num' => 'Tracking Number',
        'status' => 'Status',
        'dir' => 'ltr',
        'price' => 'Estimated Price (CNY)',
        'success' => 'Submission Successful!',
        'admin_login' => 'Admin Login'
    ],
    'fa' => [
        'title' => 'ارسال کالا از چین به ایران',
        'sender_form' => 'ثبت نام فرستنده',
        'carrier_form' => 'ثبت نام حمل کننده',
        'track_parcel' => 'رهگیری مرسوله',
        'name' => 'نام',
        'phone' => 'تلفن',
        'email' => 'ایمیل',
        'submit' => 'ارسال',
        'tracking_num' => 'شماره رهگیری',
        'status' => 'وضعیت',
        'dir' => 'rtl',
        'price' => 'قیمت تقریبی (یوان)',
        'success' => 'ثبت با موفقیت انجام شد!',
        'admin_login' => 'ورود مدیر'
    ],
    'cn' => [
        'title' => '中国到伊朗航运',
        'sender_form' => '发件人注册',
        'carrier_form' => '承运人注册',
        'track_parcel' => '包裹追踪',
        'name' => '姓名',
        'phone' => '电话',
        'email' => '电子邮件',
        'submit' => '提交',
        'tracking_num' => '追踪号码',
        'status' => '状态',
        'dir' => 'ltr',
        'price' => '预估价格 (人民币)',
        'success' => '提交成功！',
        'admin_login' => '管理员登录'
    ]
];

$t = $translations[$lang];

// --- HELPER FUNCTIONS ---

function sendTelegram($msg, $pdo) {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute(['telegram_bot_token']);
    $token = $stmt->fetchColumn();
    $stmt->execute(['telegram_chat_id']);
    $chat_id = $stmt->fetchColumn();

    if($token && $chat_id) {
        $url = "https://api.telegram.org/bot$token/sendMessage?chat_id=$chat_id&text=" . urlencode($msg);
        $ctx = stream_context_create(['http' => ['timeout' => 2]]); 
        @file_get_contents($url, false, $ctx);
    }
}

// UPDATED: Dynamic Pricing based on Admin Settings
function calculatePrice($weight) {
    global $pdo; // Use the database connection
    
    // Fetch pricing settings
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'price_%'");
    $prices = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Defaults if DB fails
    $p1 = isset($prices['price_1_5']) ? floatval($prices['price_1_5']) : 200;
    $p2 = isset($prices['price_5_10']) ? floatval($prices['price_5_10']) : 150;
    $p3 = isset($prices['price_10_plus']) ? floatval($prices['price_10_plus']) : 140;

    $rate = 0;
    if($weight <= 5) {
        $rate = $p1;
    } elseif ($weight <= 10) {
        $rate = $p2;
    } else {
        $rate = $p3;
    }

    return $weight * $rate;
}

function sendEmail($to, $subject, $message) {
    $fromEmail = 'noreply@shipping.hamchamedani.com';
    $replyTo = 'hello@hamchamedani.com';
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Shipping System <$fromEmail>" . "\r\n";
    $headers .= "Reply-To: $replyTo" . "\r\n";
    $headers .= 'X-Mailer: PHP/' . phpversion();

    $htmlMsg = "
    <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #eee; border-radius: 5px;'>
        <h3 style='color: #333;'>$subject</h3>
        <p style='color: #555;'>$message</p>
        <hr>
        <small style='color: #999;'>This is an automated message from hamchamedani.com</small>
    </div>";

    return @mail($to, $subject, $htmlMsg, $headers);
}
?>
