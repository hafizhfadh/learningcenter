<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Laravel API Documentation</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.style.css") }}" media="screen">
    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-default.print.css") }}" media="print">

    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.10/lodash.min.js"></script>

    <link rel="stylesheet"
          href="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/styles/obsidian.min.css">
    <script src="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/highlight.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jets/0.14.1/jets.min.js"></script>

    <style id="language-style">
        /* starts out as display none and is replaced with js later  */
                    body .content .bash-example code { display: none; }
                    body .content .javascript-example code { display: none; }
            </style>

    <script>
        var tryItOutBaseUrl = "https://api.learning-center-academy.local";
        var useCsrf = Boolean();
        var csrfUrl = "/sanctum/csrf-cookie";
    </script>
    <script src="{{ asset("/vendor/scribe/js/tryitout-5.6.0.js") }}"></script>

    <script src="{{ asset("/vendor/scribe/js/theme-default-5.6.0.js") }}"></script>

</head>

<body data-languages="[&quot;bash&quot;,&quot;javascript&quot;]">

<a href="#" id="nav-button">
    <span>
        MENU
        <img src="{{ asset("/vendor/scribe/images/navbar.png") }}" alt="navbar-image"/>
    </span>
</a>
<div class="tocify-wrapper">
    
            <div class="lang-selector">
                                            <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                            <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                    </div>
    
    <div class="search">
        <input type="text" class="search" id="input-search" placeholder="Search">
    </div>

    <div id="toc">
                    <ul id="tocify-header-introduction" class="tocify-header">
                <li class="tocify-item level-1" data-unique="introduction">
                    <a href="#introduction">Introduction</a>
                </li>
                            </ul>
                    <ul id="tocify-header-authenticating-requests" class="tocify-header">
                <li class="tocify-item level-1" data-unique="authenticating-requests">
                    <a href="#authenticating-requests">Authenticating requests</a>
                </li>
                            </ul>
                    <ul id="tocify-header-authentication" class="tocify-header">
                <li class="tocify-item level-1" data-unique="authentication">
                    <a href="#authentication">Authentication</a>
                </li>
                                    <ul id="tocify-subheader-authentication" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="authentication-POSTlogin">
                                <a href="#authentication-POSTlogin">User login</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="authentication-POSTrefresh">
                                <a href="#authentication-POSTrefresh">Refresh auth token</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="authentication-POSTlogout">
                                <a href="#authentication-POSTlogout">Logout</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="authentication-GETprofile">
                                <a href="#authentication-GETprofile">Get current user profile</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="authentication-GETinstitution">
                                <a href="#authentication-GETinstitution">Get institution information</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-courses" class="tocify-header">
                <li class="tocify-item level-1" data-unique="courses">
                    <a href="#courses">Courses</a>
                </li>
                                    <ul id="tocify-subheader-courses" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="courses-GETcourses">
                                <a href="#courses-GETcourses">List courses</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="courses-GETcourses-search">
                                <a href="#courses-GETcourses-search">Search courses</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="courses-GETcourses--courseId-">
                                <a href="#courses-GETcourses--courseId-">Get course details</a>
                            </li>
                                                                        </ul>
                            </ul>
                    <ul id="tocify-header-learning-paths" class="tocify-header">
                <li class="tocify-item level-1" data-unique="learning-paths">
                    <a href="#learning-paths">Learning Paths</a>
                </li>
                                    <ul id="tocify-subheader-learning-paths" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="learning-paths-GETlearning-paths">
                                <a href="#learning-paths-GETlearning-paths">List learning paths</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="learning-paths-GETlearning-paths--id-">
                                <a href="#learning-paths-GETlearning-paths--id-">Get learning path details</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="learning-paths-POSTlearning-paths--id--enroll">
                                <a href="#learning-paths-POSTlearning-paths--id--enroll">Enroll in a learning path</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="learning-paths-GETlearning-paths-progress-my">
                                <a href="#learning-paths-GETlearning-paths-progress-my">Get learning path progress</a>
                            </li>
                                                                        </ul>
                            </ul>
            </div>

    <ul class="toc-footer" id="toc-footer">
                    <li style="padding-bottom: 5px;"><a href="{{ route("scribe.postman") }}">View Postman collection</a></li>
                            <li style="padding-bottom: 5px;"><a href="{{ route("scribe.openapi") }}">View OpenAPI spec</a></li>
                <li><a href="http://github.com/knuckleswtf/scribe">Documentation powered by Scribe ✍</a></li>
    </ul>

    <ul class="toc-footer" id="last-updated">
        <li>Last updated: December 12, 2025</li>
    </ul>
</div>

<div class="page-wrapper">
    <div class="dark-box"></div>
    <div class="content">
        <h1 id="introduction">Introduction</h1>
<aside>
    <strong>Base URL</strong>: <code>https://api.learning-center-academy.local</code>
</aside>
<pre><code>This documentation aims to provide all the information you need to work with our API.

&lt;aside&gt;As you scroll, you'll see code examples for working with the API in different programming languages in the dark area to the right (or as part of the content on mobile).
You can switch the language used with the tabs at the top right (or from the nav menu at the top left on mobile).&lt;/aside&gt;</code></pre>

        <h1 id="authenticating-requests">Authenticating requests</h1>
<p>To authenticate requests, include an <strong><code>Authorization</code></strong> header with the value <strong><code>"Bearer Bearer {token}"</code></strong>.</p>
<p>All authenticated endpoints are marked with a <code>requires authentication</code> badge in the documentation below.</p>
<p>Protected endpoints require an Authorization: Bearer token and an APP_TOKEN header. Obtain both tokens from the POST /login endpoint.</p>

        <h1 id="authentication">Authentication</h1>

    <p>Endpoints for user login, token refresh, profile, institution info and logout.</p>

                                <h2 id="authentication-POSTlogin">User login</h2>

<p>
</p>

<p>Authenticate a user with email and password and issue access tokens.
Returns a Sanctum bearer token and an enhanced APP_TOKEN tied to the user.</p>

