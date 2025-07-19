# Hướng dẫn cấu hình OAuth cho Facebook và Google

## 🚀 Chức năng đã hoàn thành

✅ **Backend Laravel:**
- Cài đặt Laravel Socialite
- Tạo AuthController với 4 phương thức OAuth:
  - `redirectToFacebook()` - Chuyển hướng đến Facebook OAuth
  - `handleFacebookCallback()` - Xử lý callback từ Facebook
  - `redirectToGoogle()` - Chuyển hướng đến Google OAuth  
  - `handleGoogleCallback()` - Xử lý callback từ Google
- Cập nhật User model và database với trường `facebook_id`, `google_id`
- Cấu hình routes OAuth trong `api.php`

✅ **Frontend:**
- Thêm nút Facebook và Google OAuth vào form đăng nhập và đăng ký
- JavaScript functions để xử lý OAuth và callback
- UI responsive với Tailwind CSS và Lucide icons

## 📋 Cấu hình cần thiết

### 1. Facebook OAuth Setup

1. **Tạo Facebook App:**
   - Truy cập: https://developers.facebook.com
   - Tạo app mới với loại "Consumer"
   - Thêm sản phẩm "Facebook Login"

2. **Cấu hình Facebook Login:**
   - Valid OAuth Redirect URIs: `http://localhost:8000/api/auth/facebook/callback`
   - Scopes cần thiết: `email`, `public_profile`

3. **Lấy credentials:**
   - App ID (Client ID)
   - App Secret (Client Secret)

### 2. Google OAuth Setup

1. **Tạo Google Cloud Project:**
   - Truy cập: https://console.cloud.google.com
   - Tạo project mới hoặc chọn project hiện có
   - Bật Google+ API

2. **Tạo OAuth 2.0 credentials:**
   - APIs & Services > Credentials > Create Credentials > OAuth 2.0 Client IDs
   - Application type: Web application
   - Authorized redirect URIs: `http://localhost:8000/api/auth/google/callback`

3. **Lấy credentials:**
   - Client ID
   - Client Secret

### 3. Cập nhật file .env

```env
# OAuth Settings
FACEBOOK_CLIENT_ID=your_actual_facebook_app_id
FACEBOOK_CLIENT_SECRET=your_actual_facebook_app_secret
FACEBOOK_REDIRECT_URI=http://localhost:8000/api/auth/facebook/callback

GOOGLE_CLIENT_ID=your_actual_google_client_id
GOOGLE_CLIENT_SECRET=your_actual_google_client_secret
GOOGLE_REDIRECT_URI=http://localhost:8000/api/auth/google/callback
```

## 🔄 Luồng hoạt động OAuth

1. **User click nút Facebook/Google**
2. **Frontend chuyển hướng** → `/api/auth/facebook` hoặc `/api/auth/google`
3. **Backend redirect** → OAuth provider (Facebook/Google)
4. **User authorization** → Cho phép ứng dụng truy cập
5. **OAuth callback** → `/api/auth/facebook/callback` hoặc `/api/auth/google/callback`
6. **Backend xử lý:**
   - Lấy thông tin user từ OAuth provider
   - Tạo hoặc cập nhật user trong database
   - Tạo authentication token
   - Redirect về frontend với token và user data
7. **Frontend xử lý:**
   - Lưu token và user data vào localStorage
   - Chuyển hướng về trang chủ

## 🧪 Test OAuth

1. **Truy cập:** http://localhost:8000/oauth-test.html
2. **Click vào nút test** để kiểm tra OAuth endpoints
3. **Kiểm tra:** Logs trong Laravel để debug

## 🛠️ Troubleshooting

### Lỗi thường gặp:

**"Invalid OAuth access token"**
- Kiểm tra Client ID và Secret đã đúng chưa
- Đảm bảo Redirect URI match chính xác

**"Redirect URI mismatch"**
- Kiểm tra URL callback trong OAuth provider settings
- Đảm bảo protocol (http/https) đúng

**"App not approved for login"**
- Facebook: App cần được review nếu muốn user khác login
- Trong development mode, chỉ admin/developer/tester có thể login

## 📝 Database Schema

Đã thêm các trường sau vào bảng `users`:
- `facebook_id` (nullable) - Facebook User ID
- `google_id` (nullable) - Google User ID

## 🔐 Security Features

- CORS middleware đã cấu hình cho cross-origin requests
- CSRF protection cho OAuth endpoints
- Token-based authentication với Laravel Sanctum
- User data validation và sanitization

## 📚 Documentation tham khảo

- [Laravel Socialite](https://laravel.com/docs/socialite)
- [Facebook Login Documentation](https://developers.facebook.com/docs/facebook-login)
- [Google OAuth 2.0](https://developers.google.com/identity/protocols/oauth2)
