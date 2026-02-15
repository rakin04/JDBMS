<?php
session_start();
include 'db.php';

// 1. Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'prisoner') { 
    header("Location: index.php"); 
    exit(); 
}

$uid = $_SESSION['user_id'];

// 2. Fetch Data
// Fetch prisoner data based on the logged-in user ID
$p_data_query = $conn->query("SELECT * FROM prisoner WHERE user_id='$uid'");
$p_data = $p_data_query->fetch_assoc();

// // --- SAFETY CHECK: User exists but has no Prisoner Profile ---
// if (!$p_data) {
//     echo "<div style='font-family:sans-serif; padding:20px; text-align:center; color:#721c24; background:#f8d7da; margin:20px;'>
//             <h2>Profile Not Found</h2>
//             <p>Your user account (ID: $uid) exists, but no Prisoner Profile is linked to it.</p>
//             <p>Please contact the Administrator to fix your records.</p>
//             <a href='logout.php' style='background:#333; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Logout</a>
//           </div>";
//     exit();
// }
// -------------------------------------------------------------

$pid = $p_data['prisoner_id'];
$crime_data = $conn->query("SELECT * FROM crime WHERE prisoner_id='$pid' LIMIT 1")->fetch_assoc();
$sent_data = $conn->query("SELECT * FROM sentence WHERE prisoner_id='$pid' LIMIT 1")->fetch_assoc();

