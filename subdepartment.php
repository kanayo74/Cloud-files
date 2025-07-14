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
	'icu' => 'ICU',
];

// Check if department and sub-department parameters are valid
if (!isset($_GET['dept']) || !array_key_exists($_GET['dept'], $departments) ||
	!isset($_GET['sub']) || empty($_GET['sub'])) {
	header("Location: index.php");
	exit();
}

$currentDept = $_GET['dept'];
$currentDeptName = $departments[$currentDept];
$currentSubDept = $_GET['sub'];

// Load sub-department name
$subDeptFile = "documents/{$currentDept}/subdepartments.json";
$subDepartments = [];
if (file_exists($subDeptFile)) {
	$subDepartments = json_decode(file_get_contents($subDeptFile), true) ?: [];
}

if (!isset($subDepartments[$currentSubDept])) {
	header("Location: department.php?dept={$currentDept}");
	exit();
}

$currentSubDeptName = $subDepartments[$currentSubDept];
$uploadDir = "documents/{$currentDept}/{$currentSubDept}/";

// Function to extract text from PDF (requires smalot/pdfparser composer package)
function extractTextFromPDF($filePath)
{
	if (!class_exists('Smalot\PdfParser\Parser')) {
		return false;
	}
	
	try {
		$parser = new \Smalot\PdfParser\Parser();
		$pdf = $parser->parseFile($filePath);
		return $pdf->getText();
	} catch (Exception $e) {
		return false;
	}
}

// Function to extract potential policy number from text
function extractPolicyNumber($text)
{
	// Common policy number patterns (adjust based on your needs)
	$patterns = [
		'/Policy\s*No[:.]?\s*([A-Z0-9-]+)/i',
		'/Policy\s*Number[:.]?\s*([A-Z0-9-]+)/i',
		'/Pol\.\s*No[:.]?\s*([A-Z0-9-]+)/i',
		'/\b([A-Z]{2,3}\d{6,8})\b/',
		'/\b(\d{4}[A-Z]{2}\d{4})\b/',
	];
	
	foreach ($patterns as $pattern) {
		if (preg_match($pattern, $text, $matches)) {
			return trim($matches[1]);
		}
	}
	
	return false;
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
	$policyNumber = trim($_POST['policy_number'] ?? '');
	$autoDetect = isset($_POST['auto_detect']);
	
	if ($_FILES['document']['error'] !== UPLOAD_ERR_OK) {
		$_SESSION['error'] = "File upload error: " . $_FILES['document']['error'];
	} else {
		if ($autoDetect && empty($policyNumber)) {
			$tmpFilePath = $_FILES['document']['tmp_name'];
			$fileExtension = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
			
			if ($fileExtension === 'pdf') {
				$text = extractTextFromPDF($tmpFilePath);
				if ($text !== false) {
					$detectedPolicy = extractPolicyNumber($text);
					if ($detectedPolicy) {
						$policyNumber = $detectedPolicy;
					}
				}
			}
		}
		
		// Automatically use the original filename (without extension) as document name
		$documentName = pathinfo($_FILES['document']['name'], PATHINFO_FILENAME);
		
		if (empty($policyNumber)) {
			$_SESSION['error'] = "Policy number is required and could not be auto-detected";
		} else {
			// Create policy number folder if it doesn't exist
			$policyDir = $uploadDir . $policyNumber . '/';
			if (!file_exists($policyDir)) {
				mkdir($policyDir, 0777, true);
			}
			
			// Sanitize filename
			$originalName = basename($_FILES['document']['name']);
			$fileExtension = pathinfo($originalName, PATHINFO_EXTENSION);
			$safeName = preg_replace('/[^a-zA-Z0-9-_\.]/', '_', $documentName) . '.' . $fileExtension;
			$targetPath = $policyDir . $safeName;
			
			// Check if file already exists
			if (file_exists($targetPath)) {
				// Add timestamp to filename if it already exists
				$safeName = preg_replace('/[^a-zA-Z0-9-_\.]/', '_', $documentName) . '_' . time() . '.' . $fileExtension;
				$targetPath = $policyDir . $safeName;
			}
			
			// Move uploaded file
			if (move_uploaded_file($_FILES['document']['tmp_name'], $targetPath)) {
				$_SESSION['success'] = "Document uploaded successfully to {$policyNumber}";
			} else {
				$_SESSION['error'] = "Failed to move uploaded file";
			}
		}
	}
	
	header("Location: subdepartment.php?dept={$currentDept}&sub={$currentSubDept}");
	exit();
}

