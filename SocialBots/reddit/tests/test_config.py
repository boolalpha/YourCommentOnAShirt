"""Tests for configuration management.

Following TDD: Write tests BEFORE implementation.
"""

import pytest
import os
import tempfile
import yaml
from typing import Dict, Any
from unittest.mock import patch, mock_open


class TestConfigLoader:
    """Tests for configuration loading and validation."""
    
    @pytest.mark.unit
    def test_load_valid_config(self, sample_config, sample_env, tmp_path, monkeypatch):
        """Test loading a valid configuration from files."""
        # Arrange - Set environment variables
        for key, value in sample_env.items():
            monkeypatch.setenv(key, value)
        
        # Create temporary config file
        config_file = tmp_path / "config.yaml"
        with open(config_file, 'w') as f:
            yaml.dump(sample_config, f)
        
        # Act
        from src.config.loader import Config
        config = Config.load(config_file)
        
        # Assert
        assert config.reddit_client_id == 'test_client_id'
        assert config.reddit_client_secret == 'test_client_secret'
        assert config.reddit_username == 'TestBot'
        assert config.base_url == 'https://example.com/shop/1'
        assert config.max_comment_length == 280
    
    @pytest.mark.unit
    def test_missing_required_env_vars(self, sample_config, tmp_path):
        """Test that missing required env vars raise ConfigError."""
        # Arrange
        config_file = tmp_path / "config.yaml"
        with open(config_file, 'w') as f:
            yaml.dump(sample_config, f)
        
        # Mock os.getenv to simulate missing REDDIT_CLIENT_ID
        import os
        original_getenv = os.getenv
        def mock_getenv(key, default=None):
            if key == 'REDDIT_CLIENT_ID':
                return None
            elif key == 'REDDIT_CLIENT_SECRET':
                return 'test_secret'
            else:
                return original_getenv(key, default)
        
        # Act & Assert
        from unittest.mock import patch
        from src.config.loader import Config, ConfigError
        
        with patch('os.getenv', side_effect=mock_getenv):
            with pytest.raises(ConfigError, match="REDDIT_CLIENT_ID"):
                Config.load(config_file)
    
    @pytest.mark.unit
    def test_invalid_config_type(self, sample_env, tmp_path, monkeypatch):
        """Test that invalid config types raise ConfigError."""
        # Arrange
        for key, value in sample_env.items():
            monkeypatch.setenv(key, value)
        
        config_file = tmp_path / "config.yaml"
        invalid_config = {
            'reddit': {'user_agent': 'TestBot/1.0'},
            'bot': {
                'base_url': 'https://example.com',
                'max_comment_length': 'not_an_integer',  # Should be int
                'reply_template': '[Here\'s your shirt!]({url})',
                'max_retries': 3,
                'retry_backoff_factor': 2,
                'requests_per_minute': 60,
                'check_interval': 10
            },
            'logging': {'log_file': 'test.log', 'log_level': 'INFO'},
            'defaults': {}
        }
        
        with open(config_file, 'w') as f:
            yaml.dump(invalid_config, f)
        
        # Act & Assert
        from src.config.loader import Config, ConfigError
        with pytest.raises(ConfigError, match="max_comment_length"):
            Config.load(config_file)
    
    @pytest.mark.unit
    def test_invalid_config_range(self, sample_config, sample_env, tmp_path, monkeypatch):
        """Test that out-of-range config values raise ConfigError."""
        # Arrange
        for key, value in sample_env.items():
            monkeypatch.setenv(key, value)
        
        config_file = tmp_path / "config.yaml"
        invalid_config = sample_config.copy()
        invalid_config['bot']['requests_per_minute'] = -5  # Invalid: negative
        
        with open(config_file, 'w') as f:
            yaml.dump(invalid_config, f)
        
        # Act & Assert
        from src.config.loader import Config, ConfigError
        with pytest.raises(ConfigError, match="requests_per_minute"):
            Config.load(config_file)
    
    @pytest.mark.unit
    def test_invalid_log_level(self, sample_config, sample_env, tmp_path, monkeypatch):
        """Test that invalid log levels raise ConfigError."""
        # Arrange
        for key, value in sample_env.items():
            monkeypatch.setenv(key, value)
        
        config_file = tmp_path / "config.yaml"
        invalid_config = sample_config.copy()
        invalid_config['logging']['log_level'] = 'INVALID'
        
        with open(config_file, 'w') as f:
            yaml.dump(invalid_config, f)
        
        # Act & Assert
        from src.config.loader import Config, ConfigError
        with pytest.raises(ConfigError, match="log_level"):
            Config.load(config_file)
    
    @pytest.mark.unit
    def test_missing_config_file(self, sample_env, tmp_path, monkeypatch):
        """Test that missing config file raises FileNotFoundError."""
        # Arrange
        for key, value in sample_env.items():
            monkeypatch.setenv(key, value)
        
        config_file = tmp_path / "nonexistent.yaml"
        
        # Act & Assert
        from src.config.loader import Config
        with pytest.raises(FileNotFoundError):
            Config.load(config_file)
    
    @pytest.mark.unit
    def test_env_var_overrides(self, sample_config, sample_env, tmp_path, monkeypatch):
        """Test that environment variables can override config.yaml values."""
        # Arrange
        for key, value in sample_env.items():
            monkeypatch.setenv(key, value)
        
        # Add override for max_comment_length
        monkeypatch.setenv('BOT_MAX_COMMENT_LENGTH', '500')
        
        config_file = tmp_path / "config.yaml"
        with open(config_file, 'w') as f:
            yaml.dump(sample_config, f)
        
        # Act
        from src.config.loader import Config
        config = Config.load(config_file)
        
        # Assert - Should use env var override
        assert config.max_comment_length == 500
    
    @pytest.mark.unit
    def test_default_values_applied(self, sample_config, sample_env, tmp_path, monkeypatch):
        """Test that default values are properly loaded."""
        # Arrange
        for key, value in sample_env.items():
            monkeypatch.setenv(key, value)
        
        config_file = tmp_path / "config.yaml"
        with open(config_file, 'w') as f:
            yaml.dump(sample_config, f)
        
        # Act
        from src.config.loader import Config
        config = Config.load(config_file)
        
        # Assert
        assert config.default_text_color == '#000000'
        assert config.default_text_align == 'center'
        assert config.default_font_size == 120
        assert config.default_shirt_color == 'white'
    
    @pytest.mark.unit
    def test_reply_template_validation(self, sample_config, sample_env, tmp_path, monkeypatch):
        """Test that reply_template must contain {url} placeholder."""
        # Arrange
        for key, value in sample_env.items():
            monkeypatch.setenv(key, value)
        
        config_file = tmp_path / "config.yaml"
        invalid_config = sample_config.copy()
        invalid_config['bot']['reply_template'] = 'Missing placeholder'  # No {url}
        
        with open(config_file, 'w') as f:
            yaml.dump(invalid_config, f)
        
        # Act & Assert
        from src.config.loader import Config, ConfigError
        with pytest.raises(ConfigError, match="reply_template.*{url}"):
            Config.load(config_file)


