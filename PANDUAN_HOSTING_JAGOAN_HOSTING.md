# Panduan Lengkap Online-kan Panen Dividen ke Jagoan Hosting
**Subdomain Target:** `https://dividen.kerjaremot.com`

---

## 📦 1. Berkas yang Perlu Di-Upload ke cPanel

Seluruh berkas siap pakai sudah berada di folder proyek:
1. **`index.html`** — Aplikasi web utama lengkap dengan antarmuka Glassmorphism, Form Login/Register, Multi-User Auth, dan isolasi data per akun.
2. **`quote.php`** — Backend mini PHP cURL yang bertugas mengambil harga saham realtime Bursa Efek Indonesia (BEI `.JK`) dari Yahoo Finance tanpa kendala CORS.
3. **`.htaccess`** — Konfigurasi server Apache cPanel untuk auto-redirect HTTPS, kompresi Gzip, caching browser, dan keamanan header.

---

## 🌐 2. Langkah Pembuatan Subdomain di cPanel Jagoan Hosting

1. Login ke **cPanel Jagoan Hosting** Anda (biasanya diakses via `https://kerjaremot.com:2083` atau melalui Member Area Jagoan Hosting).
2. Cari menu **Domains** lalu klik **Subdomains** (atau **Domains** pada versi cPanel terbaru).
3. Buat subdomain baru:
   * **Subdomain:** `dividen`
   * **Domain:** `kerjaremot.com`
   * **Document Root:** biarkan default, biasanya `public_html/dividen` atau `dividen.kerjaremot.com`.
4. Klik tombol **Create / Submit**.
5. Pastikan SSL aktif:
   * Buka menu **SSL/TLS Status** di cPanel, centang subdomain `dividen.kerjaremot.com`, lalu klik **Run AutoSSL** agar otomatis mendapatkan sertifikat HTTPS gratis (Let's Encrypt / cPanel AutoSSL).

---

## 📤 3. Langkah Upload Berkas ke File Manager

1. Di cPanel, buka menu **File Manager**.
2. Masuk ke folder document root subdomain Anda (misalnya `/public_html/dividen` atau `/dividen.kerjaremot.com`).
3. Pastikan folder tersebut kosong (jika ada file bawaan `cgi-bin`, biarkan saja).
4. Klik tombol **Upload** di bagian atas menu File Manager:
   * Upload file **`index.html`**
   * Upload file **`quote.php`**
   * Upload file **`.htaccess`** (Pastikan opsi *"Show Hidden Files (dotfiles)"* di kanan atas Setting File Manager dicentang agar file `.htaccess` terlihat).
5. Selesai! Berkas sudah berada di server Jagoan Hosting.

---

## 🔐 4. Konfigurasi Firebase Authentication untuk Multi-User

Agar pengguna dapat membuat akun, login email/password, dan login dengan akun Google di domain Anda, lakukan pengaturan 2 menit berikut:

### A. Aktifkan Fitur Login di Firebase Console
1. Buka [Firebase Console](https://console.firebase.google.com).
2. Pilih proyek Firebase Anda.
3. Di menu sebelah kiri, klik **Build** → **Authentication**.
4. Buka tab **Sign-in method**:
   * **Email/Password:** Klik edit → geser tombol ke posisi **Enable** → klik **Save**.
   * **Google:** Klik edit → geser tombol ke posisi **Enable** → pilih **Support email** Anda → klik **Save**.

### B. Daftarkan Subdomain di Authorized Domains (Penting!)
Di halaman **Authentication** yang sama:
1. Klik tab **Settings** di bagian atas.
2. Klik submenu **Authorized domains** di kolom kiri tab settings.
3. Klik tombol **Add domain**.
4. Masukkan:
   * `dividen.kerjaremot.com`
   * `kerjaremot.com`
5. Klik **Done / Add**.  
   *(Langkah ini wajib dilakukan agar Google mengizinkan proses login dari alamat website Anda)*.

### C. Keamanan Isolasi Data (Firestore Rules)
Pastikan database Anda menerapkan isolasi akun agar data pengguna satu sama lain tidak bercampur:
1. Di Firebase Console, klik **Build** → **Firestore Database**.
2. Buka tab **Rules**, lalu paste aturan keamanan berikut:

```javascript
rules_version = '2';
service cloud.firestore {
  match /databases/{database}/documents {
    match /users/{userId}/{document=**} {
      allow read, write: if request.auth != null && request.auth.uid == userId;
    }
  }
}
```
3. Klik tombol **Publish**.

---

## 🚀 5. Uji Coba Aplikasi Online

1. Buka browser dan kunjungi: **`https://dividen.kerjaremot.com`**.
2. Anda akan langsung disambut oleh **Layar Login & Pendaftaran Akun**:
   * **Masuk:** Gunakan email & password yang sudah terdaftar.
   * **Daftar Akun:** Buat akun baru dengan mencentang *"Muat template portofolio awal (BBRI, DMAS, MMLP)"*.
   * **Masuk dengan Google:** Login instan 1-klik dengan akun Gmail Anda.
   * **Mode Tamu / Demo:** Bagi pengguna yang ingin mencoba aplikasi secara offline tanpa mendaftar.
3. Cek pembaruan harga realtime:
   * Aplikasi akan otomatis memanggil `https://dividen.kerjaremot.com/quote.php?ticker=BBRI` secara instan dan aman dari blokir CORS browser.
4. Setiap user yang login dengan akun berbeda akan memiliki portofolio saham, dividen, dan catatan investasinya sendiri secara 100% terisolasi.

