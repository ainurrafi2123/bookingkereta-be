<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# 🚄 Train Ticket Booking API

API RESTful untuk sistem pemesanan tiket kereta api yang dibangun dengan Laravel 11 dan menggunakan Laravel Sanctum untuk autentikasi.

---

## 📋 Features

- 🔐 Autentikasi dengan Laravel Sanctum (Bearer Token)
- 👤 Manajemen User & Penumpang
- 🚂 CRUD Kereta, Gerbong, dan Kursi
- 📅 Manajemen Jadwal Kereta
- 🎫 Sistem Booking & Pembatalan Tiket
- 📊 Dashboard Statistik untuk Petugas
- 🔒 Role-based Access Control (User & Petugas)

---

## 🚀 Getting Started

### Prerequisites

- PHP >= 8.2
- Composer
- MySQL/PostgreSQL
- Laravel 12

### Installation

1. **Clone repository**
```bash
   git clone https://github.com/ainurrafi2123/bookingkereta-be.git
   cd bookingkereta-be
```

2. **Install dependencies**
```bash
   composer install
```

3. **Setup environment**
```bash
   cp .env.example .env
   php artisan key:generate
```

4. **Configure database** di `.env`
```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=train_booking
   DB_USERNAME=root
   DB_PASSWORD=
```

5. **Create database tables**
```bash
   php artisan migrate
```
   > Semua tabel akan otomatis dibuat dari migration files

6. **Serve application**
```bash
   php artisan serve
```

---

## 📚 API Documentation

### **Akses dokumentasi lengkap di:**
```
http://localhost:8000/docs/api
```

Dokumentasi dibuat menggunakan **[Scramble](https://scramble.dedoc.co/)** yang otomatis generate dari routes dan validation rules.

### Features Dokumentasi:
- ✅ List semua endpoints
- ✅ Request & Response examples
- ✅ Authentication setup
- ✅ Try it out feature
- ✅ Export to Postman/OpenAPI

---

## 🔑 Authentication

API menggunakan **Laravel Sanctum** dengan Bearer Token.

### Quick Example:

**1. Register**
```bash
POST /api/v1/auth/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**2. Login**
```bash
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}

# Response:
{
  "success": true,
  "data": {
    "token": "1|xxxxxxxxxxxxxx"
  }
}
```

**3. Use Token**
```bash
GET /api/v1/users/me
Authorization: Bearer 1|xxxxxxxxxxxxxx
```

---

## 👥 User Roles

| Role | Permissions |
|------|-------------|
| **User** | Lihat jadwal, booking tiket, manage profile sendiri |
| **Petugas** | Full access + manage semua data + statistik |

---

## 📦 Main Endpoints

| Resource | Endpoint | Description |
|----------|----------|-------------|
| Auth | `/api/v1/auth/*` | Register, login, logout |
| Users | `/api/v1/users/*` | User management |
| Kereta | `/api/v1/kereta/*` | Train data |
| Gerbong | `/api/v1/gerbong/*` | Carriage data |
| Kursi | `/api/v1/kursi/*` | Seat management |
| Jadwal | `/api/v1/jadwal-kereta/*` | Train schedules |
| Booking | `/api/v1/pembelian-tiket/*` | Ticket booking |

**📖 Untuk detail lengkap, kunjungi: [http://localhost:8000/docs/api](http://localhost:8000/docs/api)**

---

## 🧪 Testing

### Import ke Postman

1. Buka Postman
2. Import → Link
3. Paste URL: `http://localhost:8000/docs/api.json`
4. Collection akan otomatis ter-import

---

## 🛠️ Tech Stack

- **Framework**: Laravel 12
- **Authentication**: Laravel Sanctum
- **Database**: MySQL/PostgreSQL
- **Documentation**: Scramble
- **API Standard**: RESTful

---

## 📁 Project Structure
```
├── app/
│   └── Http/
│       └── Controllers/
│           ├── AuthController.php
│           ├── UserController.php
│           ├── KeretaController.php
│           ├── GerbongController.php
│           ├── KursiController.php
│           ├── JadwalKeretaController.php
│           └── PembelianTiketController.php
├── routes/
│   └── api.php                    # All API routes
├── config/
│   └── scramble.php               # API documentation config
└── README.md
```

---

## 🔧 Configuration

### API Versioning
API menggunakan versioning dengan prefix `v1`:
```
/api/v1/*
```

### Rate Limiting
- Guest: 10 requests/minute
- Authenticated: 60 requests/minute

---

## 📝 Environment Variables
```env
APP_NAME="Train Booking API"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_DATABASE=train_booking

SANCTUM_STATEFUL_DOMAINS=localhost:8000
```

---

## 🤝 Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

---

## 📄 License

This project is licensed under the MIT License.

---

## 📞 Contact

- Email: @
- Documentation: [http://localhost:8000/docs/api](http://localhost:8000/docs/api)

---

**Made with ❤️ using Laravel**
