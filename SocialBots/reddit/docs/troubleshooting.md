# Troubleshooting Guide

Common issues and solutions for the YourCommentOnAShirt Reddit Bot.

## Table of Contents

- [Bot Not Responding](#bot-not-responding)
- [Authentication Issues](#authentication-issues)
- [Service Won't Start](#service-wont-start)
- [Rate Limiting](#rate-limiting)
- [Parameter Issues](#parameter-issues)
- [Deployment Problems](#deployment-problems)
- [Log Analysis](#log-analysis)

---

## Bot Not Responding

### Symptom

Bot doesn't reply to mentions on Reddit.

### Possible Causes & Solutions

#### 1. Service Not Running

**Check**:
```bash
sudo systemctl status reddit-bot
```

**Solution**:
```bash
sudo systemctl start reddit-bot
```

#### 2. Authentication Failed

**Check logs**:
```bash
sudo journalctl -u reddit-bot | grep -i "auth"
```

**Look for**: "Failed to authenticate" or credential errors

**Solution**: Verify `.env` credentials are correct

#### 3. Bot Not Monitoring Inbox

**Check logs**:
```bash
sudo journalctl -u reddit-bot | grep -i "monitoring"
```

**Should see**: "Starting mention monitoring loop..."

**If missing**: Bot crashed or failed to start - check error logs

#### 4. Mention Already Processed

Bot tracks processed mentions to prevent duplicates.

**Check**:
```bash
sudo journalctl -u reddit-bot | grep "mention_id"
```

**Solution**: Each mention is processed once - try a new mention

#### 5. Rate Limiting

Bot may be waiting due to rate limits.

**Check config**:
```yaml
bot:
  requests_per_minute: 60
```

**Solution**: Be patient or adjust rate limit

---

## Authentication Issues

### Symptom

```
ERROR - Failed to authenticate with Reddit
```

### Solutions

#### 1. Verify Credentials

Check `.env` file:

```bash
sudo cat /opt/reddit-bot/.env
```

Verify:
- `REDDIT_CLIENT_ID` is correct
- `REDDIT_CLIENT_SECRET` is correct
- `REDDIT_USERNAME` matches bot account
- `REDDIT_PASSWORD` is correct
- No extra spaces or quotes

#### 2. Reddit API Credentials

1. Go to https://www.reddit.com/prefs/apps
2. Verify your app exists
3. Check client ID and secret
4. Ensure app type is "script"

#### 3. Account Suspended

Check if bot account is suspended:
- Log into Reddit as the bot
- Check for suspension notices

#### 4. Password Changed

If you changed the Reddit password:

```bash
sudo nano /opt/reddit-bot/.env
# Update REDDIT_PASSWORD
sudo systemctl restart reddit-bot
```

#### 5. API Access

Reddit may temporarily block API access.

**Check**: https://www.redditstatus.com/

**Solution**: Wait for Reddit to resolve issues

---

## Service Won't Start

### Symptom

```bash
sudo systemctl start reddit-bot
# Fails immediately
```

### Solutions

#### 1. Check Status

```bash
sudo systemctl status reddit-bot -l
```

Look for specific error messages.

#### 2. Check Logs

```bash
sudo journalctl -u reddit-bot -n 50
```

Common errors:

**"FileNotFoundError: config.yaml"**
```bash
sudo cp /opt/reddit-bot/config.yaml.example /opt/reddit-bot/config.yaml
```

**"FileNotFoundError: .env"**
```bash
sudo nano /opt/reddit-bot/.env
# Add credentials
```

**"Permission denied"**
```bash
sudo chown -R reddit-bot:reddit-bot /opt/reddit-bot
sudo chmod 600 /opt/reddit-bot/.env
```

**"ModuleNotFoundError"**
```bash
cd /opt/reddit-bot
sudo -u reddit-bot pipenv install
```

#### 3. Test Manually

Run bot manually to see detailed errors:

```bash
sudo -u reddit-bot /usr/local/bin/pipenv run python /opt/reddit-bot/bot.py
```

#### 4. Check Permissions

```bash
ls -la /opt/reddit-bot/
```

Should show:
- Owner: `reddit-bot:reddit-bot`
- `.env` permissions: `600` (rw-------)

#### 5. Systemd Configuration

Verify service file:

```bash
sudo cat /etc/systemd/system/reddit-bot.service
```

Reload if modified:

```bash
sudo systemctl daemon-reload
sudo systemctl restart reddit-bot
```

---

## Rate Limiting

### Symptom

Bot is slow to respond or skipping mentions.

### Reddit API Limits

Reddit enforces rate limits:
- 60 requests per minute for OAuth
- 600 requests per 10 minutes

### Solutions

#### 1. Check Current Setting

```yaml
# config.yaml
bot:
  requests_per_minute: 60  # Default
```

#### 2. Adjust Rate Limit

If getting rate limited:

```yaml
bot:
  requests_per_minute: 30  # More conservative
```

Then:

```bash
sudo systemctl restart reddit-bot
```

#### 3. Monitor Rate Limit Errors

```bash
sudo journalctl -u reddit-bot | grep -i "rate"
```

#### 4. Check Request Timing

Bot enforces delays between requests. Check:

```yaml
bot:
  check_interval: 10  # Seconds between inbox checks
```

---

## Parameter Issues

### Symptom

Bot replies but parameters don't work as expected.

### Solutions

#### 1. Check Parameter Syntax

Correct:
```
/u/YourCommentOnAShirtBot shirtColor: black, size: xl
```

Incorrect:
```
/u/YourCommentOnAShirtBot shirtColor=black size=xl
```

Use `:` and commas.

#### 2. Check Valid Values

```bash
# Check logs for validation warnings
sudo journalctl -u reddit-bot | grep -i "invalid"
```

See [parameters.md](parameters.md) for valid values.

#### 3. Test Individual Parameters

Try one parameter at a time:

```
/u/YourCommentOnAShirtBot shirtColor: black
```

If this works, add more:

```
/u/YourCommentOnAShirtBot shirtColor: black, size: xl
```

#### 4. Check URL Encoding

The bot logs generated URLs. Check:

```bash
sudo journalctl -u reddit-bot | grep "Generated URL"
```

Verify parameters are encoded correctly.

#### 5. Parameter Aliases

Remember aliases:
- `shirtColor` → `attribute_pa_color`
- `textColor` → `color`
- `fontSize` → `fontsize`

Both forms work.

---

## Deployment Problems

### RPM Build Fails

#### Docker Not Running

```bash
# Check Docker
docker --version
docker ps

# Start Docker if needed
sudo systemctl start docker
```

#### Permission Issues

```bash
# Add user to docker group
sudo usermod -aG docker $USER
# Log out and back in
```

#### Missing Files

Ensure these exist:
- `packaging/reddit-bot.spec`
- `packaging/Dockerfile`
- `packaging/build-rpm.sh`

### RPM Install Fails

#### Check RPM

```bash
rpm -qp --info dist/reddit-bot-*.rpm
```

#### Dependencies

```bash
# Install dependencies manually if needed
sudo yum install python3 python3-pip
```

#### Cleanup Old Installation

```bash
sudo rpm -e reddit-bot
sudo rm -rf /opt/reddit-bot
sudo rpm -ivh /tmp/reddit-bot-*.rpm
```

---

## Log Analysis

### View Logs

#### Systemd Journal

```bash
# Live tail
sudo journalctl -u reddit-bot -f

# Last 100 lines
sudo journalctl -u reddit-bot -n 100

# Since yesterday
sudo journalctl -u reddit-bot --since yesterday

# Errors only
sudo journalctl -u reddit-bot -p err
```

#### Log Files

```bash
# Main log
sudo tail -f /var/log/reddit-bot/bot.log

# Error log
sudo tail -f /var/log/reddit-bot/bot.error.log
```

### Common Log Messages

#### Normal Operation

```
INFO - YourCommentOnAShirt Reddit Bot Starting
INFO - Successfully authenticated as: YourCommentOnAShirtBot
INFO - Starting mention monitoring loop...
INFO - Processing mention abc123 from u/User in r/subreddit
INFO - Generated URL: https://...
INFO - Posted reply to mention abc123
```

#### Warnings

```
WARNING - Invalid value for parameter: fontSize (too large)
WARNING - Comment author is None (deleted?), skipping author attribution
WARNING - Rate limiting: sleeping 2.5s
```

#### Errors

```
ERROR - Failed to authenticate with Reddit: Invalid credentials
ERROR - Error processing mention abc123: ...
ERROR - Error traversing to parent at level 1: API Error
```

### Debug Mode

Enable debug logging:

```yaml
# config.yaml
logging:
  log_level: "DEBUG"
```

Restart:

```bash
sudo systemctl restart reddit-bot
```

Debug logs show:
- Parsed parameters
- Comment tree traversal steps
- Rate limiting decisions
- Detailed error traces

---

## Performance Issues

### High Memory Usage

```bash
# Check memory
free -h

# Check bot process
ps aux | grep reddit-bot
```

**Solution**: Bot should use minimal memory. If high, restart:

```bash
sudo systemctl restart reddit-bot
```

### High CPU Usage

Bot should use minimal CPU when idle.

**Check for infinite loops**:

```bash
sudo journalctl -u reddit-bot -n 1000 | grep -c "Processing mention"
```

If abnormally high, check for issues.

### Slow Response Time

1. **Check rate limiting**: May be intentional delays
2. **Check Reddit API status**: https://www.redditstatus.com/
3. **Check network latency**: `ping reddit.com`

---

## Testing Issues

### Tests Fail Locally

#### Missing Dependencies

```bash
pipenv install --dev
```

#### Import Errors

```bash
# Activate environment
pipenv shell

# Or use pipenv run
pipenv run pytest
```

#### Environment Variables

Tests use mocked credentials, but some may need `.env`:

```bash
cp .env.example .env
```

### Coverage Below 90%

```bash
pipenv run pytest --cov=src --cov-report=term-missing
```

Add tests for uncovered lines.

---

## Getting Help

### 1. Check Logs First

Always start with logs:

```bash
sudo journalctl -u reddit-bot -n 100
```

### 2. Enable Debug Mode

Temporarily enable debug logging for more info.

### 3. Test Manually

Run bot manually to see immediate errors:

```bash
sudo -u reddit-bot /usr/local/bin/pipenv run python /opt/reddit-bot/bot.py
```

Press Ctrl+C to stop.

### 4. Verify Configuration

```bash
# Check files exist
ls -la /opt/reddit-bot/

# Check contents
sudo cat /opt/reddit-bot/.env
sudo cat /opt/reddit-bot/config.yaml
```

### 5. Search Documentation

- [DESIGN_DOC.md](../DESIGN_DOC.md) - Technical details
- [parameters.md](parameters.md) - Parameter reference
- [deployment.md](deployment.md) - Deployment guide
- [development.md](development.md) - Development guide

### 6. Contact Support

If issue persists:
- Collect relevant logs
- Document steps to reproduce
- Email: support@yourcommentonashirt.com

---

## Quick Diagnostics Script

Save as `diagnose.sh`:

```bash
#!/bin/bash

echo "=== Reddit Bot Diagnostics ==="
echo ""

echo "Service Status:"
sudo systemctl status reddit-bot --no-pager

echo ""
echo "Recent Logs (last 20 lines):"
sudo journalctl -u reddit-bot -n 20 --no-pager

echo ""
echo "Configuration Files:"
ls -l /opt/reddit-bot/.env /opt/reddit-bot/config.yaml 2>&1

echo ""
echo "Process Info:"
ps aux | grep reddit-bot | grep -v grep

echo ""
echo "Disk Space:"
df -h /opt/reddit-bot /var/log/reddit-bot

echo ""
echo "Memory:"
free -h
```

Run:

```bash
chmod +x diagnose.sh
./diagnose.sh > diagnostic-report.txt
```

---

## Emergency Recovery

If bot is completely broken:

### 1. Stop Service

```bash
sudo systemctl stop reddit-bot
```

### 2. Backup Configuration

```bash
sudo cp /opt/reddit-bot/.env ~/reddit-bot-env.backup
sudo cp /opt/reddit-bot/config.yaml ~/reddit-bot-config.backup
```

### 3. Reinstall

```bash
sudo rpm -e reddit-bot
sudo rpm -ivh /tmp/reddit-bot-*.rpm
```

### 4. Restore Configuration

```bash
sudo cp ~/reddit-bot-env.backup /opt/reddit-bot/.env
sudo cp ~/reddit-bot-config.backup /opt/reddit-bot/config.yaml
sudo chown reddit-bot:reddit-bot /opt/reddit-bot/.env /opt/reddit-bot/config.yaml
```

### 5. Restart

```bash
sudo systemctl start reddit-bot
sudo systemctl status reddit-bot
```

---

## Preventive Measures

1. **Regular backups** of `.env` and `config.yaml`
2. **Monitor logs** periodically
3. **Test in development** before deploying
4. **Keep documentation** updated
5. **Version control** for configuration changes

---

**Still having issues?** Contact support@yourcommentonashirt.com with:
- Output from diagnostic script
- Relevant log excerpts
- Steps to reproduce the issue

