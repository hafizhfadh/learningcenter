# Security Guide

This comprehensive security guide covers application security, infrastructure hardening, and security best practices for the Laravel application.

## Table of Contents

- [Security Overview](#security-overview)
- [Application Security](#application-security)
- [Infrastructure Security](#infrastructure-security)
- [Authentication & Authorization](#authentication--authorization)
- [Data Protection](#data-protection)
- [Security Monitoring](#security-monitoring)
- [Incident Response](#incident-response)
- [Compliance](#compliance)
- [Security Checklist](#security-checklist)

## Security Overview

### Security Architecture

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│   Firewall  │───▶│Load Balancer│───▶│  Web Server │
│    (UFW)    │    │   (Nginx)   │    │   (Nginx)   │
└─────────────┘    └─────────────┘    └─────────────┘
                                              │
                                              ▼
                                      ┌─────────────┐
                                      │ Application │
                                      │  (Laravel)  │
                                      └─────────────┘
                                              │
                                              ▼
                                      ┌─────────────┐
                                      │  Database   │
                                      │(PostgreSQL) │
                                      └─────────────┘
```

### Security Layers

1. **Network Security**: Firewall, VPN, Network segmentation
2. **Infrastructure Security**: Server hardening, Container security
3. **Application Security**: Input validation, Authentication, Authorization
4. **Data Security**: Encryption, Backup security, Access controls
5. **Monitoring Security**: Intrusion detection, Log analysis, Alerting

## Application Security

### Laravel Security Features

#### 1. CSRF Protection

```php
// Enabled by default in web middleware
// resources/views/layouts/app.blade.php
<meta name="csrf-token" content="{{ csrf_token() }}">

// In forms
<form method="POST" action="/profile">
    @csrf
    <!-- form fields -->
</form>

// In AJAX requests
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
```

#### 2. SQL Injection Prevention

```php
// Use Eloquent ORM (automatically protected)
$users = User::where('email', $email)->get();

// Use parameter binding for raw queries
$users = DB::select('SELECT * FROM users WHERE email = ?', [$email]);

// Never do this (vulnerable to SQL injection)
// $users = DB::select("SELECT * FROM users WHERE email = '$email'");
```

#### 3. XSS Protection

```php
// Blade templates automatically escape output
{{ $user->name }} // Safe - automatically escaped

// Raw output (use with caution)
{!! $trustedHtml !!} // Dangerous - only for trusted content

// Manual escaping
{{ e($userInput) }}

// Content Security Policy
// config/secure-headers.php
'csp' => [
    'default-src' => "'self'",
    'script-src' => "'self' 'unsafe-inline'",
    'style-src' => "'self' 'unsafe-inline'",
    'img-src' => "'self' data: https:",
],
```

#### 4. Input Validation

```php
// Form Request Validation
class UserRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string|max:255|regex:/^[a-zA-Z\s]+$/',
            'email' => 'required|email|unique:users,email,' . $this->user?->id,
            'password' => 'required|string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
            'phone' => 'nullable|regex:/^\+?[1-9]\d{1,14}$/',
            'website' => 'nullable|url|max:255',
        ];
    }
    
    public function messages()
    {
        return [
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
            'name.regex' => 'Name can only contain letters and spaces.',
        ];
    }
}

// Sanitization
class SanitizeInput
{
    public static function sanitize($input)
    {
        if (is_string($input)) {
            // Remove potentially dangerous characters
            $input = strip_tags($input);
            $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
            $input = trim($input);
        }
        
        return $input;
    }
}
```

#### 5. File Upload Security

```php
// File Upload Validation
class FileUploadRequest extends FormRequest
{
    public function rules()
    {
        return [
            'avatar' => [
                'required',
                'file',
                'mimes:jpeg,png,jpg,gif',
                'max:2048', // 2MB
                'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000',
                function ($attribute, $value, $fail) {
                    // Additional security checks
                    $this->validateFileContent($value, $fail);
                },
            ],
        ];
    }
    
    private function validateFileContent($file, $fail)
    {
        // Check file signature
        $allowedSignatures = [
            'image/jpeg' => [0xFF, 0xD8, 0xFF],
            'image/png' => [0x89, 0x50, 0x4E, 0x47],
        ];
        
        $fileContent = file_get_contents($file->getPathname());
        $fileSignature = array_values(unpack('C3', substr($fileContent, 0, 3)));
        
        $mimeType = $file->getMimeType();
        if (!isset($allowedSignatures[$mimeType]) || 
            $fileSignature !== $allowedSignatures[$mimeType]) {
            $fail('Invalid file format detected.');
        }
    }
}

// Secure file storage
class FileUploadService
{
    public function store($file, $directory = 'uploads')
    {
        // Generate secure filename
        $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();
        
        // Store outside web root
        $path = storage_path("app/secure/{$directory}");
        
        // Create directory if it doesn't exist
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }
        
        // Move file
        $file->move($path, $filename);
        
        return "{$directory}/{$filename}";
    }
    
    public function serve($path)
    {
        $fullPath = storage_path("app/secure/{$path}");
        
        if (!File::exists($fullPath)) {
            abort(404);
        }
        
        // Validate user has permission to access file
        if (!$this->userCanAccessFile($path)) {
            abort(403);
        }
        
        return response()->file($fullPath);
    }
}
```

### Security Headers

```php
// app/Http/Middleware/SecurityHeaders.php
class SecurityHeaders
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        // Security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        
        // HSTS (only for HTTPS)
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }
        
        // Content Security Policy
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " .
               "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
               "font-src 'self' https://fonts.gstatic.com; " .
               "img-src 'self' data: https:; " .
               "connect-src 'self'; " .
               "frame-ancestors 'none';";
        
        $response->headers->set('Content-Security-Policy', $csp);
        
        return $response;
    }
}
```

### Rate Limiting

```php
// config/rate-limiting.php
return [
    'api' => [
        'attempts' => 60,
        'decay_minutes' => 1,
    ],
    'login' => [
        'attempts' => 5,
        'decay_minutes' => 15,
    ],
    'password_reset' => [
        'attempts' => 3,
        'decay_minutes' => 60,
    ],
];

// Custom rate limiter
class CustomRateLimiter
{
    public function handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1)
    {
        $key = $this->resolveRequestSignature($request);
        
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            
            // Log rate limit exceeded
            Log::warning('Rate limit exceeded', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'retry_after' => $seconds,
            ]);
            
            return response()->json([
                'message' => 'Too many requests',
                'retry_after' => $seconds,
            ], 429);
        }
        
        RateLimiter::hit($key, $decayMinutes * 60);
        
        $response = $next($request);
        
        return $this->addHeaders(
            $response,
            $maxAttempts,
            RateLimiter::retriesLeft($key, $maxAttempts)
        );
    }
}
```

## Infrastructure Security

### Server Hardening

#### 1. SSH Security

```bash
# /etc/ssh/sshd_config
Port 2222                          # Change default port
Protocol 2                         # Use SSH protocol 2
PermitRootLogin no                 # Disable root login
PasswordAuthentication no          # Disable password authentication
PubkeyAuthentication yes           # Enable public key authentication
AuthorizedKeysFile .ssh/authorized_keys
PermitEmptyPasswords no           # Disable empty passwords
X11Forwarding no                  # Disable X11 forwarding
MaxAuthTries 3                    # Limit authentication attempts
ClientAliveInterval 300           # Client alive interval
ClientAliveCountMax 2             # Client alive count max
AllowUsers deployer               # Allow specific users only
DenyUsers root                    # Deny specific users
```

#### 2. Firewall Configuration

```bash
#!/bin/bash
# UFW Firewall Rules

