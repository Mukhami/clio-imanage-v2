<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Admin\Users\Create;
use App\Livewire\Admin\Users\Edit;
use App\Livewire\Admin\Users\Index;
use App\Models\User;
use App\Notifications\UserInvited;
use App\Notifications\UserRoleChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $admin;

    private Role $superAdminRole;

    private Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles required by Spatie Permission
        $this->superAdminRole = Role::create(['name' => 'Super Admin']);
        $this->adminRole      = Role::create(['name' => 'Admin']);

        // Create a Super Admin actor for most tests
        $this->superAdmin = User::factory()->create(['name' => 'Super Admin User']);
        $this->superAdmin->assignRole('Super Admin');

        // Create a regular Admin actor
        $this->admin = User::factory()->create(['name' => 'Admin User']);
        $this->admin->assignRole('Admin');
    }

    // -------------------------------------------------------------------------
    // 1. Admin can view users list
    // -------------------------------------------------------------------------

    public function test_admin_can_view_users_list(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.users.index'));

        $response->assertOk();
    }

    // -------------------------------------------------------------------------
    // 2. Search filter returns matching users
    // -------------------------------------------------------------------------

    public function test_search_filter_returns_matching_users(): void
    {
        $matchingUser    = User::factory()->create(['name' => 'Alice Wonderland', 'email' => 'alice@example.com']);
        $nonMatchingUser = User::factory()->create(['name' => 'Bob Builder', 'email' => 'bob@example.com']);

        $matchingUser->assignRole('Admin');
        $nonMatchingUser->assignRole('Admin');

        Livewire::actingAs($this->superAdmin)
            ->test(Index::class)
            ->set('search', 'Alice')
            ->assertSee('Alice Wonderland')
            ->assertDontSee('Bob Builder');
    }

    // -------------------------------------------------------------------------
    // 3. Role filter returns only users with that role
    // -------------------------------------------------------------------------

    public function test_role_filter_returns_only_users_with_that_role(): void
    {
        Role::create(['name' => 'Support']);

        $supportUser = User::factory()->create(['name' => 'Support Person']);
        $supportUser->assignRole('Support');

        // $this->admin already has Admin role
        Livewire::actingAs($this->superAdmin)
            ->test(Index::class)
            ->set('roleFilter', 'Support')
            ->assertSee('Support Person')
            ->assertDontSee('Admin User');
    }

    // -------------------------------------------------------------------------
    // 4. Invite user — user created, role assigned, UserInvited notification sent
    // -------------------------------------------------------------------------

    public function test_invite_user_creates_user_with_verified_email_assigns_role_and_dispatches_notification(): void
    {
        Notification::fake();

        Livewire::actingAs($this->superAdmin)
            ->test(Create::class)
            ->set('name', 'New Invitee')
            ->set('email', 'invitee@example.com')
            ->set('role', 'Admin')
            ->call('save');

        $createdUser = User::where('email', 'invitee@example.com')->first();

        $this->assertNotNull($createdUser, 'User was not created in the database.');

        // email_verified_at is set via DB insert in Create::save(); read raw value to bypass fillable guard
        $rawVerifiedAt = DB::table('users')->where('email', 'invitee@example.com')->value('email_verified_at');
        $this->assertNotNull($rawVerifiedAt, 'email_verified_at should be set on invite.');

        $this->assertTrue($createdUser->hasRole('Admin'), 'Invited user should have the Admin role.');

        Notification::assertSentTo($createdUser, UserInvited::class);
    }

    // -------------------------------------------------------------------------
    // 5. Lock user — lockUser(id) sets locked_until in the future
    // -------------------------------------------------------------------------

    public function test_lock_user_sets_locked_until_in_the_future(): void
    {
        $targetUser = User::factory()->create();
        $targetUser->assignRole('Admin');

        Livewire::actingAs($this->superAdmin)
            ->test(Index::class)
            ->call('lockUser', $targetUser->id);

        $targetUser->refresh();

        $this->assertNotNull($targetUser->locked_until, 'locked_until should not be null after locking.');
        $this->assertTrue(
            $targetUser->locked_until->isFuture(),
            'locked_until should be in the future after locking.',
        );
    }

    // -------------------------------------------------------------------------
    // 6. Unlock user — unlockUser(id) sets locked_until to null
    // -------------------------------------------------------------------------

    public function test_unlock_user_sets_locked_until_to_null(): void
    {
        $targetUser = User::factory()->create(['locked_until' => now()->addYears(10)]);
        $targetUser->assignRole('Admin');

        Livewire::actingAs($this->superAdmin)
            ->test(Index::class)
            ->call('unlockUser', $targetUser->id);

        $targetUser->refresh();

        $this->assertNull($targetUser->locked_until, 'locked_until should be null after unlocking.');
    }

    // -------------------------------------------------------------------------
    // 7. Edit user role — save() syncs role and dispatches UserRoleChanged
    // -------------------------------------------------------------------------

    public function test_edit_user_save_syncs_role_and_dispatches_role_changed_notification(): void
    {
        Notification::fake();

        Role::create(['name' => 'Support']);

        $targetUser = User::factory()->create(['name' => 'Target Person', 'email' => 'target@example.com']);
        $targetUser->assignRole('Admin');

        Livewire::actingAs($this->superAdmin)
            ->test(Edit::class, ['id' => $targetUser->id])
            ->set('selectedRole', 'Support')
            ->call('save');

        $targetUser->refresh();

        $this->assertTrue($targetUser->hasRole('Support'), 'User should now have the Support role.');
        $this->assertFalse($targetUser->hasRole('Admin'), 'User should no longer have the Admin role.');

        Notification::assertSentTo($targetUser, UserRoleChanged::class, function (UserRoleChanged $notification) {
            return $notification->newRole === 'Support';
        });
    }

    // -------------------------------------------------------------------------
    // 8. Non-admin cannot access /admin/users
    // -------------------------------------------------------------------------

    public function test_non_admin_cannot_access_admin_users_page(): void
    {
        Role::create(['name' => 'Tenant Admin']);

        $regularUser = User::factory()->create();
        $regularUser->assignRole('Tenant Admin');

        $response = $this->actingAs($regularUser)->get(route('admin.users.index'));

        // Spatie's role middleware returns 403 for unauthorized roles
        $response->assertForbidden();
    }
}
