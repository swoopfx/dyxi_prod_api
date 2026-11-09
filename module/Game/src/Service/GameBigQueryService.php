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
     * Every record must contain 'age' and ('user_id' or 'user_uuid') and neither can be empty.
     *
     * @param string $tableName Target BigQuery table name.
     * @param array|object $data Single record or array of records.
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

        if (empty($data) || (!is_array($data) && !is_object($data))) {
            throw new \InvalidArgumentException("Data payload cannot be empty and must be an object or array of objects.");
        }

        $isBatch = isset($data[0]) && is_array($data[0]);
        $rows = $isBatch ? $data : [$data];

        // Mandatory validation: every record must contain 'age' and ('user_id' or 'user_uuid') and neither can be empty!
        foreach ($rows as $idx => $row) {
            if (!is_array($row)) {
                throw new \InvalidArgumentException(sprintf("Record at index %d must be an object/associative array.", $idx));
            }

            // 1. Validate 'age' is present and not empty
            if (!array_key_exists('age', $row) || $row['age'] === null || $row['age'] === '' || (is_string($row['age']) && trim((string) $row['age']) === '')) {
                throw new \InvalidArgumentException(sprintf(
                    "Validation failed: Record at index %d is missing required field 'age' or it is empty.",
                    $idx
                ));
            }

            // 2. Validate 'user_id' or 'user_uuid' is present and not empty
            $userId = $row['user_id'] ?? null;
            $userUuid = $row['user_uuid'] ?? null;
            $hasUserId = ($userId !== null && trim((string) $userId) !== '');
            $hasUserUuid = ($userUuid !== null && trim((string) $userUuid) !== '');

            if (!$hasUserId && !$hasUserUuid) {
                throw new \InvalidArgumentException(sprintf(
                    "Validation failed: Record at index %d must contain either 'user_id' or 'user_uuid' and it cannot be empty.",
                    $idx
                ));
            }
        }

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
                // Generic handler that conforms to ANY arbitrary table name and data structure sent to it
                return $this->postGenericTable($tableName, $rows, $targetDataset, $identity, $isBatch);
        }
    }

    /**
     * Privately defined handler for the 'recog_shape_recog' cognitive assessment table.
     */
    private function postRecogShapeRecog(array $rows, string $datasetId, $identity, bool $isBatch): array
    {
        $bqService = $this->getBigQueryService();
        $bqService->ensureTableExists('recog_shape_recog', $datasetId);

        $tableMetadata = $bqService->getTable('recog_shape_recog', $datasetId);
        $allowedFields = null;
        if (!empty($tableMetadata['schema']['fields']) && is_array($tableMetadata['schema']['fields'])) {
            $allowedFields = [];
            foreach ($tableMetadata['schema']['fields'] as $field) {
                if (!empty($field['name'])) {
                    $allowedFields[$field['name']] = true;
                }
            }
        }

        $sanitizedRows = [];
        foreach ($rows as $row) {
            // Auto-generate session_id if missing
            if (empty($row['session_id'])) {
                $row['session_id'] = Uuid::uuid4()->toString();
            }

            // Harmonize user identifier: ensure user_id is populated from user_uuid if needed
            if (empty($row['user_id']) && !empty($row['user_uuid'])) {
                $row['user_id'] = (string) $row['user_uuid'];
            }

            // Auto-generate start_time if missing
            if (empty($row['start_time'])) {
                $row['start_time'] = gmdate('Y-m-d\TH:i:s\Z');
            }

            // Serialize JSON columns
            if (isset($row['input_events']) && (is_array($row['input_events']) || is_object($row['input_events']))) {
                $row['input_events'] = json_encode($row['input_events']);
            }
            if (isset($row['match_events']) && (is_array($row['match_events']) || is_object($row['match_events']))) {
                $row['match_events'] = json_encode($row['match_events']);
            }

            // Normalize numeric metrics
            if (isset($row['problem_solving_index'])) {
                $row['problem_solving_index'] = (float) $row['problem_solving_index'];
            }
            if (isset($row['creative_index'])) {
                $row['creative_index'] = (float) $row['creative_index'];
            }
            if (isset($row['average_time_correct'])) {
                $row['average_time_correct'] = (float) $row['average_time_correct'];
            }
            if (isset($row['average_time_failed'])) {
                $row['average_time_failed'] = (float) $row['average_time_failed'];
            }
            if (isset($row['total_game_time'])) {
                $row['total_game_time'] = (float) $row['total_game_time'];
            }
            if (isset($row['total_correct'])) {
                $row['total_correct'] = (int) $row['total_correct'];
            }
            if (isset($row['total_failed'])) {
                $row['total_failed'] = (int) $row['total_failed'];
            }

            // Filter columns to schema if schema is introspected
            if ($allowedFields !== null) {
                $cleanRow = [];
                foreach ($row as $k => $v) {
                    if (isset($allowedFields[$k])) {
                        $cleanRow[$k] = $v;
                    }
                }
            } else {
                $cleanRow = $row;
            }

            $sanitizedRows[] = $cleanRow;
        }

        $insertPayload = $isBatch ? $sanitizedRows : $sanitizedRows[0];
        $result = $bqService->create('recog_shape_recog', $insertPayload, $datasetId);

        return [
            'table'          => 'recog_shape_recog',
            'dataset'        => $datasetId,
            'inserted_count' => $result['inserted_count'] ?? count($sanitizedRows),
            'method'         => $result['method'] ?? 'streaming_insert',
            'session_id'     => $sanitizedRows[0]['session_id'] ?? null,
            'records'        => $isBatch ? $sanitizedRows : $sanitizedRows[0],
        ];
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
     * Privately defined generic handler that conforms to ANY arbitrary table name and data structure sent to it.
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
            if (empty($row['start_time']) && isset($allowedFields['start_time'])) {
                $row['start_time'] = gmdate('Y-m-d\TH:i:s\Z');
            }
            if (empty($row['created_at']) && isset($allowedFields['created_at'])) {
                $row['created_at'] = gmdate('Y-m-d\TH:i:s\Z');
            }

            $cleanRow = [];
            foreach ($row as $col => $val) {
                // If schema is known, ignore fields not declared in the BigQuery table
                if ($allowedFields !== null && !isset($allowedFields[$col])) {
                    continue;
                }

                $type = $fieldTypes[$col] ?? null;

                if ($type === 'JSON' || is_array($val) || is_object($val)) {
                    $cleanRow[$col] = is_string($val) ? $val : json_encode($val);
                } elseif ($type === 'INTEGER' || $type === 'INT64') {
                    $cleanRow[$col] = $val !== null ? (int) $val : null;
                } elseif ($type === 'FLOAT' || $type === 'FLOAT64') {
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

