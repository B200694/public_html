<?php

require_once 'includes/session_manager.php';
require_once 'includes/login.php';

// Check if we have search results
if (empty($_SESSION['search_data']['source'])) {
    die("No search results found. Please perform a search first.");
}

// Get analysis parameters
$taxon = $_SESSION['search_data']['taxon'];
$protein_family = $_SESSION['search_data']['protein_family'];

// Create unique filenames from current session
$session_id = session_id();
$fasta_file = "temp/temp_{$session_id}.fasta";
$motif_output_file = "temp/temp_{$session_id}.txt";

// Track these files in session
array_unshift($_SESSION['temp_files']['fasta'], $fasta_file);
array_unshift($_SESSION['temp_files']['motifs'], $motif_output_file);

// Get sequences based on search source
if ($_SESSION['search_data']['source'] === 'database') {
    $stmt = $pdo->prepare("SELECT * FROM sequences WHERE protein_name = ? AND taxonomic_group = ?");
    $stmt->execute([$protein_family, $taxon]);
    $sequences = $stmt->fetchAll();
} else {
    // Load from JSON file for NCBI results
    $ncbi_file = $_SESSION['search_data']['ncbi_file'];
    if (!file_exists($ncbi_file)) {
        die("NCBI results expired. Please perform your search again.");
    }
    $sequences = json_decode(file_get_contents($ncbi_file), true);
}

if (empty($sequences)) {
    die("No sequences found in the search results.");
}

// Create FASTA file
$fasta_content = "";
foreach ($sequences as $seq) {
    $fasta_content .= ">" . $seq['accession_id'] . " " . $seq['organism'] . "\n";
    $fasta_content .= $seq['sequence'] . "\n";
}
file_put_contents($fasta_file, $fasta_content);



// Run analysis
$command = "patmatmotifs -full -sequence $fasta_file -outfile $motif_output_file 2>&1";
$output = shell_exec($command);

$lines = file($motif_output_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// Initialize variables
$motif_results = [];
$current_sequence = ""; // Track the current sequence
$motif = $start_pos = $end_pos = $length = null;

foreach ($lines as $line) {
    // Match and extract sequence name using regular expression
    if (preg_match('/# Sequence:\s+([^\s]+)/', $line, $seq_match)) {
        $current_sequence = $seq_match[1];  // Capture the sequence name
    }

    // Match and extract motif using regular expression
    if (preg_match('/Motif = ([^\s]+)/', $line, $motif_match)) {
        $motif = trim($motif_match[1]); // Extract motif name
    }

    // Match and extract start position
    if (preg_match('/Start = position (\d+)/', $line, $start_match)) {
        $start_pos = trim($start_match[1]); // Extract start position
    }

    // Match and extract end position
    if (preg_match('/End = position (\d+)/', $line, $end_match)) {
        $end_pos = trim($end_match[1]); // Extract end position
    }

    // Match and extract motif length
    if (preg_match('/Length = (\d+)/', $line, $length_match)) {
        $length = trim($length_match[1]); // Extract motif length
    }

    // Store the result if we have all required information
    if (!empty($current_sequence) && isset($motif) && isset($start_pos) && isset($end_pos) && isset($length)) {
        $motif_results[] = [
            'sequence' => $current_sequence,
            'motif' => $motif,
            'start' => $start_pos,
            'end' => $end_pos,
            'length' => $length
        ];

        // Reset variables after storing the result (to handle multiple motifs if needed)
        $motif = $start_pos = $end_pos = $length = null;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Motif Scanning Results</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <hr style="border: 0; height: 6px; background-color: #BBE06A;">
    <header>
        <h1>All The Common Ground</h1>
        <p>A tool for exploring protein sequence conservation and motifs across taxonomic groups.</p>
        <nav>
            <ul>
                <li><a href="index.php">Search</a></li>
                <li><a href="about.html">About</a></li>
            </ul>
        </nav>
    </header>
    <hr style="border: 0; height: 6px; background-color: #EE9292;">
    <br>
    <h1>Motif Scanning Results</h1>
    <br>
    <p style="text-align: center;">Taxonomic Group: <?php echo htmlspecialchars($taxon); ?></p>
    <p style="text-align: center;">Protein Family: <?php echo htmlspecialchars($protein_family); ?></p>
    <br><br>
    <table border="1">
        <thead>
            <tr>
                <th>Sequence</th>
                <th>Motif</th>
                <th>Start Position</th>
                <th>End Position</th>
                <th>Length</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($motif_results as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['sequence']); ?></td>
                    <td><?php echo htmlspecialchars($row['motif']); ?></td>
                    <td><?php echo htmlspecialchars($row['start']); ?></td>
                    <td><?php echo htmlspecialchars($row['end']); ?></td>
                    <td><?php echo htmlspecialchars($row['length']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <br><br>
    <footer>
        <p><b>&copy; 2025 B200694 </b></p>
    </footer>
</body>
</html>