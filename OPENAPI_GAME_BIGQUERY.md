# OpenAPI 3.0 Specification: Game BigQuery Ingestion Endpoint

This document provides the full OpenAPI (Swagger) 3.0 documentation for the Game BigQuery streaming ingestion endpoint under `GameController`.

* **Base URL**: `https://api.dyxi.site` (Production) / `http://localhost:8080` (Development)
* **Primary Path**: `/api/game/game/post-bigquery`
* **Alias Path**: `/api/game/game/bigquery`
* **HTTP Method**: `POST`
* **Authentication**: Bearer JWT (`Authorization: Bearer <token>`)

---

## 1. OpenAPI 3.0 YAML Specification

```yaml
openapi: 3.0.3
info:
  title: DYXI Platform - Game BigQuery Telemetry API
  description: >
    High-performance streaming endpoint to ingest, validate, and store game assessment
    telemetry and cognitive performance metrics directly into Google BigQuery.
  version: 1.0.0
servers:
  - url: https://api.dyxi.site
    description: Production API Server
  - url: http://localhost:8080
    description: Local Development Server

paths:
  /api/game/game/post-bigquery:
    post:
      tags:
        - Games
        - Analytics & BigQuery
      summary: Stream Generic Data into Google BigQuery
      description: >
        Dynamic ingestion endpoint that receives a raw JSON payload in 'rawData' (as an object,
        array of objects, or raw JSON string) whose schema varies depending on the specified 'tableName'.
        The JSON payload is extracted, and only the relevant fields matching the target BigQuery table schema
        are inserted into the database. Every payload record MUST always be accompanied by valid 'age' (numeric and non-negative)
        and non-empty 'user_id' (string), which are validated as required (supplied either embedded within rawData records
        or at the root request level). Ingestion and table-specific transformations are handled via GameService and GameBigQueryService.
      operationId: postGameBigquery
      security:
        - bearerAuth: []
      requestBody:
        required: true
        description: Generic BigQuery ingestion payload containing tableName, rawData (varying raw JSON payload), optional dataset, and required age and user_id.
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/BigQueryGenericPostRequest'
            examples:
              singleSession:
                summary: Single Ingestion Record
                value:
                  tableName: recog_shape_recog
                  dataset: dyxi_api_pre_prod
                  rawData:
                    age: 5
                    user_id: usr_7f8d9b1a-3c4e-4e6f-8a12-9c2b4d6e8f10
                    game_id: game_shape_sorter_01
                    problem_solving_index: 88.5
                    creative_index: 72.0
                    total_correct: 9
                    total_failed: 1
              batchSessions:
                summary: Batch Ingestion Records
                value:
                  tableName: recog_shape_recog
                  dataset: dyxi_api_pre_prod
                  rawData:
                    - age: 4.5
                      user_id: usr_uuid_1001
                      game_id: game_shape_sorter_01
                      total_correct: 10
                      total_failed: 0
                    - age: 5.0
                      user_id: usr_1002
                      game_id: game_shape_sorter_01
                      total_correct: 8
                      total_failed: 2
              rawJsonStringSession:
                summary: Ingestion via Raw JSON String
                value:
                  tableName: recog_shape_recog
                  age: 5
                  user_id: usr_7f8d9b1a-3c4e-4e6f-8a12-9c2b4d6e8f10
                  rawData: '{"game_id":"game_shape_sorter_01","problem_solving_index":88.5,"creative_index":72.0,"total_correct":9,"total_failed":1}'

      responses:
        '201':
          description: Telemetry successfully ingested into BigQuery
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/BigQueryPostSuccessResponse'
              example:
                success: true
                data:
                  table: recog_shape_recog
                  dataset: dyxi_api_pre_prod
                  inserted_count: 1
                  method: rest_streaming_insert
                  session_id: 2aa6f829-167f-4f54-aa23-38e6bb557572
                  records:
                    game_id: game_shape_sorter_01
                    game_type: toddler_assessment
                    game_category: shape_recognition
                    game_program: early_intervention_v1
                    problem_solving_index: 88.5
                    creative_index: 72.0
                    average_time_correct: 2.15
                    average_time_failed: 3.80
                    total_game_time: 42.5
                    total_correct: 9
                    total_failed: 1
                    input_events: '[{"timestamp":1714567890123,"type":"touch_down","x":100.5,"y":200.0}]'
                    match_events: '[{"target":"circle","selected":"circle","correct":true,"reaction_time_ms":1250}]'
                    session_id: 2aa6f829-167f-4f54-aa23-38e6bb557572
                    user_id: usr_7f8d9b1a-3c4e-4e6f-8a12-9c2b4d6e8f10
                    start_time: "2026-11-09T15:56:02Z"
                description: Successfully posted 1 record(s) into BigQuery table 'recog_shape_recog' (dataset: 'dyxi_api_pre_prod').

        '400':
          description: Bad Request - Missing parameters, invalid regex format, or malformed JSON
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/ErrorResponse'
              examples:
                missingTable:
                  summary: Missing Table Name
                  value:
                    success: false
                    error: ValidationFailed
                    description: "The 'table_name' (or 'table') parameter is required and must be a string."
                invalidRegex:
                  summary: Invalid Table Name Regex
                  value:
                    success: false
                    error: InvalidTableName
                    description: "Invalid table name format. Table name must contain only alphanumeric characters and underscores."

        '401':
          description: Unauthorized - User identity not found in request context
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/ErrorResponse'
              example:
                success: false
                error: Unauthorized
                description: User identity not found in request context.

        '404':
          description: Not Found - BigQuery table does not exist in target dataset
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/ErrorResponse'
              example:
                success: false
                error: TableNotFound
                description: "BigQuery table 'unknown_table' does not exist in dataset 'dyxi_api_pre_prod'."

        '405':
          description: Method Not Allowed - Request method is not POST
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/ErrorResponse'
              example:
                success: false
                error: MethodNotAllowed
                description: "Method Not Allowed. Use POST."

        '500':
          description: Internal Server Error - BigQuery service unavailable or credentials missing
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/ErrorResponse'
              example:
                success: false
                error: ServiceUnavailable
                description: BigQuery service is not initialized or configured in this environment.

components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT

  schemas:
    BigQueryGenericPostRequest:
      type: object
      required:
        - tableName
        - rawData
      properties:
        tableName:
          type: string
          pattern: '^[a-zA-Z0-9_]{1,1024}$'
          example: recog_shape_recog
          description: "[REQUIRED] Target BigQuery table name. Dictates which schema fields are extracted from rawData."
        rawData:
          description: >
            [REQUIRED] Ingestion data payload: raw JSON object, array of objects, or raw JSON-encoded string.
            Its dynamic structure changes depending on the defined 'tableName'. The payload is parsed and only relevant
            table schema fields are inserted into BigQuery. Must always be accompanied by required 'age' and 'user_id'
            (either embedded in rawData or supplied at root).
          oneOf:
            - $ref: '#/components/schemas/GenericRecordPayload'
            - type: array
              items:
                $ref: '#/components/schemas/GenericRecordPayload'
            - type: string
              description: Raw JSON-encoded string containing an object or array of objects.
        age:
          type: number
          example: 5
          description: "[REQUIRED if omitted in rawData] Age of the user/ward. Must be numeric and non-negative (>= 0)."
        user_id:
          type: string
          example: usr_7f8d9b1a-3c4e-4e6f-8a12-9c2b4d6e8f10
          description: "[REQUIRED if omitted in rawData] Unique user/ward identifier. Cannot be empty."
        dataset:
          type: string
          pattern: '^[a-zA-Z0-9_]{1,1024}$'
          example: dyxi_api_pre_prod
          description: "[OPTIONAL] Target BigQuery dataset ID. Defaults to 'dyxi_api_pre_prod' if omitted."

    GenericRecordPayload:
      type: object
      required:
        - age
        - user_id
      properties:
        age:
          type: number
          example: 5
          description: "[REQUIRED] Age of the user/ward. Must be numeric and >= 0."
        user_id:
          type: string
          example: usr_7f8d9b1a-3c4e-4e6f-8a12-9c2b4d6e8f10
          description: "[REQUIRED] Unique user identifier. Cannot be empty."
        user_uuid:
          type: string
          example: 7f8d9b1a-3c4e-4e6f-8a12-9c2b4d6e8f10
          description: "[OPTIONAL] Alternative user UUID alias. Harmonized automatically with user_id."
      additionalProperties: true

    BigQueryPostSuccessResponse:
      type: object
      properties:
        success:
          type: boolean
          example: true
        data:
          type: object
          properties:
            table:
              type: string
              example: recog_shape_recog
            dataset:
              type: string
              example: dyxi_api_pre_prod
            inserted_count:
              type: integer
              example: 1
            method:
              type: string
              example: rest_streaming_insert
            session_id:
              type: string
              example: 2aa6f829-167f-4f54-aa23-38e6bb557572
            records:
              type: object
        description:
          type: string
          example: Successfully posted 1 record(s) into BigQuery table 'recog_shape_recog' (dataset: 'dyxi_api_pre_prod').

    ErrorResponse:
      type: object
      properties:
        success:
          type: boolean
          example: false
        error:
          type: string
          example: ValidationFailed
        description:
          type: string
          example: Detailed explanation of the error.
```

