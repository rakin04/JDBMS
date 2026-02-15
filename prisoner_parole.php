<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'prisoner') { 
    header("Location: index.php"); 
    exit(); 
}

$uid = $_SESSION['user_id'];
$p_data = $conn->query("SELECT * FROM prisoner WHERE user_id='$uid'")->fetch_assoc();

// Check if prisoner data exists
if (!$p_data) {
    echo "Error: Prisoner profile not found.";
    exit();
}

$pid = $p_data['prisoner_id'];
$s_data = $conn->query("SELECT * FROM sentence WHERE prisoner_id='$pid'")->fetch_assoc();
$c_data = $conn->query("SELECT * FROM crime WHERE prisoner_id='$pid'")->fetch_assoc();

// --- LOGIC CALCULATION (Read-Only) ---
$start_date = new DateTime($s_data['start_date']);
$today = new DateTime();
$interval = $start_date->diff($today);
$months_served = ($interval->y * 12) + $interval->m;
$total_duration = $s_data['duration_in_months'];
$percent_served = ($total_duration > 0) ? ($months_served / $total_duration) * 100 : 0;

$severity = $c_data['severity_level'];
$req_points = 50; $req_time_pct = 50;
switch ($severity) {
    case 'Low': $req_points = 60; $req_time_pct = 30; break;
    case 'Medium': $req_points = 70; $req_time_pct = 50; break;
    case 'Dangerous': $req_points = 85; $req_time_pct = 75; break;
    case 'Extremely Dangerous (Be Cautious)': $req_points = 95; $req_time_pct = 90; break;
}

$eligible = true;
if($p_data['total_points'] < $req_points) $eligible = false;
if($percent_served < $req_time_pct) $eligible = false;
if($s_data['parole_eligibility'] == 0) $eligible = false;

// --- HANDLE REQUEST ---
$msg = "";
if(isset($_POST['request_review'])) {
    // Check if pending request exists
    $check = $conn->query("SELECT * FROM parole_requests WHERE prisoner_id='$pid' AND status='Pending'");
    if($check->num_rows == 0) {
        $conn->query("INSERT INTO parole_requests (prisoner_id) VALUES ('$pid')");
        $msg = "Request submitted to Administration.";
    } else {
        $msg = "You already have a pending request.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parole Status</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="main-wrap">
<div class="page-container">
    <div class="card">
    <div class="card-header">My Parole Status</div>
    <div class="card-body">
    
    <div class="status-box <?php echo $eligible ? 'eligible' : 'not-eligible'; ?>">
        <h2><?php echo $eligible ? "Eligible" : "Not yet eligible"; ?></h2>
        <p><?php echo $eligible ? "You meet the criteria for a parole hearing." : "You must meet all requirements below."; ?></p>
    </div>

    <?php if(!empty($msg)): ?>
        <p class="alert alert-success"><?php echo htmlspecialchars($msg); ?></p>
    <?php endif; ?>

    <div class="metric-row">
        <span><strong>1. Behavior points</strong> (target: <?php echo $req_points; ?>)</span>
        <span class="<?php echo ($p_data['total_points'] >= $req_points) ? 'status-pass' : 'status-fail'; ?>">
            <?php echo $p_data['total_points']; ?>
        </span>
    </div>
    
    <div class="metric-row">
        <span><strong>2. Time served</strong> (target: <?php echo $req_time_pct; ?>%)</span>
        <span class="<?php echo ($percent_served >= $req_time_pct) ? 'status-pass' : 'status-fail'; ?>">
            <?php echo round($percent_served, 1); ?>%
        </span>
    </div>

    <form method="post" style="margin-top: 24px;">
        <?php if($eligible): ?>
            <button type="submit" name="request_review" class="btn btn-primary btn-block">Request parole review</button>
        <?php else: ?>
            <button type="button" disabled class="btn btn-ghost btn-block">Requirements not met</button>
        <?php endif; ?>
    </form>
    
    <a href="prisoner_dashboard.php" class="btn-back">Back to dashboard</a>
    </div>
    </div>
</div>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>