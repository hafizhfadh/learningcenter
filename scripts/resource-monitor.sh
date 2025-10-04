#!/bin/bash

# Resource Monitoring Dashboard for Learning Center
# Provides real-time monitoring of system resources optimized for 4vCPU/4GB RAM VPS

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
MAGENTA='\033[0;35m'
WHITE='\033[1;37m'
NC='\033[0m' # No Color

# Configuration
REFRESH_INTERVAL=5
LOG_FILE="/var/log/resource-monitor.log"
ALERT_CPU_THRESHOLD=80
ALERT_MEMORY_THRESHOLD=85
ALERT_DISK_THRESHOLD=90
ALERT_LOAD_THRESHOLD=3.0

# Function to clear screen and show header
show_header() {
    clear
    echo -e "${BLUE}╔══════════════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║${WHITE}                    Learning Center Resource Monitor           ${BLUE}║${NC}"
    echo -e "${BLUE}║${WHITE}                    Optimized for 4vCPU/4GB RAM VPS            ${BLUE}║${NC}"
    echo -e "${BLUE}╚══════════════════════════════════════════════════════════════════════════════╝${NC}"
    echo -e "${CYAN}Last Updated: $(date '+%Y-%m-%d %H:%M:%S')${NC}"
    echo ""
}

# Function to get CPU usage
get_cpu_usage() {
    local cpu_usage=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1)
    echo "${cpu_usage%.*}"  # Remove decimal part
}

# Function to get memory usage
get_memory_usage() {
    local memory_info=$(free -m)
    local total=$(echo "$memory_info" | awk 'NR==2{print $2}')
    local used=$(echo "$memory_info" | awk 'NR==2{print $3}')
    local available=$(echo "$memory_info" | awk 'NR==2{print $7}')
    local usage_percent=$(echo "scale=0; $used * 100 / $total" | bc)
    
    echo "$used $total $available $usage_percent"
}

# Function to get disk usage
get_disk_usage() {
    local disk_info=$(df -h / | tail -1)
    local used=$(echo "$disk_info" | awk '{print $3}')
    local total=$(echo "$disk_info" | awk '{print $2}')
    local usage_percent=$(echo "$disk_info" | awk '{print $5}' | cut -d'%' -f1)
    
    echo "$used $total $usage_percent"
}

# Function to get load average
get_load_average() {
    local load_avg=$(uptime | awk -F'load average:' '{print $2}' | awk '{print $1}' | cut -d',' -f1)
    echo "$load_avg"
}

# Function to get network statistics
get_network_stats() {
    local interface=$(ip route | grep default | awk '{print $5}' | head -1)
    if [[ -n "$interface" ]]; then
        local rx_bytes=$(cat "/sys/class/net/$interface/statistics/rx_bytes" 2>/dev/null || echo "0")
        local tx_bytes=$(cat "/sys/class/net/$interface/statistics/tx_bytes" 2>/dev/null || echo "0")
        local rx_mb=$(echo "scale=2; $rx_bytes / 1024 / 1024" | bc)
        local tx_mb=$(echo "scale=2; $tx_bytes / 1024 / 1024" | bc)
        echo "$interface $rx_mb $tx_mb"
    else
        echo "unknown 0 0"
    fi
}

# Function to get Docker container stats
get_docker_stats() {
    if command -v docker &> /dev/null && docker info &> /dev/null; then
        local running=$(docker ps -q | wc -l)
        local total=$(docker ps -aq | wc -l)
        local unhealthy=$(docker ps --filter "health=unhealthy" -q | wc -l)
        echo "$running $total $unhealthy"
    else
        echo "0 0 0"
    fi
}

# Function to get top processes by CPU
get_top_cpu_processes() {
    ps aux --sort=-%cpu | head -6 | tail -5
}

# Function to get top processes by memory
get_top_memory_processes() {
    ps aux --sort=-%mem | head -6 | tail -5
}

# Function to show color-coded bar
show_bar() {
    local percentage=$1
    local width=50
    local filled=$((percentage * width / 100))
    local empty=$((width - filled))
    
    local color
    if [[ $percentage -lt 50 ]]; then
        color=$GREEN
    elif [[ $percentage -lt 80 ]]; then
        color=$YELLOW
    else
        color=$RED
    fi
    
    printf "${color}"
    printf "█%.0s" $(seq 1 $filled)
    printf "${NC}"
    printf "░%.0s" $(seq 1 $empty)
    printf " %3d%%" $percentage
}

