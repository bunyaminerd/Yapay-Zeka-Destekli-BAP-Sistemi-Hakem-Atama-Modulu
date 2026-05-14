from flask import Flask, request, jsonify
from flask_cors import CORS
import csv
import os
import subprocess

app = Flask(__name__)
CORS(app)

PROJECTS_CSV_FILE = 'projeler.csv' # Proje bilgilerinin olduğu dosya
REVIEWERS_CSV_FILE = 'hakemcsv.csv' # Hakem bilgilerinin olduğu dosya


@app.route('/add_project', methods=['POST'])
def add_project():
    data = request.get_json()

    title = data.get('title')
    keywords = data.get('keywords')

    if not title or not keywords:
        return jsonify({'error': 'Eksik veri!'}), 400

    last_id = 0
    if os.path.exists(PROJECTS_CSV_FILE):
        with open(PROJECTS_CSV_FILE, 'r', encoding='utf-8') as file:
            reader = csv.reader(file)
            try:
                next(reader, None)  # Başlığı atla
            except StopIteration:
                pass # Dosya boş olabilir veya sadece başlık olabilir
            for row in reader:
                if row and row[0].isdigit():
                    last_id = max(last_id, int(row[0]))

    new_id = str(last_id + 1) 

    with open(PROJECTS_CSV_FILE, 'a', newline='', encoding='utf-8') as file:
        writer = csv.writer(file)
        if not os.path.exists(PROJECTS_CSV_FILE) or os.path.getsize(PROJECTS_CSV_FILE) == 0 :
             writer.writerow(['Proje ID', 'Proje Başlığı', 'Anahtar Kelimeler'])
        writer.writerow([new_id, title, keywords])

    try:
        subprocess.run(['python', 'onisleme.py'], check=True)
        subprocess.run(['python', 'fasttextmodel.py'], check=True)
        subprocess.run(["python", "minilmmodel.py"], check=True)
    except subprocess.CalledProcessError as e:
        return jsonify({'error': 'İşlem sırasında hata oluştu.', 'details': str(e)}), 500

    return jsonify({'message': 'Proje kaydedildi ve işlemler başarıyla yapıldı.', 'new_id': new_id}), 200

@app.route('/add_reviewer', methods=['POST'])
def add_reviewer():
    data = request.get_json()
    name = data.get('name')
    expertise = data.get('expertise')
    past_projects = data.get('past_projects', '')

    if not name or not expertise:
        return jsonify({'error': 'Eksik veri!'}), 400

    last_id = 0
    file_exists = os.path.exists(REVIEWERS_CSV_FILE)

    if file_exists:
        with open(REVIEWERS_CSV_FILE, 'r', encoding='utf-8') as file:
            reader = csv.reader(file)
            try:
                next(reader, None)  # Başlığı atla
            except StopIteration:
                pass
            for row in reader:
                if row and row[0].isdigit(): 
                    last_id = max(last_id, int(row[0]))
    
    new_id = str(last_id + 1) 

    with open(REVIEWERS_CSV_FILE, 'a', newline='', encoding='utf-8') as file:
        writer = csv.writer(file)
        if not file_exists or os.path.getsize(REVIEWERS_CSV_FILE) == 0:
            writer.writerow(['Hakem ID', 'İsim', 'Uzmanlık Alanları', 'Geçmiş Proje Başlıkları'])
        writer.writerow([new_id, name, expertise, past_projects])
   
    try:
        subprocess.run(['python', 'fasttextmodel.py'], check=True)
    except subprocess.CalledProcessError as e:
        return jsonify({'error': 'FastText modeli çalıştırılırken hata oluştu.', 'details': str(e)}), 500

    return jsonify({'message': 'Hakem kaydedildi ve model güncellendi.', 'new_id': new_id}), 200

if __name__ == '__main__':
    if not os.path.exists(PROJECTS_CSV_FILE):
        with open(PROJECTS_CSV_FILE, 'w', newline='', encoding='utf-8') as file:
            writer = csv.writer(file)
            writer.writerow(['Proje ID', 'Proje Başlığı', 'Anahtar Kelimeler'])
            
    if not os.path.exists(REVIEWERS_CSV_FILE):
        with open(REVIEWERS_CSV_FILE, 'w', newline='', encoding='utf-8') as file:
            writer = csv.writer(file)
            writer.writerow(['Hakem ID', 'İsim', 'Uzmanlık Alanları', 'Geçmiş Proje Başlıkları'])
            
    app.run(host='0.0.0.0', port=5000, debug=True) 