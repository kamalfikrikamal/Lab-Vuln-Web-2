#!/bin/bash
set -euo pipefail

mkdir -p /opt/teknobantu
tar xzf /tmp/teknobantu-src.tar.gz -C /opt/teknobantu
rm -f /tmp/teknobantu-src.tar.gz

chown -R root:root /opt/teknobantu
