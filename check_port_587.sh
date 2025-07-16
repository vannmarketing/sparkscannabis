#!/bin/bash

echo "Testing if port 587 is accessible..."
echo "Running comprehensive port 587 diagnostics..."

# Check local firewall status for port 587
echo -e "\n1. Checking local firewall status for port 587:"
if command -v ufw &> /dev/null; then
    echo "UFW firewall detected:"
    sudo ufw status | grep 587
elif command -v firewall-cmd &> /dev/null; then
    echo "FirewallD detected:"
    sudo firewall-cmd --list-ports | grep 587
elif command -v iptables &> /dev/null; then
    echo "IPTables detected:"
    sudo iptables -L -n | grep 587
else
    echo "No supported firewall detected or unable to check firewall status."
fi

# Try to connect to port 587 (SMTP submission port) with timeout
echo -e "\n2. Testing connection to smtp.gmail.com:587:"
timeout 5 bash -c "</dev/tcp/smtp.gmail.com/587" 2>/dev/null
RESULT=$?

if [ $RESULT -eq 0 ]; then
    echo "SUCCESS: Port 587 is OPEN and accessible to smtp.gmail.com."
else
    echo "FAILED: Port 587 appears to be BLOCKED or not accessible to smtp.gmail.com."
fi

# Try telnet if available
echo -e "\n3. Testing with telnet (if available):"
if command -v telnet &> /dev/null; then
    echo "Attempting telnet connection to smtp.gmail.com:587 (will timeout after 5 seconds):"
    timeout 5 telnet smtp.gmail.com 587
    if [ $? -eq 0 ]; then
        echo "Telnet connection successful."
    else
        echo "Telnet connection failed or timed out."
    fi
else
    echo "Telnet not available."
    
    # Try installing telnet if not available
    echo "Would you like to install telnet? (y/n)"
    read -n 1 -r
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        if command -v apt-get &> /dev/null; then
            sudo apt-get update && sudo apt-get install -y telnet
        elif command -v yum &> /dev/null; then
            sudo yum install -y telnet
        else
            echo "Unable to install telnet automatically. Please install it manually."
        fi
    fi
fi

# Try using netcat if available
echo -e "\n4. Testing with netcat (if available):"
if command -v nc &> /dev/null; then
    echo "Attempting netcat connection to smtp.gmail.com:587:"
    timeout 5 nc -vz smtp.gmail.com 587
    if [ $? -eq 0 ]; then
        echo "Netcat connection successful."
    else
        echo "Netcat connection failed or timed out."
    fi
else
    echo "Netcat not available."
fi

# Try a local outbound connection
echo -e "\n5. Testing if we can establish any outbound connection:"
timeout 5 bash -c "</dev/tcp/www.google.com/80" 2>/dev/null
if [ $? -eq 0 ]; then
    echo "SUCCESS: Outbound connection to www.google.com:80 works."
    echo "This suggests the issue is specific to port 587 and not a general connectivity problem."
else
    echo "FAILED: Cannot connect to www.google.com:80."
    echo "This suggests there might be a general outbound connectivity issue."
fi

echo -e "\nDiagnostic summary:"
if [ $RESULT -eq 0 ]; then
    echo "Port 587 appears to be OPEN."
    exit 0
else
    echo "Port 587 appears to be BLOCKED. Possible reasons:"
    echo "1. Local firewall is still blocking the port (check the firewall status above)"
    echo "2. An upstream firewall (cloud provider, hardware firewall) is blocking the port"
    echo "3. Your ISP is blocking outbound connections on port 587"
    echo "4. The target server (smtp.gmail.com) is not accepting connections from your IP"
    exit 1
fi