---

## 2. Filters & Validations Summary

The endpoint applies the following server-side filters and validations before any data touches BigQuery:

| Filter / Validation | Condition | HTTP Status | Response Error Code |
| :--- | :--- | :--- | :--- |
| **HTTP Method** | Must be `POST` | `405 Method Not Allowed` | `MethodNotAllowed` |
| **Authentication** | Bearer JWT must resolve to an active user identity | `401 Unauthorized` | `Unauthorized` |
| **JSON Syntax** | Request body must be valid, well-formed JSON | `400 Bad Request` | `InvalidJsonPayload` |
| **Payload Presence** | Request body must not be empty | `400 Bad Request` | `EmptyPayload` |
| **Table Name Required** | `tableName` (or `table_name`, `table`) must be a non-empty string | `400 Bad Request` | `ValidationFailed` |
| **Table Name Format** | Must match `^[a-zA-Z0-9_]{1,1024}$` (no SQL injection or path traversal) | `400 Bad Request` | `ValidationFailed` |
| **Dataset Format** | If supplied (`dataset` or `dataset_id`), must match `^[a-zA-Z0-9_]{1,1024}$` | `400 Bad Request` | `ValidationFailed` |
| **Data Payload Extraction** | `rawData` (or `data`, `rows`) must be an object, array of objects, or valid JSON string. JSON strings are automatically parsed and extracted. | `400 Bad Request` | `MissingDataPayload` / `InvalidRawDataJson` |
| **Mandatory Age** | Every record MUST contain the field `age`. Must be non-empty, numeric, and non-negative (`age >= 0`). Can be supplied in `rawData` or at root. | `400 Bad Request` | `ValidationFailed` |
| **Mandatory User ID** | Every record MUST contain `user_id` (or `user_uuid`). Cannot be empty. Harmonized across both fields. Can be supplied in `rawData` or at root. | `400 Bad Request` | `ValidationFailed` |
| **Table Existence** | Target table must exist in the specified dataset (`tableExists()`) | `404 Not Found` | `TableNotFound` |
| **Schema Alignment** | Introspects BigQuery table schema via `getTable()` and only extracts declared columns matching target table schema (ignoring undeclared fields to prevent BigQuery streaming rejection). | Handled dynamically | *N/A (cleansed)* |
| **Field Type Casting** | Fields are cast to target BigQuery types (`INTEGER`, `FLOAT`, `BOOLEAN`, `STRING`, `JSON` / serialized string). | Transformed | *N/A* |
| **Auto-UUID** | `session_id` and/or `uuid` generated automatically via Ramsey UUID v4 if omitted and present in schema. | Injected | *N/A* |
| **Auto-Timestamp** | `start_time` and/or `created_at` set to current UTC ISO timestamp (`Y-m-d\TH:i:s\Z`) if omitted and present in schema. | Injected | *N/A* |

