<?php
include("config.php");
session_start();
error_reporting(E_ALL);

header('Content-Type: application/json');


$mode     = $_POST['mode'] ?? 'cumulative';   // 'cumulative' or 'single_date'
$class    = trim($_POST['class'] ?? '');
$section  = strtoupper(trim($_POST['section'] ?? ''));
$date     = trim($_POST['date'] ?? '');       // only used in single_date mode

if ($class === '' || $section === '') {
    echo json_encode(['status' => 'error', 'message' => 'Class and section required']);
    exit;
}

try {
    if ($mode === 'single_date') {
        // ── Date-wise view ───────────────────────────────────────
        if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            echo json_encode(['status' => 'error', 'message' => 'Valid date required']);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT 
                s.student_id,
                CONCAT(s.fname, ' ', s.lname) AS name,
                COALESCE(a.attendence, 0) AS present
            FROM students s
            LEFT JOIN attendence a 
                ON s.student_id = a.student_id 
                AND a.class = :class 
                AND a.section = :section 
                AND DATE(a.date) = :date
            WHERE s.class = :class 
              AND s.section = :section
            ORDER BY s.student_id
        ");

        $stmt->execute([
            ':class'  => $class,
            ':section'=> $section,
            ':date'   => $date
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'mode'   => 'single_date',
            'data'   => $rows
        ]);

    } else {
        // ── Cumulative summary (Take Attendance tab) ─────────────
        // Decide your "total days" logic — here using distinct dates up to today
        $stmt_total = $pdo->prepare("
            SELECT COUNT(DISTINCT DATE(date)) AS total_days
            FROM attendence
            WHERE class = :class AND section = :section
              AND date <= CURDATE()
        ");
        $stmt_total->execute([':class'=>$class, ':section'=>$section]);
        $total_row = $stmt_total->fetch(PDO::FETCH_ASSOC);
        $total_days = (int)($total_row['total_days'] ?: 1); // avoid /0

        $stmt = $pdo->prepare("
            SELECT 
                s.student_id,
                CONCAT(s.fname, ' ', s.lname) AS name,
                COUNT(CASE WHEN a.attendence = 1 THEN 1 END) AS present_days
            FROM students s
            LEFT JOIN attendence a 
                ON s.student_id = a.student_id 
                AND a.class = :class 
                AND a.section = :section
            WHERE s.class = :class 
              AND s.section = :section
            GROUP BY s.student_id
            ORDER BY s.student_id
        ");

        $stmt->execute([
            ':class'  => $class,
            ':section'=> $section
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add percentage
        foreach ($rows as &$row) {
            $present = (int)$row['present_days'];
            $row['percentage'] = round(($present / $total_days) * 100, 1);
            $row['total_days'] = $total_days;
        }

        echo json_encode([
            'status'     => 'success',
            'mode'       => 'cumulative',
            'total_days' => $total_days,
            'data'       => $rows
        ]);
    }

} catch (Exception $e) {
    echo json_encode(['status'=>'error', 'message'=>$e->getMessage()]);
}
exit;