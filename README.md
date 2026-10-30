# 📱 Babe - Aplikasi Jual Beli Barang Bekas (Android & Localhost XAMPP)

Aplikasi **Babe (Barang Bekas)** adalah platform marketplace C2C untuk menjual dan membeli barang bekas / preloved yang terintegrasi penuh dengan backend **XAMPP Localhost (PHP & MySQL)**.

---

## 🚀 Panduan Menjalankan & Menguji Aplikasi di Localhost

### 1. Membuka di Browser Komputer / PC (Tampilan Mobile)
1. Pastikan modul **Apache** dan **MySQL** di XAMPP Control Panel sudah aktif (Running).
2. Buka browser (Google Chrome / Edge) dan kunjungi:
   👉 **`http://localhost/Babe/`**
3. *(Opsional)* Tekan tombol `F12` lalu aktifkan **Toggle Device Toolbar** (icon smartphone/tablet) di Developer Tools agar tampil dalam resolusi layar smartphone (375px - 430px).

---

### 2. Membuka Langsung di HP (Tanpa Install APK)
Ada 2 cara sangat praktis untuk membuka di HP:
- **Cara 1 (Scan QR Code):**
  1. Hubungkan HP ke jaringan **Wi-Fi yang sama** dengan laptop/komputer.
  2. Buka **`http://localhost/Babe/connect.php`** di browser komputer, atau klik tombol **"Di HP"** di header aplikasi.
  3. Scan QR Code yang muncul menggunakan kamera HP atau Google Lens!
- **Cara 2 (Ketik Link Langsung di Browser HP):**
  1. Sambungkan HP ke Wi-Fi yang sama.
  2. Buka Google Chrome / Safari di HP, lalu ketik alamat:
     👉 **`http://192.168.120.40/Babe/`**
  3. Anda bisa langsung mencoba pasang iklan, unggah foto barang langsung dari kamera HP, jelajah katalog, dan menguji tombol **Chat Penjual via WhatsApp**!

---

### 3. Membuka di Android Studio & Membuat File APK
Project Android lengkap telah disediakan di dalam folder `android_app/`:
1. Buka aplikasi **Android Studio**.
2. Pilih menu **File > Open**, lalu arahkan ke folder:
   `c:\xampp\htdocs\Babe\android_app`
3. Tunggu hingga Gradle Sync selesai.
4. Untuk menjalankan di **Android Emulator**:
   - Jika memakai Android Emulator resmi dari Android Studio, ubah alamat `APP_URL` di `MainActivity.java` menjadi:
     `public static final String APP_URL = "http://10.0.2.2/Babe/";`
5. Untuk membuat file **APK**:
   - Klik menu **Build > Build Bundle(s) / APK(s) > Build APK(s)**.
   - File APK akan otomatis terbentuk di `android_app/app/build/outputs/apk/debug/app-debug.apk` dan siap ditransfer serta diinstal di HP Android mana pun!

---

## 👥 Akun Demo Siap Pakai (Standar Online Shop)

Telah disediakan akun demo siap pakai dengan 1-klik login di modal Masuk maupun tab Akun:

| Peran | Nama Akun | Email | Nomor WhatsApp | Lokasi | Fokus Fitur |
|---|---|---|---|---|---|
| 🛒 **Pembeli** | Rian Pratama | `rian.pembeli@babe.id` | `085712345678` | Surabaya | Jelajah feed, simpan wishlist, hubungi penjual via WA |
| 🏪 **Penjual** | Budi Santoso | `budi@example.com` | `081234567890` | Jakarta Selatan | Kelola iklan iPhone & Stik PS4, ubah status terjual |
| 🏪 **Penjual 2** | Siti Rahma | `siti@example.com` | `089876543210` | Bandung | Kelola iklan Helm KYT & Kursi Kerja |
| 🚚 **Logistik** | Babe Express (Joko) | `joko.logistik@babe.id` | `087811223344` | Jabodetabek & BDG | Penjemputan paket, kurir COD & inspeksi fisik barang |
| 🛡️ **Admin** | Admin Babe Support | `admin@babe.id` | `081199887766` | Jakarta Pusat (HQ) | Moderasi katalog barang, audit status platform |

> **Tips:** Buka modal **Masuk** atau tab **Akun**, lalu klik salah satu kartu role untuk langsung masuk tanpa perlu memasukkan password (`password123`).

---

## 📁 Struktur Direktori Project

```
c:\xampp\htdocs\Babe\
├── api/                             # RESTful API Engine (PHP 8.2 & PDO)
│   ├── config/
│   │   ├── database.php             # Koneksi Database PDO
│   │   └── cors.php                 # CORS Headers & Request Helpers
│   ├── auth/                        # Otentikasi & Profil (Register, Login, Update)
│   ├── categories/                  # Endpoint Kategori Barang
│   ├── products/                    # Endpoint CRUD Barang, Filter, & Search
│   ├── favorites/                   # Endpoint Wishlist/Favorit
│   └── uploads/                     # Direktori Penyimpanan Foto Barang Bekas
├── database/
│   └── setup.php                    # Skrip Inisialisasi Database 1-Klik
├── assets/
│   ├── css/style.css                # Desain Tampilan Mobile Responsif
│   └── js/app.js                    # Logika Interaksi Client & Integrasi API
├── android_app/                     # Project Native Android Studio Siap Build APK
│   ├── app/src/main/
│   │   ├── java/com/babe/app/MainActivity.java
│   │   └── AndroidManifest.xml
│   └── build.gradle
├── index.html                       # Antarmuka Utama Aplikasi Babe
└── README.md
```

---

## 🛠️ Fitur Utama yang Sudah Berfungsi:
- ✅ **Katalog & Rekomendasi Barang**: Tampilan card modern dengan badge kondisi barang (*Seperti Baru*, *Mulus*, *Bekas Wajar*, *Minus Pemakaian*).
- ✅ **Pencarian Real-Time & Filter**: Live filter berdasarkan kata kunci, kategori, rentang harga min-max, dan kondisi fisik.
- ✅ **Pasang Iklan Barang Bekas**: Upload foto barang dari kamera/galeri, input harga, deskripsi, dan lokasi COD.
- ✅ **Detail & Hubungi Penjual**: Tombol chat otomatis membuka aplikasi **WhatsApp** penjual dengan template pesan spesifik sesuai barang yang dilihat.
- ✅ **Wishlist / Favorit**: Simpan barang incaran ke tab Favorit.
- ✅ **Pencatatan Akun & Autentikasi Mandiri**: Registrasi akun baru (Nama, Nomor WhatsApp, Kota, Email, Password), Login, Logout, dan Edit Profil dengan sinkronisasi database MySQL secara real-time.
- ✅ **Manajemen Barang Saya**: Tandai barang yang sudah laku (**Terjual**) atau hapus iklan barang.
