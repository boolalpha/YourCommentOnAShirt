# Reddit Bot Design Document – YourCommentOnAShirt

## 1. Overview

**Purpose**: Monitor Reddit for bot mentions (`/u/YourCommentOnAShirtBot`), extract the parent comment's text, and reply with a customized shirt link.

**Tech Stack**: Python 3, PRAW (Reddit API wrapper)

**Testing**: Test-driven development (TDD) required

---

## 2. Prerequisites

- Python 3.9+ with pipenv installed (`pip install pipenv`)
- Reddit API credentials (client_id, client_secret, user_agent)
- Reddit bot account created and authenticated
- Store ID: `16875297` (for Printful integration reference)

---

## 3. Bot Workflow

1. **Monitor**: Stream mentions from `reddit.inbox.stream()` or `reddit.subreddit('all').stream.mentions()`
2. **Extract**: Get comment text based on `origin` parameter (walks up comment tree)
3. **Parse**: Extract optional parameters from the mention comment body
4. **Build URL**: Construct product link with URL parameters
5. **Reply**: Post comment with generated link
6. **Mark Read**: Mark notification as read to avoid reprocessing

**Origin Parameter**: Controls which comment in the thread to use
- `origin: 0` = the mention comment itself (edge case)
- `origin: 1` = parent comment (default)
- `origin: 2` = grandparent comment
- `origin: N` = N levels up the comment tree

---

## 4. URL Structure

**Base URL**: `https://yourcommentonashirt.com/shop/1`

### Required Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `comment` | string | URL-encoded comment text | `Hello%2C+World%21` |

### Optional Parameters

| Parameter | Type | Valid Values | Default |
|-----------|------|--------------|---------|
| `color` | hex string | URL-encoded hex (e.g., `%23ffffff`) | `%23000000` (black) |
| `align` | string | `left`, `center`, `right` | `center` |
| `valign` | string | `top`, `center`, `bottom` | `center` |
| `fontsize` | integer | `20-300` | `120` |
| `font` | integer | `0-20` (font index) | `12` (NotoSans) |
| `attribute_pa_color` | string | `white`, `black`, `gold`, `irish-green`, `orange`, `red`, `royal` | `white` |
| `attribute_pa_size` | string | `xs`, `s`, `m`, `l`, `xl`, `2xl`, `3xl` | `l` |

### Font Index Reference

```python
AVAILABLE_FONTS = [
    'Annie Use Your Telescope',  # 0
    'Asset',                     # 1
    'BBH Sans Bartle',          # 2
    'BBH Sans Bogle',           # 3
    'BBH Sans Hegarty',         # 4
    'Butcherman',               # 5
    'Creepster',                # 6
    'Domine',                   # 7
    'Inter',                    # 8
    'Jolly Lodger',             # 9
    'Kablammo',                 # 10
    'Nosifer',                  # 11
    'NotoSans',                 # 12 (DEFAULT)
    'Oooh Baby',                # 13
    'Open Sans',                # 14
    'Orbitron',                 # 15
    'Playwrite AU TAS',         # 16
    'Press Start 2P',           # 17
    'Roboto',                   # 18
    'Rubik Puddles',            # 19
    'Trade Winds'               # 20
]
```

---

## 5. Bot Behavior Examples

### Example 1: Basic Usage (Default - Parent Comment)
**Reddit Thread**:
```
User A: "This is the best comment ever!"
User B: "/u/YourCommentOnAShirtBot"
```

**Bot Reply**: Links to User A's comment (origin: 1 default)
```
[Here's your shirt!](https://yourcommentonashirt.com/shop/1?comment=This+is+the+best+comment+ever%21)
```

### Example 2: With Parameters
**Reddit Thread**:
```
User A: "I love Python!"
User B: "/u/YourCommentOnAShirtBot shirtColor: black, textColor: #00ff00, size: xl"
```

**Bot Reply**: Links to User A's comment with custom styling
```
[Here's your shirt!](https://yourcommentonashirt.com/shop/1?comment=I+love+Python%21&attribute_pa_color=black&color=%2300ff00&attribute_pa_size=xl)
```

