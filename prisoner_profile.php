<?php
session_start();
include 'db.php';

// Security: Only Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') { 
    header("Location: index.php"); 
    exit(); 
}

$pid = $_GET['id'];
$curr_user_id = $_SESSION['user_id'];

// Get Current Admin ID & Name
$admin_res = $conn->query("SELECT admin_id, admin_name FROM admin WHERE user_id='$curr_user_id'");
$admin_row = $admin_res->fetch_assoc();
$curr_admin_id = $admin_row['admin_id'];

// --- DELETE LOGIC ---
if (isset($_POST['delete_prisoner'])) {
    // 1. Get the User ID associated with this prisoner
    $u_query = $conn->query("SELECT user_id FROM prisoner WHERE prisoner_id='$pid'");
    $u_data = $u_query->fetch_assoc();
    $target_uid = $u_data['user_id'];

    if ($target_uid) {
        // 2. Delete the User Account (Cascade will delete prisoner data)
        if ($conn->query("DELETE FROM user_account WHERE user_id='$target_uid'")) {
            echo "<script>alert('Prisoner and User Account deleted successfully.'); window.location.href='admin_dashboard.php';</script>";
            exit();
        } else {
            echo "<script>alert('Error deleting user: " . $conn->error . "');</script>";
        }
    } else {
        // Fallback: Just delete prisoner record if no user link
        $conn->query("DELETE FROM prisoner WHERE prisoner_id='$pid'");
        echo "<script>alert('Prisoner record deleted (No linked user found).'); window.location.href='admin_dashboard.php';</script>";
        exit();
    }
}

// --- APPROVE LOGIC (Fixed with Redirect) ---
if (isset($_POST['approve_work'])) {
    $assign_id = $_POST['assign_id'];
    $hours = $_POST['hours']; 

    $conn->begin_transaction();
    try {
        $conn->query("UPDATE duty_assignment SET status='Approved', hours_completed='$hours', admin_id='$curr_admin_id' WHERE assignment_id='$assign_id'");
        $reason = "Completed Duty Assignment ID: $assign_id";
        $conn->query("INSERT INTO behavior_record (prisoner_id, admin_id, points_change, reason) VALUES ('$pid', '$curr_admin_id', '$hours', '$reason')");
        $conn->query("UPDATE prisoner SET total_points = total_points + $hours WHERE prisoner_id='$pid'");
        $conn->commit();
        
        // STORE MESSAGE & REDIRECT
        $_SESSION['flash_msg'] = "Duty Approved & Points Awarded successfully.";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['flash_msg'] = "Error updating record.";
    }
    
    // REDIRECT TO SELF (Clears POST data)
    header("Location: prisoner_profile.php?id=$pid");
    exit();
}

// --- FETCH DATA ---
$p_data = $conn->query("SELECT * FROM prisoner WHERE prisoner_id='$pid'")->fetch_assoc();
if (!$p_data) { die("Prisoner not found."); } // Safety check

$crimes = $conn->query("SELECT * FROM crime WHERE prisoner_id='$pid'");
$sentences = $conn->query("SELECT * FROM sentence WHERE prisoner_id='$pid'");
$duties = $conn->query("SELECT da.*, d.duty_name FROM duty_assignment da JOIN duty d ON da.duty_id = d.duty_id WHERE da.prisoner_id='$pid' ORDER BY da.assignment_id DESC");
$behavior_log = $conn->query("SELECT b.*, a.admin_name FROM behavior_record b LEFT JOIN admin a ON b.admin_id = a.admin_id WHERE b.prisoner_id='$pid' ORDER BY b.record_date DESC");
$eval_log = $conn->query("SELECT pe.*, a.admin_name FROM parole_evaluation pe LEFT JOIN admin a ON pe.admin_id = a.admin_id WHERE pe.prisoner_id='$pid' ORDER BY pe.evaluation_date DESC");
$incident_log = [];
$iq = @$conn->query("SELECT * FROM incident_report WHERE prisoner_id='$pid' ORDER BY incident_date DESC");
if ($iq) while ($row = $iq->fetch_assoc()) $incident_log[] = $row;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile: <?php echo htmlspecialchars($p_data['name']); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="main-wrap">

