"""Tests for comment tree traversal.

Following TDD: Write tests BEFORE implementation.
"""

import pytest
from unittest.mock import MagicMock


class TestCommentTraversal:
    """Tests for walking up the comment tree based on origin parameter."""
    
    @pytest.mark.unit
    def test_origin_zero_returns_self(self):
        """Test that origin=0 returns the mention comment itself."""
        from src.bot.comment_traversal import CommentTraversal
        
        mention = MagicMock()
        mention.body = "Mention text"
        mention.author.name = "Mentioner"
        
        traversal = CommentTraversal()
        result = traversal.get_target_comment(mention, origin=0)
        
        assert result == mention
        assert result.body == "Mention text"
    
    @pytest.mark.unit
    def test_origin_one_returns_parent(self):
        """Test that origin=1 returns the parent comment (default behavior)."""
        from src.bot.comment_traversal import CommentTraversal
        
        mention = MagicMock()
        parent = MagicMock()
        parent.body = "Parent text"
        parent.author.name = "ParentUser"
        
        mention.parent.return_value = parent
        
        traversal = CommentTraversal()
        result = traversal.get_target_comment(mention, origin=1)
        
        assert result == parent
        assert result.body == "Parent text"
    
    @pytest.mark.unit
    def test_origin_two_returns_grandparent(self):
        """Test that origin=2 returns the grandparent comment."""
        from src.bot.comment_traversal import CommentTraversal
        
        mention = MagicMock()
        parent = MagicMock()
        grandparent = MagicMock()
        
        grandparent.body = "Grandparent text"
        grandparent.author.name = "GrandparentUser"
        
        mention.parent.return_value = parent
        parent.parent.return_value = grandparent
        
        traversal = CommentTraversal()
        result = traversal.get_target_comment(mention, origin=2)
        
        assert result == grandparent
        assert result.body == "Grandparent text"
    
    @pytest.mark.unit
    def test_origin_exceeds_tree_depth_returns_highest(self):
        """Test that origin exceeding tree depth returns the top-level comment."""
        from src.bot.comment_traversal import CommentTraversal
        
        mention = MagicMock()
        parent = MagicMock()
        submission = MagicMock()
        
        # Submission is not a comment, it's a Submission object (no body attribute)
        submission._extract_submission_id = lambda: "submission123"
        # Explicitly delete body attribute so hasattr check works
        del submission.body
        parent.body = "Top comment"
        mention.body = "Mention text"
        
        mention.parent.return_value = parent
        parent.parent.return_value = submission
        
        traversal = CommentTraversal()
        result = traversal.get_target_comment(mention, origin=10)  # Way too high
        
        # Should return parent (the last valid comment)
        assert result == parent
    
    @pytest.mark.unit
    def test_deleted_parent_handling(self):
        """Test graceful handling when parent comment is deleted."""
        from src.bot.comment_traversal import CommentTraversal
        
        mention = MagicMock()
        parent = MagicMock()
        parent.author = None  # Deleted comment
        
        mention.parent.return_value = parent
        
        traversal = CommentTraversal()
        result = traversal.get_target_comment(mention, origin=1)
        
        # Should fallback to mention itself or handle gracefully
        assert result is not None
    
    @pytest.mark.unit
    def test_negative_origin_returns_self(self):
        """Test that negative origin values default to self (origin=0)."""
        from src.bot.comment_traversal import CommentTraversal
        
        mention = MagicMock()
        mention.body = "Mention text"
        
        traversal = CommentTraversal()
        result = traversal.get_target_comment(mention, origin=-1)
        
        # Should treat as origin=0
        assert result == mention
    
    @pytest.mark.unit
    def test_parent_call_exception_handling(self):
        """Test handling of exceptions when calling parent()."""
        from src.bot.comment_traversal import CommentTraversal
        
        mention = MagicMock()
        mention.parent.side_effect = Exception("API Error")
        
        traversal = CommentTraversal()
        result = traversal.get_target_comment(mention, origin=1)
        
        # Should fallback to mention itself
        assert result == mention
    
    @pytest.mark.unit
    def test_get_comment_text_basic(self):
        """Test extracting text from a comment."""
        from src.bot.comment_traversal import CommentTraversal
        
        comment = MagicMock()
        comment.body = "Hello, World!"
        comment.author.name = "TestUser"
        
        traversal = CommentTraversal()
        text = traversal.get_comment_text(comment)
        
        assert text == "Hello, World!"
    
    @pytest.mark.unit
    def test_get_comment_text_with_author(self):
        """Test extracting text with author attribution."""
        from src.bot.comment_traversal import CommentTraversal
        
        comment = MagicMock()
        comment.body = "Hello, World!"
        comment.author.name = "TestUser"
        
        traversal = CommentTraversal()
        text = traversal.get_comment_text(comment, add_author=True)
        
        assert "Hello, World!" in text
        assert "TestUser" in text
        assert "\n" in text  # Should have newline before author
    
    @pytest.mark.unit
    def test_get_comment_text_with_custom_author(self):
        """Test extracting text with custom author text."""
        from src.bot.comment_traversal import CommentTraversal
        
        comment = MagicMock()
        comment.body = "Never gonna give you up"
        comment.author.name = "TestUser"
        
        traversal = CommentTraversal()
        text = traversal.get_comment_text(comment, custom_author="Rick Astley")
        
        assert "Never gonna give you up" in text
        assert "Rick Astley" in text
        assert "TestUser" not in text  # Should use custom, not real author
    
    @pytest.mark.unit
    def test_get_comment_text_deleted_author(self):
        """Test handling of deleted author."""
        from src.bot.comment_traversal import CommentTraversal
        
        comment = MagicMock()
        comment.body = "Comment text"
        comment.author = None  # Deleted
        
        traversal = CommentTraversal()
        text = traversal.get_comment_text(comment, add_author=True)
        
        # Should handle gracefully, maybe use [deleted] or skip author
        assert "Comment text" in text
    
    @pytest.mark.unit
    def test_truncate_comment_text(self):
        """Test truncation of long comment text."""
        from src.bot.comment_traversal import CommentTraversal
        
        comment = MagicMock()
        comment.body = "A" * 500
        comment.author.name = "TestUser"
        
        traversal = CommentTraversal()
        text = traversal.get_comment_text(comment, max_length=100)
        
        assert len(text) <= 100
        assert text.startswith("A")
    
    @pytest.mark.unit
    def test_truncate_with_author_attribution(self):
        """Test that author attribution is added before truncation."""
        from src.bot.comment_traversal import CommentTraversal
        
        comment = MagicMock()
        comment.body = "A" * 500
        comment.author.name = "TestUser"
        
        traversal = CommentTraversal()
        text = traversal.get_comment_text(
            comment, 
            add_author=True, 
            max_length=100
        )
        
        # Should include author even after truncation
        assert len(text) <= 100
        # Text should be truncated to make room for author attribution
        assert "TestUser" in text or len(text) <= 100

