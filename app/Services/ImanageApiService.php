<?php

declare(strict_types=1);

namespace App\Services;

use App\Integrations\Imanage\ImanageConnector;
use App\Integrations\Imanage\Requests\AuthenticatePassword;
use App\Integrations\Imanage\Requests\CreateImanageClient;
use App\Integrations\Imanage\Requests\CreateImanageMatter;
use App\Integrations\Imanage\Requests\CreateWorkspace;
use App\Integrations\Imanage\Requests\GetCustomFields;
use App\Integrations\Imanage\Requests\GetGroups;
use App\Integrations\Imanage\Requests\GetImanageClient;
use App\Integrations\Imanage\Requests\GetImanageMatter;
use App\Integrations\Imanage\Requests\GetLibraries;
use App\Integrations\Imanage\Requests\GetLibrary;
use App\Integrations\Imanage\Requests\GetPracticeAreas;
use App\Integrations\Imanage\Requests\GetTemplate;
use App\Integrations\Imanage\Requests\GetTemplates;
use App\Integrations\Imanage\Requests\GetUsers;
use App\Integrations\Imanage\Requests\GetWorkspace;
use App\Integrations\Imanage\Requests\GetWorkspaceSecurityPolicy;
use App\Integrations\Imanage\Requests\UpdateImanageClient;
use App\Integrations\Imanage\Requests\ApplyFolderSecurity;
use App\Integrations\Imanage\Requests\ApplyWorkspaceSecurity;
use App\Integrations\Imanage\Requests\CreateFolder;
use App\Integrations\Imanage\Requests\GetFolderChildren;
use App\Integrations\Imanage\Requests\GetFolderNameValuePairs;
use App\Integrations\Imanage\Requests\GetFolderSecurity;
use App\Integrations\Imanage\Requests\GetWorkspaceChildren;
use App\Integrations\Imanage\Requests\UpdateImanageMatter;
use App\Integrations\Imanage\Requests\UpdateWorkspace;
use App\Models\Tenant;
use RuntimeException;

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

    public function getPracticeAreas(string $customerId, string $libraryId, int $limit = 100, int $skip = 0): array
    {
        $response = $this->connector->send(new GetPracticeAreas($customerId, $libraryId, $limit, $skip));
        $response->throw();

        return $response->json();
    }

    public function getTemplates(string $customerId, string $libraryId, int $limit = 100, int $skip = 0): array
    {
        $response = $this->connector->send(new GetTemplates($customerId, $libraryId, $limit, $skip));
        $response->throw();

        return $response->json();
    }

    public function getTemplate(string $customerId, string $libraryId, string $templateId): array
    {
        $response = $this->connector->send(new GetTemplate($customerId, $libraryId, $templateId));
        $response->throw();

        return $response->json();
    }

    public function getGroups(string $customerId, string $libraryId, int $limit = 100, int $skip = 0): array
    {
        $response = $this->connector->send(new GetGroups($customerId, $libraryId, $limit, $skip));
        $response->throw();

        return $response->json();
    }

    public function getUsers(string $customerId, string $libraryId, int $limit = 100, int $skip = 0): array
    {
        $response = $this->connector->send(new GetUsers($customerId, $libraryId, $limit, $skip));
        $response->throw();

        return $response->json();
    }

    public function getWorkspace(string $customerId, string $libraryId, string $key): array
    {
        $response = $this->connector->send(new GetWorkspace($customerId, $libraryId, $key));
        $response->throw();

        return $response->json();
    }

    public function createWorkspace(string $customerId, string $libraryId, array $data): array
    {
        $response = $this->connector->send(new CreateWorkspace($customerId, $libraryId, $data));
        $response->throw();

        return $response->json();
    }

    public function updateWorkspace(string $customerId, string $libraryId, string $key, array $data): array
    {
        $response = $this->connector->send(new UpdateWorkspace($customerId, $libraryId, $key, $data));
        $response->throw();

        return $response->json();
    }

    public function getCustomFields(string $customerId, string $libraryId): array
    {
        $response = $this->connector->send(new GetCustomFields($customerId, $libraryId));
        $response->throw();

        return $response->json();
    }

    public function getWorkspaceSecurityPolicy(string $customerId, string $libraryId, string $key): array
    {
        $response = $this->connector->send(new GetWorkspaceSecurityPolicy($customerId, $libraryId, $key));
        $response->throw();

        return $response->json();
    }

    public function authenticatePassword(string $username, string $password): array
    {
        $response = $this->connector->send(new AuthenticatePassword($username, $password));
        $response->throw();

        return $response->json();
    }

    public function getWorkspaceChildren(string $customerId, string $libraryId, string $workspaceId): array
    {
        $response = $this->connector->send(new GetWorkspaceChildren($customerId, $libraryId, $workspaceId));
        $response->throw();

        return $response->json();
    }

    public function getFolderChildren(string $customerId, string $libraryId, string $folderId): array
    {
        $response = $this->connector->send(new GetFolderChildren($customerId, $libraryId, $folderId));
        $response->throw();

        return $response->json();
    }

    public function getFolderNameValuePairs(string $customerId, string $libraryId, string $folderId): array
    {
        $response = $this->connector->send(new GetFolderNameValuePairs($customerId, $libraryId, $folderId));
        $response->throw();

        return $response->json();
    }

    public function createFolder(string $customerId, string $libraryId, string $workspaceId, array $data): array
    {
        $response = $this->connector->send(new CreateFolder($customerId, $libraryId, $workspaceId, $data));
        $response->throw();

        return $response->json();
    }

    public function getFolderSecurity(string $customerId, string $libraryId, string $folderId): array
    {
        $response = $this->connector->send(new GetFolderSecurity($customerId, $libraryId, $folderId));
        $response->throw();

        return $response->json();
    }

    public function applyFolderSecurity(string $customerId, string $libraryId, string $folderId, array $data): array
    {
        $response = $this->connector->send(new ApplyFolderSecurity($customerId, $libraryId, $folderId, $data));
        $response->throw();

        return $response->json();
    }

    public function applyWorkspaceSecurity(string $customerId, string $libraryId, string $workspaceId, array $data): array
    {
        $response = $this->connector->send(new ApplyWorkspaceSecurity($customerId, $libraryId, $workspaceId, $data));
        $response->throw();

        return $response->json();
    }

    /**
     * Find an existing iManage custom1 client and update it, or create it if not found.
     * Returns the confirmed data array from a GET after the write operation.
     */
    public function findOrUpsertClient(
        string $customerId,
        string $libraryId,
        string $clientKey,
        string $description,
        bool $enabled,
        bool $hipaa,
    ): array {
        $getResponse = $this->connector->send(new GetImanageClient($customerId, $libraryId, $clientKey));

        if ($getResponse->successful() && isset($getResponse->json()['data'])) {
            // Record exists — patch it
            $patchData = [
                'description' => $description,
                'enabled'     => $enabled,
                'hipaa'       => $hipaa,
            ];

            $patchResponse = $this->connector->send(
                new UpdateImanageClient($customerId, $libraryId, $clientKey, $patchData)
            );
            $patchResponse->throw();
        } elseif ($getResponse->status() === 404) {
            // Record does not exist — create it
            $postData = [
                'id'          => $clientKey,
                'description' => $description,
                'enabled'     => $enabled,
                'hipaa'       => $hipaa,
            ];

            $postResponse = $this->connector->send(
                new CreateImanageClient($customerId, $libraryId, $postData)
            );
            $postResponse->throw();
        } else {
            // Unexpected error
            $getResponse->throw();
        }

        // Re-fetch to confirm and return the authoritative data
        $confirmedResponse = $this->connector->send(new GetImanageClient($customerId, $libraryId, $clientKey));
        $confirmedResponse->throw();

        $body = $confirmedResponse->json();

        if (! isset($body['data'])) {
            throw new RuntimeException("iManage client GET after upsert returned no data for key: {$clientKey}");
        }

        return $body['data'];
    }

    /**
     * Find an existing iManage custom2 matter and update it, or create it if not found.
     * Returns the confirmed data array from a GET after the write operation.
     */
    public function findOrUpsertMatter(
        string $customerId,
        string $libraryId,
        string $matterKey,
        string $clientKey,
        string $description,
        bool $enabled,
        bool $hipaa,
    ): array {
        $getResponse = $this->connector->send(
            new GetImanageMatter($customerId, $libraryId, $matterKey, $clientKey)
        );

        if ($getResponse->successful() && isset($getResponse->json()['data'])) {
            // Record exists — patch it
            $patchData = [
                'description' => $description,
                'enabled'     => $enabled,
                'hipaa'       => $hipaa,
            ];

            $patchResponse = $this->connector->send(
                new UpdateImanageMatter($customerId, $libraryId, $matterKey, $clientKey, $patchData)
            );
            $patchResponse->throw();
        } elseif ($getResponse->status() === 404) {
            // Record does not exist — create it
            $postData = [
                'id'          => $matterKey,
                'description' => $description,
                'enabled'     => $enabled,
                'hipaa'       => $hipaa,
                'parent'      => ['id' => $clientKey],
            ];

            $postResponse = $this->connector->send(
                new CreateImanageMatter($customerId, $libraryId, $postData)
            );
            $postResponse->throw();
        } else {
            // Unexpected error
            $getResponse->throw();
        }

        // Re-fetch to confirm and return the authoritative data
        $confirmedResponse = $this->connector->send(
            new GetImanageMatter($customerId, $libraryId, $matterKey, $clientKey)
        );
        $confirmedResponse->throw();

        $body = $confirmedResponse->json();

        if (! isset($body['data'])) {
            throw new RuntimeException("iManage matter GET after upsert returned no data for key: {$matterKey}");
        }

        return $body['data'];
    }
}
