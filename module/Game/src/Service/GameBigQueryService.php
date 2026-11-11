<?php

declare(strict_types=1);

namespace Game\Service;

use General\Service\BigQueryService;
use Ramsey\Uuid\Uuid;

/**
 * GameBigQueryService
 *
 * Dedicated service for handling BigQuery data ingestion, validation, and table-specific
 * schema adaptations for game metrics, assessments, and telemetry.
 */
class GameBigQueryService
{
    /**
     * @var BigQueryService|null
     */
    private ?BigQueryService $bigQueryService;

    /**
     * Predefined schemas for known BigQuery game telemetry and assessment tables.
     */
    private const KNOWN_TABLE_SCHEMAS = [
        'recog_shape_recog' => [
            'session_id'            => 'STRING',
            'game_id'               => 'STRING',
            'game_type'             => 'STRING',
            'game_category'         => 'STRING',
            'game_program'          => 'STRING',
            'user_id'               => 'STRING',
            'problem_solving_index' => 'FLOAT',
            'creative_index'        => 'FLOAT',
            'average_time_correct'  => 'FLOAT',
            'average_time_failed'   => 'FLOAT',
            'total_game_time'       => 'FLOAT',
            'total_correct'         => 'INTEGER',
            'total_failed'          => 'INTEGER',
            'start_time'            => 'STRING',
            'input_events'          => 'JSON',
            'match_events'          => 'JSON',
        ],
        'game_session_telemetry' => [
            'session_id'            => 'STRING',
            'game_id'               => 'STRING',
            'user_id'               => 'STRING',
            'age'                   => 'FLOAT',
            'game_type'             => 'STRING',
            'score'                 => 'FLOAT',
            'duration'              => 'FLOAT',
            'events'                => 'JSON',
            'created_at'            => 'STRING',
        ],
    ];

    /**
     * GameBigQueryService constructor.
     *
     * @param BigQueryService|null $bigQueryService
     */
    public function __construct(?BigQueryService $bigQueryService = null)
    {
        $this->bigQueryService = $bigQueryService;
    }

    /**
     * @return BigQueryService|null
     */
    public function getBigQueryService(): ?BigQueryService
    {
        return $this->bigQueryService;
    }

    /**
     * @param BigQueryService $bigQueryService
     * @return self
     */
    public function setBigQueryService(BigQueryService $bigQueryService): self
    {
        $this->bigQueryService = $bigQueryService;
        return $this;
    }

