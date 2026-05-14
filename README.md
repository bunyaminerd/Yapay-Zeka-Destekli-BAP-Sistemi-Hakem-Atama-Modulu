# BAP Projesi Hakem Atama Sistemi

Bu sistem, üniversitelerde yürütülen **BAP (Bilimsel Araştırma Projeleri)** için hakem atama sürecini modern, güvenli ve verimli bir şekilde yönetmek üzere geliştirilmiş **web tabanlı bir uygulamadır**.

## 🧩 Özellikler

- 📁 **Proje Yönetimi**  
  Proje başvurularının sisteme eklenmesi, güncellenmesi ve takibi yapılabilir.

- 👥 **Hakem Yönetimi**  
  Hakemlerin akademik uzmanlık alanları, geçmiş görevleri ve uygunluk durumları sisteme kaydedilir. Sistem, hakemlerin bilgilerini analiz ederek öneri mekanizmasında kullanır.

- 🤖 **Yapay Zeka Destekli Otomatik Hakem Atama**  
  Proje ve hakem bilgileri üzerinden benzerlik analizleri yapılır. Bu analizlerde **FastText** ve **MiniLM (SentenceTransformer)** modelleri ile metinsel içeriklerden anlamsal vektörler çıkarılır ve **cosine similarity** algoritması ile uygun hakem eşleşmeleri yapılır.

- 🔐 **Güvenli Kullanıcı Girişi**  
  Tüm kullanıcılar şifrelenmiş giriş sistemi ile doğrulanır.

- 🌐 **Türkçe Arayüz**  
  Kullanıcı dostu ve sade Türkçe arayüz sayesinde her düzeyden kullanıcı rahatlıkla işlem yapabilir.

## 🛡️ Güvenlik Notları

- ✅ **SQL Enjeksiyon Koruması**: PDO ve parametreli sorgular kullanılır.  
- ✅ **XSS (Cross-site Scripting) Önlemleri**: Giriş verileri filtrelenerek HTML kod enjekte edilmesi engellenir.  
- ✅ **Brute Force Koruması**: Yanlış şifre denemeleri belli bir sınırı aştığında kullanıcı geçici olarak engellenir.  

## 🛠️ Geliştirme

Bu proje aşağıdaki teknolojiler ile geliştirilmiştir:

- **Backend**: PHP  
- **Veritabanı**: MySQL  
- **Frontend**: HTML5, CSS3, JavaScript  
  - **UI Framework**: Bootstrap 4  
  - **İkonlar**: Font Awesome  
- **Yapay Zeka / NLP**: Python, FastText, MiniLM (SentenceTransformer)  
- **Veri İşleme**: Pandas, NumPy  
- **Benzerlik Algoritması**: Cosine Similarity  

## 🔍 Yapay Zeka Süreci

1. Proje özetleri ve hakem uzmanlık alanları metin olarak analiz edilir.  
2. Bu metinler **FastText** ve **MiniLM** modelleri ile vektörleştirilir.  
3. Her proje için, tüm hakemlerle **anlamsal benzerlik skoru** hesaplanır.  
4. En uygun skorları alan hakemler sistem tarafından önerilir.  
5. Önerilen hakem listesi yöneticiye sunulur; son karar yöneticiye aittir.  

