<?php
// Use session manager instead of direct session_start()
require_once 'includes/session_manager.php';

// Access MySQL database
require_once 'includes/login.php';

// Retrieve user input from form
$protein_family = $_GET['protein_family'] ?? '';
$taxon = $_GET['taxon'] ?? '';

// Store search parameters in session
$_SESSION['search_data']['taxon'] = $taxon;
$_SESSION['search_data']['protein_family'] = $protein_family;

// Validate inputs
if (empty($protein_family) || empty($taxon)) {
    die("Please fill out all fields.");
}

// Clean up any old temporary tables
if (!empty($_SESSION['search_data']['temp_table'])) {
    $old_table = $_SESSION['search_data']['temp_table'];
    $pdo->exec("DROP TEMPORARY TABLE IF EXISTS `$old_table`");
}

// Check for example searches
if ($taxon == "Aves" && $protein_family == "glucose-6-phosphatase") {
    $stmt = $pdo->prepare("SELECT * FROM sequences WHERE protein_name = ? AND taxonomic_group = ?");
    $stmt->execute([$protein_family, $taxon]);
    $results = $stmt->fetchAll();
    $_SESSION['search_data']['source'] = 'database';

} elseif ($taxon == "Arabidopsis" && $protein_family == "acetyl-CoA carboxylase") {
    $stmt = $pdo->prepare("SELECT * FROM sequences WHERE protein_name = ? AND taxonomic_group = ?");
    $stmt->execute([$protein_family, $taxon]);
    $results = $stmt->fetchAll();
    $_SESSION['search_data']['source'] = 'database';

} else {
    // NCBI search case
    $temp_table = "temp_results_" . uniqid();
    $pdo->exec("CREATE TEMPORARY TABLE $temp_table (
        accession_id VARCHAR(50),
        tax_id VARCHAR(50),
        organism VARCHAR(255),
        protein_name VARCHAR(255),
        gene_name VARCHAR(255),
        sequence_length INT,
        sequence TEXT
    )");
    
    $output = shell_exec("python3 fetch_ncbi_seqs.py '$taxon' '$protein_family' 2>&1");
    if ($output === null) {
        die("Failed to fetch data from NCBI. Please try again later.");
    }

    $results = json_decode($output, true);
    
    $stmt = $pdo->prepare("INSERT INTO $temp_table VALUES (:accession_id, :tax_id, :organism, :protein_name, :gene_name, :sequence_length, :sequence)");
    
    foreach ($results as $row) {
        $stmt->execute([
            ':accession_id' => $row['accession_id'],
            ':tax_id' => $row['tax_id'],
            ':organism' => $row['organism'],
            ':protein_name' => $row['protein_name'],
            ':gene_name' => $row['gene_name'],
            ':sequence_length' => strlen($row['sequence']),
            ':sequence' => $row['sequence']
        ]);
    }
    
    $_SESSION['search_data']['temp_table'] = $temp_table;
    $_SESSION['search_data']['source'] = 'ncbi';
}

// Display results
?> 

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results</title>
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
    <h1>Search Results</h1>
    <br>
    <p style="text-align: center;">Taxonomic Group: <?php echo htmlspecialchars($taxon); ?></p>
    <p style="text-align: center;">Protein Family: <?php echo htmlspecialchars($protein_family); ?></p>
    <br><br>

    <!-- Filtering and Analysis Controls -->
    <div class="analysis-controls">
        <form action="generate_plot.php" method="post" id="analysisForm" onsubmit="console.log('Form submitted with data:', new FormData(this));">
            <input type="hidden" name="taxon" value="<?php echo htmlspecialchars($taxon); ?>">
            <input type="hidden" name="protein_family" value="<?php echo htmlspecialchars($protein_family); ?>">
            
            <div class="plotcon-options">
                <h3>Conservation Analysis Options</h3>
                <div class="plotcon-group">
                    <label for="window_size">Window Size:</label>
                    <input type="number" id="window_size" name="window_size" min="1" value="10">
                </div>
            </div>

            <button type="submit" class="analysis-button" onclick="console.log('Submit button clicked');">Generate Conservation Plot</button>
        </form>
    </div>

    <table border="1">
        <thead>
            <tr>
			<th>Accession ID</th>
			<th>TaxID</th>
            <th>Organism</th>
            <th>Protein Name</th>
            <th>Gene Name</th>
            <th>Sequence Length</th>
			<th>Sequence</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $row): ?>
            	<tr>
				<td><?php echo htmlspecialchars($row['accession_id']); ?></td>
				<td><?php echo htmlspecialchars($row['tax_id']); ?></td>
                <td><?php echo htmlspecialchars($row['organism']); ?></td>
                <td><?php echo htmlspecialchars($row['protein_name']); ?></td>
                <td><?php echo htmlspecialchars($row['gene_name']); ?></td>
                <td><?php echo htmlspecialchars($row['sequence_length']); ?></td>
                <td><div class="table-container"><?php echo htmlspecialchars($row['sequence']); ?></div></td>
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
