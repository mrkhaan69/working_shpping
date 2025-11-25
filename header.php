<?php
require_once 'config.php';
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
        // FIXED: This function now Toggles (Opens and Closes)
        function toggleMenu() {
            var menu = document.getElementById('mobileMenu');
            if (menu.style.display === "block") {
                menu.style.display = "none";
            } else {
                menu.style.display = "block";
            }
        }
    </script>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark py-3">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <?php 
            // Check if logo setting exists in database, otherwise use default text
            $logo_stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key='site_logo'");
            $logo_file = $logo_stmt->fetchColumn();
            if($logo_file && file_exists("uploads/$logo_file")) {
                echo "<img src='uploads/$logo_file' height='40' class='me-2'>";
            } else {
                echo "<i class='bi bi-box-seam me-2'></i>";
            }
            ?>
            GlobalShipping
        </a>

        <!-- Mobile Toggle Button -->
        <button class="navbar-toggler" type="button" onclick="toggleMenu()">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Desktop Menu -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php"><?php echo $t['sender_form']; ?></a></li>
                <li class="nav-item"><a class="nav-link" href="carrier.php"><?php echo $t['carrier_form']; ?></a></li>
                <li class="nav-item"><a class="nav-link" href="track.php"><?php echo $t['track_parcel']; ?></a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-translate"></i> Lang</a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="?lang=en">English</a></li>
                        <li><a class="dropdown-item" href="?lang=fa">فارسی</a></li>
                        <li><a class="dropdown-item" href="?lang=cn">中文</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Mobile Menu Overlay -->
<div id="mobileMenu" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; bg-color: rgba(0,0,0,0.95); z-index:9999; background: #212529; padding-top: 60px;">
    <button onclick="toggleMenu()" class="btn btn-link text-white position-absolute top-0 end-0 m-3 fs-1"><i class="bi bi-x"></i></button>
    <div class="d-flex flex-column align-items-center justify-content-center h-100 gap-4 fs-4">
        <a href="index.php" class="text-white text-decoration-none" onclick="toggleMenu()"><?php echo $t['sender_form']; ?></a>
        <a href="carrier.php" class="text-white text-decoration-none" onclick="toggleMenu()"><?php echo $t['carrier_form']; ?></a>
        <a href="track.php" class="text-white text-decoration-none" onclick="toggleMenu()"><?php echo $t['track_parcel']; ?></a>
        <div class="d-flex gap-3 mt-4">
            <a href="?lang=en" class="btn btn-outline-light btn-sm">EN</a>
            <a href="?lang=fa" class="btn btn-outline-light btn-sm">FA</a>
            <a href="?lang=cn" class="btn btn-outline-light btn-sm">CN</a>
        </div>
    </div>
</div>