<span id="example-requests-POSTlogin">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://api.learning-center-academy.local/login" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"email\": \"admin@learningcenter.com\",
    \"password\": \"password\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://api.learning-center-academy.local/login"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "email": "admin@learningcenter.com",
    "password": "password"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTlogin">
            <blockquote>
            <p>Example response (200, Successful login):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;code&quot;: 200,
    &quot;message&quot;: &quot;Login successful&quot;,
    &quot;data&quot;: {
        &quot;user&quot;: {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Super User&quot;,
            &quot;email&quot;: &quot;admin@learningcenter.com&quot;
        },
        &quot;token&quot;: &quot;1|abcdef123456789&quot;,
        &quot;token_type&quot;: &quot;Bearer&quot;,
        &quot;expires_in&quot;: 2592000,
        &quot;app_token&quot;: &quot;eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...&quot;
    },
    &quot;pagination&quot;: {}
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, Invalid credentials):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;code&quot;: 401,
    &quot;message&quot;: &quot;The provided credentials do not match our records&quot;,
    &quot;data&quot;: [],
    &quot;pagination&quot;: {}
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, Missing client APP_TOKEN):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;code&quot;: 401,
    &quot;message&quot;: &quot;Unauthorized&quot;,
    &quot;data&quot;: [],
    &quot;pagination&quot;: {}
}</code>
 </pre>
            <blockquote>
            <p>Example response (403, Invalid client APP_TOKEN):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;code&quot;: 403,
    &quot;message&quot;: &quot;Forbidden&quot;,
    &quot;data&quot;: [],
    &quot;pagination&quot;: {}
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, Validation failed):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;code&quot;: 422,
    &quot;message&quot;: &quot;Validation failed&quot;,
    &quot;data&quot;: {
        &quot;errors&quot;: {
            &quot;email&quot;: [
                &quot;The email field is required.&quot;
            ],
            &quot;password&quot;: [
                &quot;The password field is required.&quot;
            ]
        }
    },
    &quot;pagination&quot;: {}
}</code>
 </pre>
    </span>
<span id="execution-results-POSTlogin" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTlogin"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTlogin"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTlogin" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTlogin">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTlogin" data-method="POST"
      data-path="login"
      data-authed="0"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTlogin', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTlogin"
                    onclick="tryItOut('POSTlogin');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTlogin"
                    onclick="cancelTryOut('POSTlogin');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTlogin"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>login</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTlogin"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTlogin"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTlogin"
               value="admin@learningcenter.com"
               data-component="body">
    <br>
<p>The user's email address. Example: <code>admin@learningcenter.com</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="POSTlogin"
               value="password"
               data-component="body">
    <br>
<p>The user's password. Example: <code>password</code></p>
        </div>
        </form>

                    <h2 id="authentication-POSTrefresh">Refresh auth token</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Revoke existing tokens for the user and issue a new Sanctum bearer token.</p>

<span id="example-requests-POSTrefresh">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://api.learning-center-academy.local/refresh" \
    --header "Authorization: Bearer Bearer {token}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"email\": \"student@example.com\",
    \"password\": \"password123\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://api.learning-center-academy.local/refresh"
);

const headers = {
    "Authorization": "Bearer Bearer {token}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "email": "student@example.com",
    "password": "password123"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTrefresh">
            <blockquote>
            <p>Example response (200, Token refreshed):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;code&quot;: 200,
    &quot;message&quot;: &quot;Token refreshed successfully&quot;,
    &quot;data&quot;: {
        &quot;user&quot;: {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Student User&quot;,
            &quot;email&quot;: &quot;student@example.com&quot;
        },
        &quot;token&quot;: &quot;2|newtoken123456789&quot;,
        &quot;token_type&quot;: &quot;Bearer&quot;,
        &quot;expires_in&quot;: 2592000
    },
    &quot;pagination&quot;: {}
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, Invalid credentials):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;code&quot;: 401,
    &quot;message&quot;: &quot;The provided credentials do not match our records&quot;,
    &quot;data&quot;: [],
    &quot;pagination&quot;: {}
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, Validation failed):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;code&quot;: 422,
    &quot;message&quot;: &quot;Validation failed&quot;,
    &quot;data&quot;: {
        &quot;errors&quot;: {
            &quot;email&quot;: [
                &quot;The email field is required.&quot;
            ],
            &quot;password&quot;: [
                &quot;The password field is required.&quot;
            ]
        }
    },
    &quot;pagination&quot;: {}
}</code>
 </pre>
    </span>
<span id="execution-results-POSTrefresh" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTrefresh"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTrefresh"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTrefresh" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTrefresh">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTrefresh" data-method="POST"
      data-path="refresh"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTrefresh', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTrefresh"
                    onclick="tryItOut('POSTrefresh');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTrefresh"
                    onclick="cancelTryOut('POSTrefresh');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTrefresh"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>refresh</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTrefresh"
               value="Bearer Bearer {token}"
               data-component="header">
    <br>
<p>Example: <code>Bearer Bearer {token}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTrefresh"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTrefresh"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>email</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="email"                data-endpoint="POSTrefresh"
               value="student@example.com"
               data-component="body">
    <br>
<p>The user's email address. Example: <code>student@example.com</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="POSTrefresh"
               value="password123"
               data-component="body">
    <br>
<p>The user's current password. Example: <code>password123</code></p>
        </div>
        </form>

                    <h2 id="authentication-POSTlogout">Logout</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Revoke all active tokens for the authenticated user.</p>

<span id="example-requests-POSTlogout">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://api.learning-center-academy.local/logout" \
    --header "Authorization: Bearer Bearer {token}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://api.learning-center-academy.local/logout"
);

const headers = {
    "Authorization": "Bearer Bearer {token}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTlogout">
            <blockquote>
            <p>Example response (200, Logged out):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;code&quot;: 200,
    &quot;message&quot;: &quot;Successfully logged out&quot;,
    &quot;data&quot;: [],
    &quot;pagination&quot;: {}
}</code>
 </pre>
    </span>
<span id="execution-results-POSTlogout" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTlogout"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTlogout"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTlogout" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTlogout">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTlogout" data-method="POST"
      data-path="logout"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTlogout', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTlogout"
                    onclick="tryItOut('POSTlogout');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTlogout"
                    onclick="cancelTryOut('POSTlogout');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTlogout"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>logout</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTlogout"
               value="Bearer Bearer {token}"
               data-component="header">
    <br>
