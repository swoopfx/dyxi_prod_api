# Using `/api/game/game/post-bigquery`: Ingestion, Payload Consumption & Service Processing

This guide details the complete architecture, JSON payload consumption pipeline, and service-layer processing for the Google BigQuery streaming ingestion endpoint:

* **Endpoint Route**: `/api/game/game/post-bigquery` (Alias: `/api/game/game/bigquery`)
* **HTTP Method**: `POST`
* **Controller**: `Game\Controller\GameController::postBigqueryAction()`
* **Primary Service**: `Game\Service\GameService::postBigQueryData()` (and decoupled `Game\Service\GameBigQueryService`)
* **Underlying Engine**: `General\Service\BigQueryService::create()`

---

## 1. High-Level Architectural Flow

```
+---------------------------------------------------------------------------------------------------+
| CLIENT REQUEST (cURL, Mobile App, Web Frontend)                                                   |
| - Header: Authorization: Bearer <JWT>                                                             |
| - Header: Content-Type: application/json                                                          |
| - Body: JSON containing tableName, rawData (object / array / JSON string), age, and user_id       |
+---------------------------------------------------------------------------------------------------+
                                                  |
                                                  v
+---------------------------------------------------------------------------------------------------+
| 1. CONTROLLER ACTION: GameController::postBigqueryAction()                                        |
|                                                                                                   |
|   A. Request Method Validation: Ensures request is POST (returns 405 if not).                     |
|   B. Authentication Check: Resolves user identity from ApiAuthenticateService (401 if missing).   |
|   C. JSON Body Parsing: Extracts request JSON body via json_decode() (400 if malformed).          |
|   D. Table & Dataset Resolution: Extracts tableName (required) and dataset (optional).            |
|   E. Payload Extraction:                                                                          |
|      - Extracts rawData (or data / rows).                                                         |
|      - Automatically detects and decodes raw JSON strings or arrays of JSON strings.              |
|   F. Accompaniment & Propagation:                                                                 |
|      - Propagates root-level 'age' and 'user_id' into each record if omitted within rawData.      |
|   G. Mandatory Strict Validation:                                                                 |
|      - Every record must have 'age' (numeric and >= 0).                                           |
|      - Every record must have 'user_id' (non-empty string).                                       |
|   H. Delegation: Hands execution off to GameService::postBigQueryData().                          |
+---------------------------------------------------------------------------------------------------+
                                                  |
                                                  v
+---------------------------------------------------------------------------------------------------+
| 2. SERVICE LAYER: GameService / GameBigQueryService::postBigQueryData()                           |
|                                                                                                   |
|   A. Input Sanitization: Validates table name and dataset format via regex pattern.               |
|   B. Table Verification: Invokes BigQueryService::ensureTableExists($tableName, $datasetId).      |
|   C. Schema Introspection:                                                                        |
|      - Calls BigQueryService::getTable() to inspect live BigQuery column definitions.             |
|      - Falls back to KNOWN_TABLE_SCHEMAS registry if live metadata is offline/unavailable.        |
|   D. Dynamic Field Extraction & Transformation:                                                   |
|      - Extracts ONLY the fields declared in the target table schema.                              |
|      - Drops extra/undeclared fields to prevent BigQuery streaming insert schema mismatch errors. |
|      - Type casts: INTEGER -> int, FLOAT -> float, BOOL -> bool, JSON/Arrays -> json_encode().    |
|      - Auto-injects UUID v4 ('session_id', 'uuid') if missing and present in schema.              |
|      - Auto-injects ISO-8601 UTC timestamp ('start_time', 'created_at') if missing in schema.    |
|      - Harmonizes 'user_id' and 'user_uuid' according to table schema expectations.               |
+---------------------------------------------------------------------------------------------------+
                                                  |
                                                  v
+---------------------------------------------------------------------------------------------------+
| 3. INGESTION ENGINE: BigQueryService::create()                                                    |
|                                                                                                   |
|   A. Direct Google Cloud SDK (BigQueryClient): executes $table->insertRows($insertRows)           |
|   B. Native OAuth 2.0 REST API: executes POST tabledata.insertAll with UUID insertIds.            |
|   C. Fallback: Parameterized SQL INSERT DML statement execution.                                  |
|   D. Ingestion Result: Returns inserted record count, session UUID, table, and dataset ID.       |
+---------------------------------------------------------------------------------------------------+
                                                  |
                                                  v
+---------------------------------------------------------------------------------------------------+
| 4. RESPONSE: HTTP 201 Created (or 400 Bad Request / 404 Not Found)                                |
+---------------------------------------------------------------------------------------------------+
```

---

## 2. Action Layer: JSON Payload Consumption

The `GameController::postBigqueryAction()` method handles flexible consumption of varied JSON structures.

