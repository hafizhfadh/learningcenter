#!/bin/bash

# Production Server Security Setup Script
# This script automates the setup of security configurations for a production Laravel server

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging
LOG_FILE="/var/log/security-setup.log"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Functions
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}" | tee -a "$LOG_FILE"
}

warn() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING: $1${NC}" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1${NC}" | tee -a "$LOG_FILE"
    exit 1
}

info() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')] INFO: $1${NC}" | tee -a "$LOG_FILE"
}

check_root() {
    if [[ $EUID -ne 0 ]]; then
        error "This script must be run as root"
    fi
}

backup_original_config() {
    local file="$1"
    if [[ -f "$file" ]]; then
        cp "$file" "${file}.backup.$(date +%Y%m%d_%H%M%S)"
        log "Backed up original $file"
    fi
}

install_packages() {
    log "Installing security packages..."
    
    # Update package list
    apt-get update
    
    # Install security packages
    apt-get install -y \
        fail2ban \
        ufw \
        apparmor \
        apparmor-utils \
        rkhunter \
        chkrootkit \
        lynis \
        aide \
        logwatch \
        unattended-upgrades \
        apt-listchanges \
        needrestart \
        debsums \
        acct \
        psad \
        clamav \
        clamav-daemon \
        auditd \
        audispd-plugins
    
    log "Security packages installed successfully"
}

configure_fail2ban() {
    log "Configuring Fail2ban..."
    
    # Copy Fail2ban configuration
    backup_original_config "/etc/fail2ban/jail.local"
    cp "$SCRIPT_DIR/fail2ban/jail.local" "/etc/fail2ban/jail.local"
    
    # Copy custom filters
    cp "$SCRIPT_DIR/fail2ban/filter.d/laravel-auth.conf" "/etc/fail2ban/filter.d/"
    cp "$SCRIPT_DIR/fail2ban/filter.d/laravel-api.conf" "/etc/fail2ban/filter.d/"
    
    # Enable and start Fail2ban
    systemctl enable fail2ban
    systemctl restart fail2ban
    
    log "Fail2ban configured and started"
}

configure_ufw() {
    log "Configuring UFW firewall..."
    
    # Make UFW script executable and run it
    chmod +x "$SCRIPT_DIR/ufw/ufw-rules.sh"
    "$SCRIPT_DIR/ufw/ufw-rules.sh"
    
    log "UFW firewall configured"
}

configure_sysctl() {
    log "Configuring kernel security parameters..."
    
    # Copy sysctl security configuration
    cp "$SCRIPT_DIR/sysctl/99-security.conf" "/etc/sysctl.d/"
    
    # Apply sysctl settings
    sysctl -p /etc/sysctl.d/99-security.conf
    
    log "Kernel security parameters configured"
}

configure_ssh() {
    log "Configuring SSH hardening..."
    
    # Backup original SSH config
    backup_original_config "/etc/ssh/sshd_config"
    
    # Copy hardened SSH configuration
    cp "$SCRIPT_DIR/ssh/sshd_config_hardened" "/etc/ssh/sshd_config"
    cp "$SCRIPT_DIR/ssh/banner" "/etc/ssh/banner"
    
    # Set proper permissions
    chmod 600 /etc/ssh/sshd_config
    chmod 644 /etc/ssh/banner
    
    # Test SSH configuration
    sshd -t || error "SSH configuration test failed"
    
    # Restart SSH service
    systemctl restart sshd
    
    log "SSH hardening configured"
}

configure_apparmor() {
    log "Configuring AppArmor profiles..."
    
    # Copy AppArmor profiles
    cp "$SCRIPT_DIR/apparmor/docker-nginx" "/etc/apparmor.d/"
    cp "$SCRIPT_DIR/apparmor/docker-laravel" "/etc/apparmor.d/"
    
    # Load AppArmor profiles
    apparmor_parser -r /etc/apparmor.d/docker-nginx
    apparmor_parser -r /etc/apparmor.d/docker-laravel
    
    # Enable AppArmor
    systemctl enable apparmor
    systemctl start apparmor
    
    log "AppArmor profiles configured"
}

configure_automatic_updates() {
    log "Configuring automatic security updates..."
    
    # Configure unattended upgrades
    cat > /etc/apt/apt.conf.d/50unattended-upgrades << 'EOF'
Unattended-Upgrade::Allowed-Origins {
    "${distro_id}:${distro_codename}";
    "${distro_id}:${distro_codename}-security";
    "${distro_id}ESMApps:${distro_codename}-apps-security";
    "${distro_id}ESM:${distro_codename}-infra-security";
};

Unattended-Upgrade::Package-Blacklist {
    // "vim";
    // "libc6-dev";
    // "mysql-server";
};

Unattended-Upgrade::DevRelease "false";
Unattended-Upgrade::Remove-Unused-Kernel-Packages "true";
Unattended-Upgrade::Remove-New-Unused-Dependencies "true";
Unattended-Upgrade::Remove-Unused-Dependencies "true";
Unattended-Upgrade::Automatic-Reboot "false";
Unattended-Upgrade::Automatic-Reboot-WithUsers "false";
Unattended-Upgrade::Automatic-Reboot-Time "02:00";
Unattended-Upgrade::SyslogEnable "true";
Unattended-Upgrade::SyslogFacility "daemon";
EOF

    # Enable automatic updates
    cat > /etc/apt/apt.conf.d/20auto-upgrades << 'EOF'
APT::Periodic::Update-Package-Lists "1";
APT::Periodic::Download-Upgradeable-Packages "1";
APT::Periodic::AutocleanInterval "7";
APT::Periodic::Unattended-Upgrade "1";
EOF

    log "Automatic security updates configured"
}

