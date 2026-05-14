import pandas as pd
import fasttext
import numpy as np
from sklearn.metrics.pairwise import cosine_similarity

model_path = 'cc.tr.300.bin'
ft_model = fasttext.load_model(model_path)

projects = pd.read_csv('cleaned_projeler.csv')
reviewers = pd.read_csv('cleaned_hakemcsv.csv')

def embed_text(text):
    words = text.split() 
    word_vectors = np.array([ft_model.get_word_vector(word) for word in words]) 
    return np.mean(word_vectors, axis=0)  # Vektörlerin ortalamasını alma

projects['fasttext_embeddings'] = projects['bert_input'].apply(embed_text)
reviewers['fasttext_embeddings'] = reviewers['bert_input'].apply(embed_text)

optimal_matches = []
for _, project_row in projects.iterrows():
    similarities = []
    for _, reviewer_row in reviewers.iterrows():
        score = cosine_similarity(
            [project_row['fasttext_embeddings']], 
            [reviewer_row['fasttext_embeddings']]
        )[0][0]
        
        similarities.append({
            'Proje ID': project_row['Proje ID'],
            'Hakem ID': reviewer_row['Hakem ID'],
            'Benzerlik': score
        })
    
    # En yüksek 3 skoru seç
    top_matches = sorted(similarities, key=lambda x: x['Benzerlik'], reverse=True)[:10]
    optimal_matches.extend(top_matches)

optimal_df = pd.DataFrame(optimal_matches)

optimal_df.to_csv('fasttext_top3_similarity_results.csv', index=False)

print("Her proje için en iyi 3 hakem yazıldı.")
print(optimal_df.head())