#!/bin/python3

#### OPTIONAL PYTHON ICA ####
#----------B200694----------#



### PART 1a ###

# The user of your code will specify the protein family, and the taxonomic group, and then your code
# will need to obtain the relevant protein sequence data, and perform all subsequent analyses and 
# outputs in the user's space on the MSc server.

# import modules #

import subprocess
import os
import re
import pandas as pd
import sys

# input & output #

taxa = str(input("Please enter a taxon to search for: "))
proteinfamily = str(input("Please enter a protein family to search for: "))
outfile = "sequences.fasta"


# search commands #

esearch_cmd = f"esearch -db protein -query '{taxa}[Organism] AND {proteinfamily}[Protein]'"
xtract_cmd = f"xtract -pattern ENTREZ_DIRECT -element Count"
efetch_cmd = f"efetch -format fasta"

# esearch #

num_results = subprocess.run(f"{esearch_cmd} | {xtract_cmd}", capture_output=True, shell=True, text=True).stdout.replace("\n","")
cont = input(f"Your search returned {num_results} sequences. Do you want to download them? y/n\n[It is recommended not to proceed with the download if the number of sequences in the set exceeds 1000]\n")

# efetch #

if cont == "y" :
    print(f"\nDownloading sequences...")
    subprocess.run(f"{esearch_cmd} | {efetch_cmd} > {outfile}", shell=True, text=True)
    if os.path.exists(outfile) :
        print(f"Sequences written to file -> {outfile}")
    else :
        sys.exit(f"File could not be written. Exiting the programme")
else :
    sys.exit("Exiting the programme... Why not try another search?")


### PART 1b ###

# Create a summary file from the fasta file to give the user an idea of what subsets of the sequences they might want to look at

# input & output #

infile = "sequences.fasta"
outfile = "sequences_summary.txt"

# initialise lists #

ids = []
organisms = []
partials = []
fulls = []
seqs = []
lengths = []
predicts = []

# extract info from fasta #

with open(infile, 'r') as file :
    seq = ""
    for line in file :
        line = line.strip()
        if line.startswith(">") :
            id = re.search("^>(\S+)", line)
            organism = re.search("\[(.+)\]$", line)
            ids.append(id.group().replace(">", ""))
            organisms.append(organism.group(1))
            if re.search("partial", line) :
                fulls.append("partial")
            else :
                fulls.append("complete")
            if re.search("PREDICTED", line) :
                predicts.append("Y")
            else :
                predicts.append("N")
            if seq :
                seqs.append(seq)
                lengths.append(len(seq))
                seq = ""
        else :
            seq += line
    if seq:
        seqs.append(seq)
        lengths.append(len(seq))

# create data frame and export as .txt #

df = pd.DataFrame({"ID" : ids , "Organism" : organisms , "Completeness" : \
        fulls , "Predicted" : predicts , "Length" : lengths})
df.to_csv(outfile, sep = "\t", header = True)
if os.path.exists(outfile) :
    print(f"Summary table written to file -> {outfile}")
else :
    sys.exit("File could not be written. Exiting the programme...")

print(f"\nPreview of summary table:\n")
print(df.head(10))

print(f"\nNumber of sequences for each species:\n")
print(df['Organism'].value_counts())

print(f"\nSummary statistics:\n")
print(df.describe(include = 'object'))


## PART 2 ##

# Determine, and plot, the level of conservation between the protein sequences. Here we are wanting
# to establish the degree of similarity within the sequence set chosen. The output should go to screen
# and be saved as a file output.

# subsetting #

subset = input(f"\nWould you like to subset this data before further analysis? y/n : ")
if subset == "y" :
    outfile = "subsetted_sequences.fasta"
    col = input("Which column would you like to subset from? ")
    val = input("Which value would you like to subset by? ")
    subsetted_df = df[df[col] == val]
    id_list = list(subsetted_df["ID"])
    id_outfile = open("id_list.txt", "w")
    for i in range(len(id_list)) :
        id_outfile.write(id_list[i] + "\n")
    id_outfile.close()
    subsetted_fasta = subprocess.run(f"pullseq -i '{infile}' -n id_list.txt > {outfile}", shell = True)
    if os.path.exists(outfile) :
        print(f"Subsetted file created -> {outfile}")
    else :
        sys.exit("File could not be written. Exiting the programme...")

# input & output #

cont = input(f"\nWould you like to continue by aligning these sequences and creating a conservation plot? y/n\n")
if cont != "y" :
    sys.exit("Understood. Exiting the programme...")
if subset == "y" :
    infile = "subsetted_sequences.fasta"
if subset != "y" :
    infile = "sequences.fasta"
outfile = "sequences_aligned.fasta"

# align sequences #

print(f"\nAligning sequences...")
subprocess.run(f"clustalo -i {infile} -o {outfile} -v --force", capture_output=True, shell=True)
print(f"Output file created -> {outfile}")

# input & output #

infile = "sequences_aligned.fasta"
outfile = "plot"

# plot conservation #

winsize = int(input(f"\nOver how many bases would you like to average the alignment quality? "))
print(f"\nProceeding with window size: {winsize}")
subprocess.run(f"plotcon -sequences {infile} -winsize {winsize} -graph png -goutfile {outfile}", capture_output = True, shell = True)
print(f"Plot created -> {outfile}.1.png")


## PART 3 ##

# scan protein sequence(s) of interest with motifs from the PROSITE database, 
# to determine whether any known motifs (domains) are associated with this subset of sequences: 
# were there any, and if so, what were their names?

# input & output #

cont = input("Would you like to run a blastp with a subset of the data? y/n\n")
if cont != "y" :
    sys.exit("Understood. Exiting the programme...")
if subset == "y" :
    infile = "subsetted_sequences.fasta"
else :
    infile = "sequences.fasta"
outfile = "blastoutput.out"
evalue = str(input(f"What e-value would you like to use? Use scientific notation e.g 1e-05\n"))

# run blast #

print(f"\nRunning blastp using an evalue threshold of {evalue}...")
subprocess.run(f"blastp -db swissprot -query {infile} -remote -outfmt 7 -evalue {evalue} > {outfile}", shell = True)
print(f"Blastp complete -> {outfile}")

### END OF SCRIPT ###