### Example 3: Origin Parameter (Walking Up Comment Tree)
**Reddit Thread**:
```
User A: "Original joke here"
  User B: "That's hilarious!"
    User C: "I agree!"
      User D: "/u/YourCommentOnAShirtBot origin: 2"
```

**Bot Reply**: Links to User B's comment (2 levels up from User D)
```
[Here's your shirt!](https://yourcommentonashirt.com/shop/1?comment=That%27s+hilarious%21)
```

### Example 4: Author Attribution
**Reddit Thread**:
```
User A: "Never gonna give you up"
User B: "/u/YourCommentOnAShirtBot addAuthor: true"
```

**Bot Reply**: Includes author attribution
```
[Here's your shirt!](https://yourcommentonashirt.com/shop/1?comment=Never+gonna+give+you+up%0A-+%40UserA)
```

**With Custom Author**:
```
User B: "/u/YourCommentOnAShirtBot author: Rick Astley"
```

**Bot Reply**: Custom attribution
```
[Here's your shirt!](https://yourcommentonashirt.com/shop/1?comment=Never+gonna+give+you+up%0A-+Rick+Astley)
```

---

## 6. Parameter Parsing Rules

**Format**: `key: value` pairs separated by commas

**Special Parameters** (control bot behavior, not passed to URL):
- `origin` → integer (0-N) - which comment in tree to use [default: 1]
- `addAuthor` → boolean - append "\n- @username" to comment text [default: false]
- `author` → string - append "\n- <text>" to comment text (overrides addAuthor)

**Aliases** (map user-friendly terms to URL parameters):
- `shirtColor` → `attribute_pa_color`
- `textColor` → `color` (must be hex, auto-prefix with `#` if missing)
- `size` → `attribute_pa_size`
- `fontSize` → `fontsize`
- `textAlign` → `align`
- `verticalAlign` → `valign`

**Validation**:
- Validate all values against allowed ranges/choices
- Use defaults for invalid values
- If `origin` exceeds tree depth, use top-level comment
- Log warnings for unrecognized parameters

---

## 7. Configuration Management

**Architecture**: Secrets in `.env` (git-ignored), configuration in `config.yaml` (version-controlled)

**Package Management**: Use `pipenv` for virtual environment and dependency management

**Config Validation**:
- Validate all config values on startup (types, ranges, required fields)
- Fail fast with clear error messages if config is invalid
- Log warnings for deprecated or unknown config keys

**Hot Reloading** (if possible):
- Watch `config.yaml` for changes
- Reload non-critical settings without service restart (e.g., log level, reply template, retry settings)
- Critical settings require restart (e.g., Reddit credentials, base URL)

---

### `.env` - Secrets (add to `.gitignore`)

```bash
# Reddit API Credentials
REDDIT_CLIENT_ID=your_client_id_here
REDDIT_CLIENT_SECRET=your_client_secret_here
REDDIT_USERNAME=YourCommentOnAShirtBot
REDDIT_PASSWORD=your_password_here
```

### `config.yaml` - Non-Secret Configuration

```yaml
reddit:
  user_agent: "YourCommentOnAShirtBot/1.0"

bot:
  base_url: "https://yourcommentonashirt.com/shop/1"
  max_comment_length: 280
  reply_template: "[Here's your shirt!]({url})"
  
  # Retry configuration
  max_retries: 3
  retry_backoff_factor: 2  # seconds: 2, 4, 8
  
  # Rate limiting
  requests_per_minute: 60
  check_interval: 10  # seconds between mention checks

logging:
  log_file: "bot.log"
  log_level: "INFO"  # DEBUG, INFO, WARNING, ERROR, CRITICAL
  log_rotation_max_bytes: 10485760  # 10MB
  log_rotation_backup_count: 5

defaults:
  # Default values for URL parameters
  text_color: "#000000"
  text_align: "center"
  vertical_align: "center"
  font_size: 120
  font_index: 12  # NotoSans
  shirt_color: "white"
  shirt_size: "l"
```

