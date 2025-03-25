<?php
// Access MySQL database
require_once 'login.php';

// Run the NCBI search script for Arabidopsis acetyl-coa carboxylase
$output = shell_exec("python3 fetch_ncbi_seqs.py 'Arabidopsis' 'acetyl-coa carboxylase' 2>&1");
if ($output === null) {
    die("Failed to fetch data from NCBI. Please try again later.");
}

$sequences = json_decode($output, true);

// Add each sequence to SQL row
foreach ($sequences as $seq) {
    $accession_id = $seq['accession_id'];
    $organism = $seq['organism'];
    $protein_name = $seq['protein_name'];
    $gene_name = $seq['gene_name'];
    $sequence = $seq['sequence'];
    $sequence_length = $seq['sequence_length'];
    $tax_id = $seq['tax_id'];

    $stmt = $pdo->prepare("INSERT IGNORE INTO sequences (
        accession_id, organism, protein_name, gene_name,
        sequence_length, sequence, taxonomic_group, tax_id
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        $accession_id, $organism, $protein_name, $gene_name, 
        $sequence_length, $sequence, 'Arabidopsis', $tax_id
    ]);
}

echo "Data insertion completed successfully!";
?> 