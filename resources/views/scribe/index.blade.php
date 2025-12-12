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
                    <ul id="tocify-header-endpoints" class="tocify-header">
                <li class="tocify-item level-1" data-unique="endpoints">
                    <a href="#endpoints">Endpoints</a>
                </li>
                                    <ul id="tocify-subheader-endpoints" class="tocify-subheader">
                                                    <li class="tocify-item level-2" data-unique="endpoints-POSTlogin">
                                <a href="#endpoints-POSTlogin">POST login</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTrefresh">
                                <a href="#endpoints-POSTrefresh">POST refresh</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTlogout">
                                <a href="#endpoints-POSTlogout">POST logout</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETprofile">
                                <a href="#endpoints-GETprofile">GET profile</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETinstitution">
                                <a href="#endpoints-GETinstitution">GET institution</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETlearning-paths">
                                <a href="#endpoints-GETlearning-paths">GET learning-paths</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETlearning-paths--id-">
                                <a href="#endpoints-GETlearning-paths--id-">GET learning-paths/{id}</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-POSTlearning-paths--id--enroll">
                                <a href="#endpoints-POSTlearning-paths--id--enroll">POST learning-paths/{id}/enroll</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETlearning-paths-progress-my">
                                <a href="#endpoints-GETlearning-paths-progress-my">GET learning-paths/progress/my</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETcourses">
                                <a href="#endpoints-GETcourses">GET courses</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETcourses-search">
                                <a href="#endpoints-GETcourses-search">GET courses/search</a>
                            </li>
                                                                                <li class="tocify-item level-2" data-unique="endpoints-GETcourses--courseId-">
                                <a href="#endpoints-GETcourses--courseId-">GET courses/{courseId}</a>
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
<p>To authenticate requests, include a <strong><code>Authorization</code></strong> header with the value <strong><code>"Bearer {token}"</code></strong>.</p>
<p>All authenticated endpoints are marked with a <code>requires authentication</code> badge in the documentation below.</p>
<p>Protected endpoints require an Authorization: Bearer token and an APP_TOKEN header. Obtain both tokens from the POST /login endpoint.</p>

        <h1 id="endpoints">Endpoints</h1>

    

                                <h2 id="endpoints-POSTlogin">POST login</h2>

<p>
</p>



<span id="example-requests-POSTlogin">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://api.learning-center-academy.local/login" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"email\": \"gbailey@example.net\",
    \"password\": \"|]|{+-\"
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
    "email": "gbailey@example.net",
    "password": "|]|{+-"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTlogin">
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
               value="gbailey@example.net"
               data-component="body">
    <br>
<p>Must be a valid email address. Example: <code>gbailey@example.net</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="POSTlogin"
               value="|]|{+-"
               data-component="body">
    <br>
<p>Example: <code>|]|{+-</code></p>
        </div>
        </form>

                    <h2 id="endpoints-POSTrefresh">POST refresh</h2>

<p>
</p>



<span id="example-requests-POSTrefresh">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://api.learning-center-academy.local/refresh" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"email\": \"gbailey@example.net\",
    \"password\": \"|]|{+-\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://api.learning-center-academy.local/refresh"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "email": "gbailey@example.net",
    "password": "|]|{+-"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTrefresh">
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
      data-authed="0"
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
               value="gbailey@example.net"
               data-component="body">
    <br>
<p>Must be a valid email address. Example: <code>gbailey@example.net</code></p>
        </div>
                <div style=" padding-left: 28px;  clear: unset;">
            <b style="line-height: 2;"><code>password</code></b>&nbsp;&nbsp;
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="password"                data-endpoint="POSTrefresh"
               value="|]|{+-"
               data-component="body">
    <br>
<p>Example: <code>|]|{+-</code></p>
        </div>
        </form>

                    <h2 id="endpoints-POSTlogout">POST logout</h2>

<p>
</p>



<span id="example-requests-POSTlogout">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://api.learning-center-academy.local/logout" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://api.learning-center-academy.local/logout"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTlogout">
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
      data-authed="0"
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

                    <h2 id="endpoints-GETprofile">GET profile</h2>

<p>
</p>



<span id="example-requests-GETprofile">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://api.learning-center-academy.local/profile" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://api.learning-center-academy.local/profile"
);

