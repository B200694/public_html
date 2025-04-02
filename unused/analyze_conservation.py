#!/usr/bin/env python3
import sys
import subprocess
import matplotlib.pyplot as plt

def main():
    fasta, window, outfile = sys.argv[1], int(sys.argv[2]), sys.argv[3]
    
    # Align with ClustalO
    subprocess.run(f"clustalo -i {fasta} -o {fasta}.aln --outfmt=clustal --force", shell=True)
    
    # Parse alignment
    with open(f"{fasta}.aln") as f:
        seqs = [line.split()[-1] for line in f if line[0] not in (' ', '\n') and len(line.split()) > 1]
    
    # Calculate raw conservation
    scores = []
    for i in range(len(seqs[0])):
        column = [s[i] for s in seqs if s[i] != '-']
        counts = {}
        for aa in column:
            counts[aa] = counts.get(aa, 0) + 1
        scores.append(max(counts.values())/len(seqs) if counts else 0)
    
    # Apply window (works for window=1 too)
    smoothed = []
    half = window//2
    for i in range(len(scores)):
        start = max(0, i - half)
        end = min(len(scores), i + half + 1)
        smoothed.append(sum(scores[start:end])/(end-start))
    
    # Save plot
    plt.figure(figsize=(12, 4))
    plt.plot(smoothed, color='#EE9292')
    plt.xlabel('Position')
    plt.ylabel(f'Conservation (%) (Window: {window})')
    plt.grid(alpha=0.3)
    plt.savefig(outfile, dpi=300, bbox_inches='tight')
    plt.close()

if __name__ == "__main__":
    main()