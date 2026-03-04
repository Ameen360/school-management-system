<?php
session_start();
include("config.php");
error_reporting(E_ALL);

header('Content-Type: application/json');

$response = [];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$class   = trim($input['class']   ?? '');
$section = strtoupper(trim($input['section'] ?? ''));

if (empty($class) || empty($section)) {
    echo json_encode(["status" => "error", "message" => "Class and section required"]);
    exit;
}

// Get teacher's assigned class & section
$uid = $_SESSION['uid'] ?? '';
if (empty($uid)) {
    echo json_encode(["status" => "error", "message" => "Session expired"]);
    exit;
}

$teacherQuery = "SELECT class, section FROM teachers WHERE id = ?";
$stmtT = mysqli_prepare($conn, $teacherQuery);
mysqli_stmt_bind_param($stmtT, "s", $uid);
mysqli_stmt_execute($stmtT);
$resT = mysqli_stmt_get_result($stmtT);
$teacher = mysqli_fetch_assoc($resT);
mysqli_stmt_close($stmtT);

$teacher_class   = $teacher['class']   ?? '';
$teacher_section = $teacher['section'] ?? '';

$isTeacherClass = ($teacher_class === $class && $teacher_section === $section);

// Check if today's attendance already taken - we'll use this for displaying previous attendance
$currentDay   = date('d');
$currentMonth = date('m');
$currentYear  = date('Y');

$checkQuery = "SELECT student_id, attendence FROM attendence 
               WHERE class=? AND section=? 
                 AND DAY(date)=? AND MONTH(date)=? AND YEAR(date)=?";

$stmt = mysqli_prepare($conn, $checkQuery);
mysqli_stmt_bind_param($stmt, "sssss", $class, $section, $currentDay, $currentMonth, $currentYear);
mysqli_stmt_execute($stmt);
$todayAttendance = mysqli_stmt_get_result($stmt);

// Create an array of today's attendance records
$todayAttendanceMap = [];
while ($row = mysqli_fetch_assoc($todayAttendance)) {
    $todayAttendanceMap[$row['student_id']] = $row['attendence'];
}
mysqli_stmt_close($stmt);

// Academic session dates
$currentYear  = date("Y");
$currentMonth = date('m');
$startDate = ($currentMonth <= 3) ? ($currentYear - 1) . "-04-01" : $currentYear . "-04-01";
$endDate   = ($currentMonth <= 3) ? $currentYear . "-03-31" : ($currentYear + 1) . "-03-31";

// Total working days (distinct dates) — filtered by class & section
$wdQuery = "SELECT COUNT(DISTINCT DATE(date)) 
            FROM attendence 
            WHERE class=? AND section=? 
              AND date BETWEEN ? AND ?";

$stmtWd = mysqli_prepare($conn, $wdQuery);
mysqli_stmt_bind_param($stmtWd, "ssss", $class, $section, $startDate, $endDate);
mysqli_stmt_execute($stmtWd);
mysqli_stmt_bind_result($stmtWd, $workingDays);
mysqli_stmt_fetch($stmtWd);
mysqli_stmt_close($stmtWd);

$workingDays = max((int)$workingDays, 1);

// Get students
$stuQuery = "SELECT id, fname, lname, image, roll_no FROM students 
             WHERE class=? AND section=? 
             ORDER BY roll_no ASC, fname ASC";

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
    $studentId = $row['id'];
    $rollNo = $row['roll_no'] ?? 'N/A';
    $fullName  = ucfirst(strtolower($row['fname'])) . " " . ucfirst(strtolower($row['lname']));

    $imgPath = "../studentUploads/" . $row['image'];
    $imgSrc  = file_exists($imgPath) ? $imgPath : "../images/user.png";

    // Present count for this student in current academic session
    $pQuery = "SELECT COUNT(*) FROM attendence 
               WHERE student_id = ? AND attendence = 1 
                 AND date BETWEEN ? AND ?";
    $stmtP = mysqli_prepare($conn, $pQuery);
    mysqli_stmt_bind_param($stmtP, "sss", $studentId, $startDate, $endDate);
    mysqli_stmt_execute($stmtP);
    mysqli_stmt_bind_result($stmtP, $presentCount);
    mysqli_stmt_fetch($stmtP);
    mysqli_stmt_close($stmtP);

    $present = (int)$presentCount;
    $percent = round(($present / $workingDays) * 100, 1);

    // Check if attendance was already marked for today
    $isPresentToday = isset($todayAttendanceMap[$studentId]) && $todayAttendanceMap[$studentId] == 1;
    $checked = $isPresentToday ? 'checked' : '';

    // Build the row with ALL 7 columns including percentage
    $html .= '
        <tr>
            <td>' . $count . '</td>
            <td class="student_id">' . htmlspecialchars($rollNo) . '</td>
            <td class="user">
                <img src="' . htmlspecialchars($imgSrc) . '" style="width:32px;height:32px;border-radius:50%;object-fit:cover;vertical-align:middle;margin-right:8px;">
                <span>' . htmlspecialchars($fullName) . '</span>
            </td>
            <td class="text-center">' . $workingDays . '</td>
            <td class="text-center">' . $present . '</td>
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
    "status"           => "success",
    "already_taken"    => false,
    "is_teacher_class" => true,
    "html"             => $html
]);

mysqli_close($conn);
?>