// Handle file deletion
if (isset($_GET['delete']) && isset($_GET['policy'])) {
	$policyNumber = $_GET['policy'];
	$fileName = $_GET['delete'];
	$filePath = $uploadDir . $policyNumber . '/' . $fileName;
	
	if (file_exists($filePath)) {
		if (unlink($filePath)) {
			// Remove policy folder if empty
			$policyDir = $uploadDir . $policyNumber . '/';
			if (count(glob("$policyDir/*")) === 0) {
				rmdir($policyDir);
			}
			$_SESSION['success'] = "Document deleted successfully";
		} else {
			$_SESSION['error'] = "Failed to delete document";
		}
	}
	
	header("Location: subdepartment.php?dept={$currentDept}&sub={$currentSubDept}");
	exit();
}

// Handle document details view
if (isset($_GET['view']) && isset($_GET['policy'])) {
	$policyNumber = $_GET['policy'];
	$fileName = $_GET['view'];
	$filePath = $uploadDir . $policyNumber . '/' . $fileName;
	
	if (file_exists($filePath)) {
		$fileInfo = [
			'name' => $fileName,
			'path' => $filePath,
			'size' => filesize($filePath),
			'modified' => filemtime($filePath),
			'type' => mime_content_type($filePath),
			'extension' => pathinfo($fileName, PATHINFO_EXTENSION),
		];
		
		// For PDFs, try to extract some metadata
		if (strtolower($fileInfo['extension']) === 'pdf' && function_exists('extractTextFromPDF')) {
			$text = extractTextFromPDF($filePath);
			if ($text !== false) {
				$fileInfo['preview'] = substr($text, 0, 500) . '...'; // First 500 chars
			}
		}
	} else {
		$_SESSION['error'] = "Document not found";
		header("Location: subdepartment.php?dept={$currentDept}&sub={$currentSubDept}");
		exit();
	}
}

// Search functionality
$searchQuery = $_GET['search'] ?? '';
$searchResults = [];
if (!empty($searchQuery)) {
	if (file_exists($uploadDir)) {
		$folders = array_diff(scandir($uploadDir), ['.', '..']);
		foreach ($folders as $folder) {
			if (is_dir($uploadDir . $folder)) {
				$files = array_diff(scandir($uploadDir . $folder), ['.', '..']);
				foreach ($files as $file) {
					if (!is_dir($uploadDir . $folder . '/' . $file)) {
						// Check if search query matches policy number or filename
						if (stripos($folder, $searchQuery) !== false ||
							stripos($file, $searchQuery) !== false) {
							$filePath = $uploadDir . $folder . '/' . $file;
							$searchResults[$folder][] = [
								'name' => $file,
								'path' => $filePath,
								'size' => filesize($filePath),
								'modified' => filemtime($filePath),
							];
						}
					}
				}
			}
		}
	}
}

// Display any messages from session
$error = $_SESSION['error'] ?? NULL;
$success = $_SESSION['success'] ?? NULL;
unset($_SESSION['error'], $_SESSION['success']);

// Get all policy folders and their documents
$policyFolders = [];
if (file_exists($uploadDir) && empty($searchQuery)) {
	$folders = array_diff(scandir($uploadDir), ['.', '..']);
	foreach ($folders as $folder) {
		if (is_dir($uploadDir . $folder)) {
			$files = array_diff(scandir($uploadDir . $folder), ['.', '..']);
			$fileList = [];
			foreach ($files as $file) {
				if (!is_dir($uploadDir . $folder . '/' . $file)) {
					$filePath = $uploadDir . $folder . '/' . $file;
					$fileList[] = [
						'name' => $file,
						'path' => $filePath,
						'size' => filesize($filePath),
						'modified' => filemtime($filePath),
					];
				}
			}
			if (!empty($fileList)) {
				$policyFolders[$folder] = $fileList;
			}
		}
	}
}