    /**
     * Ingest arbitrary data into BigQuery with schema validation and table-specific routing.
     *
     * Mandatory validation rule:
     * Every record must contain valid 'age' (numeric and >= 0) and non-empty 'user_id'.
     *
     * @param string $tableName Target BigQuery table name.
     * @param array|object|string $data Single record, array of records, or raw JSON string.
     * @param string|null $datasetId Optional target dataset ID.
     * @param mixed $identity Authenticated user identity payload.
     * @return array Ingestion result details.
     * @throws \InvalidArgumentException If validation fails.
     * @throws \RuntimeException If BigQueryService is unavailable.
     * @throws \Exception On BigQuery API error.
     */
    public function postBigQueryData(string $tableName, $data, ?string $datasetId = null, $identity = null): array
    {
        $bqService = $this->getBigQueryService();
        if (!$bqService) {
            throw new \RuntimeException("BigQuery service is not initialized or configured.");
        }

        $tableName = trim($tableName);
        if (empty($tableName) || !preg_match('/^[a-zA-Z0-9_]{1,1024}$/', $tableName)) {
            throw new \InvalidArgumentException("Invalid table name. Must contain only alphanumeric characters and underscores.");
        }

        $targetDataset = !empty($datasetId) ? trim($datasetId) : $bqService->getDatasetId();
        if (!preg_match('/^[a-zA-Z0-9_]{1,1024}$/', $targetDataset)) {
            throw new \InvalidArgumentException("Invalid dataset ID format.");
        }

        // If raw JSON string is passed, extract and decode it
        if (is_string($data)) {
            $decoded = json_decode(trim($data), true);
            if (json_last_error() === JSON_ERROR_NONE && (is_array($decoded) || is_object($decoded))) {
                $data = (array) $decoded;
            } else {
                throw new \InvalidArgumentException("The data parameter contains invalid JSON: " . json_last_error_msg());
            }
        }

        // If data is an array with JSON string elements, decode each element
        if (is_array($data)) {
            foreach ($data as $k => $item) {
                if (is_string($item)) {
                    $decodedItem = json_decode(trim($item), true);
                    if (json_last_error() === JSON_ERROR_NONE && (is_array($decodedItem) || is_object($decodedItem))) {
                        $data[$k] = (array) $decodedItem;
                    }
                }
            }
        }

        if (empty($data) || (!is_array($data) && !is_object($data))) {
            throw new \InvalidArgumentException("Data payload cannot be empty and must be an object, array of objects, or valid JSON string.");
        }

        $isBatch = isset($data[0]) && is_array($data[0]);
        $rows = $isBatch ? $data : [$data];

        // Mandatory validation: every record must contain valid 'age' (numeric and >= 0) and non-empty 'user_id'
        foreach ($rows as $idx => &$row) {
            if (!is_array($row)) {
                throw new \InvalidArgumentException(sprintf("Record at index %d must be an object/associative array.", $idx));
            }

            // 1. Validate 'age' is present, not empty, numeric and non-negative
            if (!array_key_exists('age', $row) || $row['age'] === null || $row['age'] === '' || (is_string($row['age']) && trim((string) $row['age']) === '')) {
                throw new \InvalidArgumentException(sprintf(
                    "Validation failed: Record at index %d is missing required field 'age' or it is empty.",
                    $idx
                ));
            }
            if (!is_numeric($row['age']) || (float) $row['age'] < 0) {
                throw new \InvalidArgumentException(sprintf(
                    "Validation failed: Record at index %d field 'age' must be a valid positive number.",
                    $idx
                ));
            }

            // 2. Validate 'user_id' is present and not empty
            $userId = $row['user_id'] ?? $row['user_uuid'] ?? null;
            if ($userId === null || trim((string) $userId) === '') {
                throw new \InvalidArgumentException(sprintf(
                    "Validation failed: Record at index %d is missing required field 'user_id' or it is empty.",
                    $idx
                ));
            }

            // Harmonize user identifier across records
            $row['user_id'] = (string) $userId;
            $row['user_uuid'] = (string) ($row['user_uuid'] ?? $userId);
        }
        unset($row);

        // Switch condition to route implementation based on the table name
        switch ($tableName) {
            case 'recog_shape_recog':
                return $this->postRecogShapeRecog($rows, $targetDataset, $identity, $isBatch);

            case 'game_session_telemetry':
            case 'game_analytics':
                return $this->postGameSessionTelemetry($tableName, $rows, $targetDataset, $identity, $isBatch);

            case 'toddler_assessment':
            case 'toddler_games':
                return $this->postToddlerAssessment($tableName, $rows, $targetDataset, $identity, $isBatch);

            case 'dyslexia_assessment':
            case 'dyslexia_subtypes':
                return $this->postDyslexiaAssessment($tableName, $rows, $targetDataset, $identity, $isBatch);

            case 'adhd_assessment':
            case 'adhd_scale':
                return $this->postAdhdAssessment($tableName, $rows, $targetDataset, $identity, $isBatch);

            case 'dyscalculia_assessment':
                return $this->postDyscalculiaAssessment($tableName, $rows, $targetDataset, $identity, $isBatch);

            default:
                // Generic handler that conforms to ANY arbitrary table name and dynamic schema sent to it
                return $this->postGenericTable($tableName, $rows, $targetDataset, $identity, $isBatch);
        }
    }

    /**
     * Privately defined handler for the 'recog_shape_recog' cognitive assessment table.
     */
    private function postRecogShapeRecog(array $rows, string $datasetId, $identity, bool $isBatch): array
    {
        return $this->postGenericTable('recog_shape_recog', $rows, $datasetId, $identity, $isBatch);
    }

    /**
     * Privately defined handler for game session telemetry & analytics tables.
     */
    private function postGameSessionTelemetry(string $tableName, array $rows, string $datasetId, $identity, bool $isBatch): array
    {
        return $this->postGenericTable($tableName, $rows, $datasetId, $identity, $isBatch);
    }

    /**
     * Privately defined handler for toddler assessment tables.
     */
    private function postToddlerAssessment(string $tableName, array $rows, string $datasetId, $identity, bool $isBatch): array
    {
        return $this->postGenericTable($tableName, $rows, $datasetId, $identity, $isBatch);
    }

    /**
     * Privately defined handler for dyslexia assessment tables.
     */
    private function postDyslexiaAssessment(string $tableName, array $rows, string $datasetId, $identity, bool $isBatch): array
    {
        return $this->postGenericTable($tableName, $rows, $datasetId, $identity, $isBatch);
    }

    /**
     * Privately defined handler for ADHD assessment tables.
     */
    private function postAdhdAssessment(string $tableName, array $rows, string $datasetId, $identity, bool $isBatch): array
    {
        return $this->postGenericTable($tableName, $rows, $datasetId, $identity, $isBatch);
    }