### 2.1 Supported Payload Formats for `rawData`

The endpoint accommodates three primary input shapes:

1. **Standard JSON Object**:
   ```json
   {
     "tableName": "recog_shape_recog",
     "rawData": {
       "age": 5,
       "user_id": "usr_1001",
       "game_id": "game_shape_01",
       "problem_solving_index": 85.0
     }
   }
   ```

2. **Batch JSON Array of Objects**:
   ```json
   {
     "tableName": "recog_shape_recog",
     "rawData": [
       {"age": 4.5, "user_id": "usr_1001", "game_id": "game_01", "total_correct": 9},
       {"age": 5.0, "user_id": "usr_1002", "game_id": "game_01", "total_correct": 8}
     ]
   }
   ```

3. **Raw JSON String** (e.g. from mobile telemetry buffers or raw event loggers):
   ```json
   {
     "tableName": "recog_shape_recog",
     "age": 5,
     "user_id": "usr_1001",
     "rawData": "{\"game_id\":\"game_01\",\"problem_solving_index\":85.0,\"total_correct\":9}"
   }
   ```

### 2.2 Propagation of Root-Level `age` and `user_id`

Callers can specify `age` and `user_id` either:
- Directly inside the `rawData` record(s), **OR**
- Alongside `rawData` at the top level of the request JSON payload.

When provided at the root level, the controller automatically injects them into every record in `rawData` before validation and processing:

```php
// Extract root-level values if supplied
$rootAge = $postData['age'] ?? null;
$rootUserId = $postData['user_id'] ?? $postData['user_uuid'] ?? null;

// Propagate into records if missing
if (!$isBatch) {
    if ($rootAge !== null && (!isset($rawData['age']) || $rawData['age'] === '')) {
        $rawData['age'] = $rootAge;
    }
    if ($rootUserId !== null && (!isset($rawData['user_id']) || $rawData['user_id'] === '')) {
        $rawData['user_id'] = $rootUserId;
    }
} else {
    foreach ($rawData as &$record) {
        if ($rootAge !== null && (!isset($record['age']) || $record['age'] === '')) {
            $record['age'] = $rootAge;
        }
        if ($rootUserId !== null && (!isset($record['user_id']) || $record['user_id'] === '')) {
            $record['user_id'] = $rootUserId;
        }
    }
}
```

### 2.3 Strict Validation Rules

Every record is evaluated against strict validation criteria before any BigQuery operations commence:

| Field | Rules | Failure Error |
| :--- | :--- | :--- |
| `age` | Must exist, cannot be `null` or empty string, must be numeric, must be non-negative (`>= 0`). | `Validation failed: Record at index 0 must contain a valid required 'age' (numeric and non-negative).` |
| `user_id` | Must exist (or `user_uuid`), cannot be `null` or empty string. Harmonized across `user_id` and `user_uuid`. | `Validation failed: Record at index 0 is missing required field 'user_id' or it is empty.` |

---

## 3. Service Layer: Dynamic Field Extraction & BigQuery Ingestion

Once validated, the controller passes the extracted payload to `GameService::postBigQueryData($tableName, $rawData, $datasetId, $identity)`.

### 3.1 Dynamic Schema Extraction

Because each target BigQuery table possesses its own specific schema (e.g. `recog_shape_recog` versus `game_session_telemetry`), the service dynamically inspects the table:

1. **Remote Schema Retrieval**: Calls `BigQueryService::getTable($tableName, $datasetId)` to fetch declared fields from Google BigQuery.
2. **Schema Registry Fallback**: If remote metadata cannot be reached (e.g. offline sandbox or credentials negotiation delay), it uses pre-registered schemas:
   ```php
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
   ```

### 3.2 Filtering Undeclared Columns

Google BigQuery streaming ingestion rejects records that contain column names not defined in the table schema (`no such field: <field_name>`).

The service extracts **only the relevant fields** that match the schema:

```php
$cleanRow = [];
foreach ($row as $col => $val) {
    // Drop fields not present in target BigQuery table schema
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
```

*Example*: For `recog_shape_recog`, `age` is validated as required on input, but since the `recog_shape_recog` table does not have an `age` column, `age` is excluded from the insert payload so BigQuery accepts the stream without schema errors. Conversely, for tables that include `age` (such as `game_session_telemetry`), `age` is preserved and cast to the column's type (`FLOAT`/`INTEGER`).

---

## 4. Practical Usage Examples

### 4.1 Example: Cognitive Assessment Ingestion (`recog_shape_recog`)

