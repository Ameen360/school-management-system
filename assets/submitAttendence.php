<?php
include('config.php');
session_start();
$response = "";

if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $jsonData = file_get_contents('php://input');
    $decodedData = json_decode($jsonData, true);
    
    $successCount = 0;
    $totalCount = count($decodedData);

    foreach ($decodedData as $studentId => $value) {
      
        $class = $value['class'];
        $section = $value['section'];
        $attendance = $value['attendence'];
        
        // Check if attendance already exists for today
        $checkQuery = "SELECT s_no FROM attendence 
                       WHERE student_id = ? 
                       AND DATE(date) = CURDATE() 
                       AND class = ? 
                       AND section = ?";
        
        $checkStmt = mysqli_prepare($conn, $checkQuery);
        mysqli_stmt_bind_param($checkStmt, "sss", $studentId, $class, $section);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_store_result($checkStmt);
        
        if (mysqli_stmt_num_rows($checkStmt) > 0) {
            // Update existing record
            $query = "UPDATE attendence 
                     SET attendence = ? 
                     WHERE student_id = ? 
                     AND DATE(date) = CURDATE() 
                     AND class = ? 
                     AND section = ?";
            
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ssss", $attendance, $studentId, $class, $section);
        } else {
            // Insert new record
            $query = "INSERT INTO attendence (student_id, attendence, class, section, date) 
                     VALUES (?, ?, ?, ?, NOW())";
            
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ssss", $studentId, $attendance, $class, $section);
        }
        
        mysqli_stmt_close($checkStmt);

        if(mysqli_stmt_execute($stmt)){
            $successCount++;
        }
        mysqli_stmt_close($stmt);
    }

    if ($successCount == $totalCount) {
        $response = "success";
    } else {
        $response = "Partially successful: $successCount out of $totalCount records processed";
    }

} else {
    $response = "Something went wrong!";
}

echo $response;
?>