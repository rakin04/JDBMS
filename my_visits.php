<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'prisoner') {
    header("Location: index.php");
    exit();
}

$uid = $_SESSION['user_id'];
$p_data = $conn->query("SELECT prisoner_id, name FROM prisoner WHERE user_id='$uid'")->fetch_assoc();
if (!$p_data) {
    header("Location: prisoner_dashboard.php");
    exit();
}
$pid = $p_data['prisoner_id'];

$visits = [];
$vq = @$conn->query("SELECT vl.visit_date, vl.duration_minutes, vl.status,
    COALESCE(vl.visitor_name, v.visitor_name) AS visitor_name,
    COALESCE(vl.relation_to_prisoner, v.relation_to_prisoner) AS relation_to_prisoner
    FROM visit_log vl LEFT JOIN visitor v ON vl.visitor_id = v.visitor_id
    WHERE vl.prisoner_id = '$pid' ORDER BY vl.visit_date DESC");
if ($vq) while ($row = $vq->fetch_assoc()) $visits[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Visits</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="main-wrap">
<div class="page-container">
    <div class="page-header">
        <h1>My Visits</h1>
        <a href="prisoner_dashboard.php" class="btn btn-ghost">Dashboard</a>
    </div>

    <div class="card">
        <div class="card-header">Visit history</div>
        <div class="card-body" style="padding: 0;">
            <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Date</th><th>Visitor</th><th>Relation</th><th>Duration</th><th>Status</th></tr></thead>
                <tbody>
                <?php
                if (count($visits) > 0) {
                    foreach ($visits as $v) {
                        $badge = $v['status'] == 'Completed' ? 'badge-success' : ($v['status'] == 'Cancelled' || $v['status'] == 'No-show' ? 'badge-danger' : 'badge-warning');
                        echo "<tr>
                            <td>{$v['visit_date']}</td>
                            <td>" . htmlspecialchars($v['visitor_name'] ?? '–') . "</td>
                            <td>" . htmlspecialchars($v['relation_to_prisoner'] ?? '–') . "</td>
                            <td>{$v['duration_minutes']} min</td>
                            <td><span class='badge $badge'>{$v['status']}</span></td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' style='text-align:center; padding:24px;'>No visits recorded.</td></tr>";
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
