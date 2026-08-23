<div class="w-full">
    @if ($submitted)
        {{-- Thank you screen --}}
        <div class="text-center">
            <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-core-600/20">
                <svg class="h-8 w-8 text-core-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
            </div>
            <h2 class="mb-2 text-xl font-bold">Thank you, {{ $contact_name }}!</h2>
            <p class="mb-6 text-sm text-zinc-500 dark:text-zinc-400">
                We've received your enquiry for <span class="font-medium">{{ $firm_name }}</span>.<br>
                Our team will be in touch at <span class="font-medium">{{ $email }}</span> shortly.
            </p>
            <flux:link :href="route('home')" wire:navigate>← Back to homepage</flux:link>
        </div>
    @else
        <div class="flex flex-col gap-6">
            <div class="text-center">
                <flux:heading size="xl">Get started with MatterLynk</flux:heading>
                <flux:subheading>Tell us about your firm and we'll be in touch.</flux:subheading>
            </div>

            <form wire:submit="submit" class="flex flex-col gap-4">
                <flux:input
                    wire:model="firm_name"
                    label="Firm name"
                    placeholder="Acme Law Group"
                    required
                />
                @error('firm_name') <flux:error>{{ $message }}</flux:error> @enderror

                <flux:input
                    wire:model="contact_name"
                    label="Your name"
                    placeholder="Jane Smith"
                    required
                />
                @error('contact_name') <flux:error>{{ $message }}</flux:error> @enderror

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <flux:input
                            wire:model="email"
                            type="email"
                            label="Work email"
                            placeholder="jane@acmelaw.com"
                            required
                        />
                        @error('email') <flux:error>{{ $message }}</flux:error> @enderror
                    </div>
                    <flux:input
                        wire:model="phone"
                        type="tel"
                        label="Phone"
                        placeholder="+1 555 000 0000"
                    />
                </div>

                <flux:select
                    wire:model="firm_size"
                    label="Firm size"
                    placeholder="Select firm size…"
                >
                    <flux:select.option value="solo">Solo practitioner</flux:select.option>
                    <flux:select.option value="small">Small firm (2–10 lawyers)</flux:select.option>
                    <flux:select.option value="mid">Mid-size firm (11–50 lawyers)</flux:select.option>
                    <flux:select.option value="large">Large firm (51–200 lawyers)</flux:select.option>
                    <flux:select.option value="enterprise">Enterprise (200+ lawyers)</flux:select.option>
                </flux:select>
                @error('firm_size') <flux:error>{{ $message }}</flux:error> @enderror

                <flux:select
                    wire:model="clio_region"
                    label="Clio region"
                    placeholder="Select your Clio region…"
                >
                    <flux:select.option value="us">Clio US (app.clio.com)</flux:select.option>
                    <flux:select.option value="ca">Clio Canada (ca.app.clio.com)</flux:select.option>
                    <flux:select.option value="eu">Clio EU (eu.app.clio.com)</flux:select.option>
                    <flux:select.option value="au">Clio Australia (au.app.clio.com)</flux:select.option>
                    <flux:select.option value="uk">Clio UK (uk.app.clio.com)</flux:select.option>
                </flux:select>
                @error('clio_region') <flux:error>{{ $message }}</flux:error> @enderror

                <flux:textarea
                    wire:model="notes"
                    label="Anything else you'd like us to know?"
                    placeholder="e.g. current iManage version, number of iManage users, go-live timeline…"
                    rows="3"
                />

                <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled">
                    <span wire:loading.remove>Submit enquiry</span>
                    <span wire:loading>Sending…</span>
                </flux:button>

                <p class="text-center text-sm text-zinc-600 dark:text-zinc-400">
                    Already have an account?
                    <flux:link :href="route('login')" wire:navigate>Log in</flux:link>
                </p>
            </form>
        </div>
    @endif
</div>
