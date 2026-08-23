<?php

declare(strict_types=1);

namespace App\Services;

use App\Integrations\Clio\ClioConnector;
use App\Integrations\Clio\Requests\CreateWebhook;
use App\Integrations\Clio\Requests\DeleteWebhook;
use App\Integrations\Clio\Requests\GetClient;
use App\Integrations\Clio\Requests\GetClients;
use App\Integrations\Clio\Requests\GetCurrentUser;
use App\Integrations\Clio\Requests\GetCustomFields;
use App\Integrations\Clio\Requests\GetGroups;
use App\Integrations\Clio\Requests\GetMatter;
use App\Integrations\Clio\Requests\GetMatters;
use App\Integrations\Clio\Requests\GetPracticeAreas;
use App\Integrations\Clio\Requests\GetUsers;
use App\Integrations\Clio\Requests\GetWebhooks;
use App\Integrations\Clio\Requests\PatchMatter;
use App\Integrations\Clio\Requests\RenewWebhook;
use App\Models\Tenant;

class ClioApiService
{
    private readonly ClioConnector $connector;

    public function __construct(Tenant $tenant)
    {
        $this->connector = new ClioConnector($tenant);
    }

    public function getCurrentUser(): array
    {
        $response = $this->connector->send(new GetCurrentUser());
        $response->throw();

        return $response->json();
    }

    public function getMatters(int $page = 1, int $limit = 200, ?string $updatedSince = null): array
    {
        $response = $this->connector->send(new GetMatters($page, $limit, $updatedSince));
        $response->throw();

        return $response->json();
    }

    public function getMatter(int $id): array
    {
        $response = $this->connector->send(new GetMatter($id));
        $response->throw();

        return $response->json();
    }

    public function getClients(int $page = 1, int $limit = 200, ?string $updatedSince = null): array
    {
        $response = $this->connector->send(new GetClients($page, $limit, $updatedSince));
        $response->throw();

        return $response->json();
    }

    public function getClient(int $id): array
    {
        $response = $this->connector->send(new GetClient($id));
        $response->throw();

        return $response->json();
    }

    public function getPracticeAreas(): array
    {
        $response = $this->connector->send(new GetPracticeAreas());
        $response->throw();

        return $response->json();
    }

    public function getUsers(): array
    {
        $response = $this->connector->send(new GetUsers());
        $response->throw();

        return $response->json();
    }

    public function getGroups(): array
    {
        $response = $this->connector->send(new GetGroups());
        $response->throw();

        return $response->json();
    }

    public function getCustomFields(): array
    {
        $response = $this->connector->send(new GetCustomFields());
        $response->throw();

        return $response->json();
    }

    public function getWebhooks(): array
    {
        $response = $this->connector->send(new GetWebhooks());
        $response->throw();

        return $response->json();
    }

    public function createWebhook(array $data): array
    {
        $response = $this->connector->send(new CreateWebhook($data));
        $response->throw();

        return $response->json();
    }

    public function deleteWebhook(int $id): bool
    {
        $response = $this->connector->send(new DeleteWebhook($id));
        $response->throw();

        return $response->successful();
    }

    public function renewWebhook(int $id, array $data = []): array
    {
        $response = $this->connector->send(new RenewWebhook($id, $data));
        $response->throw();

        return $response->json();
    }

    public function patchMatter(int $id, array $data): array
    {
        $response = $this->connector->send(new PatchMatter($id, $data));
        $response->throw();

        return $response->json();
    }
}