---

## 3. Example cURL Invocations

### 3.1 Single Record Ingestion (Fields Embedded in `rawData`)
```bash
curl -X POST https://api.dyxi.site/api/game/game/post-bigquery \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -d '{
    "tableName": "recog_shape_recog",
    "dataset": "dyxi_api_pre_prod",
    "rawData": {
      "age": 5,
      "user_id": "usr_7f8d9b1a-3c4e-4e6f-8a12-9c2b4d6e8f10",
      "game_id": "game_shape_sorter_01",
      "problem_solving_index": 88.5,
      "creative_index": 72.0,
      "total_correct": 9,
      "total_failed": 1
    }
  }'
```

### 3.2 Batch Ingestion (Array of Record Objects)
```bash
curl -X POST https://api.dyxi.site/api/game/game/post-bigquery \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -d '{
    "tableName": "recog_shape_recog",
    "dataset": "dyxi_api_pre_prod",
    "rawData": [
      {
        "age": 4.5,
        "user_id": "usr_1001",
        "game_id": "game_shape_sorter_01",
        "total_correct": 10,
        "total_failed": 0
      },
      {
        "age": 5.0,
        "user_id": "usr_1002",
        "game_id": "game_shape_sorter_01",
        "total_correct": 8,
        "total_failed": 2
      }
    ]
  }'
```

