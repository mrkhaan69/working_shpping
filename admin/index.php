<?php
// 1. Error Reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Connect (Use system path to find config one folder up)
require_once __DIR__ . '/../config.php';

// Logout Logic
if(isset($_GET['logout'])) { 
    session_destroy(); 
    header("Location: index.php"); 
    exit; 
}

// --- LOGIN LOGIC ---
if(isset($_POST['pin_submit'])) {
    $enteredPin = $_POST['p1'] . $_POST['p2'] . $_POST['p3'] . $_POST['p4'];
    if($enteredPin === '1895') {
        $_SESSION['admin'] = true;
        header("Location: index.php");
        exit;
    } else {
        $error = "Access Denied";
    }
}

// --- LOGIN SCREEN ---
if(!isset($_SESSION['admin'])) {
?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Admin Access</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { background: #1a1a2e; color: #fff; display: flex; align-items: center; justify-content: center; height: 100vh; font-family: 'Segoe UI', sans-serif; }
            .pin-box { width: 50px; height: 60px; font-size: 30px; text-align: center; border: 2px solid #0f3460; background: #16213e; color: #fff; border-radius: 10px; margin: 0 5px; transition: 0.3s; }
            .pin-box:focus { border-color: #e94560; outline: none; box-shadow: 0 0 15px rgba(233, 69, 96, 0.5); }
            .card { background: #16213e; border: 1px solid #0f3460; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
            .btn-glow { background: #e94560; border: none; color: white; transition: 0.3s; }
            .btn-glow:hover { background: #ff2e63; box-shadow: 0 0 20px #e94560; }
        </style>
        <script>
            function moveNext(from, to) {
                if(from.value.length >= 1) { if(to) document.getElementById(to).focus(); }
            }
        </script>
    </head>
    <body>
        <div class="card p-5 text-center">
            <h3 class="mb-4 text-white">SECURITY CHECK</h3>
            <form method="post">
                <div class="d-flex justify-content-center mb-4">
                    <input type="text" name="p1" id="p1" class="pin-box" maxlength="1" onkeyup="moveNext(this, 'p2')" autocomplete="off" autofocus>
                    <input type="text" name="p2" id="p2" class="pin-box" maxlength="1" onkeyup="moveNext(this, 'p3')" autocomplete="off">
                    <input type="text" name="p3" id="p3" class="pin-box" maxlength="1" onkeyup="moveNext(this, 'p4')" autocomplete="off">
                    <input type="text" name="p4" id="p4" class="pin-box" maxlength="1" autocomplete="off">
                </div>
                <button type="submit" name="pin_submit" class="btn btn-glow w-100 py-2">AUTHENTICATE</button>
                <?php if(isset($error)) echo "<p class='text-danger mt-3'>$error</p>"; ?>
            </form>
        </div>
    </body>
    </html>
<?php
    exit;
}

// --- DASHBOARD LOGIC ---

// 1. Update Settings
if(isset($_POST['update_settings'])) {
    $pdo->prepare("UPDATE settings SET setting_value=? WHERE setting_key='telegram_bot_token'")->execute([$_POST['bot_token']]);
    $pdo->prepare("UPDATE settings SET setting_value=? WHERE setting_key='telegram_chat_id'")->execute([$_POST['chat_id']]);
    $pdo->prepare("UPDATE settings SET setting_value=? WHERE setting_key='show_carriers_public'")->execute([isset($_POST['show_carriers'])?1:0]);
    if(isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        move_uploaded_file($_FILES['logo']['tmp_name'], '../uploads/logo.png');
        $pdo->query("UPDATE settings SET setting_value='logo.png' WHERE setting_key='site_logo'");
    }
    echo "<script>alert('Settings Updated');</script>";
}

// 2. Update Pricing
if(isset($_POST['update_pricing'])) {
    $pdo->prepare("UPDATE settings SET setting_value=? WHERE setting_key='price_1_5'")->execute([$_POST['price_1_5']]);
    $pdo->prepare("UPDATE settings SET setting_value=? WHERE setting_key='price_5_10'")->execute([$_POST['price_5_10']]);
    $pdo->prepare("UPDATE settings SET setting_value=? WHERE setting_key='price_10_plus'")->execute([$_POST['price_10_plus']]);
    echo "<script>alert('Pricing Updated');</script>";
}

// 3. Update Shipment Status
if(isset($_POST['update_status'])) {
    $carrier_id = !empty($_POST['carrier_id']) ? $_POST['carrier_id'] : NULL;
    $stmt = $pdo->prepare("UPDATE shipments SET status=?, carrier_id=? WHERE id=?");
    $stmt->execute([$_POST['status'], $carrier_id, $_POST['shipment_id']]);
    echo "<script>alert('Shipment Updated'); window.location.href='index.php';</script>";
}

// 4. DELETE SHIPMENT
if(isset($_POST['delete_shipment'])) {
    $stmt = $pdo->prepare("DELETE FROM shipments WHERE id=?");
    $stmt->execute([$_POST['shipment_id']]);
    echo "<script>alert('Shipment Deleted Successfully'); window.location.href='index.php';</script>";
}

// 5. DELETE CARRIER
if(isset($_POST['delete_carrier'])) {
    // Optional: Unassign shipments from this carrier first so they don't break
    $pdo->prepare("UPDATE shipments SET carrier_id = NULL WHERE carrier_id = ?")->execute([$_POST['carrier_id']]);
    
    $stmt = $pdo->prepare("DELETE FROM carriers WHERE id=?");
    $stmt->execute([$_POST['carrier_id']]);
    echo "<script>alert('Carrier Deleted Successfully'); window.location.href='index.php';</script>";
}

// Fetch Data
try {
    $shipments = $pdo->query("SELECT s.*, c.name as carrier_name, c.flight_number FROM shipments s LEFT JOIN carriers c ON s.carrier_id = c.id ORDER BY s.created_at DESC")->fetchAll();
    $carriers = $pdo->query("SELECT * FROM carriers ORDER BY id DESC")->fetchAll();
    $settings = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    die("DB Error: " . $e->getMessage());
}

function getSet($key, $arr) { return isset($arr[$key]) ? $arr[$key] : ''; }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .nav-tabs .nav-link.active { background-color: #0d6efd; color: white; }
        .nav-tabs .nav-link { color: #495057; }
        .table-responsive { font-size: 0.9rem; }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark p-3">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">System Admin</a>
            <a href="?logout" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </nav>

    <div class="container mt-4 pb-5">
        
        <ul class="nav nav-tabs mb-3 shadow-sm bg-white rounded" id="myTab" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#senders">Senders</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#carriers">Carriers</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#pricing">Pricing</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#settings">Settings</button></li>
        </ul>

        <div class="tab-content">
            
            <!-- TAB 1: SENDERS -->
            <div class="tab-pane fade show active" id="senders">
                <div class="card p-3 shadow-sm border-0">
                    <h5 class="card-title text-primary mb-3">Sender Shipments</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Track ID</th>
                                    <th>Sender Info</th>
                                    <th>Weight</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($shipments as $s): ?>
                                <tr>
                                    <td><strong><?php echo $s['tracking_number']; ?></strong></td>
                                    <td>
                                        <strong><?php echo $s['sender_name']; ?></strong><br>
                                        <?php echo $s['phone']; ?>
                                    </td>
                                    <td><?php echo $s['weight']; ?> kg</td>
                                    <td>
                                        <span class="badge <?php echo ($s['status']=='delivered')?'bg-success':'bg-warning text-dark'; ?>">
                                            <?php echo $s['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <!-- Manage Button -->
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $s['id']; ?>">Manage</button>
                                            
                                            <!-- Delete Button -->
                                            <form method="post" onsubmit="return confirm('Are you sure you want to delete this shipment? This cannot be undone.');">
                                                <input type="hidden" name="shipment_id" value="<?php echo $s['id']; ?>">
                                                <button type="submit" name="delete_shipment" class="btn btn-sm btn-outline-danger" title="Delete">🗑</button>
                                            </form>
                                        </div>

                                        <!-- Update Modal -->
                                        <div class="modal fade" id="editModal<?php echo $s['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog modal-lg">
                                                <form method="post" class="modal-content">
                                                    <div class="modal-header"><h5 class="modal-title">Shipment: <?php echo $s['tracking_number']; ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="col-md-6 border-end">
                                                                <h6 class="text-primary">Status & Carrier</h6>
                                                                <input type="hidden" name="shipment_id" value="<?php echo $s['id']; ?>">
                                                                <label class="form-label">Status</label>
                                                                <select name="status" class="form-select mb-3">
                                                                    <option value="received" <?php if($s['status']=='received') echo 'selected'; ?>>Received</option>
                                                                    <option value="handed_over" <?php if($s['status']=='handed_over') echo 'selected'; ?>>Handed Over</option>
                                                                    <option value="shipped" <?php if($s['status']=='shipped') echo 'selected'; ?>>Shipped</option>
                                                                    <option value="delivered" <?php if($s['status']=='delivered') echo 'selected'; ?>>Delivered</option>
                                                                </select>
                                                                <label class="form-label">Assign Carrier</label>
                                                                <select name="carrier_id" class="form-select mb-3">
                                                                    <option value="">-- No Carrier --</option>
                                                                    <?php foreach($carriers as $c): ?>
                                                                        <option value="<?php echo $c['id']; ?>" <?php if($s['carrier_id'] == $c['id']) echo 'selected'; ?>>
                                                                            <?php echo $c['name'] . " (" . $c['flight_number'] . ")"; ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <h6 class="text-primary">Full Details</h6>
                                                                <ul class="list-group list-group-flush small">
                                                                    <li class="list-group-item"><strong>Email:</strong> <?php echo $s['email']; ?></li>
                                                                    <li class="list-group-item"><strong>From:</strong> <?php echo $s['address_from']; ?></li>
                                                                    <li class="list-group-item"><strong>To:</strong> <?php echo $s['address_to']; ?></li>
                                                                    <li class="list-group-item"><strong>Dimensions:</strong> <?php echo $s['dimensions']; ?></li>
                                                                    <li class="list-group-item"><strong>Description:</strong> <?php echo $s['description']; ?></li>
                                                                    <li class="list-group-item"><strong>Chemicals:</strong> <?php echo $s['has_chemicals'] ? 'Yes' : 'No'; ?></li>
                                                                    <li class="list-group-item"><strong>Est Price:</strong> ¥<?php echo $s['price']; ?></li>
                                                                </ul>
                                                                <?php if($s['photo_path']): ?>
                                                                    <div class="mt-2"><a href="../uploads/<?php echo $s['photo_path']; ?>" target="_blank" class="btn btn-sm btn-dark w-100">View Parcel Photo</a></div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer"><button type="submit" name="update_status" class="btn btn-primary">Save Changes</button></div>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 2: CARRIERS -->
            <div class="tab-pane fade" id="carriers">
                <div class="card p-3 shadow-sm border-0">
                    <h5 class="card-title text-success mb-3">Registered Carriers</h5>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Name / Flight</th>
                                    <th>Date</th>
                                    <th>Route</th>
                                    <th>Cap (Kg) / Dims</th>
                                    <th>Contact</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($carriers as $c): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $c['name']; ?></strong><br>
                                        <span class="badge bg-dark"><?php echo $c['flight_number']; ?></span>
                                    </td>
                                    <td><?php echo $c['flight_date']; ?></td>
                                    <td>
                                        <small class="d-block"><strong>From:</strong> <?php echo $c['address_from']; ?></small>
                                        <small class="d-block"><strong>To:</strong> <?php echo $c['address_to']; ?></small>
                                    </td>
                                    <td>
                                        <div><?php echo isset($c['weight_capacity']) ? $c['weight_capacity'] : '0'; ?> kg</div>
                                        <small class="text-muted"><?php echo isset($c['max_dimensions']) ? $c['max_dimensions'] : '-'; ?></small>
                                    </td>
                                    <td>
                                        <small><?php echo $c['email']; ?></small><br>
                                        <small><?php echo $c['phone']; ?></small>
                                    </td>
                                    <td>
                                        <!-- Delete Button -->
                                        <form method="post" onsubmit="return confirm('Are you sure you want to delete this carrier?');">
                                            <input type="hidden" name="carrier_id" value="<?php echo $c['id']; ?>">
                                            <button type="submit" name="delete_carrier" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 3: PRICING -->
            <div class="tab-pane fade" id="pricing">
                <div class="card p-4 shadow-sm border-0">
                    <h5 class="card-title text-danger mb-4">Price Estimation Settings (CNY / Yuan)</h5>
                    <form method="post">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">1 - 5 KG (Rate per KG)</label>
                                <div class="input-group">
                                    <span class="input-group-text">¥</span>
                                    <input type="number" step="0.01" name="price_1_5" class="form-control" value="<?php echo getSet('price_1_5', $settings); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">5 - 10 KG (Rate per KG)</label>
                                <div class="input-group">
                                    <span class="input-group-text">¥</span>
                                    <input type="number" step="0.01" name="price_5_10" class="form-control" value="<?php echo getSet('price_5_10', $settings); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">10+ KG (Rate per KG)</label>
                                <div class="input-group">
                                    <span class="input-group-text">¥</span>
                                    <input type="number" step="0.01" name="price_10_plus" class="form-control" value="<?php echo getSet('price_10_plus', $settings); ?>">
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="update_pricing" class="btn btn-danger mt-4">Update Prices</button>
                    </form>
                </div>
            </div>

            <!-- TAB 4: SETTINGS -->
            <div class="tab-pane fade" id="settings">
                <div class="card p-4 shadow-sm border-0">
                    <h5 class="card-title text-secondary mb-3">General Configuration</h5>
                    <form method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Telegram Bot Token</label>
                            <input type="text" name="bot_token" class="form-control" value="<?php echo getSet('telegram_bot_token', $settings); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Telegram Chat ID</label>
                            <input type="text" name="chat_id" class="form-control" value="<?php echo getSet('telegram_chat_id', $settings); ?>">
                        </div>
                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="show_carriers" id="sc" <?php echo (getSet('show_carriers_public', $settings) == '1') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="sc">Show Carriers Table to Public?</label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Website Logo</label>
                            <input type="file" name="logo" class="form-control">
                        </div>
                        <button type="submit" name="update_settings" class="btn btn-secondary">Save Configuration</button>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
