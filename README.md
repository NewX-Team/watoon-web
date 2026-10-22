# WATOON — Streaming Sinema & Serial Novel Indonesia

<p align="center">
  <img src="public/favicon.ico" alt="WATOON Logo" width="80" height="80" />
</p>

<p align="center">
  <strong>Platform Kurasi Film Sinematik & Adaptasi Serial Naskah Sastra Pertama di Indonesia.</strong>
</p>

<p align="center">
  <a href="#fitur-utama">Fitur</a> •
  <a href="#arsitektur-pengguna--kreator">Arsitektur Pengguna</a> •
  <a href="#sistem-berlangganan--kasta">Sistem Berlangganan</a> •
  <a href="#integrasi-midtrans">Midtrans Gateway</a> •
  <a href="#tech-stack">Tech Stack</a> •
  <a href="#panduan-instalasi">Instalasi</a> •
  <a href="#pengujian-dan-kualitas-kode">Testing</a>
</p>

---

## 🎬 Tentang WATOON

**WATOON** adalah platform digital berbasis web yang memadukan pengalaman streaming film independen/kurasi berkualitas tinggi dengan publikasi naskah dan novel serial sinematik. Dirancang dengan antarmuka sinematik modern, WATOON menghadirkan ruang kolaborasi langsung antara sineas, penulis novel, dan para penikmat karya.

---

## ✨ Fitur Utama

- **Dual-Media Catalog (Film & Novel)**:
  - Pemutaran film kurasi dengan episode, sinopsis, cast, dan detail kru.
  - Arsip novel serial sinematik bab demi bab dengan preview eksklusif.
  - Segmented switcher interaktif untuk beralih antara katalog film dan novel dengan mulus.
- **Dua Jenis Dashboard Kreator Terpisah**:
  - **Dashboard Kreator Film**: Didedikasikan untuk sutradara dan produser sinema, pengunggahan karya visual, serta analitik penonton.
  - **Dashboard Kreator Novel**: Didedikasikan untuk penulis novel sastra dan serial naskah, bab berbayar, serta interaksi pembaca.
  - Switcher cepat untuk mempermudah kreator berpindah tampilan.
- **Sistem Berlangganan Kasta (Free, Pro, VIP)**:
  - Akses fitur, kualitas video (1080p hingga 4K HDR), dan katalog novel sesuai kasta aktif.
  - Aturan bisnis ketat: Pro dapat upgrade ke VIP kapan pun; VIP tidak dapat downgrade sebelum masa aktif selesai.
- **Midtrans Payment Gateway**:
  - Integrasi pembayaran resmi Midtrans Snap (QRIS, GoPay, Bank Transfer / Virtual Account, Kartu Kredit).
  - Mode simulasi/mock otomatis untuk pengembangan lokal tanpa perlu konfigurasi API key langsung.
  - Webhook aman dengan validasi SHA-512 signature key.
- **Indikator Durasi Langganan & Visual Progress Bar**:
  - Tampilan bar durasi dinamis di menu Pengaturan:
    - **Pro**: Warna khas *Cobalt Blue*.
    - **VIP**: Warna kemewahan *Radiant Gold*.
    - **Status Kritis**: Berubah menjadi merah jika sisa waktu langganan tinggal 2–3 hari.
- **Konfirmasi Pembayaran Mewah (Success Modal)**:
  - Modal interaktif elegan bergaya sinematik pasca pembayaran berhasil dengan pengenalan lencana kasta langsung aktif.
- **Sistem Pesan Berbasis Tingkatan (Tiered Direct Messaging)**:
  - Pembatasan interaksi obrolan kreator berdasarkan kasta pelanggan (VIP mengobrol dengan semua; Pro dengan Pro & Free; Free hanya dengan sesama Free).
- **Pengaturan Lengkap & Keamanan**:
  - Autentikasi dengan Laravel Fortify, verifikasi email, Two-Factor Authentication (2FA), serta manajemen profil dan tema (Dark/Light Mode).

---

## 👥 Arsitektur Pengguna & Kreator

WATOON membagi pengguna ke dalam tiga peran utama (`App\Enums\Role`):