configure_audit() {
    log "Configuring audit system..."
    
    # Configure auditd rules
    cat > /etc/audit/rules.d/audit.rules << 'EOF'
# Delete all existing rules
-D

# Buffer Size
-b 8192

# Failure Mode
-f 1

# Monitor for changes to passwd, group, shadow files
-w /etc/passwd -p wa -k identity
-w /etc/group -p wa -k identity
-w /etc/shadow -p wa -k identity
-w /etc/gshadow -p wa -k identity

# Monitor sudo configuration
-w /etc/sudoers -p wa -k scope
-w /etc/sudoers.d/ -p wa -k scope

# Monitor login/logout events
-w /var/log/faillog -p wa -k logins
-w /var/log/lastlog -p wa -k logins
-w /var/log/tallylog -p wa -k logins

# Monitor network configuration changes
-w /etc/hosts -p wa -k network
-w /etc/network/ -p wa -k network

# Monitor SSH configuration
-w /etc/ssh/sshd_config -p wa -k sshd

# Monitor cron configuration
-w /etc/cron.allow -p wa -k cron
-w /etc/cron.deny -p wa -k cron
-w /etc/cron.d/ -p wa -k cron
-w /etc/cron.daily/ -p wa -k cron
-w /etc/cron.hourly/ -p wa -k cron
-w /etc/cron.monthly/ -p wa -k cron
-w /etc/cron.weekly/ -p wa -k cron
-w /etc/crontab -p wa -k cron
-w /var/spool/cron/crontabs/ -p wa -k cron

# Monitor system calls
-a always,exit -F arch=b64 -S adjtimex -S settimeofday -k time-change
-a always,exit -F arch=b32 -S adjtimex -S settimeofday -S stime -k time-change
-a always,exit -F arch=b64 -S clock_settime -k time-change
-a always,exit -F arch=b32 -S clock_settime -k time-change
-w /etc/localtime -p wa -k time-change

# Monitor file permission changes
-a always,exit -F arch=b64 -S chmod -S fchmod -S fchmodat -F auid>=1000 -F auid!=4294967295 -k perm_mod
-a always,exit -F arch=b32 -S chmod -S fchmod -S fchmodat -F auid>=1000 -F auid!=4294967295 -k perm_mod
-a always,exit -F arch=b64 -S chown -S fchown -S fchownat -S lchown -F auid>=1000 -F auid!=4294967295 -k perm_mod
-a always,exit -F arch=b32 -S chown -S fchown -S fchownat -S lchown -F auid>=1000 -F auid!=4294967295 -k perm_mod

# Make the configuration immutable
-e 2
EOF

    # Enable and start auditd
    systemctl enable auditd
    systemctl restart auditd
    
    log "Audit system configured"
}

configure_logwatch() {
    log "Configuring Logwatch..."
    
    # Configure logwatch
    cat > /etc/logwatch/conf/logwatch.conf << 'EOF'
LogDir = /var/log
TmpDir = /var/cache/logwatch
MailTo = admin@yourcompany.com
MailFrom = Logwatch
Print = No
Save = /var/cache/logwatch
Range = yesterday
Detail = Med
Service = All
mailer = "/usr/sbin/sendmail -t"
EOF

    log "Logwatch configured"
}

configure_rkhunter() {
    log "Configuring RKHunter..."
    
    # Update RKHunter database
    rkhunter --update
    rkhunter --propupd
    
    # Configure RKHunter
    sed -i 's/^MAIL-ON-WARNING=.*/MAIL-ON-WARNING=admin@yourcompany.com/' /etc/rkhunter.conf
    sed -i 's/^#CRON_DAILY_RUN=.*/CRON_DAILY_RUN="true"/' /etc/rkhunter.conf
    sed -i 's/^#CRON_DB_UPDATE=.*/CRON_DB_UPDATE="true"/' /etc/rkhunter.conf
    
    log "RKHunter configured"
}

configure_aide() {
    log "Configuring AIDE (Advanced Intrusion Detection Environment)..."
    
    # Initialize AIDE database
    aideinit
    mv /var/lib/aide/aide.db.new /var/lib/aide/aide.db
    
    # Create daily AIDE check cron job
    cat > /etc/cron.daily/aide << 'EOF'
#!/bin/bash
/usr/bin/aide --check | /bin/mail -s "AIDE Report $(hostname)" admin@yourcompany.com
EOF
    chmod +x /etc/cron.daily/aide
    
    log "AIDE configured"
}

