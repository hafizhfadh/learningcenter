#!/bin/bash

# System Optimization Script for 4vCPU/4GB RAM Ubuntu 24.04 VPS
# This script optimizes the host system for running the learning-center application

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging
LOG_FILE="/var/log/system-optimization.log"
exec 1> >(tee -a "$LOG_FILE")
exec 2> >(tee -a "$LOG_FILE" >&2)

echo -e "${BLUE}=== System Optimization Script Started at $(date) ===${NC}"

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check if running as root
check_root() {
    if [[ $EUID -ne 0 ]]; then
        print_error "This script must be run as root (use sudo)"
        exit 1
    fi
}

# Function to backup original configuration files
backup_config() {
    local file="$1"
    if [[ -f "$file" && ! -f "$file.backup" ]]; then
        cp "$file" "$file.backup"
        print_status "Backed up $file to $file.backup"
    fi
}

# Function to optimize kernel parameters
optimize_kernel_parameters() {
    print_status "Optimizing kernel parameters..."
    
    # Backup original sysctl.conf
    backup_config "/etc/sysctl.conf"
    
    # Create optimized sysctl configuration
    cat > /etc/sysctl.d/99-learning-center.conf << 'EOF'
# Learning Center System Optimization
# Optimized for 4vCPU/4GB RAM Ubuntu 24.04 VPS

# Network Optimization
net.core.rmem_default = 262144
net.core.rmem_max = 16777216
net.core.wmem_default = 262144
net.core.wmem_max = 16777216
net.core.netdev_max_backlog = 5000
net.core.somaxconn = 1024
net.ipv4.tcp_rmem = 4096 65536 16777216
net.ipv4.tcp_wmem = 4096 65536 16777216
net.ipv4.tcp_congestion_control = bbr
net.ipv4.tcp_slow_start_after_idle = 0
net.ipv4.tcp_tw_reuse = 1
net.ipv4.tcp_fin_timeout = 30
net.ipv4.tcp_keepalive_time = 1200
net.ipv4.tcp_keepalive_probes = 7
net.ipv4.tcp_keepalive_intvl = 30
net.ipv4.tcp_max_syn_backlog = 8192
net.ipv4.tcp_max_tw_buckets = 2000000
net.ipv4.tcp_fastopen = 3
net.ipv4.tcp_mtu_probing = 1

# Memory Management (Optimized for 4GB RAM)
vm.swappiness = 10
vm.dirty_ratio = 15
vm.dirty_background_ratio = 5
vm.dirty_expire_centisecs = 3000
vm.dirty_writeback_centisecs = 500
vm.vfs_cache_pressure = 50
vm.min_free_kbytes = 65536
vm.overcommit_memory = 1
vm.overcommit_ratio = 50

# File System Optimization
fs.file-max = 2097152
fs.inotify.max_user_watches = 524288
fs.inotify.max_user_instances = 256

# Security
kernel.dmesg_restrict = 1
kernel.kptr_restrict = 2
kernel.yama.ptrace_scope = 1
net.ipv4.conf.default.rp_filter = 1
net.ipv4.conf.all.rp_filter = 1
net.ipv4.conf.all.accept_redirects = 0
net.ipv4.conf.default.accept_redirects = 0
net.ipv4.conf.all.secure_redirects = 0
net.ipv4.conf.default.secure_redirects = 0
net.ipv6.conf.all.accept_redirects = 0
net.ipv6.conf.default.accept_redirects = 0
net.ipv4.conf.all.send_redirects = 0
net.ipv4.conf.default.send_redirects = 0
net.ipv4.icmp_echo_ignore_broadcasts = 1
net.ipv4.icmp_ignore_bogus_error_responses = 1
net.ipv4.conf.all.log_martians = 1
net.ipv4.conf.default.log_martians = 1
net.ipv4.tcp_syncookies = 1
net.ipv4.tcp_rfc1337 = 1

# Process and Resource Limits
kernel.pid_max = 4194304
kernel.threads-max = 1048576
EOF

    # Apply sysctl settings
    sysctl -p /etc/sysctl.d/99-learning-center.conf
    print_status "Kernel parameters optimized"
}

# Function to optimize system limits
optimize_system_limits() {
    print_status "Optimizing system limits..."
    
    # Backup original limits.conf
    backup_config "/etc/security/limits.conf"
    
    # Create optimized limits configuration
    cat > /etc/security/limits.d/99-learning-center.conf << 'EOF'
# Learning Center System Limits
# Optimized for 4vCPU/4GB RAM Ubuntu 24.04 VPS

# Increase file descriptor limits
* soft nofile 65536
* hard nofile 65536
root soft nofile 65536
root hard nofile 65536

# Increase process limits
* soft nproc 32768
* hard nproc 32768
root soft nproc 32768
root hard nproc 32768

# Memory limits (prevent runaway processes)
* soft as 3145728
* hard as 3145728

# Core dump limits
* soft core 0
* hard core 0

# CPU time limits (prevent runaway processes)
* soft cpu 300
* hard cpu 600

# Stack size limits
* soft stack 8192
* hard stack 8192
EOF

    print_status "System limits optimized"
}

