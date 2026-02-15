<?php
session_start();
include 'db.php';

// Security check
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// --- FILTER & SEARCH LOGIC ---
$sql_query = "SELECT * FROM prisoner WHERE 1=1";
$filter_mode = "All";
$search_term = "";

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = mysqli_real_escape_string($conn, $_GET['search']);
    $sql_query .= " AND (prisoner_id = '$search_term' OR name LIKE '%$search_term%')";
    $filter_mode = "Search Results: '$search_term'";
}
elseif (isset($_GET['filter']) && $_GET['filter'] == 'pending') {
    $sql_query = "SELECT DISTINCT p.* FROM prisoner p 
                  JOIN duty_assignment da ON p.prisoner_id = da.prisoner_id 
                  WHERE da.status = 'Pending'";
    $filter_mode = "Pending Approvals";
}
// NEW: Filter for Parole Requests
elseif (isset($_GET['filter']) && $_GET['filter'] == 'parole_req') {
    $sql_query = "SELECT DISTINCT p.* FROM prisoner p 
                  JOIN parole_requests pr ON p.prisoner_id = pr.prisoner_id 
                  WHERE pr.status = 'Pending'";
    $filter_mode = "Parole Requests";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="main-wrap">
<div class="app-container">
    <div class="page-header">
        <h1>Admin Dashboard</h1>
        <div class="header-actions">
            <a href="add_prisoner.php" class="btn btn-success">+ Add Prisoner</a>
            <a href="logout.php" class="btn btn-ghost">Logout</a>
        </div>
    </div>

    <div class="tools-bar">
        <div class="filter-links">
            <span style="color: var(--text-muted); font-size: 0.875rem;">View:</span>
            <a href="admin_dashboard.php" class="<?php if($filter_mode=='All') echo 'active'; ?>">All</a>
            <a href="admin_dashboard.php?filter=pending" class="<?php if($filter_mode=='Pending Approvals') echo 'active'; ?>">Pending</a>
            <a href="admin_dashboard.php?filter=parole_req" class="<?php if($filter_mode=='Parole Requests') echo 'active'; ?>">Parole</a>
        </div>

        <form class="search-form" method="get" action="admin_dashboard.php">
            <input type="text" name="search" class="search-input" placeholder="Search ID or name…" value="<?php echo htmlspecialchars($search_term); ?>">
            <button type="submit" class="btn btn-secondary">Search</button>
            <?php if(!empty($search_term)): ?>
                <a href="admin_dashboard.php" class="btn btn-ghost">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card">
        <div class="card-body" style="padding: 0;">
    <div class="table-wrap">
    <table class="data-table">
        <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Cell</th>
            <th>Points</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $result = $conn->query($sql_query);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $pid = $row['prisoner_id'];
                
                // Check for pending duty assignments
                $pend_q = $conn->query("SELECT COUNT(*) as cnt FROM duty_assignment WHERE prisoner_id='$pid' AND status='Pending'");
                $pending_count = $pend_q->fetch_assoc()['cnt'];

                // Check for parole requests
                $req_q = $conn->query("SELECT COUNT(*) as cnt FROM parole_requests WHERE prisoner_id='$pid' AND status='Pending'");
                $req_count = $req_q->fetch_assoc()['cnt'];

                echo "<tr>
                    <td>{$row['prisoner_id']}</td>
                    <td>{$row['name']}</td>
                    <td>{$row['cell_no']}</td>
                    <td>{$row['total_points']}</td>
                    <td><strong>{$row['current_status']}</strong></td>
                    <td>
                        <a href='prisoner_profile.php?id={$row['prisoner_id']}' class='btn btn-primary btn-sm'>
                            Profile " . ($pending_count > 0 ? "<span class='badge badge-danger'>$pending_count</span>" : "") . "
                        </a>
                        <span style='margin-left: 8px;'>
                            " . ($req_count > 0 ? "<span class='req-flag'>Parole requested</span>" : "") . "
                            <a href='evaluate_prisoner.php?id={$row['prisoner_id']}' class='btn btn-secondary btn-sm'>Evaluate</a>
                        </span>
                    </td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='6' style='text-align:center; padding:32px; color: var(--text-muted);'>No prisoners found.</td></tr>";
        }
        ?>
    </tbody>
    </table>
    </div>
        </div>
    </div>
</div>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>