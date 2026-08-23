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
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

class ImanageApiService
{
    private readonly ImanageConnector $connector;

    public function __construct(Tenant $tenant)
    {
        $this->connector = new ImanageConnector($tenant);
    }

    /**
     * Retrieve all libraries accessible to the authenticated user for a given customer.
     *
     * Libraries represent iManage databases (e.g. "ACTIVE", "ARCHIVE") that
     * contain workspaces, folders, and documents. The response includes each
     * library's ID, name, and customer_id.
     *
     * @throws FatalRequestException|RequestException
     * @throws \Throwable
     */
    public function getLibraries(string $customerId): array
    {
        $response = $this->connector->send(new GetLibraries($customerId));
        $response->throw();

        return $response->json();
    }

    /**
     * Retrieve a single library by its ID.
     *
     * Returns the metadata for one specific library, including its configuration
     * and the customer it belongs to.
     *
     * @throws FatalRequestException|RequestException
     */
    public function getLibrary(string $libraryId): array
    {
        $response = $this->connector->send(new GetLibrary($libraryId));
        $response->throw();

        return $response->json();
    }

    /**
     * Retrieve practice areas (custom29) for a given library.
     *
     * Practice areas are iManage custom field 29 values used to classify
     * workspaces by legal practice (e.g. Litigation, Corporate, Real Estate).
     * Supports pagination via $limit and $skip.
     *
     * @throws FatalRequestException|RequestException
     * @throws \Throwable
     */
    public function getPracticeAreas(string $customerId, string $libraryId, int $limit = 9999, int $offset = 0): array
    {
        $response = $this->connector->send(new GetPracticeAreas($customerId, $libraryId, $limit, $offset));
        $response->throw();

        return $response->json();
    }

    /**
     * Retrieve all workspace templates available in a library.
     *
     * Templates define the folder structure that is replicated when a new
     * workspace is created. Used during matter provisioning to select the
     * appropriate template for the client's workflow. Supports pagination.
     *
     * @throws FatalRequestException|RequestException
     */
    public function getTemplates(string $customerId, string $libraryId, int $limit = 9999): array
    {
        $response = $this->connector->send(new GetTemplates($customerId, $libraryId, $limit));
        $response->throw();

        return $response->json();
    }

    /**
     * Retrieve a single workspace template by its ID.
     *
     * Returns the full template definition including its folder structure,
     * used to preview or validate a template before workspace creation.
     *
     * @throws FatalRequestException|RequestException|\JsonException|\Throwable
     */
    public function getTemplate(string $customerId, string $libraryId, string $templateId): array
    {
        $response = $this->connector->send(new GetTemplate($customerId, $libraryId, $templateId));
        $response->throw();

        return $response->json();
    }

    /**
     * Retrieve security groups defined in a library.
     *
     * Groups are used to apply access controls to workspaces and folders.
     * This is primarily used when Group Security Mapping is enabled for a
     * tenant to map Clio user roles to iManage security groups.
     * Supports pagination.
     *
     * @throws FatalRequestException|RequestException
     */
    public function getGroups(string $customerId, string $libraryId, int $limit = 9999): array
    {
        $response = $this->connector->send(new GetGroups($customerId, $libraryId, $limit));
        $response->throw();

        return $response->json();
    }

    /**
     * Retrieve users registered in a library.
     *
     * Returns iManage user accounts that can be assigned as document owners
     * or granted workspace/folder access. Supports pagination.
     *
     * @throws FatalRequestException|RequestException
     */
    public function getUsers(string $customerId, string $libraryId, int $limit = 9999): array
    {
        $response = $this->connector->send(new GetUsers($customerId, $libraryId, $limit));
        $response->throw();

        return $response->json();
    }

    /**
     * Retrieve a single workspace by its key.
     *
     * The workspace key is the iManage identifier for a matter workspace
     * (typically a combination of client and matter numbers). Returns workspace
     * metadata including its name, description, and custom fields.
     *
     * @throws FatalRequestException|RequestException
     */
    public function getWorkspace(string $customerId, string $libraryId, string $key): array
    {
        $response = $this->connector->send(new GetWorkspace($customerId, $libraryId, $key));
        $response->throw();

        return $response->json();
    }

    /**
     * Create a new workspace in iManage.
     *
     * Provisions a workspace (matter folder) in the specified library using
     * the provided data payload, which should include name, description,
     * custom fields, and an optional template ID. This is called during
     * Clio matter creation to mirror the matter in iManage.
     *
     * @throws FatalRequestException|RequestException
     */
    public function createWorkspace(string $customerId, string $libraryId, array $data): array
    {
        $response = $this->connector->send(new CreateWorkspace($customerId, $libraryId, $data));
        $response->throw();

        return $response->json();
    }

    /**
     * Update an existing workspace by its key.
     *
     * Patches the workspace with the provided data payload. Used to sync
     * changes from Clio (e.g. matter name or description updates) back into
     * the corresponding iManage workspace.
     *
     * @throws FatalRequestException|RequestException
     */
    public function updateWorkspace(string $customerId, string $libraryId, string $key, array $data): array
    {
        $response = $this->connector->send(new UpdateWorkspace($customerId, $libraryId, $key, $data));
        $response->throw();

        return $response->json();
    }

    /**
     * Retrieve custom field definitions for a library.
     *
     * Returns the schema of custom fields (custom1–custom30) configured on
     * the library, used to understand which fields are available for workspace
     * and folder profiling during matter provisioning.
     *
     * @throws FatalRequestException|RequestException
     */
    public function getCustomFields(string $customerId, string $libraryId): array
    {
        $response = $this->connector->send(new GetCustomFields($customerId, $libraryId));
        $response->throw();

        return $response->json();
    }