class TestConfigHotReload:
    """Tests for configuration hot-reloading."""
    
    @pytest.mark.unit
    def test_reload_on_config_change(self, sample_config, tmp_path):
        """Test that config reloads when config.yaml changes."""
        # Arrange
        config_file = tmp_path / "config.yaml"
        
        with open(config_file, 'w') as f:
            yaml.dump(sample_config, f)
        
        from src.config.loader import ConfigWatcher
        watcher = ConfigWatcher(config_file)
        
        # Modify config
        modified_config = sample_config.copy()
        modified_config['bot']['max_comment_length'] = 500
        
        with open(config_file, 'w') as f:
            yaml.dump(modified_config, f)
        
        # Act
        changed = watcher.check_for_changes()
        
        # Assert
        assert changed is True
        assert watcher.get_config()['bot']['max_comment_length'] == 500
    
    @pytest.mark.unit
    def test_no_reload_on_critical_setting_change(self, sample_config, tmp_path):
        """Test that critical settings (like credentials) require restart."""
        # Arrange
        config_file = tmp_path / "config.yaml"
        original_base_url = sample_config['bot']['base_url']
        
        with open(config_file, 'w') as f:
            yaml.dump(sample_config, f)
        
        from src.config.loader import ConfigWatcher
        watcher = ConfigWatcher(config_file)
        
        # Try to modify base_url (critical setting)
        modified_config = sample_config.copy()
        modified_config['bot']['base_url'] = 'https://different.com'
        
        with open(config_file, 'w') as f:
            yaml.dump(modified_config, f)
        
        # Act
        watcher.check_for_changes()
        
        # Assert - Should log warning but not apply change
        # Critical settings should remain unchanged
        current = watcher.get_config()
        assert current['bot']['base_url'] == original_base_url

