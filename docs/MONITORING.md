# Monitoring and Observability Guide

This guide covers the comprehensive monitoring setup for the Laravel application, including metrics collection, logging, alerting, and performance monitoring.

## Table of Contents

- [Monitoring Overview](#monitoring-overview)
- [Metrics Collection](#metrics-collection)
- [Logging Strategy](#logging-strategy)
- [Alerting Configuration](#alerting-configuration)
- [Performance Monitoring](#performance-monitoring)
- [Dashboard Setup](#dashboard-setup)
- [Troubleshooting](#troubleshooting)
- [Best Practices](#best-practices)

## Monitoring Overview

The monitoring stack consists of:

- **Prometheus**: Metrics collection and storage
- **Grafana**: Visualization and dashboards
- **Loki**: Log aggregation and querying
- **Promtail**: Log collection agent
- **Alertmanager**: Alert routing and notification
- **Jaeger**: Distributed tracing (optional)

### Architecture

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│ Application │───▶│ Prometheus  │───▶│   Grafana   │
│   Metrics   │    │   Server    │    │ Dashboards │
└─────────────┘    └─────────────┘    └─────────────┘
                           │
                           ▼
                   ┌─────────────┐    ┌─────────────┐
                   │Alertmanager │───▶│Notifications│
                   │             │    │(Slack/Email)│
                   └─────────────┘    └─────────────┘

┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│Application  │───▶│  Promtail   │───▶│    Loki     │
│    Logs     │    │   Agent     │    │Log Storage  │
└─────────────┘    └─────────────┘    └─────────────┘
```

## Metrics Collection

### Application Metrics

The Laravel application exposes metrics at `/metrics` endpoint:

#### Custom Metrics

```php
// app/Http/Middleware/MetricsMiddleware.php
use Prometheus\CollectorRegistry;
use Prometheus\Storage\Redis;

class MetricsMiddleware
{
    public function handle($request, Closure $next)
    {
        $start = microtime(true);
        
        $response = $next($request);
        
        $duration = microtime(true) - $start;
        
        // Record request duration
        $this->recordRequestDuration($request, $response, $duration);
        
        // Record request count
        $this->recordRequestCount($request, $response);
        
        return $response;
    }
    
    private function recordRequestDuration($request, $response, $duration)
    {
        $registry = app(CollectorRegistry::class);
        $histogram = $registry->getOrRegisterHistogram(
            'laravel',
            'http_request_duration_seconds',
            'HTTP request duration in seconds',
            ['method', 'route', 'status_code']
        );
        
        $histogram->observe(
            $duration,
            [$request->method(), $request->route()->getName(), $response->getStatusCode()]
        );
    }
}
```

#### Key Application Metrics

1. **HTTP Metrics**:
   - Request duration
   - Request count by status code
   - Request rate
   - Error rate

2. **Database Metrics**:
   - Query count
   - Query duration
   - Connection pool usage
   - Slow queries

3. **Queue Metrics**:
   - Job processing time
   - Queue length
   - Failed jobs
   - Worker status

4. **Cache Metrics**:
   - Hit/miss ratio
   - Cache operations
   - Memory usage

### Infrastructure Metrics

#### System Metrics (Node Exporter)

- CPU usage
- Memory usage
- Disk I/O
- Network I/O
- Load average
- File system usage

#### Container Metrics (cAdvisor)

- Container CPU usage
- Container memory usage
- Container network I/O
- Container restart count

#### Database Metrics (Postgres Exporter)

- Connection count
- Transaction rate
- Lock waits
- Buffer hit ratio
- Replication lag

#### Redis Metrics (Redis Exporter)

- Memory usage
- Connected clients
- Commands processed
- Key count
- Evicted keys

### Starting Monitoring Stack

```bash
# Start monitoring services
docker-compose -f docker-compose.monitoring.yml up -d

# Verify services are running
docker-compose -f docker-compose.monitoring.yml ps

# Check Prometheus targets
curl http://localhost:9090/api/v1/targets
```

## Logging Strategy

### Log Levels and Categories

#### Application Logs

```php
// config/logging.php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single', 'loki'],
        'ignore_exceptions' => false,
    ],
    
    'loki' => [
        'driver' => 'custom',
        'via' => App\Logging\LokiLogger::class,
        'url' => env('LOKI_URL', 'http://loki:3100'),
        'level' => env('LOG_LEVEL', 'debug'),
    ],
],
```

#### Structured Logging

```php
// Use structured logging for better querying
Log::info('User login', [
    'user_id' => $user->id,
    'ip_address' => $request->ip(),
    'user_agent' => $request->userAgent(),
    'timestamp' => now()->toISOString(),
]);

Log::error('Database connection failed', [
    'error' => $exception->getMessage(),
    'database' => config('database.default'),
    'host' => config('database.connections.pgsql.host'),
    'trace_id' => request()->header('X-Trace-ID'),
]);
```

### Log Collection

#### Promtail Configuration

Promtail collects logs from various sources:

1. **Application Logs**: Laravel log files
2. **Web Server Logs**: Nginx access and error logs
3. **System Logs**: Syslog, auth logs
4. **Container Logs**: Docker container logs

#### Log Parsing

```yaml
# promtail.yml - Laravel log parsing
- job_name: laravel
  static_configs:
  - targets:
      - localhost
    labels:
      job: laravel
      __path__: /var/log/laravel/*.log
  
  pipeline_stages:
  - regex:
      expression: '^\[(?P<timestamp>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (?P<environment>\w+)\.(?P<level>\w+): (?P<message>.*)'
  
  - timestamp:
      source: timestamp
      format: '2006-01-02 15:04:05'
  
  - labels:
      level:
      environment:
```

### Log Querying

#### LogQL Examples

```logql
# View all Laravel errors
{job="laravel"} |= "ERROR"

# View authentication failures
{job="laravel"} |= "authentication.failed"

# View slow queries
{job="laravel"} |= "slow query" | json | duration > 1000

# View 5xx errors from Nginx
{job="nginx"} |= " 5" | regex "(?P<status>5\d{2})"

# View failed queue jobs
{job="laravel"} |= "queue.failed" | json
```

## Alerting Configuration

### Alert Rules

#### Application Alerts

```yaml
# alert_rules.yml
groups:
- name: laravel_application
  rules:
  - alert: HighErrorRate
    expr: rate(laravel_http_requests_total{status=~"5.."}[5m]) > 0.1
    for: 2m
    labels:
      severity: critical
    annotations:
      summary: "High error rate detected"
      description: "Error rate is {{ $value }} errors per second"

  - alert: SlowResponseTime
    expr: histogram_quantile(0.95, rate(laravel_http_request_duration_seconds_bucket[5m])) > 2
    for: 5m
    labels:
      severity: warning
    annotations:
      summary: "Slow response time"
      description: "95th percentile response time is {{ $value }}s"

  - alert: QueueBacklog
    expr: laravel_queue_size > 1000
    for: 5m
    labels:
      severity: warning
    annotations:
      summary: "Queue backlog detected"
      description: "Queue has {{ $value }} pending jobs"
```

#### Infrastructure Alerts

```yaml
- name: infrastructure
  rules:
  - alert: HighCPUUsage
    expr: 100 - (avg by(instance) (irate(node_cpu_seconds_total{mode="idle"}[5m])) * 100) > 80
    for: 5m
    labels:
      severity: warning
    annotations:
      summary: "High CPU usage"
      description: "CPU usage is {{ $value }}%"

  - alert: HighMemoryUsage
    expr: (1 - (node_memory_MemAvailable_bytes / node_memory_MemTotal_bytes)) * 100 > 90
    for: 5m
    labels:
      severity: critical
    annotations:
      summary: "High memory usage"
      description: "Memory usage is {{ $value }}%"

  - alert: DiskSpaceLow
    expr: (1 - (node_filesystem_avail_bytes / node_filesystem_size_bytes)) * 100 > 85
    for: 5m
    labels:
      severity: warning
    annotations:
      summary: "Low disk space"
      description: "Disk usage is {{ $value }}%"
```

### Notification Channels

#### Slack Integration

```yaml
# alertmanager.yml
route:
  group_by: ['alertname']
  group_wait: 10s
  group_interval: 10s
  repeat_interval: 1h
  receiver: 'web.hook'
  routes:
  - match:
      severity: critical
    receiver: 'critical-alerts'
  - match:
      severity: warning
    receiver: 'warning-alerts'

receivers:
- name: 'critical-alerts'
  slack_configs:
  - api_url: 'YOUR_SLACK_WEBHOOK_URL'
    channel: '#alerts-critical'
    title: 'Critical Alert'
    text: '{{ range .Alerts }}{{ .Annotations.summary }}{{ end }}'
    
- name: 'warning-alerts'
  slack_configs:
  - api_url: 'YOUR_SLACK_WEBHOOK_URL'
    channel: '#alerts-warning'
    title: 'Warning Alert'
    text: '{{ range .Alerts }}{{ .Annotations.summary }}{{ end }}'
```

#### Email Notifications

```yaml
receivers:
- name: 'email-alerts'
  email_configs:
  - to: 'admin@yourcompany.com'
    from: 'alerts@yourcompany.com'
    smarthost: 'smtp.gmail.com:587'
    auth_username: 'alerts@yourcompany.com'
    auth_password: 'your_password'
    subject: 'Alert: {{ .GroupLabels.alertname }}'
    body: |
      {{ range .Alerts }}
      Alert: {{ .Annotations.summary }}
      Description: {{ .Annotations.description }}
      {{ end }}
```

## Performance Monitoring

### Application Performance Monitoring (APM)

#### Laravel Telescope Integration

```php
// config/telescope.php
'watchers' => [
    Watchers\CacheWatcher::class => env('TELESCOPE_CACHE_WATCHER', true),
    Watchers\CommandWatcher::class => env('TELESCOPE_COMMAND_WATCHER', true),
    Watchers\DumpWatcher::class => env('TELESCOPE_DUMP_WATCHER', true),
    Watchers\EventWatcher::class => env('TELESCOPE_EVENT_WATCHER', true),
    Watchers\ExceptionWatcher::class => env('TELESCOPE_EXCEPTION_WATCHER', true),
    Watchers\JobWatcher::class => env('TELESCOPE_JOB_WATCHER', true),
    Watchers\LogWatcher::class => env('TELESCOPE_LOG_WATCHER', true),
    Watchers\MailWatcher::class => env('TELESCOPE_MAIL_WATCHER', true),
    Watchers\ModelWatcher::class => env('TELESCOPE_MODEL_WATCHER', true),
    Watchers\NotificationWatcher::class => env('TELESCOPE_NOTIFICATION_WATCHER', true),
    Watchers\QueryWatcher::class => env('TELESCOPE_QUERY_WATCHER', true),
    Watchers\RedisWatcher::class => env('TELESCOPE_REDIS_WATCHER', true),
    Watchers\RequestWatcher::class => env('TELESCOPE_REQUEST_WATCHER', true),
    Watchers\ScheduleWatcher::class => env('TELESCOPE_SCHEDULE_WATCHER', true),
    Watchers\ViewWatcher::class => env('TELESCOPE_VIEW_WATCHER', true),
],
```

#### Database Performance

```php
// Monitor slow queries
DB::listen(function ($query) {
    if ($query->time > 1000) { // Log queries taking more than 1 second
        Log::warning('Slow query detected', [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time,
        ]);
    }
});
```

#### Queue Performance

```php
// Monitor queue job performance
class MonitoredJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function handle()
    {
        $start = microtime(true);
        
        try {
            // Job logic here
            $this->processJob();
            
            $this->recordJobMetrics('success', microtime(true) - $start);
        } catch (Exception $e) {
            $this->recordJobMetrics('failed', microtime(true) - $start);
            throw $e;
        }
    }
    
    private function recordJobMetrics($status, $duration)
    {
        // Record metrics to Prometheus
        app('prometheus')->getOrRegisterHistogram(
            'laravel',
            'queue_job_duration_seconds',
            'Queue job duration',
            ['job_class', 'status']
        )->observe($duration, [static::class, $status]);
    }
}
```

### Real User Monitoring (RUM)

#### Frontend Performance

```javascript
// resources/js/monitoring.js
class PerformanceMonitor {
    constructor() {
        this.collectPageLoadMetrics();
        this.collectUserInteractionMetrics();
    }
    
    collectPageLoadMetrics() {
        window.addEventListener('load', () => {
            const navigation = performance.getEntriesByType('navigation')[0];
            
            // Send metrics to backend
            fetch('/api/metrics/frontend', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    page_load_time: navigation.loadEventEnd - navigation.fetchStart,
                    dom_content_loaded: navigation.domContentLoadedEventEnd - navigation.fetchStart,
                    first_byte: navigation.responseStart - navigation.fetchStart,
                    url: window.location.pathname
                })
            });
        });
    }
    
    collectUserInteractionMetrics() {
        // Track click events
        document.addEventListener('click', (event) => {
            if (event.target.matches('[data-track]')) {
                this.trackInteraction('click', event.target.dataset.track);
            }
        });
    }
    
    trackInteraction(type, element) {
        fetch('/api/metrics/interaction', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                type: type,
                element: element,
                timestamp: Date.now(),
                url: window.location.pathname
            })
        });
    }
}

new PerformanceMonitor();
```

## Dashboard Setup

### Grafana Dashboards

#### Application Dashboard

Key panels:
1. **Request Rate**: Requests per second
2. **Response Time**: 95th percentile response time
3. **Error Rate**: Percentage of 5xx responses
4. **Database Connections**: Active connections
5. **Queue Length**: Pending jobs
6. **Memory Usage**: Application memory consumption

#### Infrastructure Dashboard

Key panels:
1. **CPU Usage**: Per-core and average CPU usage
2. **Memory Usage**: Available vs used memory
3. **Disk I/O**: Read/write operations per second
4. **Network I/O**: Bytes sent/received
5. **Load Average**: System load over time
6. **Container Status**: Running/stopped containers

#### Database Dashboard

Key panels:
1. **Connection Count**: Active database connections
2. **Query Performance**: Average query time
3. **Lock Waits**: Database lock statistics
4. **Buffer Hit Ratio**: Cache efficiency
5. **Replication Lag**: Master-slave delay
6. **Slow Queries**: Queries taking >1 second

### Custom Dashboards

#### Business Metrics Dashboard

```json
{
  "dashboard": {
    "title": "Business Metrics",
    "panels": [
      {
        "title": "User Registrations",
        "type": "stat",
        "targets": [
          {
            "expr": "increase(laravel_user_registrations_total[1h])"
          }
        ]
      },
      {
        "title": "Revenue",
        "type": "stat",
        "targets": [
          {
            "expr": "sum(laravel_revenue_total)"
          }
        ]
      },
      {
        "title": "Active Users",
        "type": "graph",
        "targets": [
          {
            "expr": "laravel_active_users"
          }
        ]
      }
    ]
  }
}
```

## Troubleshooting

### Common Monitoring Issues

#### 1. Missing Metrics

```bash
# Check if metrics endpoint is accessible
curl http://localhost:9000/metrics

# Verify Prometheus configuration
docker-compose exec prometheus promtool check config /etc/prometheus/prometheus.yml

# Check Prometheus targets
curl http://localhost:9090/api/v1/targets
```

#### 2. Log Collection Issues

```bash
# Check Promtail status
docker-compose logs promtail

# Verify log file permissions
ls -la /var/log/laravel/

# Test log parsing
docker-compose exec promtail promtail -config.file=/etc/promtail/config.yml -dry-run
```

#### 3. Alert Not Firing

```bash
# Check alert rules syntax
docker-compose exec prometheus promtool check rules /etc/prometheus/alert_rules.yml

# Verify alert status
curl http://localhost:9090/api/v1/alerts

# Check Alertmanager configuration
curl http://localhost:9093/api/v1/status
```

### Performance Troubleshooting

#### 1. High Response Times

```bash
# Check application metrics
curl http://localhost:9000/metrics | grep http_request_duration

# Analyze slow queries
docker-compose exec postgres psql -U laravel laravel -c "
SELECT query, mean_time, calls 
FROM pg_stat_statements 
ORDER BY mean_time DESC 
LIMIT 10;"

# Check system resources
docker stats
```

#### 2. Memory Leaks

```bash
# Monitor memory usage over time
docker stats --format "table {{.Container}}\t{{.MemUsage}}\t{{.MemPerc}}"

# Check for memory leaks in application
docker-compose exec app php artisan telescope:clear
docker-compose exec app php artisan cache:clear
```

#### 3. Queue Backlog

```bash
# Check queue status
docker-compose exec app php artisan horizon:status

# Monitor queue metrics
curl http://localhost:9000/metrics | grep queue

# Scale queue workers
docker-compose up -d --scale horizon=3
```

## Best Practices

### 1. Metrics Design

- Use consistent naming conventions
- Include relevant labels
- Avoid high cardinality metrics
- Set appropriate retention periods
- Monitor metric collection overhead

### 2. Alerting Strategy

- Define clear severity levels
- Avoid alert fatigue
- Set appropriate thresholds
- Include actionable information
- Test alert notifications

### 3. Dashboard Design

- Focus on key metrics
- Use appropriate visualizations
- Include context and annotations
- Organize by audience
- Keep dashboards maintainable

### 4. Log Management

- Use structured logging
- Include correlation IDs
- Set appropriate log levels
- Implement log rotation
- Monitor log volume

### 5. Performance Monitoring

- Monitor user experience
- Track business metrics
- Set performance budgets
- Use synthetic monitoring
- Implement distributed tracing

## Monitoring Checklist

### Daily Checks
- [ ] Review critical alerts
- [ ] Check system resource usage
- [ ] Verify backup completion
- [ ] Monitor error rates
- [ ] Review performance metrics

### Weekly Checks
- [ ] Analyze performance trends
- [ ] Review capacity planning
- [ ] Update alert thresholds
- [ ] Check log retention
- [ ] Validate monitoring coverage

### Monthly Checks
- [ ] Review monitoring costs
- [ ] Update dashboards
- [ ] Audit alert rules
- [ ] Performance optimization
- [ ] Monitoring tool updates

## Support

For monitoring issues:
- Grafana: http://your-server:3000
- Prometheus: http://your-server:9090
- Alertmanager: http://your-server:9093
- Contact: monitoring@yourcompany.com