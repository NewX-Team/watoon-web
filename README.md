# WATOON Enterprise Platform
### Digital Streaming Infrastructure & Serialized Literary Distribution

---

## 1. Ringkasan Eksekutif

WATOON adalah platform distribusi digital terpadu yang menggabungkan infrastruktur penayangan audio visual sinematik dengan penerbitan naskah sastra bersambung (*serialized novels*). Sistem ini dirancang untuk memenuhi standar industri media modern melalui arsitektur berbasis *domain-driven*, pemisahan peran multi-kreator (*multi-tenant creator isolation*), sistem monetisasi bertingkat (*tiered subscription engine*), serta integrasi *payment gateway* berkeamanan tinggi dengan rekonsiliasi transaksi secara otomatis (*automated transaction reconciliation*).

---

## 2. Arsitektur Domain & Spesifikasi Sistem

Sistem beroperasi di atas pola arsitektur monolitik terdistribusi modern menggunakan integrasi protokol **Inertia.js v3** yang menjembatani layanan backend berbasis **Laravel 12 / PHP 8.4** dengan frontend reaktif berbasis **Vue 3 / TypeScript**.

### 2.1 Dual-Content Engine
Platform mengelola dua domain katalog utama dalam satu ekosistem:
- **Cinema & Audiovisual Domain**: Pengelolaan konten visual, master tayangan beresolusi adaptif (hingga 4K HDR), penataan episode, metadata kuratorial, sinopsis naskah, dan direktori kru film.
- **Literary & Serialized Novel Domain**: Pengelolaan publikasi naskah digital berbab (*episodic chapters*), draf pra-rilis (*early screenplay access*), arsip literatur terindeks, dan segmentasi hak akses bab berbasis langganan.

Antarmuka pengguna mengadopsi kontrol segmen terindeks yang memungkinkan peralihan instan antara kedua domain konten tanpa memutus konteks sesi pengguna.

---

## 3. Kontrol Akses & Hierarki Pengguna (RBAC)

Platform menerapkan kebijakan *Role-Based Access Control* (RBAC) yang terikat langsung pada model entitas `User` melalui enumerasi tipe PHP 8.4 yang tersentralisasi.

### 3.1 Matriks Peran Pengguna (`App\Enums\Role`)

| Peran Sistem | Nilai Enum | Cakupan Akses | Rute Dashboard Utama |
|---|---|---|---|
| **Penonton / Pembaca** | `user` | Mengakses katalog publik, pemutar media, arsip bacaan, obrolan berjenjang, dan manajemen langganan. | `dashboard/User.vue` |
| **Kreator Film** | `kreator` (`creator_type: film`) | Ruang kerja manajemen karya sinematik, analitik tayangan, profil sineas, dan interaksi komunitas. | `dashboard/KreatorFilm.vue` |
| **Kreator Novel** | `kreator` (`creator_type: novel`) | Ruang kerja penerbitan naskah bab, monetisasi konten tulisan, manajemen draf, dan analitik pembaca. | `dashboard/KreatorNovel.vue` |
| **Administrator** | `admin` | Pengelolaan seluruh data master platform, moderasi konten, audit transaksi, dan parameter sistem. | `dashboard/Admin.vue` |

### 3.2 Isolasi Dashboard Kreator
Untuk menjamin independensi alur kerja masing-masing domain kreatif, sistem memisahkan secara modular antarmuka kreator film dan kreator novel:
- **Kreator Film (`KreatorFilm.vue`)**: Dirancang secara khusus untuk fokus pada operasional audiovisual, penayangan karya, dan tata kelola film.
- **Kreator Novel (`KreatorNovel.vue`)**: Dirancang secara khusus untuk fokus pada tata kelola naskah, penyusunan bab berbayar, dan penerbitan berkala.
- **Routing Engine (`DashboardController.php`)**: Melakukan resolusi halaman otomatis berdasarkan atribut `creator_type` pengguna, serta mendukung parameter kueri eksplisit (`?type=film` atau `?type=novel`) untuk keperluan pratinjau dan inspeksi administratif.

---

