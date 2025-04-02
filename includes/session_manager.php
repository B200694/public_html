<?php

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
        'plots' => [],
        'data' => [],
        'motifs' => []
    ];
} 

?>