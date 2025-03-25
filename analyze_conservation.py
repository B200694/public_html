#!/usr/bin/env python3

import sys
import json
from Bio import AlignIO, SeqIO
import subprocess
import os

# Set Matplotlib to use non-interactive backend
import matplotlib
matplotlib.use('Agg')

# Set Matplotlib configuration directory to the temp folder
os.environ['MPLCONFIGDIR'] = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'temp')

import matplotlib.pyplot as plt

def perform_alignment(fasta_file):
    # Create alignment file in the same directory as the FASTA file
    aln_file = fasta_file.replace('.fasta', '.aln')
    
    # Perform multiple sequence alignment using Clustal Omega
    try:
        cmd = ["clustalo", "-i", fasta_file, "-o", aln_file, "--outfmt=clustal"]
        result = subprocess.run(cmd, capture_output=True, text=True)
        
        if result.returncode != 0:
            print(json.dumps({"error": f"Clustal Omega error: {result.stderr}"}))
            return None
        
        # Read the alignment
        alignment = AlignIO.read(aln_file, "clustal")
        return alignment
    except Exception as e:
        print(json.dumps({"error": f"Alignment error: {str(e)}"}))
        return None

def calculate_conservation(alignment, window_size):
    # Calculate conservation scores using a sliding window approach
    try:
        num_sequences = len(alignment)
        if num_sequences < 2:
            print(json.dumps({"error": "Need at least 2 sequences for conservation analysis"}))
            return None
            
        alignment_length = alignment.get_alignment_length()
        conservation_scores = []
        
        # Calculate position-specific conservation scores first
        position_scores = []
        for i in range(alignment_length):
            column = alignment[:, i]
            # Count most frequent amino acid
            amino_acids = {}
            for aa in column:
                amino_acids[aa] = amino_acids.get(aa, 0) + 1
            
            # Calculate conservation score (0-100)
            max_count = max(amino_acids.values())
            conservation = (max_count / num_sequences) * 100
            position_scores.append(conservation)
        
        # Apply sliding window
        for i in range(alignment_length - window_size + 1):
            window_scores = position_scores[i:i + window_size]
            window_average = sum(window_scores) / window_size
            conservation_scores.append(window_average)
        
        return conservation_scores
    except Exception as e:
        print(json.dumps({"error": f"Conservation calculation error: {str(e)}"}))
        return None

def plot_conservation(conservation_scores, fasta_file):
    # Create plot file in the same directory as the FASTA file
    plot_file = fasta_file.replace('.fasta', '_plot.png')
    
    # Generate a simple conservation plot
    try:
        plt.figure(figsize=(12, 4))
        
        # Plot conservation scores
        plt.plot(conservation_scores, color='#EE9292', label='Conservation Score')
        
        plt.xlabel('Sequence Position')
        plt.ylabel('Conservation Score (%)')
        plt.grid(True, alpha=0.3)
        
        # Save the plot
        plt.savefig(plot_file, dpi=300, bbox_inches='tight')
        plt.close()
        return plot_file
    except Exception as e:
        print(json.dumps({"error": f"Plot generation error: {str(e)}"}))
        return None

def main():
    try:
        if len(sys.argv) != 3:
            print(json.dumps({"error": "Usage: python analyze_conservation.py <fasta_file> <window_size>"}))
            sys.exit(1)
        
        fasta_file = sys.argv[1]
        window_size = int(sys.argv[2])
        
        # Perform alignment
        alignment = perform_alignment(fasta_file)
        if not alignment:
            sys.exit(1)
        
        # Calculate conservation scores
        conservation_scores = calculate_conservation(alignment, window_size)
        if not conservation_scores:
            sys.exit(1)
        
        # Generate plot
        plot_file = plot_conservation(conservation_scores, fasta_file)
        if not plot_file:
            sys.exit(1)
        
        # Return results as JSON
        results = {
            "conservation_scores": conservation_scores,
            "plot_file": plot_file,
            "average_conservation": sum(conservation_scores) / len(conservation_scores),
            "max_conservation": max(conservation_scores),
            "min_conservation": min(conservation_scores)
        }
        
        print(json.dumps(results))
        
    except Exception as e:
        print(json.dumps({"error": f"Unexpected error: {str(e)}"}))
        sys.exit(1)

if __name__ == "__main__":
    main() 