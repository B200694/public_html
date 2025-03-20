<?php

// Access MySQL database
require_once 'login.php';

// Retrieve user input from form

$protein_family = $_POST['protein_family'];
$taxon = $_POST['taxon'];

// Validate inputs

if (empty($protein_family) || empty($taxon)) {
    die("Please fill out all fields.");
}

// Check if data exists in MySQL

$stmt = $pdo->prepare("SELECT * FROM sequences WHERE protein_name = ? AND taxonomic_group = ?");
$stmt->execute([$protein_family, $taxon]);
$data = $stmt->fetch();

// If it doesn't exist in MySQL, run an NCBI search

if (!$data) {
	
	// Run the script & load the output
	
	$output = shell_exec("python3 fetch_ncbi_seqs.py '$taxon' '$protein_family' 2>&1");
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

		$stmt = $pdo->prepare("
			INSERT IGNORE INTO sequences (
        			accession_id, organism, protein_name, gene_name,
        			sequence_length, sequence, taxonomic_group, tax_id
    			) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

		$stmt->execute([
    			$accession_id, $organism, $protein_name, $gene_name, $sequence_length, $sequence, $taxon, $tax_id
		]);
	}
}

// Redirect to results page
header("Location: results.php?taxon=" . urlencode($taxon) . "&protein_family=" . urlencode($protein_family));
exit();
?>
