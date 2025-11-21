# Development Guide

Guide for local development and testing of the YourCommentOnAShirt Reddit Bot.

## Prerequisites

- Python 3.9 or higher
- pipenv (`pip install pipenv`)
- Git
- Reddit API credentials
- Docker (optional, for RPM building)

## Initial Setup

### 1. Install Dependencies

```bash
cd SocialBots/reddit
pipenv install --dev
```

This installs:
- **Runtime**: `praw`, `python-dotenv`, `pyyaml`
- **Development**: `pytest`, `pytest-mock`, `pytest-cov`, `flake8`, `black`

### 2. Configure Environment

Create `.env` file (never commit this):

```bash
cp .env.example .env
nano .env
```

Add your Reddit API credentials:

```env
REDDIT_CLIENT_ID=your_client_id
REDDIT_CLIENT_SECRET=your_client_secret
REDDIT_USERNAME=YourTestBot
REDDIT_PASSWORD=your_password
```

### 3. Configure Settings

Create `config.yaml`:

```bash
cp config.yaml.example config.yaml
nano config.yaml
```

Adjust settings as needed (defaults are usually fine for development).

## Running the Bot Locally

### Activate Virtual Environment

```bash
pipenv shell
```

Or run commands with `pipenv run`:

```bash
pipenv run python bot.py
```

### Run with Custom Config

```bash
python bot.py --config /path/to/config.yaml
```

### Monitor Logs

Logs are written to `logs/bot.log` (configurable in `config.yaml`).

```bash
tail -f logs/bot.log
```

## Testing

### Run All Tests

```bash
pipenv run pytest
```

### Run with Coverage

```bash
pipenv run pytest --cov=src --cov-report=html --cov-report=term
```

View HTML coverage report:

```bash
open htmlcov/index.html
```

### Run Specific Test Categories

```bash
# Unit tests only
pipenv run pytest -m unit

# Integration tests only
pipenv run pytest -m integration

# Specific test file
pipenv run pytest tests/test_parameter_parser.py

# Specific test
pipenv run pytest tests/test_parameter_parser.py::TestParameterParser::test_parse_simple_parameters
```

### Run Tests in Verbose Mode

```bash
pipenv run pytest -v
```

### Run Tests with Live Logging

```bash
pipenv run pytest --log-cli-level=DEBUG
```

## Code Quality

### Linting with Flake8

```bash
pipenv run flake8 src/ tests/ bot.py
```

### Code Formatting with Black

```bash
# Check formatting
pipenv run black --check src/ tests/ bot.py

# Auto-format
pipenv run black src/ tests/ bot.py
```

## Project Structure

```
reddit/
├── src/                      # Source code
│   ├── bot/                  # Bot components
│   │   ├── reddit_bot.py     # Main bot orchestrator
│   │   ├── parameter_parser.py
│   │   ├── url_builder.py
│   │   └── comment_traversal.py
│   ├── config/               # Configuration management
│   │   └── loader.py
│   ├── utils/                # Utilities (if needed)
│   └── constants.py          # Constants (fonts, validation)
├── tests/                    # Test suite
│   ├── conftest.py           # Shared fixtures
│   ├── test_config.py
│   ├── test_parameter_parser.py
│   ├── test_url_builder.py
│   ├── test_comment_traversal.py
│   └── test_bot_integration.py
├── packaging/                # RPM packaging
├── docs/                     # Documentation
├── bot.py                    # Entry point
├── Pipfile                   # Dependencies
├── pytest.ini                # Pytest configuration
└── config.yaml.example       # Example config
```

## Development Workflow

### 1. Create a Feature Branch

```bash
git checkout -b feature/my-new-feature
```

### 2. Write Tests First (TDD)

Following Test-Driven Development:

```python
# tests/test_my_feature.py
import pytest

def test_my_feature():
    from src.my_module import my_function
    
    result = my_function(input_data)
    
    assert result == expected_output
```

### 3. Run Tests (They Should Fail)

```bash
pipenv run pytest tests/test_my_feature.py
```

### 4. Implement the Feature

```python
# src/my_module.py
def my_function(input_data):
    # Implementation
    return output
```

### 5. Run Tests Again (They Should Pass)

```bash
pipenv run pytest tests/test_my_feature.py
```

### 6. Check Coverage

```bash
pipenv run pytest --cov=src
```

Ensure coverage ≥ 90%.

### 7. Lint and Format

```bash
pipenv run flake8 src/ tests/
pipenv run black src/ tests/
```

### 8. Commit and Push

```bash
git add .
git commit -m "Add my new feature"
git push origin feature/my-new-feature
```

## Debugging

### Enable Debug Logging

In `config.yaml`:

```yaml
logging:
  log_level: "DEBUG"
```

### Use Python Debugger

```python
import pdb; pdb.set_trace()
```

### Mock Reddit API for Testing

Use pytest fixtures from `conftest.py`:

```python
def test_something(mock_reddit, mock_mention):
    # Your test code
    pass
```

## Common Development Tasks

### Add a New Parameter

1. Add to `PARAMETER_ALIASES` in `src/constants.py`
2. Add validation logic in `src/bot/parameter_parser.py`
3. Write tests in `tests/test_parameter_parser.py`
4. Update documentation in `docs/parameters.md`

### Add a New Font

1. Add font name to `AVAILABLE_FONTS` in `src/constants.py`
2. Update `MAX_FONT_INDEX` if needed
3. Update font documentation

### Modify Default Values

Edit `config.yaml`:

```yaml
defaults:
  text_color: "#ff0000"  # Red text
  shirt_color: "black"   # Black shirts
```

## Troubleshooting Development Issues

### Import Errors

Make sure you're in the virtual environment:

```bash
pipenv shell
```

Or use `pipenv run`:

```bash
pipenv run python bot.py
```

### Test Failures

Run with verbose output:

```bash
pipenv run pytest -vv --tb=short
```

### Coverage Below 90%

Identify uncovered lines:

```bash
pipenv run pytest --cov=src --cov-report=term-missing
```

Add tests for missing lines.

### Pipenv Issues

Reset the environment:

```bash
pipenv --rm
pipenv install --dev
```

## Best Practices

1. **Always write tests first** (TDD)
2. **Keep functions small** (< 50 lines)
3. **Use type hints** for function signatures
4. **Write docstrings** for all public functions
5. **Log appropriately**:
   - DEBUG: Detailed diagnostic info
   - INFO: General operational events
   - WARNING: Unexpected but handled
   - ERROR: Errors that need attention
6. **Handle errors gracefully** - never crash
7. **Validate all inputs** - never trust user data

## Getting Help

- Check existing tests for examples
- Review design document: `DESIGN_DOC.md`
- Check troubleshooting guide: `docs/troubleshooting.md`
- Ask the team!