<!-- SUCCESS POPUP LOGIC -->
<?php if(isset($_SESSION['flash_msg'])): ?>
    <script>
        alert("<?php echo $_SESSION['flash_msg']; ?>");
    </script>
    <?php unset($_SESSION['flash_msg']); // Clear message ?>
<?php endif; ?>

<div class="app-container">
    <div class="page-header">
        <a href="admin_dashboard.php" class="btn btn-ghost">&larr; Dashboard</a>
        <div class="header-actions">
            <a href="edit_prisoner.php?id=<?php echo $pid; ?>" class="btn btn-secondary btn-sm">Edit Profile</a>
            <form method="post" class="inline" onsubmit="return confirm('Permanently delete this prisoner and all related records?');">
                <button type="submit" name="delete_prisoner" class="btn btn-danger btn-sm">Delete</button>
            </form>
        </div>
    </div>

    <!-- 1. HEADER & KEY STATS -->
    <div class="card">
        <div class="card-header">
            <?php echo $p_data['name']; ?> 
            <span class="id-badge">ID: <?php echo $p_data['prisoner_id']; ?></span>
        </div>
        <div class="card-body">
            <div class="stats-row">
                <div class="stat-box">
                    <span class="stat-label">Current Status</span>
                    <span class="stat-value status-<?php echo $p_data['current_status']; ?>"><?php echo $p_data['current_status']; ?></span>
                </div>
                <div class="stat-box">
                    <span class="stat-label">Total Points</span>
                    <span class="stat-value"><?php echo $p_data['total_points']; ?></span>
                </div>
                <div class="stat-box">
                    <span class="stat-label">Cell Block</span>
                    <span class="stat-value"><?php echo $p_data['cell_no']; ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. PERSONAL & FAMILY INFORMATION -->
    <div class="card">
        <div class="card-header">Personal & Family Information</div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
                <!-- Column 1 -->
                <div>
                    <span class="section-subhead">Identity</span>
                    <table class="info-table">
                        <tr><td class="info-label">Date of Birth:</td><td><?php echo $p_data['dob']; ?></td></tr>
                        <tr><td class="info-label">Gender:</td><td><?php echo $p_data['gender']; ?></td></tr>
                        <tr><td class="info-label">Height:</td><td><?php echo $p_data['height_cm']; ?> cm</td></tr>
                        <tr><td class="info-label">Weight:</td><td><?php echo $p_data['weight_kg']; ?> kg</td></tr>
                        <tr><td class="info-label">Blood Group:</td><td><?php echo $p_data['blood_group']; ?></td></tr>
                        <tr><td class="info-label">Eye Color:</td><td><?php echo $p_data['eye_color']; ?></td></tr>
                        <tr><td class="info-label">Hair Color:</td><td><?php echo $p_data['hair_color']; ?></td></tr>
                    </table>
                </div>
                <!-- Column 2 -->
                <div>
                    <span class="section-subhead">Family & Contact</span>
                    <table class="info-table">
                        <tr><td class="info-label">Father's Name:</td><td><?php echo $p_data['father_name']; ?></td></tr>
                        <tr><td class="info-label">Mother's Name:</td><td><?php echo $p_data['mother_name']; ?></td></tr>
                        <tr><td class="info-label">Emergency Contact:</td><td><?php echo $p_data['emergency_contact_name']; ?></td></tr>
                        <tr><td class="info-label">Emergency Phone:</td><td><?php echo $p_data['emergency_contact_no']; ?></td></tr>
                        <tr><td class="info-label">Present Address:</td><td><?php echo $p_data['present_address']; ?></td></tr>
                        <tr><td class="info-label">Permanent Addr:</td><td><?php echo $p_data['permanent_address']; ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. LEGAL DETAILS (Crimes & Sentences) -->
    <div class="details-grid">
        <div class="card">
            <div class="card-header">Crimes Committed</div>
            <div class="card-body">
                <ul class="unstyled">
                <?php while($c = $crimes->fetch_assoc()): ?>
                    <li>
                        <strong><?php echo $c['crime_type']; ?></strong>
                        <span class="meta-text">Severity: <?php echo $c['severity_level']; ?> | Loc: <?php echo $c['location']; ?></span>
                    </li>
                <?php endwhile; ?>
                </ul>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Current Sentence</div>
            <div class="card-body">
                <ul class="unstyled">
                <?php while($s = $sentences->fetch_assoc()): ?>
                    <li>
                        <strong><?php echo $s['duration_in_months']; ?> Months Imprisonment</strong>
                        <span class="meta-text">Start: <?php echo $s['start_date']; ?> | Type: <?php echo $s['sentence_type']; ?></span>
                    </li>
                <?php endwhile; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- 4. DUTY & HISTORY -->
    <div class="card">
        <div class="card-header">Duty Assignments</div>
        <div class="card-body" style="padding: 0;">
            <div class="table-wrap">
            <table class="data-table">
                <tr><th>Duty</th><th>Hours</th><th>Status</th><th>Action</th></tr>
                <?php while($d = $duties->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $d['duty_name']; ?></td>
                    <td><?php echo $d['hours_assigned']; ?></td>
                    <td>
                        <span class="badge <?php echo ($d['status']=='Pending')?'badge-warning':'badge-success'; ?>">
                            <?php echo $d['status']; ?>
                        </span>
                    </td>
                    <td>
                        <?php if($d['status'] == 'Pending'): ?>
                            <form method="post" class="inline">
                                <input type="hidden" name="assign_id" value="<?php echo $d['assignment_id']; ?>">
                                <input type="hidden" name="hours" value="<?php echo $d['hours_assigned']; ?>">
                                <button type="submit" name="approve_work" class="btn btn-success btn-sm">Approve</button>
                            </form>
                        <?php else: echo "<span style='color:#ccc;'>Locked</span>"; endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
            </div>
        </div>
    </div>

    <div class="details-grid">
        <div class="card">
            <div class="card-header">Behavior History</div>
            <div class="card-body" style="padding: 0;">
                <div class="table-wrap">
                <table class="data-table">
                    <tr><th>Reason</th><th>Pts</th><th>Admin</th></tr>
                    <?php while($b = $behavior_log->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $b['reason']; ?></td>
                            <td style="color:<?php echo ($b['points_change']>0)?'green':'red'; ?>"><b><?php echo $b['points_change']; ?></b></td>
                            <td style="font-size:12px;"><?php echo $b['admin_name']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </table>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Parole Evaluations</div>
            <div class="card-body" style="padding: 0;">
                <div class="table-wrap">
                <table class="data-table">
                    <tr><th>Decision</th><th>Date</th><th>Admin</th></tr>
                    <?php while($e = $eval_log->fetch_assoc()): ?>
                        <tr>
                            <td><b><?php echo $e['decision']; ?></b></td>
                            <td><?php echo date("M d", strtotime($e['evaluation_date'])); ?></td>
                            <td style="font-size:12px;"><?php echo $e['admin_name']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </table>
                </div>
            </div>
        </div>
    </div>

    <?php if (count($incident_log) > 0): ?>
    <div class="card">
        <div class="card-header">Incident Reports</div>
        <div class="card-body" style="padding: 0;">
            <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Date</th><th>Type</th><th>Severity</th><th>Action taken</th></tr></thead>
                <tbody>
                <?php foreach ($incident_log as $inc): ?>
                    <tr>
                        <td><?php echo $inc['incident_date']; ?></td>
                        <td><?php echo htmlspecialchars($inc['incident_type']); ?></td>
                        <td><span class="badge badge-<?php echo $inc['severity']=='Critical'||$inc['severity']=='High' ? 'danger' : 'warning'; ?>"><?php echo $inc['severity']; ?></span></td>
                        <td><?php echo htmlspecialchars($inc['action_taken'] ?? '–'); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>