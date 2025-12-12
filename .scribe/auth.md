# Authenticating requests

To authenticate requests, include a **`Authorization`** header with the value **`"Bearer {token}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Protected endpoints require an Authorization: Bearer token and an APP_TOKEN header. Obtain both tokens from the POST /login endpoint.
