# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_AUTH_KEY}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

This API uses Laravel Sanctum for authentication. Obtain a token by sending a POST request to `/api/login` with valid credentials. Include the token in the `Authorization` header as `Bearer {token}`.
