# Laravel e-Arşiv Fatura

Bu Laravel paketi, GİB e-Arşiv Portalı ile doğrudan bağlantı kurarak kullanıcıların e-Arşiv faturalarını otomatik olarak oluşturmasını, önizlemesini almasını, indirmesini ve yönetmesini sağlar.

---
## Gereksinimler

## Depo Durumu

![PHP](https://img.shields.io/packagist/dependency-v/bercanozcan/earsiv/php?style=plastic)
![Laravel](https://img.shields.io/packagist/v/bercanozcan/earsiv?style=plastic)
![Son Commit](https://img.shields.io/github/last-commit/bercanozcan/earsiv)
![Açık Konular](https://img.shields.io/github/issues/bercanozcan/earsiv)
![Kapalı Konular](https://img.shields.io/github/issues-closed/bercanozcan/earsiv)
![Yıldızlar](https://img.shields.io/github/stars/bercanozcan/earsiv)
![Çatallar](https://img.shields.io/github/forks/bercanozcan/earsiv)

## 🔧 Kurulum

### 1. Composer ile yükleme

```
composer require bercanozcan/earsiv
```

### 2. Yapılandırma dosyasını yayınlayın

```
php artisan vendor:publish --tag=config --provider="Bercanozcan\\Earsiv\\EarsivServiceProvider"
```

Bu işlem `config/earsiv.php` dosyasını oluşturur.

---

## ⚙️ Yapılandırma

`config/earsiv.php` dosyası üzerinden indirilecek dosyaların kaydedileceği yolu ayarlayabilirsiniz:

```php
return [
    'download_path' => 'faturalar', // storage/app/faturalar
];
```

---

## 🚀 Kullanım

### Giriş ve fatura oluşturma

```php
use Bercanozcan\Earsiv\Gib;

$gib = app(Gib::class)
    ->setTestCredentials()
    ->login();

$invoice = [
    'faturaTarihi' => now()->format('d/m/Y'),
    'saat' => now()->format('H:i:s'),
    'vknTckn' => '11111111111',
    'aliciUnvan' => 'Demo Müşteri A.Ş.',
    'vergiDairesi' => 'TEST VD',
    'malHizmetTable' => [[
        'malHizmet' => 'Danışmanlık Hizmeti',
        'miktar' => 1,
        'birim' => 'HUR',
        'birimFiyat' => 1000,
        'fiyat' => 1000,
        'kdvOrani' => 20,
        'kdvTutari' => 200,
        'malHizmetTutari' => 1000,
        'iskontoOrani' => 0,
        'iskontoTutari' => 0,
        'iskontoArttm' => 'İskonto'
    ]],
    'matrah' => 1000,
    'hesaplanankdv' => 200,
    'vergilerToplami' => 200,
    'vergilerDahilToplamTutar' => 1200,
    'odenecekTutar' => 1200,
    'tip' => 'İskonto',
    'not' => 'Bu bir demo faturadır.',
];

$ettn = $gib->createDraft($invoice);

echo "Fatura oluşturuldu: $ettn";
```

---

### Fatura önizlemesi alma (HTML)

```php
$html = $gib->getHtml($ettn);
```

---

### Faturayı diske kaydetme

```php
$path = $gib->saveToDisk($ettn);
// storage/app/faturalar/xxxx.zip
```

---

## 🧪 Test Ortamı

Test kullanıcıları `setTestCredentials()` metodu ile otomatik alınır.  
Tüm işlemler [earsivportaltest.efatura.gov.tr](https://earsivportaltest.efatura.gov.tr) üzerinde gerçekleştirilir.

---

## 📁 Dosya Sistemi

Faturalar Laravel `Storage` sistemi ile belirtilen diske (örnek: `local`, `public`, `s3`) kaydedilir.  
`.env` üzerinden kontrol edilen `FILESYSTEM_DISK` değişkeni aktif olarak kullanılır.

---

## ✅ Desteklenen İşlemler

- Giriş (login)
- Fatura oluşturma (`createDraft`)
- Fatura önizleme (`getHtml`)
- Fatura indirme (`saveToDisk`)
- Kullanıcı bilgisi sorgulama (`getUserData`)
- Alıcı bilgisi alma (`getRecipientData`)
- İptal ve itiraz talepleri (yakında)
- SMS ile imzalama (yakında)

---

## 👨‍💻 Geliştirici

**Bercan Özcan**  
[GitHub - @bercanozcan](https://github.com/bercanozcan)

---

## 🛡 Uyarı

Bu paket GİB test ortamında denenmiştir.  
Gerçek ortamda kullanmadan önce verilerin doğruluğunu ve teknik dökümana uygunluğunu mutlaka test ediniz.

---

## 📄 Lisans

Bu proje MIT Lisansı ile lisanslanmıştır.
