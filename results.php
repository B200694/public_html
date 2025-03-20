<?php
// Access MySQL database
require_once 'login.php';

// Retrieve search parameters from URL
$taxon = $_GET['taxon'];
$protein_family = $_GET['protein_family'];

// Fetch data from MySQL
$stmt = $pdo->prepare("SELECT * FROM sequences WHERE protein_name = ? AND taxonomic_group = ?");
$stmt->execute([$protein_family, $taxon]);
$results = $stmt->fetchAll();

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
    <table border="1">
        <thead>
            <tr>
		<th>Accession ID</th>
		<th>Taxonomic Group</th>
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
		<td><?php echo htmlspecialchars($row['taxonomic_group']); ?></td>
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