<p>Example: <code>Bearer Bearer {token}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTlogout"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTlogout"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="authentication-GETprofile">Get current user profile</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Return the authenticated user's profile information.</p>

<span id="example-requests-GETprofile">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://api.learning-center-academy.local/profile" \
    --header "Authorization: Bearer Bearer {token}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://api.learning-center-academy.local/profile"
);

const headers = {
    "Authorization": "Bearer Bearer {token}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETprofile">
            <blockquote>
            <p>Example response (200, Profile retrieved):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;code&quot;: 200,
    &quot;message&quot;: &quot;Profile retrieved successfully&quot;,
    &quot;data&quot;: {
        &quot;id&quot;: 1,
        &quot;name&quot;: &quot;Student User&quot;,
        &quot;email&quot;: &quot;student@example.com&quot;
    },
    &quot;pagination&quot;: {}
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, Missing or invalid tokens):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;code&quot;: 401,
    &quot;message&quot;: &quot;Unauthorized&quot;,
    &quot;data&quot;: [],
    &quot;pagination&quot;: {}
}</code>
 </pre>
            <blockquote>
            <p>Example response (403, Expired app token):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;code&quot;: 403,
    &quot;message&quot;: &quot;Forbidden&quot;,
    &quot;data&quot;: [],
    &quot;pagination&quot;: {}
}</code>
 </pre>
    </span>
<span id="execution-results-GETprofile" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETprofile"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETprofile"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETprofile" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETprofile">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETprofile" data-method="GET"
      data-path="profile"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETprofile', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETprofile"
                    onclick="tryItOut('GETprofile');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETprofile"
                    onclick="cancelTryOut('GETprofile');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETprofile"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>profile</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETprofile"
               value="Bearer Bearer {token}"
               data-component="header">
    <br>
<p>Example: <code>Bearer Bearer {token}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETprofile"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETprofile"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                    <h2 id="authentication-GETinstitution">Get institution information</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Retrieve the institution associated with the authenticated user.
Only users with institution-bound roles can access this endpoint.</p>

<span id="example-requests-GETinstitution">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://api.learning-center-academy.local/institution" \
    --header "Authorization: Bearer Bearer {token}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://api.learning-center-academy.local/institution"
);

const headers = {
    "Authorization": "Bearer Bearer {token}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETinstitution">
            <blockquote>
            <p>Example response (200, Institution retrieved):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;code&quot;: 200,
    &quot;message&quot;: &quot;Institution information retrieved successfully&quot;,
    &quot;data&quot;: {
        &quot;id&quot;: 1,
        &quot;name&quot;: &quot;Learning Center University&quot;,
        &quot;slug&quot;: &quot;learning-center-university&quot;,
        &quot;domain&quot;: &quot;university.example.com&quot;,
        &quot;settings&quot;: [],
        &quot;created_at&quot;: &quot;2024-01-01T12:00:00Z&quot;,
        &quot;updated_at&quot;: &quot;2024-01-02T12:00:00Z&quot;
    },
    &quot;pagination&quot;: {}
}</code>
 </pre>
            <blockquote>
            <p>Example response (403, User without institution-bound role):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;code&quot;: 403,
    &quot;message&quot;: &quot;Access denied. Only users with institution-bound roles can access institution information&quot;,
    &quot;data&quot;: [],
    &quot;pagination&quot;: {}
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, No institution found for user):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;code&quot;: 404,
    &quot;message&quot;: &quot;No institution found for this user&quot;,
    &quot;data&quot;: [],
    &quot;pagination&quot;: {}
}</code>
 </pre>
    </span>
<span id="execution-results-GETinstitution" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETinstitution"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETinstitution"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETinstitution" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETinstitution">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETinstitution" data-method="GET"
      data-path="institution"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETinstitution', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETinstitution"
                    onclick="tryItOut('GETinstitution');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETinstitution"
                    onclick="cancelTryOut('GETinstitution');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETinstitution"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>institution</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETinstitution"
               value="Bearer Bearer {token}"
               data-component="header">
    <br>
<p>Example: <code>Bearer Bearer {token}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETinstitution"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETinstitution"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        </form>

                <h1 id="courses">Courses</h1>

    <p>Endpoints for browsing, searching and viewing course details.</p>

                                <h2 id="courses-GETcourses">List courses</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Return a paginated list of courses accessible to the current user.
Students only see published courses; staff can see all.</p>

<span id="example-requests-GETcourses">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://api.learning-center-academy.local/courses?page=1&amp;per_page=20&amp;sort=%22created_at%22&amp;order=%22desc%22" \
    --header "Authorization: Bearer Bearer {token}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"page\": 16,
    \"per_page\": 22,
    \"sort\": \"created_at\",
    \"order\": \"desc\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://api.learning-center-academy.local/courses"
);

const params = {
    "page": "1",
    "per_page": "20",
    "sort": ""created_at"",
    "order": ""desc"",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Authorization": "Bearer Bearer {token}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "page": 16,
    "per_page": 22,
    "sort": "created_at",
    "order": "desc"
};