# Function to optimize swap configuration
optimize_swap() {
    print_status "Optimizing swap configuration..."
    
    # Check current swap
    local swap_size=$(free -m | awk '/^Swap:/ {print $2}')
    
    if [[ $swap_size -eq 0 ]]; then
        print_warning "No swap detected. Creating 2GB swap file..."
        
        # Create swap file
        fallocate -l 2G /swapfile
        chmod 600 /swapfile
        mkswap /swapfile
        swapon /swapfile
        
        # Add to fstab
        if ! grep -q "/swapfile" /etc/fstab; then
            echo "/swapfile none swap sw 0 0" >> /etc/fstab
        fi
        
        print_status "2GB swap file created and activated"
    else
        print_status "Swap already configured (${swap_size}MB)"
    fi
}

# Function to optimize I/O scheduler
optimize_io_scheduler() {
    print_status "Optimizing I/O scheduler..."
    
    # Detect storage type and set appropriate scheduler
    for disk in /sys/block/*/queue/scheduler; do
        disk_name=$(echo $disk | cut -d'/' -f4)
        
        # Check if it's an SSD (most VPS use SSD)
        if [[ -f "/sys/block/$disk_name/queue/rotational" ]]; then
            rotational=$(cat "/sys/block/$disk_name/queue/rotational")
            if [[ $rotational -eq 0 ]]; then
                # SSD - use mq-deadline or none
                if grep -q "none" "$disk"; then
                    echo "none" > "$disk"
                    print_status "Set I/O scheduler to 'none' for SSD $disk_name"
                elif grep -q "mq-deadline" "$disk"; then
                    echo "mq-deadline" > "$disk"
                    print_status "Set I/O scheduler to 'mq-deadline' for SSD $disk_name"
                fi
            else
                # HDD - use mq-deadline
                if grep -q "mq-deadline" "$disk"; then
                    echo "mq-deadline" > "$disk"
                    print_status "Set I/O scheduler to 'mq-deadline' for HDD $disk_name"
                fi
            fi
        fi
    done
}

# Function to optimize CPU governor
optimize_cpu_governor() {
    print_status "Optimizing CPU governor..."
    
    # Install cpufrequtils if not present
    if ! command -v cpufreq-set &> /dev/null; then
        apt-get update -qq
        apt-get install -y cpufrequtils
    fi
    
    # Set performance governor for all CPUs
    for cpu in /sys/devices/system/cpu/cpu*/cpufreq/scaling_governor; do
        if [[ -f "$cpu" ]]; then
            echo "performance" > "$cpu"
        fi
    done
    
    print_status "CPU governor set to performance"
}

# Function to optimize network settings
optimize_network() {
    print_status "Optimizing network settings..."
    
    # Enable BBR congestion control if available
    if modprobe tcp_bbr 2>/dev/null; then
        echo "tcp_bbr" >> /etc/modules-load.d/modules.conf
        print_status "BBR congestion control enabled"
    fi
    
    # Optimize network interface settings
    for interface in $(ls /sys/class/net/ | grep -E '^(eth|ens|enp)'); do
        if [[ -d "/sys/class/net/$interface" ]]; then
            # Increase ring buffer sizes if supported
            ethtool -G "$interface" rx 4096 tx 4096 2>/dev/null || true
            
            # Enable receive packet steering
            echo 15 > "/sys/class/net/$interface/queues/rx-0/rps_cpus" 2>/dev/null || true
            
            print_status "Optimized network interface $interface"
        fi
    done
}

# Function to optimize Docker daemon
optimize_docker() {
    print_status "Optimizing Docker daemon..."
    
    # Create Docker daemon configuration
    mkdir -p /etc/docker
    
    cat > /etc/docker/daemon.json << 'EOF'
{
  "log-driver": "json-file",
  "log-opts": {
    "max-size": "10m",
    "max-file": "3"
  },
  "storage-driver": "overlay2",
  "storage-opts": [
    "overlay2.override_kernel_check=true"
  ],
  "default-ulimits": {
    "nofile": {
      "Name": "nofile",
      "Hard": 65536,
      "Soft": 65536
    },
    "nproc": {
      "Name": "nproc",
      "Hard": 32768,
      "Soft": 32768
    }
  },
  "max-concurrent-downloads": 3,
  "max-concurrent-uploads": 3,
  "default-shm-size": "128M",
  "userland-proxy": false,
  "experimental": false,
  "live-restore": true,
  "icc": false,
  "userns-remap": "default"
}
EOF

    # Restart Docker if it's running
    if systemctl is-active --quiet docker; then
        systemctl restart docker
        print_status "Docker daemon restarted with optimized configuration"
    else
        print_status "Docker daemon configuration created (will apply on next start)"
    fi
}

