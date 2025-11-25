<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['sender_name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $weight = $_POST['weight'];
    $from = $_POST['address_from'];
    $to = $_POST['address_to'];
    $desc = $_POST['description'];
    $dims = $_POST['dimensions'];
    
    // Checkbox handling
    $chem = isset($_POST['chemicals']) ? 1 : 0;
    $chem_txt = $chem ? "YES" : "No";

    // Pricing Logic
    $price = calculatePrice($weight);
    
    // Generate Tracking
    $track_num = "TRK-" . strtoupper(substr(md5(uniqid()), 0, 8));
    
    // Image Upload
    $photo_path = null;
    if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $filename = "parcel_" . time() . "." . $ext;
        move_uploaded_file($_FILES['photo']['tmp_name'], "uploads/" . $filename);
        $photo_path = $filename;
    }

    // Insert DB
    $sql = "INSERT INTO shipments (tracking_number, sender_name, phone, email, weight, address_from, address_to, description, has_chemicals, price, photo_path, dimensions) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$track_num, $name, $phone, $email, $weight, $from, $to, $desc, $chem, $price, $photo_path, $dims]);

    // --- DETAILED TELEGRAM MESSAGE ---
    $msg = "📦 *NEW SHIPMENT ORDER*\n";
    $msg .= "───────────────\n";
    $msg .= "🆔 *Track #:* `$track_num`\n";
    $msg .= "💰 *Est Price:* ¥$price\n";
    $msg .= "───────────────\n";
    $msg .= "👤 *Sender:* $name\n";
    $msg .= "📱 *Phone:* $phone\n";
    $msg .= "📧 *Email:* $email\n";
    $msg .= "───────────────\n";
    $msg .= "⚖️ *Weight:* $weight KG\n";
    $msg .= "📏 *Dims:* $dims\n";
    $msg .= "☢️ *Chemicals:* $chem_txt\n";
    $msg .= "📝 *Desc:* $desc\n";
    $msg .= "───────────────\n";
    $msg .= "🛫 *From:* $from\n";
    $msg .= "🛬 *To:* $to";

    sendTelegram($msg, $pdo);
    // --------------------------------

    // Send Email
    sendEmail($email, "Shipment Received - $track_num", "Hello $name, <br>Your tracking number is: <b>$track_num</b>.<br>Estimated Price: ¥$price");

    $success = true;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $t['dir']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['title']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    
    <script>
        function updatePrice() {
            let weight = parseFloat(document.getElementById('weight').value);
            
            // Fetch PHP rates into JS variables
            let r1 = <?php $s = $pdo->query("SELECT setting_value FROM settings WHERE setting_key='price_1_5'")->fetchColumn(); echo $s ? $s : 200; ?>;
            let r2 = <?php $s = $pdo->query("SELECT setting_value FROM settings WHERE setting_key='price_5_10'")->fetchColumn(); echo $s ? $s : 150; ?>;
            let r3 = <?php $s = $pdo->query("SELECT setting_value FROM settings WHERE setting_key='price_10_plus'")->fetchColumn(); echo $s ? $s : 140; ?>;

            if(weight > 0) {
                let rate = 0;
                if(weight <= 5) rate = r1;
                else if(weight <= 10) rate = r2;
                else rate = r3;
                
                let total = (weight * rate).toFixed(2);
                document.getElementById('priceDisplay').innerText = "¥" + total;
            } else {
                document.getElementById('priceDisplay').innerText = "¥0";
            }
        }
    </script>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border-0">
                <div class="card-body p-4">
                    <h3 class="text-center mb-4 text-primary"><?php echo $t['sender_form']; ?></h3>
                    
                    <?php if(isset($success)): ?>
                        <div class="alert alert-success">
                            <h4><?php echo $t['success']; ?></h4>
                            <p><?php echo $t['tracking_num']; ?>: <strong><?php echo $track_num; ?></strong></p>
                            <p><?php echo $t['price']; ?>: <strong>¥<?php echo $price; ?></strong></p>
                        </div>
                    <?php endif; ?>

                    <form method="post" enctype="multipart/form-data">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo $t['name']; ?></label>
                                <input type="text" name="sender_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo $t['phone']; ?></label>
                                <input type="text" name="phone" class="form-control" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label"><?php echo $t['email']; ?></label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            
                            <!-- Shipping Details -->
                            <div class="col-md-6">
                                <label class="form-label">Weight (kg)</label>
                                <input type="number" step="0.1" name="weight" id="weight" class="form-control" required onkeyup="updatePrice()">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Dimensions (L x W x H)</label>
                                <input type="text" name="dimensions" class="form-control" placeholder="e.g. 30x20x10 cm" required>
                            </div>

                            <!-- FIXED LABELS HERE -->
                            <div class="col-md-6">
                                <label class="form-label">From (Origin)</label>
                                <input type="text" name="address_from" class="form-control" placeholder="City, Country" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">To (Destination)</label>
                                <input type="text" name="address_to" class="form-control" placeholder="City, Country" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Item Description</label>
                                <textarea name="description" class="form-control" rows="2" placeholder="What is inside the box?" required></textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Upload Photo</label>
                                <input type="file" name="photo" class="form-control">
                            </div>
                            
                            <div class="col-md-6 d-flex align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="chemicals" id="chem">
                                    <label class="form-check-label text-danger fw-bold" for="chem">
                                        Contains Liquids/Batteries/Chemicals?
                                    </label>
                                </div>
                            </div>

                            <div class="col-12 text-center mt-4">
                                <h4 class="text-muted">Estimated Price: <span id="priceDisplay" class="text-primary fw-bold">¥0</span></h4>
                                <button type="submit" class="btn btn-primary btn-lg w-100 mt-2">Submit Order</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
