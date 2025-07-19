# 📚 CowReading - Hệ Thống Đọc Sách Trực Tuyến

## 🎯 Mô tả dự án

CowReading là một hệ thống đọc sách trực tuyến được xây dựng bằng Laravel. Dự án cung cấp các tính năng đầy đủ để quản lý và đọc sách trực tuyến với backend API mạnh mẽ.

## ✨ Tính năng chính

### 🔐 Hệ thống xác thực
- Đăng ký/đăng nhập người dùng
- Xác thực bằng Laravel Sanctum
- Quản lý profile người dùng
- Đổi mật khẩu
- Logout/Logout all devices

### 📖 Quản lý sách
- CRUD sách với đầy đủ thông tin
- Phân loại sách theo danh mục
- Tìm kiếm sách theo tên, tác giả, nội dung
- Sách miễn phí và premium
- Sách nổi bật
- Thống kê lượt xem và download

### 👥 Quản lý tác giả
- Thông tin chi tiết tác giả
- Danh sách sách của tác giả
- Tiểu sử và thông tin cá nhân

### 🏷️ Hệ thống danh mục
- Danh mục có cấu trúc phân cấp
- Danh mục cha - con
- Tìm kiếm sách theo danh mục

### 📑 Quản lý chương sách
- Chia sách thành các chương
- Đọc từng chương riêng biệt
- Điều hướng giữa các chương
- Ước tính thời gian đọc

### ⭐ Hệ thống đánh giá
- Đánh giá sách (1-5 sao)
- Bình luận và nhận xét
- Duyệt đánh giá
- Thống kê đánh giá

### ❤️ Yêu thích
- Thêm/bỏ sách yêu thích
- Danh sách sách yêu thích
- Toggle trạng thái yêu thích

### 📈 Theo dõi tiến độ đọc
- Theo dõi tiến độ đọc sách
- Thống kê thời gian đọc
- Sách đang đọc/đã hoàn thành
- Lưu vị trí đọc cuối cùng

### 👑 Hệ thống Premium
- Tài khoản premium
- Truy cập sách premium
- Quản lý thời hạn premium

## 🏗️ Cấu trúc dự án

### Models
```
├── User.php              # Người dùng
├── Author.php            # Tác giả
├── Category.php          # Danh mục
├── Book.php              # Sách
├── Chapter.php           # Chương sách
├── Review.php            # Đánh giá
├── Favorite.php          # Yêu thích
└── ReadingProgress.php   # Tiến độ đọc
```

### Controllers (API)
```
├── AuthController.php              # Xác thực
├── BookController.php              # Quản lý sách
├── AuthorController.php            # Quản lý tác giả
├── CategoryController.php          # Quản lý danh mục
├── ChapterController.php           # Quản lý chương
├── ReviewController.php            # Đánh giá
├── FavoriteController.php          # Yêu thích
└── ReadingProgressController.php   # Tiến độ đọc
```

### Services
```
├── ReadingService.php    # Xử lý logic đọc sách
└── FavoriteService.php   # Xử lý logic yêu thích
```

## 🚀 Cài đặt

### Yêu cầu hệ thống
- PHP >= 8.2
- Composer
- MySQL/PostgreSQL/SQLite
- Laravel 12.x

### Các bước cài đặt

1. **Cài đặt dependencies**
```bash
composer install
```

2. **Cấu hình environment**
```bash
cp .env.example .env
php artisan key:generate
```

3. **Cấu hình database trong file .env**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cowreading
DB_USERNAME=root
DB_PASSWORD=
```

4. **Chạy migrations**
```bash
php artisan migrate
```

5. **Chạy seeders (tùy chọn)**
```bash
php artisan db:seed
```

6. **Tạo storage link**
```bash
php artisan storage:link
```

7. **Chạy server**
```bash
php artisan serve
```

## 📚 API Documentation

### Authentication Endpoints
```
POST /api/auth/register        # Đăng ký
POST /api/auth/login          # Đăng nhập  
GET  /api/auth/profile        # Thông tin profile
PUT  /api/auth/profile        # Cập nhật profile
POST /api/auth/change-password # Đổi mật khẩu
POST /api/auth/logout         # Đăng xuất
POST /api/auth/logout-all     # Đăng xuất tất cả thiết bị
```

### Books Endpoints
```
GET  /api/books               # Danh sách sách
GET  /api/books/featured      # Sách nổi bật
GET  /api/books/popular       # Sách phổ biến
GET  /api/books/latest        # Sách mới nhất
GET  /api/books/search        # Tìm kiếm sách
GET  /api/books/{slug}        # Chi tiết sách
GET  /api/books/{slug}/reviews # Đánh giá sách
GET  /api/books/{slug}/download # Download sách
```

### Categories & Authors
```
GET  /api/categories          # Danh sách danh mục
GET  /api/categories/{id}     # Chi tiết danh mục
GET  /api/authors             # Danh sách tác giả
GET  /api/authors/{id}        # Chi tiết tác giả
```

### Protected Endpoints (Yêu cầu authentication)
```
# Reviews
POST /api/reviews             # Tạo đánh giá
PUT  /api/reviews/{id}        # Cập nhật đánh giá
DELETE /api/reviews/{id}      # Xóa đánh giá

# Favorites
GET  /api/favorites           # Danh sách yêu thích
POST /api/favorites/{bookId}  # Toggle yêu thích
DELETE /api/favorites/{bookId} # Bỏ yêu thích

# Reading Progress
GET  /api/reading-progress    # Tiến độ đọc
GET  /api/reading-progress/currently-reading # Đang đọc
GET  /api/reading-progress/completed # Đã hoàn thành
POST /api/reading-progress/{bookId}/start # Bắt đầu đọc
PUT  /api/reading-progress/{bookId}/progress # Cập nhật tiến độ
```

⭐ Hệ thống backend Laravel hoàn chỉnh cho website đọc sách!

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
