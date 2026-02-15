<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

$curr_user_id = $_SESSION['user_id'];
$admin_res = $conn->query("SELECT admin_id FROM admin WHERE user_id='$curr_user_id'");
$curr_admin_id = $admin_res->fetch_assoc()['admin_id'];

$msg = '';
if (isset($_POST['add_announcement'])) {
    $title = trim($_POST['title'] ?? '');
    $body = trim($_POST['body'] ?? '');
    if ($title) {
        $stmt = $conn->prepare("INSERT INTO announcement (title, body, created_by) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $title, $body, $curr_admin_id);
        if ($stmt->execute()) $msg = "Announcement posted.";
        else $msg = "Error. Run database_additions.sql to create the announcement table.";
    }
}
if (isset($_POST['toggle_announcement'])) {
    $aid = (int) $_POST['announcement_id'];
    @$conn->query("UPDATE announcement SET is_active = 1 - is_active WHERE announcement_id = $aid");
    $msg = "Visibility updated.";
}

$list = [];
$aq = @$conn->query("SELECT a.*, ad.admin_name FROM announcement a LEFT JOIN admin ad ON a.created_by = ad.admin_id ORDER BY a.created_at DESC");
if ($aq) while ($row = $aq->fetch_assoc()) $list[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="main-wrap">
<div class="app-container">
    <div class="page-header">
        <h1>Announcements</h1>
        <a href="admin_dashboard.php" class="btn btn-ghost">Dashboard</a>
    </div>
    <?php if ($msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

    <div class="card">
        <div class="card-header">New announcement</div>
        <div class="card-body">
            <form method="post">
                <label>Title</label>
                <input type="text" name="title" required placeholder="Subject">
                <label>Message</label>
                <textarea name="body" rows="4" placeholder="Content for prisoners..."></textarea>
                <button type="submit" name="add_announcement" class="btn btn-primary btn-block">Post announcement</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">All announcements</div>
        <div class="card-body">
            <?php
            if (count($list) > 0) {
                foreach ($list as $a) {
                    $active = !empty($a['is_active']);
                    echo "<div class='card' style='margin-bottom:16px; border-left: 4px solid " . ($active ? '#059669' : '#94a3b8') . ";'>
                        <div class='card-body'>
                            <strong>" . htmlspecialchars($a['title']) . "</strong>
                            <span class='meta-text'>" . date('M j, Y', strtotime($a['created_at'])) . " · " . htmlspecialchars($a['admin_name'] ?? '') . "</span>
                            <p style='margin:8px 0 0;'>" . nl2br(htmlspecialchars($a['body'] ?? '')) . "</p>
                            <form method='post' class='inline' style='margin-top:10px;'>
                                <input type='hidden' name='announcement_id' value='{$a['announcement_id']}'>
                                <button type='submit' name='toggle_announcement' class='btn btn-ghost btn-sm'>" . ($active ? 'Hide from prisoners' : 'Show to prisoners') . "</button>
                            </form>
                        </div>
                    </div>";
                }
            } else {
                echo "<p style='color: var(--text-muted);'>No announcements yet.</p>";
            }
            ?>
        </div>
    </div>
</div>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>
