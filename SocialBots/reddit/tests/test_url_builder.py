"""Tests for URL builder.

Following TDD: Write tests BEFORE implementation.
"""

import pytest
from typing import Dict, Any


class TestURLBuilder:
    """Tests for building product URLs with parameters."""
    
    @pytest.mark.unit
    def test_build_url_with_comment_only(self):
        """Test building URL with just comment text."""
        from src.bot.url_builder import URLBuilder
        
        builder = URLBuilder(base_url="https://example.com/shop/1")
        url = builder.build(comment_text="Hello, World!")
        
        assert url.startswith("https://example.com/shop/1?")
        assert "comment=Hello%2C+World%21" in url or "comment=Hello%2C%20World%21" in url
    
    @pytest.mark.unit
    def test_build_url_with_parameters(self):
        """Test building URL with additional parameters."""
        from src.bot.url_builder import URLBuilder
        
        builder = URLBuilder(base_url="https://example.com/shop/1")
        params = {
            'attribute_pa_color': 'black',
            'attribute_pa_size': 'xl',
            'fontsize': 150
        }
        
        url = builder.build(comment_text="Test", params=params)
        
        assert "comment=Test" in url
        assert "attribute_pa_color=black" in url
        assert "attribute_pa_size=xl" in url
        assert "fontsize=150" in url
    
    @pytest.mark.unit
    def test_hex_color_encoding(self):
        """Test that hex colors with # are properly URL-encoded as %23."""
        from src.bot.url_builder import URLBuilder
        
        builder = URLBuilder(base_url="https://example.com/shop/1")
        params = {'color': '#ff0000'}
        
        url = builder.build(comment_text="Test", params=params)
        
        assert "color=%23ff0000" in url or "color=%23FF0000" in url
    
    @pytest.mark.unit
    def test_preserve_newlines(self):
        """Test that newlines in comment are preserved as %0A."""
        from src.bot.url_builder import URLBuilder
        
        builder = URLBuilder(base_url="https://example.com/shop/1")
        comment = "Line 1\nLine 2"
        
        url = builder.build(comment_text=comment)
        
        assert "%0A" in url or "%0a" in url
    
    @pytest.mark.unit
    def test_special_characters_encoding(self):
        """Test proper encoding of special characters."""
        from src.bot.url_builder import URLBuilder
        
        builder = URLBuilder(base_url="https://example.com/shop/1")
        comment = "Hello! @User #hashtag $money 100%"
        
        url = builder.build(comment_text=comment)
        
        # URL should be properly encoded
        assert "Hello" in url
        assert "comment=" in url
        # Special chars should be encoded
        assert "%21" in url or "!" in url  # ! can be encoded or not
    
    @pytest.mark.unit
    def test_empty_comment_text(self):
        """Test handling of empty comment text."""
        from src.bot.url_builder import URLBuilder
        
        builder = URLBuilder(base_url="https://example.com/shop/1")
        url = builder.build(comment_text="")
        
        # Should still create valid URL
        assert url.startswith("https://example.com/shop/1")
        assert "comment=" in url
    
    @pytest.mark.unit
    def test_very_long_comment_text(self):
        """Test handling of very long comment text."""
        from src.bot.url_builder import URLBuilder
        
        builder = URLBuilder(base_url="https://example.com/shop/1")
        long_comment = "A" * 500
        
        url = builder.build(comment_text=long_comment)
        
        assert "comment=" in url
        assert len(url) > 100  # Should contain the long text
    
    @pytest.mark.unit
    def test_special_parameters_excluded(self):
        """Test that special parameters (parent, addAuthor, author) are not included in URL."""
        from src.bot.url_builder import URLBuilder
        
        builder = URLBuilder(base_url="https://example.com/shop/1")
        params = {
            'parent': 2,
            'addAuthor': True,
            'author': 'John Doe',
            'attribute_pa_size': 'xl'  # This should be included
        }
        
        url = builder.build(comment_text="Test", params=params)
        
        # Special params should NOT appear in URL
        assert "parent=" not in url
        assert "addAuthor" not in url
        assert "author=" not in url
        
        # Regular param should appear
        assert "attribute_pa_size=xl" in url
    
    @pytest.mark.unit
    def test_parameter_order_consistency(self):
        """Test that parameters are added in a consistent order."""
        from src.bot.url_builder import URLBuilder
        
        builder = URLBuilder(base_url="https://example.com/shop/1")
        params = {'a': '1', 'b': '2', 'c': '3'}
        
        url1 = builder.build(comment_text="Test", params=params)
        url2 = builder.build(comment_text="Test", params=params)
        
        # Same input should produce same URL
        assert url1 == url2
    
    @pytest.mark.unit
    def test_url_with_defaults(self):
        """Test building URL with default parameter values."""
        from src.bot.url_builder import URLBuilder
        
        defaults = {
            'color': '#000000',
            'align': 'center',
            'fontsize': 120
        }
        
        builder = URLBuilder(
            base_url="https://example.com/shop/1",
            defaults=defaults
        )
        
        # User provides only size
        params = {'attribute_pa_size': 'xl'}
        url = builder.build(comment_text="Test", params=params)
        
        # Should include user param and defaults
        assert "attribute_pa_size=xl" in url
        assert "color=%23000000" in url
        assert "align=center" in url
        assert "fontsize=120" in url
    
    @pytest.mark.unit
    def test_user_params_override_defaults(self):
        """Test that user-provided parameters override defaults."""
        from src.bot.url_builder import URLBuilder
        
        defaults = {
            'fontsize': 120,
            'align': 'center'
        }
        
        builder = URLBuilder(
            base_url="https://example.com/shop/1",
            defaults=defaults
        )
        
        # User provides custom fontsize
        params = {'fontsize': 200}
        url = builder.build(comment_text="Test", params=params)
        
        # Should use user value, not default
        assert "fontsize=200" in url
        assert "fontsize=120" not in url
    
    @pytest.mark.unit
    def test_quote_handling(self):
        """Test proper handling of quotes in comment text."""
        from src.bot.url_builder import URLBuilder
        
        builder = URLBuilder(base_url="https://example.com/shop/1")
        comment = 'He said "Hello"'
        
        url = builder.build(comment_text=comment)
        
        assert "comment=" in url
        assert "Hello" in url
    
    @pytest.mark.unit
    def test_unicode_characters(self):
        """Test handling of Unicode characters."""
        from src.bot.url_builder import URLBuilder
        
        builder = URLBuilder(base_url="https://example.com/shop/1")
        comment = "Hello 👋 World 🌍"
        
        url = builder.build(comment_text=comment)
        
        assert "comment=" in url
        # Should be encoded (exact encoding may vary)
        assert len(url) > 50
    
    @pytest.mark.unit
    def test_ampersand_handling(self):
        """Test proper handling of ampersands in comment."""
        from src.bot.url_builder import URLBuilder
        
        builder = URLBuilder(base_url="https://example.com/shop/1")
        comment = "Rock & Roll"
        
        url = builder.build(comment_text=comment)
        
        assert "comment=" in url
        # Ampersand should be encoded
        assert "%26" in url or "&" in url

