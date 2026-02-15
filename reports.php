<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// Stats (tables may not exist yet if additions not run)
$stats = [];
$stats['total_prisoners'] = (int) $conn->query("SELECT COUNT(*) as c FROM prisoner")->fetch_assoc()['c'];
$stats['normal'] = (int) $conn->query("SELECT COUNT(*) as c FROM prisoner WHERE current_status='Normal'")->fetch_assoc()['c'];
$stats['paroled'] = (int) $conn->query("SELECT COUNT(*) as c FROM prisoner WHERE current_status='Paroled'")->fetch_assoc()['c'];
$stats['isolated'] = (int) $conn->query("SELECT COUNT(*) as c FROM prisoner WHERE current_status='Isolated'")->fetch_assoc()['c'];
$stats['pending_duties'] = (int) $conn->query("SELECT COUNT(*) as c FROM duty_assignment WHERE status='Pending'")->fetch_assoc()['c'];
$stats['parole_requests'] = 0;
$r = @$conn->query("SELECT COUNT(*) as c FROM parole_requests WHERE status='Pending'");
if ($r && $r->num_rows) $stats['parole_requests'] = (int) $r->fetch_assoc()['c'];
$stats['incidents'] = 0;
$r = @$conn->query("SELECT COUNT(*) as c FROM incident_report");
if ($r && $r->num_rows) $stats['incidents'] = (int) $r->fetch_assoc()['c'];
$stats['visits_today'] = 0;
$r = @$conn->query("SELECT COUNT(*) as c FROM visit_log WHERE visit_date = CURDATE() AND status IN ('Scheduled','Completed')");
if ($r && $r->num_rows) $stats['visits_today'] = (int) $r->fetch_assoc()['c'];

// Upcoming releases (sentence end in next 90 days) – use end_date or start_date + duration
$releases = [];
$rel = $conn->query("
    SELECT p.prisoner_id, p.name, s.end_date, s.duration_in_months, s.start_date
    FROM prisoner p
    JOIN sentence s ON p.prisoner_id = s.prisoner_id
    WHERE p.current_status = 'Normal'
    ORDER BY s.end_date ASC
    LIMIT 15
");
if ($rel) {
    while ($row = $rel->fetch_assoc()) {
        $end = $row['end_date'];
        if (empty($end) && !empty($row['start_date']) && !empty($row['duration_in_months'])) {
            $d = new DateTime($row['start_date']);
            $d->modify('+' . (int)$row['duration_in_months'] . ' months');
            $end = $d->format('Y-m-d');
        }
        if ($end) $releases[] = ['prisoner_id' => $row['prisoner_id'], 'name' => $row['name'], 'end_date' => $end];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Statistics</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="main-wrap">
<div class="app-container">
    <div class="page-header">
        <h1>Reports & Statistics</h1>
        <a href="admin_dashboard.php" class="btn btn-ghost">Back to Dashboard</a>
    </div>

    <div class="stats-row" style="grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); margin-bottom: 32px;">
        <div class="stat-box" style="border-color: #93c5fd;">
            <span class="stat-label">Total Prisoners</span>
            <span class="stat-value"><?php echo $stats['total_prisoners']; ?></span>
        </div>
        <div class="stat-box" style="border-color: #86efac;">
            <span class="stat-label">Normal</span>
            <span class="stat-value"><?php echo $stats['normal']; ?></span>
        </div>
        <div class="stat-box" style="border-color: #fde047;">
            <span class="stat-label">Paroled</span>
            <span class="stat-value"><?php echo $stats['paroled']; ?></span>
        </div>
        <div class="stat-box" style="border-color: #fca5a5;">
            <span class="stat-label">Isolated</span>
            <span class="stat-value"><?php echo $stats['isolated']; ?></span>
        </div>
        <div class="stat-box" style="border-color: #c4b5fd;">
            <span class="stat-label">Pending Duties</span>
            <span class="stat-value"><?php echo $stats['pending_duties']; ?></span>
        </div>
        <div class="stat-box" style="border-color: #c4b5fd;">
            <span class="stat-label">Parole Requests</span>
            <span class="stat-value"><?php echo $stats['parole_requests']; ?></span>
        </div>
        <div class="stat-box" style="border-color: #93c5fd;">
            <span class="stat-label">Incidents (total)</span>
            <span class="stat-value"><?php echo $stats['incidents']; ?></span>
        </div>
        <div class="stat-box" style="border-color: #86efac;">
            <span class="stat-label">Visits Today</span>
            <span class="stat-value"><?php echo $stats['visits_today']; ?></span>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Upcoming sentence end dates</div>
        <div class="card-body" style="padding: 0;">
            <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Prisoner ID</th><th>Name</th><th>End date</th><th>Action</th></tr></thead>
                <tbody>
                <?php
                if (count($releases) > 0) {
                    foreach ($releases as $r) {
                        echo "<tr><td>{$r['prisoner_id']}</td><td>" . htmlspecialchars($r['name']) . "</td><td>{$r['end_date']}</td><td><a href='prisoner_profile.php?id={$r['prisoner_id']}' class='btn btn-primary btn-sm'>Profile</a></td></tr>";
                    }
                } else {
                    echo "<tr><td colspan='4' style='text-align:center; padding:24px; color: var(--text-muted);'>No sentence data or no prisoners.</td></tr>";
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
