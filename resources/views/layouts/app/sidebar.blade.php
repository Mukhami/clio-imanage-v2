<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-neutral-50 dark:bg-depth-700">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-depth-400/20 bg-depth-500 dark:bg-depth-800">
            <flux:sidebar.header>
                <a href="{{ route('admin.dashboard') }}" wire:navigate class="flex items-center px-1 py-2">
                    <img src="/logo/png/matterlynk-horizontal-dark-1200.png" alt="{{ config('app.name', 'MatterLynk') }}" class="h-7 w-auto" />
                </a>
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>

                {{-- Admin section: Super Admin, Admin, Support --}}
                @if (auth()->user()->hasAnyRole(['Super Admin', 'Admin', 'Support']))
                    <flux:sidebar.group :heading="__('Admin')">
                        <flux:sidebar.item
                            icon="squares-2x2"
                            :href="route('admin.dashboard')"
                            :current="request()->routeIs('admin.dashboard')"
                            wire:navigate
                        >
                            {{ __('Dashboard') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="building-office-2"
                            :href="route('admin.tenants.index')"
                            :current="request()->routeIs('admin.tenants.*')"
                            wire:navigate
                        >
                            {{ __('Tenants') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="arrow-path"
                            :href="route('admin.webhook-requests.index')"
                            :current="request()->routeIs('admin.webhook-requests.*')"
                            wire:navigate
                        >
                            {{ __('Webhook Requests') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="users"
                            :href="route('admin.users.index')"
                            :current="request()->routeIs('admin.users.*')"
                            wire:navigate
                        >
                            {{ __('Users') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="envelope"
                            :href="route('admin.enquiries.index')"
                            :current="request()->routeIs('admin.enquiries.*')"
                            wire:navigate
                        >
                            {{ __('Enquiries') }}
                        </flux:sidebar.item>

                        @if(auth()->user()->hasRole('Super Admin'))
                        <flux:sidebar.item
                            icon="shield-check"
                            :href="route('admin.roles.index')"
                            :current="request()->routeIs('admin.roles.*')"
                            wire:navigate
                        >
                            {{ __('Roles & Permissions') }}
                        </flux:sidebar.item>
                        @endif
                    </flux:sidebar.group>
                @endif

                {{-- Portal section: Tenant Admin, Tenant Viewer --}}
                @if (auth()->user()->hasAnyRole(['Tenant Admin', 'Tenant Viewer']))
                    <flux:sidebar.group :heading="__('Portal')">
                        <flux:sidebar.item
                            icon="squares-2x2"
                            :href="route('portal.dashboard')"
                            :current="request()->routeIs('portal.dashboard')"
                            wire:navigate
                        >
                            {{ __('Dashboard') }}
                        </flux:sidebar.item>

                        <flux:sidebar.item
                            icon="arrow-path"
                            :href="route('portal.webhook-activity')"
                            :current="request()->routeIs('portal.webhook-activity') || request()->routeIs('portal.webhook-requests.*')"
                            wire:navigate
                        >
                            {{ __('Webhook Activity') }}
                        </flux:sidebar.item>

                        @if (auth()->user()->hasRole('Tenant Admin'))
                            <flux:sidebar.item
                                icon="users"
                                :href="route('portal.users.index')"
                                :current="request()->routeIs('portal.users.*')"
                                wire:navigate
                            >
                                {{ __('Users') }}
                            </flux:sidebar.item>
                        @endif

                        <flux:sidebar.item
                            icon="cog-6-tooth"
                            :href="route('portal.settings')"
                            :current="request()->routeIs('portal.settings')"
                            wire:navigate
                        >
                            {{ __('Settings') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endif

            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item
                    icon="bell"
                    :href="route('notifications.index')"
                    :current="request()->routeIs('notifications.*')"
                    wire:navigate
                >
                    {{ __('Notifications') }}
                    @php $unreadCount = cache()->remember('user.' . auth()->id() . '.unread_notifications', 30, fn () => auth()->user()->unreadNotifications()->count()); @endphp
                    @if($unreadCount > 0)
                        <flux:badge color="red" size="sm">{{ $unreadCount }}</flux:badge>
                    @endif
                </flux:sidebar.item>

                <flux:sidebar.item icon="cog-6-tooth" :href="route('profile.edit')" wire:navigate>
                    {{ __('Settings') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="bottom" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
