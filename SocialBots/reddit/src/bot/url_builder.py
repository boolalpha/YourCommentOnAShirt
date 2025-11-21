"""
URL builder for constructing product links with parameters.
"""

import logging
from typing import Dict, Any, Optional
from urllib.parse import urlencode, quote

from src.constants import SPECIAL_PARAMETERS

logger = logging.getLogger(__name__)


class URLBuilder:
    """
    Builds product URLs with comment text and optional parameters.
    
    Handles:
    - URL encoding of comment text and parameters
    - Hex color encoding (# as %23)
    - Newline preservation (%0A)
    - Default parameter values
    - Excluding special parameters from URL
    """
    
    def __init__(self, base_url: str, defaults: Optional[Dict[str, Any]] = None):
        """
        Initialize URL builder.
        
        Args:
            base_url: Base product URL
            defaults: Default parameter values (optional)
        """
        self.base_url = base_url.rstrip('/')
        self.defaults = defaults or {}
    
    def build(
        self, 
        comment_text: str, 
        params: Optional[Dict[str, Any]] = None
    ) -> str:
        """
        Build complete product URL with comment and parameters.
        
        Args:
            comment_text: The comment text to put on the shirt
            params: Optional parameters from user
        
        Returns:
            Complete URL with all parameters
        """
        params = params or {}
        
        # Start with defaults, then apply user params (user params override defaults)
        final_params = self.defaults.copy()
        
        # Filter out special parameters before merging
        filtered_params = self._filter_special_params(params)
        final_params.update(filtered_params)
        
        # Add comment text as first parameter
        url_params = {'comment': comment_text}
        
        # Add all other parameters
        url_params.update(final_params)
        
        # Build URL with properly encoded parameters
        query_string = self._encode_params(url_params)
        
        return f"{self.base_url}?{query_string}"
    
    def _filter_special_params(self, params: Dict[str, Any]) -> Dict[str, Any]:
        """
        Filter out special parameters that shouldn't go in the URL.
        
        Args:
            params: All parameters
        
        Returns:
            Parameters with special params removed
        """
        return {
            key: value
            for key, value in params.items()
            if key not in SPECIAL_PARAMETERS and key.lower() not in SPECIAL_PARAMETERS
        }
    
    def _encode_params(self, params: Dict[str, Any]) -> str:
        """
        Encode parameters for URL, with special handling for certain values.
        
        Args:
            params: Parameters to encode
        
        Returns:
            URL-encoded query string
        """
        # Sort params for consistency (comment first, then alphabetically)
        sorted_keys = ['comment'] + sorted([k for k in params.keys() if k != 'comment'])
        
        encoded_parts = []
        for key in sorted_keys:
            if key not in params:
                continue
            
            value = params[key]
            
            # Convert value to string
            value_str = str(value)
            
            # Special handling for comment text: preserve newlines, encode special chars
            if key == 'comment':
                # Use quote_plus which converts spaces to + and newlines to %0A
                encoded_value = quote(value_str, safe='')
                # Manually handle newlines to ensure they become %0A
                encoded_value = encoded_value.replace('%0A', '%0A')  # Already correct
            else:
                # For other params, use standard encoding
                encoded_value = quote(str(value), safe='')
            
            encoded_parts.append(f"{key}={encoded_value}")
        
        return '&'.join(encoded_parts)

