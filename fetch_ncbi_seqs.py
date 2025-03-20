#!/bin/python3

from Bio import Entrez, SeqIO
import json
import sys

def fetch_seqs(taxon, protein_family) :
    
    # Initialise search
    Entrez.email = "s2150996@ed.ac.uk"
    Entrez.api_key = "bb315041260b19996a8e2a6cbbedfd2b9308"
    query = f'"{taxon}"[Organism] AND "{protein_family}"[Protein]'
    
    # Execute search
    handle = Entrez.esearch(db="protein", term=query) # search results stored in XML format
    search_results = Entrez.read(handle) # search results read in as dictionary
    id_list = search_results["IdList"] # retrieve list of sequence ids from dictionary
    records = Entrez.efetch(db="protein", id=",".join(id_list), rettype="gb", retmode="text") # create new handle storing sequence information
    
    # Obtain relevant data from records
    sequences = []
    for record in SeqIO.parse(records, "genbank"):
        
        accession_id = record.id
        organism = record.annotations.get("organism", "Unknown")
        sequence = str(record.seq) if record.seq else "Unknown"
        sequence_length = len(sequence)
        
        # Search through features for protein & gene name
        gene_name = "Unknown"
        protein_name = "Unknown"
        tax_id = "Unknown"
        for feature in record.features:
            if feature.type == "CDS":
                gene_name = feature.qualifiers.get("gene", ["Unknown"])[0]
            if feature.type == "Protein":
                protein_name =  feature.qualifiers.get("product", ["Unknown"])[0]
            if feature.type == "source":
                tax_id = feature.qualifiers.get("db_xref", ["Unknown"])[0].replace("taxon:", "")

        # Append the parsed data to the list
        sequences.append({
            "accession_id": accession_id,
            "organism": organism,
            "protein_name": protein_name,
            "gene_name": gene_name,
            "sequence": sequence,
            "sequence_length": sequence_length,
            "taxon": taxon,
            "tax_id": tax_id,
            })

    # Return the parsed data as JSON
    print(json.dumps(sequences))
    return json.dumps(sequences)
       
# Get arguments from the command line
taxon = sys.argv[1]
protein_family = sys.argv[2]

# Call the fetch_seqs function
fetch_seqs(taxon, protein_family)
