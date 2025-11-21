# Reddit Bot - Production Deployment Guide (EC2)

**Prerequisites**: Local testing complete and successful  
**Target Platform**: AWS EC2 (Amazon Linux 2023)  
**Deployment Method**: RPM package with systemd service

---

## Table of Contents

1. [Pre-Deployment Checklist](#pre-deployment-checklist)
2. [EC2 Instance Setup](#ec2-instance-setup)
3. [Bot Installation](#bot-installation)
4. [Configuration](#configuration)
5. [Service Management](#service-management)
6. [Monitoring & Logs](#monitoring--logs)
7. [Operational Tasks](#operational-tasks)
8. [Security Best Practices](#security-best-practices)
9. [Troubleshooting Production Issues](#troubleshooting-production-issues)

---

## Pre-Deployment Checklist

Before deploying to production, ensure:

- ✅ All local tests pass
- ✅ Bot runs stable locally for 1+ hours
- ✅ All URLs generated are valid and working
- ✅ Rate limiting is properly configured
- ✅ Error handling tested (deleted comments, invalid params, etc.)
- ✅ Credentials are secure and tested
- ✅ RPM package built successfully
- ✅ Bot account has sufficient karma (if needed)
- ✅ Documentation reviewed

---

## EC2 Instance Setup

### Step 1.1: Choose Instance Type

**Recommended**: `t3.micro` or `t3.small`
- Bot is lightweight (~50-100MB RAM)
- Minimal CPU usage
- t3.micro eligible for AWS Free Tier

**Specifications:**
```
Instance Type: t3.micro
vCPU: 2
RAM: 1 GB
Storage: 8-16 GB EBS (gp3)
OS: Amazon Linux 2023
```

### Step 1.2: Launch Instance

**Via AWS Console:**

1. **Go to EC2 Dashboard** → Launch Instance

2. **Configure Instance:**
   ```
   Name: reddit-bot-prod
   AMI: Amazon Linux 2023 (al2023-ami-minimal)
   Instance Type: t3.micro
   Key Pair: Create/select SSH key pair (save .pem file!)
   ```

3. **Network Settings:**
   ```
   VPC: Default (or your VPC)
   Auto-assign Public IP: Enable
   Security Group:
     - Name: reddit-bot-sg
     - Inbound Rules:
       * SSH (22) from Your IP only
     - Outbound Rules:
       * All traffic (default)
   ```

4. **Storage:**
   ```
   Volume Type: gp3
   Size: 8 GB (sufficient)
   Delete on Termination: Yes
   ```

5. **Launch Instance**

### Step 1.3: Connect to Instance

```bash
# SSH to instance (replace with your key and IP)
chmod 400 ~/path/to/your-key.pem
ssh -i ~/path/to/your-key.pem ec2-user@your-instance-ip

# Update system
sudo dnf update -y
```

### Step 1.4: Install Dependencies

```bash
# Install Python 3.9+ (Amazon Linux 2023 comes with Python 3.9)
python3 --version

# Install pip
sudo dnf install -y python3-pip

# Install pipenv
pip3 install --user pipenv

# Add to PATH (add to ~/.bashrc for persistence)
export PATH="$HOME/.local/bin:$PATH"
echo 'export PATH="$HOME/.local/bin:$PATH"' >> ~/.bashrc

# Verify
pipenv --version
```

---

## Bot Installation

### Step 2.1: Transfer RPM Package

**Option A: SCP from local machine**
```bash
# On your local machine
cd /Users/iggywright/Desktop/Projects/YourCommentOnAShirt/SocialBots/reddit
scp -i ~/path/to/your-key.pem dist/reddit-bot-1.0.0.tar.gz ec2-user@your-instance-ip:~/
```

**Option B: Build on EC2**
```bash
# On EC2 instance
git clone [your-repo-url]
cd YourCommentOnAShirt/SocialBots/reddit

# Install build dependencies
sudo dnf install -y rpm-build python3-devel

# Build RPM
pipenv install --dev
pipenv run python setup.py bdist_rpm

# RPM will be in dist/
```

### Step 2.2: Install RPM (if using RPM method)

```bash
# If you built an RPM
sudo rpm -ivh dist/reddit-bot-1.0.0-1.noarch.rpm

# Verify installation
rpm -ql reddit-bot
```

### Step 2.3: Manual Installation (Recommended for initial deployment)

```bash
# Create application directory
sudo mkdir -p /opt/reddit-bot
sudo chown ec2-user:ec2-user /opt/reddit-bot

# Copy files
cd /opt/reddit-bot
# Upload your source code here (via git clone or scp)

# If using git
git clone https://github.com/your-username/YourCommentOnAShirt.git
cd YourCommentOnAShirt/SocialBots/reddit

# Install dependencies
pipenv install --deploy

# Create logs directory
mkdir -p logs
```

---

## Configuration

### Step 3.1: Set Up Environment Variables

**Create .env file** (NEVER commit this!):

```bash
cd /opt/reddit-bot/YourCommentOnAShirt/SocialBots/reddit

# Create .env with your credentials
cat > .env << 'EOF'
REDDIT_CLIENT_ID=your_actual_client_id
REDDIT_CLIENT_SECRET=your_actual_client_secret
REDDIT_USERNAME=YourCommentOnAShirtBot
REDDIT_PASSWORD=your_actual_password
EOF

# Secure the file
chmod 600 .env
```

**Verify .env is not world-readable:**
```bash
ls -l .env
# Should show: -rw------- (600)
```

### Step 3.2: Configure config.yaml

**Production configuration:**

```bash
cp config.yaml.example config.yaml

# Edit for production
nano config.yaml
```

**Recommended production settings:**

```yaml
reddit:
  user_agent: "YourCommentOnAShirtBot/1.0 by YourRedditUsername"

bot:
  base_url: "https://yourcommentonashirt.com/shop/1"
  max_comment_length: 280
  reply_template: "[Here's your shirt!]({url})"
  max_retries: 3
  retry_backoff_factor: 2
  requests_per_minute: 60  # Full rate limit
  check_interval: 10  # seconds
  skip_existing_on_startup: true

logging:
  log_file: "/opt/reddit-bot/YourCommentOnAShirt/SocialBots/reddit/logs/bot.log"
  log_level: "INFO"  # Use INFO for production (not DEBUG)
  log_rotation_max_bytes: 10485760  # 10MB
  log_rotation_backup_count: 5

defaults:
  text_color: "#000000"
  text_align: "center"
  vertical_align: "center"
  font_size: 120
  font_index: 12
  shirt_color: "white"
  shirt_size: "l"
```

### Step 3.3: Test Configuration

```bash
# Test authentication (should exit immediately after auth)
pipenv run python -c "
from pathlib import Path
from src.config.loader import Config
from src.bot.reddit_bot import RedditBot

config = Config.load(Path('config.yaml'))
bot = RedditBot(config)
bot.authenticate()
print('✅ Authentication successful!')
"
```

---

## Service Management

### Step 4.1: Create systemd Service

**Create service file:**

```bash
sudo nano /etc/systemd/system/reddit-bot.service
```

**Service configuration:**

```ini
[Unit]
Description=YourCommentOnAShirt Reddit Bot
After=network.target
Wants=network-online.target

[Service]
Type=simple
User=ec2-user
Group=ec2-user
WorkingDirectory=/opt/reddit-bot/YourCommentOnAShirt/SocialBots/reddit
Environment="PATH=/home/ec2-user/.local/share/virtualenvs/reddit-*/bin:/usr/local/bin:/usr/bin:/bin"
ExecStart=/home/ec2-user/.local/bin/pipenv run python bot.py
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal
SyslogIdentifier=reddit-bot

# Security hardening
NoNewPrivileges=true
PrivateTmp=true
ProtectSystem=strict
ProtectHome=read-only
ReadWritePaths=/opt/reddit-bot/YourCommentOnAShirt/SocialBots/reddit/logs

[Install]
WantedBy=multi-user.target
```

**Note**: Update the virtualenv path after first run:
```bash
# Find the actual virtualenv path
pipenv --venv
# Update the Environment PATH in the service file
```

### Step 4.2: Enable and Start Service

```bash
# Reload systemd
sudo systemctl daemon-reload

# Enable service (start on boot)
sudo systemctl enable reddit-bot

# Start service
sudo systemctl start reddit-bot

# Check status
sudo systemctl status reddit-bot
```

**Expected output:**
```
● reddit-bot.service - YourCommentOnAShirt Reddit Bot
   Loaded: loaded (/etc/systemd/system/reddit-bot.service; enabled)
   Active: active (running) since Sun 2025-11-09 12:00:00 UTC; 5s ago
 Main PID: 12345 (python)
   CGroup: /system.slice/reddit-bot.service
           └─12345 python bot.py

Nov 09 12:00:00 reddit-bot[12345]: Successfully authenticated as: YourCommentOnAShirtBot
Nov 09 12:00:00 reddit-bot[12345]: Starting mention monitoring loop...
```

### Step 4.3: Verify Service is Running

```bash
# Check if process is running
ps aux | grep bot.py

# Check logs
sudo journalctl -u reddit-bot -f

# Or check log file
tail -f /opt/reddit-bot/YourCommentOnAShirt/SocialBots/reddit/logs/bot.log
```

---

## Monitoring & Logs

### Step 5.1: Real-Time Log Monitoring

**Via journalctl (systemd logs):**
```bash
# Follow logs in real-time
sudo journalctl -u reddit-bot -f

# Last 100 lines
sudo journalctl -u reddit-bot -n 100

# Logs since boot
sudo journalctl -u reddit-bot -b

# Logs from last hour
sudo journalctl -u reddit-bot --since "1 hour ago"
```

**Via log file:**
```bash
# Real-time tail
tail -f /opt/reddit-bot/YourCommentOnAShirt/SocialBots/reddit/logs/bot.log

# Last 50 lines
tail -50 /opt/reddit-bot/YourCommentOnAShirt/SocialBots/reddit/logs/bot.log

# Search for errors
grep -i error /opt/reddit-bot/YourCommentOnAShirt/SocialBots/reddit/logs/bot.log
```

### Step 5.2: Key Metrics to Monitor

**1. Authentication Status**
```bash
sudo journalctl -u reddit-bot | grep "authenticated as"
# Should see: "Successfully authenticated as: YourCommentOnAShirtBot"
```

**2. Mention Processing**
```bash
sudo journalctl -u reddit-bot | grep "Processing mention"
# Count mentions processed
sudo journalctl -u reddit-bot | grep -c "Posted reply to mention"
```

**3. Error Rate**
```bash
sudo journalctl -u reddit-bot | grep -i error
# Should be minimal or none
```

**4. Service Restarts**
```bash
sudo systemctl status reddit-bot | grep "Active:"
# Should show uptime
```

**5. Resource Usage**
```bash
# CPU and Memory
ps aux | grep bot.py

# Disk usage
du -sh /opt/reddit-bot/YourCommentOnAShirt/SocialBots/reddit/logs/
```

### Step 5.3: Set Up Log Rotation (Already configured via Python RotatingFileHandler)

Python's RotatingFileHandler automatically rotates logs:
- Max file size: 10MB
- Backup count: 5 files
- Files: bot.log, bot.log.1, bot.log.2, ..., bot.log.5

**Verify rotation is working:**
```bash
ls -lh /opt/reddit-bot/YourCommentOnAShirt/SocialBots/reddit/logs/
```

### Step 5.4: CloudWatch Integration (Optional)

**Install CloudWatch Agent:**
```bash
sudo dnf install -y amazon-cloudwatch-agent

# Configure to send logs to CloudWatch
# (Follow AWS CloudWatch Agent documentation)
```

---

## Operational Tasks

### Step 6.1: Restart Service

```bash
# Restart (stops and starts)
sudo systemctl restart reddit-bot

# Check status after restart
sudo systemctl status reddit-bot
```

### Step 6.2: Stop Service

```bash
# Stop service
sudo systemctl stop reddit-bot

# Verify stopped
sudo systemctl status reddit-bot
```

### Step 6.3: Update Configuration

```bash
# Edit config
cd /opt/reddit-bot/YourCommentOnAShirt/SocialBots/reddit
nano config.yaml

# Restart to apply changes
sudo systemctl restart reddit-bot

# Verify changes took effect
sudo journalctl -u reddit-bot -n 20
```

### Step 6.4: Update Bot Code

```bash
# Pull latest code
cd /opt/reddit-bot/YourCommentOnAShirt/SocialBots/reddit
git pull origin main

# Install any new dependencies
pipenv install

# Run tests
pipenv run pytest

# Restart service
sudo systemctl restart reddit-bot

# Monitor for issues
sudo journalctl -u reddit-bot -f
```

### Step 6.5: Update Credentials

```bash
# Edit .env
cd /opt/reddit-bot/YourCommentOnAShirt/SocialBots/reddit
nano .env

# Ensure permissions are correct
chmod 600 .env

# Restart service
sudo systemctl restart reddit-bot
```

### Step 6.6: Backup Configuration

```bash
# Backup config and .env
cd /opt/reddit-bot/YourCommentOnAShirt/SocialBots/reddit
tar -czf ~/reddit-bot-backup-$(date +%Y%m%d).tar.gz config.yaml .env

# Copy backup off-server
# On your local machine:
scp -i ~/path/to/your-key.pem ec2-user@your-instance-ip:~/reddit-bot-backup-*.tar.gz ~/backups/
```

---

## Security Best Practices

### Step 7.1: Secure Credentials

✅ **Do:**
- Store .env file with 600 permissions (owner read/write only)
- Never commit .env to git
- Use AWS Secrets Manager for sensitive data (advanced)
- Rotate credentials periodically
- Use strong, unique passwords

❌ **Don't:**
- Store credentials in environment variables globally
- Share credentials via email or chat
- Use same password for multiple services
- Commit credentials to version control

### Step 7.2: EC2 Security

**Security Group:**
- Only allow SSH from your IP
- Consider using AWS Systems Manager Session Manager (no SSH needed)
- Regularly review inbound rules

**SSH Key:**
- Keep .pem file secure (chmod 400)
- Never share private key
- Use different keys for different environments

**OS Updates:**
```bash
# Regular updates
sudo dnf update -y

# Check for security updates
sudo dnf check-update --security
```

### Step 7.3: Service Hardening

The systemd service includes security features:
- `NoNewPrivileges=true` - Prevents privilege escalation
- `PrivateTmp=true` - Isolated /tmp directory
- `ProtectSystem=strict` - Read-only system directories
- `ProtectHome=read-only` - Limited home directory access
- `ReadWritePaths=logs/` - Only logs directory writable

### Step 7.4: Monitoring for Security Issues

```bash
# Check for failed authentication attempts
sudo journalctl -u reddit-bot | grep -i "failed to authenticate"

# Check for suspicious activity
sudo journalctl -u reddit-bot | grep -i "error\|warning\|exception"

# Monitor system logs
sudo tail -f /var/log/secure  # SSH login attempts
```

---

## Troubleshooting Production Issues

### Issue: Service Won't Start

**Check status:**
```bash
sudo systemctl status reddit-bot -l
```

**Check logs:**
```bash
sudo journalctl -u reddit-bot -n 50
```

**Common causes:**
1. **Wrong working directory** - verify path in service file
2. **Missing dependencies** - run `pipenv install`
3. **Permission issues** - check .env and logs directory permissions
4. **Configuration errors** - validate config.yaml syntax

### Issue: Service Keeps Restarting

**Symptoms:**
```bash
sudo systemctl status reddit-bot
# Shows: Active: activating (auto-restart)
```

**Diagnose:**
```bash
# Check why it's crashing
sudo journalctl -u reddit-bot -n 100

# Look for:
# - Authentication failures
# - Python exceptions
# - Configuration errors
```

**Fix:**
```bash
# Stop service
sudo systemctl stop reddit-bot

# Test manually
cd /opt/reddit-bot/YourCommentOnAShirt/SocialBots/reddit
pipenv run python bot.py

# Fix issues, then restart service
sudo systemctl start reddit-bot
```

### Issue: Bot Not Responding to Mentions

**Check if service is running:**
```bash
sudo systemctl status reddit-bot
```

**Check if authenticated:**
```bash
sudo journalctl -u reddit-bot | grep "authenticated"
```

**Check mention logs:**
```bash
sudo journalctl -u reddit-bot | grep "mention"
```

**Verify network connectivity:**
```bash
curl -I https://www.reddit.com
# Should return 200 OK
```

### Issue: High Memory Usage

**Monitor memory:**
```bash
# Current usage
free -h

# Bot process memory
ps aux | grep bot.py
```

**Solutions:**
1. **Restart service periodically** (cron job):
   ```bash
   # Restart daily at 3 AM
   sudo crontab -e
   0 3 * * * /bin/systemctl restart reddit-bot
   ```

2. **Check for memory leaks** in logs:
   ```bash
   grep -i "memory\|allocation" /opt/reddit-bot/YourCommentOnAShirt/SocialBots/reddit/logs/bot.log
   ```

### Issue: Rate Limit Errors

**Symptoms:**
```bash
sudo journalctl -u reddit-bot | grep -i "rate limit"
```

**Solutions:**
1. **Reduce requests_per_minute** in config.yaml:
   ```yaml
   bot:
     requests_per_minute: 30  # Lower value
   ```

2. **Increase check_interval**:
   ```yaml
   bot:
     check_interval: 30  # seconds
   ```

3. **Wait for rate limit to reset** (usually 1 minute)

### Issue: Disk Full

**Check disk space:**
```bash
df -h
```

**If logs are too large:**
```bash
# Check log size
du -sh /opt/reddit-bot/YourCommentOnAShirt/SocialBots/reddit/logs/

# Clean old logs
cd /opt/reddit-bot/YourCommentOnAShirt/SocialBots/reddit/logs/
rm bot.log.[3-5]  # Remove oldest backups

# Or adjust rotation settings in config.yaml
```

---

## Production Monitoring Dashboard (Optional)

### Create Simple Status Page

**Install dependencies:**
```bash
pip3 install --user flask
```

**Create status.py:**
```python
from flask import Flask, jsonify
import subprocess

app = Flask(__name__)

@app.route('/status')
def status():
    result = subprocess.run(
        ['systemctl', 'is-active', 'reddit-bot'],
        capture_output=True,
        text=True
    )
    return jsonify({
        'status': 'running' if result.returncode == 0 else 'stopped',
        'service': 'reddit-bot'
    })

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=8080)
```

**Run status endpoint:**
```bash
python3 status.py &
curl http://localhost:8080/status
```

---

## Performance Tuning

### Optimize for Production

**1. Adjust rate limiting:**
```yaml
bot:
  requests_per_minute: 60  # Max for Reddit
  check_interval: 10  # Balance between responsiveness and API usage
```

**2. Set appropriate log level:**
```yaml
logging:
  log_level: "INFO"  # Use DEBUG only for troubleshooting
```

**3. Configure log rotation:**
```yaml
logging:
  log_rotation_max_bytes: 10485760  # 10MB
  log_rotation_backup_count: 5  # Keep 5 backups
```

**4. Skip old mentions:**
```yaml
bot:
  skip_existing_on_startup: true  # Don't process backlog on restart
```

---

## Disaster Recovery

### Backup Strategy

**What to backup:**
- Configuration files (config.yaml)
- Credentials (.env)
- Service file (/etc/systemd/system/reddit-bot.service)
- Recent logs (for analysis)

**Backup script:**
```bash
#!/bin/bash
# backup.sh

BACKUP_DIR=~/backups
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="reddit-bot-backup-${DATE}.tar.gz"

mkdir -p $BACKUP_DIR

cd /opt/reddit-bot/YourCommentOnAShirt/SocialBots/reddit
tar -czf $BACKUP_DIR/$BACKUP_FILE \
    config.yaml \
    .env \
    logs/bot.log

echo "Backup created: $BACKUP_DIR/$BACKUP_FILE"
```

**Run daily:**
```bash
chmod +x backup.sh
crontab -e
# Add: 0 2 * * * /path/to/backup.sh
```

### Recovery Procedure

**If instance fails:**
1. Launch new EC2 instance
2. Follow installation steps
3. Restore configuration from backup:
   ```bash
   scp backup-file.tar.gz ec2-user@new-instance-ip:~/
   cd /opt/reddit-bot/YourCommentOnAShirt/SocialBots/reddit
   tar -xzf ~/backup-file.tar.gz
   ```
4. Start service
5. Verify operation

---

## Health Check Script

**Create healthcheck.sh:**
```bash
#!/bin/bash
# healthcheck.sh - Monitor bot health

LOG_FILE="/opt/reddit-bot/YourCommentOnAShirt/SocialBots/reddit/logs/bot.log"
SERVICE_NAME="reddit-bot"

# Check if service is running
if ! systemctl is-active --quiet $SERVICE_NAME; then
    echo "❌ Service is not running!"
    exit 1
fi

# Check for recent activity (mentions processed in last hour)
RECENT_ACTIVITY=$(grep "Posted reply to mention" $LOG_FILE | tail -1)
if [ -z "$RECENT_ACTIVITY" ]; then
    echo "⚠️  No recent activity (this may be normal)"
else
    echo "✅ Service is healthy"
    echo "   Last activity: $(echo $RECENT_ACTIVITY | awk '{print $1, $2}')"
fi

# Check for errors in last 100 lines
ERROR_COUNT=$(tail -100 $LOG_FILE | grep -ci error)
if [ $ERROR_COUNT -gt 0 ]; then
    echo "⚠️  Found $ERROR_COUNT errors in recent logs"
fi

# Memory usage
MEM_USAGE=$(ps aux | grep "bot.py" | grep -v grep | awk '{print $4}')
echo "   Memory usage: ${MEM_USAGE}%"

exit 0
```

**Run manually or via cron:**
```bash
chmod +x healthcheck.sh
./healthcheck.sh
```

---

## Production Checklist

Before going live:

### Pre-Launch
- ✅ All tests pass in production environment
- ✅ Credentials are secure and working
- ✅ Service starts automatically on boot
- ✅ Logs are rotating correctly
- ✅ URLs are being generated correctly
- ✅ Bot responds to test mentions
- ✅ Error handling works as expected
- ✅ Rate limiting is configured appropriately

### Post-Launch
- ✅ Monitor logs for first 24 hours
- ✅ Verify mentions are being processed
- ✅ Check for any errors or warnings
- ✅ Confirm URLs are working
- ✅ Monitor resource usage
- ✅ Set up backups
- ✅ Document any issues and resolutions

### Ongoing
- ✅ Review logs weekly
- ✅ Monitor performance metrics
- ✅ Keep system updated
- ✅ Rotate credentials periodically
- ✅ Test disaster recovery procedure
- ✅ Review and update documentation

---

## Support & Resources

### Useful Commands Reference

```bash
# Service management
sudo systemctl start reddit-bot
sudo systemctl stop reddit-bot
sudo systemctl restart reddit-bot
sudo systemctl status reddit-bot

# Logs
sudo journalctl -u reddit-bot -f
tail -f /opt/reddit-bot/YourCommentOnAShirt/SocialBots/reddit/logs/bot.log

# Configuration test
cd /opt/reddit-bot/YourCommentOnAShirt/SocialBots/reddit
pipenv run python bot.py --config config.yaml

# Process info
ps aux | grep bot.py
pgrep -fl bot.py

# Disk space
df -h
du -sh /opt/reddit-bot/YourCommentOnAShirt/SocialBots/reddit/logs/
```

### Documentation Links

- [Reddit API Documentation](https://www.reddit.com/dev/api/)
- [PRAW Documentation](https://praw.readthedocs.io/)
- [systemd Service Documentation](https://www.freedesktop.org/software/systemd/man/systemd.service.html)
- [AWS EC2 Documentation](https://docs.aws.amazon.com/ec2/)

---

**Last Updated**: November 9, 2025  
**Version**: 1.0  
**Status**: Production Ready


