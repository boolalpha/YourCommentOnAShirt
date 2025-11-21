"""Tests for parameter parser.

Following TDD: Write tests BEFORE implementation.
"""

import pytest
from typing import Dict, Any


class TestParameterParser:
    """Tests for parsing parameters from mention comment body."""
    
    @pytest.mark.unit
    def test_parse_simple_parameters(self):
        """Test parsing simple key: value pairs."""
        from src.bot.parameter_parser import ParameterParser
        
        parser = ParameterParser()
        text = "/u/YourCommentOnAShirtBot size: xl, color: #ff0000"
        
        params = parser.parse(text)
        
        assert 'attribute_pa_size' in params
        assert params['attribute_pa_size'] == 'xl'
        assert 'color' in params
        assert params['color'] == '#ff0000'
    
    @pytest.mark.unit
    def test_parse_with_alias_mapping(self):
        """Test that aliases are mapped to correct parameter names."""
        from src.bot.parameter_parser import ParameterParser
        
        parser = ParameterParser()
        text = "/u/YourCommentOnAShirtBot shirtColor: black, textColor: #00ff00, fontSize: 150"
        
        params = parser.parse(text)
        
        # Aliases should be mapped
        assert params['attribute_pa_color'] == 'black'  # shirtColor -> attribute_pa_color
        assert params['color'] == '#00ff00'  # textColor -> color
        assert params['fontsize'] == 150  # fontSize -> fontsize
    
    @pytest.mark.unit
    def test_parse_special_parameters(self):
        """Test that special parameters (parent, addAuthor, author) are separated."""
        from src.bot.parameter_parser import ParameterParser
        
        parser = ParameterParser()
        text = "/u/YourCommentOnAShirtBot parent: 2, addAuthor: true, shirtColor: white"
        
        params = parser.parse(text)
        
        # Special parameters should be in special_params
        assert params.get('parent') == 2
        assert params.get('addAuthor') is True
        assert 'attribute_pa_color' in params  # Regular params still work
    
    @pytest.mark.unit
    def test_validate_integer_range(self):
        """Test validation of integer parameters (fontsize: 20-300)."""
        from src.bot.parameter_parser import ParameterParser
        
        parser = ParameterParser()
        
        # Valid fontsize
        params = parser.parse("/u/YourCommentOnAShirtBot fontsize: 120")
        assert params['fontsize'] == 120
        
        # Too small - should use default
        params = parser.parse("/u/YourCommentOnAShirtBot fontsize: 10")
        assert params.get('fontsize') is None  # Invalid values not included
        
        # Too large - should use default
        params = parser.parse("/u/YourCommentOnAShirtBot fontsize: 500")
        assert params.get('fontsize') is None
    
    @pytest.mark.unit
    def test_validate_font_index(self):
        """Test validation of font index (0-20)."""
        from src.bot.parameter_parser import ParameterParser
        
        parser = ParameterParser()
        
        # Valid font index
        params = parser.parse("/u/YourCommentOnAShirtBot font: 12")
        assert params['font'] == 12
        
        # Invalid - out of range
        params = parser.parse("/u/YourCommentOnAShirtBot font: 30")
        assert params.get('font') is None
    
    @pytest.mark.unit
    def test_validate_choice_parameters(self):
        """Test validation of choice parameters (align, valign, shirt colors)."""
        from src.bot.parameter_parser import ParameterParser
        
        parser = ParameterParser()
        
        # Valid choices
        params = parser.parse("/u/YourCommentOnAShirtBot align: left, valign: top")
        assert params['align'] == 'left'
        assert params['valign'] == 'top'
        
        # Invalid choice
        params = parser.parse("/u/YourCommentOnAShirtBot align: diagonal")
        assert params.get('align') is None
    
    @pytest.mark.unit
    def test_validate_shirt_color(self):
        """Test validation of shirt color choices."""
        from src.bot.parameter_parser import ParameterParser
        
        parser = ParameterParser()
        
        # Valid color
        params = parser.parse("/u/YourCommentOnAShirtBot shirtColor: black")
        assert params['attribute_pa_color'] == 'black'
        
        # Invalid color
        params = parser.parse("/u/YourCommentOnAShirtBot shirtColor: purple")
        assert params.get('attribute_pa_color') is None
    
    @pytest.mark.unit
    def test_validate_shirt_size(self):
        """Test validation of shirt size choices."""
        from src.bot.parameter_parser import ParameterParser
        
        parser = ParameterParser()
        
        # Valid sizes
        params = parser.parse("/u/YourCommentOnAShirtBot size: xl")
        assert params['attribute_pa_size'] == 'xl'
        
        params = parser.parse("/u/YourCommentOnAShirtBot size: 2xl")
        assert params['attribute_pa_size'] == '2xl'
        
        # Invalid size
        params = parser.parse("/u/YourCommentOnAShirtBot size: xxl")
        assert params.get('attribute_pa_size') is None
    
    @pytest.mark.unit
    def test_hex_color_auto_prefix(self):
        """Test that hex colors without # are automatically prefixed."""
        from src.bot.parameter_parser import ParameterParser
        
        parser = ParameterParser()
        
        # Without # prefix
        params = parser.parse("/u/YourCommentOnAShirtBot textColor: ff0000")
        assert params['color'] == '#ff0000'
        
        # With # prefix (should not duplicate)
        params = parser.parse("/u/YourCommentOnAShirtBot textColor: #00ff00")
        assert params['color'] == '#00ff00'
    
    @pytest.mark.unit
    def test_parse_boolean_parameters(self):
        """Test parsing boolean parameters like addAuthor."""
        from src.bot.parameter_parser import ParameterParser
        
        parser = ParameterParser()
        
        # Various boolean representations
        params = parser.parse("/u/YourCommentOnAShirtBot addAuthor: true")
        assert params['addAuthor'] is True
        
        params = parser.parse("/u/YourCommentOnAShirtBot addAuthor: True")
        assert params['addAuthor'] is True
        
        params = parser.parse("/u/YourCommentOnAShirtBot addAuthor: false")
        assert params['addAuthor'] is False
        
        params = parser.parse("/u/YourCommentOnAShirtBot addAuthor: yes")
        assert params['addAuthor'] is True
    
    @pytest.mark.unit
    def test_parse_author_text(self):
        """Test parsing custom author text."""
        from src.bot.parameter_parser import ParameterParser
        
        parser = ParameterParser()
        
        # Custom author text
        params = parser.parse("/u/YourCommentOnAShirtBot author: Rick Astley")
        assert params['author'] == 'Rick Astley'
    
    @pytest.mark.unit
    def test_case_insensitive_keys(self):
        """Test that parameter keys are case-insensitive."""
        from src.bot.parameter_parser import ParameterParser
        
        parser = ParameterParser()
        
        # Mixed case keys
        params = parser.parse("/u/YourCommentOnAShirtBot ShirtColor: black, SIZE: xl")
        assert params['attribute_pa_color'] == 'black'
        assert params['attribute_pa_size'] == 'xl'
    
    @pytest.mark.unit
    def test_whitespace_handling(self):
        """Test robust whitespace handling around colons and commas."""
        from src.bot.parameter_parser import ParameterParser
        
        parser = ParameterParser()
        
        # Various whitespace patterns
        params = parser.parse("/u/YourCommentOnAShirtBot size:xl,color:#ff0000")
        assert params['attribute_pa_size'] == 'xl'
        
        params = parser.parse("/u/YourCommentOnAShirtBot size : xl , color : #ff0000")
        assert params['color'] == '#ff0000'
    
    @pytest.mark.unit
    def test_empty_or_no_parameters(self):
        """Test handling mention with no parameters."""
        from src.bot.parameter_parser import ParameterParser
        
        parser = ParameterParser()
        
        # Just the mention
        params = parser.parse("/u/YourCommentOnAShirtBot")
        assert params == {}
        
        # Empty string
        params = parser.parse("")
        assert params == {}
    
    @pytest.mark.unit
    def test_malformed_parameters_are_skipped(self):
        """Test that malformed parameter syntax is gracefully skipped."""
        from src.bot.parameter_parser import ParameterParser
        
        parser = ParameterParser()
        
        # Missing value
        params = parser.parse("/u/YourCommentOnAShirtBot size: xl, color:")
        assert params['attribute_pa_size'] == 'xl'
        assert 'color' not in params
        
        # Missing key
        params = parser.parse("/u/YourCommentOnAShirtBot size: xl, : black")
        assert params['attribute_pa_size'] == 'xl'
    
    @pytest.mark.unit
    def test_parent_parameter_type_conversion(self):
        """Test that parent parameter is converted to integer."""
        from src.bot.parameter_parser import ParameterParser
        
        parser = ParameterParser()
        
        params = parser.parse("/u/YourCommentOnAShirtBot parent: 2")
        assert params['parent'] == 2
        assert isinstance(params['parent'], int)
        
        # Invalid integer
        params = parser.parse("/u/YourCommentOnAShirtBot parent: not_a_number")
        assert params.get('parent') is None
    
    @pytest.mark.unit
    def test_ignore_markdown_links(self):
        """Test that markdown links (from Reddit's u/ conversion) are ignored."""
        from src.bot.parameter_parser import ParameterParser
        
        parser = ParameterParser()
        
        # Reddit converts /u/username to markdown [u/username](https://...)
        text = "Test [u/CommentOnAShirt](https://www.reddit.com/user/CommentOnAShirt/) shirtColor: gold"
        params = parser.parse(text)
        
        # Should NOT parse 'https' as a parameter
        assert 'https' not in params
        assert 'http' not in params
        
        # Should correctly parse the actual parameter
        assert params['attribute_pa_color'] == 'gold'
    
    @pytest.mark.unit
    def test_ignore_multiple_markdown_links(self):
        """Test multiple markdown links and parameters together."""
        from src.bot.parameter_parser import ParameterParser
        
        parser = ParameterParser()
        
        # Multiple links and parameters
        text = (
            "[u/Bot](https://www.reddit.com/user/Bot/) "
            "size: xl, "
            "[see this](https://example.com/page) "
            "textColor: ff0000"
        )
        params = parser.parse(text)
        
        # Should NOT parse URLs as parameters
        assert 'https' not in params
        assert 'http' not in params
        
        # Should correctly parse actual parameters
        assert params['attribute_pa_size'] == 'xl'
        assert params['color'] == '#ff0000'

