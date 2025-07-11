<?php
session_start();

// Define departments with their display names
$departments = [
    'alternative_distribution' => 'ALTERNATIVE DISTRIBUTION CHANNEL UNIT',
    'credit_control' => 'CREDIT CONTROL UNIT',
    'customer_service' => 'CUSTOMER SERVICE UNIT',
    'hcm' => 'HCM',
    'inspection_survey' => 'INSPECTION AND SURVEY',
    'internal_audit' => 'INTERNAL AUDIT',
    'it' => 'IT',
    'claim' => 'CLAIM',
    'life_group' => 'LIFE GROUP',
    're_insurance' => 'RE-INSURANCE',
    'retail_op' => 'RETAIL OP',
    'technical_operation' => 'TECHNICAL OPERATION',
    'corporate_marketing' => 'NON-RETAIL MARKETING (CORPORATE MARKETING)',
    'products_research' => 'PRODUCTS AND RESEARCH',
    'internal_control' => 'INTERNAL CONTROL',
    'icu' => 'ICU'
];

// Check if department parameter is valid
if (!isset($_GET['dept']) || !array_key_exists($_GET['dept'], $departments)) {
    header("Location: index.php");
    exit();
}

$currentDept = $_GET['dept'];
$currentDeptName = $departments[$currentDept];

// File to store sub-departments
$subDeptFile = "documents/{$currentDept}/subdepartments.json";

// Load existing sub-departments
$subDepartments = [];
if (file_exists($subDeptFile)) {
    $subDepartments = json_decode(file_get_contents($subDeptFile), true) ?: [];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create new CLASS OF BUSINESS
    if (isset($_POST['new_subdept'])) {
        $newSubDept = trim($_POST['new_subdept']);
        $newSubDeptKey = strtolower(str_replace([' ', '-'], '_', $newSubDept));
        
        // Validate
        if (empty($newSubDept)) {
            $_SESSION['error'] = "CLASS OF BUSINESS name cannot be empty";
        } elseif (strlen($newSubDept) > 50) {
            $_SESSION['error'] = "CLASS OF BUSINESS name is too long (max 50 characters)";
        } elseif (array_key_exists($newSubDeptKey, $subDepartments)) {
            $_SESSION['error'] = "CLASS OF BUSINESS already exists";
        } else {
            // Add new CLASS OF BUSINESS
            $subDepartments[$newSubDeptKey] = $newSubDept;
            file_put_contents($subDeptFile, json_encode($subDepartments, JSON_PRETTY_PRINT));
            
            // Create directory if it doesn't exist
            $newDir = "documents/{$currentDept}/{$newSubDeptKey}";
            if (!file_exists($newDir)) {
                mkdir($newDir, 0777, true);
            }
            
            $_SESSION['success'] = "CLASS OF BUSINESS '{$newSubDept}' created successfully";
        }
    }
    // Edit CLASS OF BUSINESS name
    elseif (isset($_POST['edit_subdept']) && isset($_POST['subdept_key'])) {
        $subdeptKey = $_POST['subdept_key'];
        $newName = trim($_POST['edit_subdept']);
        
        if (isset($subDepartments[$subdeptKey])) {
            if (empty($newName)) {
                $_SESSION['error'] = "CLASS OF BUSINESS name cannot be empty";
            } else {
                $subDepartments[$subdeptKey] = $newName;
                file_put_contents($subDeptFile, json_encode($subDepartments, JSON_PRETTY_PRINT));
                $_SESSION['success'] = "CLASS OF BUSINESS updated successfully";
            }
        }
    }
    // Delete CLASS OF BUSINESS
    elseif (isset($_POST['delete_subdept']) && isset($_POST['subdept_key'])) {
        $subdeptKey = $_POST['subdept_key'];
        
        if (isset($subDepartments[$subdeptKey])) {
            // Remove from array
            unset($subDepartments[$subdeptKey]);
            file_put_contents($subDeptFile, json_encode($subDepartments, JSON_PRETTY_PRINT));
            
            // Delete directory and contents
            $dirToDelete = "documents/{$currentDept}/{$subdeptKey}";
            if (file_exists($dirToDelete)) {
                array_map('unlink', glob("$dirToDelete/*.*"));
                rmdir($dirToDelete);
            }
            
            $_SESSION['success'] = "CLASS OF BUSINESS deleted successfully";
        }
    }
    
    // Redirect to avoid form resubmission
    header("Location: department.php?dept={$currentDept}");
    exit();
}