## 4. Mesin Berlangganan & Model Monetisasi (Subscription Engine)

Sistem monetisasi WATOON beroperasi menggunakan aturan bisnis deterministik dengan tingkatan kasta akses (*access tiering*).

### 4.1 Spesifikasi Kasta (`App\Enums\UserTier`)

| Tingkat Kasta | Peringkat Relatif (`rank`) | Batas Kualitas Streaming | Hak Akses Konten Novel | Hak Akses Komunikasi Kreator |
|---|---|---|---|---|
| **Free** | `1` (Default Akun Baru) | Resolusi Standar Katalog Terbuka | Bab pembuka publik | Terbatas pada sesama pengguna Free |
| **Pro** | `2` | 1080p Full HD (Bebas Iklan) | Seluruh episode novel terbit | Obrolan dengan kreator kasta Pro & Free |
| **VIP** | `3` (Tier Tertinggi) | 4K HDR Cinema Master | Akses perdana naskah & bab eksklusif | Obrolan tanpa batasan ke seluruh kreator |

### 4.2 Siklus Penagihan & Ketetapan Finansial (`App\Enums\BillingCycle`)
- **Bulanan (`monthly`)**: Masa aktif 30 hari kalender.
  - Pro: Rp 80.000,-
  - VIP: Rp 150.000,-
- **Tahunan (`yearly`)**: Masa aktif 365 hari kalender (Diskon 20% annualized).
  - Pro: Rp 768.000,-
  - VIP: Rp 1.440.000,-

### 4.3 Invarian & Aturan Transisi Status
1. **Aturan Pendaftaran Awal**: Setiap akun yang baru didaftarkan secara otomatis diklasifikasikan ke dalam tier **Free** dengan masa kedaluwarsa kosong (`null`).
2. **Peningkatan Kasta (Upgrade)**: Pengguna kasta Pro berhak melakukan peningkatan ke kasta VIP kapan pun dengan tarif standar, yang secara instan mereset masa aktif dan memperbarui hak istimewa pengguna.
3. **Imutabilitas Penurunan Kasta (Downgrade Restriction)**: Pengguna yang memegang kasta VIP aktif dilarang menurunkan status ke Pro atau Free hingga seluruh masa berlangganan VIP berakhir (`subscription_expires_at <= now()`).
4. **Telemetri Durasi & Visualisasi Status**:
   - Sistem menghitung persentase sisa masa aktif secara *real-time* berbasis detik.
   - Antarmuka pengaturan akun menyajikan visualisasi progress bar adaptif:
     - Indikator kasta Pro: Menggunakan palet standar *Cobalt Corporate*.
     - Indikator kasta VIP: Menggunakan palet *Gold Luxury*.
     - Batas Kritis (*Expiring Soon*): Otomatis bertransisi ke warna merah peringatan ketika sisa masa aktif berada di ambang $\le 72\text{ jam}$ (3 hari kalender).

---

## 5. Infrastruktur Pembayaran & Webhook (Midtrans Gateway)

Pemrosesan pembayaran terintegrasi dengan gateway pembayaran nasional Midtrans melalui arsitektur API yang aman dan toleran terhadap kegagalan.

### 5.1 Siklus Transaksi
1. **Checkout Dispatch (`POST /subscription/checkout`)**:
   - Memvalidasi parameter kasta dan siklus penagihan.
   - Memverifikasi kelayakan pengguna melalui metode `canSubscribeTo()`.
   - Menginisiasi rekaman transaksi pada tabel `subscriptions` dengan status `pending` dan format identifikasi: `WTN-SUB-{TIMESTAMP}-{RANDOM_HEX}`.
   - Mengirimkan muatan data ke endpoint Midtrans Snap API dan mengembalikan token transaksi terenkripsi beserta redirect URL.
2. **Webhook Listener (`POST /midtrans/webhook`)**:
   - Menerima notifikasi server-to-server asinkron dari Midtrans.
   - Melakukan verifikasi integritas kriptografis melalui signature key:
     $$\text{Signature} = \text{SHA512}(\text{order\_id} + \text{status\_code} + \text{gross\_amount} + \text{server\_key})$$
   - Memutakhirkan status langganan pengguna (`settlement` / `capture` $\rightarrow$ penetapan masa aktif dan pembaharuan kasta pada model `User`).
