<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Handle Add Category
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $name = $_POST['category_name'];
    $p1 = $_POST['price_1_5'];
    $p2 = $_POST['price_5_10'];
    $p3 = $_POST['price_10_plus'];

    $stmt = $conn->prepare("INSERT INTO pricing_categories (category_name, price_1_5, price_5_10, price_10_plus) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sddd", $name, $p1, $p2, $p3);
    $stmt->execute();
    header("Location: manage_pricing.php");
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM pricing_categories WHERE id = $id");
    header("Location: manage_pricing.php");
    exit;
}

$cats = $conn->query("SELECT * FROM pricing_categories ORDER BY id DESC");
?>
<?php include 'header.php'; ?>

<div class="container mt-4">
    <h3>Manage Pricing Categories</h3>
    
    <div class="row">
        <!-- Add Form -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">Add Category</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label>Category Name</label>
                            <input type="text" name="category_name" class="form-control" placeholder="e.g. Electronics" required>
                        </div>
                        <div class="mb-3">
                            <label>Price (1-5 KG)</label>
                            <input type="number" step="0.01" name="price_1_5" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Price (5-10 KG)</label>
                            <input type="number" step="0.01" name="price_5_10" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Price (10 KG+)</label>
                            <input type="number" step="0.01" name="price_10_plus" class="form-control" required>
                        </div>
                        <button type="submit" name="add_category" class="btn btn-success w-100">Save Category</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- List -->
        <div class="col-md-8">
            <table class="table table-bordered table-striped bg-white">
                <thead class="table-dark">
                    <tr>
                        <th>Category</th>
                        <th>1-5 KG</th>
                        <th>5-10 KG</th>
                        <th>10 KG+</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $cats->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['category_name']; ?></td>
                        <td><?php echo $row['price_1_5']; ?></td>
                        <td><?php echo $row['price_5_10']; ?></td>
                        <td><?php echo $row['price_10_plus']; ?></td>
                        <td>
                            <a href="manage_pricing.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete?');">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
