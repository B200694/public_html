<?php
/**
 * Simple Session Manager
 * Place at top of any PHP file needing session access
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize session arrays if they don't exist
if (!isset($_SESSION['search_data'])) {
    $_SESSION['search_data'] = [
        'source' => null,    // 'database' or 'ncbi'
        'temp_table' => null,
        'taxon' => null,
        'protein_family' => null
    ];
}

if (!isset($_SESSION['temp_files'])) {
    $_SESSION['temp_files'] = [
        'fasta' => [],
        'alignments' => [],
        'plots' => []
    ];
}

// Cleanup function for old files
function clean_temp_files() {
    $max_files = 5; // Keep last 5 files of each type
    
    foreach ($_SESSION['temp_files'] as $type => $files) {
        // Trim the stored file list
        $_SESSION['temp_files'][$type] = array_slice($files, 0, $max_files);
        
        // Determine file extension
        $ext = match($type) {
            'fasta' => 'fasta',
            'alignments' => 'aln',
            'plots' => 'png',
            default => ''
        };
        
        if ($ext) {
            // Delete physical files not in the session
            foreach (glob("temp/*.$ext") as $file) {
                if (!in_array($file, $_SESSION['temp_files'][$type])) {
                    @unlink($file);
                }
            }
        }
    }
}

// Register cleanup to run automatically
register_shutdown_function('clean_temp_files');