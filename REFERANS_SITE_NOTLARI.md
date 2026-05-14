# Referans Site Analiz Notlari

Kaynak: https://go.lojiteam.com/businesses

Bu dosya, yeni sistemi kurarken referans alinacak menu ve sayfa icerigi ozetini tutar.

## Sol Navigasyon Menu Yapisi

- Guncel Durum
- Siparisler (acilir)
  - Teslim Edilenler
  - Iptal Edilenler
- Isletmeler
- Kuryeler
- Basvurular (acilir)
  - Restoran
  - Kurye
- Harita
- Cari Hesap
- Raporlar
- Kurye Raporlari
- Restoran Raporlari
- Yonetim
- Ayarlar
- Kontor Yukle

## Sayfa Patternleri

### 1) Guncel Durum / Siparisler
- Ustte durum anahtarlari (Istatistikler, Siparisler, Harita)
- Sekmeler (ornek: Tumu)
- Arama kutusu
- Sutunlar butonu
- Siparis tablosu + sayfalama

### 2) Teslim Edilen Siparisler
- Baslik: Teslim Edilen Siparisler
- Tarih araligi filtreleri (baslangic / bitis)
- Filtreler:
  - Tum Isletmeler
  - Tum Siparis Kanallari
  - Tum Personeller
  - Odeme Yontemi
- KPI kartlari:
  - Paket Sayisi
  - Paket Ortalama Tutari
  - Toplam Satis Tutari
- Arama + sutun secimi + tablo + sayfalama

### 3) Iptal Edilen Siparisler
- Baslik: Iptal Edilen Siparisler
- Teslim edilenlerle ayni filtre ve KPI yapisi
- Arama + tablo + sayfalama

### 4) Isletmeler
- Arama
- Sutunlar
- Satir bazli durum/toggle
- Satir aksiyonlari:
  - Ucretlendirme
  - Operasyon Ayarlari
  - Baglan
  - Ayril

### 5) Kuryeler
- Ust aksiyon: Yeni Kurye Ekle
- Arama + sutunlar
- Satir bazli secimler / durumlar
- Satir bazli aksiyonlar (ornek: Sil)

### 6) Basvurular
- Alt menu:
  - Restoran
  - Kurye

## Uygulama Tarzi Notlari
- Sol menu hiyerarsik (grup + alt grup + alt link)
- Liste sayfalarinda ortak dil:
  - arama
  - filtre
  - sutun secimi
  - tablo
  - sayfalama
- KPI kartlariyla listeyi birlestiren rapor tarzi ekranlar var

## Ileride Yapilacaklar (Bu Nota Gore)
- Bu menu agaci birebir uygulanacak
- Sayfa basliklari ve aksiyon adlari referansa yakin tutulacak
- Liste bileşenleri ortak bir standarda baglanacak (filtre/arama/sutun/sayfalama)

