#!/usr/bin/env python3
"""
Main entry point for YourCommentOnAShirt Reddit Bot.

Usage:
    python bot.py [--config CONFIG_PATH]
"""

import sys
import signal
import logging
from pathlib import Path
from logging.handlers import RotatingFileHandler
import argparse

from src.config.loader import Config
from src.bot.reddit_bot import RedditBot

# Global bot instance for signal handlers
bot_instance = None


def setup_logging(config: Config) -> None:
    """
    Configure logging with rotation.
    
    Args:
        config: Configuration object with logging settings
    """
    # Create logs directory if it doesn't exist
    log_path = Path(config.log_file)
    log_path.parent.mkdir(parents=True, exist_ok=True)
    
    # Configure root logger
    logger = logging.getLogger()
    logger.setLevel(config.log_level)
    
    # Console handler
    console_handler = logging.StreamHandler()
    console_handler.setLevel(config.log_level)
    console_format = logging.Formatter(
        '%(asctime)s - %(name)s - %(levelname)s - %(message)s',
        datefmt='%Y-%m-%d %H:%M:%S'
    )
    console_handler.setFormatter(console_format)
    logger.addHandler(console_handler)
    
    # File handler with rotation
    file_handler = RotatingFileHandler(
        config.log_file,
        maxBytes=config.log_rotation_max_bytes,
        backupCount=config.log_rotation_backup_count
    )
    file_handler.setLevel(config.log_level)
    file_format = logging.Formatter(
        '%(asctime)s - %(name)s - %(levelname)s - [%(filename)s:%(lineno)d] - %(message)s',
        datefmt='%Y-%m-%d %H:%M:%S'
    )
    file_handler.setFormatter(file_format)
    logger.addHandler(file_handler)
    
    logger.info("Logging configured successfully")


def signal_handler(signum, frame):
    """
    Handle shutdown signals gracefully.
    
    Args:
        signum: Signal number
        frame: Current stack frame
    """
    logger = logging.getLogger(__name__)
    logger.info(f"Received signal {signum}, initiating graceful shutdown...")
    
    # Perform cleanup here if needed
    
    sys.exit(0)


def parse_args():
    """Parse command-line arguments."""
    parser = argparse.ArgumentParser(
        description='YourCommentOnAShirt Reddit Bot'
    )
    parser.add_argument(
        '--config',
        type=str,
        default='config.yaml',
        help='Path to config.yaml file (default: config.yaml)'
    )
    return parser.parse_args()


def main():
    """Main entry point."""
    global bot_instance
    
    # Parse arguments
    args = parse_args()
    config_path = Path(args.config)
    
    try:
        # Load configuration
        print(f"Loading configuration from {config_path}...")
        config = Config.load(config_path)
        
        # Setup logging
        setup_logging(config)
        logger = logging.getLogger(__name__)
        logger.info("=" * 60)
        logger.info("YourCommentOnAShirt Reddit Bot Starting")
        logger.info("=" * 60)
        
        # Register signal handlers for graceful shutdown
        signal.signal(signal.SIGINT, signal_handler)
        signal.signal(signal.SIGTERM, signal_handler)
        
        # Initialize bot
        logger.info("Initializing bot...")
        bot_instance = RedditBot(config)
        
        # Authenticate
        bot_instance.authenticate()
        
        # Run main loop
        logger.info("Starting main event loop...")
        bot_instance.run()
        
    except FileNotFoundError as e:
        print(f"ERROR: Configuration file not found: {e}", file=sys.stderr)
        print(f"Please create {config_path} (see config.yaml.example)", file=sys.stderr)
        sys.exit(1)
    
    except Exception as e:
        if 'logger' in locals():
            logger.critical(f"Fatal error: {e}", exc_info=True)
        else:
            print(f"FATAL ERROR: {e}", file=sys.stderr)
        sys.exit(1)


if __name__ == '__main__':
    main()

