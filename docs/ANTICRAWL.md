# CowReading Anti-Crawl System

Hệ thống chống crawler được tích hợp để bảo vệ CowReading khỏi các bot, crawler và các hoạt động độc hại.

## 🛡️ Tính năng

### Phát hiện & Chặn
- **Bot Detection**: Phát hiện tự động user agents của bot/crawler
- **Rate Limiting**: Giới hạn số requests từ mỗi IP
- **Pattern Matching**: Phát hiện các URL/request pattern đáng ngờ
- **Honeypot Traps**: Các đường dẫn bẫy để phát hiện bot
- **Repeat Offender Tracking**: Theo dõi và chặn lâu hơn cho IP vi phạm nhiều lần

### Quản lý Admin
- **Dashboard**: Xem thống kê tổng quan
- **IP Management**: Quản lý danh sách IP bị chặn
- **Logs Viewer**: Xem nhật ký hoạt động
- **Real-time Monitoring**: Giám sát real-time

## ⚙️ Cấu hình

### File cấu hình: `config/anticrawl.php`

```php
// Bật/tắt hệ thống
'enabled' => true,

// Rate limiting
'rate_limit' => [
    'requests_per_minute' => 60,
    'burst_limit' => 10,
],

// Thời gian chặn
'blocking' => [
    'default_duration' => 24, // giờ
    'repeat_offender_duration' => 720, // giờ (30 ngày)
    'offense_threshold' => 3, // số lần vi phạm
],
```

### Biến môi trường (.env)

```env
ANTICRAWL_ENABLED=true
ANTICRAWL_RATE_LIMIT=60
ANTICRAWL_BURST_LIMIT=10
ANTICRAWL_ALERT_EMAIL=admin@cowreading.com
```

## 🚀 Cài đặt & Sử dụng

### 1. Middleware đã được đăng ký tự động
Middleware `AntiCrawlMiddleware` sẽ tự động áp dụng cho tất cả web routes.

### 2. Truy cập Admin Panel
```
/admin/anticrawl
```

### 3. Quản lý qua Artisan Command
```bash
# Xem danh sách IPs bị chặn
php artisan anticrawl:manage list

# Chặn một IP
php artisan anticrawl:manage block 192.168.1.100 --reason="Spam bot" --duration=24

# Bỏ chặn IP
php artisan anticrawl:manage unblock 192.168.1.100

# Xóa tất cả IPs bị chặn
php artisan anticrawl:manage clear
```

## 📊 Dashboard Features

### 1. Overview Stats
- Số IPs bị chặn
- Tổng số vi phạm
- Rate limits đang hoạt động
- Số lần chặn hôm nay

### 2. IP Management
- Xem danh sách IPs bị chặn
- Chặn/bỏ chặn IP thủ công
- Xem lý do và số lần vi phạm

### 3. Logs Monitoring
- Xem logs real-time
- Lọc theo loại sự kiện, IP, ngày
- Tải logs về file
- Thống kê tự động

## 🔧 Tùy chỉnh

### Bot Patterns
Thêm/sửa patterns phát hiện bot trong config:

```php
'bot_patterns' => [
    'bot', 'crawler', 'spider', 'scraper',
    'your-custom-pattern'
],
```

### Suspicious Patterns
Patterns URL đáng ngờ:

```php
'suspicious_patterns' => [
    'wp-admin', 'phpmyadmin', '../',
    'your-suspicious-pattern'
],
```

### Honeypot Traps
Đường dẫn bẫy:

```php
'honeypots' => [
    '/admin.php',
    '/wp-admin/',
    '/your-trap-path'
],
```

### Whitelist IPs
IPs không bao giờ bị chặn:

```php
'whitelist' => [
    '127.0.0.1',
    'your-server-ip'
],
```

## 📝 Logs

Logs được ghi vào `storage/logs/laravel.log` với các event:

- `Blocked IP attempted access`
- `Bot detected and blocked`
- `Rate limit exceeded`
- `Suspicious activity detected`
- `Honeypot accessed`

## 🔄 Cache

Hệ thống sử dụng Laravel Cache với prefix `anticrawl:`:

- `anticrawl:blocked_ip:{ip}`: IP bị chặn
- `anticrawl:rate_limit:{ip}`: Rate limiting counter
- `anticrawl:offense_count:{ip}`: Số lần vi phạm

## ⚡ Performance

- Middleware chạy rất nhanh (< 5ms)
- Sử dụng cache để tránh database queries
- Có thể scale với Redis cache
- Minimal memory footprint

## 🛠️ Troubleshooting

### Nếu bị chặn nhầm:
1. Thêm IP vào whitelist
2. Hoặc bỏ chặn qua admin panel
3. Hoặc chạy: `php artisan anticrawl:manage unblock YOUR_IP`

### Nếu middleware không hoạt động:
1. Kiểm tra `ANTICRAWL_ENABLED=true` trong .env
2. Chạy `php artisan config:cache`
3. Kiểm tra middleware đã đăng ký trong `bootstrap/app.php`

### Performance Issues:
1. Sử dụng Redis thay vì file cache
2. Giảm rate limit nếu cần
3. Tối ưu bot patterns

## 📞 Support

Để được hỗ trợ, vui lòng:
1. Kiểm tra logs trong `/admin/anticrawl/logs`
2. Xem config trong `config/anticrawl.php`
3. Đặt câu hỏi với thông tin chi tiết

---

**Lưu ý**: Hệ thống anti-crawl được thiết kế để bảo vệ mà không ảnh hưởng đến người dùng thực. Luôn test kỹ trước khi deploy lên production.
