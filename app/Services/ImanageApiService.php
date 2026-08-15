<?php

declare(strict_types=1);

namespace App\Services;

use App\Integrations\Imanage\ImanageConnector;
use App\Integrations\Imanage\Requests\AuthenticatePassword;
use App\Integrations\Imanage\Requests\CreateWorkspace;
use App\Integrations\Imanage\Requests\GetCustomFields;
use App\Integrations\Imanage\Requests\GetGroups;
use App\Integrations\Imanage\Requests\GetLibraries;
use App\Integrations\Imanage\Requests\GetLibrary;
use App\Integrations\Imanage\Requests\GetPracticeAreas;
use App\Integrations\Imanage\Requests\GetTemplate;
use App\Integrations\Imanage\Requests\GetTemplates;
use App\Integrations\Imanage\Requests\GetUsers;
use App\Integrations\Imanage\Requests\GetWorkspace;
use App\Integrations\Imanage\Requests\GetWorkspaceSecurityPolicy;
use App\Integrations\Imanage\Requests\UpdateWorkspace;
use App\Models\Tenant;

class ImanageApiService
{
    private readonly ImanageConnector $connector;

    public function __construct(Tenant $tenant)
    {
        $this->connector = new ImanageConnector($tenant);
    }

    public function getLibraries(): array
    {
        $response = $this->connector->send(new GetLibraries());
        $response->throw();

        return $response->json();
    }

    public function getLibrary(string $libraryId): array
    {
        $response = $this->connector->send(new GetLibrary($libraryId));
        $response->throw();

        return $response->json();
    }

    public function getPracticeAreas(string $libraryId, int $limit = 100, int $skip = 0): array
    {
        $response = $this->connector->send(new GetPracticeAreas($libraryId, $limit, $skip));
        $response->throw();

        return $response->json();
    }

    public function getTemplates(string $libraryId, int $limit = 100, int $skip = 0): array
    {
        $response = $this->connector->send(new GetTemplates($libraryId, $limit, $skip));
        $response->throw();

        return $response->json();
    }

    public function getTemplate(string $libraryId, string $templateId): array
    {
        $response = $this->connector->send(new GetTemplate($libraryId, $templateId));
        $response->throw();

        return $response->json();
    }

    public function getGroups(string $libraryId, int $limit = 100, int $skip = 0): array
    {
        $response = $this->connector->send(new GetGroups($libraryId, $limit, $skip));
        $response->throw();

        return $response->json();
    }

    public function getUsers(string $libraryId, int $limit = 100, int $skip = 0): array
    {
        $response = $this->connector->send(new GetUsers($libraryId, $limit, $skip));
        $response->throw();

        return $response->json();
    }

    public function getWorkspace(string $libraryId, string $key): array
    {
        $response = $this->connector->send(new GetWorkspace($libraryId, $key));
        $response->throw();

        return $response->json();
    }

    public function createWorkspace(string $libraryId, array $data): array
    {
        $response = $this->connector->send(new CreateWorkspace($libraryId, $data));
        $response->throw();

        return $response->json();
    }

    public function updateWorkspace(string $libraryId, string $key, array $data): array
    {
        $response = $this->connector->send(new UpdateWorkspace($libraryId, $key, $data));
        $response->throw();

        return $response->json();
    }

    public function getCustomFields(string $libraryId): array
    {
        $response = $this->connector->send(new GetCustomFields($libraryId));
        $response->throw();

        return $response->json();
    }

    public function getWorkspaceSecurityPolicy(string $libraryId, string $key): array
    {
        $response = $this->connector->send(new GetWorkspaceSecurityPolicy($libraryId, $key));
        $response->throw();

        return $response->json();
    }

    public function authenticatePassword(string $username, string $password): array
    {
        $response = $this->connector->send(new AuthenticatePassword($username, $password));
        $response->throw();

        return $response->json();
    }
}
