"""
Comment tree traversal for extracting target comment based on origin parameter.
"""

import logging
from typing import Optional

logger = logging.getLogger(__name__)


class CommentTraversal:
    """
    Handles traversing the Reddit comment tree to find the target comment.
    
    Supports:
    - Walking up the comment tree by N levels (origin parameter)
    - Extracting comment text with optional author attribution
    - Truncation of long comments
    - Graceful handling of deleted comments and API errors
    """
    
    def get_target_comment(self, mention, origin: int = 1):
        """
        Get the target comment based on origin level.
        
        Args:
            mention: The mention comment (starting point)
            origin: How many levels up to traverse (0=self, 1=parent, etc.)
        
        Returns:
            The target comment object
        """
        # Handle negative origin (treat as 0)
        if origin < 0:
            logger.warning(f"Negative origin value: {origin}, using 0")
            origin = 0
        
        # Origin 0 means the mention itself
        if origin == 0:
            return mention
        
        # Walk up the tree
        current = mention
        for i in range(origin):
            try:
                parent = current.parent()
                
                # Check if we've reached a Submission (top level)
                # Submissions have _extract_submission_id method but not 'body' attribute
                # Check for body attribute which Comments have but Submissions don't
                if not hasattr(parent, 'body'):
                    logger.info(
                        f"Reached submission after {i+1} levels, "
                        f"using last valid comment"
                    )
                    return current
                
                # Check if parent is deleted
                if hasattr(parent, 'author') and parent.author is None:
                    logger.warning(
                        f"Parent comment at level {i+1} is deleted, "
                        f"using current comment"
                    )
                    return current
                
                current = parent
                
            except Exception as e:
                logger.error(
                    f"Error traversing to parent at level {i+1}: {e}, "
                    f"using current comment"
                )
                return current
        
        return current
    
    def get_comment_text(
        self,
        comment,
        add_author: bool = False,
        custom_author: Optional[str] = None,
        max_length: Optional[int] = None
    ) -> str:
        """
        Extract text from comment with optional author attribution and truncation.
        
        Args:
            comment: Reddit comment object
            add_author: Whether to add "- @username" attribution
            custom_author: Custom author text (overrides add_author)
            max_length: Maximum length for comment text (None = no limit)
        
        Returns:
            Processed comment text
        """
        text = comment.body
        
        # Add author attribution if requested
        if custom_author:
            author_line = f"\n- {custom_author}"
            text = text + author_line
        elif add_author:
            try:
                if comment.author and comment.author.name:
                    author_line = f"\n- @{comment.author.name}"
                    text = text + author_line
                else:
                    logger.warning("Comment author is None (deleted?), skipping author attribution")
            except Exception as e:
                logger.error(f"Error getting comment author: {e}")
        
        # Truncate if needed
        if max_length and len(text) > max_length:
            text = text[:max_length]
            logger.info(f"Truncated comment text to {max_length} characters")
        
        return text

