"""
Parameter parser for extracting and validating bot parameters from mention text.
"""

import re
import logging
from typing import Dict, Any, Optional

from src.constants import (
    PARAMETER_ALIASES,
    SPECIAL_PARAMETERS,
    VALID_SHIRT_COLORS,
    VALID_SHIRT_SIZES,
    VALID_TEXT_ALIGNMENTS,
    VALID_VERTICAL_ALIGNMENTS,
    MIN_FONT_SIZE,
    MAX_FONT_SIZE,
    MIN_FONT_INDEX,
    MAX_FONT_INDEX,
)

logger = logging.getLogger(__name__)


class ParameterParser:
    """
    Parses parameters from bot mention comments.
    
    Extracts key:value pairs, applies aliases, validates types and ranges,
    and separates special parameters from URL parameters.
    """
    
    # Regex pattern to extract key:value pairs
    # Matches: "key: value" with optional whitespace
    PARAM_PATTERN = re.compile(r'(\w+)\s*:\s*([^,\n]+?)(?:,|\n|$)')
    
    # Regex to match markdown links: [text](url)
    MARKDOWN_LINK_PATTERN = re.compile(r'\[([^\]]*)\]\([^\)]+\)')
    
    def parse(self, text: str) -> Dict[str, Any]:
        """
        Parse parameters from mention comment text.
        
        Args:
            text: The comment body containing bot mention and parameters
        
        Returns:
            Dictionary of validated parameters (both regular and special)
        """
        if not text:
            return {}
        
        # Strip markdown links to prevent URLs (https:, http:) from being parsed as parameters
        # Reddit converts /u/username to [u/username](https://...)
        cleaned_text = self._strip_markdown_links(text)
        
        params = {}
        matches = self.PARAM_PATTERN.findall(cleaned_text)
        
        for key, value in matches:
            key = key.strip()
            value = value.strip()
            
            if not key or not value:
                continue
            
            # Convert key to lowercase for case-insensitive matching
            key_lower = key.lower()
            
            # Check if this is a special parameter first
            if key_lower in SPECIAL_PARAMETERS:
                # For special params, preserve a normalized key name
                normalized_key = self._normalize_special_param_key(key_lower)
                parsed_value = self._parse_special_parameter(key_lower, value)
                if parsed_value is not None:
                    params[normalized_key] = parsed_value
            else:
                # Apply alias mapping for regular parameters
                mapped_key = PARAMETER_ALIASES.get(key_lower, key_lower)
                parsed_value = self._parse_and_validate(mapped_key, value, key_lower)
                if parsed_value is not None:
                    params[mapped_key] = parsed_value
        
        return params
    
    def _strip_markdown_links(self, text: str) -> str:
        """
        Remove markdown links from text to prevent URLs from being parsed as parameters.
        
        Converts [text](url) -> text
        
        Args:
            text: Text potentially containing markdown links
        
        Returns:
            Text with markdown links replaced by their link text only
        """
        # Replace [text](url) with just text
        return self.MARKDOWN_LINK_PATTERN.sub(r'\1', text)
    
    def _normalize_special_param_key(self, key_lower: str) -> str:
        """
        Normalize special parameter key names to camelCase.
        
        Args:
            key_lower: Lowercased key
        
        Returns:
            Normalized key name
        """
        if key_lower == 'addauthor':
            return 'addAuthor'
        return key_lower
    
    def _parse_and_validate(
        self, 
        param_name: str, 
        value: str, 
        original_key: str
    ) -> Optional[Any]:
        """
        Parse and validate a single parameter value.
        
        Args:
            param_name: Mapped parameter name
            value: Raw value string
            original_key: Original key before alias mapping
        
        Returns:
            Validated and type-converted value, or None if invalid
        """
        # Validate based on parameter type
        if param_name == 'fontsize':
            return self._validate_integer_range(value, MIN_FONT_SIZE, MAX_FONT_SIZE)
        
        elif param_name == 'font':
            return self._validate_integer_range(value, MIN_FONT_INDEX, MAX_FONT_INDEX)
        
        elif param_name == 'color':
            return self._validate_hex_color(value)
        
        elif param_name == 'align':
            return self._validate_choice(value, VALID_TEXT_ALIGNMENTS)
        
        elif param_name == 'valign':
            return self._validate_choice(value, VALID_VERTICAL_ALIGNMENTS)
        
        elif param_name == 'attribute_pa_color':
            return self._validate_choice(value, VALID_SHIRT_COLORS)
        
        elif param_name == 'attribute_pa_size':
            return self._validate_choice(value, VALID_SHIRT_SIZES)
        
        # Default: return as string
        return value
    
    def _parse_special_parameter(self, key: str, value: str) -> Optional[Any]:
        """
        Parse special parameters that control bot behavior.
        
        Args:
            key: Parameter key (parent, addAuthor, author)
            value: Parameter value
        
        Returns:
            Parsed value with correct type
        """
        key_lower = key.lower()
        
        if key_lower == 'parent':
            # Parse as integer
            try:
                parent = int(value)
                if parent < 0:
                    logger.warning(f"Invalid parent value: {parent} (must be non-negative)")
                    return None
                return parent
            except ValueError:
                logger.warning(f"Invalid parent value: {value} (not an integer)")
                return None
        
        elif key_lower in ('addauthor', 'addAuthor'):
            # Parse as boolean
            return self._parse_boolean(value)
        
        elif key_lower == 'author':
            # Return as string (custom author text)
            return value
        
        return None
    
    def _validate_integer_range(
        self, 
        value: str, 
        min_val: int, 
        max_val: int
    ) -> Optional[int]:
        """
        Validate integer parameter within range.
        
        Args:
            value: String value to parse
            min_val: Minimum allowed value
            max_val: Maximum allowed value
        
        Returns:
            Validated integer or None if invalid
        """
        try:
            int_val = int(value)
            if min_val <= int_val <= max_val:
                return int_val
            else:
                logger.warning(
                    f"Value {int_val} out of range [{min_val}, {max_val}]"
                )
                return None
        except ValueError:
            logger.warning(f"Invalid integer value: {value}")
            return None
    
    def _validate_hex_color(self, value: str) -> Optional[str]:
        """
        Validate and normalize hex color code.
        
        Auto-prefixes with # if missing.
        
        Args:
            value: Hex color string
        
        Returns:
            Normalized hex color with # prefix, or None if invalid
        """
        # Remove # if present
        clean_value = value.lstrip('#')
        
        # Validate hex format (3 or 6 characters)
        if re.match(r'^[0-9a-fA-F]{3}$|^[0-9a-fA-F]{6}$', clean_value):
            return f'#{clean_value}'
        
        logger.warning(f"Invalid hex color: {value}")
        return None
    
    def _validate_choice(self, value: str, valid_choices: set) -> Optional[str]:
        """
        Validate that value is in set of valid choices.
        
        Args:
            value: Value to validate
            valid_choices: Set of valid choices
        
        Returns:
            Validated value (lowercased) or None if invalid
        """
        value_lower = value.lower()
        if value_lower in valid_choices:
            return value_lower
        
        logger.warning(
            f"Invalid choice: {value}. Valid options: {', '.join(sorted(valid_choices))}"
        )
        return None
    
    def _parse_boolean(self, value: str) -> bool:
        """
        Parse boolean value from string.
        
        Args:
            value: String representation of boolean
        
        Returns:
            Boolean value
        """
        value_lower = value.lower().strip()
        return value_lower in ('true', 'yes', '1', 'on')

