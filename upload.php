<?php
// Define allowed file types
$allowedTypes = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'ppt' => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'txt' => 'text/plain'
];

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if file was uploaded without errors
    if (isset($_FILES["document"]) && $_FILES["document"]["error"] == 0) {
        $fileName = $_FILES["document"]["name"];
        $fileType = $_FILES["document"]["type"];
        $fileSize = $_FILES["document"]["size"];
        $tempName = $_FILES["document"]["tmp_name"];
        
        // Verify file extension
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!array_key_exists($ext, $allowedTypes)) {
            die("Error: Please select a valid file format.");
        }
        
        // Verify file type
        if (!in_array($fileType, $allowedTypes)) {
            die("Error: Invalid file type.");
        }
        
        // Verify file size (10MB max)
        $maxSize = 10 * 1024 * 1024;
        if ($fileSize > $maxSize) {
            die("Error: File size is larger than the allowed limit (10MB).");
        }
        
        // Determine upload path based on department and subdepartment
        $uploadPath = "documents/" . $_POST["department"] . "/";
        if (isset($_POST["subdepartment"])) {
            $uploadPath .= $_POST["subdepartment"] . "/";
            
            // Make sure subdepartment exists
            $subDeptFile = "documents/" . $_POST["department"] . "/subdepartments.json";
            if (!file_exists($subDeptFile)) {
                die("Error: Sub-department not found.");
            }
            
            $subDepartments = json_decode(file_get_contents($subDeptFile), true);
            if (!array_key_exists($_POST["subdepartment"], $subDepartments)) {
                die("Error: Sub-department not found.");
            }
        }
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }
        
        // Sanitize filename
        $newFileName = preg_replace("/[^A-Za-z0-9._-]/", "_", basename($fileName));
        
        // Check if file already exists
        if (file_exists($uploadPath . $newFileName)) {
            die("Error: File already exists.");
        }
        
        // Attempt to move the uploaded file to its new location
        if (move_uploaded_file($tempName, $uploadPath . $newFileName)) {
            // Redirect back to the appropriate page
            if (isset($_POST["subdepartment"])) {
                header("Location: subdepartment.php?dept=" . urlencode($_POST["department"]) . "&sub=" . urlencode($_POST["subdepartment"]));
            } else {
                header("Location: department.php?dept=" . urlencode($_POST["department"]));
            }
            exit();
        } else {
            echo "Error: There was a problem uploading your file. Please try again.";
        }
    } else {
        echo "Error: " . $_FILES["document"]["error"];
    }
} else {
    header("Location: index.php");
    exit();
}
?>