    /**
     * Retrieve the security policy for a workspace.
     *
     * Returns the access control configuration applied to a workspace,
     * including inherited and explicit permissions. Used when reviewing
     * or replicating security policies across workspaces.
     *
     * @throws FatalRequestException|RequestException
     */
    public function getWorkspaceSecurityPolicy(string $customerId, string $libraryId, string $key): array
    {
        $response = $this->connector->send(new GetWorkspaceSecurityPolicy($customerId, $libraryId, $key));
        $response->throw();

        return $response->json();
    }

    /**
     * Authenticate using a username and password to obtain a session token.
     *
     * Posts credentials to the iManage session endpoint and returns the
     * authentication response including the session token. Used for
     * verifying service account credentials during tenant setup.
     *
     * @throws FatalRequestException|RequestException
     */
    public function authenticatePassword(string $username, string $password): array
    {
        $response = $this->connector->send(new AuthenticatePassword($username, $password));
        $response->throw();

        return $response->json();
    }

    /**
     * Retrieve the immediate children of a workspace.
     *
     * Returns the top-level folders and documents directly inside a workspace.
     * Used when replicating template folder structures into a newly created
     * workspace or when traversing the workspace hierarchy.
     *
     * @throws FatalRequestException|RequestException
     */
    public function getWorkspaceChildren(string $customerId, string $libraryId, string $workspaceId): array
    {
        $response = $this->connector->send(new GetWorkspaceChildren($customerId, $libraryId, $workspaceId));
        $response->throw();

        return $response->json();
    }

    /**
     * Retrieve the children of a folder.
     *
     * Returns sub-folders and documents nested inside a specific folder.
     * Used during recursive traversal of workspace folder trees when
     * applying templates or replicating folder structures.
     *
     * @throws FatalRequestException|RequestException
     */
    public function getFolderChildren(string $customerId, string $libraryId, string $folderId): array
    {
        $response = $this->connector->send(new GetFolderChildren($customerId, $libraryId, $folderId));
        $response->throw();

        return $response->json();
    }

    /**
     * Retrieve the name-value profile pairs for a folder.
     *
     * Returns the profiling metadata attached to a folder (class, subclass,
     * and custom field values). Used to inspect or validate folder attributes
     * after creation or during sync operations.
     *
     * @throws FatalRequestException|RequestException
     */
    public function getFolderNameValuePairs(string $customerId, string $libraryId, string $folderId): array
    {
        $response = $this->connector->send(new GetFolderNameValuePairs($customerId, $libraryId, $folderId));
        $response->throw();

        return $response->json();
    }

    /**
     * Create a folder inside a workspace.
     *
     * Provisions a new folder within the specified workspace using the provided
     * data payload, which should include the folder name, description, default
     * security, and profile fields. Called when replicating template folder
     * structures during matter workspace provisioning.
     *
     * @throws FatalRequestException|RequestException
     */
    public function createFolder(string $customerId, string $libraryId, string $workspaceId, array $data): array
    {
        $response = $this->connector->send(new CreateFolder($customerId, $libraryId, $workspaceId, $data));
        $response->throw();

        return $response->json();
    }

    /**
     * Retrieve the security policy applied to a folder.
     *
     * Returns the explicit access control entries on a folder, including
     * inherited permissions. Used when auditing or replicating security
     * configurations across folder hierarchies.
     *
     * @throws FatalRequestException|RequestException
     */
    public function getFolderSecurity(string $customerId, string $libraryId, string $folderId): array
    {
        $response = $this->connector->send(new GetFolderSecurity($customerId, $libraryId, $folderId));
        $response->throw();

        return $response->json();
    }

    /**
     * Apply a security policy to a folder.
     *
     * Posts an access control payload to the folder's security endpoint,
     * granting or restricting access for specified users and groups. Used
     * when Group Security Mapping is enabled to enforce Clio-driven
     * permissions on iManage folders.
     *
     * @throws FatalRequestException|RequestException
     */
    public function applyFolderSecurity(string $customerId, string $libraryId, string $folderId, array $data): array
    {
        $response = $this->connector->send(new ApplyFolderSecurity($customerId, $libraryId, $folderId, $data));
        $response->throw();

        return $response->json();
    }

    /**
     * Apply a security policy to a workspace.
     *
     * Posts an access control payload to the workspace's security endpoint,
     * granting or restricting access for specified users and groups. Used
     * when Group Security Mapping is enabled to enforce Clio-driven
     * permissions on newly created iManage workspaces.
     *
     * @throws FatalRequestException|RequestException
     */
    public function applyWorkspaceSecurity(string $customerId, string $libraryId, string $workspaceId, array $data): array
    {
        $response = $this->connector->send(new ApplyWorkspaceSecurity($customerId, $libraryId, $workspaceId, $data));
        $response->throw();

        return $response->json();
    }

    /**
     * Find an existing iManage client (custom1) and update it, or create it if not found.
     *
     * Performs a GET to check whether the client record already exists in iManage.
     * If found, it patches the description, enabled flag, and HIPAA flag.
     * If not found (404), it creates the record with the provided key and attributes.
     * After the write, a final GET is performed to return the authoritative
     * persisted data, ensuring the caller always receives confirmed values.
     *
     * @throws FatalRequestException|RequestException|RuntimeException
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
     * Find an existing iManage matter (custom2) and update it, or create it if not found.
     *
     * Performs a GET to check whether the matter record already exists in iManage.
     * If found, it patches the description, enabled flag, and HIPAA flag.
     * If not found (404), it creates the record linked to the parent client key.
     * After the write, a final GET is performed to return the authoritative
     * persisted data, ensuring the caller always receives confirmed values.
     *
     * @throws FatalRequestException|RequestException|RuntimeException
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
