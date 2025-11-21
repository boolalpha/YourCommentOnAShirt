# Quick Start: Real API Testing (15-Minute Setup)

**Goal**: Get your bot running with Reddit's real API in ~15 minutes  
**Level**: You've completed local testing with mocks, ready for real Reddit

---

## Prerequisites

- ✅ All 61 tests passing
- ✅ Bot code complete and tested
- ✅ Have a Reddit account (for creating the app and bot account)
- ✅ Terminal/command line access

---

## Part 1: Reddit Setup (5 minutes)

### Step 1: Create Reddit App
1. Go to: https://www.reddit.com/prefs/apps
2. Click "create another app"
3. Fill out:
   - **Name**: `YourCommentOnAShirtBot`
   - **Type**: ☑️ **script**
   - **Redirect URI**: `http://localhost:8080`
4. Click "create app"
5. **Save these:**
   - **Client ID**: (under "personal use script") 
   - **Client Secret**: (labeled "secret")

### Step 2: Create Bot Account
1. Open private/incognito browser window
2. Go to: https://www.reddit.com/register
3. Create account:
   - **Username**: `YourCommentOnAShirtBot`
   - **Password**: [strong password]
   - **Email**: [valid email]
4. Verify email
5. Optional: Mark as bot account in profile settings

**⚠️ Important:** New accounts need ~24 hours or a few karma points before posting. Plan accordingly!

---

## Part 2: Local Configuration (3 minutes)

### Step 3: Configure Credentials

```bash
# Navigate to project
cd /Users/iggywright/Desktop/Projects/YourCommentOnAShirt/SocialBots/reddit

# Edit .env file (create if doesn't exist)
nano .env
```

**Add your credentials:**
```bash
REDDIT_CLIENT_ID=YourClientIdHere
REDDIT_CLIENT_SECRET=YourClientSecretHere
REDDIT_USERNAME=YourCommentOnAShirtBot
REDDIT_PASSWORD=YourBotPasswordHere
```

Save and exit (Ctrl+O, Enter, Ctrl+X)

### Step 4: Configure for Testing

```bash
# Edit config.yaml
nano config.yaml
```

**Set DEBUG logging:**
```yaml
logging:
  log_level: "DEBUG"  # See everything during testing
  log_file: "logs/bot.log"
```

**Conservative rate limiting:**
```yaml
bot:
  requests_per_minute: 30  # Start conservative
  check_interval: 10
```

Save and exit

---

## Part 3: Test Environment (3 minutes)

### Step 5: Create Test Subreddit (Recommended)

1. Go to: https://www.reddit.com/subreddits/create
2. Create:
   - **Name**: `YourCommentBotTest` (or similar)
   - **Type**: **Private**
   - **Description**: "Testing bot"
3. Add `u/YourCommentOnAShirtBot` as approved user

**Alternative:** Use r/test (public, but less control)

---

## Part 4: First Run (4 minutes)

### Step 6: Start the Bot

```bash
# Ensure you're in the project directory
cd /Users/iggywright/Desktop/Projects/YourCommentOnAShirt/SocialBots/reddit

# Create logs directory if it doesn't exist
mkdir -p logs

# Start bot
pipenv run python bot.py
```

**Expected output:**
```
Loading configuration from config.yaml...
2025-11-09 10:30:00 - __main__ - INFO - ============================================================
2025-11-09 10:30:00 - __main__ - INFO - YourCommentOnAShirt Reddit Bot Starting
2025-11-09 10:30:00 - __main__ - INFO - ============================================================
2025-11-09 10:30:01 - src.bot.reddit_bot - INFO - Authenticating with Reddit API...
2025-11-09 10:30:02 - src.bot.reddit_bot - INFO - Successfully authenticated as: YourCommentOnAShirtBot
2025-11-09 10:30:02 - src.bot.reddit_bot - INFO - Starting mention monitoring loop...
```

✅ **Success!** Bot is running and waiting for mentions.

