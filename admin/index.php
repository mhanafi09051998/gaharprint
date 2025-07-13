<?php
session_start();

// If the user is not logged in, redirect to the login page (which is this page)
// We will add the login check logic later. For now, we assume not logged in.
$is_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

require_once '../includes/db.php';

// Dummy admin credentials for now. In a real application, fetch from the 'admins' table.
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_HASH', password_hash('password123', PASSWORD_DEFAULT)); // Replace with a strong password

// Handle login request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if ($username === ADMIN_USERNAME && password_verify($password, ADMIN_PASSWORD_HASH)) {
        $_SESSION['admin_logged_in'] = true;
        header("Location: index.php");
        exit;
    } else {
        $login_error = "Nama pengguna atau kata sandi salah.";
    }
}

// Handle logout request
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// If not logged in, show the login form
if (!$is_logged_in) {
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Gahar Print</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; height: 100vh; }
        #login-form { width: 100%; max-width: 400px; }
        .error { color: var(--error-color); margin-top: 1rem; text-align: center; }
    </style>
</head>
<body>
    <section id="login-form">
        <form action="index.php" method="post">
            <h2>Admin Login</h2>
            <div class="form-group">
                <label for="username">Nama Pengguna</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Kata Sandi</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Login</button>
            <?php if (isset($login_error)): ?>
                <p class="error"><?php echo $login_error; ?></p>
            <?php endif; ?>
        </form>
    </section>
</body>
</html>
<?php
    exit; // Stop further execution
}

// --- LOGGED-IN ADMIN DASHBOARD ---

// --- Pagination & Search Logic ---
$limit = 20; // Orders per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Base SQL parts
$sql_select = "SELECT o.id, o.order_number, o.whatsapp_number, o.upload_date, o.status, COUNT(uf.id) AS file_count";
$sql_from = "FROM orders o LEFT JOIN uploaded_files uf ON o.id = uf.order_id";
$sql_where = "";
$params = [];
$param_types = "";

if (!empty($search_term)) {
    $sql_where = "WHERE o.order_number LIKE ? OR o.whatsapp_number LIKE ?";
    $search_like = "%" . $search_term . "%";
    $params = [$search_like, $search_like];
    $param_types = "ss";
}

$sql_group_order = "GROUP BY o.id ORDER BY o.upload_date DESC";

// Get total number of records for pagination
$total_sql = "SELECT COUNT(DISTINCT o.id) as total " . $sql_from . " " . $sql_where;
$total_stmt = $conn->prepare($total_sql);
if (!empty($params)) {
    $total_stmt->bind_param($param_types, ...$params);
}
$total_stmt->execute();
$total_result = $total_stmt->get_result()->fetch_assoc();
$total_records = $total_result['total'];
$total_pages = ceil($total_records / $limit);
$total_stmt->close();

// Get records for the current page
$sql = $sql_select . " " . $sql_from . " " . $sql_where . " " . $sql_group_order . " LIMIT ? OFFSET ?";
$param_types .= "ii";
$params[] = $limit;
$params[] = $offset;

$stmt = $conn->prepare($sql);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$allowed_statuses = ['Menunggu Verifikasi', 'Sudah Dicetak', 'Dibatalkan'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Gahar Print</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .search-pagination { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 1rem; }
        .pagination a { margin: 0 5px; color: var(--accent-color); text-decoration: none; }
        .pagination a.active { font-weight: bold; }
    </style>
</head>
<body>
    <header>
        <h1>Admin Dashboard</h1>
        <p><a href="index.php?logout=1" class="btn">Logout</a></p>
    </header>

    <main>
        <section id="order-management">
            <h2>Manajemen Pemesanan</h2>
            
            <div class="search-pagination">
                <form action="index.php" method="get" class="search-form">
                    <input type="text" name="search" placeholder="Cari No. Pesanan/WhatsApp" value="<?php echo htmlspecialchars($search_term); ?>">
                    <button type="submit" class="btn">Cari</button>
                </form>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>No. Pesanan</th>
                            <th>No. WhatsApp</th>
                            <th>Jumlah File</th>
                            <th>Tgl. Unggah</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['order_number']); ?></td>
                                    <td>+62<?php echo htmlspecialchars($row['whatsapp_number']); ?></td>
                                    <td><?php echo $row['file_count']; ?></td>
                                    <td><?php echo date("d M Y, H:i", strtotime($row['upload_date'])); ?></td>
                                    <td>
                                        <form action="update_status.php" method="post" style="display: flex; align-items: center;">
                                            <input type="hidden" name="order_id" value="<?php echo $row['id']; ?>">
                                            <select name="status" onchange="this.form.submit()">
                                                <?php foreach ($allowed_statuses as $status): ?>
                                                    <option value="<?php echo $status; ?>" <?php echo ($row['status'] === $status) ? 'selected' : ''; ?>>
                                                        <?php echo $status; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <a href="download.php?order_id=<?php echo $row['id']; ?>" class="btn">Download</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">Tidak ada pesanan ditemukan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="search-pagination">
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_term); ?>" class="<?php echo ($page == $i) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        </section>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>