setup_file_permissions() {
    log "Setting up secure file permissions..."
    
    # Secure important system files
    chmod 600 /etc/shadow
    chmod 600 /etc/gshadow
    chmod 644 /etc/passwd
    chmod 644 /etc/group
    chmod 600 /boot/grub/grub.cfg 2>/dev/null || true
    chmod 600 /etc/ssh/ssh_host_*_key 2>/dev/null || true
    chmod 644 /etc/ssh/ssh_host_*_key.pub 2>/dev/null || true
    
    # Secure log files
    chmod 640 /var/log/auth.log 2>/dev/null || true
    chmod 640 /var/log/syslog 2>/dev/null || true
    
    log "File permissions secured"
}

disable_unused_services() {
    log "Disabling unused services..."
    
    # List of services to disable (customize as needed)
    services_to_disable=(
        "telnet"
        "rsh"
        "rlogin"
        "vsftpd"
        "xinetd"
        "ypbind"
        "tftp"
        "certmonger"
        "cgconfig"
        "cgred"
        "cpuspeed"
        "kdump"
        "mdmonitor"
        "messagebus"
        "netconsole"
        "netfs"
        "nfslock"
        "pcscd"
        "portmap"
        "quota_nld"
        "rdisc"
        "restorecond"
        "saslauthd"
        "smartd"
        "yum-updatesd"
    )
    
    for service in "${services_to_disable[@]}"; do
        if systemctl is-enabled "$service" >/dev/null 2>&1; then
            systemctl disable "$service"
            systemctl stop "$service" 2>/dev/null || true
            log "Disabled service: $service"
        fi
    done
    
    log "Unused services disabled"
}

create_security_report() {
    log "Creating security configuration report..."
    
    report_file="/root/security-setup-report-$(date +%Y%m%d_%H%M%S).txt"
    
    cat > "$report_file" << EOF
Security Setup Report
Generated: $(date)
Hostname: $(hostname)
OS: $(lsb_release -d | cut -f2)
Kernel: $(uname -r)

=== Installed Security Tools ===
$(dpkg -l | grep -E "(fail2ban|ufw|apparmor|rkhunter|chkrootkit|lynis|aide)" | awk '{print $2 " " $3}')

=== Active Security Services ===
$(systemctl is-active fail2ban ufw apparmor auditd 2>/dev/null | paste <(echo -e "fail2ban\nufw\napparmor\nauditd") -)

=== UFW Status ===
$(ufw status)

=== Fail2ban Status ===
$(fail2ban-client status)

=== AppArmor Status ===
$(aa-status)

=== SSH Configuration ===
Port: $(grep "^Port" /etc/ssh/sshd_config | awk '{print $2}')
PermitRootLogin: $(grep "^PermitRootLogin" /etc/ssh/sshd_config | awk '{print $2}')
PasswordAuthentication: $(grep "^PasswordAuthentication" /etc/ssh/sshd_config | awk '{print $2}')

=== Security Recommendations ===
1. Regularly update the system: apt update && apt upgrade
2. Monitor logs: tail -f /var/log/auth.log
3. Check Fail2ban status: fail2ban-client status
4. Run security scans: lynis audit system
5. Check for rootkits: rkhunter --check
6. Review AIDE reports: aide --check
7. Monitor system with: htop, iotop, nethogs

=== Next Steps ===
1. Configure email notifications for security alerts
2. Set up log rotation and archival
3. Schedule regular security scans
4. Review and customize security policies
5. Test backup and recovery procedures
6. Implement intrusion detection system
7. Set up centralized logging

EOF

    log "Security report created: $report_file"
}

main() {
    log "Starting production server security setup..."
    
    check_root
    
    # Create log directory if it doesn't exist
    mkdir -p "$(dirname "$LOG_FILE")"
    
    # Run security setup steps
    install_packages
    configure_fail2ban
    configure_ufw
    configure_sysctl
    configure_ssh
    configure_apparmor
    configure_automatic_updates
    configure_audit
    configure_logwatch
    configure_rkhunter
    configure_aide
    setup_file_permissions
    disable_unused_services
    create_security_report
    
    log "Security setup completed successfully!"
    warn "Please review the security report and customize configurations as needed"
    warn "Remember to:"
    warn "1. Update email addresses in configuration files"
    warn "2. Test SSH access before closing current session"
    warn "3. Configure monitoring and alerting"
    warn "4. Schedule regular security audits"
    
    info "Reboot recommended to ensure all changes take effect"
}

# Show usage if help requested
if [[ "${1:-}" == "--help" ]] || [[ "${1:-}" == "-h" ]]; then
    echo "Usage: $0 [--help]"
    echo ""
    echo "This script sets up comprehensive security configurations for a production server."
    echo "It must be run as root and will:"
    echo "  - Install and configure security packages"
    echo "  - Set up firewall rules"
    echo "  - Harden SSH configuration"
    echo "  - Configure intrusion detection"
    echo "  - Set up audit logging"
    echo "  - Configure automatic security updates"
    echo "  - And much more..."
    echo ""
    echo "Options:"
    echo "  --help, -h    Show this help message"
    exit 0
fi

# Run main function
main "$@"