---

## 9. Packaging & Build

**Strategy**: Build RPM packages using Docker, deploy to Amazon Linux EC2

**Build Process:**
- Use Docker with Amazon Linux 2023 as build environment
- Create RPM spec file defining package structure and dependencies
- Build script creates tarball (excluding .git, .venv, .env, Pipfile.lock) and runs rpmbuild
- RPM post-install hook runs `pipenv install --deploy` to create virtualenv and install dependencies
- RPM pre-uninstall hook stops and disables systemd service

**Deployment:**
- Install RPM on EC2: `sudo rpm -ivh reddit-bot-*.rpm`
- Configure `.env` with secrets (not included in package)
- Service auto-starts on boot via systemd

**Updates:**
- Increment version, rebuild RPM, upgrade with `rpm -Uvh`

---

## 10. Bot Architecture & Streaming

### How Reddit Streaming Works

**PRAW Streaming** uses long-polling to continuously check for new mentions:

```python
# Infinite loop that runs 24/7
for mention in reddit.inbox.stream(skip_existing=True):
    process_mention(mention)
```

### Process Management with systemd

**Service File** (`/etc/systemd/system/reddit-bot.service`):

```ini
[Unit]
Description=YourCommentOnAShirt Reddit Bot
After=network.target

[Service]
Type=simple
User=reddit-bot
WorkingDirectory=/opt/reddit-bot
ExecStart=/usr/local/bin/pipenv run python bot.py
Restart=always
RestartSec=10
StandardOutput=append:/var/log/reddit-bot/bot.log
StandardError=append:/var/log/reddit-bot/bot.error.log

# Graceful shutdown
KillMode=mixed
KillSignal=SIGTERM
TimeoutStopSec=30

[Install]
WantedBy=multi-user.target
```

---

## 11. Logging

**Log Location**: `/var/log/reddit-bot/bot.log`

**Log Levels**:
- `INFO`: Bot startup, mentions processed, replies sent
- `WARNING`: Rate limits, validation failures, retries
- `ERROR`: API failures, crashes, unhandled exceptions

**What to Log**:
- Mention received (username, comment ID, origin level used)
- Comment tree traversal (how many levels walked up)
- Parameters parsed from mention text
- Comment text modifications (author attribution added)
- Generated URL
- Reply posted (success/failure)
- Errors with full stack traces

**Log Rotation**:
- Max file size: 10MB
- Keep 5 backup files
- Use Python's `RotatingFileHandler` or systemd's log management

**Access Logs**:
- systemd: `sudo journalctl -u reddit-bot -f`
- Direct: `tail -f /var/log/reddit-bot/bot.log`

**Format**: Timestamp, log level, message (structured for easy parsing)

---

## 12. Deployment Checklist

**Initial Setup:**
1. Build RPM package using Docker (`./build-rpm.sh`)
2. Copy RPM to EC2 instance
3. Install: `sudo rpm -ivh reddit-bot-*.rpm`
4. Configure `.env` with Reddit API credentials
5. Enable service: `sudo systemctl enable reddit-bot`
6. Start service: `sudo systemctl start reddit-bot`

**Production Environment:**
- **Host**: AWS EC2 instance BoolAlphaV2 (same as WordPress)
- **Install Path**: `/opt/reddit-bot/`
- **Process Manager**: systemd (configured in RPM)
- **Logs**: `/var/log/reddit-bot/bot.log`
- **Service**: Auto-starts on boot, auto-restarts on crash

---

## 13. Implementation Checklist

### Core Project Setup
- [ ] Project directory structure (src/, tests/, config/, logs/, packaging/)
- [ ] .gitignore file (.env, __pycache__, *.pyc, .venv, *.log, *.rpm)
- [ ] Pipfile with dependencies (praw, python-dotenv, pyyaml, pytest, pytest-mock)
- [ ] Example config files (.env.example, config.yaml.example)
- [ ] Main entry point (bot.py or src/main.py)

