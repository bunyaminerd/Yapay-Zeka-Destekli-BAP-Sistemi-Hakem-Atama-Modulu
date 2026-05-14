import pandas as pd
import re
import nltk
from nltk.corpus import stopwords

# NLTK stopwords'ü indir
nltk.download('stopwords')

# Durak kelimelerini alalım
stop_words = set(stopwords.words('turkish'))

# Temizleme fonksiyonu
def clean_text(text):
    # Küçük harfe çevirme
    text = text.lower()
    # Özel karakterleri kaldırma
    text = re.sub(r'\W', ' ', text)
    # Sayıları kaldırma
    text = re.sub(r'\d+', '', text)
    # Fazla boşlukları kaldırma
    text = re.sub(r'\s+', ' ', text).strip()
    # Durak kelimelerini kaldırma
    text = ' '.join([word for word in text.split() if word not in stop_words])
    return text

# Verileri Yükle
projects = pd.read_csv('projeler.csv')
reviewers = pd.read_csv('hakemcsv.csv')

# Projeler temizliği
projects['cleaned_title'] = projects['Başlık'].apply(clean_text)
projects['cleaned_keywords'] = projects['Anahtar Kelimeler'].apply(clean_text)

# Hakemler temizliği
reviewers['cleaned_expertise'] = reviewers['Uzmanlık Alanları'].apply(clean_text)
reviewers['cleaned_past_projects'] = reviewers['Geçmiş Proje Başlıkları'].apply(clean_text)

# Metin Birleştirme 

projects['bert_input'] = projects['cleaned_title'] + " " + projects['cleaned_keywords']
reviewers['bert_input'] = reviewers['cleaned_expertise'] + " " + reviewers['cleaned_past_projects']

# Sonuçlar
print("Temizlenmiş Projeler:")
print(projects[['Proje ID', 'bert_input']].head())

print("\nTemizlenmiş Hakemler:")
print(reviewers[['Hakem ID', 'bert_input']].head())


projects.to_csv('cleaned_projeler.csv', index=False)
reviewers.to_csv('cleaned_hakemcsv.csv', index=False)