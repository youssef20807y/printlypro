<?php
class PclZip {
    private $zipPath;
    private $error = '';

    public function __construct($zipPath) {
        $this->zipPath = $zipPath;
    }

    public function getError() {
        return $this->error;
    }

    public function create($fileList) {
        if (!extension_loaded('zip')) {
            $this->error = "ZIP extension not available";
            return 0;
        }

        try {
            $zip = new ZipArchive();
            if ($zip->open($this->zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                $this->error = "Cannot create ZIP file";
                return 0;
            }

            // Set compression level
            $zip->setArchiveComment('Created by PclZip');

            foreach ($fileList as $file) {
                if (!file_exists($file['filepath'])) {
                    $this->error = "File not found: " . $file['filepath'];
                    continue;
                }
                if (!is_readable($file['filepath'])) {
                    $this->error = "File not readable: " . $file['filepath'];
                    continue;
                }
                
                // Add file with compression
                if (!$zip->addFile($file['filepath'], basename($file['filename']))) {
                    $this->error = "Failed to add file: " . $file['filename'];
                    continue;
                }
                
                // Set file compression level
                $zip->setCompressionName(basename($file['filename']), ZipArchive::CM_DEFLATE, 9);
            }
            
            if (!$zip->close()) {
                $this->error = "Failed to close ZIP file";
                return 0;
            }
            
            return 1;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return 0;
        }
    }
}