| Peran | Enum Value | Deskripsi | Halaman Dashboard |
|---|---|---|---|
| **Penonton / Pembaca** | `user` | Pengguna umum yang menikmati film dan novel. | `dashboard/User.vue` |
| **Kreator Film** | `kreator` (`creator_type: film`) | Sineas, sutradara, atau videografer independen. | `dashboard/KreatorFilm.vue` |
| **Kreator Novel** | `kreator` (`creator_type: novel`) | Penulis naskah, novelis, dan sastrawan serial. | `dashboard/KreatorNovel.vue` |
| **Administrator** | `admin` | Pengelola sistem dan kurasi konten. | `dashboard/Admin.vue` |

> [!TIP]
> Saat mendaftar akun baru (`/register`), pengguna dapat langsung memilih peran utama (Penonton, Kreator Film, atau Kreator Novel). Kreator juga dapat menguji tampilan antar dashboard melalui parameter URL `?type=film` atau `?type=novel`.

---

## 💎 Sistem Berlangganan & Kasta

### 1. Tingkatan Kasta (`App\Enums\UserTier`)

- **FREE (Default)**:
  - Akses streaming katalog publik/standar.
  - Akses bab pembuka serial novel.
  - Obrolan terbatas hanya dengan sesama kreator Free.
- **PRO**:
  - Streaming Full HD 1080p tanpa jeda iklan.
  - Akses seluruh episode novel serial yang diterbitkan.
  - Akses obrolan privat dengan kreator kasta Pro & Free.
- **VIP (Elit)**:
  - Master 4K HDR Cinema tanpa batas.
  - Akses perdana naskah skenario dan bab novel sebelum rilis publik.
  - Akses obrolan langsung tanpa batasan dengan semua kreator (VIP, Pro, Free).

### 2. Siklus Penagihan & Harga (`App\Enums\BillingCycle`)

- **Bulanan (30 hari)**:
  - Pro: Rp 80.000 / bulan
  - VIP: Rp 150.000 / bulan
- **Tahunan (365 hari - Hemat 20%)**:
  - Pro: Rp 768.000 / tahun
  - VIP: Rp 1.440.000 / tahun

---

## 💳 Integrasi Midtrans

Pembayaran diproses melalui **Midtrans Snap API**:

1. **Checkout**: `POST /subscription/checkout` membuat order berformat `WTN-SUB-{YmdHis}-{RANDOM}` dan mengembalikan `snap_token`.
2. **Webhook Endpoint**: `POST /midtrans/webhook` menerima notifikasi status transaksi (`settlement`, `capture`, `pending`, `expire`, `cancel`).
3. **Verifikasi Keamanan**: Signature diverifikasi menggunakan formula:
   $$\text{Signature} = \text{SHA512}(\text{order\_id} + \text{status\_code} + \text{gross\_amount} + \text{server\_key})$$
4. **Mock / Mode Pengembangan**:
   - Jika `MIDTRANS_SERVER_KEY` belum diisi di `.env`, sistem otomatis beralih ke mock interaktif sehingga Anda dapat menguji seluruh alur checkout, konfirmasi, dan simulasi pembayaran secara instan di lokal.

---

## 🛠️ Tech Stack