fetch(url, {
    method: "GET",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETcourses">
            <blockquote>
            <p>Example response (200, Courses retrieved):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;code&quot;: 200,
    &quot;message&quot;: &quot;Courses retrieved successfully&quot;,
    &quot;data&quot;: [
        {
            &quot;id&quot;: 1,
            &quot;title&quot;: &quot;Intro to Programming&quot;,
            &quot;slug&quot;: &quot;intro-to-programming&quot;,
            &quot;description&quot;: &quot;Learn the basics of programming.&quot;,
            &quot;banner_url&quot;: &quot;https://example.com/banners/intro-to-programming.png&quot;,
            &quot;tags&quot;: [
                &quot;programming&quot;,
                &quot;beginner&quot;
            ],
            &quot;estimated_time&quot;: 3600,
            &quot;is_published&quot;: true,
            &quot;created_at&quot;: &quot;2024-01-01T12:00:00Z&quot;,
            &quot;instructor&quot;: {
                &quot;id&quot;: 10,
                &quot;name&quot;: &quot;Jane Doe&quot;,
                &quot;email&quot;: &quot;jane@example.com&quot;
            },
            &quot;enrollment_status&quot;: &quot;not_enrolled&quot;,
            &quot;total_lessons&quot;: 10,
            &quot;total_tasks&quot;: 5
        }
    ],
    &quot;pagination&quot;: {
        &quot;current_page&quot;: 1,
        &quot;per_page&quot;: 20,
        &quot;total&quot;: 35,
        &quot;last_page&quot;: 2,
        &quot;from&quot;: 1,
        &quot;to&quot;: 20,
        &quot;has_more_pages&quot;: true
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, Validation failed):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;code&quot;: 422,
    &quot;message&quot;: &quot;Validation failed&quot;,
    &quot;data&quot;: {
        &quot;errors&quot;: {
            &quot;page&quot;: [
                &quot;The page must be at least 1.&quot;
            ]
        }
    },
    &quot;pagination&quot;: {}
}</code>
 </pre>
    </span>
<span id="execution-results-GETcourses" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETcourses"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETcourses"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETcourses" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETcourses">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETcourses" data-method="GET"
      data-path="courses"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETcourses', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETcourses"
                    onclick="tryItOut('GETcourses');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETcourses"
                    onclick="cancelTryOut('GETcourses');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETcourses"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>courses</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETcourses"
               value="Bearer Bearer {token}"
               data-component="header">
    <br>
<p>Example: <code>Bearer Bearer {token}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETcourses"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETcourses"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETcourses"
               value="1"
               data-component="query">
    <br>
<p>The page number to return. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETcourses"
               value="20"
               data-component="query">
    <br>
<p>Number of items per page (1-100). Example: <code>20</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>sort</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="sort"                data-endpoint="GETcourses"
               value=""created_at""
               data-component="query">
    <br>
<p>Field to sort by: title, created_at, estimated_time. Example: <code>"created_at"</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>order</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="order"                data-endpoint="GETcourses"
               value=""desc""
               data-component="query">
    <br>
<p>Sort direction: asc or desc. Example: <code>"desc"</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETcourses"
               value="16"
               data-component="body">
    <br>
<p>Must be at least 1. Example: <code>16</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETcourses"
               value="22"
               data-component="body">
    <br>
<p>Must be at least 1. Must not be greater than 100. Example: <code>22</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>sort</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="sort"                data-endpoint="GETcourses"
               value="created_at"
               data-component="body">
    <br>
<p>Example: <code>created_at</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>title</code></li> <li><code>created_at</code></li> <li><code>estimated_time</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>order</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="order"                data-endpoint="GETcourses"
               value="desc"
               data-component="body">
    <br>
<p>Example: <code>desc</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>asc</code></li> <li><code>desc</code></li></ul>
        </div>
        </form>

                    <h2 id="courses-GETcourses-search">Search courses</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Search courses by title, description, tags, instructor and date/time filters.
Supports relevance-based sorting when a search query is provided.</p>

<span id="example-requests-GETcourses-search">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://api.learning-center-academy.local/courses/search?q=%22programming%22&amp;instructor=%22Jane+Doe%22&amp;tags=%22beginner%2Cbackend%22&amp;start_date=%222024-01-01%22&amp;end_date=%222024-12-31%22&amp;min_time=30&amp;max_time=120&amp;page=1&amp;per_page=20&amp;sort=%22relevance%22&amp;order=%22desc%22" \
    --header "Authorization: Bearer Bearer {token}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"q\": \"b\",
    \"instructor\": \"n\",
    \"tags\": \"g\",
    \"start_date\": \"2025-12-12\",
    \"end_date\": \"2052-01-05\",
    \"min_time\": 39,
    \"max_time\": 84,
    \"page\": 66,
    \"per_page\": 17,
    \"sort\": \"estimated_time\",
    \"order\": \"asc\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://api.learning-center-academy.local/courses/search"
);

const params = {
    "q": ""programming"",
    "instructor": ""Jane Doe"",
    "tags": ""beginner,backend"",
    "start_date": ""2024-01-01"",
    "end_date": ""2024-12-31"",
    "min_time": "30",
    "max_time": "120",
    "page": "1",
    "per_page": "20",
    "sort": ""relevance"",
    "order": ""desc"",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Authorization": "Bearer Bearer {token}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "q": "b",
    "instructor": "n",
    "tags": "g",
    "start_date": "2025-12-12",
    "end_date": "2052-01-05",
    "min_time": 39,
    "max_time": 84,
    "page": 66,
    "per_page": 17,
    "sort": "estimated_time",
    "order": "asc"
};

