<?php
// Define departments with their display names
$departments = [
    'alternative_distribution' => 'ALTERNATIVE DISTRIBUTION CHANNEL UNIT',
    'credit_control' => 'CREDIT CONTROL UNIT',
    'customer_service' => 'CUSTOMER SERVICE UNIT',
    'hcm' => 'HUMAN CAPITAL MANAGEMENT',
    'inspection_survey' => 'INSPECTION AND SURVEY',
    'internal_audit' => 'INTERNAL AUDIT',
    'it' => 'INFORMATION TECHNOLOGY',
    'claim' => 'CLAIMS DEPARTMENT',
    'life_group' => 'LIFE GROUP',
    're_insurance' => 'RE-INSURANCE',
    'retail_op' => 'RETAIL OPERATIONS',
    'technical_operation' => 'TECHNICAL OPERATION',
    'corporate_marketing' => 'CORPORATE MARKETING',
    'products_research' => 'PRODUCTS AND RESEARCH',
    'internal_control' => 'INTERNAL CONTROL',
    'icu' => 'INTEGRATED CONTROL UNIT'
];

// Create documents directory if it doesn't exist
if (!file_exists('documents')) {
    mkdir('documents', 0777, true);
}

$server_on = true;
$headers = get_headers('http://localhost:8005');

if (!$headers || strpos($headers[0], '404'))
	$server_on = false;

