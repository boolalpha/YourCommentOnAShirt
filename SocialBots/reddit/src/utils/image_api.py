"""
Image generation API client.

Provides a client for making authenticated requests to the Node.js image generation service.
"""

import logging
import requests
from typing import Dict, Any, Optional
from dataclasses import dataclass

logger = logging.getLogger(__name__)


@dataclass
class ImageGenerationResult:
    """Result from image generation API."""
    success: bool
    image_url: Optional[str] = None
    full_url: Optional[str] = None
    generation_time_ms: Optional[int] = None
    error: Optional[str] = None
    message: Optional[str] = None


class ImageAPIClient:
    """
    Client for authenticated communication with the image generation API.
    
    Usage:
        client = ImageAPIClient(api_url, api_key)
        result = client.generate_preview(
            comment_text="Hello World",
            text_color="#000000",
            font_size=120
        )
        if result.success:
            print(f"Image URL: {result.full_url}")
    """
    
    def __init__(self, api_url: str, api_key: str):
        """
        Initialize the API client.
        
        Args:
            api_url: Full URL to the image generation endpoint
                     e.g., "http://localhost:3000/api/generate-preview"
            api_key: API key for authentication
        """
        self.api_url = api_url
        self.api_key = api_key
        self.session = requests.Session()
        self.session.headers.update({
            'X-API-Key': api_key,
            'Content-Type': 'application/json'
        })
    
    def generate_preview(
        self,
        comment_text: str,
        text_color: str = '#000000',
        text_align: str = 'center',
        text_valign: str = 'center',
        font_size: int = 120,
        font_index: int = 12,
        shirt_color: str = 'white',
        timeout: int = 30
    ) -> ImageGenerationResult:
        """
        Generate a preview image with the specified parameters.
        
        Args:
            comment_text: The text to render on the shirt
            text_color: Hex color code (e.g., "#000000")
            text_align: Text alignment: "left", "center", "right"
            text_valign: Vertical alignment: "flex-start", "center", "flex-end"
            font_size: Font size in pixels (20-300)
            font_index: Font index (0-20)
            shirt_color: Shirt color: "white", "black", "gold", etc.
            timeout: Request timeout in seconds
        
        Returns:
            ImageGenerationResult with success status and image URL or error
        """
        try:
            logger.info(f"Generating preview image via API...")
            logger.debug(f"  Text: {comment_text[:50]}...")
            logger.debug(f"  Parameters: color={text_color}, size={font_size}, font={font_index}")
            
            data = {
                'comment_text': comment_text,
                'text_color': text_color,
                'text_align': text_align,
                'text_valign': text_valign,
                'font_size': font_size,
                'font_index': font_index,
                'shirt_color': shirt_color
            }
            
            response = self.session.post(
                self.api_url,
                json=data,
                timeout=timeout
            )
            
            # Parse response
            try:
                result = response.json()
            except ValueError:
                logger.error(f"Failed to parse API response as JSON: {response.text[:200]}")
                return ImageGenerationResult(
                    success=False,
                    error='invalid_response',
                    message='API returned invalid JSON'
                )
            
            # Check HTTP status
            if response.status_code == 200:
                logger.info(f"✓ Image generated successfully: {result.get('image_url')}")
                return ImageGenerationResult(
                    success=True,
                    image_url=result.get('image_url'),
                    full_url=result.get('full_url'),
                    generation_time_ms=result.get('generation_time_ms')
                )
            elif response.status_code == 401:
                logger.error("API request unauthorized - missing API key")
                return ImageGenerationResult(
                    success=False,
                    error=result.get('error', 'unauthorized'),
                    message='API key required or missing'
                )
            elif response.status_code == 403:
                logger.error("API request forbidden - invalid API key")
                return ImageGenerationResult(
                    success=False,
                    error=result.get('error', 'forbidden'),
                    message='Invalid API key'
                )
            else:
                logger.error(f"API request failed with status {response.status_code}")
                return ImageGenerationResult(
                    success=False,
                    error=result.get('error', 'unknown_error'),
                    message=result.get('message', f'HTTP {response.status_code}')
                )
        
        except requests.exceptions.Timeout:
            logger.error(f"API request timed out after {timeout}s")
            return ImageGenerationResult(
                success=False,
                error='timeout',
                message=f'Request timed out after {timeout} seconds'
            )
        
        except requests.exceptions.ConnectionError as e:
            logger.error(f"Failed to connect to API: {e}")
            return ImageGenerationResult(
                success=False,
                error='connection_error',
                message='Could not connect to image generation service'
            )
        
        except Exception as e:
            logger.error(f"Unexpected error calling image API: {e}", exc_info=True)
            return ImageGenerationResult(
                success=False,
                error='unexpected_error',
                message=str(e)
            )
    
    def health_check(self, timeout: int = 5) -> bool:
        """
        Check if the image generation API is available.
        
        Args:
            timeout: Request timeout in seconds
        
        Returns:
            True if service is healthy, False otherwise
        """
        try:
            # Health endpoint doesn't require API key
            health_url = self.api_url.replace('/api/generate-preview', '/health')
            response = requests.get(health_url, timeout=timeout)
            
            if response.status_code == 200:
                data = response.json()
                if data.get('status') == 'ok':
                    logger.info("✓ Image API health check passed")
                    return True
            
            logger.warning(f"Image API health check failed: {response.status_code}")
            return False
        
        except Exception as e:
            logger.error(f"Image API health check failed: {e}")
            return False


def create_client_from_config(config) -> Optional[ImageAPIClient]:
    """
    Create an ImageAPIClient from bot configuration.
    
    Args:
        config: Config object with image_api_url and image_api_key
    
    Returns:
        ImageAPIClient if configured, None otherwise
    """
    if not config.image_api_url or not config.image_api_key:
        logger.debug("Image API not configured - skipping client creation")
        return None
    
    logger.info(f"Initializing image API client: {config.image_api_url}")
    return ImageAPIClient(config.image_api_url, config.image_api_key)

