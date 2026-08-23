<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Integrations\Clio\Requests\ExchangeAuthCode;
use App\Models\ClioOAuthAccessCode;
use App\Models\ClioOAuthAccessToken;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClioOAuthController extends Controller
{
    /**
     * Redirect the admin to Clio's OAuth authorization page for the given tenant.
     */
    public function redirect(int $id): RedirectResponse
    {
        $tenant = Tenant::with('clioLocation')->findOrFail($id);

        $appUrl     = rtrim((string) ($tenant->clioLocation?->app_url ?? 'https://app.clio.com/'), '/');
        $redirectUri = config('services.clio.redirect_uri');

        $url = $appUrl . '/oauth/authorize?' . http_build_query([
            'response_type' => 'code',
            'client_id'     => (string) $tenant->clio_app_id,
            'redirect_uri'  => $redirectUri,
            'state'         => $tenant->reference,
        ]);

        return redirect()->away($url);
    }

    /**
     * Handle the OAuth callback from Clio, exchange the code for tokens.
     */
    public function callback(Request $request): RedirectResponse
    {
        $code  = $request->get('code');
        $state = $request->get('state');

        if (! $code || ! $state) {
            return redirect()->route('admin.tenants.index')
                ->with('error', 'Clio authorisation failed: missing code or state parameter.');
        }

        $tenant = Tenant::with('clioLocation')->where('reference', $state)->first();

        if (! $tenant) {
            return redirect()->route('admin.tenants.index')
                ->with('error', 'Clio authorisation failed: tenant not found for the given state.');
        }

        $redirectUri = config('services.clio.redirect_uri');

        // Record the short-lived auth code for audit purposes
        ClioOAuthAccessCode::create([
            'tenant_id'    => $tenant->id,
            'code'         => $code,
            'redirect_uri' => $redirectUri,
            'expires_at'   => now()->addMinutes(10),
        ]);

        $tokenUrl = rtrim((string) ($tenant->clioLocation?->app_url ?? 'https://app.clio.com/'), '/') . '/oauth/token';

        try {
            $response = (new ExchangeAuthCode(
                tokenUrl:     $tokenUrl,
                clientId:     (string) $tenant->clio_app_id,
                clientSecret: (string) $tenant->clio_app_secret,
                code:         $code,
                redirectUri:  $redirectUri,
            ))->send();

            $data = $response->json();

            if (empty($data['access_token'])) {
                Log::error('Clio token exchange returned no access_token', [
                    'tenant_id' => $tenant->id,
                    'response'  => $data,
                ]);

                return redirect()->route('admin.tenants.show', $tenant->id)
                    ->with('error', 'Clio authorisation failed: no access token returned.');
            }

            // Revoke all existing active tokens for this tenant
            $tenant->clioOAuthAccessTokens()->where('revoked', false)->update(['revoked' => true]);

            ClioOAuthAccessToken::create([
                'tenant_id'          => $tenant->id,
                'access_token'       => $data['access_token'],
                'refresh_token'      => $data['refresh_token'] ?? null,
                'access_expires_at'  => now()->addSeconds(max(0, ($data['expires_in'] ?? 3600) - 300)),
                'refresh_expires_at' => isset($data['refresh_expires_in'])
                    ? now()->addSeconds($data['refresh_expires_in'])
                    : null,
                'revoked' => false,
            ]);

            return redirect()->route('admin.tenants.show', $tenant->id)
                ->with('success', 'Clio connected successfully.');

        } catch (\Throwable $e) {
            Log::error('Clio token exchange exception', [
                'tenant_id' => $tenant->id,
                'error'     => $e->getMessage(),
            ]);

            return redirect()->route('admin.tenants.show', $tenant->id)
                ->with('error', 'Clio authorisation failed: ' . $e->getMessage());
        }
    }
}