fetch(url, {
    method: "GET",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETcourses-search">
            <blockquote>
            <p>Example response (200, Search completed):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;code&quot;: 200,
    &quot;message&quot;: &quot;Search completed successfully&quot;,
    &quot;data&quot;: [
        {
            &quot;id&quot;: 1,
            &quot;title&quot;: &quot;Intro to Programming&quot;,
            &quot;slug&quot;: &quot;intro-to-programming&quot;,
            &quot;description&quot;: &quot;Learn the basics of programming.&quot;,
            &quot;banner_url&quot;: &quot;https://example.com/banners/intro-to-programming.png&quot;,
            &quot;tags&quot;: [
                &quot;programming&quot;,
                &quot;beginner&quot;
            ],
            &quot;estimated_time&quot;: 3600,
            &quot;is_published&quot;: true,
            &quot;created_at&quot;: &quot;2024-01-01T12:00:00Z&quot;,
            &quot;instructor&quot;: {
                &quot;id&quot;: 10,
                &quot;name&quot;: &quot;Jane Doe&quot;,
                &quot;email&quot;: &quot;jane@example.com&quot;
            },
            &quot;enrollment_status&quot;: &quot;not_enrolled&quot;,
            &quot;total_lessons&quot;: 10,
            &quot;total_tasks&quot;: 5,
            &quot;relevance_score&quot;: 1
        }
    ],
    &quot;pagination&quot;: {
        &quot;current_page&quot;: 1,
        &quot;per_page&quot;: 20,
        &quot;total&quot;: 10,
        &quot;last_page&quot;: 1,
        &quot;from&quot;: 1,
        &quot;to&quot;: 10,
        &quot;has_more_pages&quot;: false
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (422, Validation failed):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;code&quot;: 422,
    &quot;message&quot;: &quot;Validation failed&quot;,
    &quot;data&quot;: {
        &quot;errors&quot;: {
            &quot;start_date&quot;: [
                &quot;The start date does not match the format Y-m-d.&quot;
            ]
        }
    },
    &quot;pagination&quot;: {}
}</code>
 </pre>
    </span>
<span id="execution-results-GETcourses-search" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETcourses-search"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETcourses-search"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETcourses-search" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETcourses-search">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETcourses-search" data-method="GET"
      data-path="courses/search"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETcourses-search', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETcourses-search"
                    onclick="tryItOut('GETcourses-search');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETcourses-search"
                    onclick="cancelTryOut('GETcourses-search');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETcourses-search"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>courses/search</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETcourses-search"
               value="Bearer Bearer {token}"
               data-component="header">
    <br>
<p>Example: <code>Bearer Bearer {token}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETcourses-search"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETcourses-search"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>q</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="q"                data-endpoint="GETcourses-search"
               value=""programming""
               data-component="query">
    <br>
<p>Search term used to match title, description or tags. Example: <code>"programming"</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>instructor</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="instructor"                data-endpoint="GETcourses-search"
               value=""Jane Doe""
               data-component="query">
    <br>
<p>Filter by instructor name. Example: <code>"Jane Doe"</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>tags</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="tags"                data-endpoint="GETcourses-search"
               value=""beginner,backend""
               data-component="query">
    <br>
<p>Comma-separated list of tags to filter by. Example: <code>"beginner,backend"</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>start_date</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="start_date"                data-endpoint="GETcourses-search"
               value=""2024-01-01""
               data-component="query">
    <br>
<p>Filter courses created on or after this date (Y-m-d). Example: <code>"2024-01-01"</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>end_date</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="end_date"                data-endpoint="GETcourses-search"
               value=""2024-12-31""
               data-component="query">
    <br>
<p>Filter courses created on or before this date (Y-m-d). Example: <code>"2024-12-31"</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>min_time</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="min_time"                data-endpoint="GETcourses-search"
               value="30"
               data-component="query">
    <br>
<p>Minimum estimated time in minutes. Example: <code>30</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>max_time</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="max_time"                data-endpoint="GETcourses-search"
               value="120"
               data-component="query">
    <br>
<p>Maximum estimated time in minutes. Example: <code>120</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETcourses-search"
               value="1"
               data-component="query">
    <br>
<p>The page number to return. Example: <code>1</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETcourses-search"
               value="20"
               data-component="query">
    <br>
<p>Number of items per page (1-100). Example: <code>20</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>sort</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="sort"                data-endpoint="GETcourses-search"
               value=""relevance""
               data-component="query">
    <br>
<p>Sort field: title, created_at, estimated_time, relevance. Example: <code>"relevance"</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>order</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="order"                data-endpoint="GETcourses-search"
               value=""desc""
               data-component="query">
    <br>
<p>Sort direction: asc or desc. Example: <code>"desc"</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>Body Parameters</b></h4>
        <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>q</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="q"                data-endpoint="GETcourses-search"
               value="b"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>b</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>instructor</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="instructor"                data-endpoint="GETcourses-search"
               value="n"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>n</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>tags</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="tags"                data-endpoint="GETcourses-search"
               value="g"
               data-component="body">
    <br>
<p>Must not be greater than 255 characters. Example: <code>g</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>start_date</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="start_date"                data-endpoint="GETcourses-search"
               value="2025-12-12"
               data-component="body">
    <br>
<p>Must be a valid date in the format <code>Y-m-d</code>. Example: <code>2025-12-12</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>end_date</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="end_date"                data-endpoint="GETcourses-search"
               value="2052-01-05"
               data-component="body">
    <br>
<p>Must be a valid date in the format <code>Y-m-d</code>. Must be a date after or equal to <code>start_date</code>. Example: <code>2052-01-05</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>min_time</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="min_time"                data-endpoint="GETcourses-search"
               value="39"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>39</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>max_time</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="max_time"                data-endpoint="GETcourses-search"
               value="84"
               data-component="body">
    <br>
<p>Must be at least 0. Example: <code>84</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="page"                data-endpoint="GETcourses-search"
               value="66"
               data-component="body">
    <br>
<p>Must be at least 1. Example: <code>66</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETcourses-search"
               value="17"
               data-component="body">
    <br>
<p>Must be at least 1. Must not be greater than 100. Example: <code>17</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>sort</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="sort"                data-endpoint="GETcourses-search"
               value="estimated_time"
               data-component="body">
    <br>
<p>Example: <code>estimated_time</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>title</code></li> <li><code>created_at</code></li> <li><code>estimated_time</code></li> <li><code>relevance</code></li></ul>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>order</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="order"                data-endpoint="GETcourses-search"
               value="asc"
               data-component="body">
    <br>
<p>Example: <code>asc</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>asc</code></li> <li><code>desc</code></li></ul>
        </div>
        </form>

                    <h2 id="courses-GETcourses--courseId-">Get course details</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Retrieve full details for a single course, including lessons, sections, tasks and statistics.
Students cannot access unpublished courses.</p>

<span id="example-requests-GETcourses--courseId-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://api.learning-center-academy.local/courses/1" \
    --header "Authorization: Bearer Bearer {token}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://api.learning-center-academy.local/courses/1"
);

const headers = {
    "Authorization": "Bearer Bearer {token}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETcourses--courseId-">
            <blockquote>
            <p>Example response (200, Course found):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;code&quot;: 200,
    &quot;message&quot;: &quot;Course details retrieved successfully&quot;,
    &quot;data&quot;: {
        &quot;id&quot;: 1,
        &quot;title&quot;: &quot;Intro to Programming&quot;,
        &quot;slug&quot;: &quot;intro-to-programming&quot;,
        &quot;description&quot;: &quot;Learn the basics of programming.&quot;,
        &quot;banner_url&quot;: &quot;https://example.com/banners/intro-to-programming.png&quot;,
        &quot;tags&quot;: [
            &quot;programming&quot;,
            &quot;beginner&quot;
        ],
        &quot;estimated_time&quot;: 3600,
        &quot;is_published&quot;: true,
        &quot;created_at&quot;: &quot;2024-01-01T12:00:00Z&quot;,
        &quot;updated_at&quot;: &quot;2024-01-02T12:00:00Z&quot;,
        &quot;instructor&quot;: {
            &quot;id&quot;: 10,
            &quot;name&quot;: &quot;Jane Doe&quot;,
            &quot;email&quot;: &quot;jane@example.com&quot;
        },
        &quot;teachers&quot;: [],
        &quot;enrollment_status&quot;: &quot;not_enrolled&quot;,
        &quot;enrollment_date&quot;: null,
        &quot;progress_percentage&quot;: 0,
        &quot;lessons&quot;: [],
        &quot;lesson_sections&quot;: [],
        &quot;tasks&quot;: [],
        &quot;learning_paths&quot;: [],
        &quot;statistics&quot;: {
            &quot;total_lessons&quot;: 10,
            &quot;completed_lessons&quot;: 0,
            &quot;total_tasks&quot;: 5,
            &quot;completed_tasks&quot;: 0,
            &quot;total_enrolled_students&quot;: 0,
            &quot;average_completion_rate&quot;: 0
        }
    },
    &quot;pagination&quot;: {}
}</code>
 </pre>
            <blockquote>
            <p>Example response (403, Unpublished course for student):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;code&quot;: 403,
    &quot;message&quot;: &quot;Access denied. This course is not published.&quot;,
    &quot;data&quot;: [],
    &quot;pagination&quot;: {}
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, Course not found):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;code&quot;: 404,
    &quot;message&quot;: &quot;Course not found&quot;,
    &quot;data&quot;: [],
    &quot;pagination&quot;: {}
}</code>
 </pre>
    </span>
