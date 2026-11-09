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
      summary: Stream Game Telemetry into Google BigQuery
      description: >
        Validates and streams game assessment telemetry, session performance, and event data
        directly into Google BigQuery. Requires table name and data payload. Dataset defaults
        to 'dyxi_api_pre_prod' if omitted. Filters payload attributes against the BigQuery table
        schema, auto-populates session UUID and timestamps, and executes high-performance streaming insertion.
      operationId: postGameBigquery
      security:
        - bearerAuth: []
      requestBody:
        required: true
        description: BigQuery Game Telemetry ingestion payload containing table, optional dataset, and record data.
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/BigQueryGamePostRequest'
            examples:
              singleSession:
                summary: Single Game Assessment Session
                value:
                  table_name: recog_shape_recog
                  dataset_id: dyxi_api_pre_prod
                  data:
                    age: 5
                    user_id: usr_7f8d9b1a-3c4e-4e6f-8a12-9c2b4d6e8f10
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
                    start_time: "2026-11-09T14:30:00Z"
                    input_events:
                      - timestamp: 1714567890123
                        type: touch_down
                        x: 100.5
                        y: 200.0
                    match_events:
                      - target: circle
                        selected: circle
                        correct: true
                        reaction_time_ms: 1250
              batchSessions:
                summary: Batch Multiple Assessment Sessions
                value:
                  table_name: recog_shape_recog
                  dataset_id: dyxi_api_pre_prod
                  data:
                    - age: 4.5
                      user_uuid: usr_uuid_1001
                      game_id: game_shape_sorter_01
                      total_correct: 10
                      total_failed: 0
                    - age: 5.0
                      user_id: usr_1002
                      game_id: game_shape_sorter_01
                      total_correct: 8
                      total_failed: 2

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
    BigQueryGamePostRequest:
      type: object
      required:
        - table_name
        - data
      properties:
        table_name:
          type: string
          pattern: '^[a-zA-Z0-9_]{1,1024}$'
          example: recog_shape_recog
          description: Name of the target BigQuery table.
        dataset_id:
          type: string
          pattern: '^[a-zA-Z0-9_]{1,1024}$'
          example: dyxi_api_pre_prod
          description: Optional BigQuery dataset ID. Defaults to 'dyxi_api_pre_prod' if omitted.
        data:
          oneOf:
            - $ref: '#/components/schemas/GameTelemetryRecord'
            - type: array
              items:
                $ref: '#/components/schemas/GameTelemetryRecord'
          description: Single game telemetry record object or an array of records for batch insertion.

    GameTelemetryRecord:
      type: object
      required:
        - age
      properties:
        age:
          type: number
          example: 5
          description: "[REQUIRED] Age of the user/ward. Cannot be empty."
        user_id:
          type: string
          example: usr_7f8d9b1a-3c4e-4e6f-8a12-9c2b4d6e8f10
          description: "[REQUIRED if user_uuid is omitted] Unique user/ward identifier. Cannot be empty."
        user_uuid:
          type: string
          example: 7f8d9b1a-3c4e-4e6f-8a12-9c2b4d6e8f10
          description: "[REQUIRED if user_id is omitted] Unique user/ward UUID. Cannot be empty."
        session_id:
          type: string
          format: uuid
          example: 2aa6f829-167f-4f54-aa23-38e6bb557572
          description: UUID for the assessment session. Auto-generated if omitted.
        game_id:
          type: string
          example: game_shape_sorter_01
          description: Unique game identifier.
        game_type:
          type: string
          example: toddler_assessment
        game_category:
          type: string
          example: shape_recognition
        game_program:
          type: string
          example: early_intervention_v1
        problem_solving_index:
          type: number
          format: float
          example: 88.5
          minimum: 0.0
          maximum: 100.0
        creative_index:
          type: number
          format: float
          example: 72.0
          minimum: 0.0
          maximum: 100.0
        average_time_correct:
          type: number
          format: float
          example: 2.15
        average_time_failed:
          type: number
          format: float
          example: 3.80
        total_game_time:
          type: number
          format: float
          example: 42.5
        total_correct:
          type: integer
          example: 9
        total_failed:
          type: integer
          example: 1
        start_time:
          type: string
          format: date-time
          example: "2026-11-09T14:30:00Z"
          description: ISO-8601 UTC timestamp. Defaults to UTC now if omitted.
        input_events:
          type: array
          description: Touch gesture events array or serialized JSON string.
          items:
            type: object
        match_events:
          type: array
          description: Shape match attempts array or serialized JSON string.
          items:
            type: object

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

The endpoint applies the following server-side filters before any data touches BigQuery:

| Filter / Validation | Condition | HTTP Status | Response Error Code |
| :--- | :--- | :--- | :--- |
| **HTTP Method** | Must be `POST` | `405 Method Not Allowed` | `MethodNotAllowed` |
| **Authentication** | Bearer JWT must resolve to an active user identity | `401 Unauthorized` | `Unauthorized` |
| **JSON Syntax** | Body must be valid, well-formed JSON | `400 Bad Request` | `InvalidJsonPayload` |
| **Payload Presence** | Request body must not be empty | `400 Bad Request` | `EmptyPayload` |
| **Table Name Required** | `table_name` or `table` must be a non-empty string | `400 Bad Request` | `ValidationFailed` |
| **Table Name Format** | Must match `^[a-zA-Z0-9_]{1,1024}$` (no SQL injection or path traversal) | `400 Bad Request` | `InvalidTableName` |
| **Dataset Format** | If supplied, must match `^[a-zA-Z0-9_]{1,1024}$` | `400 Bad Request` | `InvalidDatasetId` |
| **Data Payload** | `data` must be an object or array of objects | `400 Bad Request` | `MissingDataPayload` |
| **Mandatory Age** | Every record MUST contain the field `age` and it cannot be empty | `400 Bad Request` | `ValidationFailed` |
| **Mandatory User ID/UUID** | Every record MUST contain either `user_id` or `user_uuid` and cannot be empty | `400 Bad Request` | `ValidationFailed` |
| **Table Existence** | Table must exist in the target dataset (`tableExists()`) | `404 Not Found` | `TableNotFound` |
| **Table Switch Routing** | Dispatched via `GameService` switch condition (specific logic for `recog_shape_recog`, generic for any other table) | Handled dynamically | *N/A* |
| **Schema Alignment** | Automatically drops undeclared columns matching `getTable()` schema | Filtered dynamically | *N/A (cleansed)* |
| **Auto-UUID** | `session_id` generated automatically if omitted | Injected | *N/A* |
| **Auto-Timestamp** | `start_time` set to current UTC ISO timestamp if omitted | Injected | *N/A* |
| **JSON Serialization** | Array/object columns (like `input_events`, `match_events`) serialized to JSON strings | Serialized | *N/A* |

---

## 3. Example cURL Invocations

### 3.1 Single Record Ingestion
```bash
curl -X POST https://api.dyxi.site/api/game/game/post-bigquery \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -d '{
    "table_name": "recog_shape_recog",
    "dataset_id": "dyxi_api_pre_prod",
    "data": {
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

### 3.2 Batch Ingestion
```bash
curl -X POST https://api.dyxi.site/api/game/game/post-bigquery \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -d '{
    "table_name": "recog_shape_recog",
    "data": [
      {
        "game_id": "game_shape_sorter_01",
        "user_id": "usr_1001",
        "total_correct": 10,
        "total_failed": 0
      },
      {
        "game_id": "game_shape_sorter_01",
        "user_id": "usr_1002",
        "total_correct": 8,
        "total_failed": 2
      }
    ]
  }'
```

