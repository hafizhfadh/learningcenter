# CORS Configuration

## Overview
This Laravel application has been configured to allow Cross-Origin Resource Sharing (CORS) for localhost development environments, specifically to support React Vite SWC frontend applications running on different ports.

In production, CORS is controlled via the `CORS_ALLOWED_ORIGINS` environment variable so that only explicitly trusted HTTPS origins can access the API.

## Configuration Details

### File: `config/cors.php`

The CORS configuration includes:

- **Paths**: `['api/*', 'sanctum/csrf-cookie']` - Applies CORS to all API routes and Sanctum CSRF cookie endpoint
- **Allowed Methods**: `['*']` - All HTTP methods are allowed
- **Allowed Origins**: Environment-dependent:
  - **Development**: `['*']` - All origins allowed for development flexibility
  - **Production**: `[]` - No wildcard origins in production for security
- **Allowed Origins Patterns**: 
  - `http://localhost:*` - Any HTTP localhost port
  - `https://localhost:*` - Any HTTPS localhost port  
  - `http://127.0.0.1:*` - Any HTTP 127.0.0.1 port
  - `https://127.0.0.1:*` - Any HTTPS 127.0.0.1 port
- **Allowed Headers**: `['*']` - All headers allowed
- **Supports Credentials**: `true` - Enables cookie/authentication support

## Frontend Integration

### React Vite SWC Setup
Your React application can now make requests to the Laravel API from any localhost port:

```javascript
// Example API call from React (localhost:3000, localhost:5173, etc.)
const response = await fetch('http://localhost/api/courses', {
  method: 'GET',
  credentials: 'include', // Important for authentication
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  }
});
```

### Authentication with Sanctum
For authenticated requests, ensure you include credentials:

```javascript
// Login request
const loginResponse = await fetch('http://localhost/api/login', {
  method: 'POST',
  credentials: 'include',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  body: JSON.stringify({
    email: 'user@example.com',
    password: 'password'
  })
});

// Subsequent authenticated requests
const dataResponse = await fetch('http://localhost/api/protected-endpoint', {
  method: 'GET',
  credentials: 'include', // This sends the authentication cookie
  headers: {
    'Accept': 'application/json',
  }
});
```

## Security Considerations

1. **Production Environment**: The configuration automatically restricts origins in production, and you should explicitly configure:
   - `FORCE_HTTPS=true`
   - `CORS_ALLOWED_ORIGINS` with your HTTPS frontend origins (for example `https://app.your-domain.com`)
2. **Credentials Support**: Enabled to support Sanctum authentication cookies
3. **Pattern Matching**: Uses specific localhost patterns instead of wildcard for better security
4. **Header Control**: While all headers are allowed in development, consider restricting in production

## Testing CORS

You can test CORS functionality using curl:

```bash
# Test preflight request
curl -H "Origin: http://localhost:3000" \
     -H "Access-Control-Request-Method: GET" \
     -H "Access-Control-Request-Headers: X-Requested-With" \
     -X OPTIONS http://localhost/api/courses -v

# Test actual request
curl -H "Origin: http://localhost:3000" \
     http://localhost/api/courses -v
```

## Common Vite Ports
The configuration supports common Vite development ports:
- `localhost:3000` - Create React App default
- `localhost:5173` - Vite default port
- `localhost:4173` - Vite preview port
- Any other localhost port your React app uses

## Troubleshooting

If you encounter CORS issues:

1. **Check Origin**: Ensure your frontend is running on `localhost` or `127.0.0.1`
2. **Verify Credentials**: Include `credentials: 'include'` in fetch requests
3. **Clear Cache**: Restart both Laravel and React development servers
4. **Check Headers**: Ensure proper `Content-Type` and `Accept` headers
5. **Environment**: Verify `APP_ENV` setting affects origin restrictions
