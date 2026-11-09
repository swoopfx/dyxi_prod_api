# BigQueryService Real-World Scenario & Usage Guide

This guide provides comprehensive, real-world examples of how to integrate and use [`BigQueryService`](file:///Applications/MAMP/htdocs/dyxi_prod_api/module/General/src/Service/BigQueryService.php) within the DYXI platform.

The examples below are centered around the **Shape Recognition Cognitive Assessment Game** (`recog_shape_recog`), which is an active table within our Google BigQuery dataset (`dyxi_api_pre_prod`).

---

## 1. Scenario Architecture Overview

When a user or toddler completes a game assessment session in the mobile or web client:
1. **Game Completion Event**: The client posts performance telemetry (time-to-complete, problem solving indices, input events, shape match events).
2. **Ingestion Layer**: A controller or service receives the payload and uses [`BigQueryService::create()`](file:///Applications/MAMP/htdocs/dyxi_prod_api/module/General/src/Service/BigQueryService.php) to stream the record directly into BigQuery.
3. **Analytics & Aggregation**: The dashboard and clinician reporting tools query BigQuery via [`BigQueryService::executeQuery()`](file:///Applications/MAMP/htdocs/dyxi_prod_api/module/General/src/Service/BigQueryService.php) using parameterized filters (by date range, game program, user cohort, or cognitive performance).
4. **Assessment Adjustment**: If a clinician updates an evaluation index or flags a session, [`BigQueryService::update()`](file:///Applications/MAMP/htdocs/dyxi_prod_api/module/General/src/Service/BigQueryService.php) updates the record in BigQuery.
5. **GDPR / Data Retention**: Test sessions or requested deletions are removed using [`BigQueryService::delete()`](file:///Applications/MAMP/htdocs/dyxi_prod_api/module/General/src/Service/BigQueryService.php).

---

## 2. Table Schema & Data Structures

### Table: `recog_shape_recog`
* **Project ID**: `dyxi-502320`
* **Dataset ID**: `dyxi_api_pre_prod`
* **Region / Location**: `europe-west4`

| Column Name | BigQuery Type | Nullable / Mode | Description |
| :--- | :--- | :--- | :--- |
| `session_id` | `STRING` | REQUIRED | UUID string identifying the assessment run |
| `game_id` | `STRING` | REQUIRED | Unique game identifier (e.g. `uuid-game-001`) |
| `game_type` | `STRING` | NULLABLE | Category (e.g. `toddler_assessment`, `dyslexia`, `adhd`) |
| `game_category` | `STRING` | NULLABLE | Specific subcategory (e.g. `shape_recognition`) |
| `game_program` | `STRING` | NULLABLE | Assessment program name (e.g. `early_intervention_v1`) |
| `user_id` | `STRING` | REQUIRED | Unique user / ward identifier |
| `problem_solving_index`| `FLOAT` | NULLABLE | Normalized cognitive index score (0.0 to 100.0) |
| `creative_index` | `FLOAT` | NULLABLE | Normalized creativity/exploration metric (0.0 to 100.0) |
| `average_time_correct` | `FLOAT` | NULLABLE | Mean response time in seconds for correct attempts |
| `average_time_failed` | `FLOAT` | NULLABLE | Mean response time in seconds for incorrect attempts |
| `total_game_time` | `FLOAT` | NULLABLE | Total duration of the session in seconds |
| `total_correct` | `INTEGER` | NULLABLE | Count of correct matches |
| `total_failed` | `INTEGER` | NULLABLE | Count of failed attempts |
| `start_time` | `STRING` | NULLABLE | ISO-8601 UTC timestamp string (`Y-m-d\TH:i:s\Z`) |
| `input_events` | `JSON` | NULLABLE | Detailed touch, drag, and tap telemetry |
| `match_events` | `JSON` | NULLABLE | Shape pair attempts, target shape, and match latency |

---

## 3. Dependency Injection & Service Setup

To use `BigQueryService` inside any Controller, Service, or Action Handler, inject it through the standard Laminas Service Manager:

### Service Manager Factory Example
```php
<?php

declare(strict_types=1);

namespace Game\Service\Factory;

use Game\Service\GameTelemetryService;
use General\Service\BigQueryService;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class GameTelemetryServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): GameTelemetryService
    {
        /** @var BigQueryService $bigQueryService */
        $bigQueryService = $container->get('bigquery_service'); // or BigQueryService::class

        return new GameTelemetryService($bigQueryService);
    }
}
```

---

## 4. Code Examples by Use Case

### 4.1 Verifying Table & Dataset Existence

Before performing large operations or during startup health checks, verify table availability:

```php
use General\Service\BigQueryService;

/** @var BigQueryService $bq */
$bq = $container->get('bigquery_service');

// 1. Check if the target table exists
if ($bq->tableExists('recog_shape_recog')) {
    echo "Table 'recog_shape_recog' is active and ready.\n";
}

// 2. Ensure table exists (throws \Exception if missing)
$bq->ensureTableExists('recog_shape_recog');

// 3. Inspect table metadata & columns
$metadata = $bq->getTable('recog_shape_recog');
print_r($metadata['schema']['fields']);
```

---

### 4.2 Ingesting a Single Game Session (Streaming Insert)

When a game session ends, assemble the associative payload and stream it directly to BigQuery:

```php
use General\Service\BigQueryService;
use Ramsey\Uuid\Uuid;

/** @var BigQueryService $bq */
$bq = $container->get('bigquery_service');

$sessionData = [
    'session_id'            => Uuid::uuid4()->toString(),
    'game_id'               => 'game_shape_sorter_01',
    'game_type'             => 'toddler_assessment',
    'game_category'         => 'shape_recognition',
    'game_program'          => 'early_intervention_v1',
    'user_id'               => 'usr_7f8d9b1a-3c4e-4e6f-8a12-9c2b4d6e8f10',
    'problem_solving_index' => 87.50,
    'creative_index'        => 65.20,
    'average_time_correct'  => 2.34,
    'average_time_failed'   => 4.12,
    'total_game_time'       => 45.80,
    'total_correct'         => 8,
    'total_failed'          => 2,
    'start_time'            => gmdate('Y-m-d\TH:i:s\Z'),
    // JSON columns: store as valid JSON string or associative array
    'input_events'          => json_encode([
        ['timestamp' => 1714567890123, 'type' => 'touch_down', 'x' => 120.5, 'y' => 340.2],
        ['timestamp' => 1714567890456, 'type' => 'touch_up',   'x' => 180.0, 'y' => 345.1],
    ]),
    'match_events'          => json_encode([
        ['target' => 'circle',   'selected' => 'circle',   'correct' => true,  'reaction_time_ms' => 1250],
        ['target' => 'triangle', 'selected' => 'square',   'correct' => false, 'reaction_time_ms' => 2800],
        ['target' => 'triangle', 'selected' => 'triangle', 'correct' => true,  'reaction_time_ms' => 1420],
    ]),
];

// Perform streaming insert
$result = $bq->create('recog_shape_recog', $sessionData);

if ($result['success']) {
    echo sprintf("Successfully ingested session %s via %s.\n", $sessionData['session_id'], $result['method']);
}
```

---

### 4.3 Batch / Bulk Ingestion of Multiple Game Sessions

For offline sync or background queue ingestion (e.g. Redis message worker flushing batches):

```php
$batchRows = [
    [
        'session_id'            => Uuid::uuid4()->toString(),
        'game_id'               => 'game_shape_sorter_01',
        'game_type'             => 'toddler_assessment',
        'game_category'         => 'shape_recognition',
        'game_program'          => 'early_intervention_v1',
        'user_id'               => 'usr_1001',
        'problem_solving_index' => 92.0,
        'creative_index'        => 74.0,
        'average_time_correct'  => 1.95,
        'average_time_failed'   => 3.50,
        'total_game_time'       => 38.2,
        'total_correct'         => 10,
        'total_failed'          => 1,
        'start_time'            => gmdate('Y-m-d\TH:i:s\Z', time() - 3600),
        'input_events'          => json_encode([]),
        'match_events'          => json_encode([]),
    ],
    [
        'session_id'            => Uuid::uuid4()->toString(),
        'game_id'               => 'game_shape_sorter_01',
        'game_type'             => 'toddler_assessment',
        'game_category'         => 'shape_recognition',
        'game_program'          => 'early_intervention_v1',
        'user_id'               => 'usr_1002',
        'problem_solving_index' => 78.4,
        'creative_index'        => 58.0,
        'average_time_correct'  => 3.10,
        'average_time_failed'   => 5.20,
        'total_game_time'       => 52.0,
        'total_correct'         => 7,
        'total_failed'          => 3,
        'start_time'            => gmdate('Y-m-d\TH:i:s\Z'),
        'input_events'          => json_encode([]),
        'match_events'          => json_encode([]),
    ],
];

// Passing array of rows automatically triggers batch streaming insert
$batchResult = $bq->create('recog_shape_recog', $batchRows);

echo "Total records inserted: " . $batchResult['inserted_count'] . "\n";
```

---

### 4.4 Parameterized Analytics Queries with Typed Filters

Always use parameter placeholders (`@param_name`) with the `$params` binding array to prevent SQL injection and allow query plan reuse.

#### Example A: User Performance History
```php
$sql = "
    SELECT
        session_id,
        game_program,
        problem_solving_index,
        creative_index,
        total_correct,
        total_failed,
        total_game_time,
        start_time
    FROM `dyxi-502320.dyxi_api_pre_prod.recog_shape_recog`
    WHERE user_id = @userId
      AND game_category = @category
    ORDER BY start_time DESC
    LIMIT @limit
";

$params = [
    'userId'   => 'usr_7f8d9b1a-3c4e-4e6f-8a12-9c2b4d6e8f10',
    'category' => 'shape_recognition',
    'limit'    => 20,
];

$sessions = $bq->executeQuery($sql, $params, 'recog_shape_recog');

foreach ($sessions as $session) {
    echo sprintf(
        "Session %s: Score %.1f | Correct: %d | Time: %.1fs\n",
        $session['session_id'],
        (float) $session['problem_solving_index'],
        (int) $session['total_correct'],
        (float) $session['total_game_time']
    );
}
```

#### Example B: Program Cohort Statistics
```php
$sql = "
    SELECT
        game_program,
        COUNT(DISTINCT user_id) AS total_users,
        COUNT(session_id)       AS total_sessions,
        ROUND(AVG(problem_solving_index), 2) AS avg_problem_solving,
        ROUND(AVG(creative_index), 2)        AS avg_creative_index,
        ROUND(AVG(average_time_correct), 2)  AS avg_reaction_time_sec,
        SUM(total_correct)      AS aggregate_correct,
        SUM(total_failed)       AS aggregate_failed
    FROM `dyxi-502320.dyxi_api_pre_prod.recog_shape_recog`
    WHERE game_type = @gameType
      AND problem_solving_index >= @minScore
    GROUP BY game_program
    HAVING total_sessions >= @minSessions
    ORDER BY avg_problem_solving DESC
";

$programStats = $bq->executeQuery($sql, [
    'gameType'    => 'toddler_assessment',
    'minScore'    => 50.0,
    'minSessions' => 5,
]);

print_r($programStats);
```

---

### 4.5 Updating Assessment Scores or Evaluator Notes

When a clinician recalculates scores or updates program classifications:

```php
$targetSessionId = 'c0a8012e-8d9b-4e12-b123-9c8a7b6d5e4f';

$updatedValues = [
    'problem_solving_index' => 91.50,
    'creative_index'        => 70.00,
    'game_program'          => 'advanced_shape_curriculum_v2',
];

$whereClause = "session_id = @target_session_id AND user_id = @target_user_id";

$whereParams = [
    'target_session_id' => $targetSessionId,
    'target_user_id'    => 'usr_7f8d9b1a-3c4e-4e6f-8a12-9c2b4d6e8f10',
];

// Execute DML UPDATE
$updateResult = $bq->update(
    'recog_shape_recog',
    $updatedValues,
    $whereClause,
    $whereParams
);

if ($updateResult['success']) {
    echo "Successfully updated session {$targetSessionId}.\n";
}
```

---

### 4.6 Deleting Test or Incomplete Sessions (GDPR / Maintenance)

Remove test records or invalid sessions cleanly using a parameterized `WHERE` clause:

```php
// Delete automated QA test records
$whereClause = "user_id = @testUserId AND start_time < @cutoffDate";

$whereParams = [
    'testUserId' => 'qa_automation_test_user',
    'cutoffDate' => '2026-01-01T00:00:00Z',
];

$deleteResult = $bq->delete('recog_shape_recog', $whereClause, $whereParams);

if ($deleteResult['success']) {
    echo "Purged test data from recog_shape_recog.\n";
}
```

---

## 5. Integration into a Laminas Controller

Here is a full pattern showing how to use `BigQueryService` inside an API Controller:

```php
<?php

declare(strict_types=1);

namespace Game\Controller;

use General\Service\BigQueryService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Ramsey\Uuid\Uuid;

class AssessmentTelemetryController extends AbstractActionController
{
    private BigQueryService $bigQueryService;

    public function __construct(BigQueryService $bigQueryService)
    {
        $this->bigQueryService = $bigQueryService;
    }

    /**
     * POST /api/game/telemetry/finish
     * Receives game completion payload and logs to BigQuery.
     */
    public function finishSessionAction(): JsonModel
    {
        $request = $this->getRequest();
        if (!$request->isPost()) {
            $this->getResponse()->setStatusCode(405);
            return new JsonModel(['error' => 'Method not allowed. POST required.']);
        }

        $rawBody = (string) $request->getContent();
        $payload = json_decode($rawBody, true) ?? [];

        if (empty($payload['user_id']) || empty($payload['game_id'])) {
            $this->getResponse()->setStatusCode(400);
            return new JsonModel(['error' => 'Missing required fields: user_id and game_id.']);
        }

        $sessionId = $payload['session_id'] ?? Uuid::uuid4()->toString();

        $bqRecord = [
            'session_id'            => $sessionId,
            'game_id'               => (string) $payload['game_id'],
            'game_type'             => (string) ($payload['game_type'] ?? 'assessment'),
            'game_category'         => (string) ($payload['game_category'] ?? 'shape_recognition'),
            'game_program'          => (string) ($payload['game_program'] ?? 'default_program'),
            'user_id'               => (string) $payload['user_id'],
            'problem_solving_index' => (float) ($payload['problem_solving_index'] ?? 0.0),
            'creative_index'        => (float) ($payload['creative_index'] ?? 0.0),
            'average_time_correct'  => (float) ($payload['average_time_correct'] ?? 0.0),
            'average_time_failed'   => (float) ($payload['average_time_failed'] ?? 0.0),
            'total_game_time'       => (float) ($payload['total_game_time'] ?? 0.0),
            'total_correct'         => (int) ($payload['total_correct'] ?? 0),
            'total_failed'          => (int) ($payload['total_failed'] ?? 0),
            'start_time'            => $payload['start_time'] ?? gmdate('Y-m-d\TH:i:s\Z'),
            'input_events'          => is_array($payload['input_events'] ?? null)
                ? json_encode($payload['input_events'])
                : (string) ($payload['input_events'] ?? '{}'),
            'match_events'          => is_array($payload['match_events'] ?? null)
                ? json_encode($payload['match_events'])
                : (string) ($payload['match_events'] ?? '{}'),
        ];

        try {
            $response = $this->bigQueryService->create('recog_shape_recog', $bqRecord);

            return new JsonModel([
                'success'    => true,
                'session_id' => $sessionId,
                'method'     => $response['method'],
                'timestamp'  => gmdate('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            $this->getResponse()->setStatusCode(500);
            return new JsonModel([
                'success' => false,
                'error'   => 'Failed to log telemetry: ' . $e->getMessage(),
            ]);
        }
    }
}
```

---

## 6. Best Practices & Key Considerations

1. **Named Bindings**:
   Always use `@paramName` in SQL queries and supply associative keys in the `$params` array. This ensures proper data type casting (INT64, FLOAT64, BOOL, STRING) and prevents SQL injection.
2. **Streaming Insert Latency**:
   BigQuery streaming inserts (`tabledata.insertAll`) are available for real-time querying within seconds of ingestion. Note that BigQuery imposes a temporary metadata buffer lock on newly streamed rows before allowing `UPDATE` / `DELETE` DML mutations on them (typically 30–90 minutes).
3. **Location Awareness**:
   Our dataset is located in `europe-west4`. The `BigQueryService` automatically configures `europe-west4` for all query jobs to prevent multi-region job routing errors.
4. **Credential Security**:
   The service account JSON file (`dyxi-502320-ec4ecc7ca00d.json`) is stored in `data/` and `data/keys/`. Both paths are excluded in `.gitignore` to prevent committing secrets to source control.

