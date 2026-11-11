<?php

declare(strict_types=1);

namespace General\Service;

use Google\Cloud\BigQuery\BigQueryClient;
use Ramsey\Uuid\Uuid;

/**
 * Service class connecting to Google BigQuery.
 * Ensures table existence before executing queries and provides generic CRUD operations (Create, Update, Delete).
 * Supports both Google Cloud SDK (BigQueryClient) and resilient native Google BigQuery REST API / OAuth 2.0.
 */
class BigQueryService
{
    /**
     * BigQuery Project ID.
     */
    private string $projectId;

    /**
     * Default Dataset ID.
     */
    private string $datasetId;

    /**
     * BigQuery geographic dataset location (e.g. 'europe-west4', 'US').
     */
    private string $location;

    /**
     * Path to service account JSON key file.
     */
    private ?string $keyFilePath;

    /**
     * Credentials JSON array payload.
     */
    private ?array $keyFileJson;

    /**
     * Service account client email.
     */
    private ?string $clientEmail = null;

    /**
     * Raw configuration array.
     */
    private array $config;

    /**
     * Cached Google OAuth access token.
     */
    private ?string $cachedAccessToken = null;

    /**
     * Timestamp when the cached access token expires.
     */
    private int $tokenExpiresAt = 0;

    /**
     * BigQuery Client instance.
     *
     * @var BigQueryClient|object|null
     */
    private $client = null;

    /**
     * BigQueryService Constructor.
     *
     * @param array $config Configuration array containing optional 'bigquery' key options.
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
        $bqConfig = $config['bigquery'] ?? $config['google_bigquery'] ?? [];

        // 1. Resolve key file path
        $keyPath = getenv('BIGQUERY_KEY_FILE')
            ?: getenv('GOOGLE_APPLICATION_CREDENTIALS')
            ?: ($bqConfig['key_file'] ?? null);

        $baseDir = dirname(__DIR__, 4);

        if (!empty($keyPath)) {
            if (!file_exists($keyPath)) {
                $candidate = $baseDir . '/' . ltrim($keyPath, '/');
                if (file_exists($candidate)) {
                    $keyPath = $candidate;
                }
            }
        } else {
            // Default lookups in data/ and data/keys/
            if (file_exists($baseDir . '/data/dyxi-502320-ec4ecc7ca00d.json')) {
                $keyPath = $baseDir . '/data/dyxi-502320-ec4ecc7ca00d.json';
            } elseif (file_exists($baseDir . '/data/keys/dyxi-502320-ec4ecc7ca00d.json')) {
                $keyPath = $baseDir . '/data/keys/dyxi-502320-ec4ecc7ca00d.json';
            }
        }
        $this->keyFilePath = $keyPath;

        // 2. Resolve credentials JSON array
        $keyJsonRaw = $bqConfig['key_json'] ?? getenv('BIGQUERY_KEY_JSON') ?: null;
        if (is_array($keyJsonRaw)) {
            $this->keyFileJson = $keyJsonRaw;
        } elseif (is_string($keyJsonRaw) && !empty($keyJsonRaw)) {
            $this->keyFileJson = json_decode($keyJsonRaw, true);
        } elseif (!empty($this->keyFilePath) && file_exists($this->keyFilePath)) {
            $contents = file_get_contents($this->keyFilePath);
            if ($contents !== false) {
                $decoded = json_decode($contents, true);
                $this->keyFileJson = is_array($decoded) ? $decoded : null;
            } else {
                $this->keyFileJson = null;
            }
        } else {
            $this->keyFileJson = null;
        }

        // 3. Resolve Project ID
        $envProject = getenv('BIGQUERY_PROJECT_ID') ?: getenv('GOOGLE_CLOUD_PROJECT');
        if (!empty($envProject)) {
            $this->projectId = (string) $envProject;
        } elseif (!empty($bqConfig['project_id'])) {
            $this->projectId = (string) $bqConfig['project_id'];
        } elseif (!empty($this->keyFileJson['project_id'])) {
            $this->projectId = (string) $this->keyFileJson['project_id'];
        } else {
            $this->projectId = 'dyxi-502320';
        }

        // 4. Resolve Dataset ID
        $this->datasetId = (string) (getenv('BIGQUERY_DATASET_ID')
            ?: ($bqConfig['dataset_id'] ?? 'dyxi_api_pre_prod'));

        // 5. Resolve Dataset Location
        $this->location = (string) (getenv('BIGQUERY_LOCATION')
            ?: ($bqConfig['location'] ?? 'europe-west4'));

        // 6. Resolve Client Email
        $this->clientEmail = (string) (getenv('BIGQUERY_CLIENT_EMAIL')
            ?: ($bqConfig['client_email'] ?? ($this->keyFileJson['client_email'] ?? null)));
    }

    /**
     * Retrieve or instantiate the BigQuery client instance.
     *
     * @return BigQueryClient|object
     */
    public function getClient()
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $options = [
            'projectId' => $this->projectId,
            'location'  => $this->location,
        ];