3. **Arsitektur Mock / Fallback**:
   - Apabila parameter kredensial `MIDTRANS_SERVER_KEY` belum terdefinisi di lingkungan pengujian, sistem otomatis mengaktifkan *mock transaction engine* sehingga pengujian fungsional dan integrasi lokal dapat diselesaikan tanpa hambatan.

---

## 6. Protokol Komunikasi Tersegregasi (Tiered Messaging)

Sistem perpesanan (`/messages`) dikendalikan oleh matriks otorisasi terarah:
$$\text{Izin Obrolan}(U_{\text{pengirim}}, U_{\text{penerima}}) \iff \text{Rank}(U_{\text{pengirim}}.\text{tier}) \ge \text{Rank}(U_{\text{penerima}}.\text{tier})$$

Pengguna yang mencoba menginisiasi sesi komunikasi dengan entitas kreator yang berada di atas tier aksesnya akan secara otomatis diblokir di tingkat aplikasi dan diarahkan ke modal penawaran peningkatan kasta (*upgrade modal*).

---

## 7. Spesifikasi Teknologi

| Lapisan Sistem | Komponen / Pustaka | Spesifikasi / Versi |
|---|---|---|
| **Core Runtime** | PHP | 8.4+ (Strict typing, constructor property promotion, enums) |
| **Backend Framework** | Laravel Framework | 12.x / 13.x |
| **Authentication Engine** | Laravel Fortify | 1.x (Headless authentication, 2FA, session security) |
| **Type-Safe Routing** | Laravel Wayfinder | Generasi fungsi TypeScript otomatis dari deklarasi rute backend |
| **Client-Server Bridge** | Inertia.js | Versi 3.0 (Single-page app tanpa kompleksitas REST API redundan) |
| **Frontend Framework** | Vue.js | Versi 3.5+ (Composition API, `<script setup lang="ts">`) |
| **CSS Architecture** | Tailwind CSS | Versi 4.x |
| **Component Primitives** | Reka UI | Primitif UI standar aksesibilitas |
| **Iconography** | `@lucide/vue` | Paket ikon resmi Lucide untuk Vue 3 |
| **Frontend Tooling** | Vite Plus (`vite-plus`) | Bundler performa tinggi terintegrasi dengan `@inertiajs/vite` |
| **Database Engine** | MySQL / SQLite | Didukung penuh dengan skema migrasi terstandarisasi |
| **Testing Suite** | Pest PHP | Versi 5.x |
| **Code Standardization** | Laravel Pint | Format standar PSR-12 & Laravel internal guidelines |

---

## 8. Parameter Konfigurasi Lingkungan (`.env`)

Tabel konfigurasi variabel lingkungan yang wajib didefinisikan:

| Kunci Konfigurasi | Deskripsi | Rekomendasi Nilai Produksi |
|---|---|---|
| `APP_NAME` | Nama entitas aplikasi | `Watoon` |
| `APP_ENV` | Mode operasional lingkungan | `production` |
| `APP_KEY` | Kunci enkripsi simetris aplikasi | `base64:...` |
| `APP_DEBUG` | Parameter pelaporan galat detail | `false` |
| `APP_URL` | Alamat domain primer | `https://watoon.id` |
| `DB_CONNECTION` | Driver koneksi pangkalan data | `mysql` |
| `DB_HOST` | Alamat kluster basis data | `127.0.0.1` |
| `DB_DATABASE` | Nama skema basis data | `watoon_enterprise` |
| `MIDTRANS_SERVER_KEY` | Kunci privat server Midtrans | `Mid-server-xxxxxxxxxxxx` |
| `MIDTRANS_CLIENT_KEY` | Kunci publik client Midtrans | `Mid-client-xxxxxxxxxxxx` |
| `MIDTRANS_IS_PRODUCTION` | Mode gerbang transaksi | `true` |

---

## 9. Prosedur Instalasi & Operasionalisasi