// Create department directories if they don't exist
foreach (array_keys($departments) as $dept) {
    $dirPath = "documents/$dept";
    if (!file_exists($dirPath)) {
        mkdir($dirPath, 0777, true);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enterprise Document Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-bg: #f8f9fa;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
        }
        
        .navbar-brand img {
            height: 40px;
            margin-right: 10px;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
        }
        
        .department-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            height: 100%;
        }
        
        .department-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }
        
        .card-icon {
            font-size: 2.5rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }
        
        .search-container {
            max-width: 600px;
            margin: 0 auto 2rem;
        }
        
        .stats-card {
            background-color: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .footer {
            background-color: var(--primary-color);
            color: white;
            padding: 2rem 0;
            margin-top: 3rem;
        }
        
        .dept-icon {
            width: 60px;
            height: 60px;
            background-color: rgba(52, 152, 219, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        
        .dept-icon i {
            color: var(--secondary-color);
            font-size: 1.5rem;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="logo.png" alt="Company Logo">
                <span>NSIA INSURANCE</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-home me-1"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-info-circle me-1"></i> About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-question-circle me-1"></i> Help</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-cog me-1"></i> Settings</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 fw-bold mb-3">Document Upload</h1>
            <p class="lead mb-4">Centralized document management system for all departments</p>
            
            <!-- Search Bar -->
            <div class="search-container">
                <div class="input-group mb-3">
                    <input type="text" class="form-control form-control-lg" placeholder="Search documents, departments..." aria-label="Search">
                    <button class="btn btn-primary" type="button">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
                <div class="text-start">
                    <a href="#" class="text-white me-3"><i class="fas fa-filter me-1"></i> Advanced Search</a>
                    <a href="#" class="text-white"><i class="fas fa-clock me-1"></i> Recent Documents</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Quick Stats -->
        <div class="row stats-card">
            <div class="col-md-3 text-center">
                <h3 class="text-primary">16</h3>
                <p class="text-muted">Departments</p>
            </div>
            <div class="col-md-3 text-center">
                <h3 class="text-primary"><?php echo count(glob('documents/*/*')); ?></h3>
                <p class="text-muted">Total Documents</p>
            </div>
            <div class="col-md-3 text-center">
                <h3 class="text-primary">24</h3>
                <p class="text-muted">Active Users</p>
            </div>
            <div class="col-md-3 text-center">
                <h3 class="text-primary">5.2GB</h3>
                <p class="text-muted">Storage Used</p>
            </div>
        </div>

        <!-- Department Cards -->
        <h2 class="mb-4 text-center">Departments</h2>
        
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-filter"></i></span>
                    <select class="form-select" id="departmentFilter">
                        <option selected>Filter by category...</option>
                        <option value="operations">Operations</option>
                        <option value="finance">Finance</option>
                        <option value="technical">Technical</option>
                        <option value="support">Support</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-sort"></i></span>
                    <select class="form-select" id="departmentSort">
                        <option selected>Sort by...</option>
                        <option value="name_asc">Name (A-Z)</option>
                        <option value="name_desc">Name (Z-A)</option>
                        <option value="recent">Recently Updated</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="row">
            <?php foreach ($departments as $key => $name): ?>
                <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                    <div class="card department-card h-100">
                        <div class="card-body text-center">
                            <div class="dept-icon">
                                <?php 
                                    // Different icons for different department types
                                    if (strpos($key, 'it') !== false) {
                                        echo '<i class="fas fa-laptop-code"></i>';
                                    } elseif (strpos($key, 'customer') !== false) {
                                        echo '<i class="fas fa-headset"></i>';
                                    } elseif (strpos($key, 'finance') !== false || strpos($key, 'credit') !== false) {
                                        echo '<i class="fas fa-chart-line"></i>';
                                    } elseif (strpos($key, 'technical') !== false) {
                                        echo '<i class="fas fa-cogs"></i>';
                                    } else {
                                        echo '<i class="fas fa-building"></i>';
                                    }
                                ?>
                            </div>
                            <h5 class="card-title"><?php echo htmlspecialchars($name); ?></h5>
                            <p class="text-muted small mb-3">
                                <?php 
                                    $docCount = count(glob("documents/$key/*"));
                                    echo "$docCount documents";
                                ?>
                            </p>
                            <a target="_blank" href="<?= str_replace(' ', '_', strtolower($name)) === 'technical_operation' ? ($server_on ? 'http://localhost:8005' : 'http://localhost/file-upload') : 'department.php?dept=' . urlencode($key); ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-folder-open me-1"></i> Access
                            </a>
                            <a href="#" class="btn btn-outline-secondary btn-sm ms-2" data-bs-toggle="tooltip" title="Quick View">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                        <div class="card-footer bg-transparent">
                            <small class="text-muted">
                                Last updated: <?php echo date("M d, Y", filemtime("documents/$key")); ?>
                            </small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Recent Activity Section -->
        <div class="card mb-5">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-history me-2"></i> Recent Activity
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-file-upload text-success me-2"></i> New document uploaded to IT Department</span>
                        <small class="text-muted">2 minutes ago</small>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-user-plus text-info me-2"></i> New user registered in HR Department</span>
                        <small class="text-muted">15 minutes ago</small>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-file-download text-primary me-2"></i> Document downloaded from Finance</span>
                        <small class="text-muted">1 hour ago</small>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5>Enterprise DocHub</h5>
                    <p>Centralized document management solution for your organization.</p>
                    <div class="social-icons">
                        <a href="#" class="text-white me-2"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-md-2 mb-4 mb-md-0">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white">Home</a></li>
                        <li><a href="#" class="text-white">Documents</a></li>
                        <li><a href="#" class="text-white">Departments</a></li>
                        <li><a href="#" class="text-white">Help Center</a></li>
                    </ul>
                </div>
                <div class="col-md-6 mb-4 mb-md-0">
                    <h5>Support</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-phone me-2"></i> +1 (234) 567-890</li>
                        <li><i class="fas fa-envelope me-2"></i> Anayo.madueke@nsiainsurance.com</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i> 3 Elsie Femi Pearse St, Lagos</li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>System Status</h5>
                    <div class="mb-2">
                        <span class="badge bg-success">Operational</span>
                        <small class="text-white-50 ms-2">All systems normal</small>
                    </div>
                    <div class="progress mb-2" style="height: 5px;">
                        <div class="progress-bar bg-success" style="width: 95%"></div>
                    </div>
                    <small class="text-white-50">Storage: 5.2GB of 10GB used</small>
                </div>
            </div>
            <hr class="my-4 bg-light">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2025 NSIA. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-white me-3">Privacy Policy</a>
                    <a href="#" class="text-white me-3">Terms of Service</a>
                    <a href="#" class="text-white">Sitemap</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enable tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
        // Simple search functionality
        document.querySelector('.search-container button').addEventListener('click', function() {
            const searchTerm = document.querySelector('.search-container input').value.toLowerCase();
            const cards = document.querySelectorAll('.department-card');
            
            cards.forEach(card => {
                const deptName = card.querySelector('.card-title').textContent.toLowerCase();
                if (deptName.includes(searchTerm)) {
                    card.parentElement.style.display = 'block';
                } else {
                    card.parentElement.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
