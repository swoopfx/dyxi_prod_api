# Dyxi API Documentation: Consultant and Game Modules

This document provides detailed API specifications for the **Consultant** and **Game** modules within the Dyxi API.

All endpoints are built using Laminas MVC and Doctrine ORM, returning JSON payloads.

---

## Table of Contents

- [Global Conventions](#global-conventions)
  - [Base URL](#base-url)
  - [Authentication](#authentication)
  - [Response Envelopes](#response-envelopes)
- [Consultant Module](#consultant-module)
  - [Register Consultant](#register-consultant)
  - [List Consultants](#list-consultants)
  - [View Consultant](#view-consultant)
- [Game Module](#game-module)
  - [Games](#games)
    - [Register Game](#register-game)
    - [List Games](#list-games)
    - [View Game Details](#view-game-details)
    - [Update Game](#update-game)
    - [Delete Game](#delete-game)
  - [Game Types](#game-types)
    - [Register Game Type](#register-game-type)
    - [List Game Types](#list-game-types)
    - [View Game Type Details](#view-game-type-details)
    - [Update Game Type](#update-game-type)
    - [Delete Game Type](#delete-game-type)
  - [Curriculums](#curriculums)
    - [Register Curriculum](#register-curriculum)
    - [List Curriculums](#list-curriculums)
    - [View Curriculum Details](#view-curriculum-details)
    - [Update Curriculum](#update-curriculum)
    - [Delete Curriculum](#delete-curriculum)
  - [Games Collections](#games-collections)
    - [Register Games Collection](#register-games-collection)
    - [List Games Collections](#list-games-collections)
    - [View Games Collection Details](#view-games-collection-details)
    - [Update Games Collection](#update-games-collection)
    - [Delete Games Collection](#delete-games-collection)

---

## Global Conventions

### Base URL

During development, the API is available locally at:
```
http://localhost:8080
```

### Authentication

All endpoints described in this document require Bearer Token Authentication. The token must be passed in the `Authorization` request header:

```http
Authorization: Bearer <JWT_access_token>
```

> [!WARNING]
> Requests without a valid Bearer token, or where the token has expired, will return a `401 Unauthorized` status.

### Response Envelopes

The API uses standard response envelopes.

#### Success Envelope
```json
{
  "success": true,
  "data": { ... } | [ ... ],
  "description": "Success message (optional)"
}
```

#### Error Envelope
```json
{
  "success": false,
  "error": "ErrorCode",
  "description": "A detailed description of the error."
}
```

#### Common HTTP Status Codes
* **`200 OK`**: Request succeeded, returns requested resource or status.
* **`201 Created`**: New resource created successfully.
* **`400 Bad Request`**: Validation failed or missing parameters.
* **`401 Unauthorized`**: Authentication missing or invalid.
* **`405 Method Not Allowed`**: Request method (e.g. GET/POST) is incorrect.

---

## Consultant Module

Routes mapped to the Consultant module follow the segment pattern `/api/consultant[/:action[/:id]]`.

### Register Consultant

Creates a new consultant profile.

* **URL**: `/api/consultant/register`
* **Method**: `POST`
* **Headers**:
  * `Content-Type: application/json`
  * `Authorization: Bearer <token>`
* **Request Body**:
  | Field | Type | Required | Description |
  | :--- | :--- | :--- | :--- |
  | `fullname` | `string` | **Yes** | Full name of the consultant. |
  | `email` | `string` | **Yes** | Email address. Must be a valid email format and unique. |
  | `phone` | `string` | No | Contact phone number. |
  | `specialization` | `string` | No | Field of expertise. |
  | `bio` | `string` | No | Short biographical details. |
  | `status` | `string` | No | State/status of the profile. |
  | `user_id` | `int`\|`string` | No | ID or UUID of the associated user account. |
  | `uuid` | `string` | No | Custom UUID. Auto-generated if not provided. |

* **Example Request**:
  ```json
  {
    "fullname": "Jane Doe",
    "email": "jane.doe@example.com",
    "phone": "+15550199",
    "specialization": "Child Psychology",
    "bio": "Expert in early childhood development assessment.",
    "user_id": "resu64aed5e840d2f"
  }
  ```

* **Example Response (201 Created)**:
  ```json
  {
    "success": true,
    "data": {
      "id": 1,
      "uuid": "43e2e0fb-9d41-4770-9856-11fdb853d9e0",
      "fullname": "Jane Doe",
      "email": "jane.doe@example.com",
      "phone": "+15550199",
      "specialization": "Child Psychology",
      "bio": "Expert in early childhood development assessment.",
      "status": "active",
      "user": {
        "id": 4,
        "fullname": "Jane Doe",
        "email": "jane.doe@example.com"
      },
      "created_on": "2026-08-30 20:15:00",
      "updated_on": "2026-08-30 20:15:00"
    },
    "description": "Successfully registered Consultant profile."
  }
  ```

---

### List Consultants

Retrieves a list of all consultant profiles.

* **URL**: `/api/consultant` OR `/api/consultant/list`
* **Method**: `GET`
* **Headers**:
  * `Authorization: Bearer <token>`

* **Example Response (200 OK)**:
  ```json
  {
    "success": true,
    "data": [
      {
        "id": 1,
        "uuid": "43e2e0fb-9d41-4770-9856-11fdb853d9e0",
        "fullname": "Jane Doe",
        "email": "jane.doe@example.com",
        "phone": "+15550199",
        "specialization": "Child Psychology",
        "bio": "Expert in early childhood development assessment.",
        "status": "active",
        "user": {
          "id": 4,
          "fullname": "Jane Doe",
          "email": "jane.doe@example.com"
        },
        "created_on": "2026-08-30 20:15:00",
        "updated_on": "2026-08-30 20:15:00"
      }
    ]
  }
  ```

---

### View Consultant

Retrieves detailed information for a single consultant by their database ID, UUID, or email.

* **URL**: `/api/consultant/view/:id` (where `:id` is the ID, UUID, or email)
* **Alternative Query Parameters**: `/api/consultant/view?id=<id>` OR `/api/consultant/view?uuid=<uuid>`
* **Method**: `GET`
* **Headers**:
  * `Authorization: Bearer <token>`

* **Example Response (200 OK)**:
  ```json
  {
    "success": true,
    "data": {
      "id": 1,
      "uuid": "43e2e0fb-9d41-4770-9856-11fdb853d9e0",
      "fullname": "Jane Doe",
      "email": "jane.doe@example.com",
      "phone": "+15550199",
      "specialization": "Child Psychology",
      "bio": "Expert in early childhood development assessment.",
      "status": "active",
      "user": {
        "id": 4,
        "fullname": "Jane Doe",
        "email": "jane.doe@example.com"
      },
      "created_on": "2026-08-30 20:15:00",
      "updated_on": "2026-08-30 20:15:00"
    }
  }
  ```

---

## Game Module

The Game module manages four sub-resources: **Games**, **Game Types**, **Curriculums**, and **Games Collections**.

---

### Games

Routes follow the segment pattern `/api/game/game[/:action[/:id]]`.

#### Register Game

Creates a new game.

* **URL**: `/api/game/game/register`
* **Method**: `POST`
* **Headers**:
  * `Content-Type: application/json`
  * `Authorization: Bearer <token>`
* **Request Body**:
  | Field | Type | Required | Description |
  | :--- | :--- | :--- | :--- |
  | `title` | `string` | **Yes** | Title of the game. |
  | `game_type_id` | `int`\|`string` | **Yes** | ID, UUID, or name of the GameType. |
  | `curriculum_id` | `int`\|`string` | No | ID, UUID, or name of the Curriculum. |
  | `unique_identifier`| `string` | No | Custom unique string. Auto-generated (e.g. `GAME-A1B2C3D4`) if omitted. Must be unique. |
  | `description` | `string` | No | Summary of what the game is. |
  | `uuid` | `string` | No | Custom UUID. Auto-generated if not provided. |

* **Example Request**:
  ```json
  {
    "title": "Math Quest",
    "game_type_id": "Puzzle",
    "curriculum_id": "Primary Arithmetic",
    "description": "An interactive math puzzle game."
  }
  ```

* **Example Response (201 Created)**:
  ```json
  {
    "success": true,
    "data": {
      "id": 1,
      "uuid": "8e3c5a71-d602-4ef8-bc4b-e85d820f4c39",
      "unique_identifier": "GAME-FD491EA3",
      "title": "Math Quest",
      "description": "An interactive math puzzle game.",
      "game_type": {
        "id": 2,
        "name": "Puzzle",
        "uuid": "d4e2831f-0b32-45bb-b3b4-10b2c93049b1"
      },
      "curriculum": {
        "id": 1,
        "name": "Primary Arithmetic",
        "uuid": "82f831f0-0b32-45bb-b3b4-10b2c93049ba"
      },
      "created_on": "2026-08-30 20:20:00",
      "updated_on": "2026-08-30 20:20:00"
    },
    "description": "Successfully registered Game."
  }
  ```

---

#### List Games

Retrieves all registered games.

* **URL**: `/api/game/game` OR `/api/game/game/list`
* **Method**: `GET`
* **Headers**:
  * `Authorization: Bearer <token>`

* **Example Response (200 OK)**:
  ```json
  {
    "success": true,
    "data": [
      {
        "id": 1,
        "uuid": "8e3c5a71-d602-4ef8-bc4b-e85d820f4c39",
        "unique_identifier": "GAME-FD491EA3",
        "title": "Math Quest",
        "description": "An interactive math puzzle game.",
        "game_type": {
          "id": 2,
          "name": "Puzzle",
          "uuid": "d4e2831f-0b32-45bb-b3b4-10b2c93049b1"
        },
        "curriculum": {
          "id": 1,
          "name": "Primary Arithmetic",
          "uuid": "82f831f0-0b32-45bb-b3b4-10b2c93049ba"
        },
        "created_on": "2026-08-30 20:20:00",
        "updated_on": "2026-08-30 20:20:00"
      }
    ]
  }
  ```

---

#### View Game Details

Retrieves details for a game by its database ID, UUID, unique identifier, or title.

* **URL**: `/api/game/game/info/:id`
* **Alternative Query Parameters**: `/api/game/game/info?id=<id>` OR `/api/game/game/info?uuid=<uuid>` OR `/api/game/game/info?unique_identifier=<id>`
* **Method**: `GET`
* **Headers**:
  * `Authorization: Bearer <token>`

* **Example Response (200 OK)**:
  ```json
  {
    "success": true,
    "data": {
      "id": 1,
      "uuid": "8e3c5a71-d602-4ef8-bc4b-e85d820f4c39",
      "unique_identifier": "GAME-FD491EA3",
      "title": "Math Quest",
      "description": "An interactive math puzzle game.",
      "game_type": {
        "id": 2,
        "name": "Puzzle",
        "uuid": "d4e2831f-0b32-45bb-b3b4-10b2c93049b1"
      },
      "curriculum": {
        "id": 1,
        "name": "Primary Arithmetic",
        "uuid": "82f831f0-0b32-45bb-b3b4-10b2c93049ba"
      },
      "created_on": "2026-08-30 20:20:00",
      "updated_on": "2026-08-30 20:20:00"
    }
  }
  ```

---

#### Update Game

Updates an existing game.

* **URL**: `/api/game/game/update/:id`
* **Method**: `PUT`
* **Headers**:
  * `Content-Type: application/json`
  * `Authorization: Bearer <token>`
* **Request Body**:
  | Field | Type | Required | Description |
  | :--- | :--- | :--- | :--- |
  | `title` | `string` | No | New title of the game. |
  | `description` | `string` | No | New description. Can be `null` to clear. |
  | `game_type_id` | `int`\|`string` | No | New GameType identifier. |
  | `curriculum_id` | `int`\|`string` | No | New Curriculum identifier. Can be `null` to disassociate. |

* **Example Request**:
  ```json
  {
    "title": "Math Quest Extended",
    "description": "An interactive math puzzle game with 10 new levels."
  }
  ```

* **Example Response (200 OK)**:
  ```json
  {
    "success": true,
    "data": {
      "id": 1,
      "uuid": "8e3c5a71-d602-4ef8-bc4b-e85d820f4c39",
      "unique_identifier": "GAME-FD491EA3",
      "title": "Math Quest Extended",
      "description": "An interactive math puzzle game with 10 new levels.",
      "game_type": {
        "id": 2,
        "name": "Puzzle",
        "uuid": "d4e2831f-0b32-45bb-b3b4-10b2c93049b1"
      },
      "curriculum": {
        "id": 1,
        "name": "Primary Arithmetic",
        "uuid": "82f831f0-0b32-45bb-b3b4-10b2c93049ba"
      },
      "created_on": "2026-08-30 20:20:00",
      "updated_on": "2026-08-30 20:25:00"
    },
    "description": "Successfully updated Game."
  }
  ```

---

#### Delete Game

Removes a game from the database.

* **URL**: `/api/game/game/delete/:id`
* **Method**: `DELETE`
* **Headers**:
  * `Authorization: Bearer <token>`

* **Example Response (200 OK)**:
  ```json
  {
    "success": true,
    "description": "Successfully deleted Game."
  }
  ```

---

### Game Types

Routes follow the segment pattern `/api/game/game-type[/:action[/:id]]`.

#### Register Game Type

Creates a new type/category of games.

* **URL**: `/api/game/game-type/register`
* **Method**: `POST`
* **Headers**:
  * `Content-Type: application/json`
  * `Authorization: Bearer <token>`
* **Request Body**:
  | Field | Type | Required | Description |
  | :--- | :--- | :--- | :--- |
  | `name` | `string` | **Yes** | Unique name of the game type. |
  | `description` | `string` | No | Description of the category. |
  | `uuid` | `string` | No | Custom UUID. Auto-generated if not provided. |

* **Example Request**:
  ```json
  {
    "name": "Puzzle",
    "description": "Brain-teasing puzzles, spatial logic, and patterns."
  }
  ```

* **Example Response (201 Created)**:
  ```json
  {
    "success": true,
    "data": {
      "id": 2,
      "uuid": "d4e2831f-0b32-45bb-b3b4-10b2c93049b1",
      "name": "Puzzle",
      "description": "Brain-teasing puzzles, spatial logic, and patterns.",
      "created_on": "2026-08-30 20:10:00",
      "updated_on": "2026-08-30 20:10:00"
    },
    "description": "Successfully registered GameType."
  }
  ```

---

#### List Game Types

Retrieves all game types.

* **URL**: `/api/game/game-type` OR `/api/game/game-type/list`
* **Method**: `GET`
* **Headers**:
  * `Authorization: Bearer <token>`

* **Example Response (200 OK)**:
  ```json
  {
    "success": true,
    "data": [
      {
        "id": 2,
        "uuid": "d4e2831f-0b32-45bb-b3b4-10b2c93049b1",
        "name": "Puzzle",
        "description": "Brain-teasing puzzles, spatial logic, and patterns.",
        "created_on": "2026-08-30 20:10:00",
        "updated_on": "2026-08-30 20:10:00"
      }
    ]
  }
  ```

---

#### View Game Type Details

Retrieves details for a game type by its ID, UUID, or name.

* **URL**: `/api/game/game-type/info/:id`
* **Alternative Query Parameters**: `/api/game/game-type/info?id=<id>` OR `/api/game/game-type/info?uuid=<uuid>`
* **Method**: `GET`
* **Headers**:
  * `Authorization: Bearer <token>`

* **Example Response (200 OK)**:
  ```json
  {
    "success": true,
    "data": {
      "id": 2,
      "uuid": "d4e2831f-0b32-45bb-b3b4-10b2c93049b1",
      "name": "Puzzle",
      "description": "Brain-teasing puzzles, spatial logic, and patterns.",
      "created_on": "2026-08-30 20:10:00",
      "updated_on": "2026-08-30 20:10:00"
    }
  }
  ```

---

#### Update Game Type

Updates an existing game type.

* **URL**: `/api/game/game-type/update/:id`
* **Method**: `PUT`
* **Headers**:
  * `Content-Type: application/json`
  * `Authorization: Bearer <token>`
* **Request Body**:
  | Field | Type | Required | Description |
  | :--- | :--- | :--- | :--- |
  | `name` | `string` | No | New name (must be unique). |
  | `description` | `string` | No | New description. Can be `null`. |

* **Example Request**:
  ```json
  {
    "description": "Updated puzzle description including spatial reasoning."
  }
  ```

* **Example Response (200 OK)**:
  ```json
  {
    "success": true,
    "data": {
      "id": 2,
      "uuid": "d4e2831f-0b32-45bb-b3b4-10b2c93049b1",
      "name": "Puzzle",
      "description": "Updated puzzle description including spatial reasoning.",
      "created_on": "2026-08-30 20:10:00",
      "updated_on": "2026-08-30 20:22:00"
    },
    "description": "Successfully updated GameType."
  }
  ```

---

#### Delete Game Type

Removes a game type from the database.

* **URL**: `/api/game/game-type/delete/:id`
* **Method**: `DELETE`
* **Headers**:
  * `Authorization: Bearer <token>`

* **Example Response (200 OK)**:
  ```json
  {
    "success": true,
    "description": "Successfully deleted GameType."
  }
  ```

---

### Curriculums

Routes follow the segment pattern `/api/game/curriculum[/:action[/:id]]`.

All curriculum operations leverage the **`dyxi_curriculum`** Redis cache namespace with a default permanent TTL (`0` = forever / no expiration). Redis cache keys are strictly unique (`curriculum_info_<id>`, `curriculum_info_<uuid>`, `curriculum_info_ward_<ward_uuid>`).

#### Register Curriculum

Creates a new curriculum roadmap and primes Redis cache.

* **URL**: `/api/game/curriculum/register`
* **Method**: `POST`
* **Headers**:
  * `Content-Type: application/json`
  * `Authorization: Bearer <token>`
* **Request Body**:
  | Field | Type | Required | Description |
  | :--- | :--- | :--- | :--- |
  | `name` | `string` | **Yes** | Unique name of the curriculum. |
  | `description` | `string` | No | Description of curriculum goals. |
  | `min_age` | `int` | No | Minimum target age. |
  | `max_age` | `int` | No | Maximum target age. |
  | `ward_uuid` | `string` | No | Associated Ward UUID for direct Ward cache key indexing. |
  | `ttl` | `int` | No | Custom cache expiration in seconds (default `0` = forever). |
  | `force_recreate` | `bool` | No | If `true`, recreates entity & purges existing cache if it already exists. |
  | `uuid` | `string` | No | Custom UUID. Auto-generated if not provided. |

* **Example Request**:
  ```json
  {
    "name": "Primary Arithmetic",
    "description": "Basic arithmetic including addition and subtraction.",
    "min_age": 5,
    "max_age": 9,
    "ward_uuid": "e4d9a21b-872f-4e01-8f92-9111ab56100a",
    "ttl": 0
  }
  ```

* **Example Response (201 Created)**:
  ```json
  {
    "success": true,
    "data": {
      "id": 1,
      "uuid": "82f831f0-0b32-45bb-b3b4-10b2c93049ba",
      "name": "Primary Arithmetic",
      "description": "Basic arithmetic including addition and subtraction.",
      "min_age": 5,
      "max_age": 9,
      "created_on": "2026-09-01 14:00:00",
      "updated_on": "2026-09-01 14:00:00"
    },
    "description": "Successfully registered Curriculum."
  }
  ```

---

#### Recreate Curriculum for Ward

Force-recreates a curriculum for a specific Ward UUID, purging old Redis cache entries and re-priming fresh data and filters.

* **URL**: `/api/game/curriculum/recreate`
* **Method**: `POST`
* **Headers**:
  * `Content-Type: application/json`
  * `Authorization: Bearer <token>`
* **Request Body**:
  | Field | Type | Required | Description |
  | :--- | :--- | :--- | :--- |
  | `name` | `string` | **Yes** | Name of the curriculum. |
  | `ward_uuid` | `string` | **Yes** | Ward UUID for which the curriculum is being recreated. |
  | `description` | `string` | No | Updated curriculum goals/description. |
  | `min_age` | `int` | No | Minimum target age. |
  | `max_age` | `int` | No | Maximum target age. |
  | `ttl` | `int` | No | Custom TTL in seconds (default `0` = forever). |

* **Example Request**:
  ```json
  {
    "ward_uuid": "e4d9a21b-872f-4e01-8f92-9111ab56100a",
    "name": "Advanced Focus & Logic",
    "description": "Tailored curriculum focusing on pattern recognition.",
    "min_age": 7,
    "max_age": 12,
    "ttl": 0
  }
  ```

* **Example Response (200 OK)**:
  ```json
  {
    "success": true,
    "data": {
      "id": 1,
      "uuid": "82f831f0-0b32-45bb-b3b4-10b2c93049ba",
      "name": "Advanced Focus & Logic",
      "description": "Tailored curriculum focusing on pattern recognition.",
      "min_age": 7,
      "max_age": 12,
      "created_on": "2026-09-01 14:00:00",
      "updated_on": "2026-09-01 14:15:00"
    },
    "description": "Successfully recreated Curriculum for Ward."
  }
  ```

---

#### List Curriculums

Retrieves all curriculums with automatic Redis **Hit/Miss** caching.

* **URL**: `/api/game/curriculum` OR `/api/game/curriculum/list`
* **Method**: `GET`
* **Headers**:
  * `Authorization: Bearer <token>`

* **Example Response (200 OK)**:
  ```json
  {
    "success": true,
    "data": [
      {
        "id": 1,
        "uuid": "82f831f0-0b32-45bb-b3b4-10b2c93049ba",
        "name": "Primary Arithmetic",
        "description": "Basic arithmetic including addition and subtraction.",
        "min_age": 5,
        "max_age": 9,
        "created_on": "2026-09-01 14:00:00",
        "updated_on": "2026-09-01 14:00:00"
      }
    ]
  }
  ```

---

#### View Curriculum Details

Retrieves details for a curriculum by its database integer ID, UUID, or Ward UUID.

* **URL**: `/api/game/curriculum/info/:id` (where `:id` can be ID, UUID, or Ward UUID)
* **Alternative Query Parameters**: `/api/game/curriculum/info?id=<id>` OR `/api/game/curriculum/info?uuid=<uuid>`
* **Method**: `GET`
* **Headers**:
  * `Authorization: Bearer <token>`

* **Example Response (200 OK)**:
  ```json
  {
    "success": true,
    "data": {
      "id": 1,
      "uuid": "82f831f0-0b32-45bb-b3b4-10b2c93049ba",
      "name": "Primary Arithmetic",
      "description": "Basic arithmetic including addition and subtraction.",
      "min_age": 5,
      "max_age": 9,
      "created_on": "2026-09-01 14:00:00",
      "updated_on": "2026-09-01 14:00:00"
    }
  }
  ```

---

#### Update Curriculum

Updates an existing curriculum and invalidates stale Redis cache entries.

* **URL**: `/api/game/curriculum/update/:id`
* **Method**: `PUT`
* **Headers**:
  * `Content-Type: application/json`
  * `Authorization: Bearer <token>`
* **Request Body**:
  | Field | Type | Required | Description |
  | :--- | :--- | :--- | :--- |
  | `name` | `string` | No | New name (must be unique). |
  | `description` | `string` | No | New description. Can be `null`. |
  | `min_age` | `int` | No | New minimum age. Can be `null`. |
  | `max_age` | `int` | No | New maximum age. Can be `null`. |

* **Example Request**:
  ```json
  {
    "max_age": 10
  }
  ```

* **Example Response (200 OK)**:
  ```json
  {
    "success": true,
    "data": {
      "id": 1,
      "uuid": "82f831f0-0b32-45bb-b3b4-10b2c93049ba",
      "name": "Primary Arithmetic",
      "description": "Basic arithmetic including addition and subtraction.",
      "min_age": 5,
      "max_age": 10,
      "created_on": "2026-09-01 14:00:00",
      "updated_on": "2026-09-01 14:20:00"
    },
    "description": "Successfully updated Curriculum."
  }
  ```

---

#### Delete Curriculum

Removes a curriculum from the database and purges Redis cache.

* **URL**: `/api/game/curriculum/delete/:id`
* **Method**: `DELETE`
* **Headers**:
  * `Authorization: Bearer <token>`

* **Example Response (200 OK)**:
  ```json
  {
    "success": true,
    "description": "Successfully deleted Curriculum."
  }
  ```

---

### Games Collections

Routes follow the segment pattern `/api/game/collection[/:action[/:id]]`.

#### Register Games Collection

Creates a new group/collection of games.

* **URL**: `/api/game/collection/register`
* **Method**: `POST`
* **Headers**:
  * `Content-Type: application/json`
  * `Authorization: Bearer <token>`
* **Request Body**:
  | Field | Type | Required | Description |
  | :--- | :--- | :--- | :--- |
  | `name` | `string` | **Yes** | Unique name of the collection. |
  | `description` | `string` | No | Description of the collection. |
  | `game_ids` | `array` | No | List of game IDs, UUIDs, unique identifiers, or titles to include. |
  | `uuid` | `string` | No | Custom UUID. Auto-generated if not provided. |

* **Example Request**:
  ```json
  {
    "name": "Dyscalculia Training Pack",
    "description": "A curated collection of games designed to treat dyscalculia.",
    "game_ids": ["GAME-FD491EA3"]
  }
  ```

* **Example Response (201 Created)**:
  ```json
  {
    "success": true,
    "data": {
      "id": 1,
      "uuid": "2a9e38c4-a621-4f11-85bc-2236a908dbce",
      "name": "Dyscalculia Training Pack",
      "description": "A curated collection of games designed to treat dyscalculia.",
      "games": [
        {
          "id": 1,
          "uuid": "8e3c5a71-d602-4ef8-bc4b-e85d820f4c39",
          "title": "Math Quest",
          "unique_identifier": "GAME-FD491EA3"
        }
      ],
      "created_on": "2026-08-30 20:35:00",
      "updated_on": "2026-08-30 20:35:00"
    },
    "description": "Successfully registered GamesCollection."
  }
  ```

---

#### List Games Collections

Retrieves all game collections.

* **URL**: `/api/game/collection` OR `/api/game/collection/list`
* **Method**: `GET`
* **Headers**:
  * `Authorization: Bearer <token>`

* **Example Response (200 OK)**:
  ```json
  {
    "success": true,
    "data": [
      {
        "id": 1,
        "uuid": "2a9e38c4-a621-4f11-85bc-2236a908dbce",
        "name": "Dyscalculia Training Pack",
        "description": "A curated collection of games designed to treat dyscalculia.",
        "games": [
          {
            "id": 1,
            "uuid": "8e3c5a71-d602-4ef8-bc4b-e85d820f4c39",
            "title": "Math Quest",
            "unique_identifier": "GAME-FD491EA3"
          }
        ],
        "created_on": "2026-08-30 20:35:00",
        "updated_on": "2026-08-30 20:35:00"
      }
    ]
  }
  ```

---

#### View Games Collection Details

Retrieves details for a game collection by its ID, UUID, or name.

* **URL**: `/api/game/collection/info/:id`
* **Alternative Query Parameters**: `/api/game/collection/info?id=<id>` OR `/api/game/collection/info?uuid=<uuid>`
* **Method**: `GET`
* **Headers**:
  * `Authorization: Bearer <token>`

* **Example Response (200 OK)**:
  ```json
  {
    "success": true,
    "data": {
      "id": 1,
      "uuid": "2a9e38c4-a621-4f11-85bc-2236a908dbce",
      "name": "Dyscalculia Training Pack",
      "description": "A curated collection of games designed to treat dyscalculia.",
      "games": [
        {
          "id": 1,
          "uuid": "8e3c5a71-d602-4ef8-bc4b-e85d820f4c39",
          "title": "Math Quest",
          "unique_identifier": "GAME-FD491EA3"
        }
      ],
      "created_on": "2026-08-30 20:35:00",
      "updated_on": "2026-08-30 20:35:00"
    }
  }
  ```

---

#### Update Games Collection

Updates an existing games collection. Note that when providing `game_ids`, it replaces the entire set of games in the collection.

* **URL**: `/api/game/collection/update/:id`
* **Method**: `PUT`
* **Headers**:
  * `Content-Type: application/json`
  * `Authorization: Bearer <token>`
* **Request Body**:
  | Field | Type | Required | Description |
  | :--- | :--- | :--- | :--- |
  | `name` | `string` | No | New name (must be unique). |
  | `description` | `string` | No | New description. Can be `null`. |
  | `game_ids` | `array` | No | Complete new list of game IDs, UUIDs, unique identifiers, or titles (replaces existing). |

* **Example Request**:
  ```json
  {
    "description": "Updated training pack description.",
    "game_ids": []
  }
  ```

* **Example Response (200 OK)**:
  ```json
  {
    "success": true,
    "data": {
      "id": 1,
      "uuid": "2a9e38c4-a621-4f11-85bc-2236a908dbce",
      "name": "Dyscalculia Training Pack",
      "description": "Updated training pack description.",
      "games": [],
      "created_on": "2026-08-30 20:35:00",
      "updated_on": "2026-08-30 20:40:00"
    },
    "description": "Successfully updated GamesCollection."
  }
  ```

---

#### Delete Games Collection

Removes a games collection from the database.

* **URL**: `/api/game/collection/delete/:id`
* **Method**: `DELETE`
* **Headers**:
  * `Authorization: Bearer <token>`

* **Example Response (200 OK)**:
  ```json
  {
    "success": true,
    "description": "Successfully deleted GamesCollection."
  }
  ```