### Backend
- **Framework**: [Laravel 12](https://laravel.com/)
- **PHP**: PHP 8.4+
- **Autentikasi**: [Laravel Fortify](https://laravel.com/docs/fortify)
- **Typed Routes**: [Laravel Wayfinder](https://github.com/laravel/wayfinder)
- **Database**: MySQL / SQLite (mendukung keduanya)
- **Testing**: [Pest PHP 5](https://pestphp.com/)
- **Code Style**: [Laravel Pint](https://laravel.com/docs/pint)

### Frontend
- **SPA Bridge**: [Inertia.js v3](https://inertiajs.com/) (Vue 3 Adapter)
- **UI Framework**: [Vue 3](https://vuejs.org/) (Composition API, `<script setup lang="ts">`, TypeScript)
- **Styling**: [Tailwind CSS v4](https://tailwindcss.com/)
- **Icons**: [`@lucide/vue`](https://lucide.dev/)
- **Components**: [Reka UI](https://reka-ui.com/)
- **Toast Notifications**: `vue-sonner`
- **Asset Bundler**: Vite Plus (`vite-plus` / `vp`)

---

## 🚀 Panduan Instalasi

### Persyaratan Sistem
- PHP >= 8.4
- Composer >= 2.x
- Node.js >= 20.x & npm
- Database (MySQL 8.x atau SQLite)

### Langkah Pemasangan

1. **Clone repositori**:
   ```bash
   git clone https://github.com/username/watoon.git
   cd watoon
   ```

2. **Pasang dependensi PHP**:
   ```bash
   composer install
   ```

3. **Pasang dependensi Node.js**:
   ```bash
   npm install
   ```

4. **Konfigurasi file `.env`**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Jalankan migrasi database**:
   ```bash
   php artisan migrate
   ```

6. **Konfigurasi Midtrans di `.env` (Opsional)**:
   ```env
   MIDTRANS_SERVER_KEY=SB-Mid-server-xxxxxxx
   MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxxxxx
   MIDTRANS_IS_PRODUCTION=false
   ```
   *(Kosongkan jika ingin menggunakan mode simulasi otomatis).*

7. **Jalankan server aplikasi**:
   ```bash
   composer run dev
   # Atau jalankan secara terpisah:
   # Terminal 1: php artisan serve
   # Terminal 2: npm run dev
   ```

8. **Akses aplikasi**:
   Buka peramban di `http://127.0.0.1:8000`.

---

## 🧪 Pengujian dan Kualitas Kode

WATOON dilengkapi dengan rangkaian pengujian otomatis (*feature & unit tests*) menggunakan **Pest**:

```bash
# Menjalankan seluruh test suite
php artisan test --compact

# Menjalankan test spesifik dashboard
vendor/bin/pest tests/Feature/DashboardTest.php

# Menjalankan test spesifik registrasi & peran
vendor/bin/pest tests/Feature/Auth/RegistrationTest.php
```

Format kode otomatis menggunakan Laravel Pint:
```bash
vendor/bin/pint --format agent
```

Build aset produksi:
```bash
npm run build
```

---

## 📁 Struktur Direktori Utama

```
watoon/
├── app/
│   ├── Actions/Fortify/       # Registrasi, profil, password
│   ├── Enums/                 # Role, CreatorType, UserTier, BillingCycle, SubscriptionStatus
│   ├── Http/
│   │   ├── Controllers/       # DashboardController, SubscriptionPaymentController, FilmController, dll.
│   │   └── Middleware/        # HandleInertiaRequests (shared user & auth props)
│   ├── Models/                # User, Subscription
│   └── Services/              # MidtransService (Snap API, Webhook, Mock engine)
├── database/
│   ├── factories/             # Factory untuk User & state pengujian
│   └── migrations/            # Skema database (users, subscriptions, tiers, roles)
├── resources/js/
│   ├── components/            # Komponen reusable UI & Lucide icons
│   ├── layouts/               # AppLayout, FlixReadAuthLayout, SettingsLayout
│   ├── pages/                 # Halaman Inertia Vue
│   │   ├── auth/              # Login, Register (dengan seleksi Kreator Film/Novel)
│   │   ├── dashboard/         # User.vue, KreatorFilm.vue, KreatorNovel.vue, Admin.vue
│   │   ├── films/             # Show.vue, Watch.vue
│   │   ├── messages/          # Index.vue (Tier-based messaging)
│   │   ├── settings/          # UserSettings.vue (Progress bar durasi langganan)
│   │   └── subscription/      # Pricing.vue, Success Modal
│   └── types/                 # Definisi tipe TypeScript
├── routes/
│   ├── web.php                # Rute web utama, pricing, subscription checkout & webhook
│   └── settings.php           # Rute pengaturan profil, password, 2FA
└── tests/
    └── Feature/               # Feature tests untuk auth, subscription, dashboard, dll.
```

---

## 📄 Lisensi

Aplikasi ini dilisensikan di bawah lisensi [MIT](LICENSE).