<span id="execution-results-GETcourses--courseId-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETcourses--courseId-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETcourses--courseId-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETcourses--courseId-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETcourses--courseId-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETcourses--courseId-" data-method="GET"
      data-path="courses/{courseId}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETcourses--courseId-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETcourses--courseId-"
                    onclick="tryItOut('GETcourses--courseId-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETcourses--courseId-"
                    onclick="cancelTryOut('GETcourses--courseId-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETcourses--courseId-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>courses/{courseId}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETcourses--courseId-"
               value="Bearer Bearer {token}"
               data-component="header">
    <br>
<p>Example: <code>Bearer Bearer {token}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETcourses--courseId-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETcourses--courseId-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>courseId</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="courseId"                data-endpoint="GETcourses--courseId-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the course to retrieve. Example: <code>1</code></p>
            </div>
                    </form>

                <h1 id="learning-paths">Learning Paths</h1>

    <p>Endpoints for browsing learning paths, viewing details, enrolling and tracking progress.</p>

                                <h2 id="learning-paths-GETlearning-paths">List learning paths</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Return a cursor-paginated list of learning paths accessible to the current user.</p>

<span id="example-requests-GETlearning-paths">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://api.learning-center-academy.local/learning-paths?per_page=15&amp;cursor=%22eyJpZCI6M30%22&amp;search=%22programming%22&amp;enrolled=%22enrolled%22" \
    --header "Authorization: Bearer Bearer {token}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://api.learning-center-academy.local/learning-paths"
);

const params = {
    "per_page": "15",
    "cursor": ""eyJpZCI6M30"",
    "search": ""programming"",
    "enrolled": ""enrolled"",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Authorization": "Bearer Bearer {token}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETlearning-paths">
            <blockquote>
            <p>Example response (200, Learning paths found):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;code&quot;: 200,
    &quot;message&quot;: &quot;Learning paths retrieved successfully&quot;,
    &quot;data&quot;: [
        {
            &quot;id&quot;: 1,
            &quot;name&quot;: &quot;Programming Basics&quot;,
            &quot;slug&quot;: &quot;programming-basics&quot;,
            &quot;description&quot;: &quot;Introductory programming path&quot;,
            &quot;banner_url&quot;: &quot;https://example.com/banners/programming-basics.png&quot;,
            &quot;is_active&quot;: true,
            &quot;total_estimated_time&quot;: 7200,
            &quot;courses_count&quot;: 3,
            &quot;is_enrolled&quot;: true,
            &quot;progress&quot;: 45,
            &quot;created_at&quot;: &quot;2024-01-01T12:00:00Z&quot;,
            &quot;updated_at&quot;: &quot;2024-01-02T12:00:00Z&quot;
        }
    ],
    &quot;pagination&quot;: {
        &quot;per_page&quot;: 15,
        &quot;next_cursor&quot;: &quot;eyJpZCI6M30&quot;,
        &quot;prev_cursor&quot;: null,
        &quot;has_more&quot;: true,
        &quot;count&quot;: 1
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (200, No learning paths):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;code&quot;: 200,
    &quot;message&quot;: &quot;No learning paths found&quot;,
    &quot;data&quot;: [],
    &quot;pagination&quot;: {}
}</code>
 </pre>
            <blockquote>
            <p>Example response (401, Missing or invalid tokens):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;code&quot;: 401,
    &quot;message&quot;: &quot;Unauthorized&quot;,
    &quot;data&quot;: [],
    &quot;pagination&quot;: {}
}</code>
 </pre>
            <blockquote>
            <p>Example response (403, Expired app token):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;code&quot;: 403,
    &quot;message&quot;: &quot;Forbidden&quot;,
    &quot;data&quot;: [],
    &quot;pagination&quot;: {}
}</code>
 </pre>
    </span>
<span id="execution-results-GETlearning-paths" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETlearning-paths"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETlearning-paths"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETlearning-paths" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETlearning-paths">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETlearning-paths" data-method="GET"
      data-path="learning-paths"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETlearning-paths', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETlearning-paths"
                    onclick="tryItOut('GETlearning-paths');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETlearning-paths"
                    onclick="cancelTryOut('GETlearning-paths');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETlearning-paths"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>learning-paths</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETlearning-paths"
               value="Bearer Bearer {token}"
               data-component="header">
    <br>
<p>Example: <code>Bearer Bearer {token}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETlearning-paths"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETlearning-paths"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETlearning-paths"
               value="15"
               data-component="query">
    <br>
<p>Number of items per page, maximum 50. Example: <code>15</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>cursor</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="cursor"                data-endpoint="GETlearning-paths"
               value=""eyJpZCI6M30""
               data-component="query">
    <br>
