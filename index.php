<?php
session_start();
include 'db.php';

$error_msg = ""; // Variable to store error messages

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prevent SQL Injection (Basic protection)
    $username = mysqli_real_escape_string($conn, $username);
    $password = mysqli_real_escape_string($conn, $password);

    // Query 'user_account' table
    $sql = "SELECT * FROM user_account WHERE username='$username' AND password='$password'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['user_id'] = $row['user_id'];
        $_SESSION['role'] = $row['role'];
        
        if ($row['role'] == 'admin') {
            header("Location: admin_dashboard.php");
            exit(); // <--- CRITICAL FIX: Stops script execution here
        } else {
            header("Location: prisoner_dashboard.php");
            exit(); // <--- CRITICAL FIX: Stops script execution here
        }
    } else {
        $error_msg = "Invalid Username or Password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JDBMS Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php $minimal_header = true; include 'includes/header.php'; ?>
    <div class="main-wrap login-wrap">
    <div class="login-card">
        <div class="login-header">
            <h1>JDBMS Access</h1>
            <p>Jail Database Management System</p>
        </div>
        
        <div class="login-body">
            <?php if(!empty($error_msg)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error_msg); ?></div>
            <?php endif; ?>

            <form method="post">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter username" required autocomplete="username">

                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter password" required autocomplete="current-password">

                <button type="submit" class="btn btn-primary btn-block">Sign in</button>
            </form>
        </div>
    </div>
    </div>
<?php include 'includes/footer.php'; ?>
</body>
</html>