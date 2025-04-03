import sys
import pandas as pd
import matplotlib.pyplot as plt

def create_motif_plot(data_file, session_id):
    # Read the JSON data into a pandas DataFrame
    df = pd.read_json(data_file)
    
    # Count motif frequencies
    motif_counts = df['motif'].value_counts()
    
    # Get top 10 most common motifs
    top_motifs = motif_counts.head(10)

    colors = ['#D67676', '#BBE06A']
    
    # Create the plot
    plt.figure(figsize=(12, 6))
    bars = plt.bar(top_motifs.index, motif_counts)

    for i, bar in enumerate(bars):
        bar.set_color(colors[i % len(colors)])
    
    # Customize the plot
    plt.xticks(rotation=45, ha='right')
    plt.xlabel('Motif Name')
    plt.ylabel('Frequency')
    plt.title('Most Common Protein Motifs')
    
    # Adjust layout to prevent label cutoff
    plt.tight_layout()
    
    # Save the plot
    output_file = f'temp/motif_plot_{session_id}.png'
    plt.savefig(f'{output_file}')
    plt.close()
    
    return output_file

json_file = sys.argv[1]
session_id = sys.argv[2]
    
# Create and save the plot
output_file = create_motif_plot(json_file, session_id)
print(output_file)