```bash
curl -X POST https://api.dyxi.site/api/game/game/post-bigquery \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <JWT_TOKEN>" \
  -d '{
    "tableName": "recog_shape_recog",
    "dataset": "dyxi_api_pre_prod",
    "rawData": {
      "age": 5,
      "user_id": "usr_7f8d9b1a-3c4e-4e6f-8a12-9c2b4d6e8f10",
      "game_id": "game_shape_sorter_01",
      "game_type": "toddler_assessment",
      "game_category": "shape_recognition",
      "game_program": "early_intervention_v1",
      "problem_solving_index": 88.5,
      "creative_index": 72.0,
      "average_time_correct": 2.15,
      "average_time_failed": 3.80,
      "total_game_time": 42.5,
      "total_correct": 9,
      "total_failed": 1,
      "input_events": [
        {"timestamp": 1714567890123, "type": "touch_down", "x": 100.5, "y": 200.0}
      ],
      "match_events": [
        {"target": "circle", "selected": "circle", "correct": true, "reaction_time_ms": 1250}
      ]
    }
  }'
```

**Response (HTTP 201 Created)**:
```json
{
  "success": true,
  "data": {
    "table": "recog_shape_recog",
    "dataset": "dyxi_api_pre_prod",
    "inserted_count": 1,
    "method": "rest_streaming_insert",
    "session_id": "3bb7f102-178e-4a65-bb34-49f7cc668683",
    "records": {
      "game_id": "game_shape_sorter_01",
      "game_type": "toddler_assessment",
      "game_category": "shape_recognition",
      "game_program": "early_intervention_v1",
      "user_id": "usr_7f8d9b1a-3c4e-4e6f-8a12-9c2b4d6e8f10",
      "problem_solving_index": 88.5,
      "creative_index": 72,
      "average_time_correct": 2.15,
      "average_time_failed": 3.8,
      "total_game_time": 42.5,
      "total_correct": 9,
      "total_failed": 1,
      "input_events": "[{\"timestamp\":1714567890123,\"type\":\"touch_down\",\"x\":100.5,\"y\":200}]",
      "match_events": "[{\"target\":\"circle\",\"selected\":\"circle\",\"correct\":true,\"reaction_time_ms\":1250}]",
      "session_id": "3bb7f102-178e-4a65-bb34-49f7cc668683",
      "start_time": "2026-11-11T06:40:00Z"
    }
  },
  "description": "Successfully posted 1 record(s) into BigQuery table 'recog_shape_recog' (dataset: 'dyxi_api_pre_prod')."
}
```

---

### 4.2 Example: Ingestion via Raw Stringified JSON Payload

When clients buffer payloads as raw JSON strings:

```bash
curl -X POST https://api.dyxi.site/api/game/game/post-bigquery \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <JWT_TOKEN>" \
  -d '{
    "tableName": "recog_shape_recog",
    "age": 4.5,
    "user_id": "usr_9921",
    "rawData": "{\"game_id\":\"game_shape_sorter_01\",\"problem_solving_index\":92.0,\"total_correct\":10,\"total_failed\":0}"
  }'
```

**Controller Processing**:
1. Detects `is_string($rawData) === true`.
2. Parses via `json_decode()`.
3. Injects root `age: 4.5` and `user_id: "usr_9921"`.
4. Validates `age` (numeric, non-negative) and `user_id` (non-empty string).
5. Forwards to `GameService` for schema filtering and streaming insertion.

---

### 4.3 Example: Batch Ingestion

```bash
curl -X POST https://api.dyxi.site/api/game/game/post-bigquery \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <JWT_TOKEN>" \
  -d '{
    "tableName": "recog_shape_recog",
    "rawData": [
      {
        "age": 4,
        "user_id": "usr_ward_101",
        "game_id": "shape_1",
        "total_correct": 8,
        "total_failed": 2
      },
      {
        "age": 5,
        "user_id": "usr_ward_102",
        "game_id": "shape_1",
        "total_correct": 10,
        "total_failed": 0
      }
    ]
  }'
```

---

## 5. Error Scenarios & Troubleshooting

| Error Code | Description | Solution |
| :--- | :--- | :--- |
| `ValidationFailed` | Missing required `age` or `user_id`. | Ensure both `age` (e.g. `5` or `5.0`) and `user_id` (e.g. `"usr_123"`) are included in each record or at root. |
| `ValidationFailed` | Negative age supplied (`age: -1`). | `age` must be non-negative numeric value (`>= 0`). |
| `InvalidRawDataJson` | The string passed in `rawData` is malformed JSON. | Validate JSON syntax in client serialization before sending. |
| `TableNotFound` | The requested table does not exist in target BigQuery dataset. | Verify table name spelling or create table in BigQuery dataset. |
| `MissingDataPayload`| `rawData` is empty or not an object/array/string. | Supply a valid payload under `rawData` or `data`. |
| `Unauthorized` | Bearer token is missing, expired, or invalid. | Authenticate and supply `Authorization: Bearer <token>`. |