# Reset UFW
ufw --force reset

# Default policies
ufw default deny incoming
ufw default allow outgoing

# SSH (custom port)
ufw allow 2222/tcp

# HTTP/HTTPS
ufw allow 80/tcp
ufw allow 443/tcp

# Rate limiting for SSH
ufw limit 2222/tcp

# Application specific ports (restrict to internal network)
ufw allow from 10.0.0.0/8 to any port 3000  # Grafana
ufw allow from 10.0.0.0/8 to any port 9090  # Prometheus
ufw allow from 10.0.0.0/8 to any port 9093  # Alertmanager

# Block common attack vectors
ufw deny 23      # Telnet
ufw deny 135     # RPC
ufw deny 137:139 # NetBIOS
ufw deny 445     # SMB
ufw deny 1433    # MSSQL
ufw deny 3389    # RDP

# Enable UFW
ufw --force enable
```

#### 3. Fail2ban Configuration

```ini
# /etc/fail2ban/jail.local
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 3
backend = systemd
banaction = ufw
ignoreip = 127.0.0.1/8 10.0.0.0/8 192.168.0.0/16

[sshd]
enabled = true
port = 2222
filter = sshd
logpath = /var/log/auth.log
maxretry = 3
bantime = 86400

[nginx-http-auth]
enabled = true
filter = nginx-http-auth
logpath = /var/log/nginx/error.log
maxretry = 3

[nginx-noscript]
enabled = true
filter = nginx-noscript
logpath = /var/log/nginx/access.log
maxretry = 6

[nginx-badbots]
enabled = true
filter = nginx-badbots
logpath = /var/log/nginx/access.log
maxretry = 2

