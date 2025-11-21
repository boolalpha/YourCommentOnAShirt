"""Integration tests for the complete bot workflow.

Tests the full end-to-end flow with mocked Reddit API.
"""

import pytest
from unittest.mock import MagicMock, patch, call


class TestBotIntegration:
    """Integration tests for complete bot workflow."""
    
    @pytest.mark.integration
    def test_bot_authentication(self, sample_config, sample_env, tmp_path, monkeypatch):
        """Test that bot can authenticate with Reddit."""
        from src.config.loader import Config
        from src.bot.reddit_bot import RedditBot
        
        # Set environment variables
        for key, value in sample_env.items():
            monkeypatch.setenv(key, value)
        
        # Create config file
        config_file = tmp_path / "config.yaml"
        import yaml
        with open(config_file, 'w') as f:
            yaml.dump(sample_config, f)
        
        config = Config.load(config_file)
        bot = RedditBot(config)
        
        # Mock PRAW
        with patch('src.bot.reddit_bot.praw.Reddit') as mock_reddit_class:
            mock_reddit = MagicMock()
            mock_user = MagicMock()
            mock_user.name = 'TestBot'
            mock_reddit.user.me.return_value = mock_user
            mock_reddit_class.return_value = mock_reddit
            
            bot.authenticate()
            
            # Verify authentication was called with correct credentials
            mock_reddit_class.assert_called_once()
            call_kwargs = mock_reddit_class.call_args[1]
            assert call_kwargs['client_id'] == 'test_client_id'
            assert call_kwargs['client_secret'] == 'test_client_secret'
            assert call_kwargs['username'] == 'TestBot'
    
    @pytest.mark.integration
    def test_process_simple_mention(self, sample_config, sample_env, tmp_path, monkeypatch):
        """Test processing a simple mention with default parameters."""
        from src.config.loader import Config
        from src.bot.reddit_bot import RedditBot
        
        # Setup
        for key, value in sample_env.items():
            monkeypatch.setenv(key, value)
        
        config_file = tmp_path / "config.yaml"
        import yaml
        with open(config_file, 'w') as f:
            yaml.dump(sample_config, f)
        
        config = Config.load(config_file)
        bot = RedditBot(config)
        
        # Mock mention and parent
        mention = MagicMock()
        mention.id = 'mention123'
        mention.body = '/u/TestBot'
        mention.author.name = 'Requester'
        mention.subreddit.display_name = 'test'
        
        parent = MagicMock()
        parent.id = 'parent123'
        parent.body = 'This is a great comment!'
        parent.author.name = 'OriginalPoster'
        
        mention.parent.return_value = parent
        
        # Process mention
        success = bot.process_mention(mention)
        
        assert success is True
        assert mention.id in bot.processed_mentions
        
        # Verify reply was posted
        mention.reply.assert_called_once()
        reply_text = mention.reply.call_args[0][0]
        assert 'Here\'s your shirt!' in reply_text or "Here's your shirt!" in reply_text
        assert 'https://example.com/shop/1' in reply_text
        
        # Verify mention was marked as read
        mention.mark_read.assert_called_once()
    
    @pytest.mark.integration
    def test_process_mention_with_parameters(self, sample_config, sample_env, tmp_path, monkeypatch):
        """Test processing a mention with custom parameters."""
        from src.config.loader import Config
        from src.bot.reddit_bot import RedditBot
        
        # Setup
        for key, value in sample_env.items():
            monkeypatch.setenv(key, value)
        
        config_file = tmp_path / "config.yaml"
        import yaml
        with open(config_file, 'w') as f:
            yaml.dump(sample_config, f)
        
        config = Config.load(config_file)
        bot = RedditBot(config)
        
        # Mock mention with parameters
        mention = MagicMock()
        mention.id = 'mention456'
        mention.body = '/u/TestBot shirtColor: black, size: xl, textColor: #ff0000'
        mention.author.name = 'Requester'
        mention.subreddit.display_name = 'test'
        
        parent = MagicMock()
        parent.id = 'parent456'
        parent.body = 'Amazing comment'
        parent.author.name = 'OriginalPoster'
        
        mention.parent.return_value = parent
        
        # Process mention
        success = bot.process_mention(mention)
        
        assert success is True
        
        # Verify reply contains parameters in URL
        reply_text = mention.reply.call_args[0][0]
        assert 'attribute_pa_color=black' in reply_text
        assert 'attribute_pa_size=xl' in reply_text
        assert '%23ff0000' in reply_text or '%23FF0000' in reply_text  # Encoded color
    
    @pytest.mark.integration
    def test_process_mention_with_parent(self, sample_config, sample_env, tmp_path, monkeypatch):
        """Test processing a mention with parent=2 (grandparent comment)."""
        from src.config.loader import Config
        from src.bot.reddit_bot import RedditBot
        
        # Setup
        for key, value in sample_env.items():
            monkeypatch.setenv(key, value)
        
        config_file = tmp_path / "config.yaml"
        import yaml
        with open(config_file, 'w') as f:
            yaml.dump(sample_config, f)
        
        config = Config.load(config_file)
        bot = RedditBot(config)
        
        # Mock mention with parent parameter
        mention = MagicMock()
        mention.id = 'mention789'
        mention.body = '/u/TestBot parent: 2'
        mention.author.name = 'Requester'
        mention.subreddit.display_name = 'test'
        
        parent = MagicMock()
        parent.id = 'parent789'
        parent.body = 'Parent comment'
        parent.author.name = 'ParentUser'
        
        grandparent = MagicMock()
        grandparent.id = 'grandparent789'
        grandparent.body = 'This is the grandparent!'
        grandparent.author.name = 'GrandparentUser'
        
        mention.parent.return_value = parent
        parent.parent.return_value = grandparent
        
        # Process mention
        success = bot.process_mention(mention)
        
        assert success is True
        
        # Verify reply contains grandparent comment text
        reply_text = mention.reply.call_args[0][0]
        assert 'grandparent' in reply_text.lower()
    
    @pytest.mark.integration
    def test_process_mention_with_author_attribution(self, sample_config, sample_env, tmp_path, monkeypatch):
        """Test processing a mention with addAuthor parameter."""
        from src.config.loader import Config
        from src.bot.reddit_bot import RedditBot
        
        # Setup
        for key, value in sample_env.items():
            monkeypatch.setenv(key, value)
        
        config_file = tmp_path / "config.yaml"
        import yaml
        with open(config_file, 'w') as f:
            yaml.dump(sample_config, f)
        
        config = Config.load(config_file)
        bot = RedditBot(config)
        
        # Mock mention
        mention = MagicMock()
        mention.id = 'mention999'
        mention.body = '/u/TestBot addAuthor: true'
        mention.author.name = 'Requester'
        mention.subreddit.display_name = 'test'
        
        parent = MagicMock()
        parent.id = 'parent999'
        parent.body = 'Quote this'
        parent.author.name = 'QuoteAuthor'
        
        mention.parent.return_value = parent
        
        # Process mention
        success = bot.process_mention(mention)
        
        assert success is True
        
        # Verify reply contains author attribution
        reply_text = mention.reply.call_args[0][0]
        assert 'QuoteAuthor' in reply_text or '@QuoteAuthor' in reply_text
    
    @pytest.mark.integration
    def test_skip_already_processed_mention(self, sample_config, sample_env, tmp_path, monkeypatch):
        """Test that already processed mentions are skipped."""
        from src.config.loader import Config
        from src.bot.reddit_bot import RedditBot
        
        # Setup
        for key, value in sample_env.items():
            monkeypatch.setenv(key, value)
        
        config_file = tmp_path / "config.yaml"
        import yaml
        with open(config_file, 'w') as f:
            yaml.dump(sample_config, f)
        
        config = Config.load(config_file)
        bot = RedditBot(config)
        
        # Mock mention
        mention = MagicMock()
        mention.id = 'duplicate123'
        mention.body = '/u/TestBot'
        mention.author.name = 'Requester'
        mention.subreddit.display_name = 'test'
        
        parent = MagicMock()
        parent.body = 'Comment'
        parent.author.name = 'Author'
        mention.parent.return_value = parent
        
        # Process first time
        bot.process_mention(mention)
        assert mention.reply.call_count == 1
        
        # Process again (should skip)
        result = bot.process_mention(mention)
        assert result is False
        assert mention.reply.call_count == 1  # Should not increase
    
    @pytest.mark.integration
    def test_error_handling_in_process_mention(self, sample_config, sample_env, tmp_path, monkeypatch):
        """Test that errors in processing are handled gracefully."""
        from src.config.loader import Config
        from src.bot.reddit_bot import RedditBot
        
        # Setup
        for key, value in sample_env.items():
            monkeypatch.setenv(key, value)
        
        config_file = tmp_path / "config.yaml"
        import yaml
        with open(config_file, 'w') as f:
            yaml.dump(sample_config, f)
        
        config = Config.load(config_file)
        bot = RedditBot(config)
        
        # Mock mention that will cause an error in parent traversal
        mention = MagicMock()
        mention.id = 'error123'
        mention.body = '/u/TestBot'
        mention.author.name = 'Requester'
        mention.subreddit.display_name = 'test'
        
        # Make parent() raise an exception
        mention.parent.side_effect = Exception("API Error")
        
        # Process should still succeed (falls back to mention text itself)
        success = bot.process_mention(mention)
        
        # Bot gracefully handles the error and uses the mention itself
        assert success is True
        assert mention.id in bot.processed_mentions
        
        # Reply should still be posted (using mention text as fallback)
        mention.reply.assert_called_once()

