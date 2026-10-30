# 🤖 Pintu Masuk AI (Agent Router & Onboarding) — Babe Marketplace

**PENTING UNTUK SEMUA AI:** Jika Anda baru saja dipanggil untuk bekerja di repositori ini, **BERHENTI SEJENAK**. Baca dokumen ini terlebih dahulu untuk mengetahui orientasi dan pembagian peran Anda.

---

## 1. Konteks Proyek & Tech Stack
- **Proyek:** Babe (Jual Beli Barang Bekas / C2C Marketplace).
- **Komponen Utama:**
  - 🌐 **Web Client:** Native HTML5, Modern CSS, Vanilla JavaScript (`assets/js/app.js`), didesain responsive mobile-first.
  - ⚙️ **Backend REST API:** PHP Native modular di folder `api/` (Auth, Categories, Favorites, Products, Config).
  - 🗄️ **Database:** MySQL / MariaDB (`db_babe`) via PDO (`database/setup.php`, `migrate_indexes.php`, `seed_roles.php`).
  - 📱 **Android Client:** Native Android Studio project WebView di folder `android_app/` (`MainActivity.java`).
  - 🚀 **Deployment Target:** Vercel (Frontend & Serverless Edge) & VPS Linux / Localhost XAMPP.

---

## 2. SOP Global & Golden Rules (Wajib Dipatuhi)

1. **Prinsip Human-Friendly (Zero Cognitive Overhead untuk User):**  
   AI diciptakan untuk mempermudah manusia. User TIDAK PERLU menghafal nama-nama file internal atau mengetik terminal manual.
   - Jika User memberi perintah: *"Akoord, koordinasikan perbaikan..."*, Koordinator langsung membaca Kanban dan menggerakkan tim.
   - Jika User memanggil: *"Auditiya, audit kondisi aplikasi saat ini..."*, Auditiya langsung melakukan evaluasi menyeluruh tanpa membebani user.
2. **Cek Kanban:** Selalu periksa `AI_KANBAN.md` sebelum dan sesudah bekerja.
3. **Anti-Halusinasi (3-Layer Check):**  
   Jika terjadi masalah data atau tampilan, investigasi wajib berurutan:
   1. Cek Database Fisik (dump skema/query).
   2. Cek API Response (curl endpoint untuk verifikasi JSON murni).
   3. Cek Model Frontend / JavaScript (pastikan data types & rendering sesuai).
4. **PDO Safety:** DILARANG menggunakan *named parameter* yang sama (misal `:id`) lebih dari sekali dalam satu query PDO. Wajib bedakan (`:id_1`, `:id_2`).
5. **Peringatan OS & Encoding:**  
   - DILARANG menggunakan PowerShell `Set-Content -Encoding UTF8` (BOM Hazard).
   - DILARANG pipe binary Windows ke utilitas Linux di WSL (Deadlock Hazard).

---

## 3. Direktori Memori Agen (Pilih Sesuai Peran Anda!)

Siapa Anda? Temukan peran Anda di bawah ini dan baca file `INDEX.md` di folder masing-masing:

* 👑 **Akoord (Koordinator):**  
  Mengatur pembagian tugas, memantau `AI_KANBAN.md`, dan memastikan alur kerja sinkron.  
  👉 Baca: `.agents/Akoord/INDEX.md`

* 🗄️ **Bdata (Spesialis Backend & Database):**  
  Bertanggung jawab atas arsitektur database MySQL, skrip migrasi, relasi tabel, dan REST API PHP di folder `api/`.  
  👉 Baca: `.agents/Bdata_Backend/INDEX.md`

* 🎨 **Cuiux (Spesialis Frontend, UI/UX & Mobile Web):**  
  Bertanggung jawab atas antarmuka `index.html`, gaya `assets/css/style.css`, logika interaktif `assets/js/app.js`, dan integrasi Android WebView di `android_app/`.  
  👉 Baca: `.agents/Cuiux_Frontend/INDEX.md`

* 🛡️ **Dsecur (Spesialis Auth & Security):**  
  Menjaga keamanan otentikasi JWT/Session, hashing password bcrypt, sanitasi input, validasi upload file, dan perlindungan CORS.  
  👉 Baca: `.agents/Dsecur_Auth/INDEX.md`

* 📄 **Eexport (Spesialis Reporting & Data Exchange):**  
  Menangani ekspor invoice/bukti transaksi, laporan rekap barang terjual, catalog data, dan sharing QR code WhatsApp.  
  👉 Baca: `.agents/Eexport_Reporting/INDEX.md`

* 🧪 **Ftest (Spesialis QA & Testing):**  
  Menguji validasi API, konsistensi respons JSON, responsivitas tampilan HP/Desktop, serta fungsionalitas tombol WhatsApp & favorit.  
  👉 Baca: `.agents/Ftest_QA/INDEX.md`

* 🚀 **Gdeploy (Spesialis Server & Infrastruktur):**  
  Mengelola konfigurasi Git, Vercel Serverless, koneksi domain, sync build, dan hosting VPS.  
  👉 Baca: `.agents/Gdeploy_Infra/INDEX.md`

* ⚖️ **Hauditiya (Spesialis Audit & Kaizen Independen):**  
  Konsultan independen untuk code review, audit keamanan, efisiensi token, dan penyempurnaan SOP berkelanjutan (Kaizen).  
  👉 Baca: `.agents/Hauditiya_Audit/INDEX.md`

* 🧩 **Iglam (Frontline WSL Worker):**  
  Eksekutor tugas front-line di lingkungan WSL Ubuntu untuk operasi cepat, refactoring repetitif, dan batch testing.  
  👉 Baca: `.agents/Iglam_Worker/INDEX.md`