[laravel-auth]
enabled = true
filter = laravel-auth
logpath = /var/log/laravel/laravel.log
maxretry = 5
bantime = 1800

[laravel-api]
enabled = true
filter = laravel-api
logpath = /var/log/laravel/laravel.log
maxretry = 10
bantime = 3600
```

### Container Security

#### 1. Docker Security

```dockerfile
# Use specific version tags
FROM php:8.2-fpm-alpine3.18

# Create non-root user
RUN addgroup -g 1001 -S appgroup && \
    adduser -u 1001 -S appuser -G appgroup

# Install security updates
RUN apk update && apk upgrade

# Remove unnecessary packages
RUN apk del --purge wget curl

# Set proper file permissions
COPY --chown=appuser:appgroup . /var/www/html

# Use non-root user
USER appuser

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD php artisan health:check || exit 1
```

#### 2. Docker Compose Security

```yaml
# docker-compose.yml
version: '3.8'

services:
  app:
    build: .
    user: "1001:1001"
    read_only: true
    tmpfs:
      - /tmp
      - /var/tmp
    volumes:
      - ./storage:/var/www/html/storage:rw
    cap_drop:
      - ALL
    cap_add:
      - CHOWN
      - SETGID
      - SETUID
    security_opt:
      - no-new-privileges:true
      - apparmor:docker-laravel
    networks:
      - app-network

  postgres:
    image: postgres:15-alpine
    environment:
      POSTGRES_PASSWORD_FILE: /run/secrets/postgres_password
    secrets:
      - postgres_password
    volumes:
      - postgres_data:/var/lib/postgresql/data:rw
    cap_drop:
      - ALL
    cap_add:
      - CHOWN
      - SETGID
      - SETUID
      - DAC_OVERRIDE
    security_opt:
      - no-new-privileges:true

secrets:
  postgres_password:
    file: ./secrets/postgres_password.txt

networks:
  app-network:
    driver: bridge
    internal: true
```

#### 3. AppArmor Profiles

```bash
# /etc/apparmor.d/docker-laravel
#include <tunables/global>

