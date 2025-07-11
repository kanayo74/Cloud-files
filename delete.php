<?php
// Define departments with their display names
$departments = [
    'alternative_distribution' => 'ALTERNATIVE DISTRIBUTION CHANNEL UNIT',
    // ... (other departments same as before)
];

// Check if required parameters are present
if (!isset($_GET['dept']) || !array_key_exists($_GET['dept'], $departments) || !isset($_GET['file'])) {
    header("Location: index.php");
    exit();
}

$currentDept = $_GET['dept'];
$fileToDelete = $_GET['file'];

// Check if this is a sub-department file
if (isset($_GET['sub'])) {
    $currentSub = $_GET['sub'];
    $filePath = "documents/{$currentDept}/{$currentSub}/{$fileToDelete}";
    
    // Verify sub-department exists
    $subDeptFile = "documents/{$currentDept}/subdepartments.json";
    if (!file_exists($subDeptFile)) {
        die("Error: Sub-department not found.");
    }
    
    $subDepartments = json_decode(file_get_contents($subDeptFile), true);
    if (!array_key_exists($currentSub, $subDepartments)) {
        die("Error: Sub-department not found.");
    }
    
    $redirect = "subdepartment.php?dept=" . urlencode($currentDept) . "&sub=" . urlencode($currentSub);
} else {
    $filePath = "documents/{$currentDept}/{$fileToDelete}";
    $redirect = "department.php?dept=" . urlencode($currentDept);
}

// Check if file exists and delete it
if (file_exists($filePath)) {
    unlink($filePath);
}

header("Location: $redirect");
exit();
?>