### 3.3 Raw JSON String Ingestion (with Root `age` and `user_id`)
```bash
curl -X POST https://api.dyxi.site/api/game/game/post-bigquery \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -d '{
    "tableName": "recog_shape_recog",
    "age": 5,
    "user_id": "usr_7f8d9b1a-3c4e-4e6f-8a12-9c2b4d6e8f10",
    "rawData": "{\"game_id\":\"game_shape_sorter_01\",\"problem_solving_index\":88.5,\"creative_index\":72.0,\"total_correct\":9,\"total_failed\":1}"
  }'
```

### 3.4 Dynamic Table Ingestion (`game_session_telemetry`)
```bash
curl -X POST https://api.dyxi.site/api/game/game/post-bigquery \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -d '{
    "tableName": "game_session_telemetry",
    "rawData": {
      "age": 6.5,
      "user_id": "usr_45678",
      "game_id": "dyslexia_phonics_01",
      "game_type": "dyslexia_assessment",
      "score": 94.2,
      "duration": 180.5,
      "events": [
        {"action": "tap_letter", "letter": "b", "latency_ms": 320},
        {"action": "tap_letter", "letter": "d", "latency_ms": 410}
      ]
    }
  }'
```

---

## 4. End-to-End Execution Flow: Payload Consumption & Service Processing

```
+---------------------------------------------------------------------------------------+
| 1. CONTROLLER LAYER: GameController::postBigqueryAction()                             |
|                                                                                       |
|   1. HTTP & Auth check: POST method, valid Bearer JWT identity                        |
|   2. Extract 'tableName' & optional 'dataset'                                         |
|   3. Ingest & extract 'rawData':                                                      |
|      - Parses JSON string or nested JSON string items via json_decode()               |
|      - Converts to associative record array(s)                                        |
|   4. Accompaniment & Propagation:                                                     |
|      - If 'age' or 'user_id' provided at root, propagates into each rawData record    |
|   5. Strict Validation:                                                               |
|      - Asserts 'age' is present, non-empty, numeric, and >= 0                         |
|      - Asserts 'user_id' is present and non-empty string                              |
|   6. Delegates to GameService::postBigQueryData($tableName, $rawData, ...)            |
+---------------------------------------------------------------------------------------+
                                           |
                                           v
+---------------------------------------------------------------------------------------+
| 2. SERVICE LAYER: GameService / GameBigQueryService::postBigQueryData()              |
|                                                                                       |
|   1. Table & dataset format sanitization via regex                                    |
|   2. Ensures table exists: BigQueryService::ensureTableExists($tableName)             |
|   3. Introspects table schema: BigQueryService::getTable($tableName)                  |
|      - Maps schema fields and types (INTEGER, FLOAT, STRING, JSON, BOOL, etc.)        |
|      - Merges with known table schema registry fallbacks if remote metadata offline   |
|   4. Dynamic Field Extraction & Transformation:                                       |
|      - Extracts ONLY the fields that exist in the target table's BigQuery schema      |
|      - Filters out undeclared columns (preventing streaming insert schema errors)     |
|      - Type-casts integers, floats, booleans, and JSON-serializes objects/arrays      |
|      - Auto-injects UUID v4 ('session_id', 'uuid') if declared and missing            |
|      - Auto-injects ISO-8601 UTC timestamp ('start_time', 'created_at') if missing   |
|      - Harmonizes 'user_id' & 'user_uuid' according to table schema expectations      |
+---------------------------------------------------------------------------------------+
                                           |
                                           v
+---------------------------------------------------------------------------------------+
| 3. INGESTION ENGINE: BigQueryService::create()                                        |
|                                                                                       |
|   1. Checks available client:                                                         |
|      - Google Cloud BigQueryClient SDK: executes $table->insertRows()                 |
|      - Native OAuth2 REST API: executes POST tabledata.insertAll                      |
|      - SQL DML fallback: executes parameterized INSERT INTO statement                 |
|   2. Confirms successful insertion count                                              |
|   3. Returns structured metadata array                                                |
+---------------------------------------------------------------------------------------+
                                           |
                                           v
+---------------------------------------------------------------------------------------+
| 4. CLIENT RESPONSE: HTTP 201 Created                                                  |
|                                                                                       |
|   {                                                                                   |
|     "success": true,                                                                  |
|     "data": {                                                                         |
|       "table": "recog_shape_recog",                                                   |
|       "dataset": "dyxi_api_pre_prod",                                                 |
|       "inserted_count": 1,                                                            |
|       "method": "streaming_insert",                                                   |
|       "session_id": "2aa6f829-167f-4f54-aa23-38e6bb557572",                           |
|       "records": { ... }                                                              |
|     },                                                                                |
|     "description": "Successfully posted 1 record(s) into BigQuery table..."           |
|   }                                                                                   |
+---------------------------------------------------------------------------------------+
```