        if (!empty($this->keyFilePath) && file_exists($this->keyFilePath)) {
            $options['keyFilePath'] = $this->keyFilePath;
        } elseif (!empty($this->keyFileJson) && is_array($this->keyFileJson)) {
            $options['keyFile'] = $this->keyFileJson;
        }

        if (class_exists(BigQueryClient::class)) {
            $this->client = new BigQueryClient($options);
            return $this->client;
        }

        // Native resilient client object if Google Cloud BigQuery SDK library is not installed
        $service = $this;
        $this->client = new class($this->projectId, $this->datasetId, $this->location, $service) {
            private string $projectId;
            private string $datasetId;
            private string $location;
            private BigQueryService $service;

            public function __construct(string $projectId, string $datasetId, string $location, BigQueryService $service)
            {
                $this->projectId = $projectId;
                $this->datasetId = $datasetId;
                $this->location  = $location;
                $this->service   = $service;
            }

            public function getProjectId(): string
            {
                return $this->projectId;
            }

            public function getDatasetId(): string
            {
                return $this->datasetId;
            }

            public function getLocation(): string
            {
                return $this->location;
            }

            public function getService(): BigQueryService
            {
                return $this->service;
            }

            public function dataset(string $datasetId)
            {
                $svc = $this->service;
                return new class($datasetId, $svc) {
                    private string $id;
                    private BigQueryService $svc;

                    public function __construct(string $id, BigQueryService $svc)
                    {
                        $this->id = $id;
                        $this->svc = $svc;
                    }

                    public function exists(): bool
                    {
                        $datasets = $this->svc->listDatasets();
                        return in_array($this->id, $datasets, true);
                    }

                    public function table(string $tableId)
                    {
                        $dsId = $this->id;
                        $svc = $this->svc;
                        return new class($tableId, $dsId, $svc) {
                            private string $tableId;
                            private string $datasetId;
                            private BigQueryService $svc;

                            public function __construct(string $tableId, string $datasetId, BigQueryService $svc)
                            {
                                $this->tableId = $tableId;
                                $this->datasetId = $datasetId;
                                $this->svc = $svc;
                            }

                            public function exists(): bool
                            {
                                return $this->svc->tableExists($this->tableId, $this->datasetId);
                            }
                        };
                    }
                };
            }
        };

