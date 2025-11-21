# Deployment Guide

Guide for deploying the YourCommentOnAShirt Reddit Bot to production on Amazon Linux EC2.

## Overview

The bot is packaged as an RPM and deployed to AWS EC2 (Amazon Linux 2023) running alongside the WordPress site.

## Prerequisites

- Amazon Linux 2023 EC2 instance
- SSH access to the server
- Reddit API credentials
- Docker installed locally (for building RPM)

## Building the RPM

### 1. Build on Your Local Machine

```bash
cd packaging
./build-rpm.sh
```

This script:
1. Creates a source tarball
2. Builds an Amazon Linux 2023 Docker container
3. Compiles the RPM package
4. Outputs to `dist/reddit-bot-*.rpm`

### 2. Verify Build

```bash
ls -lh dist/
```

You should see:
- `reddit-bot-1.0.0-1.*.noarch.rpm` (the package)
- `reddit-bot-1.0.0.tar.gz` (source tarball)

## Deployment Steps

### 1. Copy RPM to Server

```bash
scp dist/reddit-bot-*.rpm ec2-user@your-server:/tmp/
```

### 2. SSH to Server

```bash
ssh ec2-user@your-server
```

### 3. Install the RPM

```bash
sudo rpm -ivh /tmp/reddit-bot-*.rpm
```

The installation:
- Creates `/opt/reddit-bot/` directory
- Creates `reddit-bot` system user
- Installs Python dependencies via pipenv
- Installs systemd service
- Creates `/var/log/reddit-bot/` for logs

### 4. Configure Reddit API Credentials

Create `.env` file (this is NOT included in the RPM for security):

```bash
sudo nano /opt/reddit-bot/.env
```

Add credentials:

```env
REDDIT_CLIENT_ID=your_actual_client_id
REDDIT_CLIENT_SECRET=your_actual_client_secret
REDDIT_USERNAME=YourCommentOnAShirtBot
REDDIT_PASSWORD=your_actual_password
```

Set proper permissions:

```bash
sudo chown reddit-bot:reddit-bot /opt/reddit-bot/.env
sudo chmod 600 /opt/reddit-bot/.env
```

### 5. Configure Bot Settings

Copy example config and adjust:

```bash
sudo cp /opt/reddit-bot/config.yaml.example /opt/reddit-bot/config.yaml
sudo nano /opt/reddit-bot/config.yaml
```

Key settings to review:

```yaml
reddit:
  user_agent: "YourCommentOnAShirtBot/1.0"

bot:
  base_url: "https://yourcommentonashirt.com/shop/1"
  max_comment_length: 280
  requests_per_minute: 60  # Respect Reddit rate limits

logging:
  log_level: "INFO"  # Use "DEBUG" for troubleshooting
```

Set ownership:

```bash
sudo chown reddit-bot:reddit-bot /opt/reddit-bot/config.yaml
```

### 6. Enable and Start the Service

```bash
# Enable auto-start on boot
sudo systemctl enable reddit-bot

# Start the service
sudo systemctl start reddit-bot

# Check status
sudo systemctl status reddit-bot
```

Expected output:

```
● reddit-bot.service - YourCommentOnAShirt Reddit Bot
   Loaded: loaded (/etc/systemd/system/reddit-bot.service; enabled)
   Active: active (running) since ...
```

### 7. Verify It's Working

View live logs:

```bash
sudo journalctl -u reddit-bot -f
```

You should see:

```
[timestamp] INFO - YourCommentOnAShirt Reddit Bot Starting
[timestamp] INFO - Authenticating with Reddit API...
[timestamp] INFO - Successfully authenticated as: YourCommentOnAShirtBot
[timestamp] INFO - Starting mention monitoring loop...
```

Check log files:

```bash
sudo tail -f /var/log/reddit-bot/bot.log
```

## Updating the Bot

### 1. Build New RPM Version

Update version in `packaging/reddit-bot.spec`:

```spec
Version:        1.1.0
```

Build:

```bash
cd packaging
./build-rpm.sh
```

### 2. Upload and Upgrade

```bash
scp dist/reddit-bot-*.rpm ec2-user@your-server:/tmp/
ssh ec2-user@your-server
sudo rpm -Uvh /tmp/reddit-bot-*.rpm
```

The upgrade process:
1. Stops the running service
2. Installs new files
3. Preserves your `.env` and `config.yaml`
4. Restarts the service

### 3. Verify Upgrade

```bash
sudo systemctl status reddit-bot
sudo journalctl -u reddit-bot -n 50
```

