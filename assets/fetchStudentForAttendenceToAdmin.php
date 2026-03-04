<?php
include("config.php");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$class   = trim($data['class'] ?? '');
$section = strtoupper(trim($data['section'] ?? ''));

if (empty($class) || empty($section)) {
    echo json_encode(["status" => "error", "message" => "Class and section required"]);
    exit;
}

// 1. Get today's attendance (to pre-check toggles)
$today = date('Y-m-d');
$todayQuery = "SELECT student_id, attendence 
               FROM attendence 
               WHERE class = ? AND section = ? AND DATE(date) = ?";
$stmt = mysqli_prepare($conn, $todayQuery);
mysqli_stmt_bind_param($stmt, "sss", $class, $section, $today);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$todayAttendance = [];
while ($row = mysqli_fetch_assoc($result)) {
    $todayAttendance[$row['student_id']] = (int)$row['attendence'];
}
mysqli_stmt_close($stmt);

// 2. Academic session dates
$year = date("Y");
$month = date('m');
$startDate = ($month <= 3) ? ($year - 1) . "-04-01" : $year . "-04-01";
$endDate   = ($month <= 3) ? $year . "-03-31" : ($year + 1) . "-03-31";

// 3. Working days count
$wdQuery = "SELECT COUNT(DISTINCT DATE(date)) 
            FROM attendence 
            WHERE class = ? AND section = ? 
              AND date BETWEEN ? AND ?";
$stmt = mysqli_prepare($conn, $wdQuery);
mysqli_stmt_bind_param($stmt, "ssss", $class, $section, $startDate, $endDate);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $workingDays);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);
$workingDays = max((int)$workingDays, 1);

// 4. Students
$stuQuery = "SELECT id, fname, lname, image 
             FROM students 
             WHERE class = ? AND section = ? 
             ORDER BY fname ASC, lname ASC";
$stmt = mysqli_prepare($conn, $stuQuery);
mysqli_stmt_bind_param($stmt, "ss", $class, $section);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo json_encode(["status" => "no_data", "message" => "No students found"]);
    exit;
}

$count = 1;
$html = "";

while ($row = mysqli_fetch_assoc($result)) {
    $sid = $row['id'];
    $name = ucfirst(strtolower($row['fname'])) . " " . ucfirst(strtolower($row['lname']));
    $imgPath = "../studentUploads/" . $row['image'];
    $imgSrc = file_exists($imgPath) ? $imgPath : "../images/user.png";

    // Present count
    $presentQuery = "SELECT COUNT(*) FROM attendence 
                     WHERE student_id = ? AND attendence = 1 
                       AND date BETWEEN ? AND ?";
    $stmtP = mysqli_prepare($conn, $presentQuery);
    mysqli_stmt_bind_param($stmtP, "sss", $sid, $startDate, $endDate);
    mysqli_stmt_execute($stmtP);
    mysqli_stmt_bind_result($stmtP, $presentCount);
    mysqli_stmt_fetch($stmtP);
    mysqli_stmt_close($stmtP);

    $percent = round(($presentCount / $workingDays) * 100, 1);

    // Pre-check toggle
    $checked = (isset($todayAttendance[$sid]) && $todayAttendance[$sid] === 1) ? 'checked' : '';

    $html .= '
        <tr>
            <td>' . $count . '.</td>
            <td class="student_id">' . htmlspecialchars($sid) . '</td>
            <td class="user">
                <img src="' . htmlspecialchars($imgSrc) . '" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
                <p>' . htmlspecialchars($name) . '</p>
            </td>
            <td class="text-center">' . $workingDays . '</td>
            <td class="text-center">' . $presentCount . '</td>
            <td class="text-center">' . $percent . '%</td>
            <td class="text-center">
                <label class="switch">
                    <input type="checkbox" class="attendenceCheckbox" ' . $checked . '>
                    <span class="slider round"></span>
                </label>
            </td>
        </tr>';
    $count++;
}

echo json_encode([
    "status" => "success",
    "html"   => $html
]);

mysqli_close($conn);
?>