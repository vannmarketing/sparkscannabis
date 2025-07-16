#!/bin/bash

echo "Script to open port 587 (SMTP submission port)"
echo "This script requires root/sudo privileges to modify firewall rules"

# Check if the script is being run as root
if [ "$EUID" -ne 0 ]; then
  echo "Please run this script with sudo or as root"
  exit 1
fi

# Detect which firewall is in use and open port 587
if command -v ufw &> /dev/null; then
    echo "UFW firewall detected. Opening port 587..."
    ufw allow 587/tcp
    ufw status | grep 587
elif command -v firewall-cmd &> /dev/null; then
    echo "FirewallD detected. Opening port 587..."
    firewall-cmd --permanent --add-port=587/tcp
    firewall-cmd --reload
    firewall-cmd --list-ports | grep 587
elif command -v iptables &> /dev/null; then
    echo "Using iptables to open port 587..."
    iptables -A INPUT -p tcp --dport 587 -j ACCEPT
    # Make the rule persistent (may vary by distribution)
    if command -v iptables-save &> /dev/null; then
        if [ -d "/etc/iptables" ]; then
            iptables-save > /etc/iptables/rules.v4
        elif [ -f "/etc/sysconfig/iptables" ]; then
            iptables-save > /etc/sysconfig/iptables
        else
            echo "Warning: iptables rules may not persist after reboot."
            echo "You may need to save the rules according to your distribution."
        fi
    fi
    iptables -L -n | grep 587
else
    echo "No supported firewall detected. Please manually configure your firewall to open port 587."
    exit 1
fi

echo "Port 587 should now be open. Run the check_port_587.sh script to verify."
