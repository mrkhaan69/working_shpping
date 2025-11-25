<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Input Data
    $name = $_POST['name'];
    $flight_number = $_POST['flight_number'];
    $flight_date = $_POST['flight_date'];
    $from = $_POST['address_from'];
    $to = $_POST['address_to'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $capacity = $_POST['weight_capacity'];
    $max_dims = $_POST['max_dimensions'];

    // Insert into Database
    $sql = "INSERT INTO carriers (name, flight_number, flight_date, address_from, address_to, email, phone, weight_capacity, max_dimensions) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $flight_number, $flight_date, $from, $to, $email, $phone, $capacity, $max_dims]);

    // --- DETAILED TELEGRAM MESSAGE ---
    $msg = "✈️ *NEW CARRIER REGISTERED*\n";
    $msg .= "──────────────────\n";
    $msg .= "👤 *Name:* $name\n";
    $msg .= "🎫 *Flight:* `$flight_number`\n";
    $msg .= "📅 *Date:* $flight_date\n";
    $msg .= "──────────────────\n";
    $msg .= "🛫 *From:* $from\n";
    $msg .= "🛬 *To:* $to\n";
    $msg .= "──────────────────\n";
    $msg .= "⚖️ *Cap:* $capacity kg\n";
    $msg .= "📦 *Max Dims:* $max_dims\n";
    $msg .= "📱 *Phone:* $phone\n";
    $msg .= "📧 *Email:* $email";

    sendTelegram($msg, $pdo);
    // --------------------------------

    $success = true;
}

// Fetch carriers for Public Table (if enabled in settings)
$show_public = $pdo->query("SELECT setting_value FROM settings WHERE setting_key='show_carriers_public'")->fetchColumn();
$carriers = [];
if($show_public == '1') {
    $carriers = $pdo->query("SELECT * FROM carriers WHERE flight_date >= CURDATE() ORDER BY flight_date ASC")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $t['dir']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['carrier_form']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'header.php'; ?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border-0">
                <div class="card-body p-4">
                    <h3 class="text-center mb-4 text-success"><?php echo $t['carrier_form']; ?></h3>
                    
                    <?php if(isset($success)): ?>
                        <div class="alert alert-success">
                            Registered Successfully! We will contact you shortly.
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo $t['name']; ?></label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Flight Number</label>
                                <input type="text" name="flight_number" class="form-control" placeholder="e.g. EK123" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Date of Travel</label>
                                <input type="date" name="flight_date" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Available Weight (kg)</label>
                                <input type="number" step="0.1" name="weight_capacity" class="form-control" required>
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
                                <label class="form-label">Max Luggage Dimensions</label>
                                <input type="text" name="max_dimensions" class="form-control" placeholder="e.g. Max suitcase size 28 inches">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label"><?php echo $t['email']; ?></label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo $t['phone']; ?></label>
                                <input type="text" name="phone" class="form-control" required>
                            </div>

                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-success w-100 py-2">Register Flight</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Public List (Optional) -->
            <?php if($show_public == '1' && !empty($carriers)): ?>
                <div class="mt-5">
                    <h4 class="mb-3">Upcoming Flights</h4>
                    <div class="list-group">
                        <?php foreach($carriers as $c): ?>
                            <div class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1"><?php echo $c['address_from']; ?> <i class="bi bi-arrow-right"></i> <?php echo $c['address_to']; ?></h5>
                                    <small><?php echo $c['flight_date']; ?></small>
                                </div>
                                <p class="mb-1">Flight: <?php echo $c['flight_number']; ?> | Cap: <?php echo $c['weight_capacity']; ?>kg</p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

</body>
</html>