    /**
     * Privately defined handler for dyscalculia assessment tables.
     */
    private function postDyscalculiaAssessment(string $tableName, array $rows, string $datasetId, $identity, bool $isBatch): array
    {
        return $this->postGenericTable($tableName, $rows, $datasetId, $identity, $isBatch);
    }

    /**
     * Privately defined generic handler that extracts the relevant fields according to the target BigQuery table schema.
     */
    private function postGenericTable(string $tableName, array $rows, string $datasetId, $identity, bool $isBatch): array
    {
        $bqService = $this->getBigQueryService();
        $bqService->ensureTableExists($tableName, $datasetId);

        $tableMetadata = $bqService->getTable($tableName, $datasetId);
        $allowedFields = null;
        $fieldTypes = [];
        if (!empty($tableMetadata['schema']['fields']) && is_array($tableMetadata['schema']['fields'])) {
            $allowedFields = [];
            foreach ($tableMetadata['schema']['fields'] as $field) {
                if (!empty($field['name'])) {
                    $allowedFields[$field['name']] = true;
                    $fieldTypes[$field['name']] = strtoupper($field['type'] ?? 'STRING');
                }
            }
        } elseif (isset(self::KNOWN_TABLE_SCHEMAS[$tableName])) {
            $allowedFields = [];
            foreach (self::KNOWN_TABLE_SCHEMAS[$tableName] as $fieldName => $fieldType) {
                $allowedFields[$fieldName] = true;
                $fieldTypes[$fieldName] = strtoupper($fieldType);
            }
        }

        $sanitizedRows = [];
        foreach ($rows as $row) {
            // Auto-generate session_id or uuid if missing and defined in table schema
            if (empty($row['session_id']) && ($allowedFields === null || isset($allowedFields['session_id']))) {
                $row['session_id'] = Uuid::uuid4()->toString();
            }
            if (empty($row['uuid']) && ($allowedFields === null || isset($allowedFields['uuid']))) {
                $row['uuid'] = Uuid::uuid4()->toString();
            }

            // Harmonize user_id and user_uuid based on schema expectations
            if (empty($row['user_id']) && !empty($row['user_uuid'])) {
                $row['user_id'] = (string) $row['user_uuid'];
            }
            if (empty($row['user_uuid']) && !empty($row['user_id'])) {
                $row['user_uuid'] = (string) $row['user_id'];
            }

            // Auto-generate start_time / created_at if missing and present in schema
            if (empty($row['start_time']) && ($allowedFields === null || isset($allowedFields['start_time']))) {
                $row['start_time'] = gmdate('Y-m-d\TH:i:s\Z');
            }
            if (empty($row['created_at']) && ($allowedFields === null || isset($allowedFields['created_at']))) {
                $row['created_at'] = gmdate('Y-m-d\TH:i:s\Z');
            }

            $cleanRow = [];
            foreach ($row as $col => $val) {
                // If schema is known, extract only fields declared in the BigQuery table
                if ($allowedFields !== null && !isset($allowedFields[$col])) {
                    continue;
                }

                $type = $fieldTypes[$col] ?? null;

                if ($type === 'JSON' || is_array($val) || is_object($val)) {
                    $cleanRow[$col] = is_string($val) ? $val : json_encode($val);
                } elseif ($type === 'INTEGER' || $type === 'INT64') {
                    $cleanRow[$col] = $val !== null ? (int) $val : null;
                } elseif ($type === 'FLOAT' || $type === 'FLOAT64' || $type === 'NUMERIC') {
                    $cleanRow[$col] = $val !== null ? (float) $val : null;
                } elseif ($type === 'BOOLEAN' || $type === 'BOOL') {
                    $cleanRow[$col] = $val !== null ? (bool) $val : null;
                } else {
                    $cleanRow[$col] = $val !== null ? (is_array($val) ? json_encode($val) : (string) $val) : null;
                }
            }

            if (empty($cleanRow)) {
                // If column filtering stripped everything, pass original row with JSON-encoded arrays
                $fallbackRow = [];
                foreach ($row as $k => $v) {
                    $fallbackRow[$k] = (is_array($v) || is_object($v)) ? json_encode($v) : $v;
                }
                $cleanRow = $fallbackRow;
            }

            $sanitizedRows[] = $cleanRow;
        }

        $insertPayload = $isBatch ? $sanitizedRows : $sanitizedRows[0];
        $result = $bqService->create($tableName, $insertPayload, $datasetId);

        return [
            'table'          => $tableName,
            'dataset'        => $datasetId,
            'inserted_count' => $result['inserted_count'] ?? count($sanitizedRows),
            'method'         => $result['method'] ?? 'streaming_insert',
            'session_id'     => $sanitizedRows[0]['session_id'] ?? null,
            'records'        => $isBatch ? $sanitizedRows : $sanitizedRows[0],
        ];
    }
}