❌ **Error?** See [Troubleshooting](#troubleshooting) below.

### Step 7: Test with Real Mention

**In your test subreddit (from your main account):**

1. **Create a post** (any title/text)

2. **Add a comment:**
   ```
   This is my witty comment that should be on a shirt!
   ```

3. **Reply to that comment:**
   ```
   /u/YourCommentOnAShirtBot parent: 1, shirtColor: black
   ```

4. **Watch bot logs** - should process within 10-30 seconds

5. **Check Reddit** - bot should reply with shirt link

**Expected log output:**
```
INFO - Processing mention abc123 from u/YourUsername in r/YourCommentBotTest
DEBUG - Parsed parameters: {'parent': 1, 'shirtColor': 'black'}
INFO - Target comment: xyz789 by u/YourUsername  
INFO - Generated URL: https://yourcommentonashirt.com/shop/1?comment=This+is+my+...
INFO - Posted reply to mention abc123
```

---

## Verification Checklist

After first successful run:

- ✅ Bot authenticated successfully
- ✅ Bot detected the mention
- ✅ Bot parsed parameters correctly
- ✅ Bot navigated to correct comment (parent level)
- ✅ Bot generated a valid URL
- ✅ Bot posted a reply
- ✅ URL works when clicked (loads product page)
- ✅ No errors in logs

---

## What to Test

### Basic Tests (5-10 mentions)

1. **Self mention** (parent: 0)
2. **Parent mention** (parent: 1)
3. **Grandparent mention** (parent: 2)
4. **Different shirt colors** (white, black, red, etc.)
5. **Different sizes** (s, m, l, xl, 2xl)
6. **Custom text color** (textColor: ff0000)
7. **Different font sizes** (fontSize: 80, 120, 200)
8. **Author attribution** (addAuthor: true)
9. **Long comment** (test 280 char truncation)
10. **Special characters** (emoji, symbols, etc.)

### Example Mentions to Try

```
/u/YourCommentOnAShirtBot parent: 1

/u/YourCommentOnAShirtBot parent: 1, shirtColor: black, size: xl

/u/YourCommentOnAShirtBot parent: 1, textColor: ff0000, fontSize: 150

/u/YourCommentOnAShirtBot parent: 1, addAuthor: true

/u/YourCommentOnAShirtBot parent: 2, shirtColor: royal, size: 2xl, fontSize: 100
```

---

## Stopping the Bot

Press `Ctrl+C` in the terminal:

```
^C
2025-11-09 10:45:00 - src.bot.reddit_bot - INFO - Received keyboard interrupt, shutting down...
2025-11-09 10:45:00 - __main__ - INFO - Received signal 2, initiating graceful shutdown...
```

Bot stops cleanly.

---

## Troubleshooting

### ❌ Authentication Failed

**Error:**
```
ERROR - Failed to authenticate with Reddit: ...
```

**Fix:**
1. Double-check credentials in `.env`
2. Ensure Reddit app is type "script" (not "web app")
3. Verify bot account credentials (try logging into Reddit manually)
4. Check for typos in client ID/secret

### ❌ Bot Doesn't See Mentions

**Symptoms:** Bot runs, but doesn't process mentions

**Fix:**
1. Verify username is **exactly** `YourCommentOnAShirtBot`
2. Check spelling: `/u/YourCommentOnAShirtBot` (not @)
3. Ensure bot account can post (has some karma, not rate-limited)
4. Wait 24 hours for new account restrictions to lift

### ❌ "RATELIMIT" Error

**Error:**
```
praw.exceptions.RedditAPIException: RATELIMIT
```

**Fix:**
1. Lower `requests_per_minute` in config.yaml to 20-30
2. Increase `check_interval` to 20-30 seconds
3. Wait a few minutes for rate limit to reset
4. Ensure bot account has sufficient karma/age

### ❌ Bot Replies But URL Broken

**Symptoms:** Bot posts reply, but link doesn't work

**Fix:**
1. Check `base_url` in config.yaml
2. Verify https://yourcommentonashirt.com/shop/1 is correct
3. Test URL manually in browser
4. Check logs for generated URL

### ❌ Permission Error / Can't Post

**Symptoms:** Bot tries to reply but fails

**Fix:**
1. Verify bot account is not shadowbanned
2. Check bot account karma (might need a few points)
3. Wait 24 hours for new account restrictions
4. Test in your own subreddit first

---

## Monitoring Commands

```bash
# View logs in real-time
tail -f logs/bot.log

# Search for errors
grep -i error logs/bot.log

# Count processed mentions
grep "Posted reply" logs/bot.log | wc -l

# Check authentication
grep "authenticated" logs/bot.log
```

---

## Next Steps

Once you've verified the bot works with real mentions:

1. ✅ **Run for 1-2 hours** to ensure stability
2. ✅ **Test all parameter combinations**
3. ✅ **Verify URLs are correct**
4. ✅ **Check error handling** (deleted comments, etc.)
5. ✅ **Monitor resource usage**

Then move to: **[Production Deployment](production-deployment.md)**

---

## Configuration Files Reference

### .env (credentials)
```bash
REDDIT_CLIENT_ID=your_client_id
REDDIT_CLIENT_SECRET=your_client_secret  
REDDIT_USERNAME=YourCommentOnAShirtBot
REDDIT_PASSWORD=your_password
```

### config.yaml (settings)
```yaml
reddit:
  user_agent: "YourCommentOnAShirtBot/1.0"

bot:
  base_url: "https://yourcommentonashirt.com/shop/1"
  max_comment_length: 280
  reply_template: "[Here's your shirt!]({url})"
  requests_per_minute: 30  # Conservative for testing
  check_interval: 10

logging:
  log_file: "logs/bot.log"
  log_level: "DEBUG"  # Verbose for testing
```

---

## Getting Help

If you encounter issues:

1. **Check logs**: `tail -f logs/bot.log`
2. **Review error messages**: Usually self-explanatory
3. **Verify configuration**: Credentials correct? Config valid YAML?
4. **Test manually**: Can you log into Reddit with bot credentials?
5. **Reddit status**: Check https://www.redditstatus.com/

**Common Issues:**
- New account restrictions (24hr wait)
- Rate limiting (reduce request frequency)
- Authentication errors (double-check credentials)
- Network issues (check connection)

---

## Summary

**You just:**
1. ✅ Created Reddit app credentials
2. ✅ Created bot account
3. ✅ Configured local environment
4. ✅ Started bot with real API
5. ✅ Tested with real mentions
6. ✅ Verified bot works end-to-end

**Estimated time:** 15-30 minutes (plus 24hr wait for new accounts)

**Status:** 🎉 **Bot is live and working with Reddit's real API!**

---

**Next:** See [real-api-testing.md](real-api-testing.md) for comprehensive testing guide  
**Then:** See [production-deployment.md](production-deployment.md) for EC2 deployment

---

**Last Updated**: November 9, 2025  
**Version**: 1.0  
**Status**: Ready for use

