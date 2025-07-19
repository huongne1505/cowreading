<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Book;
use App\Models\Author;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Create a notification for a user
     */
    public function createNotification(
        User $user,
        string $type,
        string $title,
        string $message,
        array $data = null,
        string $actionUrl = null,
        bool $isImportant = false
    ): Notification {
        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'action_url' => $actionUrl,
            'is_important' => $isImportant
        ]);
    }

    /**
     * Notify user about new book publication
     */
    public function notifyBookPublished(Book $book): void
    {
        // Notify users who favorited the author
        $users = User::whereHas('favoriteBooks', function ($query) use ($book) {
            $query->where('author_id', $book->author_id);
        })->get();

        foreach ($users as $user) {
            $this->createNotification(
                $user,
                Notification::TYPE_BOOK_PUBLISHED,
                'Sách mới được xuất bản',
                "Tác giả {$book->author->name} vừa xuất bản sách mới: {$book->title}",
                ['book_id' => $book->id, 'author_id' => $book->author_id],
                "/books/{$book->slug}",
                false
            );
        }
    }

    /**
     * Notify user about new chapter
     */
    public function notifyChapterPublished(Book $book, $chapter): void
    {
        // Notify users who are reading this book
        $users = User::whereHas('readingProgress', function ($query) use ($book) {
            $query->where('book_id', $book->id);
        })->get();

        foreach ($users as $user) {
            $this->createNotification(
                $user,
                Notification::TYPE_CHAPTER_PUBLISHED,
                'Chương mới được cập nhật',
                "Chương mới \"{$chapter->title}\" của sách \"{$book->title}\" đã được cập nhật",
                ['book_id' => $book->id, 'chapter_id' => $chapter->id],
                "/books/{$book->slug}/chapters/{$chapter->id}",
                false
            );
        }
    }

    /**
     * Notify user about review status
     */
    public function notifyReviewApproved(User $user, Book $book): void
    {
        $this->createNotification(
            $user,
            Notification::TYPE_REVIEW_APPROVED,
            'Đánh giá được duyệt',
            "Đánh giá của bạn về sách \"{$book->title}\" đã được duyệt",
            ['book_id' => $book->id],
            "/books/{$book->slug}",
            false
        );
    }

    /**
     * Notify user about review rejection
     */
    public function notifyReviewRejected(User $user, Book $book, string $reason = null): void
    {
        $message = "Đánh giá của bạn về sách \"{$book->title}\" đã bị từ chối";
        if ($reason) {
            $message .= ". Lý do: {$reason}";
        }

        $this->createNotification(
            $user,
            Notification::TYPE_REVIEW_REJECTED,
            'Đánh giá bị từ chối',
            $message,
            ['book_id' => $book->id, 'reason' => $reason],
            "/books/{$book->slug}",
            false
        );
    }

    /**
     * Notify user about premium expiration
     */
    public function notifyPremiumExpiring(User $user): void
    {
        $this->createNotification(
            $user,
            Notification::TYPE_PREMIUM_EXPIRING,
            'Tài khoản Premium sắp hết hạn',
            'Tài khoản Premium của bạn sẽ hết hạn trong 3 ngày. Gia hạn ngay để không bị gián đoạn.',
            ['expires_at' => $user->premium_expires_at],
            '/premium/renew',
            true
        );
    }

    /**
     * Notify user about premium expired
     */
    public function notifyPremiumExpired(User $user): void
    {
        $this->createNotification(
            $user,
            Notification::TYPE_PREMIUM_EXPIRED,
            'Tài khoản Premium đã hết hạn',
            'Tài khoản Premium của bạn đã hết hạn. Gia hạn ngay để tiếp tục sử dụng các tính năng cao cấp.',
            [],
            '/premium/renew',
            true
        );
    }

    /**
     * Create system announcement
     */
    public function createSystemAnnouncement(
        string $title,
        string $message,
        array $data = null,
        string $actionUrl = null,
        bool $isImportant = false
    ): Collection {
        $users = User::where('is_active', true)->get();
        $notifications = collect();

        foreach ($users as $user) {
            $notifications->push($this->createNotification(
                $user,
                Notification::TYPE_SYSTEM_ANNOUNCEMENT,
                $title,
                $message,
                $data,
                $actionUrl,
                $isImportant
            ));
        }

        return $notifications;
    }

    /**
     * Get unread notifications count for user
     */
    public function getUnreadCount(User $user): int
    {
        return $user->notifications()->unread()->count();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification): void
    {
        $notification->markAsRead();
    }

    /**
     * Mark all notifications as read for user
     */
    public function markAllAsRead(User $user): void
    {
        $user->notifications()->unread()->update(['read_at' => now()]);
    }

    /**
     * Delete old notifications
     */
    public function deleteOldNotifications(int $days = 30): int
    {
        return Notification::where('created_at', '<', now()->subDays($days))->delete();
    }

    /**
     * Get notifications for user with pagination
     */
    public function getUserNotifications(User $user, int $perPage = 15)
    {
        return $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get important notifications for user
     */
    public function getImportantNotifications(User $user, int $limit = 5)
    {
        return $user->notifications()
            ->important()
            ->unread()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
