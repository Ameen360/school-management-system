<?php
$plain = '12011999';          // ← your student's 8-digit DOB
echo password_hash($plain, PASSWORD_DEFAULT);
?>