# Function to optimize systemd services
optimize_systemd() {
    print_status "Optimizing systemd services..."
    
    # Disable unnecessary services for a web server
    local services_to_disable=(
        "bluetooth.service"
        "cups.service"
        "cups-browsed.service"
        "avahi-daemon.service"
        "ModemManager.service"
        "whoopsie.service"
        "apport.service"
        "snapd.service"
        "snapd.socket"
        "snapd.seeded.service"
    )
    
    for service in "${services_to_disable[@]}"; do
        if systemctl is-enabled --quiet "$service" 2>/dev/null; then
            systemctl disable "$service" 2>/dev/null || true
            systemctl stop "$service" 2>/dev/null || true
            print_status "Disabled unnecessary service: $service"
        fi
    done
    
    # Optimize systemd journal
    mkdir -p /etc/systemd/journald.conf.d
    cat > /etc/systemd/journald.conf.d/99-learning-center.conf << 'EOF'
[Journal]
SystemMaxUse=100M
SystemMaxFileSize=10M
SystemMaxFiles=10
RuntimeMaxUse=50M
RuntimeMaxFileSize=5M
RuntimeMaxFiles=5
ForwardToSyslog=no
ForwardToKMsg=no
ForwardToConsole=no
ForwardToWall=no
MaxRetentionSec=1week
EOF

    systemctl restart systemd-journald
    print_status "Systemd journal optimized"
}

# Function to create monitoring script
create_monitoring_script() {
    print_status "Creating system monitoring script..."
    
    cat > /usr/local/bin/system-monitor.sh << 'EOF'
#!/bin/bash

# System Monitoring Script for Learning Center
# Monitors system resources and alerts on issues

LOG_FILE="/var/log/system-monitor.log"
ALERT_THRESHOLD_CPU=80
ALERT_THRESHOLD_MEMORY=85
ALERT_THRESHOLD_DISK=90

# Function to log with timestamp
log_message() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> "$LOG_FILE"
}

# Check CPU usage
check_cpu() {
    local cpu_usage=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1)
    cpu_usage=${cpu_usage%.*}  # Remove decimal part
    
    if [[ $cpu_usage -gt $ALERT_THRESHOLD_CPU ]]; then
        log_message "ALERT: High CPU usage: ${cpu_usage}%"
        return 1
    fi
    return 0
}

# Check memory usage
check_memory() {
    local memory_usage=$(free | grep Mem | awk '{printf "%.0f", $3/$2 * 100.0}')
    
    if [[ $memory_usage -gt $ALERT_THRESHOLD_MEMORY ]]; then
        log_message "ALERT: High memory usage: ${memory_usage}%"
        return 1
    fi
    return 0
}

# Check disk usage
check_disk() {
    local disk_usage=$(df / | tail -1 | awk '{print $5}' | cut -d'%' -f1)
    
    if [[ $disk_usage -gt $ALERT_THRESHOLD_DISK ]]; then
        log_message "ALERT: High disk usage: ${disk_usage}%"
        return 1
    fi
    return 0
}

# Check Docker containers
check_docker() {
    if command -v docker &> /dev/null; then
        local unhealthy_containers=$(docker ps --filter "health=unhealthy" --format "table {{.Names}}" | tail -n +2)
        if [[ -n "$unhealthy_containers" ]]; then
            log_message "ALERT: Unhealthy Docker containers: $unhealthy_containers"
            return 1
        fi
    fi
    return 0
}

# Main monitoring function
main() {
    local alerts=0
    
    check_cpu || ((alerts++))
    check_memory || ((alerts++))
    check_disk || ((alerts++))
    check_docker || ((alerts++))
    
    if [[ $alerts -eq 0 ]]; then
        log_message "INFO: All systems normal"
    else
        log_message "WARNING: $alerts alert(s) detected"
    fi
}

main "$@"
EOF

    chmod +x /usr/local/bin/system-monitor.sh
    
    # Create systemd service for monitoring
    cat > /etc/systemd/system/system-monitor.service << 'EOF'
[Unit]
Description=System Monitor for Learning Center
After=network.target

