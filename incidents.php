<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

$curr_user_id = $_SESSION['user_id'];
$admin_res = $conn->query("SELECT admin_id, admin_name FROM admin WHERE user_id='$curr_user_id'");
$admin_row = $admin_res->fetch_assoc();
$curr_admin_id = $admin_row['admin_id'];

$msg = '';
if (isset($_POST['add_incident'])) {
    $pid = $_POST['prisoner_id'] ?? '';
    $itype = trim($_POST['incident_type'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $idate = $_POST['incident_date'] ?? date('Y-m-d');
    $action = trim($_POST['action_taken'] ?? '');
    $sev = $_POST['severity'] ?? 'Medium';
    if ($pid && $itype) {
        $stmt = $conn->prepare("INSERT INTO incident_report (prisoner_id, admin_id, incident_type, description, incident_date, action_taken, severity) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisssss", $pid, $curr_admin_id, $itype, $desc, $idate, $action, $sev);
        if ($stmt->execute()) $msg = "Incident reported.";
        else $msg = "Error. Ensure table incident_report exists (run database_additions.sql).";
    }
}

$incidents = [];
$iq = @$conn->query("SELECT i.*, p.name as prisoner_name, a.admin_name FROM incident_report i LEFT JOIN prisoner p ON i.prisoner_id = p.prisoner_id LEFT JOIN admin a ON i.admin_id = a.admin_id ORDER BY i.incident_date DESC, i.incident_id DESC LIMIT 80");
if ($iq) while ($row = $iq->fetch_assoc()) $incidents[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incident Reports</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="main-wrap">
<div class="app-container">
    <div class="page-header">
        <h1>Incident Reports</h1>
        <a href="admin_dashboard.php" class="btn btn-ghost">Dashboard</a>
    </div>
    <?php if ($msg): ?><div class="alert <?php echo strpos($msg,'Error')!==false?'alert-error':'alert-success'; ?>"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

    <div class="card">
        <div class="card-header">Report new incident</div>
        <div class="card-body">
            <form method="post" class="form-grid" style="max-width: 100%;">
                <div>
                    <label>Prisoner</label>
                    <select name="prisoner_id" required>
                        <option value="">Select</option>
                        <?php
                        $pr = $conn->query("SELECT prisoner_id, name FROM prisoner ORDER BY name");
                        while ($p = $pr->fetch_assoc()) echo "<option value='{$p['prisoner_id']}'>" . htmlspecialchars($p['name']) . " ({$p['prisoner_id']})</option>";
                        ?>
                    </select>
                </div>
                <div>
                    <label>Incident type</label>
                    <input type="text" name="incident_type" required placeholder="e.g. Violence, Contraband, Escape attempt">
                </div>
                <div style="grid-column: 1 / -1;">
                    <label>Description</label>
                    <textarea name="description" rows="2" placeholder="What happened?"></textarea>
                </div>
                <div>
                    <label>Date</label>
                    <input type="date" name="incident_date" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div>
                    <label>Severity</label>
                    <select name="severity">
                        <option value="Low">Low</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="High">High</option>
                        <option value="Critical">Critical</option>
                    </select>
                </div>
                <div style="grid-column: 1 / -1;">
                    <label>Action taken</label>
                    <input type="text" name="action_taken" placeholder="e.g. Isolation, points deducted">
                </div>
                <button type="submit" name="add_incident" class="btn btn-danger btn-block" style="grid-column: 1 / -1;">Submit incident report</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Recent incidents</div>
        <div class="card-body" style="padding: 0;">
            <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Date</th><th>Prisoner</th><th>Type</th><th>Severity</th><th>Action taken</th><th>Reported by</th></tr></thead>
                <tbody>
                <?php
                if (count($incidents) > 0) {
                    foreach ($incidents as $i) {
                        $sev_class = $i['severity'] == 'Critical' ? 'badge-danger' : ($i['severity'] == 'High' ? 'badge-warning' : 'badge-info');
                        echo "<tr>
                            <td>{$i['incident_date']}</td>
                            <td><a href='prisoner_profile.php?id={$i['prisoner_id']}'>" . htmlspecialchars($i['prisoner_name'] ?? $i['prisoner_id']) . "</a></td>
                            <td>" . htmlspecialchars($i['incident_type']) . "</td>
                            <td><span class='badge $sev_class'>{$i['severity']}</span></td>
                            <td>" . htmlspecialchars($i['action_taken'] ?? '–') . "</td>
                            <td>" . htmlspecialchars($i['admin_name'] ?? '') . "</td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' style='text-align:center; padding:24px;'>No incidents. Run database_additions.sql if the table is missing.</td></tr>";
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
