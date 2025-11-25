<?php
require_once 'config.php';
$result = null;

if(isset($_GET['tracking_number'])) {
    $track_num = trim($_GET['tracking_number']);
    $stmt = $pdo->prepare("SELECT * FROM shipments WHERE tracking_number = ?");
    $stmt->execute([$track_num]);
    $result = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $t['dir']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['track_parcel']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Icon Progress Bar Styles */
        .track-steps { display: flex; justify-content: space-between; margin-top: 50px; position: relative; }
        .track-steps::before { content: ''; position: absolute; top: 25px; left: 0; width: 100%; height: 4px; background: #e9ecef; z-index: 1; }
        .step { position: relative; z-index: 2; text-align: center; background: #fff; width: 25%; }
        .step-icon { width: 50px; height: 50px; border-radius: 50%; background: #e9ecef; color: #6c757d; display: inline-flex; align-items: center; justify-content: center; font-size: 24px; border: 4px solid #fff; }
        .step-text { display: block; margin-top: 10px; font-size: 14px; font-weight: 600; color: #adb5bd; }
        
        /* Active State */
        .step.active .step-icon { background: #0d6efd; color: #fff; box-shadow: 0 0 0 4px #cce5ff; }
        .step.active .step-text { color: #0d6efd; }
        .track-steps.s1::before { background: linear-gradient(to right, #0d6efd 0%, #0d6efd 15%, #e9ecef 15%); }
        .track-steps.s2::before { background: linear-gradient(to right, #0d6efd 0%, #0d6efd 50%, #e9ecef 50%); }
        .track-steps.s3::before { background: linear-gradient(to right, #0d6efd 0%, #0d6efd 85%, #e9ecef 85%); }
        .track-steps.s4::before { background: #0d6efd; }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg border-0">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4 text-primary"><?php echo $t['track_parcel']; ?></h2>
                    
                    <form method="get" class="d-flex gap-2 mb-5">
                        <input type="text" name="tracking_number" class="form-control form-control-lg" placeholder="<?php echo $t['tracking_num']; ?>" required>
                        <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-search"></i></button>
                    </form>

                    <?php if($result): 
                        // Logic to determine progress step
                        $status = $result['status'];
                        $step = 0;
                        if($status == 'received') $step = 1;
                        if($status == 'handed_over') $step = 2;
                        if($status == 'shipped') $step = 3;
                        if($status == 'delivered') $step = 4;
                    ?>
                        <div class="text-center mb-4">
                            <h5><?php echo $t['tracking_num']; ?>: <span class="badge bg-dark"><?php echo $result['tracking_number']; ?></span></h5>
                        </div>

                        <!-- Icon Progress Bar -->
                        <div class="track-steps s<?php echo $step; ?>">
                            
                            <!-- Step 1: Received -->
                            <div class="step <?php if($step >= 1) echo 'active'; ?>">
                                <div class="step-icon"><i class="bi bi-box-seam"></i></div>
                                <span class="step-text">Received</span>
                            </div>

                            <!-- Step 2: Handed Over -->
                            <div class="step <?php if($step >= 2) echo 'active'; ?>">
                                <div class="step-icon"><i class="bi bi-person-check"></i></div>
                                <span class="step-text">Handed Over</span>
                            </div>

                            <!-- Step 3: Shipped -->
                            <div class="step <?php if($step >= 3) echo 'active'; ?>">
                                <div class="step-icon"><i class="bi bi-airplane"></i></div>
                                <span class="step-text">In Flight</span>
                            </div>

                            <!-- Step 4: Delivered -->
                            <div class="step <?php if($step >= 4) echo 'active'; ?>">
                                <div class="step-icon"><i class="bi bi-house-check"></i></div>
                                <span class="step-text">Delivered</span>
                            </div>

                        </div>

                        <div class="mt-5 p-3 bg-light rounded">
                            <div class="row">
                                <div class="col-6"><strong>Sender:</strong> <?php echo $result['sender_name']; ?></div>
                                <div class="col-6 text-end"><strong>Weight:</strong> <?php echo $result['weight']; ?> kg</div>
                                <div class="col-12 mt-2 text-muted small">Last Update: <?php echo $result['updated_at']; ?></div>
                            </div>
                        </div>

                    <?php elseif(isset($_GET['tracking_number'])): ?>
                        <div class="alert alert-danger text-center">Tracking number not found.</div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