const headers = {
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
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
set-cookie: XSRF-TOKEN=eyJpdiI6IlZoSHJGd1k1WHRQZkpiREJnK0QwSXc9PSIsInZhbHVlIjoiOG1odUNrMlJPaU42VWtUVEtJemNwOWRpeHNqbVJ1WFZOeGFkc0FySEhvS2N5TExrTGUwT3VtYlVPaFJaVGpFMXB4Vm9sNnI2UmJ6RWZVNjJsOEJDWGw2cEhKejhVa2F5dVRzN056OXg2UTJ6ckl3cXM5L3pQa0c1WWEwVXZadFMiLCJtYWMiOiJmNDg5YjQzM2QwZmI5ODQ2OGY5NmNiMWZhYzIxZDY1MjQ3ZmQ3NjEwMDkyNTRlNDFjN2U3MGQ2ODVlYTM3NTJkIiwidGFnIjoiIn0%3D; expires=Fri, 12 Dec 2025 17:37:20 GMT; Max-Age=7200; path=/; secure; samesite=lax; laravel-session=eyJpdiI6Im14R21KRG41TjMraVNvSVpRd2RwNXc9PSIsInZhbHVlIjoiOXhwZ2FCZ1dnOXZvb093L2N4UHhVbG96ajFDemFxUFhBd3F4cTZ2M0l4Zzd5YnJwc3lJWGZLcHZacnZwa29uSFFwSnAyUnZ2ZC9aWkV5NDh3NUpIa0F5dGFrZDZBcVBZTDMvNUY0M2c1WmliZkF6WjJmciswOGs3YmdnNGZiSUciLCJtYWMiOiI5NjU1MDk5NDNjNWZkOTk0OGY4NjJhMmQ1MDE1NmZlZjFiYWM1NjZmNjFlMmMwNWY2MzViYjUxMmE2M2I4NTk3IiwidGFnIjoiIn0%3D; expires=Fri, 12 Dec 2025 17:37:20 GMT; Max-Age=7200; path=/; secure; httponly; samesite=lax
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
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
      data-authed="0"
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

                    <h2 id="endpoints-GETinstitution">GET institution</h2>

<p>
</p>



<span id="example-requests-GETinstitution">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://api.learning-center-academy.local/institution" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://api.learning-center-academy.local/institution"
);

const headers = {
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
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
set-cookie: XSRF-TOKEN=eyJpdiI6IitaWkZRVkNGRHl4NllGeXJ6aXJpRlE9PSIsInZhbHVlIjoiSktRYnM2ZFVaQ2l6Umh0TWVpdVkwbUlRbnBtRE5DSDI3dk90M0VGKzNRWVJ6Z2FDNnUwbHozOUh3WGcyeHFCZXkzbHdvMkVmN3d1WllCMVhNcnU3ek5CRmxrRVl0aVJDMVNtNG5ISW9paUk1cjZKNGRrR0gxVlI5VVNrVUVnY3EiLCJtYWMiOiI4MzI4MmY0YTgyY2Q4NTg0Y2M4YjY3NWI4MDZlOTlmNDM3ZjMxMjE2YjYxYTE1ODc0OTZmMDUyYzljN2RjNjUxIiwidGFnIjoiIn0%3D; expires=Fri, 12 Dec 2025 17:37:20 GMT; Max-Age=7200; path=/; secure; samesite=lax; laravel-session=eyJpdiI6Im5qelBjYnRCWkVnODVlUFBnNEx5eGc9PSIsInZhbHVlIjoiVFJkWHRObUlhNkUxM3JvR0hwZGNHMmRXTGZrL3hWUll5RWRMYWpvQ1ljZU94Rzl3SU5oS2xZaVNsbktvdXAzN1pXOTlGYzZxVDBNZlI2cHlWUU9DVEFqbjlaVElkZG8zQ0NjYnk1ZzhVL3d6U2NLelp5enNoZFN3UEFXZ1hqYkIiLCJtYWMiOiI4YTkyMzU0NmNhNDcxYzczMjU1ZmI1YWI4ZWMyYzk3OWRjZjQzMDc4MDE3ZjM0YWYwYTg3ZjcyYTg2ZWQ1ZjZmIiwidGFnIjoiIn0%3D; expires=Fri, 12 Dec 2025 17:37:20 GMT; Max-Age=7200; path=/; secure; httponly; samesite=lax
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
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
      data-authed="0"
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

                    <h2 id="endpoints-GETlearning-paths">GET learning-paths</h2>

<p>
</p>



<span id="example-requests-GETlearning-paths">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://api.learning-center-academy.local/learning-paths" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://api.learning-center-academy.local/learning-paths"
);

