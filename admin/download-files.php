<?php
require_once '../includes/db.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Increase memory limit and execution time for large files
ini_set('memory_limit', '3000M');
set_time_limit(300000);

// Validate order ID
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    die('رقم الطلب غير صالح.');
}

$order_id = intval($_GET['order_id']);

// Get files for order
try {
    $stmt = $conn->prepare("
        SELECT oif.file_name, oi.item_id
        FROM order_items oi
        JOIN order_item_files oif ON oi.item_id = oif.item_id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($files)) {
        die('لا توجد ملفات للتحميل.');
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die('حدث خطأ في جلب بيانات الملفات.');
}

// Prepare ZIP file with better temp directory handling
$zip_name = sprintf('order-%d-files-%s.zip', $order_id, date('Y-m-d-His'));
$temp_dir = ini_get('upload_tmp_dir') ? ini_get('upload_tmp_dir') : sys_get_temp_dir();

// Verify temp directory is writable
if (!is_writable($temp_dir)) {
    error_log("Temp directory is not writable: " . $temp_dir);
    die('المجلد المؤقت غير قابل للكتابة.');
}

$zip_path = rtrim($temp_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $zip_name;

// Clear any existing file
if (file_exists($zip_path)) {
    if (!unlink($zip_path)) {
        error_log("Failed to delete existing ZIP file: " . $zip_path);
    }
}

$success = false;
$error_message = '';

// Check for ZIP support
if (extension_loaded('zip')) {
    $zip = new ZipArchive();
    $res = $zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    
    if ($res === TRUE) {
        $success = true;
        foreach ($files as $file) {
            $file_path = realpath("../uploads/designs/" . $file['file_name']);
            if ($file_path && file_exists($file_path) && is_readable($file_path)) {
                $relativePath = basename($file['file_name']);
                if (!$zip->addFile($file_path, $relativePath)) {
                    $error_message = "Failed to add file to ZIP: " . $file_path;
                    error_log($error_message);
                    $success = false;
                    break;
                }
            } else {
                $error_message = "File not found or not readable: " . $file['file_name'];
                error_log($error_message);
                $success = false;
                break;
            }
        }
        $zip->close();
        
        if (!$success) {
            // Clean up failed ZIP file
            if (file_exists($zip_path)) {
                unlink($zip_path);
            }
        }
    } else {
        $error_message = "Failed to create ZIP file. Error code: " . $res;
        error_log($error_message);
    }
} elseif (file_exists('../includes/pclzip.lib.php')) {
    require_once '../includes/pclzip.lib.php';
    
    $archive = new PclZip($zip_path);
    $file_list = array();
    
    foreach ($files as $file) {
        $file_path = realpath("../uploads/designs/" . $file['file_name']);
        if ($file_path && file_exists($file_path) && is_readable($file_path)) {
            $file_list[] = $file_path;
        } else {
            $error_message = "File not found or not readable: " . $file['file_name'];
            error_log($error_message);
            $success = false;
            break;
        }
    }
    
    if (empty($error_message)) {
        $result = $archive->create($file_list, PCLZIP_OPT_REMOVE_ALL_PATH);
        if ($result == 0) {
            $error_message = "PclZip error: " . $archive->errorInfo(true);
            error_log($error_message);
            $success = false;
        } else {
            $success = true;
        }
    }
} else {
    $error_message = "No ZIP extension available and PclZip not found.";
    error_log($error_message);
}

if ($success && file_exists($zip_path) && filesize($zip_path) > 0) {
    // Send headers
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . basename($zip_name) . '"');
    header('Content-Length: ' . filesize($zip_path));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: public');
    header('Expires: 0');
    
    // Clean output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Send file
    if (readfile($zip_path) === false) {
        error_log("Failed to readfile: " . $zip_path);
    }
    
    // Delete temp file
    unlink($zip_path);
    exit;
} else {
    // Clean up if ZIP creation failed
    if (file_exists($zip_path)) {
        unlink($zip_path);
    }
    
    error_log("ZIP creation failed for order ID: " . $order_id . ". Error: " . $error_message);
    die('حدث خطأ أثناء إنشاء ملف ZIP. الرجاء المحاولة مرة أخرى أو الاتصال بمسؤول النظام. التفاصيل: ' . $error_message);
}