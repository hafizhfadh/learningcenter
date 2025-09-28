#!/bin/bash

# UFW Firewall Configuration Script for Production Server
# This script configures a secure firewall setup for the Laravel SaaS application

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Logging function
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

warn() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING: $1${NC}"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1${NC}"
}

# Check if running as root
check_root() {
    if [[ $EUID -ne 0 ]]; then
        error "This script must be run as root"
        exit 1
    fi
}

# Reset UFW to defaults
reset_ufw() {
    log "Resetting UFW to defaults..."
    ufw --force reset
    ufw default deny incoming
    ufw default allow outgoing
    ufw default deny forward
}

# Configure basic rules
configure_basic_rules() {
    log "Configuring basic UFW rules..."
    
    # Allow SSH (change port if using non-standard)
    ufw allow 22/tcp comment 'SSH'
    
    # Allow HTTP and HTTPS
    ufw allow 80/tcp comment 'HTTP'
    ufw allow 443/tcp comment 'HTTPS'
    
    # Allow loopback
    ufw allow in on lo
    ufw allow out on lo
}

# Configure application-specific rules
configure_app_rules() {
    log "Configuring application-specific rules..."
    
    # Allow Grafana (restrict to specific IPs in production)
    ufw allow from 10.0.0.0/8 to any port 3000 comment 'Grafana - Internal'
    ufw allow from 172.16.0.0/12 to any port 3000 comment 'Grafana - Internal'
    ufw allow from 192.168.0.0/16 to any port 3000 comment 'Grafana - Internal'
    
    # Allow Prometheus (restrict to specific IPs)
    ufw allow from 10.0.0.0/8 to any port 9090 comment 'Prometheus - Internal'
    ufw allow from 172.16.0.0/12 to any port 9090 comment 'Prometheus - Internal'
    ufw allow from 192.168.0.0/16 to any port 9090 comment 'Prometheus - Internal'
    
    # Allow Alertmanager (restrict to specific IPs)
    ufw allow from 10.0.0.0/8 to any port 9093 comment 'Alertmanager - Internal'
    ufw allow from 172.16.0.0/12 to any port 9093 comment 'Alertmanager - Internal'
    ufw allow from 192.168.0.0/16 to any port 9093 comment 'Alertmanager - Internal'
}

# Configure rate limiting
configure_rate_limiting() {
    log "Configuring rate limiting..."
    
    # Rate limit SSH connections
    ufw limit ssh comment 'Rate limit SSH'
    
    # Rate limit HTTP connections (basic protection)
    ufw limit 80/tcp comment 'Rate limit HTTP'
    ufw limit 443/tcp comment 'Rate limit HTTPS'
}

# Configure logging
configure_logging() {
    log "Configuring UFW logging..."
    ufw logging on
}

# Block common attack vectors
block_attacks() {
    log "Blocking common attack vectors..."
    
    # Block common malicious ports
    ufw deny 23 comment 'Block Telnet'
    ufw deny 135 comment 'Block RPC'
    ufw deny 139 comment 'Block NetBIOS'
    ufw deny 445 comment 'Block SMB'
    ufw deny 1433 comment 'Block MSSQL'
    ufw deny 3389 comment 'Block RDP'
    ufw deny 5432 comment 'Block PostgreSQL external'
    ufw deny 6379 comment 'Block Redis external'
    
    # Block ping (optional - uncomment if needed)
    # echo 'net/ipv4/icmp_echo_ignore_all = 1' >> /etc/sysctl.conf
}

# Configure Docker-specific rules
configure_docker_rules() {
    log "Configuring Docker-specific rules..."
    
    # Allow Docker daemon (if needed for remote management)
    # ufw allow from trusted_ip to any port 2376 comment 'Docker daemon TLS'
    
    # Allow Docker Swarm (if using Docker Swarm)
    # ufw allow 2377/tcp comment 'Docker Swarm management'
    # ufw allow 7946/tcp comment 'Docker Swarm node communication'
    # ufw allow 7946/udp comment 'Docker Swarm node communication'
    # ufw allow 4789/udp comment 'Docker Swarm overlay network'
}

# Configure backup and monitoring access
configure_monitoring_access() {
    log "Configuring monitoring and backup access..."
    
    # Allow Node Exporter (internal only)
    ufw allow from 10.0.0.0/8 to any port 9100 comment 'Node Exporter - Internal'
    ufw allow from 172.16.0.0/12 to any port 9100 comment 'Node Exporter - Internal'
    ufw allow from 192.168.0.0/16 to any port 9100 comment 'Node Exporter - Internal'
    
    # Allow cAdvisor (internal only)
    ufw allow from 10.0.0.0/8 to any port 8080 comment 'cAdvisor - Internal'
    ufw allow from 172.16.0.0/12 to any port 8080 comment 'cAdvisor - Internal'
    ufw allow from 192.168.0.0/16 to any port 8080 comment 'cAdvisor - Internal'
    
    # Allow specific backup servers (add your backup server IPs)
    # ufw allow from backup_server_ip to any port 22 comment 'Backup server SSH'
}

# Enable UFW
enable_ufw() {
    log "Enabling UFW..."
    ufw --force enable
    
    # Ensure UFW starts on boot
    systemctl enable ufw
}

# Display current status
show_status() {
    log "Current UFW status:"
    ufw status verbose
}

# Main execution
main() {
    log "Starting UFW firewall configuration..."
    
    check_root
    reset_ufw
    configure_basic_rules
    configure_app_rules
    configure_rate_limiting
    configure_logging
    block_attacks
    configure_docker_rules
    configure_monitoring_access
    enable_ufw
    show_status
    
    log "UFW firewall configuration completed successfully!"
    warn "Please verify the rules are correct before disconnecting from SSH"
    warn "Test SSH connectivity from another terminal before closing this session"
}

# Run main function
main "$@"