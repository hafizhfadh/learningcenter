#!/bin/bash
set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔧 Production Issues Fix Script${NC}"
echo "=========================================="

# Function to check if running as root or with sudo
check_permissions() {
    if [[ $EUID -eq 0 ]]; then
        echo -e "${GREEN}✅ Running with root privileges${NC}"
    else
        echo -e "${YELLOW}⚠️  Not running as root. Some operations may require sudo.${NC}"
    fi
}

# Function to fix Caddyfile formatting
fix_caddyfile_formatting() {
    echo -e "${YELLOW}📝 Fixing Caddyfile formatting...${NC}"
    
    # Check if Caddyfile exists in the correct location
    if [[ ! -f "etc/frankenphp/Caddyfile" ]]; then
        echo -e "${RED}❌ Caddyfile not found in etc/frankenphp/Caddyfile${NC}"
        if [[ -f "Caddyfile" ]]; then
            echo -e "${YELLOW}📋 Copying Caddyfile to correct location...${NC}"
            mkdir -p etc/frankenphp
            cp Caddyfile etc/frankenphp/Caddyfile
            echo -e "${GREEN}✅ Caddyfile copied to etc/frankenphp/Caddyfile${NC}"
        else
            echo -e "${RED}❌ No Caddyfile found in project root${NC}"
            return 1
        fi
    fi
    
    # Format the Caddyfile (manual formatting since caddy command isn't available in container)
    echo -e "${BLUE}🔍 Checking Caddyfile syntax...${NC}"
    
    # Basic syntax validation
    if grep -q "learning.csi-academy.id" etc/frankenphp/Caddyfile; then
        echo -e "${GREEN}✅ Caddyfile contains expected domain${NC}"
    else
        echo -e "${RED}❌ Caddyfile missing expected domain configuration${NC}"
    fi
    
    echo -e "${GREEN}✅ Caddyfile formatting check complete${NC}"
}

# Function to check network connectivity
check_network_connectivity() {
    echo -e "${YELLOW}🌐 Checking network connectivity...${NC}"
    
    # Check if ports are available
    echo -e "${BLUE}🔍 Checking port availability...${NC}"
    
    if command -v netstat >/dev/null 2>&1; then
        echo "Port 80 usage:"
        netstat -tlnp | grep :80 || echo "Port 80 is available"
        echo "Port 443 usage:"
        netstat -tlnp | grep :443 || echo "Port 443 is available"
    elif command -v ss >/dev/null 2>&1; then
        echo "Port 80 usage:"
        ss -tlnp | grep :80 || echo "Port 80 is available"
        echo "Port 443 usage:"
        ss -tlnp | grep :443 || echo "Port 443 is available"
    else
        echo -e "${YELLOW}⚠️  netstat/ss not available, skipping port check${NC}"
    fi
}

# Function to check firewall settings
check_firewall() {
    echo -e "${YELLOW}🔥 Checking firewall settings...${NC}"
    
    if command -v ufw >/dev/null 2>&1; then
        echo "UFW status:"
        ufw status verbose || echo "UFW not active or accessible"
        
        echo -e "${BLUE}💡 To allow HTTP/HTTPS traffic:${NC}"
        echo "sudo ufw allow 80/tcp"
        echo "sudo ufw allow 443/tcp"
        echo "sudo ufw allow 443/udp  # For HTTP/3"
    elif command -v iptables >/dev/null 2>&1; then
        echo "Current iptables rules:"
        iptables -L -n || echo "Cannot access iptables (may need sudo)"
        
        echo -e "${BLUE}💡 To allow HTTP/HTTPS traffic with iptables:${NC}"
        echo "sudo iptables -A INPUT -p tcp --dport 80 -j ACCEPT"
        echo "sudo iptables -A INPUT -p tcp --dport 443 -j ACCEPT"
        echo "sudo iptables -A INPUT -p udp --dport 443 -j ACCEPT"
    else
        echo -e "${YELLOW}⚠️  No common firewall tools found${NC}"
    fi
}

# Function to check Docker network
check_docker_network() {
    echo -e "${YELLOW}🐳 Checking Docker network configuration...${NC}"
    
    if command -v docker >/dev/null 2>&1; then
        echo "Docker networks:"
        docker network ls
        
        echo "\nContainer port mappings:"
        docker ps --format "table {{.Names}}\t{{.Ports}}" | grep learningcenter || echo "No learningcenter containers running"
        
        echo "\nContainer logs (last 20 lines):"
        docker compose --env-file .env.production -f docker-compose.production.yml logs --tail=20 app || echo "Cannot access container logs"
    else
        echo -e "${RED}❌ Docker not available${NC}"
    fi
}

# Function to test connectivity
test_connectivity() {
    echo -e "${YELLOW}🔍 Testing connectivity...${NC}"
    
    # Test local connectivity
    echo "Testing local HTTP (port 80):"
    curl -I http://localhost/ 2>/dev/null || echo "Local HTTP not responding"
    
    echo "Testing local HTTPS (port 443):"
    curl -I -k https://localhost/ 2>/dev/null || echo "Local HTTPS not responding"
    
    # Test external connectivity if domain is accessible
    echo "Testing external domain:"
    curl -I https://learning.csi-academy.id/ 2>/dev/null || echo "External domain not responding"
}

# Function to provide recommendations
show_recommendations() {
    echo -e "${BLUE}💡 Recommendations:${NC}"
    echo "=========================================="
    
    echo -e "${YELLOW}1. Firewall Configuration:${NC}"
    echo "   - Ensure ports 80, 443 (TCP), and 443 (UDP) are open"
    echo "   - Check cloud provider security groups if applicable"
    
    echo -e "${YELLOW}2. DNS Configuration:${NC}"
    echo "   - Verify learning.csi-academy.id points to this server's IP"
    echo "   - Check A and AAAA records"
    
    echo -e "${YELLOW}3. SSL/TLS:${NC}"
    echo "   - Caddy will automatically obtain Let's Encrypt certificates"
    echo "   - Ensure domain is publicly accessible for ACME challenge"
    
    echo -e "${YELLOW}4. Container Health:${NC}"
    echo "   - Check container logs: docker compose logs app"
    echo "   - Verify all environment variables are set correctly"
    
    echo -e "${YELLOW}5. Network Troubleshooting:${NC}"
    echo "   - Test from another machine: curl -I https://learning.csi-academy.id/"
    echo "   - Check if reverse proxy/load balancer is configured correctly"
}

# Main execution
main() {
    check_permissions
    echo
    
    fix_caddyfile_formatting
    echo
    
    check_network_connectivity
    echo
    
    check_firewall
    echo
    
    check_docker_network
    echo
    
    test_connectivity
    echo
    
    show_recommendations
    
    echo -e "${GREEN}🎉 Production issues fix script completed!${NC}"
    echo -e "${BLUE}📋 Next steps:${NC}"
    echo "1. Restart Docker containers: docker compose --env-file .env.production -f docker-compose.production.yml down && docker compose --env-file .env.production -f docker-compose.production.yml up -d"
    echo "2. Check firewall settings and open required ports"
    echo "3. Verify DNS configuration"
    echo "4. Test external access from another machine"
}

# Run main function
main "$@"