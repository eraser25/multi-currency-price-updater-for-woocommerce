# multi-currency-price-updater-for-woocommerce
WooCommerce eklentisi - Ürün fiyatlarını gerçek zamanlı döviz kurları ile otomatik günceller (USD, EUR, GBP, JPY)

TÜRKÇE KULLANIM KLAVUZU AŞAĞIDADIR.

# Multi-Currency Price Updater for WooCommerce

How to use?

A WordPress plugin that automatically updates WooCommerce product prices using real-time exchange rates for multiple currencies (USD, EUR, GBP, JPY, CAD, TRY).

## Features

- **Real-time Exchange Rates**: Fetches current exchange rates from reliable financial APIs
- **Multiple Currency Support**: USD, EUR, GBP, JPY, CAD, and TRY
- **Automatic Price Updates**: Set custom intervals for automatic price updates
- **Product Management**: Individual product currency settings and base price management
- **Price History**: Track all price changes with detailed history
- **Backup & Restore**: Create price backups before updates and restore when needed
- **Email Alerts**: Get notified when exchange rates reach specified thresholds
- **Bulk Operations**: Update multiple products simultaneously
- **Category Filtering**: Filter products by categories for easier management
- **Dashboard Widget**: Quick overview of current rates and statistics

## Installation

1. Download the plugin files
2. Upload to `/wp-content/plugins/multi-currency-price-updater-for-woocommerce/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Make sure WooCommerce is installed and activated

## Requirements

- WordPress 5.0 or higher
- WooCommerce 3.0 or higher
- PHP 7.4 or higher

## How to Use

### 1. Initial Setup

After activation, go to **Currency Updater** in your WordPress admin menu.

### 2. Configure Settings

1. Navigate to **Currency Updater > Settings**
2. Set your preferences:
   - **Default Currency**: Choose the default currency for new products
   - **Rate Alerts**: Set minimum and maximum rate thresholds
   - **Email Alerts**: Enable/disable email notifications

### 3. Product Configuration

1. Go to **Currency Updater** main page
2. You'll see a list of all your WooCommerce products
3. For each product you want to manage:
   - **Base Price**: Enter the base price in the selected currency
   - **Currency**: Choose the currency for this product
   - **Active**: Check to enable automatic updates for this product

### 4. Update Prices

**Manual Update:**
1. Select products using checkboxes
2. Click **"Update Selected"** to apply current exchange rates

**Automatic Updates:**
1. Set **Update Interval** (in seconds)
2. Click **"Save Interval"**
3. Prices will update automatically at specified intervals

### 5. Backup Management

**Create Backup:**
- Click **"Backup Prices"** before making bulk changes
- Backups are saved automatically with timestamps

**Restore Backup:**
1. Go to **Currency Updater > Backups**
2. Select a backup from the list
3. Click **"Restore"** to revert prices

### 6. Monitor Changes

**Price History:**
- Go to **Currency Updater > Price History**
- View all price changes with dates and exchange rates

**Dashboard Widget:**
- Check current exchange rates on your WordPress dashboard
- See active product count and last update time

## API Information

The plugin uses Turkish Central Bank and other financial APIs to fetch real-time exchange rates. No API key required for basic functionality.

## Support

For support, bug reports, or feature requests, please create an issue on GitHub.

## License

This plugin is licensed under GPL v2 or later.

----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

nasıl kullanılır?

WooCommerce ürün fiyatlarını gerçek zamanlı döviz kurları kullanarak otomatik olarak güncelleyen bir WordPress eklentisi.

## Özellikler

- **Gerçek Zamanlı Döviz Kurları**: Güvenilir finansal API'lerden güncel kurları alır
- **Çoklu Para Birimi Desteği**: USD, EUR, GBP, JPY, CAD ve TRY
- **Otomatik Fiyat Güncellemeleri**: Özel aralıklarla otomatik güncelleme
- **Ürün Yönetimi**: Bireysel ürün para birimi ayarları ve temel fiyat yönetimi
- **Fiyat Geçmişi**: Tüm fiyat değişikliklerini detaylı geçmişle takip edin
- **Yedekleme ve Geri Yükleme**: Güncellemeler öncesi fiyat yedekleri oluşturun
- **E-posta Uyarıları**: Döviz kurları belirlenen eşiklere ulaştığında bildirim alın
- **Toplu İşlemler**: Birden fazla ürünü aynı anda güncelleyin
- **Kategori Filtreleme**: Daha kolay yönetim için ürünleri kategorilere göre filtreleyin
- **Dashboard Widget'ı**: Güncel kurlar ve istatistiklerin hızlı özeti

## Kurulum

1. Eklenti dosyalarını indirin
2. `/wp-content/plugins/multi-currency-price-updater-for-woocommerce/` dizinine yükleyin
3. WordPress admin panelinde 'Eklentiler' menüsünden eklentiyi etkinleştirin
4. WooCommerce'in kurulu ve aktif olduğundan emin olun

## Gereksinimler

- WordPress 5.0 veya üzeri
- WooCommerce 3.0 veya üzeri
- PHP 7.4 veya üzeri

## Nasıl Kullanılır

### 1. İlk Kurulum

Etkinleştirme sonrası, WordPress admin menüsünde **Currency Updater** seçeneğine gidin.

### 2. Ayarları Yapılandırın

1. **Currency Updater > Settings** sayfasına gidin
2. Tercihlerinizi ayarlayın:
   - **Varsayılan Para Birimi**: Yeni ürünler için varsayılan para birimini seçin
   - **Kur Uyarıları**: Minimum ve maksimum kur eşiklerini belirleyin
   - **E-posta Uyarıları**: E-posta bildirimlerini etkinleştirin/devre dışı bırakın

### 3. Ürün Yapılandırması

1. **Currency Updater** ana sayfasına gidin
2. Tüm WooCommerce ürünlerinizin listesini göreceksiniz
3. Yönetmek istediğiniz her ürün için:
   - **Base Price**: Seçilen para biriminde temel fiyatı girin
   - **Currency**: Bu ürün için para birimini seçin
   - **Active**: Bu ürün için otomatik güncellemeleri etkinleştirmek üzere işaretleyin

### 4. Fiyatları Güncelleyin

**Manuel Güncelleme:**
1. Onay kutularını kullanarak ürünleri seçin
2. Güncel döviz kurlarını uygulamak için **"Update Selected"** düğmesine tıklayın

**Otomatik Güncellemeler:**
1. **Update Interval** (saniye cinsinden) ayarlayın
2. **"Save Interval"** düğmesine tıklayın
3. Fiyatlar belirlenen aralıklarla otomatik olarak güncellenecek

### 5. Yedek Yönetimi

**Yedek Oluşturma:**
- Toplu değişiklikler yapmadan önce **"Backup Prices"** düğmesine tıklayın
- Yedekler otomatik olarak zaman damgasıyla kaydedilir

**Yedek Geri Yükleme:**
1. **Currency Updater > Backups** sayfasına gidin
2. Listeden bir yedek seçin
3. Fiyatları geri almak için **"Restore"** düğmesine tıklayın

### 6. Değişiklikleri İzleyin

**Fiyat Geçmişi:**
- **Currency Updater > Price History** sayfasına gidin
- Tüm fiyat değişikliklerini tarihler ve döviz kurlarıyla birlikte görüntüleyin

**Dashboard Widget'ı:**
- WordPress dashboard'unuzda güncel döviz kurlarını kontrol edin
- Aktif ürün sayısını ve son güncelleme zamanını görün

## API Bilgileri

Eklenti, gerçek zamanlı döviz kurlarını almak için Türkiye Cumhuriyet Merkez Bankası ve diğer finansal API'leri kullanır. Temel işlevsellik için API anahtarı gerekmez.

## Destek

Destek, hata raporları veya özellik istekleri için lütfen GitHub'da bir issue oluşturun.

## Lisans

Bu eklenti GPL v2 veya sonraki sürümler altında lisanslanmıştır.