// Display any messages from session
$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($currentDeptName); ?> - CLASS OF BUSINESS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .subdept-card {
            transition: transform 0.2s;
        }
        .subdept-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .action-buttons {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .edit-form {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><?php echo htmlspecialchars($currentDeptName); ?></h1>
            <a href="index.php" class="btn btn-secondary">Back to Departments</a>
        </div>
        
        <!-- CLASS OF BUSINESS creation form -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4><i class="bi bi-plus-circle"></i> Create New CLASS OF BUSINESS</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <form method="post">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" name="new_subdept" 
                               placeholder="Enter new CLASS OF BUSINESS name" required
                               maxlength="50">
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-save"></i> Create
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Display existing CLASS OF BUSINESS -->
        <?php if (!empty($subDepartments)): ?>
            <div class="row">
                <div class="col-md-12">
                    <h3 class="mb-3"><i class="bi bi-folder"></i> CLASS OF BUSINESS</h3>
                </div>
                <?php foreach ($subDepartments as $key => $name): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card subdept-card h-100 position-relative">
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-outline-primary edit-trigger" 
                                        data-subdept="<?php echo htmlspecialchars($key); ?>"
                                        data-name="<?php echo htmlspecialchars($name); ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this CLASS OF BUSINESS and all its documents?')">
                                    <input type="hidden" name="subdept_key" value="<?php echo htmlspecialchars($key); ?>">
                                    <button type="submit" name="delete_subdept" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                            
                            <div class="card-body text-center">
                                <!-- Display form -->
                                <div class="display-content">
                                    <h5 class="card-title"><?php echo htmlspecialchars($name); ?></h5>
                                    <a href="subdepartment.php?dept=<?php echo urlencode($currentDept); ?>&sub=<?php echo urlencode($key); ?>" 
                                       class="btn btn-primary">
                                        <i class="bi bi-folder2-open"></i> Open
                                    </a>
                                    <div class="mt-2 text-muted small">
                                        <?php 
                                        $docCount = count(glob("documents/{$currentDept}/{$key}/*"));
                                        echo $docCount . " document(s)";
                                        ?>
                                    </div>
                                </div>
                                
                                <!-- Edit form (hidden by default) -->
                                <div class="edit-form" id="edit-form-<?php echo htmlspecialchars($key); ?>">
                                    <form method="post">
                                        <input type="hidden" name="subdept_key" value="<?php echo htmlspecialchars($key); ?>">
                                        <div class="input-group mb-2">
                                            <input type="text" class="form-control" name="edit_subdept" 
                                                   value="<?php echo htmlspecialchars($name); ?>" required>
                                        </div>
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <i class="bi bi-check"></i> Save
                                        </button>
                                        <button type="button" class="btn btn-secondary btn-sm cancel-edit">
                                            <i class="bi bi-x"></i> Cancel
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                No CLASS OF BUSINESS created yet. Please create one using the form above.
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle edit buttons
        document.querySelectorAll('.edit-trigger').forEach(button => {
            button.addEventListener('click', function() {
                const subdeptKey = this.getAttribute('data-subdept');
                const displayContent = this.closest('.card-body').querySelector('.display-content');
                const editForm = document.getElementById(`edit-form-${subdeptKey}`);
                
                displayContent.style.display = 'none';
                editForm.style.display = 'block';
            });
        });
        
        // Handle cancel buttons
        document.querySelectorAll('.cancel-edit').forEach(button => {
            button.addEventListener('click', function() {
                const cardBody = this.closest('.card-body');
                cardBody.querySelector('.display-content').style.display = 'block';
                cardBody.querySelector('.edit-form').style.display = 'none';
            });
        });
    </script>
</body>
</html>