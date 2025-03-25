<?php

require_once 'includes/session_manager.php';
require_once 'includes/login.php';

// Check if we have search results
if (empty($_SESSION['search_data']['source'])) {
    die("No search results found. Please perform a search first.");
}

// Get analysis parameters
$window_size = (int)($_POST['window_size'] ?? 10);
$taxon = $_SESSION['search_data']['taxon'];
$protein_family = $_SESSION['search_data']['protein_family'];

// Create unique filenames from current session
$session_id = session_id();
$fasta_file = "temp/temp_{$session_id}.fasta";
$aln_file = "temp/temp_{$session_id}.aln";
$plot_file = "temp/conservation_plot_{$session_id}.png";

// Track these files in session
array_unshift($_SESSION['temp_files']['fasta'], $fasta_file);
array_unshift($_SESSION['temp_files']['alignments'], $aln_file);
array_unshift($_SESSION['temp_files']['plots'], $plot_file);

try {
    // Get sequences based on search source
    if ($_SESSION['search_data']['source'] === 'database') {
        $stmt = $pdo->prepare("SELECT * FROM sequences WHERE protein_name = ? AND taxonomic_group = ?");
        $stmt->execute([$protein_family, $taxon]);
    } else {
        $stmt = $pdo->query("SELECT * FROM {$_SESSION['search_data']['temp_table']}");
    }
    
    $sequences = $stmt->fetchAll();
    
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
    $command = "python3 analyze_conservation.py '$fasta_file' $window_size 2>&1";
    $output = shell_exec($command);

    // Parse results
    $json_start = strpos($output, '{');
    $results = $json_start !== false ? json_decode(substr($output, $json_start), true) : null;

    if (!$results) {
        throw new Exception("Analysis failed: " . $output);
    }

} catch (Exception $e) {
    // Clean up on error
    @unlink($fasta_file);
    @unlink($aln_file);
    @unlink($plot_file);
    die($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conservation Analysis Results</title>
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
    <h1>Conservation Analysis Results</h1>
    <br>
    <p style="text-align: center;">Taxonomic Group: <?php echo htmlspecialchars($_POST['taxon']); ?></p>
    <p style="text-align: center;">Protein Family: <?php echo htmlspecialchars($_POST['protein_family']); ?></p>
    <br><br>

    <div class="analysis-summary">
        <h3>Analysis Summary</h3>
        <p><strong>Window Size:</strong> <?php echo htmlspecialchars($window_size); ?></p>
        <p><strong>Average Conservation:</strong> <?php echo number_format($results['average_conservation'], 2); ?>%</p>
        <p><strong>Maximum Conservation:</strong> <?php echo number_format($results['max_conservation'], 2); ?>%</p>
        <p><strong>Minimum Conservation:</strong> <?php echo number_format($results['min_conservation'], 2); ?>%</p>
    </div>

    <div class="plot-container">
        <img src="<?php echo htmlspecialchars($results['plot_file']); ?>" alt="Conservation Plot">
    </div>

    <br><br>
    <footer>
        <p><b>&copy; 2025 B200694 </b></p>
    </footer>
</body>
</html> 