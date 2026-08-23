<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Primary --}}
        <title>MatterLynk — Connect Clio and iManage. Automatically.</title>
        <meta name="description" content="MatterLynk synchronises your Clio matters to iManage workspaces in real time. Automatic workspace creation, group security mapping, and field mapping — zero manual filing.">
        <meta name="robots" content="index, follow">
        <link rel="canonical" href="{{ config('app.url') }}">

        {{-- Open Graph --}}
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ config('app.url') }}">
        <meta property="og:site_name" content="MatterLynk">
        <meta property="og:title" content="MatterLynk — Connect Clio and iManage. Automatically.">
        <meta property="og:description" content="MatterLynk synchronises your Clio matters to iManage workspaces in real time. Automatic workspace creation, group security mapping, and field mapping — zero manual filing.">
        <meta property="og:image" content="{{ config('app.url') }}/logo/png/matterlynk-horizontal-dark-1200.png">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:alt" content="MatterLynk — Clio to iManage integration">
        <meta property="og:locale" content="en_GB">

        {{-- Twitter Card --}}
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="MatterLynk — Connect Clio and iManage. Automatically.">
        <meta name="twitter:description" content="MatterLynk synchronises your Clio matters to iManage workspaces in real time. Automatic workspace creation, group security mapping, and field mapping — zero manual filing.">
        <meta name="twitter:image" content="{{ config('app.url') }}/logo/png/matterlynk-horizontal-dark-1200.png">
        <meta name="twitter:image:alt" content="MatterLynk — Clio to iManage integration">

        {{-- Favicons --}}
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        {{-- Performance hints --}}
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link rel="dns-prefetch" href="https://fonts.bunny.net">

        {{-- Structured data --}}
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@graph": [
                {
                    "@type": "Organization",
                    "@id": "{{ config('app.url') }}/#organization",
                    "name": "MatterLynk",
                    "url": "{{ config('app.url') }}",
                    "logo": {
                        "@type": "ImageObject",
                        "url": "{{ config('app.url') }}/logo/png/matterlynk-horizontal-dark-1200.png"
                    },
                    "contactPoint": {
                        "@type": "ContactPoint",
                        "contactType": "sales",
                        "url": "{{ config('app.url') }}/enquiry"
                    }
                },
                {
                    "@type": "SoftwareApplication",
                    "@id": "{{ config('app.url') }}/#software",
                    "name": "MatterLynk",
                    "applicationCategory": "BusinessApplication",
                    "operatingSystem": "Web",
                    "description": "MatterLynk synchronises your Clio matters to iManage workspaces in real time. Automatic workspace creation, group security mapping, and field mapping — zero manual filing.",
                    "url": "{{ config('app.url') }}",
                    "provider": { "@id": "{{ config('app.url') }}/#organization" },
                    "offers": {
                        "@type": "Offer",
                        "availability": "https://schema.org/InStock",
                        "url": "{{ config('app.url') }}/enquiry"
                    },
                    "featureList": [
                        "Real-time Clio webhook integration",
                        "Automatic iManage workspace creation",
                        "Template workspace support",
                        "Group security mapping",
                        "Bidirectional matter linking",
                        "Configurable field mapping engine",
                        "Multi-tenant architecture"
                    ]
                },
                {
                    "@type": "WebSite",
                    "@id": "{{ config('app.url') }}/#website",
                    "url": "{{ config('app.url') }}",
                    "name": "MatterLynk",
                    "publisher": { "@id": "{{ config('app.url') }}/#organization" }
                }
            ]
        }
        </script>

        @fonts
        @vite(['resources/css/app.css'])
    </head>
    <body class="bg-depth-700 text-neutral-100 antialiased">

        {{-- ── NAV ──────────────────────────────────────────────────────────── --}}
        <header class="fixed inset-x-0 top-0 z-50 bg-depth-500/95 backdrop-blur border-b border-depth-400/20">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4 lg:px-8">
                <a href="{{ route('home') }}">
                    <img src="/logo/matterlynk-horizontal-dark.svg" alt="{{ config('app.name', 'MatterLynk') }}" class="h-8 w-auto" />
                </a>

                <nav class="hidden items-center gap-8 text-sm font-medium lg:flex">
                    <a href="#how-it-works" class="text-neutral-300 transition hover:text-core-400">How it works</a>
                    <a href="#features" class="text-neutral-300 transition hover:text-core-400">Features</a>
                </nav>

                <div class="flex items-center gap-3">
                    @auth
                        @php
                            $dashRoute = auth()->user()->hasAnyRole(['Super Admin', 'Admin', 'Support'])
                                ? route('admin.dashboard')
                                : route('portal.dashboard');
                        @endphp
                        <a href="{{ $dashRoute }}"
                           class="rounded-lg bg-core-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-core-500">
                            Go to Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                           class="text-sm font-medium text-neutral-300 transition hover:text-white">
                            Log in
                        </a>
                        <a href="{{ route('enquiry') }}"
                           class="rounded-lg bg-core-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-core-500">
                            Get started
                        </a>
                    @endauth
                </div>
            </div>
        </header>

        {{-- ── HERO ─────────────────────────────────────────────────────────── --}}
        <section class="relative flex min-h-screen items-center overflow-hidden pt-20">
            {{-- Brand gradient backdrop --}}
            <div class="pointer-events-none absolute inset-0"
                 style="background: linear-gradient(135deg, #0c0636 0%, #095169 25%, #059b9a 50%, #53ba83 75%, #9fd96b 100%); opacity: 0.18;"></div>
            {{-- Depth scrim --}}
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-b from-depth-700/60 via-depth-700/40 to-depth-700/90"></div>

            <div class="relative mx-auto max-w-5xl px-6 py-24 text-center lg:px-8">
                <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-core-400/30 bg-core-400/10 px-4 py-1.5 text-xs font-medium text-core-400">
                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-core-400"></span>
                    Real-time Clio ↔ iManage Sync
                </div>

                <h1 class="mb-6 text-5xl font-bold leading-tight tracking-tight text-white lg:text-7xl">
                    Connect Clio and iManage.<br>
                    <span class="bg-gradient-to-r from-core-400 to-growth-400 bg-clip-text text-transparent">Automatically.</span>
                </h1>

                <p class="mx-auto mb-10 max-w-2xl text-lg leading-relaxed text-neutral-300">
                    MatterLynk listens to Clio webhooks and instantly creates the right iManage workspace — with the correct template, client details, and security mapping. No manual steps. No missed matters.
                </p>

                <div class="flex flex-col items-center justify-center gap-4 sm:flex-row">
                    @auth
                        @php
                            $dashRoute = auth()->user()->hasAnyRole(['Super Admin', 'Admin', 'Support'])
                                ? route('admin.dashboard')
                                : route('portal.dashboard');
                        @endphp
                        <a href="{{ $dashRoute }}"
                           class="rounded-xl bg-core-600 px-8 py-3.5 text-base font-semibold text-white shadow-lg transition hover:bg-core-500">
                            Open Dashboard
                        </a>
                    @else
                        <a href="{{ route('enquiry') }}"
                           class="rounded-xl bg-core-600 px-8 py-3.5 text-base font-semibold text-white shadow-lg transition hover:bg-core-500">
                            Get started
                        </a>
                        <a href="{{ route('login') }}"
                           class="rounded-xl border border-neutral-600 px-8 py-3.5 text-base font-semibold text-neutral-300 transition hover:border-neutral-400 hover:text-white">
                            Log in
                        </a>
                    @endauth
                </div>
            </div>
        </section>

        {{-- ── TRUST BAND ───────────────────────────────────────────────────── --}}
        <section class="border-y border-depth-400/20 bg-depth-600/60 py-12">
            <div class="mx-auto grid max-w-5xl grid-cols-2 gap-8 px-6 text-center lg:grid-cols-4 lg:px-8">
                <div>
                    <p class="text-3xl font-bold text-core-400">Real-time</p>
                    <p class="mt-1 text-sm text-neutral-400">Webhook-driven sync</p>
                </div>
                <div>
                    <p class="text-3xl font-bold text-core-400">100%</p>
                    <p class="mt-1 text-sm text-neutral-400">Automated workspace creation</p>
                </div>
                <div>
                    <p class="text-3xl font-bold text-core-400">Multi-tenant</p>
                    <p class="mt-1 text-sm text-neutral-400">Per-firm configuration</p>
                </div>
                <div>
                    <p class="text-3xl font-bold text-core-400">Zero</p>
                    <p class="mt-1 text-sm text-neutral-400">Manual filing steps</p>
                </div>
            </div>
        </section>

        {{-- ── HOW IT WORKS ─────────────────────────────────────────────────── --}}
        <section id="how-it-works" class="py-24">
            <div class="mx-auto max-w-5xl px-6 lg:px-8">
                <div class="mb-16 text-center">
                    <p class="mb-3 text-sm font-semibold uppercase tracking-widest text-core-400">How it works</p>
                    <h2 class="text-3xl font-bold text-white lg:text-4xl">Three steps. Zero effort.</h2>
                </div>

                <div class="grid gap-8 lg:grid-cols-3">
                    <div class="relative rounded-2xl border border-depth-400/20 bg-depth-600/40 p-8">
                        <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-core-600/20 text-core-400">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                            </svg>
                        </div>
                        <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-neutral-500">Step 1</div>
                        <h3 class="mb-3 text-lg font-semibold text-white">Clio fires a webhook</h3>
                        <p class="text-sm leading-relaxed text-neutral-400">When a matter is opened or updated in Clio, MatterLynk receives the event instantly via a secure webhook endpoint.</p>
                    </div>

                    <div class="relative rounded-2xl border border-depth-400/20 bg-depth-600/40 p-8">
                        <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-core-600/20 text-core-400">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 0 1 0-.255c.007-.378-.138-.75-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                        </div>
                        <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-neutral-500">Step 2</div>
                        <h3 class="mb-3 text-lg font-semibold text-white">MatterLynk maps and creates</h3>
                        <p class="text-sm leading-relaxed text-neutral-400">The engine selects the right iManage template and practice area, then creates a fully-configured workspace — with all metadata pre-filled.</p>
                    </div>

                    <div class="relative rounded-2xl border border-depth-400/20 bg-depth-600/40 p-8">
                        <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-growth-700/30 text-growth-400">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                            </svg>
                        </div>
                        <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-neutral-500">Step 3</div>
                        <h3 class="mb-3 text-lg font-semibold text-white">Security applied automatically</h3>
                        <p class="text-sm leading-relaxed text-neutral-400">Group security mappings are applied so only the right people access the workspace — no admin touch required.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ── FEATURES ─────────────────────────────────────────────────────── --}}
        <section id="features" class="border-t border-depth-400/20 py-24">
            <div class="mx-auto max-w-5xl px-6 lg:px-8">
                <div class="mb-16 text-center">
                    <p class="mb-3 text-sm font-semibold uppercase tracking-widest text-core-400">Features</p>
                    <h2 class="text-3xl font-bold text-white lg:text-4xl">Everything you need to stay in sync</h2>
                </div>

                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {{-- Feature 1 --}}
                    <div class="rounded-2xl border border-depth-400/20 bg-depth-600/30 p-6 transition hover:border-core-400/30 hover:bg-depth-600/60">
                        <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-core-600/20 text-core-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v8.25A2.25 2.25 0 0 0 4.5 16.5h15a2.25 2.25 0 0 0 2.25-2.25V9A2.25 2.25 0 0 0 19.5 6.75h-6.69Z" />
                            </svg>
                        </div>
                        <h3 class="mb-2 font-semibold text-white">Template Workspaces</h3>
                        <p class="text-sm leading-relaxed text-neutral-400">Each practice area maps to the correct iManage template, ensuring consistent folder structures across all matters.</p>
                    </div>

                    {{-- Feature 2 --}}
                    <div class="rounded-2xl border border-depth-400/20 bg-depth-600/30 p-6 transition hover:border-core-400/30 hover:bg-depth-600/60">
                        <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-core-600/20 text-core-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 0 1 21.75 8.25Z" />
                            </svg>
                        </div>
                        <h3 class="mb-2 font-semibold text-white">Group Security Mapping</h3>
                        <p class="text-sm leading-relaxed text-neutral-400">Clio groups map directly to iManage security entries. Private workspaces are locked down the moment they are created.</p>
                    </div>

                    {{-- Feature 3 --}}
                    <div class="rounded-2xl border border-depth-400/20 bg-depth-600/30 p-6 transition hover:border-core-400/30 hover:bg-depth-600/60">
                        <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-core-600/20 text-core-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                            </svg>
                        </div>
                        <h3 class="mb-2 font-semibold text-white">Bidirectional Linking</h3>
                        <p class="text-sm leading-relaxed text-neutral-400">After creation, the iManage workspace ID is written back to the Clio matter as a custom field — closing the loop.</p>
                    </div>

                    {{-- Feature 4 --}}
                    <div class="rounded-2xl border border-depth-400/20 bg-depth-600/30 p-6 transition hover:border-core-400/30 hover:bg-depth-600/60">
                        <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-core-600/20 text-core-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                            </svg>
                        </div>
                        <h3 class="mb-2 font-semibold text-white">Field Mapping Engine</h3>
                        <p class="text-sm leading-relaxed text-neutral-400">Configurable field mappings translate Clio matter data into the exact iManage custom attribute format your firm requires.</p>
                    </div>

                    {{-- Feature 5 --}}
                    <div class="rounded-2xl border border-depth-400/20 bg-depth-600/30 p-6 transition hover:border-core-400/30 hover:bg-depth-600/60">
                        <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-core-600/20 text-core-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5 10.5 6l3 3 2.25-2.25L21 12.75M3.75 13.5v4.5h16.5v-4.5" />
                            </svg>
                        </div>
                        <h3 class="mb-2 font-semibold text-white">Real-time Webhooks</h3>
                        <p class="text-sm leading-relaxed text-neutral-400">Events are processed the moment Clio fires them. The activity log gives you a full audit trail of every sync operation.</p>
                    </div>

                    {{-- Feature 6 --}}
                    <div class="rounded-2xl border border-depth-400/20 bg-depth-600/30 p-6 transition hover:border-core-400/30 hover:bg-depth-600/60">
                        <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-core-600/20 text-core-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                            </svg>
                        </div>
                        <h3 class="mb-2 font-semibold text-white">Multi-tenant Architecture</h3>
                        <p class="text-sm leading-relaxed text-neutral-400">Each firm has its own isolated configuration, credentials, and data — so one tenant's setup never affects another.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ── CTA ──────────────────────────────────────────────────────────── --}}
        <section class="relative overflow-hidden py-24">
            <div class="pointer-events-none absolute inset-0"
                 style="background: linear-gradient(135deg, #077a84 0%, #059b9a 50%, #53ba83 100%); opacity: 0.15;"></div>
            <div class="relative mx-auto max-w-3xl px-6 text-center lg:px-8">
                <h2 class="mb-4 text-3xl font-bold text-white lg:text-4xl">Ready to stop filing manually?</h2>
                <p class="mb-8 text-lg text-neutral-300">Get your firm set up in minutes. MatterLynk handles the rest.</p>
                <div class="flex flex-col items-center justify-center gap-4 sm:flex-row">
                    @auth
                        @php
                            $dashRoute = auth()->user()->hasAnyRole(['Super Admin', 'Admin', 'Support'])
                                ? route('admin.dashboard')
                                : route('portal.dashboard');
                        @endphp
                        <a href="{{ $dashRoute }}"
                           class="rounded-xl bg-core-600 px-8 py-3.5 text-base font-semibold text-white shadow-lg transition hover:bg-core-500">
                            Open Dashboard
                        </a>
                    @else
                        <a href="{{ route('enquiry') }}"
                           class="rounded-xl bg-core-600 px-8 py-3.5 text-base font-semibold text-white shadow-lg transition hover:bg-core-500">
                            Get started free
                        </a>
                        <a href="{{ route('login') }}"
                           class="rounded-xl border border-neutral-500 px-8 py-3.5 text-base font-semibold text-neutral-300 transition hover:border-neutral-300 hover:text-white">
                            Log in
                        </a>
                    @endauth
                </div>
            </div>
        </section>

        {{-- ── FOOTER ───────────────────────────────────────────────────────── --}}
        <footer class="border-t border-depth-400/20 bg-depth-500 py-10">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-6 sm:flex-row lg:px-8">
                <a href="{{ route('home') }}">
                    <img src="/logo/matterlynk-horizontal-dark.svg" alt="{{ config('app.name', 'MatterLynk') }}" class="h-7 w-auto" />
                </a>
                <p class="text-xs text-neutral-500">&copy; {{ date('Y') }} MatterLynk. All rights reserved.</p>
                <div class="flex items-center gap-6 text-xs text-neutral-500">
                    <a href="#" class="transition hover:text-neutral-300">Privacy</a>
                    <a href="#" class="transition hover:text-neutral-300">Terms</a>
                    <a href="{{ route('enquiry') }}" class="transition hover:text-neutral-300">Contact</a>
                </div>
            </div>
        </footer>

    </body>
</html>
