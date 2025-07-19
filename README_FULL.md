# CowReading - Laravel Book Reading Platform

## Tổng quan dự án

CowReading là một nền tảng đọc sách trực tuyến được phát triển bằng Laravel, cung cấp một hệ thống backend hoàn chỉnh và chuyên nghiệp cho việc quản lý và đọc sách.

## Tính năng chính

### 🔐 Hệ thống Authentication
- Đăng ký/Đăng nhập với Laravel Sanctum
- Reset mật khẩu qua email
- Quản lý profile người dùng
- Phân quyền (Admin, Moderator, User)

### 📚 Quản lý sách
- CRUD sách với thông tin chi tiết
- Quản lý chương sách
- Tải lên và tải xuống sách
- Trạng thái sách (draft, published, unpublished)
- Theo dõi lượt xem và download

### 👥 Quản lý tác giả & thể loại
- Quản lý thông tin tác giả
- Phân loại sách theo thể loại
- Thống kê sách theo tác giả/thể loại

### ⭐ Hệ thống đánh giá & review
- Đánh giá sách (1-5 sao)
- Viết review chi tiết
- Quản lý trạng thái review
- Thống kê rating trung bình

### 💖 Yêu thích & Collections
- Thêm sách vào yêu thích
- Tạo bộ sưu tập sách cá nhân
- Chia sẻ collections công khai
- Quản lý bookmarks

### 📈 Theo dõi tiến độ đọc
- Theo dõi tiến độ đọc từng sách
- Lịch sử đọc sách
- Thống kê thời gian đọc
- Reading streaks

### 🔔 Hệ thống thông báo
- Thông báo sách mới
- Thông báo review
- Thông báo cập nhật hệ thống
- Đánh dấu đã đọc/chưa đọc

### 🔍 Tìm kiếm nâng cao
- Tìm kiếm toàn văn
- Lọc theo tác giả, thể loại, rating
- Tìm kiếm trong nội dung sách
- Gợi ý tìm kiếm

### 🎯 Hệ thống gợi ý
- Gợi ý sách dựa trên lịch sử đọc
- Sách trending
- Sách mới phát hành
- Collaborative filtering

### 📊 Analytics & Báo cáo
- Thống kê người dùng
- Thống kê sách
- Báo cáo hoạt động đọc
- Dashboard admin

### 🛡️ Admin Panel
- Quản lý người dùng
- Quản lý nội dung
- Thống kê hệ thống
- Cài đặt hệ thống

## Cấu trúc Database

### Tables chính:
- `users` - Thông tin người dùng
- `authors` - Tác giả
- `categories` - Thể loại sách
- `books` - Sách
- `chapters` - Chương sách
- `reviews` - Đánh giá
- `favorites` - Yêu thích
- `reading_progress` - Tiến độ đọc
- `reading_sessions` - Phiên đọc
- `notifications` - Thông báo
- `book_collections` - Bộ sưu tập
- `bookmarks` - Bookmark
- `tags` - Thẻ tag

## API Endpoints

### Public Routes
```
GET /api/books - Danh sách sách
GET /api/books/{slug} - Chi tiết sách
GET /api/authors - Danh sách tác giả
GET /api/categories - Danh sách thể loại
GET /api/search - Tìm kiếm
GET /api/recommendations/trending - Sách trending
```

### Protected Routes (Auth Required)
```
POST /api/reviews - Tạo review
GET /api/favorites - Danh sách yêu thích
POST /api/reading-progress - Cập nhật tiến độ
GET /api/notifications - Thông báo
GET /api/analytics/user - Thống kê cá nhân
```

### Admin Routes
```
GET /api/admin/dashboard - Dashboard admin
GET /api/admin/users - Quản lý người dùng
GET /api/admin/books - Quản lý sách
GET /api/admin/analytics - Thống kê hệ thống
```

## Models & Relationships

### User Model
```php
- hasMany: readingProgress, reviews, favoriteBooks, notifications
- hasMany: bookCollections, bookmarks, readingSessions
```

### Book Model
```php
- belongsTo: author, category
- hasMany: chapters, reviews, readingProgress
- belongsToMany: favoritedBy, tags
```

### Author Model
```php
- hasMany: books
```

### Category Model
```php
- hasMany: books
```

## Services

### ReadingService
- Quản lý tiến độ đọc
- Tính toán thống kê
- Ghi nhận phiên đọc