### 9.1 Kebutuhan Lingkungan Server
- PHP versi 8.4 atau lebih tinggi dengan ekstensi: `pdo`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`.
- Composer 2.7+.
- Node.js LTS (versi 20.x atau 22.x) dan npm.
- Akses basis data relasional aktif.

### 9.2 Perintah Standar Operasional (SOP)

```bash
# 1. Kloning repositori
git clone https://github.com/org/watoon-enterprise.git
cd watoon-enterprise

# 2. Instalasi dependensi backend
composer install --no-dev --optimize-autoloader

# 3. Instalasi dependensi antarmuka
npm ci

# 4. Inisialisasi berkas konfigurasi
cp .env.example .env
php artisan key:generate

# 5. Migrasi struktur skema basis data
php artisan migrate --force

# 6. Kompilasi aset produksi antarmuka
npm run build

# 7. Optimalisasi performa lingkungan produksi
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Untuk lingkungan pengembangan lokal:
```bash
composer run dev
```

---

## 10. Pengujian, Penjaminan Mutu & Standar Kode

Seluruh fungsionalitas inti tercakup dalam pengujian otomatis untuk menjamin integritas logika bisnis dan mencegah regresi sistem:

```bash
# Eksekusi seluruh rangkaian pengujian otomatis
php artisan test --compact

# Eksekusi pengujian spesifik perutean dan dashboard
vendor/bin/pest tests/Feature/DashboardTest.php

# Eksekusi pengujian registrasi, peran dan segregasi kreator
vendor/bin/pest tests/Feature/Auth/RegistrationTest.php
```

Standarisasi pemformatan kode (*Code Formatting Compliance*):
```bash
vendor/bin/pint --format agent
```

Analisis statis dan pemeriksaan tipe TypeScript:
```bash
npm run types:check
npm run check
```

---

## 11. Topologi Direktori Repositori

```
├── app/
│   ├── Actions/Fortify/             # Layanan autentikasi, registrasi & profil
│   ├── Enums/                       # Domain enumerasi (Role, CreatorType, UserTier, dll.)
│   ├── Http/
│   │   ├── Controllers/             # Controller logika aplikasi & routing dashboard
│   │   └── Middleware/              # Middleware HTTP & serialisasi Inertia shareable props
│   ├── Models/                      # Model Eloquent (User, Subscription)
│   └── Services/                    # Layanan integrasi eksternal (MidtransService)
├── database/
│   ├── factories/                   # Data factory terparameterisasi untuk pengujian
│   └── migrations/                  # Definisi terstruktur skema basis data relasional
├── resources/js/
│   ├── actions/                     # Aksi controller ter-generate via Laravel Wayfinder
│   ├── components/                  # Komponen antarmuka atomik & modular
│   ├── layouts/                     # Kerangka tata letak terisolasi (App, Auth, Settings)
│   ├── pages/                       # Komponen halaman primer Inertia.js
│   │   ├── auth/                    # Alur login, registrasi, pemulihan kredensial
│   │   ├── dashboard/               # Dashboard User, KreatorFilm, KreatorNovel, Admin
│   │   ├── films/                   # Katalog, detail kuratorial, pemutar video
│   │   ├── messages/                # Obrolan berjenjang berbasis kasta
│   │   ├── settings/                # Pengaturan profil, keamanan, visualisasi masa aktif
│   │   └── subscription/            # Matriks penetapan harga & modal konfirmasi sukses
│   ├── routes/                      # Rute Wayfinder bertipe statis
│   └── types/                       # Antarmuka dan tipe data TypeScript terpusat
├── routes/
│   ├── web.php                      # Deklarasi rute web dan webhook transaksi
│   └── settings.php                 # Rute manajemen akun internal
└── tests/
    └── Feature/                     # Pengujian fungsional modul dan skenario bisnis
```

---

## 12. Ketentuan Lisensi & Hak Cipta

Seluruh kode sumber, arsitektur, dan aset dalam repositori ini dilindungi di bawah lisensi resmi [MIT License](LICENSE).
Semua hak kekayaan intelektual terkait merek dagang WATOON dilindungi oleh hukum yang berlaku.
