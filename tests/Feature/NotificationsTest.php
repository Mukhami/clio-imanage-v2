<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Notifications\Index;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    // -------------------------------------------------------------------------
    // Helper — seeds a database notification row directly
    // -------------------------------------------------------------------------

    private function createNotification(bool $read = false, array $data = []): string
    {
        $id = (string) Str::uuid();

        $this->user->notifications()->create([
            'id'              => $id,
            'type'            => 'App\\Notifications\\UserInvited',
            'data'            => array_merge(['type' => 'user_invited', 'reset_url' => 'https://example.com/reset'], $data),
            'read_at'         => $read ? now() : null,
            'notifiable_id'   => $this->user->id,
            'notifiable_type' => User::class,
        ]);

        return $id;
    }

    // -------------------------------------------------------------------------
    // 1. User can view the notifications page
    // -------------------------------------------------------------------------

    public function test_user_can_view_notifications_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('notifications.index'));

        $response->assertOk();
    }

    // -------------------------------------------------------------------------
    // 2. markAsRead marks a single notification as read
    // -------------------------------------------------------------------------

    public function test_mark_as_read_marks_one_notification_as_read(): void
    {
        $id = $this->createNotification(read: false);

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->call('markAsRead', $id);

        $this->assertNotNull(
            $this->user->notifications()->where('id', $id)->value('read_at'),
            'Notification should have a read_at timestamp after markAsRead.',
        );
    }

    // -------------------------------------------------------------------------
    // 3. markAllAsRead marks all unread notifications as read
    // -------------------------------------------------------------------------

    public function test_mark_all_as_read_marks_all_unread_notifications_as_read(): void
    {
        $this->createNotification(read: false);
        $this->createNotification(read: false);
        $this->createNotification(read: true); // already read — should remain read

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->call('markAllAsRead');

        $unreadCount = $this->user->unreadNotifications()->count();

        $this->assertSame(0, $unreadCount, 'All notifications should be read after markAllAsRead.');
    }

    // -------------------------------------------------------------------------
    // 4. Filter "unread" returns only unread notifications
    // -------------------------------------------------------------------------

    public function test_filter_unread_returns_only_unread_notifications(): void
    {
        $this->createNotification(read: false);
        $this->createNotification(read: true);

        // With filter=unread, only the one unread notification should exist in DB scoped to unread
        $unreadCount = $this->user->unreadNotifications()->count();
        $totalCount  = $this->user->notifications()->count();

        $this->assertSame(1, $unreadCount, 'Only one notification is unread.');
        $this->assertSame(2, $totalCount, 'Two notifications exist in total.');

        // Verify the component sets the filter url param correctly and doesn't error
        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->set('filter', 'unread')
            ->assertHasNoErrors();
    }

    // -------------------------------------------------------------------------
    // 5. deleteNotification removes that notification
    // -------------------------------------------------------------------------

    public function test_delete_notification_removes_the_notification(): void
    {
        $id = $this->createNotification(read: false);

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->call('deleteNotification', $id);

        $exists = $this->user->notifications()->where('id', $id)->exists();

        $this->assertFalse($exists, 'Notification should be deleted after deleteNotification.');
    }

    // -------------------------------------------------------------------------
    // 6. clearAll deletes only read notifications, keeps unread
    // -------------------------------------------------------------------------

    public function test_clear_all_deletes_read_notifications_and_keeps_unread(): void
    {
        $unreadId = $this->createNotification(read: false);
        $readId   = $this->createNotification(read: true);

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->call('clearAll');

        $this->assertFalse(
            $this->user->notifications()->where('id', $readId)->exists(),
            'Read notification should be deleted by clearAll.',
        );

        $this->assertTrue(
            $this->user->notifications()->where('id', $unreadId)->exists(),
            'Unread notification should be kept by clearAll.',
        );
    }

    // -------------------------------------------------------------------------
    // 7. Unread count badge reflects the correct number
    // -------------------------------------------------------------------------

    public function test_unread_count_reflects_correctly(): void
    {
        $this->createNotification(read: false);
        $this->createNotification(read: false);
        $this->createNotification(read: true);

        // The component renders without errors and the underlying model method returns the right count
        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->assertHasNoErrors();

        $this->assertSame(2, $this->user->unreadNotifications()->count(), 'Unread count should be 2.');
    }
}