### NotificationService
- Gửi thông báo
- Quản lý loại thông báo
- Push notifications

### FavoriteService
- Quản lý yêu thích
- Gợi ý dựa trên yêu thích

## Controllers

### API Controllers
1. `AuthController` - Authentication
2. `BookController` - Quản lý sách
3. `AuthorController` - Quản lý tác giả
4. `CategoryController` - Quản lý thể loại
5. `ChapterController` - Quản lý chương
6. `ReviewController` - Quản lý review
7. `FavoriteController` - Quản lý yêu thích
8. `ReadingProgressController` - Tiến độ đọc
9. `NotificationController` - Thông báo
10. `BookCollectionController` - Bộ sưu tập
11. `AnalyticsController` - Thống kê
12. `RecommendationController` - Gợi ý
13. `SearchController` - Tìm kiếm
14. `AdminController` - Admin panel

## Middleware

### AdminMiddleware
- Kiểm tra quyền admin/moderator
- Kiểm tra trạng thái active
- Bảo vệ routes admin

## Factories & Seeders

### Factories
- `UserFactory` - Tạo user test
- `BookFactory` - Tạo sách test
- `AuthorFactory` - Tạo tác giả test
- `CategoryFactory` - Tạo thể loại test

### Seeders
- `DatabaseSeeder` - Chạy tất cả seeders
- `AdminSeeder` - Tạo admin users

## Tính năng nâng cao

### 1. Recommendation System
- Content-based filtering
- Collaborative filtering
- Hybrid approach

### 2. Analytics Dashboard
- Real-time statistics
- User behavior tracking
- Performance metrics

### 3. Advanced Search
- Full-text search
- Faceted search
- Search suggestions

### 4. Notification System
- Push notifications
- Email notifications
- In-app notifications

### 5. Reading Analytics
- Reading speed tracking
- Reading patterns
- Progress analytics

### 6. Social Features
- Collections sharing
- Review system
- User profiles

## Cài đặt và chạy

### Requirements
- PHP 8.2+
- Laravel 11.x
- MySQL 8.0+
- Composer

### Installation
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

### Seeding Data
```bash
php artisan db:seed --class=AdminSeeder
php artisan db:seed
```

## API Documentation

### Authentication
```
POST /api/register
POST /api/login
POST /api/logout
POST /api/forgot-password
POST /api/reset-password
```

### Books
```
GET /api/books
GET /api/books/{slug}
POST /api/books (Admin)
PUT /api/books/{id} (Admin)
DELETE /api/books/{id} (Admin)
```

### Reviews
```
GET /api/books/{slug}/reviews
POST /api/books/{slug}/reviews (Auth)
PUT /api/reviews/{id} (Auth)
DELETE /api/reviews/{id} (Auth)
```

### Analytics
```
GET /api/analytics/user (Auth)
GET /api/analytics/book/{slug}
GET /api/analytics/system
```

## Testing

### Test Users
- Admin: admin@cowreading.com / password
- Moderator: moderator@cowreading.com / password
- Demo: demo@cowreading.com / demo123

## Features Overview

✅ **Core Features**
- User authentication & authorization
- Book management (CRUD)
- Author & category management
- Review system
- Reading progress tracking
- Favorites system

✅ **Advanced Features**
- Notification system
- Book collections
- Bookmarks
- Reading sessions
- Tag system
- Analytics dashboard
- Recommendation engine
- Advanced search
- Admin panel

✅ **Technical Features**
- RESTful API design
- Laravel Sanctum authentication
- Eloquent relationships
- Database migrations
- Factory & seeder patterns
- Middleware protection
- Service layer architecture

## Security

### Implemented Security Features
- Token-based authentication
- Role-based access control
- Input validation
- SQL injection prevention
- XSS protection
- CSRF protection
- Rate limiting (configurable)

## Performance

### Optimization Features
- Database indexing
- Eager loading relationships
- Pagination
- Caching (configurable)
- Query optimization

## Extensibility

### Future Enhancements
- File upload for books
- Real-time reading
- Social features
- Mobile app support
- Multi-language support
- Payment integration
- AI-powered recommendations

---

**Dự án CowReading cung cấp một nền tảng đọc sách hoàn chỉnh với đầy đủ tính năng từ cơ bản đến nâng cao, sẵn sàng để phát triển thêm hoặc triển khai production.**
