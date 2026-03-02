<?php
// partials/select_states.php
$nigerian_states = array(
    "Abia", "Adamawa", "Akwa Ibom", "Anambra", "Bauchi", "Bayelsa", 
    "Benue", "Borno", "Cross River", "Delta", "Ebonyi", "Edo", 
    "Ekiti", "Enugu", "Federal Capital Territory", "Gombe", 
    "Imo", "Jigawa", "Kaduna", "Kano", "Katsina", "Kebbi", "Kogi", 
    "Kwara", "Lagos", "Nasarawa", "Niger", "Ogun", "Ondo", "Osun", 
    "Oyo", "Plateau", "Rivers", "Sokoto", "Taraba", "Yobe", "Zamfara"
);

sort($nigerian_states);

foreach ($nigerian_states as $state) {
    $value = strtolower(str_replace(' ', '-', $state));
    echo '<option value="' . $value . '">' . htmlspecialchars($state) . '</option>';
}
?>