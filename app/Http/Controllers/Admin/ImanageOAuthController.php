<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImanageOAuthAccessToken;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImanageOAuthController extends Controller
{
    /**
     * Redirect the admin to iManage's OAuth authorization page for the given tenant.
     */
    public function redirect(int $id): RedirectResponse
    {
        $tenant = Tenant::findOrFail($id);

        $baseUrl     = rtrim((string) $tenant->imanage_cloud_url, '/');
        $redirectUri = config('services.imanage.redirect_uri');

        $url = $baseUrl . '/auth/oauth2/authorize?' . http_build_query([
            'response_type' => 'code',
            'client_id'     => (string) $tenant->imanage_app_id,
            'scope'         => 'user',
            'state'         => $tenant->reference,
            'redirect_uri'  => $redirectUri,
        ]);

        return redirect()->away($url);
    }

    /**
     * Handle the OAuth callback from iManage, exchange the code for tokens.
     */
    public function callback(Request $request): RedirectResponse
    {
        $code  = $request->get('code');
        $state = $request->get('state');

        if (! $code || ! $state) {
            return redirect()->route('admin.tenants.index')
                ->with('error', 'iManage authorisation failed: missing code or state parameter.');
        }

        $tenant = Tenant::where('reference', $state)->first();

        if (! $tenant) {
            return redirect()->route('admin.tenants.index')
                ->with('error', 'iManage authorisation failed: tenant not found for the given state.');
        }

        $baseUrl     = rtrim((string) $tenant->imanage_cloud_url, '/');
        $tokenUrl    = $baseUrl . '/auth/oauth2/token';
        $redirectUri = config('services.imanage.redirect_uri');

        try {
            $response = Http::timeout(30)
                ->acceptJson()
                ->asForm()
                ->post($tokenUrl, [
                    'grant_type'    => 'authorization_code',
                    'client_id'     => (string) $tenant->imanage_app_id,
                    'client_secret' => (string) $tenant->imanage_app_secret,
                    'code'          => $code,
                    'redirect_uri'  => $redirectUri,
                ]);

            if (! $response->successful() || empty($response->json()['access_token'])) {
                Log::error('iManage token exchange failed', [
                    'tenant_id' => $tenant->id,
                    'status'    => $response->status(),
                    'body'      => $response->body(),
                ]);

                return redirect()->route('admin.tenants.show', $tenant->id)
                    ->with('error', 'iManage authorisation failed: could not exchange token.');
            }

            $data = $response->json();

            // Revoke all existing active tokens for this tenant
            $tenant->imanageOAuthAccessTokens()->where('revoked', false)->update(['revoked' => true]);

            ImanageOAuthAccessToken::create([
                'tenant_id'    => $tenant->id,
                'access_token' => $data['access_token'],
                'refresh_token'=> $data['refresh_token'] ?? null,
                'expires_at'   => now()->addSeconds(max(0, ($data['expires_in'] ?? 3600) - 300)),
                'revoked'      => false,
            ]);

            return redirect()->route('admin.tenants.show', $tenant->id)
                ->with('success', 'iManage connected successfully.');

        } catch (\Throwable $e) {
            Log::error('iManage token exchange exception', [
                'tenant_id' => $tenant->id,
                'error'     => $e->getMessage(),
            ]);

            return redirect()->route('admin.tenants.show', $tenant->id)
                ->with('error', 'iManage authorisation failed: ' . $e->getMessage());
        }
    }
}
