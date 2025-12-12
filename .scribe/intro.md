# Introduction



<aside>
    <strong>Base URL</strong>: <code>https://api.learning-center-academy.local</code>
</aside>

    This documentation describes the Learning Center API, including the dual-token authentication flow.

    <p>The authentication system uses:</p>
    <ul>
      <li>A <strong>client APP_TOKEN</strong> header to identify the calling application when logging in.</li>
      <li>A standard <strong>auth token</strong> (Bearer token) used with the <code>Authorization</code> header.</li>
      <li>An enhanced <strong>app_token</strong> returned from the login endpoint, which includes user-specific claims and is required on all protected routes.</li>
    </ul>

    <p>High-level steps:</p>
    <ol>
      <li>Obtain a client APP_TOKEN from the platform administrator and configure it on your client.</li>
      <li>Call <code>POST /login</code> with valid credentials and the <code>APP_TOKEN</code> header.</li>
      <li>Store the returned <code>token</code> and <code>app_token</code> securely.</li>
      <li>For all protected endpoints, send:
        <ul>
          <li><code>Authorization: Bearer {token}</code></li>
          <li><code>APP_TOKEN: {app_token}</code></li>
        </ul>
      </li>
    </ol>

    <aside>As you scroll, you'll see code examples for working with the API in different programming languages in the dark area to the right (or as part of the content on mobile).
    You can switch the language used with the tabs at the top right (or from the nav menu at the top left on mobile).</aside>

