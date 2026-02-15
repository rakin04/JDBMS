<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

$curr_user_id = $_SESSION['user_id'];
$admin_res = $conn->query("SELECT admin_id FROM admin WHERE user_id='$curr_user_id'");
$admin_row = $admin_res ? $admin_res->fetch_assoc() : null;
$curr_admin_id = $admin_row ? $admin_row['admin_id'] : null;

$msg = '';
if (isset($_POST['log_visit'])) {
    $pid = trim($_POST['prisoner_id'] ?? '');
    $vname = trim($_POST['visitor_name'] ?? '');
    $vrelation = trim($_POST['relation_to_prisoner'] ?? '');
    $vphone = trim($_POST['visitor_phone'] ?? '');
    $vdate = $_POST['visit_date'] ?? '';
    $duration = (int) ($_POST['duration_minutes'] ?? 30);
    $notes = trim($_POST['notes'] ?? '');
    if ($pid && $vname && $vdate) {
        // Check if visit_log has inline visitor columns (after running database_visit_log_update.sql)
        $has_inline = false;
        $cols = @$conn->query("SHOW COLUMNS FROM visit_log LIKE 'visitor_name'");
        if ($cols && $cols->num_rows > 0) $has_inline = true;
        if ($has_inline) {
            $stmt = $conn->prepare("INSERT INTO visit_log (prisoner_id, visitor_name, relation_to_prisoner, visitor_phone, visit_date, duration_minutes, notes, logged_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssisi", $pid, $vname, $vrelation, $vphone, $vdate, $duration, $notes, $curr_admin_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO visit_log (prisoner_id, visit_date, duration_minutes, notes, logged_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssisi", $pid, $vdate, $duration, $notes, $curr_admin_id);
        }
        if ($stmt->execute()) $msg = "Visit logged.";
        else $msg = "Error. Run database_visit_log_update.sql to add visitor columns.";
    } else {
        $msg = "Prisoner, visitor name and date are required.";
    }
}
if (isset($_POST['update_status'])) {
    $visit_id = (int) $_POST['visit_id'];
    $status = $conn->real_escape_string($_POST['status']);
    $conn->query("UPDATE visit_log SET status='$status' WHERE visit_id=$visit_id");
    $msg = "Visit status updated.";
}

// All prisoners for the dropdown (search will filter via JS)
$prisoners = [];
$pr = $conn->query("SELECT prisoner_id, name, cell_no FROM prisoner ORDER BY name");
if ($pr) while ($p = $pr->fetch_assoc()) $prisoners[] = $p;

$visits_list = [];
$vq = @$conn->query("SELECT vl.*, v.visitor_name as v_name, v.relation_to_prisoner as v_relation, p.name as prisoner_name FROM visit_log vl LEFT JOIN visitor v ON vl.visitor_id = v.visitor_id LEFT JOIN prisoner p ON vl.prisoner_id = p.prisoner_id ORDER BY vl.visit_date DESC, vl.visit_id DESC LIMIT 50");
if ($vq) {
    while ($row = $vq->fetch_assoc()) {
        $row['display_visitor_name'] = !empty($row['visitor_name']) ? $row['visitor_name'] : ($row['v_name'] ?? '');
        $row['display_relation'] = !empty($row['relation_to_prisoner']) ? $row['relation_to_prisoner'] : ($row['v_relation'] ?? '');
        $visits_list[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Visits</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="main-wrap">
<div class="app-container">
    <div class="page-header">
        <h1>Log Visits</h1>
        <a href="admin_dashboard.php" class="btn btn-ghost">Dashboard</a>
    </div>
    <?php if ($msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

    <div class="card">
        <div class="card-header">Log a visit</div>
        <div class="card-body">
            <form method="post" id="log-visit-form">
                <label for="prisoner_search">Search prisoner (by name or ID)</label>
                <input type="text" id="prisoner_search" placeholder="Type to filter list..." autocomplete="off" style="margin-bottom: 8px;">

                <label for="prisoner_id">Prisoner</label>
                <select name="prisoner_id" id="prisoner_id" required>
                    <option value="">— Select prisoner —</option>
                    <?php foreach ($prisoners as $p): ?>
                        <option value="<?php echo htmlspecialchars($p['prisoner_id']); ?>" data-name="<?php echo htmlspecialchars($p['name']); ?>" data-id="<?php echo htmlspecialchars($p['prisoner_id']); ?>">
                            <?php echo htmlspecialchars($p['prisoner_id']); ?> – <?php echo htmlspecialchars($p['name']); ?> (Cell: <?php echo htmlspecialchars($p['cell_no'] ?? '–'); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <span class="section-subhead" style="margin-top: 20px;">Visitor information</span>
                <label>Visitor name</label>
                <input type="text" name="visitor_name" required placeholder="Full name">
                <label>Relation to prisoner</label>
                <input type="text" name="relation_to_prisoner" placeholder="e.g. Spouse, Lawyer, Parent">
                <label>Visitor phone</label>
                <input type="text" name="visitor_phone" placeholder="Contact number">

                <label>Visit date</label>
                <input type="date" name="visit_date" required value="<?php echo date('Y-m-d'); ?>">
                <label>Duration (minutes)</label>
                <input type="number" name="duration_minutes" value="30" min="5" max="120">
                <label>Notes</label>
                <input type="text" name="notes" placeholder="Optional">
                <button type="submit" name="log_visit" class="btn btn-primary btn-block">Log visit</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Recent visits</div>
        <div class="card-body" style="padding: 0;">
            <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Date</th><th>Prisoner</th><th>Visitor</th><th>Relation</th><th>Duration</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                <?php
                if (count($visits_list) > 0) {
                    foreach ($visits_list as $v) {
                        $badge = $v['status'] == 'Completed' ? 'badge-success' : ($v['status'] == 'Cancelled' ? 'badge-danger' : 'badge-warning');
                        $visitor_name = $v['display_visitor_name'] ?? '';
                        $relation = $v['display_relation'] ?? '';
                        echo "<tr>
                            <td>{$v['visit_date']}</td>
                            <td>" . htmlspecialchars($v['prisoner_name'] ?? $v['prisoner_id']) . " ({$v['prisoner_id']})</td>
                            <td>" . htmlspecialchars($visitor_name) . "</td>
                            <td>" . htmlspecialchars($relation) . "</td>
                            <td>{$v['duration_minutes']} min</td>
                            <td><span class='badge $badge'>{$v['status']}</span></td>
                            <td>
                                <form method='post' class='inline'>
                                    <input type='hidden' name='visit_id' value='{$v['visit_id']}'>
                                    <select name='status' onchange='this.form.submit()' style='padding:4px; font-size:12px;'>
                                        <option value='Scheduled'" . ($v['status']=='Scheduled'?' selected':'') . ">Scheduled</option>
                                        <option value='Completed'" . ($v['status']=='Completed'?' selected':'') . ">Completed</option>
                                        <option value='Cancelled'" . ($v['status']=='Cancelled'?' selected':'') . ">Cancelled</option>
                                        <option value='No-show'" . ($v['status']=='No-show'?' selected':'') . ">No-show</option>
                                    </select>
                                    <input type='hidden' name='update_status' value='1'>
                                </form>
                            </td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='7' style='text-align:center; padding:24px;'>No visits yet.</td></tr>";
                }
                ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>
</div>
<script>
(function() {
    var search = document.getElementById('prisoner_search');
    var select = document.getElementById('prisoner_id');
    if (!search || !select) return;
    var options = [].slice.call(select.querySelectorAll('option'));
    options.shift(); // remove first "Select prisoner"
    search.addEventListener('input', function() {
        var q = this.value.trim().toLowerCase();
        options.forEach(function(opt) {
            var name = (opt.getAttribute('data-name') || '').toLowerCase();
            var id = (opt.getAttribute('data-id') || '').toLowerCase();
            var show = !q || name.indexOf(q) !== -1 || id.indexOf(q) !== -1;
            opt.style.display = show ? '' : 'none';
            opt.disabled = show ? false : true;
        });
        if (q && select.value === '') select.options[1] && (select.selectedIndex = 1);
    });
})();
</script>
<?php include 'includes/footer.php'; ?>
</body>
</html>
