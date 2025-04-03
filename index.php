<?php
// Start session for potential session messages
require_once 'includes/session_manager.php';
session_regenerate_id(true);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <hr style="border: 0; height: 6px; background-color: #BBE06A;">
    <header>
        <h1>Protif</h1>
        <p>A tool for exploring protein sequence conservation and motifs across taxonomic groups.</p>
        <nav>
            <ul>
                <li><a href="index.php">Search</a></li>
                <li><a href="help.html">Help</a></li>
                <li class="dropdown">
                    <a href="about.html">About</a>
                    <ul class="dropdown-menu">
                        <li><a href="content.html">Content</a></li>
                        <li><a href="credits.html">Credits</a></li>
                    </ul>  
                </li>
            </ul>
        </nav>
    </header>
    <hr style="border: 0; height: 6px; background-color: #EE9292;">
    <main>
        <br>
        <h1>Search</h1>
        <br>
        <div class = "search-container">
            <form action="search.php" method="get">
                <div class="form-group">
                    <label for="taxon">Taxonomic Group:</label>
                    <input type="text" id="taxon" name="taxon" placeholder="e.g Aves" required>
                </div>
                <div class="form-group">
                    <label for="protein_family">Protein Family:</label>
                    <input type="text" id="protein_family" name="protein_family" placeholder="e.g glucose-6-phosphatase" required>
                </div>
                <div class="example-buttons">
                    <label>Examples:</label>
                    <button type="button" onclick="fillExample1()">1</button>
                    <button type="button" onclick="fillExample2()">2</button>
                </div>
                
                <button type="submit">Search</button>
            </form>
        </div>
        <br>
        <div class = "info-box">
            <p><b>Protif</b> is an interface for exploring protein sequence conservation and motifs across taxonomic groups.</p>
            <br>
            <p>To use the tool, simply enter the taxonomic group and protein you are interested in and click the search button. Searches that are not found in the local database will be queried from NCBI.</p>
            <br>
            <p>Two example datasets are provided, be sure to check them out!<p>
        </div>
        <br>
        <br>
    </main>
    <footer>
        <p><b>B200694 IWD2 2025</b></p>
    </footer>

    <script>
        function fillExample1() {
            document.getElementById('taxon').value = 'Aves';
            document.getElementById('protein_family').value = 'glucose-6-phosphatase';
        }

        function fillExample2() {
            document.getElementById('taxon').value = 'Arabidopsis';
            document.getElementById('protein_family').value = 'acetyl-CoA carboxylase';
        }
    </script>
</body>
</html>