// Sort policy folders by most recently modified
uasort($policyFolders, function ($a, $b) {
	$aTime = max(array_column($a, 'modified'));
	$bTime = max(array_column($b, 'modified'));
	return $bTime - $aTime;
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo htmlspecialchars($currentSubDeptName); ?> - Documents</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
	<style>
		.document-card {
			transition: transform 0.2s;
		}
		
		.document-card:hover {
			transform: translateY(-3px);
		}
		
		.file-icon {
			font-size: 2rem;
		}
		
		.policy-folder {
			background-color: #f8f9fa;
			border-radius: 5px;
			padding: 15px;
			margin-bottom: 20px;
		}
		
		.document-preview {
			max-height: 500px;
			overflow-y: auto;
			background-color: #f8f9fa;
			padding: 15px;
			border-radius: 5px;
			white-space: pre-wrap;
		}
		
		.search-highlight {
			background-color: yellow;
			font-weight: bold;
		}
		
		.upload-dropzone {
			border: 2px dashed #dee2e6;
			border-radius: 5px;
			padding: 20px;
			text-align: center;
			cursor: pointer;
			transition: all 0.3s;
		}
		
		.upload-dropzone:hover {
			border-color: #0d6efd;
			background-color: #f8f9fa;
		}
		
		.upload-dropzone.active {
			border-color: #0d6efd;
			background-color: #e7f1ff;
		}
		
		.folder-header {
			cursor: pointer;
			padding: 10px;
			background-color: #e9ecef;
			border-radius: 5px;
			margin-bottom: 10px;
		}
		
		.folder-header:hover {
			background-color: #dee2e6;
		}
		
		.folder-contents {
			display: none;
			padding: 10px;
			background-color: #f8f9fa;
			border-radius: 5px;
		}
		
		.folder-open .folder-contents {
			display: block;
		}
		
		.folder-open .bi-folder {
			display: none;
		}
		
		.folder-open .bi-folder2-open {
			display: inline-block;
		}
		
		.bi-folder2-open {
			display: none;
		}
	</style>
</head>
<body>
	<div class="container mt-5">
		<nav aria-label="breadcrumb" class="mb-4">
			<ol class="breadcrumb">
				<li class="breadcrumb-item"><a href="index.php">Departments</a></li>
				<li class="breadcrumb-item"><a href="department.php?dept=<?php echo urlencode($currentDept); ?>"><?php echo htmlspecialchars($currentDeptName); ?></a></li>
				<li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($currentSubDeptName); ?></li>
			</ol>
		</nav>
		
		<div class="d-flex justify-content-between align-items-center mb-4">
			<h1>
				<i class="bi bi-folder"></i> <?php echo htmlspecialchars($currentSubDeptName); ?>
				<small class="text-muted"><?php echo htmlspecialchars($currentDeptName); ?></small>
			</h1>
			<a href="department.php?dept=<?php echo urlencode($currentDept); ?>" class="btn btn-secondary">
				<i class="bi bi-arrow-left"></i> Back
			</a>
		</div>
		
		<?php if ($error): ?>
			<div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
		<?php endif; ?>
		<?php if ($success): ?>
			<div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
		<?php endif; ?>
		
		<!-- Document View Modal -->
		<?php if (isset($fileInfo)): ?>
			<div class="modal fade show" id="documentModal" tabindex="-1" aria-labelledby="documentModalLabel" aria-hidden="false" style="display: block;">
				<div class="modal-dialog modal-lg">
					<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title" id="documentModalLabel">Document Details</h5>
							<a href="subdepartment.php?dept={$currentDept}&sub={$currentSubDept}" class="btn-close" aria-label="Close"></a>
						</div>
						<div class="modal-body">
							<div class="row mb-3">
								<div class="col-md-6">
									<p><strong>Policy Number:</strong> <?php echo htmlspecialchars($_GET['policy']); ?></p>
									<p><strong>File Name:</strong> <?php echo htmlspecialchars($fileInfo['name']); ?></p>
								</div>
								<div class="col-md-6">
									<p><strong>File Size:</strong> <?php echo round($fileInfo['size'] / 1024, 2); ?> KB</p>
									<p><strong>Last Modified:</strong> <?php echo date('Y-m-d H:i', $fileInfo['modified']); ?></p>
								</div>
							</div>
							
							<?php if (isset($fileInfo['preview'])): ?>
								<h5>Document Preview:</h5>
								<div class="document-preview">
									<?php echo nl2br(htmlspecialchars($fileInfo['preview'])); ?>
								</div>
							<?php else: ?>
								<div class="alert alert-info">
									Preview not available for this file type.
								</div>
							<?php endif; ?>
						</div>
						<div class="modal-footer">
							<a href="<?php echo htmlspecialchars(str_replace('documents/', '', $fileInfo['path'])); ?>"
							   class="btn btn-primary" download>
								<i class="bi bi-download"></i> Download
							</a>
							<a href="subdepartment.php?dept=<?php echo urlencode($currentDept); ?>&sub=<?php echo urlencode($currentSubDept); ?>"
							   class="btn btn-secondary">
								Close
							</a>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-backdrop fade show"></div>
		<?php endif; ?>
		
		<!-- Search form -->
		<div class="card mb-4">
			<div class="card-header bg-info text-white">
				<h4><i class="bi bi-search"></i> Search Documents</h4>
			</div>
			<div class="card-body">
				<form method="get" class="row g-3">
					<input type="hidden" name="dept" value="<?php echo htmlspecialchars($currentDept); ?>">
					<input type="hidden" name="sub" value="<?php echo htmlspecialchars($currentSubDept); ?>">
					<div class="col-md-10">
						<input type="text" class="form-control" name="search" placeholder="Search by policy number or document name..." value="<?php echo htmlspecialchars($searchQuery); ?>">
					</div>
					<div class="col-md-2">
						<button type="submit" class="btn btn-primary w-100">
							<i class="bi bi-search"></i> Search
						</button>
					</div>
				</form>
				<?php if (!empty($searchQuery)): ?>
					<div class="mt-3">
						<a href="subdepartment.php?dept=<?php echo urlencode($currentDept); ?>&sub=<?php echo urlencode($currentSubDept); ?>" class="btn btn-sm btn-outline-secondary">
							<i class="bi bi-x-circle"></i> Clear search
						</a>
					</div>
				<?php endif; ?>
			</div>
		</div>
		
		<!-- Upload form -->
		<div class="card mb-5">
			<div class="card-header bg-primary text-white">
				<h4><i class="bi bi-upload"></i> Upload New Document</h4>
			</div>
			<div class="card-body">
				<form method="post" enctype="multipart/form-data" id="uploadForm">
					<div class="row">
						<div class="col-md-4 mb-3">
							<label for="policy_number" class="form-label">Policy Number</label>
							<input type="text" class="form-control" id="policy_number" name="policy_number" required
							       value="<?php echo isset($_GET['policy']) ? htmlspecialchars($_GET['policy']) : ''; ?>"
							       list="policyNumbers">
							<datalist id="policyNumbers">
								<?php foreach (array_keys($policyFolders) as $policy): ?>
								<option value="<?php echo htmlspecialchars($policy); ?>">
									<?php endforeach; ?>
							</datalist>
						</div>
						<div class="col-md-5 mb-3">
							<label for="document" class="form-label">Document File</label>
							<div class="upload-dropzone" id="dropzone">
								<div id="dropzoneContent">
									<i class="bi bi-cloud-arrow-up fs-1"></i>
									<p class="mt-2">Drag & drop files here or click to browse</p>
									<input type="file" class="d-none" id="document" name="document" required>
								</div>
								<div id="fileName" class="mt-2 fw-bold d-none"></div>
							</div>
						</div>
						<div class="col-md-3 mb-3 d-flex align-items-end">
							<div class="form-check form-switch">
								<input class="form-check-input" type="checkbox" id="auto_detect" name="auto_detect" checked>
								<label class="form-check-label" for="auto_detect">Auto-detect policy number</label>
							</div>
						</div>
					</div>
					<button type="submit" class="btn btn-primary">
						<i class="bi bi-upload"></i> Upload Document
					</button>
				</form>
			</div>
		</div>
		
		<!-- Documents by policy number -->
		<h3 class="mb-4"><i class="bi bi-files"></i> Documents</h3>
		
		<?php if (empty($policyFolders) && empty($searchResults)): ?>
			<div class="alert alert-info">
				No documents found. Upload your first document using the form above.
			</div>
		<?php else: ?>
			<?php
			$displayFolders = !empty($searchQuery) ? $searchResults : $policyFolders;
			
			if (empty($displayFolders) && !empty($searchQuery)): ?>
				<div class="alert alert-warning">
					No documents found matching your search "<?php echo htmlspecialchars($searchQuery); ?>".
				</div>
			<?php else: ?>
				<div class="row">
					<?php foreach ($displayFolders as $policyNumber => $documents): ?>
						<div class="col-lg-3 col-md-6">
							<div class="policy-folder">
								<div class="folder-header d-flex justify-content-between align-items-center" onclick="toggleFolder(this)">
									<div>
										<i class="bi bi-folder text-warning"></i>
										<i class="bi bi-folder2-open text-warning"></i>
										<strong>Policy:
											<span class="<?php echo !empty($searchQuery) && stripos($policyNumber, $searchQuery) !== false ? 'search-highlight' : ''; ?>">
                                    <?php echo htmlspecialchars($policyNumber); ?>
                                </span>
										</strong>
										<span class="badge bg-secondary ms-2"><?php echo count($documents); ?> document(s)</span>
									</div>
									<div class="d-flex align-items-end flex-column flex-fill w-50">
										<button class="btn btn-sm btn-outline-primary mb-1" data-bs-toggle="modal" data-bs-target="#addDocumentModal" data-policy="<?php echo htmlspecialchars($policyNumber); ?>">
											<i class="bi bi-plus"></i> Add Document
										</button>
										<a href="subdepartment.php?dept=<?php echo urlencode($currentDept); ?>&sub=<?php echo urlencode($currentSubDept); ?>&policy=<?php echo urlencode($policyNumber); ?>" class="btn btn-sm btn-outline-secondary ms-1">
											<i class="bi bi-upload"></i> Quick Upload
										</a>
									</div>
								</div>
								
								<div class="folder-contents">
									<!--<div class="row">-->
										<?php foreach ($documents as $document): ?>
											<!--<div class="col-md-4 mb-4">-->
												<div class="card document-card h-100">
													<div class="card-body">
														<div class="text-center mb-3">
															<?php
															$extension = pathinfo($document['name'], PATHINFO_EXTENSION);
															$icon = "bi-file-earmark";
															if (in_array(strtolower($extension), ['pdf'])) $icon = "bi-file-earmark-pdf";
															elseif (in_array(strtolower($extension), ['doc', 'docx'])) $icon = "bi-file-earmark-word";
															elseif (in_array(strtolower($extension), ['xls', 'xlsx'])) $icon = "bi-file-earmark-excel";
															elseif (in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif'])) $icon = "bi-file-earmark-image";
															?>
															<i class="bi <?php echo $icon; ?> file-icon text-primary"></i>
														</div>
														<h5 class="card-title <?php echo !empty($searchQuery) && stripos($document['name'], $searchQuery) !== false ? 'search-highlight' : ''; ?>">
															<?php echo htmlspecialchars(pathinfo($document['name'], PATHINFO_FILENAME)); ?>
														</h5>
														<p class="card-text small text-muted">
															<?php echo round($document['size'] / 1024, 2); ?> KB<br>
															<?php echo date('Y-m-d H:i', $document['modified']); ?>
														</p>
														<div class="d-flex justify-content-between">
															<a href="<?php echo htmlspecialchars(str_replace('documents/', '', $document['path'])); ?>"
															   class="btn btn-sm btn-success" download>
																<i class="bi bi-download"></i> Download
															</a>
															<div>
																<a href="subdepartment.php?dept=<?php echo urlencode($currentDept); ?>&sub=<?php echo urlencode($currentSubDept); ?>&view=<?php echo urlencode($document['name']); ?>&policy=<?php echo urlencode($policyNumber); ?>"
																   class="btn btn-sm btn-info">
																	<i class="bi bi-eye"></i> View
																</a>
																<a href="subdepartment.php?dept=<?php echo urlencode($currentDept); ?>&sub=<?php echo urlencode($currentSubDept); ?>&delete=<?php echo urlencode($document['name']); ?>&policy=<?php echo urlencode($policyNumber); ?>"
																   class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this document?')">
																	<i class="bi bi-trash"></i> Delete
																</a>
															</div>
														</div>
													</div>
												</div>
											<!--</div>-->
										<?php endforeach; ?>
									<!--</div>-->
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	
	<!-- Add Document Modal -->
	<div class="modal fade" id="addDocumentModal" tabindex="-1" aria-labelledby="addDocumentModalLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<form method="post" enctype="multipart/form-data" id="modalUploadForm">
					<div class="modal-header">
						<h5 class="modal-title" id="addDocumentModalLabel">Add Document to Policy</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<input type="hidden" id="modalPolicyNumber" name="policy_number">
						<div class="mb-3">
							<label for="modalDocument" class="form-label">Document File</label>
							<input type="file" class="form-control" id="modalDocument" name="document" required>
						</div>
						<div class="form-check mb-3">
							<input class="form-check-input" type="checkbox" id="modalAutoDetect" name="auto_detect" checked>
							<label class="form-check-label" for="modalAutoDetect">Auto-detect policy number</label>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
						<button type="submit" class="btn btn-primary">Upload</button>
					</div>
				</form>
			</div>
		</div>
	</div>
	
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
	<script>
		// Initialize the add document modal
		const addDocumentModal = document.getElementById('addDocumentModal');
		if (addDocumentModal) {
			addDocumentModal.addEventListener('show.bs.modal', function (event) {
				const button = event.relatedTarget;
				const policyNumber = button.getAttribute('data-policy');
				const modalTitle = addDocumentModal.querySelector('.modal-title');
				const modalPolicyInput = addDocumentModal.querySelector('#modalPolicyNumber');
				
				modalTitle.textContent = `Add Document to Policy: ${policyNumber}`;
				modalPolicyInput.value = policyNumber;
			});
		}
		
		// Drag and drop file upload
		const dropzone = document.getElementById('dropzone');
		const fileInput = document.getElementById('document');
		const dropzoneContent = document.getElementById('dropzoneContent');
		const fileNameDisplay = document.getElementById('fileName');
		
		if (dropzone && fileInput) {
			dropzone.addEventListener('click', () => fileInput.click());
			
			fileInput.addEventListener('change', () => {
				if (fileInput.files.length) {
					fileNameDisplay.textContent = fileInput.files[0].name;
					fileNameDisplay.classList.remove('d-none');
					dropzone.classList.add('active');
				}
			});
			
			dropzone.addEventListener('dragover', (e) => {
				e.preventDefault();
				dropzone.classList.add('active');
			});
			
			['dragleave', 'dragend'].forEach(type => {
				dropzone.addEventListener(type, () => {
					dropzone.classList.remove('active');
				});
			});
			
			dropzone.addEventListener('drop', (e) => {
				e.preventDefault();
				dropzone.classList.remove('active');
				
				if (e.dataTransfer.files.length) {
					fileInput.files = e.dataTransfer.files;
					fileNameDisplay.textContent = e.dataTransfer.files[0].name;
					fileNameDisplay.classList.remove('d-none');
				}
			});
		}
		
		// Toggle folder visibility
		function toggleFolder(element) {
			element.parentElement.classList.toggle('folder-open');
		}
		
		// Auto-focus search field if there's a search query
		document.addEventListener('DOMContentLoaded', function () {
			const searchInput = document.querySelector('input[name="search"]');
			if (searchInput && searchInput.value) {
				searchInput.focus();
			}
			
			// Highlight search terms in the document
			const searchQuery = "<?php echo addslashes($searchQuery); ?>";
			if (searchQuery) {
				const regex = new RegExp(searchQuery, 'gi');
				const elements = document.querySelectorAll('.search-highlight');
				
				elements.forEach(el => {
					const text = el.textContent;
					el.innerHTML = text.replace(regex, match =>
						`<span class="search-highlight">${match}</span>`);
				});
			}
			
			// If URL has policy parameter, scroll to that policy section and open it
			const urlParams = new URLSearchParams(window.location.search);
			if (urlParams.has('policy')) {
				const policyNumber = urlParams.get('policy');
				const policyElement = document.querySelector(`.policy-folder h4 span:contains("${policyNumber}")`);
				if (policyElement) {
					const folder = policyElement.closest('.policy-folder');
					folder.classList.add('folder-open');
					folder.scrollIntoView({
						behavior: 'smooth'
					});
				}
			}
		});
	</script>
</body>
</html>