const headers = {
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
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
set-cookie: XSRF-TOKEN=eyJpdiI6IklSbFdiQjdobGlCb3R0VUV6UmYxUVE9PSIsInZhbHVlIjoibWZqN3ByVDFHdUl0MUMxRHpqMkdBR2g0SFdMalk0Szh1Z2ppaldMc29XbTZZTlBOMWhSeVFxcjlaZGRGNmpScHFuSTN4bm1Wa20vWXhlSUttSzZnczhUTkVFTFZTeTg1ekVsMWdHaWpIa1R4WFVuamdmeGpvUUhnR25rbU1YNXMiLCJtYWMiOiIxNWExMDVjNWUxYjAwNDQzYzQzZWU2MDUxMzMyMDU3Y2M0ZGNjM2E5MDg1ZWE0MDQ3ZGE3NjMwZDFkMWNhMGNkIiwidGFnIjoiIn0%3D; expires=Fri, 12 Dec 2025 17:37:20 GMT; Max-Age=7200; path=/; secure; samesite=lax; laravel-session=eyJpdiI6InZNVHB5K2VPR0NoVzYrV0NkVGk3MlE9PSIsInZhbHVlIjoibGI0MzBTcEVqZnQ2V1hjYW5ua0dyZjFUK2lMTkVld3hvbE5RT1ZpZlZGQVZQVXVEVVk0REJ0Z2ZrZkJsUkJxZ0ZkQlpqMHYybCtFYktpRGdJYUtUMUZmSkNVMUNsTHVqRW9YTy8veFA5RVhIeHczazhJL2pYVUswcXJUYkpSVGkiLCJtYWMiOiIwMzUzMzgwZWE0OTA0Y2Y0MjZjOTdkYmNiODZmMDM4NDIxNzFmNGIzMDUxZjVhYmJlYmZjOGIxYTFhZmQxZThlIiwidGFnIjoiIn0%3D; expires=Fri, 12 Dec 2025 17:37:20 GMT; Max-Age=7200; path=/; secure; httponly; samesite=lax
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
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
      data-authed="0"
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
                        </form>

                    <h2 id="endpoints-GETlearning-paths--id-">GET learning-paths/{id}</h2>

<p>
</p>



<span id="example-requests-GETlearning-paths--id-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://api.learning-center-academy.local/learning-paths/architecto" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://api.learning-center-academy.local/learning-paths/architecto"
);