## Service Management

### Start/Stop/Restart

```bash
# Start
sudo systemctl start reddit-bot

# Stop
sudo systemctl stop reddit-bot

# Restart
sudo systemctl restart reddit-bot

# Reload config (if supported)
sudo systemctl reload reddit-bot
```

### Enable/Disable Auto-Start

```bash
# Enable (start on boot)
sudo systemctl enable reddit-bot

# Disable
sudo systemctl disable reddit-bot
```

### Check Service Status

```bash
sudo systemctl status reddit-bot
```

## Monitoring

### View Live Logs

```bash
# systemd journal
sudo journalctl -u reddit-bot -f

# Log file
sudo tail -f /var/log/reddit-bot/bot.log
```

### View Recent Logs

```bash
# Last 100 lines
sudo journalctl -u reddit-bot -n 100

# Since yesterday
sudo journalctl -u reddit-bot --since yesterday

# Last hour
sudo journalctl -u reddit-bot --since "1 hour ago"
```

### Check for Errors

```bash
# Errors only
sudo journalctl -u reddit-bot -p err

# Error log file
sudo cat /var/log/reddit-bot/bot.error.log
```

### Monitor Resource Usage

```bash
# CPU and memory
sudo systemctl status reddit-bot

# Detailed process info
ps aux | grep reddit-bot
```

## Troubleshooting Deployment

### Service Won't Start

Check logs:

```bash
sudo journalctl -u reddit-bot -n 50
```

Common issues:
- Missing `.env` file
- Invalid Reddit credentials
- Missing `config.yaml`
- Permission issues

### Authentication Fails

Verify credentials:

```bash
sudo -u reddit-bot cat /opt/reddit-bot/.env
```

Test manually:

```bash
sudo -u reddit-bot /usr/local/bin/pipenv run python /opt/reddit-bot/bot.py
```

### Service Crashes

Check crash logs:

```bash
sudo journalctl -u reddit-bot --since "10 minutes ago"
```

Service will auto-restart (configured in systemd).

### Rate Limit Issues

If hitting Reddit rate limits, adjust in `config.yaml`:

```yaml
bot:
  requests_per_minute: 30  # Lower value
```

Then restart:

```bash
sudo systemctl restart reddit-bot
```

## Uninstalling

### 1. Stop and Disable Service

```bash
sudo systemctl stop reddit-bot
sudo systemctl disable reddit-bot
```

### 2. Remove RPM

```bash
sudo rpm -e reddit-bot
```

This:
- Stops the service
- Removes installed files
- Preserves logs in `/var/log/reddit-bot/`

### 3. Clean Up (Optional)

```bash
# Remove logs
sudo rm -rf /var/log/reddit-bot/

# Remove user (optional)
sudo userdel reddit-bot
```

## Security Best Practices

1. **Never commit `.env` file** to version control
2. **Restrict `.env` permissions**: `chmod 600`
3. **Run as unprivileged user**: Service runs as `reddit-bot` user
4. **Keep credentials secure**: Use SSH keys, not passwords
5. **Regular updates**: Keep bot and dependencies updated
6. **Monitor logs**: Watch for suspicious activity
7. **Rate limiting**: Respect Reddit's API limits

## Backup and Recovery

### Backup Configuration

```bash
sudo cp /opt/reddit-bot/.env ~/reddit-bot-env.backup
sudo cp /opt/reddit-bot/config.yaml ~/reddit-bot-config.backup
```

### Restore Configuration

```bash
sudo cp ~/reddit-bot-env.backup /opt/reddit-bot/.env
sudo cp ~/reddit-bot-config.backup /opt/reddit-bot/config.yaml
sudo chown reddit-bot:reddit-bot /opt/reddit-bot/.env /opt/reddit-bot/config.yaml
sudo systemctl restart reddit-bot
```

## Production Checklist

- [ ] RPM built successfully
- [ ] RPM uploaded to server
- [ ] RPM installed without errors
- [ ] `.env` file created with valid credentials
- [ ] `config.yaml` configured appropriately
- [ ] Service enabled for auto-start
- [ ] Service started successfully
- [ ] Bot authenticates with Reddit
- [ ] Logs show mention monitoring active
- [ ] Test mention works correctly
- [ ] Monitoring set up (logs, alerts)
- [ ] Backup of configuration created

## Support

For issues:
- Check logs: `sudo journalctl -u reddit-bot -f`
- Review [troubleshooting.md](troubleshooting.md)
- Contact: support@yourcommentonashirt.com

