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
$command = "clustalo -i $fasta_file -o $aln_file -v --force";
$output = shell_exec($command);
$command = "plotcon -sequences $aln_file -winsize $window_size -graph png -goutfile $plot_file";
$output = shell_exec($command);

// Handle the .1 suffix that plotcon adds
$actual_plot_file = $plot_file . ".1.png";
if (file_exists($actual_plot_file)) {
    rename($actual_plot_file, $plot_file);
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
    <p style="text-align: center;">Taxonomic Group: <?php echo htmlspecialchars($taxon); ?></p>
    <p style="text-align: center;">Protein Family: <?php echo htmlspecialchars($protein_family); ?></p>
    <br><br>

    <div class="plot-container">
        <img src="<?php echo htmlspecialchars($plot_file); ?>" alt="Conservation Plot">
    </div>

    <br><br>
    <footer>
        <p><b>&copy; 2025 B200694 </b></p>
    </footer>

    <?php
    // Clean up files that are no longer needed
    if (isset($_SESSION['temp_files'])) {
        // Clean up FASTA files
        foreach (glob("temp/*.fasta") as $file) {
            if (!in_array($file, $_SESSION['temp_files']['fasta'])) {
                @unlink($file);
            }
        }
        
        // Clean up alignment files
        foreach (glob("temp/*.aln") as $file) {
            if (!in_array($file, $_SESSION['temp_files']['alignments'])) {
                @unlink($file);
            }
        }
        
        // Clean up old plot files
        foreach (glob("temp/*.png") as $file) {
            if (!in_array($file, $_SESSION['temp_files']['plots'])) {
                @unlink($file);
            }
        }
        
        // Clean up NCBI JSON files that are no longer needed
        foreach (glob("temp/*.json") as $file) {
            if (!in_array($file, $_SESSION['temp_files']['data'])) {
                @unlink($file);
            }
        }
    }
    ?>
</body>
</html> 