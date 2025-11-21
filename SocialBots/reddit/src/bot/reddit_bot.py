"""
Main Reddit bot orchestrator.

Integrates all components: PRAW authentication, parameter parsing, 
URL building, comment traversal, and reply posting.
"""

import logging
import time
import praw
from typing import Set, Optional
from pathlib import Path

from src.config.loader import Config
from src.bot.parameter_parser import ParameterParser
from src.bot.url_builder import URLBuilder
from src.bot.comment_traversal import CommentTraversal

logger = logging.getLogger(__name__)


class RedditBot:
    """
    Main bot class that monitors Reddit mentions and generates shirt links.
    
    Workflow:
    1. Authenticate with Reddit API
    2. Monitor inbox for mentions
    3. Parse parameters from mention text
    4. Traverse comment tree to find target comment
    5. Build product URL
    6. Post reply with link
    7. Mark mention as read
    """
    
    def __init__(self, config: Config):
        """
        Initialize the Reddit bot.
        
        Args:
            config: Configuration object with all settings
        """
        self.config = config
        self.reddit: Optional[praw.Reddit] = None
        self.parser = ParameterParser()
        self.url_builder = URLBuilder(
            base_url=config.base_url,
            defaults=self._get_defaults_dict(config)
        )
        self.traversal = CommentTraversal()
        
        # Track processed mentions to prevent duplicates
        self.processed_mentions: Set[str] = set()
        
        # Rate limiting
        self.last_request_time = 0
        self.min_request_interval = 60.0 / config.requests_per_minute
    
    def _get_defaults_dict(self, config: Config) -> dict:
        """
        Convert config defaults to dictionary for URL builder.
        
        Args:
            config: Configuration object
        
        Returns:
            Dictionary of default parameter values
        """
        return {
            'color': config.default_text_color,
            'align': config.default_text_align,
            'valign': config.default_vertical_align,
            'fontsize': config.default_font_size,
            'font': config.default_font_index,
            'attribute_pa_color': config.default_shirt_color,
            'attribute_pa_size': config.default_shirt_size,
        }
    
    def authenticate(self) -> None:
        """
        Authenticate with Reddit API using PRAW.
        
        Raises:
            Exception: If authentication fails
        """
        try:
            logger.info("Authenticating with Reddit API...")
            
            self.reddit = praw.Reddit(
                client_id=self.config.reddit_client_id,
                client_secret=self.config.reddit_client_secret,
                username=self.config.reddit_username,
                password=self.config.reddit_password,
                user_agent=self.config.reddit_user_agent
            )
            
            # Verify authentication by getting current user
            current_user = self.reddit.user.me()
            logger.info(f"Successfully authenticated as: {current_user.name}")
            
        except Exception as e:
            logger.error(f"Failed to authenticate with Reddit: {e}")
            raise
    
    def process_mention(self, mention) -> bool:
        """
        Process a single mention: extract params, build URL, post reply.
        
        Args:
            mention: Reddit comment object mentioning the bot
        
        Returns:
            True if successfully processed, False otherwise
        """
        try:
            mention_id = mention.id
            
            # Skip if already processed
            if mention_id in self.processed_mentions:
                logger.debug(f"Skipping already processed mention: {mention_id}")
                return False
            
            logger.info(
                f"Processing mention {mention_id} from u/{mention.author.name} "
                f"in r/{mention.subreddit.display_name}"
            )
            
            # Parse parameters from mention text
            params = self.parser.parse(mention.body)
            logger.debug(f"Parsed parameters: {params}")
            
            # Extract special parameters
            parent = params.get('parent', 1)  # Default to parent comment
            add_author = params.get('addAuthor', False)
            custom_author = params.get('author')
            
            # Get target comment based on parent level
            target_comment = self.traversal.get_target_comment(mention, origin=parent)
            logger.info(f"Target comment: {target_comment.id} by u/{target_comment.author.name if target_comment.author else '[deleted]'}")
            
            # Extract comment text with optional author attribution
            comment_text = self.traversal.get_comment_text(
                target_comment,
                add_author=add_author,
                custom_author=custom_author,
                max_length=self.config.max_comment_length
            )
            logger.debug(f"Comment text (truncated): {comment_text[:100]}...")
            
            # Build product URL
            url = self.url_builder.build(comment_text, params=params)
            logger.info(f"Generated URL: {url}")
            
            # Format reply using template
            reply_text = self.config.reply_template.format(url=url)
            
            # Post reply
            self._rate_limit()
            mention.reply(reply_text)
            logger.info(f"Posted reply to mention {mention_id}")
            
            # Mark as read and processed
            mention.mark_read()
            self.processed_mentions.add(mention_id)
            
            return True
            
        except Exception as e:
            logger.error(f"Error processing mention {mention.id}: {e}", exc_info=True)
            return False
    
    def _rate_limit(self) -> None:
        """
        Enforce rate limiting between requests.
        """
        elapsed = time.time() - self.last_request_time
        if elapsed < self.min_request_interval:
            sleep_time = self.min_request_interval - elapsed
            logger.debug(f"Rate limiting: sleeping {sleep_time:.2f}s")
            time.sleep(sleep_time)
        
        self.last_request_time = time.time()
    
    def run(self) -> None:
        """
        Main event loop: monitor mentions and process them.
        
        Runs indefinitely until interrupted.
        """
        if not self.reddit:
            raise RuntimeError("Bot not authenticated. Call authenticate() first.")
        
        logger.info("Starting mention monitoring loop...")
        
        try:
            skip_existing = self.config.skip_existing_on_startup if hasattr(self.config, 'skip_existing_on_startup') else True
            logger.info(f"Monitoring inbox (skip_existing={skip_existing})...")
            
            for mention in self.reddit.inbox.stream(skip_existing=skip_existing):
                if mention is None:
                    time.sleep(self.config.check_interval)
                    continue
                
                # Only process items with body (comments)
                if not hasattr(mention, 'body'):
                    continue
                
                # Verify it's a username mention
                bot_username = self.reddit.user.me().name.lower()
                if bot_username not in mention.body.lower():
                    continue
                
                self.process_mention(mention)
                time.sleep(self.config.check_interval)
        
        except KeyboardInterrupt:
            logger.info("Received keyboard interrupt, shutting down...")
        except Exception as e:
            error_msg = str(e)
            if "invalid_grant" in error_msg:
                logger.error("OAuth error: Reddit credentials invalid or expired")
                logger.error("Please check: 1) Password unchanged, 2) App credentials correct")
            else:
                logger.error(f"Fatal error in main loop: {e}", exc_info=True)
            raise

