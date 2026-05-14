import pandas as pd
import numpy as np
from sentence_transformers import SentenceTransformer
from sklearn.metrics.pairwise import cosine_similarity


model = SentenceTransformer('paraphrase-multilingual-MiniLM-L12-v2')


projects = pd.read_csv('cleaned_projeler.csv')
reviewers = pd.read_csv('cleaned_hakemcsv.csv')


projects['embedding'] = projects['bert_input'].apply(lambda x: model.encode(x, convert_to_numpy=True))
reviewers['embedding'] = reviewers['bert_input'].apply(lambda x: model.encode(x, convert_to_numpy=True))


optimal_matches = []
for _, project_row in projects.iterrows():
    similarities = []
    for _, reviewer_row in reviewers.iterrows():
        score = cosine_similarity(
            [project_row['embedding']],
            [reviewer_row['embedding']]
        )[0][0]

        similarities.append({
            'Proje ID': project_row['Proje ID'],
            'Hakem ID': reviewer_row['Hakem ID'],
            'Benzerlik': score
        })

    # En iyi 10 eşleşmeyi al
    top_matches = sorted(similarities, key=lambda x: x['Benzerlik'], reverse=True)[:10]
    optimal_matches.extend(top_matches)


optimal_df = pd.DataFrame(optimal_matches)
optimal_df.to_csv('minilm12v2_top10_similarity_results.csv', index=False)

print("Her proje için en iyi 10 hakem yazıldı.")
print(optimal_df.head())