const headers = {
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
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
set-cookie: XSRF-TOKEN=eyJpdiI6ImF5azZ3dG9ZMmV3QWtUYTdiU1BqNVE9PSIsInZhbHVlIjoiWWtSY3FSdmxHS29OQkNUbzJoTlJGdjg5VitNWjBYN1RoNElveVBOTVhDSXBSSHBJS0NmaHd6TEtnVFBsdHJFLzFFSWYwLzNzZEN1OHVoUkd1YzhTK2hqZ1UvK3JVRzNRQXR6VmNzM3g1Uk9STjFPRVh1VVdmNnZMaEs4dVZMN2ciLCJtYWMiOiI4ZWNjYWZhOWFlMjBhZDAyOTE0N2E3Nzc1YTQyMGY0YTk0MmE2ZmU5NzhmM2Q4ZDRjMzQ0YTdkNWUzNjMxNjIzIiwidGFnIjoiIn0%3D; expires=Fri, 12 Dec 2025 17:37:20 GMT; Max-Age=7200; path=/; secure; samesite=lax; laravel-session=eyJpdiI6IklpOFJtRW9oZlpoQU12bG0ydmdQNVE9PSIsInZhbHVlIjoiblVXR2hxbnpjTy9BUlRCd3NPZVpBL0FseDNBTytIR01SSE9EWmdIeFFWWSt6dUhYUFZJd1Y0SkoyZUdYQUw5ZCtPUjNZeTR0ajRLOXlYazdNamVVTkZkMXNxbUJzVnpRSVlqeVF6Y3NaQ2MwNmJWZFhiMW16dndUMnU0UnVKNmwiLCJtYWMiOiJkZDljNzIyNmJjMDc0ODBhNTgyZDgxMGIwYWFjNmExY2Y3NjY1ZDY5NzNjODNkZWRjMmJlZDJjNjUxZDBjYTk1IiwidGFnIjoiIn0%3D; expires=Fri, 12 Dec 2025 17:37:20 GMT; Max-Age=7200; path=/; secure; httponly; samesite=lax
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
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
      data-authed="0"
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
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="GETlearning-paths--id-"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the learning path. Example: <code>architecto</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-POSTlearning-paths--id--enroll">POST learning-paths/{id}/enroll</h2>

<p>
</p>



<span id="example-requests-POSTlearning-paths--id--enroll">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request POST \
    "https://api.learning-center-academy.local/learning-paths/architecto/enroll" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://api.learning-center-academy.local/learning-paths/architecto/enroll"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "POST",
    headers,
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-POSTlearning-paths--id--enroll">
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
      data-authed="0"
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
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="id"                data-endpoint="POSTlearning-paths--id--enroll"
               value="architecto"
               data-component="url">
    <br>
<p>The ID of the learning path. Example: <code>architecto</code></p>
            </div>
                    </form>

                    <h2 id="endpoints-GETlearning-paths-progress-my">GET learning-paths/progress/my</h2>

<p>
</p>



<span id="example-requests-GETlearning-paths-progress-my">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://api.learning-center-academy.local/learning-paths/progress/my" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://api.learning-center-academy.local/learning-paths/progress/my"
);

