"""Configuration loader with validation and hot-reloading support.

This module handles loading configuration from .env and config.yaml files,
with comprehensive validation and support for hot-reloading non-critical settings.
"""

import os
import yaml
import logging
from pathlib import Path
from typing import Dict, Any, Optional, Set
from dataclasses import dataclass
from dotenv import load_dotenv


logger = logging.getLogger(__name__)


class ConfigError(Exception):
    """Raised when configuration is invalid or incomplete."""
    pass


# Critical settings that require service restart to change
CRITICAL_SETTINGS: Set[str] = {
    'reddit.user_agent',
    'bot.base_url',
}

# Valid log levels
VALID_LOG_LEVELS: Set[str] = {
    'DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL'
}


@dataclass
class Config:
    """Configuration container with all bot settings."""
    
    # Reddit credentials
    reddit_client_id: str
    reddit_client_secret: str
    reddit_username: str
    reddit_password: str
    reddit_user_agent: str
    
    # Bot settings
    base_url: str
    max_comment_length: int
    reply_template: str
    max_retries: int
    retry_backoff_factor: float
    requests_per_minute: int
    check_interval: int
    
    # Image API settings (optional)
    image_api_url: Optional[str]
    image_api_key: Optional[str]
    
    # Logging settings
    log_file: str
    log_level: str
    log_rotation_max_bytes: int
    log_rotation_backup_count: int
    
    # Default values for URL parameters
    default_text_color: str
    default_text_align: str
    default_vertical_align: str
    default_font_size: int
    default_font_index: int
    default_shirt_color: str
    default_shirt_size: str
    
    @classmethod
    def load(cls, config_path: Path, env_path: Optional[Path] = None) -> 'Config':
        """Load and validate configuration from files.
        
        Args:
            config_path: Path to config.yaml file
            env_path: Path to .env file (optional, will look for .env in current dir)
        
        Returns:
            Validated Config instance
        
        Raises:
            FileNotFoundError: If config file doesn't exist
            ConfigError: If configuration is invalid
        """
        # Load environment variables
        if env_path and env_path.exists():
            load_dotenv(env_path)
        elif Path('.env').exists():
            load_dotenv()
        
        # Load YAML config
        if not config_path.exists():
            raise FileNotFoundError(f"Config file not found: {config_path}")
        
        with open(config_path, 'r') as f:
            config_data = yaml.safe_load(f)
        
        if not config_data:
            raise ConfigError("Config file is empty or invalid YAML")
        
        # Validate and extract configuration
        return cls._from_dict(config_data)
    
    @classmethod
    def _from_dict(cls, data: Dict[str, Any]) -> 'Config':
        """Create Config from dictionary with validation.
        
        Args:
            data: Configuration dictionary from YAML
        
        Returns:
            Validated Config instance
        
        Raises:
            ConfigError: If any validation fails
        """
        # Required environment variables
        reddit_client_id = os.getenv('REDDIT_CLIENT_ID')
        reddit_client_secret = os.getenv('REDDIT_CLIENT_SECRET')
        reddit_username = os.getenv('REDDIT_USERNAME')
        reddit_password = os.getenv('REDDIT_PASSWORD')
        
        if not reddit_client_id:
            raise ConfigError("Missing required environment variable: REDDIT_CLIENT_ID")
        if not reddit_client_secret:
            raise ConfigError("Missing required environment variable: REDDIT_CLIENT_SECRET")
        if not reddit_username:
            raise ConfigError("Missing required environment variable: REDDIT_USERNAME")
        if not reddit_password:
            raise ConfigError("Missing required environment variable: REDDIT_PASSWORD")
        
        # Extract sections
        reddit_config = data.get('reddit', {})
        bot_config = data.get('bot', {})
        logging_config = data.get('logging', {})
        defaults = data.get('defaults', {})
        
        # Reddit settings
        reddit_user_agent = reddit_config.get('user_agent', 'YourCommentOnAShirtBot/1.0')
        
        # Bot settings (with env var overrides)
        base_url = bot_config.get('base_url', 'https://yourcommentonashirt.com/shop/1')
        max_comment_length = cls._get_int_with_env_override(
            'BOT_MAX_COMMENT_LENGTH', 
            bot_config.get('max_comment_length', 280)
        )
        reply_template = bot_config.get('reply_template', '[Here\'s your shirt!]({url})')
        max_retries = bot_config.get('max_retries', 3)
        retry_backoff_factor = float(bot_config.get('retry_backoff_factor', 2))
        requests_per_minute = cls._get_int_with_env_override(
            'BOT_REQUESTS_PER_MINUTE',
            bot_config.get('requests_per_minute', 60)
        )
        check_interval = bot_config.get('check_interval', 10)
        
        # Image API settings (optional)
        image_api_url = bot_config.get('image_api_url')
        image_api_key = os.getenv('IMAGE_API_KEY')
        
        # Logging settings
        log_file = logging_config.get('log_file', 'logs/bot.log')
        log_level = os.getenv('LOG_LEVEL', logging_config.get('log_level', 'INFO'))
        log_rotation_max_bytes = logging_config.get('log_rotation_max_bytes', 10485760)
        log_rotation_backup_count = logging_config.get('log_rotation_backup_count', 5)
        
        # Default values
        default_text_color = defaults.get('text_color', '#000000')
        default_text_align = defaults.get('text_align', 'center')
        default_vertical_align = defaults.get('vertical_align', 'center')
        default_font_size = defaults.get('font_size', 120)
        default_font_index = defaults.get('font_index', 12)
        default_shirt_color = defaults.get('shirt_color', 'white')
        default_shirt_size = defaults.get('shirt_size', 'l')
        
        # Validation
        cls._validate_type('max_comment_length', max_comment_length, int)
        cls._validate_type('max_retries', max_retries, int)
        cls._validate_type('retry_backoff_factor', retry_backoff_factor, (int, float))
        cls._validate_type('requests_per_minute', requests_per_minute, int)
        cls._validate_type('check_interval', check_interval, int)
        cls._validate_type('log_rotation_max_bytes', log_rotation_max_bytes, int)
        cls._validate_type('log_rotation_backup_count', log_rotation_backup_count, int)
        cls._validate_type('default_font_size', default_font_size, int)
        cls._validate_type('default_font_index', default_font_index, int)
        
        # Range validation
        if max_comment_length <= 0:
            raise ConfigError("max_comment_length must be positive")
        if max_retries < 0:
            raise ConfigError("max_retries must be non-negative")
        if retry_backoff_factor <= 0:
            raise ConfigError("retry_backoff_factor must be positive")
        if requests_per_minute <= 0:
            raise ConfigError("requests_per_minute must be positive")
        if check_interval <= 0:
            raise ConfigError("check_interval must be positive")
        
        # Log level validation
        if log_level.upper() not in VALID_LOG_LEVELS:
            raise ConfigError(
                f"Invalid log_level: {log_level}. Must be one of: {', '.join(VALID_LOG_LEVELS)}"
            )
        
        # Reply template validation
        if '{url}' not in reply_template:
            raise ConfigError("reply_template must contain {url} placeholder")
        
        # String validations
        if not base_url.startswith(('http://', 'https://')):
            raise ConfigError("base_url must start with http:// or https://")
        
        return cls(
            reddit_client_id=reddit_client_id,
            reddit_client_secret=reddit_client_secret,
            reddit_username=reddit_username,
            reddit_password=reddit_password,
            reddit_user_agent=reddit_user_agent,
            base_url=base_url,
            max_comment_length=max_comment_length,
            reply_template=reply_template,
            max_retries=max_retries,
            retry_backoff_factor=retry_backoff_factor,
            requests_per_minute=requests_per_minute,
            check_interval=check_interval,
            image_api_url=image_api_url,
            image_api_key=image_api_key,
            log_file=log_file,
            log_level=log_level.upper(),
            log_rotation_max_bytes=log_rotation_max_bytes,
            log_rotation_backup_count=log_rotation_backup_count,
            default_text_color=default_text_color,
            default_text_align=default_text_align,
            default_vertical_align=default_vertical_align,
            default_font_size=default_font_size,
            default_font_index=default_font_index,
            default_shirt_color=default_shirt_color,
            default_shirt_size=default_shirt_size,
        )
    
    @staticmethod
    def _validate_type(name: str, value: Any, expected_type: type) -> None:
        """Validate that a value is of the expected type.
        
        Args:
            name: Name of the config parameter
            value: Value to validate
            expected_type: Expected type or tuple of types
        
        Raises:
            ConfigError: If type doesn't match
        """
        if not isinstance(value, expected_type):
            if isinstance(expected_type, tuple):
                type_names = ' or '.join(t.__name__ for t in expected_type)
            else:
                type_names = expected_type.__name__
            raise ConfigError(
                f"Invalid type for {name}: expected {type_names}, got {type(value).__name__}"
            )
    
    @staticmethod
    def _get_int_with_env_override(env_var: str, default: int) -> int:
        """Get integer value with optional environment variable override.
        
        Args:
            env_var: Environment variable name
            default: Default value from config
        
        Returns:
            Integer value (from env var or default)
        """
        env_value = os.getenv(env_var)
        if env_value:
            try:
                return int(env_value)
            except ValueError:
                logger.warning(f"Invalid value for {env_var}: {env_value}, using default: {default}")
        return default