# Function to show system overview
show_system_overview() {
    echo -e "${WHITE}┌─ System Overview ─────────────────────────────────────────────────────────────┐${NC}"
    
    # CPU Usage
    local cpu_usage=$(get_cpu_usage)
    echo -e "${WHITE}│${NC} CPU Usage:    $(show_bar $cpu_usage)"
    if [[ $cpu_usage -gt $ALERT_CPU_THRESHOLD ]]; then
        echo -e "${WHITE}│${RED}               ⚠ HIGH CPU USAGE ALERT! ${NC}"
    fi
    echo -e "${WHITE}│${NC}"
    
    # Memory Usage
    local memory_data=$(get_memory_usage)
    local mem_used=$(echo $memory_data | awk '{print $1}')
    local mem_total=$(echo $memory_data | awk '{print $2}')
    local mem_available=$(echo $memory_data | awk '{print $3}')
    local mem_percent=$(echo $memory_data | awk '{print $4}')
    
    echo -e "${WHITE}│${NC} Memory Usage: $(show_bar $mem_percent)"
    echo -e "${WHITE}│${NC}               Used: ${CYAN}${mem_used}MB${NC} / Total: ${CYAN}${mem_total}MB${NC} / Available: ${CYAN}${mem_available}MB${NC}"
    if [[ $mem_percent -gt $ALERT_MEMORY_THRESHOLD ]]; then
        echo -e "${WHITE}│${RED}               ⚠ HIGH MEMORY USAGE ALERT! ${NC}"
    fi
    echo -e "${WHITE}│${NC}"
    
    # Disk Usage
    local disk_data=$(get_disk_usage)
    local disk_used=$(echo $disk_data | awk '{print $1}')
    local disk_total=$(echo $disk_data | awk '{print $2}')
    local disk_percent=$(echo $disk_data | awk '{print $3}')
    
    echo -e "${WHITE}│${NC} Disk Usage:   $(show_bar $disk_percent)"
    echo -e "${WHITE}│${NC}               Used: ${CYAN}${disk_used}${NC} / Total: ${CYAN}${disk_total}${NC}"
    if [[ $disk_percent -gt $ALERT_DISK_THRESHOLD ]]; then
        echo -e "${WHITE}│${RED}               ⚠ HIGH DISK USAGE ALERT! ${NC}"
    fi
    echo -e "${WHITE}│${NC}"
    
    # Load Average
    local load_avg=$(get_load_average)
    echo -e "${WHITE}│${NC} Load Average: ${CYAN}${load_avg}${NC}"
    if (( $(echo "$load_avg > $ALERT_LOAD_THRESHOLD" | bc -l) )); then
        echo -e "${WHITE}│${RED}               ⚠ HIGH LOAD AVERAGE ALERT! ${NC}"
    fi
    
    echo -e "${WHITE}└───────────────────────────────────────────────────────────────────────────────┘${NC}"
    echo ""
}

# Function to show network statistics
show_network_stats() {
    echo -e "${WHITE}┌─ Network Statistics ──────────────────────────────────────────────────────────┐${NC}"
    
    local network_data=$(get_network_stats)
    local interface=$(echo $network_data | awk '{print $1}')
    local rx_mb=$(echo $network_data | awk '{print $2}')
    local tx_mb=$(echo $network_data | awk '{print $3}')
    
    echo -e "${WHITE}│${NC} Interface:    ${CYAN}${interface}${NC}"
    echo -e "${WHITE}│${NC} RX Total:     ${CYAN}${rx_mb} MB${NC}"
    echo -e "${WHITE}│${NC} TX Total:     ${CYAN}${tx_mb} MB${NC}"
    
    # Show active connections
    local connections=$(ss -tuln | grep LISTEN | wc -l)
    echo -e "${WHITE}│${NC} Listening:    ${CYAN}${connections} ports${NC}"
    
    echo -e "${WHITE}└───────────────────────────────────────────────────────────────────────────────┘${NC}"
    echo ""
}

# Function to show Docker statistics
show_docker_stats() {
    echo -e "${WHITE}┌─ Docker Statistics ───────────────────────────────────────────────────────────┐${NC}"
    
    local docker_data=$(get_docker_stats)
    local running=$(echo $docker_data | awk '{print $1}')
    local total=$(echo $docker_data | awk '{print $2}')
    local unhealthy=$(echo $docker_data | awk '{print $3}')
    
    echo -e "${WHITE}│${NC} Running:      ${GREEN}${running}${NC} containers"
    echo -e "${WHITE}│${NC} Total:        ${CYAN}${total}${NC} containers"
    
    if [[ $unhealthy -gt 0 ]]; then
        echo -e "${WHITE}│${NC} Unhealthy:    ${RED}${unhealthy}${NC} containers ${RED}⚠${NC}"
    else
        echo -e "${WHITE}│${NC} Unhealthy:    ${GREEN}${unhealthy}${NC} containers"
    fi
    
    # Show container resource usage if Docker is available
    if command -v docker &> /dev/null && docker info &> /dev/null; then
        echo -e "${WHITE}│${NC}"
        echo -e "${WHITE}│${NC} Container Resource Usage:"
        docker stats --no-stream --format "table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}" 2>/dev/null | head -6 | tail -5 | while read line; do
            if [[ -n "$line" ]]; then
                echo -e "${WHITE}│${NC}   ${CYAN}${line}${NC}"
            fi
        done
    fi
    
    echo -e "${WHITE}└───────────────────────────────────────────────────────────────────────────────┘${NC}"
    echo ""
}

