# YourCommentOnAShirt Reddit Bot

A production-ready Reddit bot that monitors mentions and generates custom t-shirt product links for [YourCommentOnAShirt.com](https://yourcommentonashirt.com).

[![Python 3.9+](https://img.shields.io/badge/python-3.9+-blue.svg)](https://www.python.org/downloads/)
[![Tests](https://img.shields.io/badge/tests-61%20passed-success.svg)](tests/)
[![Coverage](https://img.shields.io/badge/coverage-87%25-success.svg)](htmlcov/)
[![Status](https://img.shields.io/badge/status-production%20ready-success.svg)]()

## 🚀 Ready for Real API Testing!

**Current Phase**: Local testing complete, ready for Reddit API testing and production deployment.

**Quick Start Guides:**
- **[15-Minute Quick Start](docs/quickstart-real-api.md)** - Get bot running with real Reddit API
- **[Comprehensive Testing Guide](docs/real-api-testing.md)** - Complete testing documentation
- **[Production Deployment Guide](docs/production-deployment.md)** - Deploy to AWS EC2

## Features

- 🤖 **Automated Mention Monitoring**: Continuously monitors Reddit for bot mentions
- 🎨 **Customizable Parameters**: Support for colors, sizes, fonts, alignment, and more
- 🔄 **Comment Tree Traversal**: Can target parent, grandparent, or any level up the comment tree
- ✍️ **Author Attribution**: Optional author attribution with custom text support
- 🛡️ **Production-Ready**: Comprehensive error handling, rate limiting, and retry logic
- 📦 **Easy Deployment**: RPM package for Amazon Linux with systemd service
- ✅ **Test-Driven Development**: 61 tests with 87% code coverage

## Quick Start

### Option 1: Real API Testing (Recommended - Start Here!)

**Get bot running with real Reddit in 15 minutes** → [Quick Start Guide](docs/quickstart-real-api.md)

```bash
# 1. Create Reddit app: https://www.reddit.com/prefs/apps
# 2. Create bot account: u/YourCommentOnAShirtBot
# 3. Configure credentials
cd /Users/iggywright/Desktop/Projects/YourCommentOnAShirt/SocialBots/reddit
nano .env  # Add your Reddit credentials

# 4. Run bot
pipenv run python bot.py

# 5. Test with a mention on Reddit
# In r/test: /u/YourCommentOnAShirtBot parent: 1
```

See **[Quick Start Guide](docs/quickstart-real-api.md)** for detailed steps.

### Option 2: Local Development (Testing with Mocks)

If you want to run tests or develop locally:

1. **Install dependencies**:
   ```bash
   cd SocialBots/reddit
   pipenv install --dev
   ```

2. **Run tests**:
   ```bash
   pipenv run pytest
   # All 61 tests should pass
   ```

3. **View coverage**:
   ```bash
   pipenv run pytest --cov=src --cov-report=html
   open htmlcov/index.html
   ```

See **[Development Guide](docs/development.md)** for detailed development workflow.

## Bot Usage Examples

### Example 1: Basic Usage
```
User A: "This is hilarious!"
User B: "/u/YourCommentOnAShirtBot"
```
Bot generates a link with User A's comment on a shirt.

### Example 2: With Custom Parameters
```
User A: "I love Python!"
User B: "/u/YourCommentOnAShirtBot shirtColor: black, textColor: #00ff00, size: xl"
```
Bot creates a link with custom shirt color (black), text color (green), and size (XL).

### Example 3: Targeting Grandparent Comment
```
User A: "Original funny comment"
  User B: "Haha that's great"
    User C: "I agree"
      User D: "/u/YourCommentOnAShirtBot parent: 2"
```
Bot uses User B's comment (2 levels up).

### Example 4: With Author Attribution
```
User A: "Never gonna give you up"
User B: "/u/YourCommentOnAShirtBot addAuthor: true"
```
Bot includes "- @UserA" on the shirt.

## Supported Parameters

| Parameter | Values | Default | Description |
|-----------|--------|---------|-------------|
| `parent` | 0-N | 1 | Which comment level to use (0=self, 1=parent, etc.) |
| `shirtColor` | white, black, gold, irish-green, orange, red, royal | white | Shirt color |
| `size` | xs, s, m, l, xl, 2xl, 3xl | l | Shirt size |
| `textColor` | Hex code (e.g., #ff0000) | #000000 | Text color |
| `fontSize` | 20-300 | 120 | Font size |
| `font` | 0-20 | 12 | Font index (see docs) |
| `textAlign` | left, center, right | center | Horizontal alignment |
| `verticalAlign` | top, center, bottom | center | Vertical alignment |
| `addAuthor` | true/false | false | Add "@username" attribution |
| `author` | Any text | - | Custom author text |

See [docs/parameters.md](docs/parameters.md) for complete parameter documentation.

## Project Structure

```
reddit/
├── src/
│   ├── __init__.py
│   ├── constants.py              # Font list, validation constants
│   ├── bot/
│   │   ├── __init__.py
│   │   ├── reddit_bot.py         # Main bot orchestrator
│   │   ├── parameter_parser.py   # Parse user parameters
│   │   ├── url_builder.py        # Build product URLs
│   │   └── comment_traversal.py  # Navigate comment trees
│   └── config/
│       ├── __init__.py
│       └── loader.py             # Config loading & validation
├── tests/                        # 61 tests with 87% coverage
├── packaging/                    # RPM spec, Dockerfile, build script
├── bot.py                        # Main entry point
├── Pipfile                       # Python dependencies
├── config.yaml.example           # Example configuration
└── README.md                     # This file
```

## Production Deployment

**Prerequisites**: Complete local testing with real Reddit API first.

### Quick Deploy to EC2

```bash
# 1. Launch EC2 instance (t3.micro, Amazon Linux 2023)
# 2. SSH to instance
ssh -i your-key.pem ec2-user@your-instance-ip

# 3. Install bot
sudo dnf update -y
sudo dnf install -y python3-pip git
pip3 install --user pipenv

mkdir -p /opt/reddit-bot && cd /opt/reddit-bot
git clone [your-repo]
cd YourCommentOnAShirt/SocialBots/reddit
pipenv install --deploy

# 4. Configure
nano .env  # Add credentials
chmod 600 .env
nano config.yaml  # Set log_level: "INFO"

# 5. Create systemd service (see production-deployment.md)
sudo nano /etc/systemd/system/reddit-bot.service
sudo systemctl daemon-reload
sudo systemctl enable reddit-bot
sudo systemctl start reddit-bot

# 6. Verify
sudo systemctl status reddit-bot
sudo journalctl -u reddit-bot -f
```

**Full Guide**: See [Production Deployment Guide](docs/production-deployment.md) for complete instructions, security best practices, monitoring, and operational procedures.

## Documentation

### Getting Started
- **[Quick Start (15 min)](docs/quickstart-real-api.md)** ⭐ - Get bot running with real Reddit API
- **[Real API Testing Guide](docs/real-api-testing.md)** - Comprehensive testing documentation

### Deployment & Operations
- **[Production Deployment Guide](docs/production-deployment.md)** - Deploy to AWS EC2 with systemd
- **[Deployment Options](docs/deployment.md)** - RPM packaging and other deployment methods
- **[Troubleshooting](docs/troubleshooting.md)** - Common issues and solutions

### Reference
- **[Parameter Reference](docs/parameters.md)** - Complete parameter documentation
- **[Development Guide](docs/development.md)** - Local setup, testing, and contributing
- **[Design Document](DESIGN_DOC.md)** - Technical specifications and architecture

## Testing

Run all tests:
```bash
pipenv run pytest
```

Run with coverage report:
```bash
pipenv run pytest --cov=src --cov-report=html
```

Run specific test categories:
```bash
pipenv run pytest -m unit        # Unit tests only
pipenv run pytest -m integration # Integration tests only
```

## Architecture

The bot follows a modular architecture:

1. **Configuration Layer**: Loads and validates config from `.env` and `config.yaml`
2. **Parameter Parser**: Extracts and validates user parameters from mention text
3. **Comment Traversal**: Navigates Reddit comment trees to find target comment
4. **URL Builder**: Constructs product URLs with proper encoding
5. **Reddit Bot**: Orchestrates all components and manages the event loop

## Contributing

This is a production bot for YourCommentOnAShirt.com. For internal development:

1. Create a feature branch
2. Write tests first (TDD approach)
3. Implement the feature
4. Ensure tests pass and coverage ≥ 90%
5. Submit for review

## License

MIT License - See LICENSE file for details

## Support

For issues or questions:
- Check [docs/troubleshooting.md](docs/troubleshooting.md)
- Review logs: `sudo journalctl -u reddit-bot -f`
- Contact: support@yourcommentonashirt.com

---

Built with ❤️ for YourCommentOnAShirt.com