<p>The pagination cursor from the previous response. Example: <code>"eyJpZCI6M30"</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>search</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="search"                data-endpoint="GETlearning-paths"
               value=""programming""
               data-component="query">
    <br>
<p>Filter by learning path name or description. Example: <code>"programming"</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>enrolled</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="enrolled"                data-endpoint="GETlearning-paths"
               value=""enrolled""
               data-component="query">
    <br>
<p>Filter by enrollment status: all, enrolled, not_enrolled. Example: <code>"enrolled"</code></p>
            </div>
                </form>

                    <h2 id="learning-paths-GETlearning-paths--id-">Get learning path details</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Retrieve full details for a single learning path, including enrolled courses and user progress.</p>

<span id="example-requests-GETlearning-paths--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://api.learning-center-academy.local/learning-paths/1" \
    --header "Authorization: Bearer Bearer {token}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://api.learning-center-academy.local/learning-paths/1"
);

const headers = {
    "Authorization": "Bearer Bearer {token}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETlearning-paths--id-">
            <blockquote>
            <p>Example response (200, Learning path found):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;code&quot;: 200,
    &quot;message&quot;: &quot;Learning path details retrieved successfully&quot;,
    &quot;data&quot;: {
        &quot;id&quot;: 1,
        &quot;name&quot;: &quot;Programming Basics&quot;,
        &quot;slug&quot;: &quot;programming-basics&quot;,
        &quot;description&quot;: &quot;Introductory programming path&quot;,
        &quot;banner_url&quot;: &quot;https://example.com/banners/programming-basics.png&quot;,
        &quot;is_active&quot;: true,
        &quot;total_estimated_time&quot;: 7200,
        &quot;courses_count&quot;: 3,
        &quot;is_enrolled&quot;: true,
        &quot;progress&quot;: 45,
        &quot;courses&quot;: [],
        &quot;enrollment&quot;: {
            &quot;enrolled_at&quot;: &quot;2024-01-01T12:00:00Z&quot;,
            &quot;progress&quot;: 45,
            &quot;status&quot;: &quot;enrolled&quot;
        },
        &quot;created_at&quot;: &quot;2024-01-01T12:00:00Z&quot;,
        &quot;updated_at&quot;: &quot;2024-01-02T12:00:00Z&quot;
    },
    &quot;pagination&quot;: {}
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, Learning path not accessible):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;code&quot;: 404,
    &quot;message&quot;: &quot;Learning path not found or not accessible&quot;,
    &quot;data&quot;: [],
    &quot;pagination&quot;: {}
}</code>
 </pre>
    </span>
<span id="execution-results-GETlearning-paths--id-" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETlearning-paths--id-"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETlearning-paths--id-"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETlearning-paths--id-" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETlearning-paths--id-">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETlearning-paths--id-" data-method="GET"
      data-path="learning-paths/{id}"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETlearning-paths--id-', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETlearning-paths--id-"
                    onclick="tryItOut('GETlearning-paths--id-');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETlearning-paths--id-"
                    onclick="cancelTryOut('GETlearning-paths--id-');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETlearning-paths--id-"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>learning-paths/{id}</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETlearning-paths--id-"
               value="Bearer Bearer {token}"
               data-component="header">
    <br>
<p>Example: <code>Bearer Bearer {token}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETlearning-paths--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETlearning-paths--id-"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="GETlearning-paths--id-"
               value="1"
               data-component="url">
    <br>
<p>The ID of the learning path. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="learning-paths-POSTlearning-paths--id--enroll">Enroll in a learning path</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Enroll the authenticated user in the given learning path and its courses.</p>

<span id="example-requests-POSTlearning-paths--id--enroll">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://api.learning-center-academy.local/learning-paths/1/enroll" \
    --header "Authorization: Bearer Bearer {token}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://api.learning-center-academy.local/learning-paths/1/enroll"
);

const headers = {
    "Authorization": "Bearer Bearer {token}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTlearning-paths--id--enroll">
            <blockquote>
            <p>Example response (201, Enrollment created):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;code&quot;: 201,
    &quot;message&quot;: &quot;Successfully enrolled in learning path&quot;,
    &quot;data&quot;: {
        &quot;learning_path_id&quot;: 1,
        &quot;user_id&quot;: 1,
        &quot;enrolled_at&quot;: &quot;2024-01-01T12:00:00Z&quot;,
        &quot;progress&quot;: 0,
        &quot;status&quot;: &quot;enrolled&quot;,
        &quot;courses_enrolled&quot;: 3
    },
    &quot;pagination&quot;: {}
}</code>
 </pre>
            <blockquote>
            <p>Example response (400, Already enrolled):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;code&quot;: 400,
    &quot;message&quot;: &quot;You are already enrolled in this learning path&quot;,
    &quot;data&quot;: [],
    &quot;pagination&quot;: {}
}</code>
 </pre>
            <blockquote>
            <p>Example response (404, Learning path not accessible):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;code&quot;: 404,
    &quot;message&quot;: &quot;Learning path not found or not accessible&quot;,
    &quot;data&quot;: [],
    &quot;pagination&quot;: {}
}</code>
 </pre>
    </span>
<span id="execution-results-POSTlearning-paths--id--enroll" hidden>
    <blockquote>Received response<span
                id="execution-response-status-POSTlearning-paths--id--enroll"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-POSTlearning-paths--id--enroll"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-POSTlearning-paths--id--enroll" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-POSTlearning-paths--id--enroll">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-POSTlearning-paths--id--enroll" data-method="POST"
      data-path="learning-paths/{id}/enroll"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('POSTlearning-paths--id--enroll', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-POSTlearning-paths--id--enroll"
                    onclick="tryItOut('POSTlearning-paths--id--enroll');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-POSTlearning-paths--id--enroll"
                    onclick="cancelTryOut('POSTlearning-paths--id--enroll');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-POSTlearning-paths--id--enroll"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-black">POST</small>
            <b><code>learning-paths/{id}/enroll</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="POSTlearning-paths--id--enroll"
               value="Bearer Bearer {token}"
               data-component="header">
    <br>
<p>Example: <code>Bearer Bearer {token}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="POSTlearning-paths--id--enroll"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="POSTlearning-paths--id--enroll"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                        <h4 class="fancy-heading-panel"><b>URL Parameters</b></h4>
                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>id</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="id"                data-endpoint="POSTlearning-paths--id--enroll"
               value="1"
               data-component="url">
    <br>
<p>The ID of the learning path to enroll in. Example: <code>1</code></p>
            </div>
                    </form>

                    <h2 id="learning-paths-GETlearning-paths-progress-my">Get learning path progress</h2>

<p>
<small class="badge badge-darkred">requires authentication</small>
</p>

<p>Retrieve progress for the authenticated user across all enrolled learning paths.</p>

<span id="example-requests-GETlearning-paths-progress-my">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://api.learning-center-academy.local/learning-paths/progress/my?per_page=15&amp;cursor=%22eyJpZCI6M30%22" \
    --header "Authorization: Bearer Bearer {token}" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://api.learning-center-academy.local/learning-paths/progress/my"
);

