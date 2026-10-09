<?php

declare(strict_types=1);

namespace General\Service;

use Google\Cloud\BigQuery\BigQueryClient;

/**
 * Service class connecting to Google BigQuery.
 * Ensures table existence before executing queries and provides generic CRUD operations (Create, Update, Delete).
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
     * Path to service account JSON key file.
     */
    private ?string $keyFilePath;

    /**
     * Credentials JSON array payload.
     */
    private ?array $keyFileJson;

    /**
     * Raw configuration array.
     */
    private array $config;

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

        $this->projectId = (string) (getenv('BIGQUERY_PROJECT_ID')
            ?: getenv('GOOGLE_CLOUD_PROJECT')
            ?: ($bqConfig['project_id'] ?? 'dyxi-platform'));

        $this->datasetId = (string) (getenv('BIGQUERY_DATASET_ID')
            ?: ($bqConfig['dataset_id'] ?? 'dyxi_analytics'));

        $this->keyFilePath = getenv('BIGQUERY_KEY_FILE')
            ?: getenv('GOOGLE_APPLICATION_CREDENTIALS')
            ?: ($bqConfig['key_file'] ?? null);

        $keyJsonRaw = $bqConfig['key_json'] ?? getenv('BIGQUERY_KEY_JSON') ?: null;
        if (is_array($keyJsonRaw)) {
            $this->keyFileJson = $keyJsonRaw;
        } elseif (is_string($keyJsonRaw) && !empty($keyJsonRaw)) {
            $this->keyFileJson = json_decode($keyJsonRaw, true);
        } else {
            $this->keyFileJson = null;
        }
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

        // Fallback placeholder client object if Google Cloud BigQuery library is not loaded
        $this->client = new class($this->projectId, $this->datasetId) {
            private string $projectId;
            private string $datasetId;

            public function __construct(string $projectId, string $datasetId)
            {
                $this->projectId = $projectId;
                $this->datasetId = $datasetId;
            }

            public function getProjectId(): string
            {
                return $this->projectId;
            }

            public function getDatasetId(): string
            {
                return $this->datasetId;
            }
        };

        return $this->client;
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

        // Fallback query check via INFORMATION_SCHEMA
        try {
            $sql = sprintf(
                "SELECT table_name FROM `%s.%s.INFORMATION_SCHEMA.TABLES` WHERE table_name = @tableName LIMIT 1",
                $this->projectId,
                $targetDataset
            );
            $results = $this->runRawQuery($sql, ['tableName' => $tableName]);
            return !empty($results);
        } catch (\Throwable $e) {
            return false;
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

        // Fallback SQL INSERT DML statement execution
        $columns = array_keys($rows[0]);
        $paramBindings = [];
        $valuePlaceholders = [];

        foreach ($rows as $index => $row) {
            $rowPlaceholders = [];
            foreach ($row as $colName => $value) {
                $paramKey = 'ins_' . $index . '_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $colName);
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
            $paramKey = 'upd_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $colName);
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

        // Return empty array fallback if SDK client is uninitialized
        return [];
    }
}
