#!/bin/bash
set -euo pipefail

# /etc/issue is shown on the console before login, unlike /etc/motd which
# only appears after an interactive login - and SSH is disabled at the end
# of this build, so the console banner is the only place a participant will
# ever see the lab's IP/URL.
cat > /usr/local/sbin/teknobantu-issue.sh <<'EOF'
#!/bin/sh
IP=$(ip -4 -o addr show scope global \
      | awk '$2 !~ /^(docker0|veth|br-)/ {print $4}' \
      | cut -d/ -f1 \
      | grep -v '^10\.0\.2\.15$' \
      | tail -n1)
{
  echo "Debian GNU/Linux 12 \n \l"
  echo ""
  echo "=== TeknoBantu Lab ==="
  if [ -n "$IP" ]; then
    echo "Lab URL: http://$IP:8083"
  else
    echo "No IP yet on the lab network adapter - check VirtualBox network settings."
  fi
  echo "======================="
  echo ""
} > /etc/issue
EOF
chmod +x /usr/local/sbin/teknobantu-issue.sh

cat > /etc/systemd/system/teknobantu-issue.service <<'EOF'
[Unit]
Description=Refresh /etc/issue with TeknoBantu lab IP
After=network-online.target
Wants=network-online.target

[Service]
Type=oneshot
ExecStart=/usr/local/sbin/teknobantu-issue.sh

[Install]
WantedBy=multi-user.target
EOF

systemctl enable teknobantu-issue.service