const params = {
    "per_page": "15",
    "cursor": ""eyJpZCI6M30"",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Authorization": "Bearer Bearer {token}",
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETlearning-paths-progress-my">
            <blockquote>
            <p>Example response (200, Progress retrieved):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;code&quot;: 200,
    &quot;message&quot;: &quot;Learning path progress retrieved successfully&quot;,
    &quot;data&quot;: [
        {
            &quot;learning_path&quot;: {
                &quot;id&quot;: 1,
                &quot;name&quot;: &quot;Programming Basics&quot;,
                &quot;slug&quot;: &quot;programming-basics&quot;,
                &quot;banner_url&quot;: &quot;https://example.com/banners/programming-basics.png&quot;,
                &quot;total_estimated_time&quot;: 7200,
                &quot;courses_count&quot;: 3
            },
            &quot;enrollment&quot;: {
                &quot;enrolled_at&quot;: &quot;2024-01-01T12:00:00Z&quot;,
                &quot;progress&quot;: 45,
                &quot;status&quot;: &quot;enrolled&quot;
            },
            &quot;course_progress&quot;: []
        }
    ],
    &quot;pagination&quot;: {
        &quot;per_page&quot;: 15,
        &quot;next_cursor&quot;: &quot;eyJpZCI6M30&quot;,
        &quot;prev_cursor&quot;: null,
        &quot;has_more&quot;: true,
        &quot;count&quot;: 1
    }
}</code>
 </pre>
            <blockquote>
            <p>Example response (200, No enrollments):</p>
        </blockquote>
                <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;code&quot;: 200,
    &quot;message&quot;: &quot;No learning path enrollments found&quot;,
    &quot;data&quot;: [],
    &quot;pagination&quot;: {}
}</code>
 </pre>
    </span>
<span id="execution-results-GETlearning-paths-progress-my" hidden>
    <blockquote>Received response<span
                id="execution-response-status-GETlearning-paths-progress-my"></span>:
    </blockquote>
    <pre class="json"><code id="execution-response-content-GETlearning-paths-progress-my"
      data-empty-response-text="<Empty response>" style="max-height: 400px;"></code></pre>
</span>
<span id="execution-error-GETlearning-paths-progress-my" hidden>
    <blockquote>Request failed with error:</blockquote>
    <pre><code id="execution-error-message-GETlearning-paths-progress-my">

Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</code></pre>
</span>
<form id="form-GETlearning-paths-progress-my" data-method="GET"
      data-path="learning-paths/progress/my"
      data-authed="1"
      data-hasfiles="0"
      data-isarraybody="0"
      autocomplete="off"
      onsubmit="event.preventDefault(); executeTryOut('GETlearning-paths-progress-my', this);">
    <h3>
        Request&nbsp;&nbsp;&nbsp;
                    <button type="button"
                    style="background-color: #8fbcd4; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-tryout-GETlearning-paths-progress-my"
                    onclick="tryItOut('GETlearning-paths-progress-my');">Try it out ⚡
            </button>
            <button type="button"
                    style="background-color: #c97a7e; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-canceltryout-GETlearning-paths-progress-my"
                    onclick="cancelTryOut('GETlearning-paths-progress-my');" hidden>Cancel 🛑
            </button>&nbsp;&nbsp;
            <button type="submit"
                    style="background-color: #6ac174; padding: 5px 10px; border-radius: 5px; border-width: thin;"
                    id="btn-executetryout-GETlearning-paths-progress-my"
                    data-initial-text="Send Request 💥"
                    data-loading-text="⏱ Sending..."
                    hidden>Send Request 💥
            </button>
            </h3>
            <p>
            <small class="badge badge-green">GET</small>
            <b><code>learning-paths/progress/my</code></b>
        </p>
                <h4 class="fancy-heading-panel"><b>Headers</b></h4>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Authorization</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Authorization" class="auth-value"               data-endpoint="GETlearning-paths-progress-my"
               value="Bearer Bearer {token}"
               data-component="header">
    <br>
<p>Example: <code>Bearer Bearer {token}</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Content-Type</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Content-Type"                data-endpoint="GETlearning-paths-progress-my"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                                <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>Accept</code></b>&nbsp;&nbsp;
&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="Accept"                data-endpoint="GETlearning-paths-progress-my"
               value="application/json"
               data-component="header">
    <br>
<p>Example: <code>application/json</code></p>
            </div>
                            <h4 class="fancy-heading-panel"><b>Query Parameters</b></h4>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>per_page</code></b>&nbsp;&nbsp;
<small>integer</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="number" style="display: none"
               step="any"               name="per_page"                data-endpoint="GETlearning-paths-progress-my"
               value="15"
               data-component="query">
    <br>
<p>Number of items per page, maximum 50. Example: <code>15</code></p>
            </div>
                                    <div style="padding-left: 28px; clear: unset;">
                <b style="line-height: 2;"><code>cursor</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
<i>optional</i> &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="cursor"                data-endpoint="GETlearning-paths-progress-my"
               value=""eyJpZCI6M30""
               data-component="query">
    <br>
<p>The pagination cursor from the previous response. Example: <code>"eyJpZCI6M30"</code></p>
            </div>
                </form>

            

        
    </div>
    <div class="dark-box">
                    <div class="lang-selector">
                                                        <button type="button" class="lang-button" data-language-name="bash">bash</button>
                                                        <button type="button" class="lang-button" data-language-name="javascript">javascript</button>
                            </div>
            </div>
</div>
</body>
</html>