        return $this->client;
    }

    /**
     * Generates a signed OAuth 2.0 access token via Google Service Account private key assertion.
     *
     * @return string Valid Google OAuth 2.0 Bearer access token.
     * @throws \Exception If private key or credentials cannot be parsed or exchanged.
     */
    public function getAccessToken(): string
    {
        if ($this->cachedAccessToken !== null && time() < ($this->tokenExpiresAt - 60)) {
            return $this->cachedAccessToken;
        }

        if (empty($this->keyFileJson) || empty($this->keyFileJson['private_key']) || empty($this->keyFileJson['client_email'])) {
            throw new \Exception("BigQuery service account credentials missing client_email or private_key.");
        }

        $now = time();
        $tokenUri = $this->keyFileJson['token_uri'] ?? 'https://oauth2.googleapis.com/token';

        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claimSet = [
            'iss'   => $this->keyFileJson['client_email'],
            'scope' => 'https://www.googleapis.com/auth/bigquery',
            'aud'   => $tokenUri,
            'exp'   => $now + 3600,
            'iat'   => $now,
        ];

        $b64Url = static function ($data): string {
            $input = is_string($data) ? $data : (string) json_encode($data);
            return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($input));
        };

        $signingInput = $b64Url($header) . '.' . $b64Url($claimSet);

        $privateKey = openssl_pkey_get_private($this->keyFileJson['private_key']);
        if (!$privateKey) {
            throw new \Exception("Failed to parse BigQuery private key from service account credentials.");
        }

        $signature = '';
        $signed = openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        if (!$signed) {
            throw new \Exception("OpenSSL failed to sign Google OAuth JWT assertion.");
        }

        $jwt = $signingInput . '.' . $b64Url($signature);

        $curlOptions = [
            CURLOPT_URL            => $tokenUri,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        ];

        $caFile = $this->detectCaFile();
        if ($caFile !== null) {
            $curlOptions[CURLOPT_CAINFO] = $caFile;
        }

        $ch = curl_init();
        curl_setopt_array($ch, $curlOptions);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            throw new \Exception("cURL error during Google OAuth token exchange: " . $curlErr);
        }

        $decoded = json_decode((string) $response, true);
        if ($httpCode !== 200 || empty($decoded['access_token'])) {
            $msg = $decoded['error_description'] ?? ($decoded['error'] ?? 'Unknown authentication error');
            throw new \Exception("Google OAuth token exchange failed (HTTP {$httpCode}): {$msg}");
        }

        $this->cachedAccessToken = (string) $decoded['access_token'];
        $expiresIn = isset($decoded['expires_in']) ? (int) $decoded['expires_in'] : 3600;
        $this->tokenExpiresAt = $now + $expiresIn;

        return $this->cachedAccessToken;
    }

    /**
     * Executes an authenticated HTTP request to the Google BigQuery v2 REST API.
     *
     * @param string $method HTTP method (GET, POST, DELETE, etc.).
     * @param string $endpoint Endpoint path relative to BigQuery v2 API.
     * @param array|null $payload Optional JSON body payload.
     * @return array Decoded response array including '__http_code'.
     * @throws \Exception On network failure or API error response.
     */
    public function callRestApi(string $method, string $endpoint, ?array $payload = null): array
    {
        $token = $this->getAccessToken();
        $url = 'https://bigquery.googleapis.com/bigquery/v2/' . ltrim($endpoint, '/');

        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        $curlOptions = [
            CURLOPT_URL            => $url,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => $headers,
        ];

        if ($payload !== null) {
            $curlOptions[CURLOPT_POSTFIELDS] = json_encode($payload);
        }

        $caFile = $this->detectCaFile();
        if ($caFile !== null) {
            $curlOptions[CURLOPT_CAINFO] = $caFile;
        }

        $ch = curl_init();
        curl_setopt_array($ch, $curlOptions);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            throw new \Exception("BigQuery REST request failed ({$method} {$endpoint}): " . $curlErr);
        }

        $data = json_decode((string) $response, true) ?: [];
        $data['__http_code'] = $httpCode;

        if ($httpCode >= 400 && $httpCode !== 404) {
            $errMessage = $data['error']['message'] ?? json_encode($data['error'] ?? $data);
            throw new \Exception(sprintf(
                "BigQuery API Error [HTTP %d] on %s: %s",
                $httpCode,
                $endpoint,
                $errMessage
            ));
        }

        return $data;
    }

    /**
     * Helper to detect valid SSL CA certificate bundle path.
     */
    private function detectCaFile(): ?string
    {
        $npmCa = getenv('npm_config_cafile');
        if ($npmCa && file_exists($npmCa)) {
            return $npmCa;
        }
        $candidates = [
            '/usr/local/etc/openssl@3/cert.pem',
            '/etc/ssl/cert.pem',
            '/etc/ssl/certs/ca-certificates.crt',
            '/usr/local/share/ca-certificates/cacert.pem',
        ];
        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }
        return null;
    }

    /**
     * Get active Project ID.
     */
    public function getProjectId(): string
    {
        return $this->projectId;
    }

    /**
     * Set active Project ID.
     */
    public function setProjectId(string $projectId): self
    {
        $this->projectId = $projectId;
        $this->client = null;
        return $this;
    }

    /**
     * Get active Default Dataset ID.
     */
    public function getDatasetId(): string
    {
        return $this->datasetId;
    }

    /**
     * Set active Default Dataset ID.
     */
    public function setDatasetId(string $datasetId): self
    {
        $this->datasetId = $datasetId;
        return $this;
    }

    /**
     * Get active Dataset geographic location.
     */
    public function getLocation(): string
    {
        return $this->location;
    }

    /**
     * Set active Dataset geographic location.
     */
    public function setLocation(string $location): self
    {
        $this->location = $location;
        return $this;
    }

    /**
     * Get key file path.
     */
    public function getKeyFilePath(): ?string
    {
        return $this->keyFilePath;
    }

    /**
     * Get decoded service account credentials JSON.
     */
    public function getKeyFileJson(): ?array
    {
        return $this->keyFileJson;
    }

    /**
     * Get client email.
     */
    public function getServiceAccountEmail(): ?string
    {
        return $this->clientEmail;
    }

    /**
     * Lists datasets available under the configured BigQuery Project.
     *
     * @return array List of dataset IDs.
     */
    public function listDatasets(): array
    {
        $path = sprintf('projects/%s/datasets', rawurlencode($this->projectId));
        $data = $this->callRestApi('GET', $path);
        $datasetList = [];
        foreach ($data['datasets'] ?? [] as $ds) {
            if (!empty($ds['datasetReference']['datasetId'])) {
                $datasetList[] = $ds['datasetReference']['datasetId'];
            }
        }
        return $datasetList;
    }

    /**
     * Lists table names under a given dataset.
     *
     * @param string|null $datasetId Optional dataset ID (defaults to active datasetId).
     * @return array List of table names.
     */
    public function listTables(?string $datasetId = null): array
    {
        $targetDataset = !empty($datasetId) ? $datasetId : $this->datasetId;
        $path = sprintf('projects/%s/datasets/%s/tables', rawurlencode($this->projectId), rawurlencode($targetDataset));
        $data = $this->callRestApi('GET', $path);
        $tables = [];
        foreach ($data['tables'] ?? [] as $t) {
            if (!empty($t['tableReference']['tableId'])) {
                $tables[] = $t['tableReference']['tableId'];
            }
        }
        return $tables;
    }

    /**
     * Retrieves table metadata schema from BigQuery.
     *
     * @param string $tableName Table name to inspect.
     * @param string|null $datasetId Optional target dataset ID.
     * @return array|null Table schema and metadata or null if not found.
     */
    public function getTable(string $tableName, ?string $datasetId = null): ?array
    {
        $targetDataset = !empty($datasetId) ? $datasetId : $this->datasetId;
        $client = $this->getClient();

        if ($client instanceof BigQueryClient) {
            try {
                $dataset = $client->dataset($targetDataset);
                $table = $dataset->table($tableName);
                if ($table->exists()) {
                    return $table->info();
                }
            } catch (\Throwable $e) {
                // fall through to REST
            }
        }

        try {
            $path = sprintf(
                'projects/%s/datasets/%s/tables/%s',
                rawurlencode($this->projectId),
                rawurlencode($targetDataset),
                rawurlencode($tableName)
            );
            $response = $this->callRestApi('GET', $path);
            if (($response['__http_code'] ?? 0) === 200) {
                return $response;
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    /**
     * Checks if a specified table exists in the BigQuery dataset.
     *
     * @param string $tableName Table name to verify.
     * @param string|null $datasetId Optional target dataset ID (defaults to active datasetId).
     * @return bool True if table exists, false otherwise.
     */
    public function tableExists(string $tableName, ?string $datasetId = null): bool
    {
        $targetDataset = !empty($datasetId) ? $datasetId : $this->datasetId;
        $client = $this->getClient();

        if ($client instanceof BigQueryClient) {
            try {
                $dataset = $client->dataset($targetDataset);
                if (!$dataset->exists()) {
                    return false;
                }
                $table = $dataset->table($tableName);
                return $table->exists();
            } catch (\Throwable $e) {
                return false;
            }
        }

        // Direct REST check
        try {
            $path = sprintf(
                'projects/%s/datasets/%s/tables/%s',
                rawurlencode($this->projectId),
                rawurlencode($targetDataset),
                rawurlencode($tableName)
            );
            $response = $this->callRestApi('GET', $path);
            return ($response['__http_code'] ?? 0) === 200;
        } catch (\Throwable $e) {
            // Fallback query check via INFORMATION_SCHEMA
            try {
                $sql = sprintf(
                    "SELECT table_name FROM `%s.%s.INFORMATION_SCHEMA.TABLES` WHERE table_name = @tableName LIMIT 1",
                    $this->projectId,
                    $targetDataset
                );
                $results = $this->runRawQuery($sql, ['tableName' => $tableName]);
                return !empty($results);
            } catch (\Throwable $e2) {
                return false;
            }
        }
    }

    /**
     * Enforces table existence before performing operations.
     *
     * @param string $tableName Table name to verify.
     * @param string|null $datasetId Optional dataset ID.
     * @throws \Exception If table does not exist.
     */
    public function ensureTableExists(string $tableName, ?string $datasetId = null): void
    {
        $targetDataset = !empty($datasetId) ? $datasetId : $this->datasetId;
        if (!$this->tableExists($tableName, $targetDataset)) {
            throw new \Exception(sprintf(
                "BigQuery table '%s' does not exist in dataset '%s' (project '%s').",
                $tableName,
                $targetDataset,
                $this->projectId
            ));
        }
    }

    /**
     * Executes a SQL query after checking table existence if a table name is provided.
     *
     * @param string $query Standard SQL query string.
     * @param array $params Parameter bindings array.
     * @param string|null $tableName Optional table name to verify before executing.
     * @param string|null $datasetId Optional dataset ID.
     * @return array Query result rows array.
     * @throws \Exception If table check fails or query execution errors.
     */
    public function executeQuery(string $query, array $params = [], ?string $tableName = null, ?string $datasetId = null): array
    {
        if (!empty($tableName)) {
            $this->ensureTableExists($tableName, $datasetId);
        }

        return $this->runRawQuery($query, $params);
    }

    /**
     * Generic CREATE / INSERT operation: Inserts one or more records into a specified table.
     * Verifies table existence before executing the insert.
     *
     * @param string $tableName Target BigQuery table name.
     * @param array $data Single associative row array or array of associative row arrays.
     * @param string|null $datasetId Optional target dataset ID.
     * @return array Execution status and details.
     * @throws \Exception If table does not exist or payload is empty.
     */
    public function create(string $tableName, array $data, ?string $datasetId = null): array
    {
        $targetDataset = !empty($datasetId) ? $datasetId : $this->datasetId;
        $this->ensureTableExists($tableName, $targetDataset);

        if (empty($data)) {
            throw new \Exception("Cannot insert empty data array into BigQuery table '{$tableName}'.");
        }

        $rows = isset($data[0]) && is_array($data[0]) ? $data : [$data];
        $client = $this->getClient();

        // High-performance streaming insert via Google BigQuery SDK if available
        if ($client instanceof BigQueryClient) {
            $insertRows = [];
            foreach ($rows as $row) {
                $insertRows[] = ['data' => $row];
            }
            $table = $client->dataset($targetDataset)->table($tableName);
            $insertResponse = $table->insertRows($insertRows);

            if ($insertResponse->isSuccessful()) {
                return [
                    'success'        => true,
                    'inserted_count' => count($rows),
                    'tableName'      => $tableName,
                    'datasetId'      => $targetDataset,
                    'method'         => 'streaming_insert',
                ];
            }

            $errors = $insertResponse->failedRows();
            throw new \Exception("BigQuery streaming insert failed: " . json_encode($errors));
        }

        // Direct BigQuery REST tabledata.insertAll streaming insert
        try {
            $insertRows = [];
            foreach ($rows as $row) {
                $insertRows[] = [
                    'insertId' => Uuid::uuid4()->toString(),
                    'json'     => $row,
                ];
            }

            $endpoint = sprintf(
                'projects/%s/datasets/%s/tables/%s/insertAll',
                rawurlencode($this->projectId),
                rawurlencode($targetDataset),
                rawurlencode($tableName)
            );

            $payload = [
                'kind' => 'bigquery#tableDataInsertAllRequest',
                'rows' => $insertRows,
            ];

            $res = $this->callRestApi('POST', $endpoint, $payload);
            if (!empty($res['insertErrors'])) {
                throw new \Exception("BigQuery streaming insert errors: " . json_encode($res['insertErrors']));
            }

            return [
                'success'        => true,
                'inserted_count' => count($rows),
                'tableName'      => $tableName,
                'datasetId'      => $targetDataset,
                'method'         => 'rest_streaming_insert',
            ];
        } catch (\Throwable $restEx) {
            // Fallback SQL INSERT DML statement execution if insertAll fails
            $columns = array_keys($rows[0]);
            $paramBindings = [];
            $valuePlaceholders = [];

            foreach ($rows as $index => $row) {
                $rowPlaceholders = [];
                foreach ($row as $colName => $value) {
                    $paramKey = 'ins_' . $index . '_' . preg_replace('/[^a-zA-Z0-9_]/', '_', (string) $colName);
                    $rowPlaceholders[] = '@' . $paramKey;
                    $paramBindings[$paramKey] = $value;
                }
                $valuePlaceholders[] = '(' . implode(', ', $rowPlaceholders) . ')';
            }

            $sql = sprintf(
                "INSERT INTO `%s.%s.%s` (%s) VALUES %s",
                $this->projectId,
                $targetDataset,
                $tableName,
                implode(', ', array_map(fn($col) => "`{$col}`", $columns)),
                implode(', ', $valuePlaceholders)
            );

            $result = $this->runRawQuery($sql, $paramBindings);

            return [
                'success'        => true,
                'inserted_count' => count($rows),
                'tableName'      => $tableName,
                'datasetId'      => $targetDataset,
                'method'         => 'sql_dml_insert',
                'details'        => $result,
            ];
        }
    }

    /**
     * Generic UPDATE operation: Updates records matching a WHERE clause in a specified table.
     * Verifies table existence before executing the update.
     *
     * @param string $tableName Target BigQuery table name.
     * @param array $data Associative array of column => value updates.
     * @param string $whereClause SQL WHERE condition (e.g. "uuid = @target_uuid").
     * @param array $params Parameter bindings for the WHERE clause.
     * @param string|null $datasetId Optional dataset ID.
     * @return array Execution status and response details.
     * @throws \Exception If table does not exist, data is empty, or WHERE clause is missing.
     */
    public function update(string $tableName, array $data, string $whereClause, array $params = [], ?string $datasetId = null): array
    {
        $targetDataset = !empty($datasetId) ? $datasetId : $this->datasetId;
        $this->ensureTableExists($tableName, $targetDataset);

        if (empty($data)) {
            throw new \Exception("Update data payload cannot be empty.");
        }
        if (empty(trim($whereClause))) {
            throw new \Exception("A valid non-empty WHERE clause is required for BigQuery UPDATE operations.");
        }

        $setAssignments = [];
        $mergedParams = $params;

        foreach ($data as $colName => $value) {
            $paramKey = 'upd_' . preg_replace('/[^a-zA-Z0-9_]/', '_', (string) $colName);
            $setAssignments[] = sprintf("`%s` = @%s", $colName, $paramKey);
            $mergedParams[$paramKey] = $value;
        }

        $sql = sprintf(
            "UPDATE `%s.%s.%s` SET %s WHERE %s",
            $this->projectId,
            $targetDataset,
            $tableName,
            implode(', ', $setAssignments),
            $whereClause
        );

        $result = $this->runRawQuery($sql, $mergedParams);

        return [
            'success'   => true,
            'tableName' => $tableName,
            'datasetId' => $targetDataset,
            'method'    => 'sql_dml_update',
            'details'   => $result,
        ];
    }

    /**
     * Generic DELETE operation: Deletes records matching a WHERE clause in a specified table.
     * Verifies table existence before executing the deletion.
     *
     * @param string $tableName Target BigQuery table name.
     * @param string $whereClause SQL WHERE condition (e.g. "id = @target_id").
     * @param array $params Parameter bindings for the WHERE clause.
     * @param string|null $datasetId Optional target dataset ID.
     * @return array Execution status and details.
     * @throws \Exception If table does not exist or WHERE clause is missing.
     */
    public function delete(string $tableName, string $whereClause, array $params = [], ?string $datasetId = null): array
    {
        $targetDataset = !empty($datasetId) ? $datasetId : $this->datasetId;
        $this->ensureTableExists($tableName, $targetDataset);

        if (empty(trim($whereClause))) {
            throw new \Exception("A valid non-empty WHERE clause is required for BigQuery DELETE operations.");
        }

        $sql = sprintf(
            "DELETE FROM `%s.%s.%s` WHERE %s",
            $this->projectId,
            $targetDataset,
            $tableName,
            $whereClause
        );

        $result = $this->runRawQuery($sql, $params);

        return [
            'success'   => true,
            'tableName' => $tableName,
            'datasetId' => $targetDataset,
            'method'    => 'sql_dml_delete',
            'details'   => $result,
        ];
    }

    /**
     * Helper to execute a raw SQL query string against BigQuery.
     *
     * @param string $sql SQL statement string.
     * @param array $params Parameter bindings.
     * @return array Array of result row arrays.
     */
    private function runRawQuery(string $sql, array $params = []): array
    {
        $client = $this->getClient();

        if ($client instanceof BigQueryClient) {
            $queryConfig = $client->query($sql);
            if (!empty($params)) {
                $queryConfig->parameters($params);
            }
            $queryResults = $client->runQuery($queryConfig);
            $rows = [];
            foreach ($queryResults as $row) {
                $rows[] = $row;
            }
            return $rows;
        }

        // Native Google BigQuery REST API execution
        $queryParameters = [];
        foreach ($params as $paramName => $paramValue) {
            $type = 'STRING';
            $valStr = (string) $paramValue;
            if (is_int($paramValue)) {
                $type = 'INT64';
            } elseif (is_float($paramValue)) {
                $type = 'FLOAT64';
            } elseif (is_bool($paramValue)) {
                $type = 'BOOL';
                $valStr = $paramValue ? 'true' : 'false';
            }

            $queryParameters[] = [
                'name'           => (string) $paramName,
                'parameterType'  => ['type' => $type],
                'parameterValue' => ['value' => $valStr],
            ];
        }

        $endpoint = sprintf('projects/%s/queries', rawurlencode($this->projectId));
        $payload = [
            'query'          => $sql,
            'useLegacySql'   => false,
            'location'       => $this->location,
            'defaultDataset' => [
                'datasetId' => $this->datasetId,
                'projectId' => $this->projectId,
            ],
        ];

        if (!empty($queryParameters)) {
            $payload['parameterMode'] = 'NAMED';
            $payload['queryParameters'] = $queryParameters;
        }

        $data = $this->callRestApi('POST', $endpoint, $payload);
        if (isset($data['error'])) {
            throw new \Exception("BigQuery query error: " . ($data['error']['message'] ?? json_encode($data['error'])));
        }

        $fields = array_column($data['schema']['fields'] ?? [], 'name');
        $rows = [];
        foreach ($data['rows'] ?? [] as $row) {
            $item = [];
            foreach ($row['f'] ?? [] as $colIndex => $cell) {
                $colName = $fields[$colIndex] ?? "col_{$colIndex}";
                $item[$colName] = $cell['v'] ?? null;
            }
            $rows[] = $item;
        }

        return $rows;
    }
}
