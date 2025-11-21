# Reddit Bot - Real API Testing Guide

**Status**: Ready for real API testing  
**Prerequisites**: 61 tests passing, 87% coverage, all components implemented

---

## Table of Contents

1. [Phase 1: Reddit App Setup](#phase-1-reddit-app-setup)
2. [Phase 2: Bot Account Setup](#phase-2-bot-account-setup)
3. [Phase 3: Local Configuration](#phase-3-local-configuration)
4. [Phase 4: Test Environment Setup](#phase-4-test-environment-setup)
5. [Phase 5: Local Testing](#phase-5-local-testing)
6. [Phase 6: Verification & Monitoring](#phase-6-verification--monitoring)
7. [Troubleshooting](#troubleshooting)

---

## Phase 1: Reddit App Setup

### Step 1.1: Create Reddit Application

1. **Log into your main Reddit account** (not the bot account yet)
   - Go to: https://www.reddit.com/prefs/apps
   - Scroll to "Developed Applications"

2. **Click "create another app..." or "are you a developer? create an app..."**

3. **Fill out the form:**
   ```
   Name: YourCommentOnAShirtBot
   App type: [✓] script
   Description: Bot that generates custom t-shirt links from Reddit comments
   About URL: https://yourcommentonashirt.com (optional)
   Redirect URI: http://localhost:8080 (required but not used for script apps)
   ```

4. **Click "create app"**

5. **Save your credentials:**
   - **Client ID**: The short string under "personal use script" (looks like: `AbCdEfGhIjKlMnO`)
   - **Client Secret**: The longer string labeled "secret" (looks like: `AbCdEfGhIjKlMnOpQrStUvWxYz0123`)

### Step 1.2: Understanding OAuth for Script Apps

**What is a Script App?**
- Script apps are for personal use or bots you control
- They use "password flow" OAuth (username/password authentication)
- Rate limit: ~60 requests per minute per authenticated client
- Perfect for bots that run continuously

**Why Script Type?**
- Web apps require user interaction for OAuth
- Script apps can authenticate automatically
- Suitable for server-side bot deployment

---

## Phase 2: Bot Account Setup

### Step 2.1: Create Bot Account

1. **Log out of Reddit (or use private browsing)**

2. **Create new account:**
   - Go to: https://www.reddit.com/register
   - Username: `YourCommentOnAShirtBot`
   - Email: Use a real email (for password recovery)
   - Password: Use a strong password (you'll need it for authentication)

3. **Verify email** (check your inbox)

4. **Important first-time setup:**
   - Reddit requires new accounts to have some karma before posting
   - **Option A**: Make 1-2 legitimate comments in friendly subreddits to get a few karma points
   - **Option B**: Wait 24 hours for the account to age (recommended)

### Step 2.2: Configure Bot Account

1. **Log into the bot account**: `u/YourCommentOnAShirtBot`

2. **Go to Preferences:**
   - https://www.reddit.com/settings/profile
   - Mark as bot account: Settings → Profile → "Mark account as bot"
   - This adds the "Bot Account" flair to your profile

3. **Set up notifications:**
   - Enable inbox notifications for mentions
   - This helps you monitor bot activity

---

## Phase 3: Local Configuration

### Step 3.1: Create .env File

Copy the example and fill in your credentials:

```bash
cd /Users/iggywright/Desktop/Projects/YourCommentOnAShirt/SocialBots/reddit
cp .env.example .env
```

Edit `.env` with your credentials:

```bash
# Your credentials from Phase 1 & 2
REDDIT_CLIENT_ID=AbCdEfGhIjKlMnO
REDDIT_CLIENT_SECRET=AbCdEfGhIjKlMnOpQrStUvWxYz0123
REDDIT_USERNAME=YourCommentOnAShirtBot
REDDIT_PASSWORD=your_actual_bot_password
```

**Security Check:**
- ✅ Verify `.env` is in `.gitignore`
- ✅ Never commit credentials to git
- ✅ Use environment-specific .env files (local vs production)

### Step 3.2: Configure for Testing

Edit `config.yaml` for testing mode:

```yaml
reddit:
  user_agent: "YourCommentOnAShirtBot/1.0 (testing)"

bot:
  base_url: "https://yourcommentonashirt.com/shop/1"
  max_comment_length: 280
  reply_template: "[Here's your shirt!]({url})"
  max_retries: 3
  retry_backoff_factor: 2
  requests_per_minute: 30  # Conservative for testing
  check_interval: 10
  skip_existing_on_startup: true  # Don't process old mentions

logging:
  log_file: "logs/bot.log"
  log_level: "DEBUG"  # Verbose logging for testing
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

---

## Phase 4: Test Environment Setup

### Step 4.1: Create Private Test Subreddit

**Why?** Test safely without spamming real subreddits

1. **Create a subreddit:**
   - Go to: https://www.reddit.com/subreddits/create
   - Name: `YourCommentTestBot` (or similar)
   - Type: **Private**
   - Description: "Testing environment for YourCommentOnAShirtBot"

2. **Add the bot as approved user:**
   - Go to subreddit settings → Approved Users
   - Add: `YourCommentOnAShirtBot`

3. **Test from your main account:**
   - Join the private subreddit with your main account
   - You'll post test mentions here

### Step 4.2: Alternative Testing Methods

**Option A: Use r/test** (public test subreddit)
- Less control, but no setup needed
- Posts are auto-deleted after some time

**Option B: Use direct messages**
- Send messages mentioning the bot
- Bot can read inbox mentions

**Option C: Create test posts in r/test**
- Make a post, then comment with mentions
- Good for testing comment tree navigation

---

## Phase 5: Local Testing

### Step 5.1: Pre-flight Checks

Verify everything is ready:

```bash
# 1. Check environment
cd /Users/iggywright/Desktop/Projects/YourCommentOnAShirt/SocialBots/reddit

# 2. Verify dependencies
pipenv --version
pipenv check

# 3. Run tests (ensure all pass)
pipenv run pytest -v

# 4. Check configuration files exist
ls -la .env config.yaml

# 5. Verify logs directory
mkdir -p logs
```

### Step 5.2: First Test Run (Dry Run)

Start the bot with verbose logging:

```bash
# Run in foreground with debug logging
pipenv run python bot.py

# Or with custom config
pipenv run python bot.py --config config.yaml
```

**Expected Output:**
```
Loading configuration from config.yaml...
2025-11-09 10:30:00 - __main__ - INFO - ============================================================
2025-11-09 10:30:00 - __main__ - INFO - YourCommentOnAShirt Reddit Bot Starting
2025-11-09 10:30:00 - __main__ - INFO - ============================================================
2025-11-09 10:30:00 - __main__ - INFO - Initializing bot...
2025-11-09 10:30:01 - src.bot.reddit_bot - INFO - Authenticating with Reddit API...
2025-11-09 10:30:02 - src.bot.reddit_bot - INFO - Successfully authenticated as: YourCommentOnAShirtBot
2025-11-09 10:30:02 - __main__ - INFO - Starting main event loop...
2025-11-09 10:30:02 - src.bot.reddit_bot - INFO - Starting mention monitoring loop...
```

**If authentication fails**, see [Troubleshooting](#troubleshooting) section.

### Step 5.3: Create Test Mention

**In your test subreddit (from your main account):**

1. **Create a post:**
   ```
   Title: "Test Post for Bot"
   Body: "This is a test post."
   ```

2. **Add first comment:**
   ```
   This is a hilarious comment!
   ```

3. **Reply to that comment with a mention:**
   ```
   /u/YourCommentOnAShirtBot parent: 1, shirtColor: black, size: xl
   ```

4. **Watch the bot logs** - you should see:
   ```
   2025-11-09 10:31:15 - src.bot.reddit_bot - INFO - Processing mention abc123 from u/YourUsername in r/YourCommentTestBot
   2025-11-09 10:31:15 - src.bot.reddit_bot - DEBUG - Parsed parameters: {'parent': 1, 'shirtColor': 'black', 'size': 'xl'}
   2025-11-09 10:31:15 - src.bot.reddit_bot - INFO - Target comment: xyz789 by u/YourUsername
   2025-11-09 10:31:15 - src.bot.reddit_bot - DEBUG - Comment text (truncated): This is a hilarious comment!
   2025-11-09 10:31:15 - src.bot.reddit_bot - INFO - Generated URL: https://yourcommentonashirt.com/shop/1?comment=This+is+a+hilarious+comment%21&...
   2025-11-09 10:31:16 - src.bot.reddit_bot - INFO - Posted reply to mention abc123
   ```

5. **Check Reddit** - bot should have replied with the shirt link!

### Step 5.4: Comprehensive Test Cases

Test each major feature:

#### Test 1: Basic Mention (Self)
```
Comment: "Amazing quote!"
Mention: /u/YourCommentOnAShirtBot parent: 0
Expected: Uses "Amazing quote!" as text
```

#### Test 2: Parent Comment
```
Parent: "This made my day!"
Child: /u/YourCommentOnAShirtBot parent: 1
Expected: Uses parent comment text
```

#### Test 3: Grandparent Comment
```
Grandparent: "Best comment ever"
Parent: "I agree!"
Child: /u/YourCommentOnAShirtBot parent: 2
Expected: Uses grandparent comment text
```

#### Test 4: Custom Parameters
```
/u/YourCommentOnAShirtBot parent: 1, shirtColor: black, size: 2xl, textColor: ff0000, fontSize: 150
Expected: All parameters in URL
```

#### Test 5: Author Attribution
```
/u/YourCommentOnAShirtBot parent: 1, addAuthor: true
Expected: Includes author attribution
```

#### Test 6: Long Comment
```
Comment: [300+ character comment]
Mention: /u/YourCommentOnAShirtBot parent: 1
Expected: Truncated to 280 characters
```

#### Test 7: Special Characters
```
Comment: "I love pizza! 🍕 #foodie @everyone"
Mention: /u/YourCommentOnAShirtBot parent: 1
Expected: Proper URL encoding
```

### Step 5.5: Monitor Performance

Keep an eye on:

1. **Response Time:**
   - Should reply within 10-30 seconds (depending on `check_interval`)
   - Check logs for timing information

2. **Rate Limiting:**
   - Look for "Rate limiting: sleeping X" messages
   - Should not hit Reddit API rate limits (60/min)

3. **Error Handling:**
   - Test with deleted comments
   - Test with [removed] comments
   - Test with invalid parameters

4. **Memory Usage:**
   ```bash
   # In another terminal
   ps aux | grep python
   # Check VSZ/RSS columns
   ```

### Step 5.6: Graceful Shutdown

Test stopping the bot:

```bash
# Press Ctrl+C in the terminal running the bot
^C
```

**Expected Output:**
```
2025-11-09 10:35:00 - src.bot.reddit_bot - INFO - Received keyboard interrupt, shutting down...
2025-11-09 10:35:00 - __main__ - INFO - Received signal 2, initiating graceful shutdown...
```

Bot should exit cleanly without errors.

---

## Phase 6: Verification & Monitoring

### Step 6.1: Verify Bot is Working

**Checklist:**
- ✅ Bot authenticates successfully
- ✅ Bot detects mentions in inbox
- ✅ Bot parses parameters correctly
- ✅ Bot navigates comment tree (parent levels)
- ✅ Bot generates valid URLs
- ✅ Bot posts replies successfully
- ✅ Bot marks mentions as read (no duplicates)
- ✅ Bot respects rate limits
- ✅ Bot handles errors gracefully
- ✅ Bot logs all actions

### Step 6.2: Log Analysis

**Check logs for issues:**

```bash
# View live logs
tail -f logs/bot.log

# Search for errors
grep -i error logs/bot.log

# Search for warnings
grep -i warning logs/bot.log

# Count processed mentions
grep "Posted reply to mention" logs/bot.log | wc -l

# View authentication logs
grep -i "authenticated" logs/bot.log
```

### Step 6.3: Reddit Account Check

**Verify on Reddit:**
1. Go to: https://www.reddit.com/user/YourCommentOnAShirtBot
2. Check comment history - should see bot replies
3. Verify links work when clicked
4. Check for any error messages in replies

### Step 6.4: URL Validation

**Test generated URLs:**
1. Copy a generated URL from bot reply
2. Paste in browser
3. Verify product page loads correctly
4. Check that comment text appears in product customization
5. Verify all parameters (color, size, etc.) are correct

---

## Troubleshooting

### Issue: Authentication Fails

**Symptoms:**
```
ERROR - Failed to authenticate with Reddit: ...
```

**Solutions:**
1. **Verify credentials in .env:**
   ```bash
   cat .env | grep REDDIT_
   ```
   - Check for typos, extra spaces, quotes
   - Credentials should NOT be in quotes

2. **Check Reddit app type:**
   - Must be "script" type, not "web app"
   - Recreate app if needed

3. **Verify bot account:**
   - Can you log into Reddit with these credentials?
   - Account not suspended or banned?

4. **Check IP restrictions:**
   - Reddit may block VPNs or cloud IPs
   - Try from your home connection first

### Issue: Bot Doesn't See Mentions

**Symptoms:**
- Bot runs but doesn't process mentions
- No log entries for mentions

**Solutions:**
1. **Verify mention format:**
   - Must be: `/u/YourCommentOnAShirtBot` (exact username)
   - Case-insensitive but spelling must match

2. **Check skip_existing setting:**
   - Set `skip_existing_on_startup: false` to process old mentions
   - Restart bot after changing

3. **Inbox permissions:**
   - Bot account must have inbox enabled
   - Check Reddit account settings

4. **Rate limiting:**
   - New accounts may have restrictions
   - Wait 24 hours or gain some karma

### Issue: Bot Replies but URL is Broken

**Symptoms:**
- Bot posts reply successfully
- URL doesn't load or shows errors

**Solutions:**
1. **Check base_url in config.yaml:**
   ```yaml
   bot:
     base_url: "https://yourcommentonashirt.com/shop/1"
   ```

2. **Verify URL encoding:**
   - Check logs for generated URL
   - Test URL directly in browser

3. **Parameter validation:**
   - Check logs for parameter warnings
   - Verify parameter format matches docs

### Issue: Rate Limit Errors

**Symptoms:**
```
praw.exceptions.RedditAPIException: RATELIMIT
```

**Solutions:**
1. **Reduce requests_per_minute:**
   ```yaml
   bot:
     requests_per_minute: 30  # Lower value
   ```

2. **Increase check_interval:**
   ```yaml
   bot:
     check_interval: 30  # Check less frequently
   ```

3. **Wait for rate limit to reset:**
   - Reddit rate limits reset every minute
   - Bot will automatically retry

### Issue: Bot Crashes or Hangs

**Symptoms:**
- Bot stops responding
- Process exits unexpectedly

**Solutions:**
1. **Check logs for stack traces:**
   ```bash
   tail -100 logs/bot.log
   ```

2. **Memory issues:**
   - Monitor memory usage over time
   - Restart bot periodically if needed

3. **Network issues:**
   - Check internet connection
   - Reddit API may be down (check https://reddit.com/status)

4. **Run with debug logging:**
   ```yaml
   logging:
     log_level: "DEBUG"
   ```

### Issue: Duplicate Replies

**Symptoms:**
- Bot replies multiple times to same mention

**Solutions:**
1. **Check processed_mentions tracking:**
   - Bot tracks processed mentions in memory
   - Restarting bot resets this (by design)

2. **Verify mark_read is called:**
   - Check logs for "Posted reply to mention" followed by mark_read

3. **Avoid running multiple instances:**
   - Only run one bot instance at a time
   - Check for zombie processes:
     ```bash
     ps aux | grep bot.py
     ```

---

## Testing Best Practices

### Do's ✅

- Start with DEBUG logging
- Test in private subreddit first
- Monitor logs continuously during testing
- Test all parameter combinations
- Verify URLs work before mass deployment
- Test error cases (deleted comments, invalid params)
- Use conservative rate limits initially
- Keep credentials secure

### Don'ts ❌

- Don't test in large public subreddits initially
- Don't ignore authentication errors
- Don't run multiple bot instances simultaneously
- Don't commit .env file to git
- Don't spam mentions for testing
- Don't ignore rate limit warnings
- Don't skip the 24-hour account aging period

---

## Next Steps

Once local testing is successful:
1. ✅ All test cases pass
2. ✅ Bot runs stable for 1+ hours
3. ✅ URLs work correctly
4. ✅ No errors in logs
5. ✅ Rate limiting working properly

**You're ready for production deployment!**

See: [deployment.md](deployment.md) for EC2 deployment guide.

---

## Quick Reference

### Start Bot (Local Testing)
```bash
cd /Users/iggywright/Desktop/Projects/YourCommentOnAShirt/SocialBots/reddit
pipenv run python bot.py
```

### Stop Bot
```
Press Ctrl+C
```

### View Logs
```bash
tail -f logs/bot.log
```

### Run Tests
```bash
pipenv run pytest -v
```

### Check Configuration
```bash
pipenv run python -c "from pathlib import Path; from src.config.loader import Config; c = Config.load(Path('config.yaml')); print(f'User: {c.reddit_username}')"
```

---

**Last Updated**: November 9, 2025  
**Version**: 1.0  
**Status**: Ready for testing

