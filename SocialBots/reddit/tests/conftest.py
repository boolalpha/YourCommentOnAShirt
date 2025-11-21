"""
Pytest configuration and shared fixtures for all tests.
"""
import pytest
from unittest.mock import Mock, MagicMock
from typing import Dict, Any


@pytest.fixture
def sample_config() -> Dict[str, Any]:
    """
    Fixture providing a valid test configuration.
    
    Returns:
        Dict containing all configuration values
    """
    return {
        "reddit": {
            "user_agent": "TestBot/1.0"
        },
        "bot": {
            "base_url": "https://example.com/shop/1",
            "max_comment_length": 280,
            "reply_template": "[Here's your shirt!]({url})",
            "max_retries": 3,
            "retry_backoff_factor": 2,
            "requests_per_minute": 60,
            "check_interval": 10,
            "skip_existing_on_startup": True
        },
        "logging": {
            "log_file": "logs/test.log",
            "log_level": "INFO",
            "log_rotation_max_bytes": 10485760,
            "log_rotation_backup_count": 5
        },
        "defaults": {
            "text_color": "#000000",
            "text_align": "center",
            "vertical_align": "center",
            "font_size": 120,
            "font_index": 12,
            "shirt_color": "white",
            "shirt_size": "l"
        }
    }


@pytest.fixture
def sample_env() -> Dict[str, str]:
    """
    Fixture providing sample environment variables.
    
    Returns:
        Dict containing Reddit API credentials
    """
    return {
        "REDDIT_CLIENT_ID": "test_client_id",
        "REDDIT_CLIENT_SECRET": "test_client_secret",
        "REDDIT_USERNAME": "TestBot",
        "REDDIT_PASSWORD": "test_password"
    }


@pytest.fixture
def mock_reddit():
    """
    Fixture providing a mock Reddit instance.
    
    Returns:
        Mock Reddit object with necessary attributes
    """
    reddit = MagicMock()
    reddit.user.me.return_value.name = "TestBot"
    return reddit


@pytest.fixture
def mock_comment():
    """
    Fixture providing a mock Reddit comment.
    
    Returns:
        Mock Comment object
    """
    comment = MagicMock()
    comment.id = "test123"
    comment.author.name = "TestUser"
    comment.body = "This is a test comment"
    comment.subreddit.display_name = "test"
    comment.parent_id = "t1_parent123"
    return comment


@pytest.fixture
def mock_mention():
    """
    Fixture providing a mock Reddit mention (comment mentioning the bot).
    
    Returns:
        Mock Comment object representing a bot mention
    """
    mention = MagicMock()
    mention.id = "mention123"
    mention.author.name = "MentioningUser"
    mention.body = "/u/YourCommentOnAShirtBot"
    mention.subreddit.display_name = "test"
    mention.parent_id = "t1_parent456"
    
    # Mock parent comment
    parent = MagicMock()
    parent.id = "parent456"
    parent.author.name = "OriginalCommenter"
    parent.body = "This is the parent comment text"
    parent.parent_id = "t3_post789"  # Link to submission
    
    mention.parent.return_value = parent
    return mention


@pytest.fixture
def mock_comment_tree():
    """
    Fixture providing a mock comment tree for testing origin parameter.
    
    Structure:
        Level 0 (self): "Mention comment"
        Level 1 (parent): "Parent comment"
        Level 2 (grandparent): "Grandparent comment"
        Level 3 (submission): Post title
    
    Returns:
        Mock mention comment with full parent chain
    """
    # Level 0: Mention itself
    mention = MagicMock()
    mention.id = "mention_id"
    mention.author.name = "Mentioner"
    mention.body = "/u/YourCommentOnAShirtBot origin: 2"
    
    # Level 1: Parent
    parent = MagicMock()
    parent.id = "parent_id"
    parent.author.name = "ParentAuthor"
    parent.body = "Parent comment text"
    
    # Level 2: Grandparent
    grandparent = MagicMock()
    grandparent.id = "grandparent_id"
    grandparent.author.name = "GrandparentAuthor"
    grandparent.body = "Grandparent comment text"
    
    # Level 3: Submission (top level)
    submission = MagicMock()
    submission.id = "submission_id"
    submission.title = "Post Title"
    
    # Wire up the tree
    mention.parent.return_value = parent
    parent.parent.return_value = grandparent
    grandparent.parent.return_value = submission
    
    return mention
