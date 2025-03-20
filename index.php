<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Protein Sequence Analysis</title>
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
    <main>
	<form action="search.php" method="POST">

        	<label for="taxon">Taxonomic Group:</label>
		<input type="text" id="taxon" name="taxon" placeholder="e.g Aves" required>

                <label for="protein_family">Protein Family:</label>
                <input type="text" id="protein_family" name="protein_family" placeholder="e.g glucose-6-phosphatase" required>

        	<button type="submit">Search</button>
    	</form>
    </main>
    <footer>
	    <p><b>&copy; 2025 B200694 </b></p>
    </footer>
</body>
</html>