class ConfigWatcher:
    """Watch configuration file for changes and support hot-reloading."""
    
    def __init__(self, config_path: Path):
        """Initialize config watcher.
        
        Args:
            config_path: Path to config.yaml file
        """
        self.config_path = config_path
        self._last_mtime: Optional[float] = None
        self._config: Optional[Dict[str, Any]] = None
        self._load_config()
    
    def _load_config(self) -> None:
        """Load configuration from file."""
        with open(self.config_path, 'r') as f:
            self._config = yaml.safe_load(f)
        self._last_mtime = os.path.getmtime(self.config_path)
    
    def check_for_changes(self) -> bool:
        """Check if config file has changed and reload if needed.
        
        Returns:
            True if config was reloaded, False otherwise
        """
        try:
            current_mtime = os.path.getmtime(self.config_path)
            
            if current_mtime != self._last_mtime:
                old_config = self._config.copy()
                self._load_config()
                
                # Check if any critical settings changed
                if self._has_critical_changes(old_config, self._config):
                    logger.warning(
                        "Critical configuration settings changed. "
                        "Restart service to apply changes."
                    )
                    # Revert to old config for critical settings
                    self._merge_critical_settings(old_config, self._config)
                else:
                    logger.info("Configuration reloaded successfully")
                
                return True
        except Exception as e:
            logger.error(f"Error checking for config changes: {e}")
        
        return False
    
    def get_config(self) -> Dict[str, Any]:
        """Get current configuration.
        
        Returns:
            Configuration dictionary
        """
        return self._config.copy()
    
    def _has_critical_changes(self, old: Dict[str, Any], new: Dict[str, Any]) -> bool:
        """Check if critical settings have changed.
        
        Args:
            old: Old configuration
            new: New configuration
        
        Returns:
            True if critical settings changed
        """
        for setting in CRITICAL_SETTINGS:
            parts = setting.split('.')
            old_val = old
            new_val = new
            
            for part in parts:
                old_val = old_val.get(part, {})
                new_val = new_val.get(part, {})
            
            if old_val != new_val:
                logger.warning(f"Critical setting changed: {setting}")
                return True
        
        return False
    
    def _merge_critical_settings(self, old: Dict[str, Any], new: Dict[str, Any]) -> None:
        """Merge critical settings from old config into new config.
        
        Args:
            old: Old configuration
            new: New configuration (modified in place)
        """
        for setting in CRITICAL_SETTINGS:
            parts = setting.split('.')
            
            # Get old value
            old_val = old
            for part in parts[:-1]:
                old_val = old_val.get(part, {})
            old_val = old_val.get(parts[-1])
            
            # Set in new config
            new_val = new
            for part in parts[:-1]:
                if part not in new_val:
                    new_val[part] = {}
                new_val = new_val[part]
            new_val[parts[-1]] = old_val