const headers = {
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
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
set-cookie: XSRF-TOKEN=eyJpdiI6IjJoNTcyRHJBbDRuc1F3UkZhSlN1eHc9PSIsInZhbHVlIjoiUDRVWEx0S2xEZHd5bFV0UGxXRU9ONHB6UUFjb01OdnBkQ1JUYkxNVWNjdjdSdnBRclpqc3UzcHBPWWVPcDFDK1R6TXhXMW9DWkFUbXFIVVpnL1JZTW4rNm5vOFlmeVdES0VMOWdZWGEvb0hpRWdUWXZBU25kbUFzb05HWGxPUDYiLCJtYWMiOiI0NmIwZTRmMjgzZGM5MjFjNjg2YzM5OTIxZTI2MzRmYTUwMThkMmFhN2EzOTQyNjhlYTRkMDY1YWFiODYyNmYzIiwidGFnIjoiIn0%3D; expires=Fri, 12 Dec 2025 17:37:20 GMT; Max-Age=7200; path=/; secure; samesite=lax; laravel-session=eyJpdiI6InU1ajcreWxuNnRpUjJoSmF6T3dYOFE9PSIsInZhbHVlIjoicUdnMGpHWFFGWml4NSthQUJOWTVQOFhCeEtQQ2NuMFhDUUNvYTVIYmo4em5jMmxKVml3U21RQkg2ZjNWWk9Ua1pCRjVVcHI3QUJPUDF4eEh4b3hKWnJpVUJzNGJmOURjbytVamJzSDY4YnAvcFNoR0xOWkVOb2RPL3dLSzVRaUoiLCJtYWMiOiJkOWE3MGE1NDUzM2MwODcxYzU5ZTg2OTc1ZTU0ZjBmMDVkZTA1M2Q2NWIwZmE3ZGQyMzkzNjIwMDQxMmRkZTZkIiwidGFnIjoiIn0%3D; expires=Fri, 12 Dec 2025 17:37:20 GMT; Max-Age=7200; path=/; secure; httponly; samesite=lax
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
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
      data-authed="0"
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
                        </form>

                    <h2 id="endpoints-GETcourses">GET courses</h2>

<p>
</p>



<span id="example-requests-GETcourses">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://api.learning-center-academy.local/courses" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"page\": 16,
    \"per_page\": 22,
    \"sort\": \"estimated_time\",
    \"order\": \"desc\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://api.learning-center-academy.local/courses"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "page": 16,
    "per_page": 22,
    "sort": "estimated_time",
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
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
set-cookie: XSRF-TOKEN=eyJpdiI6IkZiaW4wdmtQQ3hwVXdOaXNIWVRPVXc9PSIsInZhbHVlIjoiVGdPcHNGc0xzbXdVaU1jVHRxckU3MHR4bUc1TzdXSy9hS0drNndxYTh3OXQ4Z3NtR0Y1K05xRFFUQ00xNjdrdzY0NFhWNFRVYjEvSnh6OUhVbTVEb1BLQTVLTGcxNXgyY3BRVHo2THM4eUlpdGN6WDBEMmJZbXJnazdrQitETlUiLCJtYWMiOiI2YTc2OTIzMjA0Yjk1NjRjMjk0YWI1NTFiYjNmMDllMzU1NjJmMzUxYTJlNzE1YTJkODY0M2Q1NzUxYmMwZTZjIiwidGFnIjoiIn0%3D; expires=Fri, 12 Dec 2025 17:37:20 GMT; Max-Age=7200; path=/; secure; samesite=lax; laravel-session=eyJpdiI6Ijd6TDZHRTc1eDB1eDJhbHhuM00rS2c9PSIsInZhbHVlIjoibnptR055a0NuU0ljU2dKY2FGMGpxODFpUmxBVDFvQ3VNek96Rll5cTc4RzFobjhhMXN3OStIZm1uNTJZY2FDWkNtakZHUWlsQVJVZktyYjhzTGJlbVFRWDRiaVZPY3VrZUJ2QmlROFRoNUFvMTM4Tm0zZjRxL3hsQnd0SGVYNU8iLCJtYWMiOiI0Njc0NzFjMDc4MGE0M2E3MWFhMTk1NzUxMmI4NzhmMmE3Yjk4ZmM3MGU3MmU3NDdkMjE5YThjZWViMWZlOGI2IiwidGFnIjoiIn0%3D; expires=Fri, 12 Dec 2025 17:37:20 GMT; Max-Age=7200; path=/; secure; httponly; samesite=lax
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
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
      data-authed="0"
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
               value="estimated_time"
               data-component="body">
    <br>
<p>Example: <code>estimated_time</code></p>
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

                    <h2 id="endpoints-GETcourses-search">GET courses/search</h2>

<p>
</p>



<span id="example-requests-GETcourses-search">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://api.learning-center-academy.local/courses/search" \
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
    \"sort\": \"title\",
    \"order\": \"desc\"
}"
</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://api.learning-center-academy.local/courses/search"
);

const headers = {
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
    "sort": "title",
    "order": "desc"
};