### Configuration & Authentication
- [ ] Configuration loader with validation (.env + config.yaml)
- [ ] Config schema validation (types, ranges, required fields)
- [ ] Config hot-reloading for non-critical settings
- [ ] PRAW authentication setup with credential validation
- [ ] Font constants (AVAILABLE_FONTS list with validation)

### Core Bot Logic
- [ ] Mention stream monitoring (reddit.inbox.stream with skip_existing)
- [ ] Mark mentions as read (prevent reprocessing)
- [ ] Duplicate response prevention (track processed mention IDs)
- [ ] Comment tree traversal with origin parameter support
- [ ] Comment length truncation (respect max_comment_length)
- [ ] Comment text modification (author attribution logic)

### Parameter Processing
- [ ] Parameter parser (extract key: value pairs from mention text)
- [ ] Parameter alias mapping (shirtColor → attribute_pa_color, etc.)
- [ ] Parameter type validation (integers, strings, hex colors)
- [ ] Parameter range validation (fontsize: 20-300, font: 0-20)
- [ ] Parameter choice validation (align, valign, shirt colors, sizes)
- [ ] Special parameter handling (origin, addAuthor, author)

### URL Generation
- [ ] URL builder with base URL construction
- [ ] URL parameter encoding (handle spaces, special chars)
- [ ] Hex color encoding (auto-prefix #, URL-encode as %23)
- [ ] Comment text encoding (preserve newlines as %0A)
- [ ] Reply formatter (apply reply_template from config)

### Error Handling & Resilience
- [ ] API error handling (Reddit API exceptions)
- [ ] Network error handling (timeouts, connection errors)
- [ ] Rate limit handling (respect Reddit API limits + config limits)
- [ ] Retry logic with exponential backoff (max_retries, backoff_factor)
- [ ] Validation error handling (invalid parameters → use defaults)
- [ ] Graceful degradation (if comment tree walk fails, handle gracefully)

### Logging & Monitoring
- [ ] Logging setup (RotatingFileHandler, configurable levels)
- [ ] Log mention received (username, comment_id, subreddit)
- [ ] Log parameters parsed (show defaults applied)
- [ ] Log tree traversal (levels walked, comment selected)
- [ ] Log URL generated (full URL for debugging)
- [ ] Log reply status (success/failure with details)
- [ ] Log errors with full stack traces

### Process Management
- [ ] Graceful shutdown handlers (SIGTERM/SIGINT)
- [ ] Rate limiting implementation (requests_per_minute enforcement)
- [ ] Stream reconnection logic (handle stream interruptions)
- [ ] Main event loop with error recovery

### Testing Infrastructure
- [ ] Pytest configuration (pytest.ini, conftest.py)
- [ ] Test fixtures (mock Reddit API, mock comments, sample configs)
- [ ] Unit tests: Configuration loader and validation
- [ ] Unit tests: Parameter parser (all aliases and validations)
- [ ] Unit tests: URL builder (encoding, special characters)
- [ ] Unit tests: Comment tree traversal (origin parameter)
- [ ] Unit tests: Author attribution logic
- [ ] Unit tests: Comment length truncation
- [ ] Unit tests: Error handling and retry logic
- [ ] Integration tests: Full workflow with mock Reddit API
- [ ] Integration tests: Edge cases (malformed input, missing parent)

### Packaging & Deployment
- [ ] RPM spec file (dependencies, file locations, permissions)
- [ ] Dockerfile for RPM build (Amazon Linux 2023 base)
- [ ] Build script (create tarball, run rpmbuild)
- [ ] Systemd service file (with proper restart and logging)
- [ ] RPM post-install script (create user, run pipenv install)
- [ ] RPM pre-uninstall script (stop service, cleanup)

### Documentation
- [ ] README.md (project overview, installation, configuration)
- [ ] Development setup guide (local testing without deploying)
- [ ] Deployment guide (RPM installation on EC2)
- [ ] API documentation (parameter reference)
- [ ] Troubleshooting guide (common issues and solutions)