# Function to show top processes
show_top_processes() {
    echo -e "${WHITE}┌─ Top CPU Processes ───────────────────────────────────────────────────────────┐${NC}"
    echo -e "${WHITE}│${NC} ${YELLOW}USER       PID  %CPU %MEM    VSZ   RSS TTY      STAT START   TIME COMMAND${NC}"
    get_top_cpu_processes | while read line; do
        echo -e "${WHITE}│${NC} ${line}"
    done
    echo -e "${WHITE}└───────────────────────────────────────────────────────────────────────────────┘${NC}"
    echo ""
    
    echo -e "${WHITE}┌─ Top Memory Processes ────────────────────────────────────────────────────────┐${NC}"
    echo -e "${WHITE}│${NC} ${YELLOW}USER       PID  %CPU %MEM    VSZ   RSS TTY      STAT START   TIME COMMAND${NC}"
    get_top_memory_processes | while read line; do
        echo -e "${WHITE}│${NC} ${line}"
    done
    echo -e "${WHITE}└───────────────────────────────────────────────────────────────────────────────┘${NC}"
    echo ""
}

# Function to show system information
show_system_info() {
    echo -e "${WHITE}┌─ System Information ──────────────────────────────────────────────────────────┐${NC}"
    echo -e "${WHITE}│${NC} Hostname:     ${CYAN}$(hostname)${NC}"
    echo -e "${WHITE}│${NC} Kernel:       ${CYAN}$(uname -r)${NC}"
    echo -e "${WHITE}│${NC} OS:           ${CYAN}$(lsb_release -d 2>/dev/null | cut -f2 || echo "Unknown")${NC}"
    echo -e "${WHITE}│${NC} Uptime:       ${CYAN}$(uptime -p)${NC}"
    echo -e "${WHITE}│${NC} CPU Cores:    ${CYAN}$(nproc)${NC}"
    echo -e "${WHITE}│${NC} Architecture: ${CYAN}$(uname -m)${NC}"
    echo -e "${WHITE}└───────────────────────────────────────────────────────────────────────────────┘${NC}"
    echo ""
}

# Function to log alerts
log_alert() {
    local message="$1"
    echo "$(date '+%Y-%m-%d %H:%M:%S') - ALERT: $message" >> "$LOG_FILE"
}

# Function to check for alerts
check_alerts() {
    local cpu_usage=$(get_cpu_usage)
    local memory_data=$(get_memory_usage)
    local mem_percent=$(echo $memory_data | awk '{print $4}')
    local disk_data=$(get_disk_usage)
    local disk_percent=$(echo $disk_data | awk '{print $3}')
    local load_avg=$(get_load_average)
    local docker_data=$(get_docker_stats)
    local unhealthy=$(echo $docker_data | awk '{print $3}')
    
    # Check CPU alert
    if [[ $cpu_usage -gt $ALERT_CPU_THRESHOLD ]]; then
        log_alert "High CPU usage: ${cpu_usage}%"
    fi
    
    # Check memory alert
    if [[ $mem_percent -gt $ALERT_MEMORY_THRESHOLD ]]; then
        log_alert "High memory usage: ${mem_percent}%"
    fi
    
    # Check disk alert
    if [[ $disk_percent -gt $ALERT_DISK_THRESHOLD ]]; then
        log_alert "High disk usage: ${disk_percent}%"
    fi
    
    # Check load average alert
    if (( $(echo "$load_avg > $ALERT_LOAD_THRESHOLD" | bc -l) )); then
        log_alert "High load average: ${load_avg}"
    fi
    
    # Check Docker unhealthy containers
    if [[ $unhealthy -gt 0 ]]; then
        log_alert "Unhealthy Docker containers: ${unhealthy}"
    fi
}

