<?php
session_start();
include 'db.php';

// Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// Function to generate random 4-char Alphanumeric ID
function generatePrisonerID() {
    $chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $id = "";
    for ($i = 0; $i < 4; $i++) {
        $id .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $id;
}

if (isset($_POST['add_prisoner'])) {
    // 1. Account Info - TRIMMED to prevent login issues
    $u_name = trim($_POST['username']);
    $u_pass = trim($_POST['password']);
    
    // 2. Personal Basic
    $name = $_POST['fullname'];
    $dob = $_POST['dob'];
    $cell = $_POST['cell'];
    
    // 3. Family & Address
    $father = $_POST['father_name'];
    $mother = $_POST['mother_name'];
    $present = $_POST['present_address'];
    $permanent = $_POST['permanent_address'];

    // 4. Physical Attributes
    $gender = $_POST['gender'];
    $height = $_POST['height'];
    $weight = $_POST['weight'];
    $blood = $_POST['blood_group'];
    $eye = $_POST['eye_color'];
    $hair = $_POST['hair_color'];
    
    // 5. Emergency Contact
    $e_name = $_POST['e_name'];
    $e_no = $_POST['e_no'];
    
    // 6. Crime & Sentence
    $crime_type = $_POST['crime_type'];
    $severity = $_POST['severity']; 
    $crime_loc = $_POST['location'];
    $s_duration = $_POST['duration']; 
    $s_start = $_POST['start_date'];
    $s_eligibility = $_POST['eligibility']; 

    // Check if username exists
    $check = $conn->query("SELECT * FROM user_account WHERE username='$u_name'");
    if ($check->num_rows > 0) {
        echo "<script>alert('Error: Username already exists!');</script>";
    } else {
        $conn->begin_transaction();
        try {
            // A. Generate Custom ID
            $new_pid = generatePrisonerID();

            // B. Create User Account
            $stmt_user = $conn->prepare("INSERT INTO user_account (username, password, role) VALUES (?, ?, 'prisoner')");
            $stmt_user->bind_param("ss", $u_name, $u_pass);
            $stmt_user->execute();
            $uid = $conn->insert_id;

            // C. Create Prisoner Entity
            $stmt = $conn->prepare("INSERT INTO prisoner (
                prisoner_id, user_id, name, dob, cell_no, 
                father_name, mother_name, present_address, permanent_address,
                gender, height_cm, weight_kg, blood_group, eye_color, hair_color, 
                emergency_contact_name, emergency_contact_no
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            // Type string: s (id) + i (uid) + s (name) + s (dob) + s (cell) + s(father) + s(mother) + s(pres) + s(perm) + s(gen) + i(ht) + i(wt) + s(bld) + s(eye) + s(hair) + s(em_nm) + s(em_no)
            // Total: 17 chars. Order must match values.
            $stmt->bind_param("sissssssssiiissss", 
                $new_pid, $uid, $name, $dob, $cell, 
                $father, $mother, $present, $permanent,
                $gender, $height, $weight, $blood, $eye, $hair, 
                $e_name, $e_no
            );
            $stmt->execute();

            // D. Create Crime
            $conn->query("INSERT INTO crime (prisoner_id, crime_type, severity_level, location, crime_date) VALUES ('$new_pid', '$crime_type', '$severity', '$crime_loc', CURDATE())");

            // E. Create Sentence
            $conn->query("INSERT INTO sentence (prisoner_id, duration_in_months, start_date, parole_eligibility) VALUES ('$new_pid', '$s_duration', '$s_start', '$s_eligibility')");

            $conn->commit();
            echo "<script>alert('Success! Generated ID: $new_pid. You can now login with username: $u_name'); window.location.href='admin_dashboard.php';</script>";
        } catch (Exception $e) {
            $conn->rollback();
            echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Prisoner</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="main-wrap">
    <div class="page-container">
        <div class="card">
        <div class="card-header">Register New Prisoner</div>
        <div class="card-body">
        
        <form method="post" class="form-grid" style="max-width: 100%;">
            
            <!-- 1. Account -->
            <div class="section-title">1. Account Details</div>
            <div>
                <label>Username</label><input type="text" name="username" required>
            </div>
            <div>
                <label>Password</label><input type="password" name="password" required>
            </div>
            
            <!-- 2. Personal Basic -->
            <div class="section-title">2. Personal Information</div>
            <div>
                <label>Full Name</label><input type="text" name="fullname" required>
            </div>
            <div>
                <label>Date of Birth</label><input type="date" name="dob" required>
            </div>
            <div>
                <label>Gender</label>
                <select name="gender" required>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div>
                <label>Cell Block</label><input type="text" name="cell" required>
            </div>

            <!-- 3. Family & Address -->
            <div class="section-title">3. Family & Address Information</div>
            <div>
                <label>Father's Name</label><input type="text" name="father_name" required>
            </div>
            <div>
                <label>Mother's Name</label><input type="text" name="mother_name" required>
            </div>
            <div>
                <label>Present Address</label><input type="text" name="present_address" required>
            </div>
            <div>
                <label>Permanent Address</label><input type="text" name="permanent_address" required>
            </div>

            <!-- 4. Physical Identity -->
            <div class="section-title">4. Physical Identification</div>
            <div>
                <label>Height (cm)</label><input type="number" name="height" placeholder="e.g. 175" required>
            </div>
            <div>
                <label>Weight (kg)</label><input type="number" name="weight" placeholder="e.g. 70" required>
            </div>
            <div>
                <label>Blood Group</label>
                <select name="blood_group" required>
                    <option value="A+">A+</option><option value="A-">A-</option>
                    <option value="B+">B+</option><option value="B-">B-</option>
                    <option value="O+">O+</option><option value="O-">O-</option>
                    <option value="AB+">AB+</option><option value="AB-">AB-</option>
                </select>
            </div>
            <div>
                <label>Eye Color</label><input type="text" name="eye_color" placeholder="e.g. Brown" required>
            </div>
            <div style="grid-column: span 2;">
                <label>Hair Color</label><input type="text" name="hair_color" placeholder="e.g. Black" required>
            </div>

            <!-- 5. Emergency Contact -->
            <div class="section-title">5. Emergency Contact</div>
            <div>
                <label>Guardian Name</label><input type="text" name="e_name" required>
            </div>
            <div>
                <label>Phone Number</label><input type="text" name="e_no" required>
            </div>
            
            <!-- 6. Crime & Sentence -->
            <div class="section-title">6. Crime & Sentencing</div>
            <div>
                <label>Crime Type</label><input type="text" name="crime_type" required>
            </div>
            <div>
                <label>Severity Level</label>
                <select name="severity" required>
                    <option value="Extremely Dangerous (Be Cautious)">Extremely Dangerous</option>
                    <option value="Dangerous">Dangerous</option>
                    <option value="Medium">Medium</option>
                    <option value="Low">Low</option>
                </select>
            </div>
            <div style="grid-column: span 2;">
                <label>Location of Crime</label><input type="text" name="location" required>
            </div>
            <div>
                <label>Sentence Duration (Months)</label><input type="number" name="duration" required>
            </div>
            <div>
                <label>Start Date</label><input type="date" name="start_date" required>
            </div>
            <div style="grid-column: span 2;">
                <label>Parole Eligibility</label>
                <select name="eligibility" required>
                    <option value="1">Eligible for Parole</option>
                    <option value="0">No Parole (Ineligible)</option>
                </select>
            </div>
            
            <button type="submit" name="add_prisoner" class="btn btn-success btn-block" style="grid-column: 1 / -1; margin-top: 8px;">Add Prisoner</button>
        </form>

        <a href="admin_dashboard.php" class="btn-back">Cancel & back to dashboard</a>
        </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>