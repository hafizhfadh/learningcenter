# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer Bearer {YOUR_AUTH_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

<p><strong>Authentication Overview</strong></p>
<p>The Learning Center API uses a dual-token authentication model with three key pieces:</p>
<ul>
  <li><code>APP_TOKEN</code> – A client identifier header required when calling <code>POST /login</code>. This is configured per application and validated on the server.</li>
  <li><code>token</code> – A standard Bearer auth token returned from <code>/login</code> and used in the <code>Authorization</code> header.</li>
  <li><code>app_token</code> – An enhanced token returned from <code>/login</code> which contains user-specific claims (user ID, organization, issued-at, expiry) and must be sent on all protected endpoints.</li>
</ul>

<p><strong>Step-by-step flow</strong></p>
<ol>
  <li>Configure your client APP_TOKEN (provided by the platform) in your backend or frontend environment.</li>
  <li>Call <code>POST /login</code> with:
    <ul>
      <li>Request body: <code>email</code>, <code>password</code></li>
      <li>Header: <code>APP_TOKEN: {client-app-token}</code></li>
    </ul>
  </li>
  <li>On success, the response payload includes:
    <ul>
      <li><code>data.token</code> – Bearer auth token</li>
      <li><code>data.app_token</code> – Enhanced app token with user claims</li>
      <li><code>data.expires_in</code> – Lifetime (in seconds) for the auth token</li>
    </ul>
  </li>
  <li>For all protected endpoints (for example <code>GET /profile</code>, <code>GET /courses</code>), send both headers:
    <ul>
      <li><code>Authorization: Bearer {token}</code></li>
      <li><code>APP_TOKEN: {app_token}</code></li>
    </ul>
  </li>
</ol>

<p><strong>Example requests</strong></p>
<pre><code class="language-bash"># 1. Login
curl -X POST "https://api.learning-center-academy.local/login" \
  -H "Accept: application/json" \
  -H "APP_TOKEN: {client-app-token}" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'

# Response (excerpt)
# {
#   "data": {
#     "user": { ... },
#     "token": "{auth_token}",
#     "token_type": "Bearer",
#     "expires_in": 2592000,
#     "app_token": "{enhanced_app_token}"
#   }
# }

# 2. Call protected endpoint
curl -X GET "https://api.learning-center-academy.local/profile" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {auth_token}" \
  -H "APP_TOKEN: {enhanced_app_token}"</code></pre>

<pre><code class="language-javascript">// Login (example using fetch)
const loginResponse = await fetch('https://api.learning-center-academy.local/login', {
  method: 'POST',
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'APP_TOKEN': clientAppToken,
  },
  body: JSON.stringify({ email, password }),
});

const loginData = await loginResponse.json();
const authToken = loginData.data.token;
const appToken = loginData.data.app_token;

// Call protected endpoint
const profileResponse = await fetch('https://api.learning-center-academy.local/profile', {
  headers: {
    'Accept': 'application/json',
    'Authorization': `Bearer `,
    'APP_TOKEN': appToken,
  },
});</code></pre>

<p><strong>Access control and roles</strong></p>
<p>Endpoints also enforce role-based access control on top of authentication:</p>
<ul>
  <li><code>student</code> – Can access their own profile, institution, learning paths, and courses.</li>
  <li><code>school_teacher</code> / <code>school_admin</code> – Have extended access to institution-bound resources and teaching tools.</li>
  <li><code>super_admin</code> – Has elevated administrative permissions, but may be restricted from some student-only endpoints (see specific endpoint docs).</li>
</ul>

<p>Refer to the endpoint-specific documentation for detailed role requirements.</p>

<p><strong>Common authentication errors</strong></p>
<ul>
  <li><code>401 Unauthorized</code> – Missing or invalid <code>Authorization</code> header, malformed <code>APP_TOKEN</code>, or subject mismatch.</li>
  <li><code>403 Forbidden</code> – Valid token, but expired <code>app_token</code> or insufficient permissions for the requested resource.</li>
</ul>

<p><strong>Security best practices</strong></p>
<ul>
  <li>Always use HTTPS (<code>https://</code>) for all authenticated requests.</li>
  <li>Store tokens only in secure storage (HTTP-only cookies or secure storage on mobile), never in plain localStorage if XSS is a concern.</li>
  <li>Rotate and revoke tokens regularly and on logout.</li>
  <li>Do not log raw token values; log only metadata (user ID, reason, timestamps).</li>
</ul>