# Function to show help
show_help() {
    echo -e "${BLUE}Learning Center Resource Monitor${NC}"
    echo ""
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  -h, --help              Show this help message"
    echo "  -i, --interval SECONDS  Set refresh interval (default: 5)"
    echo "  -o, --once              Run once and exit"
    echo "  -l, --log               Show recent alerts from log"
    echo "  -c, --config            Show current configuration"
    echo ""
    echo "Interactive Commands:"
    echo "  q, Q, Ctrl+C           Quit"
    echo "  r, R                   Refresh immediately"
    echo "  h, H                   Show help"
    echo ""
    echo "Alert Thresholds:"
    echo "  CPU Usage:             ${ALERT_CPU_THRESHOLD}%"
    echo "  Memory Usage:          ${ALERT_MEMORY_THRESHOLD}%"
    echo "  Disk Usage:            ${ALERT_DISK_THRESHOLD}%"
    echo "  Load Average:          ${ALERT_LOAD_THRESHOLD}"
    echo ""
    echo "Log File: $LOG_FILE"
}

# Function to show recent alerts
show_recent_alerts() {
    echo -e "${BLUE}Recent Alerts (last 20):${NC}"
    echo ""
    if [[ -f "$LOG_FILE" ]]; then
        tail -20 "$LOG_FILE" | while read line; do
            echo -e "${YELLOW}$line${NC}"
        done
    else
        echo -e "${CYAN}No alerts logged yet.${NC}"
    fi
}

# Function to show configuration
show_config() {
    echo -e "${BLUE}Current Configuration:${NC}"
    echo ""
    echo -e "Refresh Interval:      ${CYAN}${REFRESH_INTERVAL} seconds${NC}"
    echo -e "Log File:              ${CYAN}${LOG_FILE}${NC}"
    echo -e "CPU Alert Threshold:   ${CYAN}${ALERT_CPU_THRESHOLD}%${NC}"
    echo -e "Memory Alert Threshold: ${CYAN}${ALERT_MEMORY_THRESHOLD}%${NC}"
    echo -e "Disk Alert Threshold:  ${CYAN}${ALERT_DISK_THRESHOLD}%${NC}"
    echo -e "Load Alert Threshold:  ${CYAN}${ALERT_LOAD_THRESHOLD}${NC}"
}

# Function to handle interactive mode
interactive_mode() {
    # Set up trap for cleanup
    trap 'echo -e "\n${CYAN}Monitoring stopped.${NC}"; exit 0' INT TERM
    
    while true; do
        show_header
        show_system_overview
        show_network_stats
        show_docker_stats
        show_top_processes
        show_system_info
        
        # Check for alerts
        check_alerts
        
        echo -e "${CYAN}Press 'q' to quit, 'r' to refresh, 'h' for help${NC}"
        echo -e "${CYAN}Next refresh in ${REFRESH_INTERVAL} seconds...${NC}"
        
        # Wait for input or timeout
        if read -t $REFRESH_INTERVAL -n 1 key 2>/dev/null; then
            case $key in
                q|Q)
                    echo -e "\n${CYAN}Monitoring stopped.${NC}"
                    exit 0
                    ;;
                r|R)
                    continue
                    ;;
                h|H)
                    clear
                    show_help
                    echo ""
                    read -p "Press Enter to continue..."
                    ;;
            esac
        fi
    done
}

# Main function
main() {
    local run_once=false
    local show_alerts=false
    local show_conf=false
    
    # Parse command line arguments
    while [[ $# -gt 0 ]]; do
        case $1 in
            -h|--help)
                show_help
                exit 0
                ;;
            -i|--interval)
                REFRESH_INTERVAL="$2"
                shift 2
                ;;
            -o|--once)
                run_once=true
                shift
                ;;
            -l|--log)
                show_alerts=true
                shift
                ;;
            -c|--config)
                show_conf=true
                shift
                ;;
            *)
                echo "Unknown option: $1"
                show_help
                exit 1
                ;;
        esac
    done
    
    # Install bc if not available (needed for calculations)
    if ! command -v bc &> /dev/null; then
        echo -e "${YELLOW}Installing bc (calculator) for resource calculations...${NC}"
        if command -v apt-get &> /dev/null; then
            sudo apt-get update -qq && sudo apt-get install -y bc
        elif command -v yum &> /dev/null; then
            sudo yum install -y bc
        fi
    fi
    
    # Handle different modes
    if [[ "$show_alerts" == true ]]; then
        show_recent_alerts
        exit 0
    fi
    
    if [[ "$show_conf" == true ]]; then
        show_config
        exit 0
    fi
    
    if [[ "$run_once" == true ]]; then
        show_header
        show_system_overview
        show_network_stats
        show_docker_stats
        show_top_processes
        show_system_info
        check_alerts
        exit 0
    fi
    
    # Run in interactive mode
    interactive_mode
}

# Run main function with all arguments
main "$@"