fetch(url, {
    method: "GET",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre></div>

</span>

<span id="example-responses-GETcourses-search">
            <blockquote>
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
set-cookie: XSRF-TOKEN=eyJpdiI6Im9JNHZCM0FGc29Ja2hraGlIVlE0cEE9PSIsInZhbHVlIjoiQmlFRVZYSWpXYTV5T281VG5IQUlrc3V0UWpiZWpmUExPWkpFQW16a3d4aCtFMzFxRXNnZVdJajJkZEdQZEZJcFczbEdhUEFNWnB5K2krUGp3Q2txaVZxNy8rdm5SbzVmcERsZjhUOUVtQi9rTGZUY1ErT20rOERva2RUSlMrTEMiLCJtYWMiOiJmOWVkNmMzZDI2MDc3MGMyZDY1Mjg4MzhlYjlkM2VmYjgxYjExYzFiMTQ4MzA1NWNlZThmOWI2YzA3Yjg5YjYxIiwidGFnIjoiIn0%3D; expires=Fri, 12 Dec 2025 17:37:20 GMT; Max-Age=7200; path=/; secure; samesite=lax; laravel-session=eyJpdiI6IkNOYXJrRkpBTXp1YXFKVVVHTk41b1E9PSIsInZhbHVlIjoiQ0c1ek4waGN6bXNCVDlhKzJFdUNEWEo5QjYrWjQ2eU8vSDYra1c2bFRITWJpYWsyVVgzcitpalBBNmhFVmh3MnUrQUNoeXIvUmxQSmR6cnMwNU5yR0thMFBNRHdTVFBWS0RIQ3VCZy96Z1VRNU5LTVJlZVptNFB3d2IvcFNhQnMiLCJtYWMiOiJmNmMzMDdlNWQyNmFmYWE4MWNmNDE2YjI1Mzg5YjUzZjRkZjRjNDg3MDZiMDNlNDE4OWFjYmM4NTEzYWNhMDYwIiwidGFnIjoiIn0%3D; expires=Fri, 12 Dec 2025 17:37:20 GMT; Max-Age=7200; path=/; secure; httponly; samesite=lax
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
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
      data-authed="0"
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
               value="title"
               data-component="body">
    <br>
<p>Example: <code>title</code></p>
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
               value="desc"
               data-component="body">
    <br>
<p>Example: <code>desc</code></p>
Must be one of:
<ul style="list-style-type: square;"><li><code>asc</code></li> <li><code>desc</code></li></ul>
        </div>
        </form>

                    <h2 id="endpoints-GETcourses--courseId-">GET courses/{courseId}</h2>

<p>
</p>



<span id="example-requests-GETcourses--courseId-">
<blockquote>Example request:</blockquote>


<div class="bash-example">
    <pre><code class="language-bash">curl --request GET \
    --get "https://api.learning-center-academy.local/courses/architecto" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre></div>


<div class="javascript-example">
    <pre><code class="language-javascript">const url = new URL(
    "https://api.learning-center-academy.local/courses/architecto"
);

const headers = {
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
            <p>Example response (401):</p>
        </blockquote>
                <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">cache-control: no-cache, private
content-type: application/json
set-cookie: XSRF-TOKEN=eyJpdiI6IkFlSVY1QmExVGt1MzVNZU9jSjl4OWc9PSIsInZhbHVlIjoiOWdMRk9Dc21aU3M0Z1piSmJJZVdDb1luQWFyQ2lYWFlxRUNESFNOZmxKL01FdlByTHVSTTRvSjhoSUZDbGJ4bGFqbjhGcldBODNtWGYrZWhFbjhDRk5uNGlHU3lqRytjaVc5dnZ3b0RlYlRnaldBaklXaTl3VjdZWmdpc0ZoYXAiLCJtYWMiOiJhMmFlYTdjYTk0MTE1MDM0OTJlMzViZTk2OGE3MTJmZTZmNWNhZTM4OTVhOWVhZDdiMmFiMDU4YWJlNzBjMzNhIiwidGFnIjoiIn0%3D; expires=Fri, 12 Dec 2025 17:37:20 GMT; Max-Age=7200; path=/; secure; samesite=lax; laravel-session=eyJpdiI6IkZrMmFMNXdEcVMrUHFmME9UNEsrM3c9PSIsInZhbHVlIjoiUUZVYTRKNHhBazBqeVJzdEZYK1kzRzJJTkc0Rm1kRllIRzRLT3d5QXhDQkZtUVBWbk5JQ21rQk1rNGxUV1I2QjFNV3Y0TGNkWFh3cUk3WE9hKzJQVWU2TllqTFhVcDl6YUJ3VXRjYVZIUmRlUVV6NEtMWmV0NzZMZm52OU9YOVIiLCJtYWMiOiI2ZTZjMDE1Mzc2ZDVlMTFjOGMwMzc0M2IxNDNhNGU5ZTUyOTk0ZjJiZjQwZTVkM2RhNDRmMTYxMDRiYTQ5YWFhIiwidGFnIjoiIn0%3D; expires=Fri, 12 Dec 2025 17:37:20 GMT; Max-Age=7200; path=/; secure; httponly; samesite=lax
 </code></pre></details>         <pre>

<code class="language-json" style="max-height: 300px;">{
    &quot;message&quot;: &quot;Unauthenticated.&quot;
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
      data-authed="0"
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
<small>string</small>&nbsp;
 &nbsp;
 &nbsp;
                <input type="text" style="display: none"
                              name="courseId"                data-endpoint="GETcourses--courseId-"
               value="architecto"
               data-component="url">
    <br>
<p>Example: <code>architecto</code></p>
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