// 3. Handle Work Request
if (isset($_POST['submit_work'])) {
    $duty_id = $_POST['duty_id'];
    $hours = $_POST['hours'];
    
    $stmt = $conn->prepare("INSERT INTO duty_assignment (prisoner_id, duty_id, hours_assigned, status) VALUES (?, ?, ?, 'Pending')");
    
    $stmt->bind_param("sii", $pid, $duty_id, $hours);
    
    if ($stmt->execute()) { 
        $_SESSION['flash_msg'] = "Duty request submitted successfully."; 
    } else { 
        $_SESSION['flash_msg'] = "Error submitting request: " . $conn->error; 
    }
    
    header("Location: prisoner_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prisoner Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="main-wrap">

<?php if(isset($_SESSION['flash_msg'])): ?>
    <script> alert("<?php echo $_SESSION['flash_msg']; ?>"); </script>
    <?php unset($_SESSION['flash_msg']); ?>
<?php endif; ?>

<div class="page-container">

    <!-- 1. MAIN STATS -->
    <div class="card">
        <div class="card-header">
            Welcome, <?php echo htmlspecialchars($p_data['name']); ?>
            <a href="logout.php" style="color: rgba(255,255,255,0.9); text-decoration: none; font-size: 0.875rem;">Logout</a>
        </div>
        <div class="card-body">
            <div class="stats-row">
                <div class="stat-box">
                    <span class="stat-label">Crime Type</span>
                    <span class="stat-value"><?php echo isset($crime_data['crime_type']) ? htmlspecialchars($crime_data['crime_type']) : 'N/A'; ?></span>
                </div>
                <div class="stat-box">
                    <span class="stat-label">Sentence</span>
                    <span class="stat-value"><?php echo isset($sent_data['duration_in_months']) ? $sent_data['duration_in_months'] . " mo" : 'N/A'; ?></span>
                </div>
                <div class="stat-box">
                    <span class="stat-label">Points</span>
                    <span class="stat-value"><?php echo $p_data['total_points']; ?></span>
                </div>
                <div class="stat-box">
                    <span class="stat-label">Status</span>
                    <span class="stat-value status-<?php echo $p_data['current_status']; ?>"><?php echo $p_data['current_status']; ?></span>
                </div>
            </div>
            <div style="padding-top: 12px; border-top: 1px solid var(--border);">
                <a href="prisoner_parole.php" class="btn btn-primary">Check Parole Status</a>
            </div>
        </div>
    </div>

    <?php
    $announcements = [];
    $aq = @$conn->query("SELECT * FROM announcement WHERE is_active = 1 ORDER BY created_at DESC LIMIT 5");
    if ($aq) while ($a = $aq->fetch_assoc()) $announcements[] = $a;
    if (count($announcements) > 0):
    ?>
    <div class="card">
        <div class="card-header">Announcements</div>
        <div class="card-body">
            <?php foreach ($announcements as $a): ?>
                <div style="margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid var(--border);">
                    <strong><?php echo htmlspecialchars($a['title']); ?></strong>
                    <span class="meta-text"><?php echo date('M j, Y', strtotime($a['created_at'])); ?></span>
                    <p style="margin: 6px 0 0;"><?php echo nl2br(htmlspecialchars($a['body'] ?? '')); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- 2. PERSONAL INFORMATION -->
    <div class="card">
        <div class="card-header">My Personal File</div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                <div>
                    <span class="sub-header">Identification</span>
                    <table class="info-table">
                        <tr><td class="lbl">Prisoner ID:</td><td class="val"><?php echo $p_data['prisoner_id']; ?></td></tr>
                        <tr><td class="lbl">Date of Birth:</td><td class="val"><?php echo $p_data['dob']; ?></td></tr>
                        <tr><td class="lbl">Gender:</td><td class="val"><?php echo $p_data['gender']; ?></td></tr>
                        <tr><td class="lbl">Height/Weight:</td><td class="val"><?php echo $p_data['height_cm']." cm / ".$p_data['weight_kg']." kg"; ?></td></tr>
                        <tr><td class="lbl">Blood Group:</td><td class="val"><?php echo $p_data['blood_group']; ?></td></tr>
                        <tr><td class="lbl">Appearance:</td><td class="val"><?php echo "Eyes: ".$p_data['eye_color'].", Hair: ".$p_data['hair_color']; ?></td></tr>
                    </table>
                </div>
                <div>
                    <span class="sub-header">Family & Contact</span>
                    <table class="info-table">
                        <tr><td class="lbl">Father:</td><td class="val"><?php echo isset($p_data['father_name']) ? $p_data['father_name'] : 'N/A'; ?></td></tr>
                        <tr><td class="lbl">Mother:</td><td class="val"><?php echo isset($p_data['mother_name']) ? $p_data['mother_name'] : 'N/A'; ?></td></tr>
                        <tr><td class="lbl">Emergency:</td><td class="val"><?php echo isset($p_data['emergency_contact_name']) ? $p_data['emergency_contact_name']." (".$p_data['emergency_contact_no'].")" : 'N/A'; ?></td></tr>
                        <tr><td class="lbl">Home:</td><td class="val"><?php echo isset($p_data['permanent_address']) ? $p_data['permanent_address'] : 'N/A'; ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. REQUEST DUTY -->
    <div class="card">
        <div class="card-header">Request Duty / Log Work</div>
        <div class="card-body">
            <form method="post">
                <label>Duty type</label>
                <select name="duty_id" required>
                    <?php
                    $d_res = $conn->query("SELECT * FROM duty");
                    while($d = $d_res->fetch_assoc()) {
                        echo "<option value='{$d['duty_id']}'>{$d['duty_name']} (Req: {$d['required_hours_per_date']} hrs)</option>";
                    }
                    ?>
                </select>
                <label>Hours worked</label>
                <input type="number" name="hours" placeholder="e.g. 5" required min="1" max="12">
                <button type="submit" name="submit_work" class="btn btn-primary btn-block">Submit Request</button>
            </form>
        </div>
    </div>

    <!-- 4. HISTORY -->
    <div class="card">
        <div class="card-header">My Duty History</div>
        <div class="card-body" style="padding: 0;">
            <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Duty</th><th>Hours</th><th>Status</th></tr></thead>
                <tbody>
                <?php
                $hist = $conn->query("SELECT da.*, d.duty_name FROM duty_assignment da JOIN duty d ON da.duty_id = d.duty_id WHERE da.prisoner_id='$pid' ORDER BY da.assignment_id DESC");
                if ($hist->num_rows > 0) {
                    while($h = $hist->fetch_assoc()){
                        $badge_class = ($h['status'] == 'Pending') ? 'badge badge-warning' : 'badge badge-success';
                        echo "<tr><td>{$h['duty_name']}</td><td>{$h['hours_assigned']}</td><td><span class='$badge_class'>{$h['status']}</span></td></tr>";
                    }
                } else {
                    echo "<tr><td colspan='3' style='text-align:center; padding:24px; color: var(--text-muted);'>No duty history.</td></tr>";
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