profile docker-laravel flags=(attach_disconnected,mediate_deleted) {
  #include <abstractions/base>
  
  # Capabilities
  capability chown,
  capability setgid,
  capability setuid,
  capability dac_override,
  
  # Network access
  network inet tcp,
  network inet udp,
  
  # File system access
  /var/www/html/ r,
  /var/www/html/** r,
  /var/www/html/storage/** rw,
  /var/www/html/bootstrap/cache/** rw,
  
  # PHP and system files
  /usr/local/bin/php ix,
  /usr/local/lib/php/** r,
  /etc/php/** r,
  /tmp/** rw,
  /var/tmp/** rw,
  
  # Deny dangerous operations
  deny /proc/sys/** w,
  deny /sys/** w,
  deny mount,
  deny umount,
  deny ptrace,
  deny capability sys_admin,
  deny capability sys_module,
}
```

### SSL/TLS Configuration

#### 1. Nginx SSL Configuration

```nginx
# /etc/nginx/sites-available/laravel-ssl
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com;
    
    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    ssl_trusted_certificate /etc/letsencrypt/live/yourdomain.com/chain.pem;
    
    # SSL Security
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    ssl_session_tickets off;
    
    # OCSP Stapling
    ssl_stapling on;
    ssl_stapling_verify on;
    resolver 8.8.8.8 8.8.4.4 valid=300s;
    resolver_timeout 5s;
    
    # Security Headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    
    # Application configuration
    root /var/www/html/public;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Security
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 300;
    }
    
    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }
    
    location ~ \.(env|log|htaccess)$ {
        deny all;
    }
}

# Redirect HTTP to HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com;
    return 301 https://$server_name$request_uri;
}
```

## Authentication & Authorization

### Multi-Factor Authentication

```php
// app/Models/User.php
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    use TwoFactorAuthenticatable;
    
    protected $fillable = [
        'name', 'email', 'password', 'two_factor_enabled',
    ];
    
    protected $hidden = [
        'password', 'remember_token', 'two_factor_recovery_codes', 'two_factor_secret',
    ];
    
    protected $casts = [
        'email_verified_at' => 'datetime',
        'two_factor_enabled' => 'boolean',
    ];
}

// Two-Factor Authentication Service
class TwoFactorService
{
    public function enable(User $user)
    {
        $user->forceFill([
            'two_factor_secret' => encrypt(app(TwoFactorAuthenticationProvider::class)->generateSecretKey()),
            'two_factor_recovery_codes' => encrypt(json_encode(Collection::times(8, function () {
                return RecoveryCode::generate();
            })->all())),
        ])->save();
        
        $user->two_factor_enabled = true;
        $user->save();
        
        // Log security event
        Log::info('Two-factor authentication enabled', [
            'user_id' => $user->id,
            'ip_address' => request()->ip(),
        ]);
    }
    
    public function verify(User $user, string $code): bool
    {
        $verified = app(TwoFactorAuthenticationProvider::class)->verify(
            decrypt($user->two_factor_secret),
            $code
        );
        
        if (!$verified) {
            // Log failed attempt
            Log::warning('Two-factor authentication failed', [
                'user_id' => $user->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }
        
        return $verified;
    }
}
```

### Role-Based Access Control

```php
// app/Models/Role.php
class Role extends Model
{
    protected $fillable = ['name', 'description'];
    
    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }
    
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}

// app/Models/Permission.php
class Permission extends Model
{
    protected $fillable = ['name', 'description'];
    
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}

// Authorization Middleware
class RoleMiddleware
{
    public function handle($request, Closure $next, ...$roles)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        
        $user = auth()->user();
        
        if (!$user->hasAnyRole($roles)) {
            Log::warning('Unauthorized access attempt', [
                'user_id' => $user->id,
                'required_roles' => $roles,
                'user_roles' => $user->roles->pluck('name')->toArray(),
                'url' => $request->fullUrl(),
                'ip_address' => $request->ip(),
            ]);
            
            abort(403, 'Unauthorized');
        }
        
        return $next($request);
    }
}

// User model with roles
class User extends Authenticatable
{
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
    
    public function hasRole($role)
    {
        return $this->roles->contains('name', $role);
    }
    
    public function hasAnyRole($roles)
    {
        return $this->roles->whereIn('name', $roles)->isNotEmpty();
    }
    
    public function hasPermission($permission)
    {
        return $this->roles->flatMap->permissions->contains('name', $permission);
    }
}
```

### Session Security

```php
// config/session.php
return [
    'lifetime' => 120, // 2 hours
    'expire_on_close' => true,
    'encrypt' => true,
    'files' => storage_path('framework/sessions'),
    'connection' => env('SESSION_CONNECTION', 'redis'),
    'table' => 'sessions',
    'store' => env('SESSION_STORE', 'redis'),
    'lottery' => [2, 100], // 2% chance of garbage collection
    'cookie' => env('SESSION_COOKIE', 'laravel_session'),
    'path' => '/',
    'domain' => env('SESSION_DOMAIN', null),
    'secure' => env('SESSION_SECURE_COOKIE', true),
    'http_only' => true,
    'same_site' => 'strict',
];

// Session Security Middleware
class SessionSecurity
{
    public function handle($request, Closure $next)
    {
        // Regenerate session ID on login
        if ($request->user() && !$request->session()->has('user_authenticated')) {
            $request->session()->regenerate();
            $request->session()->put('user_authenticated', true);
        }
        
        // Check for session hijacking
        $this->validateSession($request);
        
        return $next($request);
    }
    
    private function validateSession($request)
    {
        $session = $request->session();
        
        // Check IP address consistency
        if ($session->has('ip_address') && $session->get('ip_address') !== $request->ip()) {
            Log::warning('Session IP address mismatch', [
                'session_ip' => $session->get('ip_address'),
                'request_ip' => $request->ip(),
                'user_id' => auth()->id(),
            ]);
            
            auth()->logout();
            $session->invalidate();
            abort(401, 'Session security violation');
        }
        
        // Store IP address for future validation
        $session->put('ip_address', $request->ip());
        
        // Check user agent consistency
        if ($session->has('user_agent') && $session->get('user_agent') !== $request->userAgent()) {
            Log::warning('Session user agent mismatch', [
                'session_ua' => $session->get('user_agent'),
                'request_ua' => $request->userAgent(),
                'user_id' => auth()->id(),
            ]);
        }
        
        $session->put('user_agent', $request->userAgent());
    }
}
```

## Data Protection

### Encryption

```php
// Database Encryption
class EncryptedAttribute
{
    public static function encrypt($value)
    {
        return encrypt($value);
    }
    
    public static function decrypt($value)
    {
        try {
            return decrypt($value);
        } catch (DecryptException $e) {
            return null;
        }
    }
}

// Model with encrypted attributes
class User extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'ssn'];
    
    protected $casts = [
        'phone' => 'encrypted',
        'ssn' => 'encrypted',
    ];
    
    public function setPhoneAttribute($value)
    {
        $this->attributes['phone'] = EncryptedAttribute::encrypt($value);
    }
    
    public function getPhoneAttribute($value)
    {
        return EncryptedAttribute::decrypt($value);
    }
}

// File Encryption Service
class FileEncryptionService
{
    public function encryptFile($filePath, $key = null)
    {
        $key = $key ?: config('app.key');
        $data = file_get_contents($filePath);
        $encrypted = encrypt($data);
        
        file_put_contents($filePath . '.encrypted', $encrypted);
        unlink($filePath); // Remove original file
        
        return $filePath . '.encrypted';
    }
    
    public function decryptFile($encryptedFilePath, $key = null)
    {
        $key = $key ?: config('app.key');
        $encrypted = file_get_contents($encryptedFilePath);
        $decrypted = decrypt($encrypted);
        
        $originalPath = str_replace('.encrypted', '', $encryptedFilePath);
        file_put_contents($originalPath, $decrypted);
        
        return $originalPath;
    }
}
```

### Data Anonymization

```php
// Data Anonymization Service
class DataAnonymizationService
{
    public function anonymizeUser(User $user)
    {
        $user->update([
            'name' => 'Anonymous User ' . $user->id,
            'email' => 'anonymous' . $user->id . '@example.com',
            'phone' => null,
            'address' => null,
            'date_of_birth' => null,
        ]);
        
        // Log anonymization
        Log::info('User data anonymized', [
            'user_id' => $user->id,
            'anonymized_at' => now(),
            'requested_by' => auth()->id(),
        ]);
    }
    
    public function anonymizeOldData()
    {
        // Anonymize users who haven't logged in for 2 years
        $oldUsers = User::where('last_login_at', '<', now()->subYears(2))
                       ->whereNull('anonymized_at')
                       ->get();
        
        foreach ($oldUsers as $user) {
            $this->anonymizeUser($user);
            $user->update(['anonymized_at' => now()]);
        }
    }
}

// GDPR Compliance
class GDPRService
{
    public function exportUserData(User $user)
    {
        $data = [
            'personal_information' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'created_at' => $user->created_at,
            ],
            'activity_logs' => $user->activityLogs()->get(),
            'orders' => $user->orders()->with('items')->get(),
            'preferences' => $user->preferences,
        ];
        
        return $data;
    }
    
    public function deleteUserData(User $user)
    {
        // Delete related data
        $user->activityLogs()->delete();
        $user->sessions()->delete();
        $user->tokens()->delete();
        
        // Anonymize orders (keep for business records)
        $user->orders()->update([
            'user_name' => 'Deleted User',
            'user_email' => 'deleted@example.com',
        ]);
        
        // Delete user
        $user->delete();
        
        Log::info('User data deleted (GDPR)', [
            'user_id' => $user->id,
            'deleted_at' => now(),
            'requested_by' => auth()->id(),
        ]);
    }
}
```

### Backup Security

```bash
#!/bin/bash
# Secure backup script

BACKUP_DIR="/secure/backups"
DB_NAME="laravel"
ENCRYPTION_KEY="your-encryption-key"
DATE=$(date +%Y%m%d_%H%M%S)

# Create encrypted database backup
pg_dump $DB_NAME | gzip | gpg --cipher-algo AES256 --compress-algo 1 --symmetric --output "$BACKUP_DIR/db_$DATE.sql.gz.gpg"

# Create encrypted file backup
tar -czf - /var/www/html/storage | gpg --cipher-algo AES256 --compress-algo 1 --symmetric --output "$BACKUP_DIR/files_$DATE.tar.gz.gpg"

# Set secure permissions
chmod 600 "$BACKUP_DIR"/*

# Remove old backups (keep 30 days)
find "$BACKUP_DIR" -name "*.gpg" -mtime +30 -delete

# Verify backup integrity
gpg --decrypt "$BACKUP_DIR/db_$DATE.sql.gz.gpg" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "Database backup verified successfully"
else
    echo "Database backup verification failed"
    exit 1
fi
```

## Security Monitoring

### Intrusion Detection

```php
// Intrusion Detection Service
class IntrusionDetectionService
{
    private $suspiciousPatterns = [
        'sql_injection' => [
            '/union\s+select/i',
            '/drop\s+table/i',
            '/insert\s+into/i',
            '/delete\s+from/i',
        ],
        'xss' => [
            '/<script/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
        ],
        'path_traversal' => [
            '/\.\.\//i',
            '/\.\.\\/i',
            '/etc\/passwd/i',
        ],
    ];
    
    public function analyzeRequest(Request $request)
    {
        $threats = [];
        $input = $request->all();
        
        foreach ($this->suspiciousPatterns as $type => $patterns) {
            foreach ($patterns as $pattern) {
                if ($this->containsPattern($input, $pattern)) {
                    $threats[] = $type;
                    
                    Log::warning('Potential security threat detected', [
                        'type' => $type,
                        'pattern' => $pattern,
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'url' => $request->fullUrl(),
                        'input' => $input,
                        'user_id' => auth()->id(),
                    ]);
                }
            }
        }
        
        if (!empty($threats)) {
            $this->handleThreat($request, $threats);
        }
        
        return $threats;
    }
    
    private function containsPattern($input, $pattern)
    {
        $inputString = json_encode($input);
        return preg_match($pattern, $inputString);
    }
    
    private function handleThreat(Request $request, array $threats)
    {
        // Block IP temporarily
        Cache::put(
            'blocked_ip_' . $request->ip(),
            true,
            now()->addHours(1)
        );
        
        // Send alert
        $this->sendSecurityAlert($request, $threats);
        
        // Log to security log
        Log::channel('security')->critical('Security threat blocked', [
            'threats' => $threats,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'blocked_until' => now()->addHours(1),
        ]);
    }
}

// Security Monitoring Middleware
class SecurityMonitoring
{
    public function handle($request, Closure $next)
    {
        // Check if IP is blocked
        if (Cache::has('blocked_ip_' . $request->ip())) {
            abort(403, 'Access denied due to security violation');
        }
        
        // Analyze request for threats
        app(IntrusionDetectionService::class)->analyzeRequest($request);
        
        return $next($request);
    }
}
```

### Security Logging

```php
// Security Event Logger
class SecurityLogger
{
    public static function logLogin(User $user, Request $request, bool $successful = true)
    {
        Log::channel('security')->info('User login attempt', [
            'event' => 'login',
            'user_id' => $user->id,
            'email' => $user->email,
            'successful' => $successful,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now(),
        ]);
    }
    
    public static function logLogout(User $user, Request $request)
    {
        Log::channel('security')->info('User logout', [
            'event' => 'logout',
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $request->ip(),
            'session_duration' => $request->session()->get('login_time') 
                ? now()->diffInMinutes($request->session()->get('login_time'))
                : null,
            'timestamp' => now(),
        ]);
    }
    
    public static function logPermissionDenied(User $user, Request $request, string $permission)
    {
        Log::channel('security')->warning('Permission denied', [
            'event' => 'permission_denied',
            'user_id' => $user->id,
            'email' => $user->email,
            'permission' => $permission,
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'timestamp' => now(),
        ]);
    }
    
    public static function logDataAccess(User $user, string $resource, array $data = [])
    {
        Log::channel('security')->info('Data access', [
            'event' => 'data_access',
            'user_id' => $user->id,
            'email' => $user->email,
            'resource' => $resource,
            'data' => $data,
            'timestamp' => now(),
        ]);
    }
}
```

### Vulnerability Scanning

```bash
#!/bin/bash
# Automated vulnerability scanning

# Update vulnerability databases
freshclam

# Scan for malware
clamscan -r /var/www/html --log=/var/log/clamav/scan.log

# Check for rootkits
rkhunter --check --skip-keypress --report-warnings-only

# File integrity monitoring
aide --check

# Check for security updates
unattended-upgrade --dry-run

# Scan for open ports
nmap -sS -O localhost

# Check SSL configuration
testssl.sh --quiet yourdomain.com

# Generate security report
cat > /var/log/security-scan.log << EOF
Security Scan Report - $(date)
================================

ClamAV Scan: $(grep "Infected files" /var/log/clamav/scan.log | tail -1)
RKHunter: $(grep "Warning" /var/log/rkhunter.log | wc -l) warnings found
AIDE: $(aide --check 2>&1 | grep -c "changed\|added\|removed") changes detected
Security Updates: $(apt list --upgradable 2>/dev/null | grep -c security)

EOF
```

## Incident Response

### Incident Response Plan

#### 1. Detection and Analysis

```php
// Incident Detection Service
class IncidentDetectionService
{
    public function detectIncident(array $indicators)
    {
        $incident = new SecurityIncident([
            'type' => $this->classifyIncident($indicators),
            'severity' => $this->calculateSeverity($indicators),
            'indicators' => $indicators,
            'detected_at' => now(),
            'status' => 'detected',
        ]);
        
        $incident->save();
        
        // Trigger incident response
        $this->triggerResponse($incident);
        
        return $incident;
    }
    
    private function classifyIncident(array $indicators)
    {
        // Classify based on indicators
        if (in_array('multiple_failed_logins', $indicators)) {
            return 'brute_force_attack';
        }
        
        if (in_array('sql_injection_attempt', $indicators)) {
            return 'injection_attack';
        }
        
        if (in_array('unusual_data_access', $indicators)) {
            return 'data_breach';
        }
        
        return 'unknown';
    }
    
    private function triggerResponse(SecurityIncident $incident)
    {
        // Immediate response based on severity
        switch ($incident->severity) {
            case 'critical':
                $this->criticalIncidentResponse($incident);
                break;
            case 'high':
                $this->highIncidentResponse($incident);
                break;
            case 'medium':
                $this->mediumIncidentResponse($incident);
                break;
        }
    }
}
```

#### 2. Containment

```php
// Incident Containment Service
class IncidentContainmentService
{
    public function containBruteForceAttack($ipAddress)
    {
        // Block IP address
        Cache::put('blocked_ip_' . $ipAddress, true, now()->addHours(24));
        
        // Add to fail2ban
        exec("fail2ban-client set sshd banip $ipAddress");
        
        // Notify administrators
        $this->notifyAdministrators('Brute force attack contained', [
            'ip_address' => $ipAddress,
            'action' => 'IP blocked for 24 hours',
        ]);
    }
    
    public function containDataBreach(User $user)
    {
        // Disable user account
        $user->update(['status' => 'suspended']);
        
        // Revoke all sessions
        $user->sessions()->delete();
        
        // Revoke API tokens
        $user->tokens()->delete();
        
        // Log containment action
        Log::channel('security')->critical('Data breach contained', [
            'user_id' => $user->id,
            'action' => 'Account suspended, sessions revoked',
            'contained_at' => now(),
        ]);
    }
    
    public function containInjectionAttack($request)
    {
        // Block IP and user agent
        Cache::put('blocked_ip_' . $request->ip(), true, now()->addDays(7));
        Cache::put('blocked_ua_' . md5($request->userAgent()), true, now()->addDays(7));
        
        // Enable additional security measures
        Cache::put('enhanced_security_mode', true, now()->addHours(1));
        
        // Notify security team
        $this->notifySecurityTeam('Injection attack detected and contained', [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
        ]);
    }
}
```

#### 3. Recovery

```php
// Incident Recovery Service
class IncidentRecoveryService
{
    public function recoverFromIncident(SecurityIncident $incident)
    {
        switch ($incident->type) {
            case 'data_breach':
                return $this->recoverFromDataBreach($incident);
            case 'brute_force_attack':
                return $this->recoverFromBruteForce($incident);
            case 'injection_attack':
                return $this->recoverFromInjection($incident);
        }
    }
    
    private function recoverFromDataBreach(SecurityIncident $incident)
    {
        // Force password reset for affected users
        $affectedUsers = $this->getAffectedUsers($incident);
        
        foreach ($affectedUsers as $user) {
            $user->update(['password_reset_required' => true]);
            
            // Send notification
            Mail::to($user)->send(new SecurityIncidentNotification($incident));
        }
        
        // Audit data access logs
        $this->auditDataAccess($incident);
        
        // Update security measures
        $this->enhanceSecurityMeasures();
        
        return [
            'affected_users' => $affectedUsers->count(),
            'actions_taken' => [
                'forced_password_reset',
                'user_notification',
                'security_enhancement',
            ],
        ];
    }
    
    private function enhanceSecurityMeasures()
    {
        // Enable stricter rate limiting
        config(['rate-limiting.api.attempts' => 30]);
        
        // Require 2FA for all admin users
        User::where('role', 'admin')->update(['two_factor_required' => true]);
        
        // Enable additional logging
        config(['logging.channels.security.level' => 'debug']);
    }
}
```

### Incident Communication

```php
// Incident Communication Service
class IncidentCommunicationService
{
    public function notifyStakeholders(SecurityIncident $incident)
    {
        $stakeholders = $this->getStakeholders($incident);
        
        foreach ($stakeholders as $stakeholder) {
            $this->sendNotification($stakeholder, $incident);
        }
    }
    
    private function getStakeholders(SecurityIncident $incident)
    {
        $stakeholders = ['security_team'];
        
        if ($incident->severity === 'critical') {
            $stakeholders[] = 'management';
            $stakeholders[] = 'legal_team';
        }
        
        if ($incident->type === 'data_breach') {
            $stakeholders[] = 'privacy_officer';
            $stakeholders[] = 'customer_service';
        }
        
        return $stakeholders;
    }
    
    public function generateIncidentReport(SecurityIncident $incident)
    {
        return [
            'incident_id' => $incident->id,
            'type' => $incident->type,
            'severity' => $incident->severity,
            'detected_at' => $incident->detected_at,
            'contained_at' => $incident->contained_at,
            'resolved_at' => $incident->resolved_at,
            'impact' => $this->assessImpact($incident),
            'root_cause' => $incident->root_cause,
            'actions_taken' => $incident->actions_taken,
            'lessons_learned' => $incident->lessons_learned,
            'recommendations' => $this->generateRecommendations($incident),
        ];
    }
}
```

## Compliance

### GDPR Compliance

```php
// GDPR Compliance Service
class GDPRComplianceService
{
    public function handleDataSubjectRequest(string $type, User $user, array $data = [])
    {
        $request = new DataSubjectRequest([
            'type' => $type,
            'user_id' => $user->id,
            'data' => $data,
            'status' => 'pending',
            'requested_at' => now(),
        ]);
        
        $request->save();
        
        switch ($type) {
            case 'access':
                return $this->handleAccessRequest($request);
            case 'rectification':
                return $this->handleRectificationRequest($request);
            case 'erasure':
                return $this->handleErasureRequest($request);
            case 'portability':
                return $this->handlePortabilityRequest($request);
        }
    }
    
    private function handleAccessRequest(DataSubjectRequest $request)
    {
        $user = $request->user;
        $data = app(GDPRService::class)->exportUserData($user);
        
        // Generate report
        $report = $this->generateDataReport($data);
        
        // Send to user
        Mail::to($user)->send(new DataAccessReport($report));
        
        $request->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
        
        return $report;
    }
    
    public function trackConsent(User $user, string $purpose, bool $granted)
    {
        ConsentRecord::create([
            'user_id' => $user->id,
            'purpose' => $purpose,
            'granted' => $granted,
            'granted_at' => $granted ? now() : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
    
    public function auditDataProcessing()
    {
        return [
            'data_categories' => $this->getDataCategories(),
            'processing_purposes' => $this->getProcessingPurposes(),
            'legal_bases' => $this->getLegalBases(),
            'retention_periods' => $this->getRetentionPeriods(),
            'third_party_transfers' => $this->getThirdPartyTransfers(),
        ];
    }
}
```

### SOC 2 Compliance

```php
// SOC 2 Compliance Service
class SOC2ComplianceService
{
    public function generateSecurityReport()
    {
        return [
            'security_policies' => $this->getSecurityPolicies(),
            'access_controls' => $this->auditAccessControls(),
            'system_operations' => $this->auditSystemOperations(),
            'change_management' => $this->auditChangeManagement(),
            'risk_mitigation' => $this->auditRiskMitigation(),
        ];
    }
    
    private function auditAccessControls()
    {
        return [
            'user_access_reviews' => $this->getUserAccessReviews(),
            'privileged_access' => $this->getPrivilegedAccess(),
            'authentication_controls' => $this->getAuthenticationControls(),
            'authorization_controls' => $this->getAuthorizationControls(),
        ];
    }
    
    public function trackSystemChanges(string $component, array $changes, User $user)
    {
        SystemChange::create([
            'component' => $component,
            'changes' => $changes,
            'changed_by' => $user->id,
            'approved_by' => $changes['approved_by'] ?? null,
            'change_type' => $changes['type'] ?? 'modification',
            'risk_level' => $this->assessChangeRisk($changes),
            'implemented_at' => now(),
        ]);
    }
}
```

## Security Checklist

### Daily Security Tasks
- [ ] Review security alerts and logs
- [ ] Check failed login attempts
- [ ] Verify backup completion
- [ ] Monitor system resource usage
- [ ] Review firewall logs
- [ ] Check SSL certificate status

### Weekly Security Tasks
- [ ] Update security patches
- [ ] Review user access permissions
- [ ] Analyze security metrics
- [ ] Test backup restoration
- [ ] Review incident reports
- [ ] Update threat intelligence

### Monthly Security Tasks
- [ ] Conduct vulnerability scan
- [ ] Review security policies
- [ ] Audit user accounts
- [ ] Test incident response procedures
- [ ] Review third-party integrations
- [ ] Update security documentation

### Quarterly Security Tasks
- [ ] Penetration testing
- [ ] Security awareness training
- [ ] Disaster recovery testing
- [ ] Compliance audit
- [ ] Risk assessment update
- [ ] Security tool evaluation

### Annual Security Tasks
- [ ] Comprehensive security audit
- [ ] Policy review and update
- [ ] Threat modeling exercise
- [ ] Business continuity planning
- [ ] Vendor security assessments
- [ ] Regulatory compliance review

## Emergency Contacts

### Security Team
- **Security Officer**: security@yourcompany.com
- **Incident Response**: incident@yourcompany.com
- **24/7 Hotline**: +1-555-SECURITY

### External Resources
- **Hosting Provider**: support@hostingprovider.com
- **SSL Certificate Authority**: support@certauthority.com
- **Security Consultant**: consultant@securityfirm.com

### Regulatory Bodies
- **Data Protection Authority**: dpa@government.gov
- **Cybersecurity Agency**: cyber@government.gov

Remember: Security is an ongoing process, not a one-time setup. Regular reviews, updates, and improvements are essential for maintaining a strong security posture.