[Service]
Type=oneshot
ExecStart=/usr/local/bin/system-monitor.sh
User=root
StandardOutput=journal
StandardError=journal
EOF

    # Create systemd timer for monitoring
    cat > /etc/systemd/system/system-monitor.timer << 'EOF'
[Unit]
Description=Run System Monitor every 5 minutes
Requires=system-monitor.service

[Timer]
OnCalendar=*:0/5
Persistent=true

[Install]
WantedBy=timers.target
EOF

    systemctl daemon-reload
    systemctl enable system-monitor.timer
    systemctl start system-monitor.timer
    
    print_status "System monitoring script created and scheduled"
}

# Function to optimize package management
optimize_packages() {
    print_status "Optimizing package management..."
    
    # Update package cache
    apt-get update -qq
    
    # Remove unnecessary packages
    apt-get autoremove -y
    apt-get autoclean
    
    # Install essential packages for optimization
    apt-get install -y \
        htop \
        iotop \
        nethogs \
        sysstat \
        dstat \
        ncdu \
        tree \
        curl \
        wget \
        unzip \
        git \
        vim \
        tmux \
        jq
    
    print_status "Package management optimized"
}

# Function to create optimization report
create_optimization_report() {
    print_status "Creating optimization report..."
    
    local report_file="/var/log/optimization-report-$(date +%Y%m%d-%H%M%S).txt"
    
    cat > "$report_file" << EOF
=== System Optimization Report ===
Generated: $(date)
Hostname: $(hostname)
Kernel: $(uname -r)
OS: $(lsb_release -d | cut -f2)

=== System Resources ===
CPU Cores: $(nproc)
Total Memory: $(free -h | grep Mem | awk '{print $2}')
Available Memory: $(free -h | grep Mem | awk '{print $7}')
Disk Usage: $(df -h / | tail -1 | awk '{print $5}')
Swap Usage: $(free -h | grep Swap | awk '{print $3}')

=== Network Configuration ===
$(ip addr show | grep -E '^[0-9]+:|inet ')

=== Active Services ===
$(systemctl list-units --type=service --state=active --no-pager | head -20)

=== Docker Status ===
$(if command -v docker &> /dev/null; then docker system df; else echo "Docker not installed"; fi)

=== Optimization Applied ===
- Kernel parameters optimized
- System limits increased
- Swap configured (if needed)
- I/O scheduler optimized
- CPU governor set to performance
- Network settings optimized
- Docker daemon optimized
- Systemd services optimized
- System monitoring enabled
- Package management optimized

=== Recommendations ===
1. Monitor system resources regularly using htop, iotop, and nethogs
2. Check logs in /var/log/system-monitor.log for alerts
3. Review Docker container resource usage periodically
4. Consider implementing log rotation for application logs
5. Monitor disk space and clean up old logs regularly
6. Keep system packages updated with security patches
7. Review and adjust resource limits based on actual usage patterns

=== Next Steps ===
1. Deploy the learning-center application
2. Configure SSL/TLS certificates
3. Set up monitoring and alerting
4. Implement backup procedures
5. Configure log aggregation
EOF

    print_status "Optimization report created: $report_file"
    echo -e "${BLUE}Report location: $report_file${NC}"
}

# Main execution
main() {
    print_status "Starting system optimization for Learning Center..."
    
    # Check prerequisites
    check_root
    
    # Run optimization steps
    optimize_kernel_parameters
    optimize_system_limits
    optimize_swap
    optimize_io_scheduler
    optimize_cpu_governor
    optimize_network
    optimize_docker
    optimize_systemd
    create_monitoring_script
    optimize_packages
    create_optimization_report
    
    print_status "System optimization completed successfully!"
    echo -e "${GREEN}=== Optimization Summary ===${NC}"
    echo -e "${GREEN}✓ Kernel parameters optimized${NC}"
    echo -e "${GREEN}✓ System limits increased${NC}"
    echo -e "${GREEN}✓ Swap configured${NC}"
    echo -e "${GREEN}✓ I/O scheduler optimized${NC}"
    echo -e "${GREEN}✓ CPU governor optimized${NC}"
    echo -e "${GREEN}✓ Network settings optimized${NC}"
    echo -e "${GREEN}✓ Docker daemon optimized${NC}"
    echo -e "${GREEN}✓ Systemd services optimized${NC}"
    echo -e "${GREEN}✓ System monitoring enabled${NC}"
    echo -e "${GREEN}✓ Package management optimized${NC}"
    echo ""
    echo -e "${YELLOW}Please reboot the system to ensure all optimizations take effect:${NC}"
    echo -e "${YELLOW}sudo reboot${NC}"
}

# Run main function
main "$@"