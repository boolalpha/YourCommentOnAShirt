/**
 * API Key Authentication Middleware
 * 
 * Protects endpoints by requiring a valid API key in request headers.
 * The API key should be passed as: X-API-Key header
 */

const crypto = require('crypto');

/**
 * API Key validation middleware
 * Checks for valid API key in X-API-Key header
 */
function requireApiKey(req, res, next) {
    const providedKey = req.headers['x-api-key'];
    const validKey = process.env.API_KEY;

    // Check if API key is configured
    if (!validKey) {
        console.error('[Auth] ERROR: API_KEY not configured in environment');
        return res.status(500).json({
            success: false,
            error: 'server_misconfigured',
            message: 'API key not configured on server'
        });
    }

    // Check if API key was provided
    if (!providedKey) {
        console.warn('[Auth] Request blocked: No API key provided');
        console.warn(`  IP: ${req.ip}`);
        console.warn(`  Path: ${req.path}`);
        return res.status(401).json({
            success: false,
            error: 'unauthorized',
            message: 'API key required. Include X-API-Key header.'
        });
    }

    // Constant-time comparison to prevent timing attacks
    const providedBuffer = Buffer.from(providedKey);
    const validBuffer = Buffer.from(validKey);

    // Ensure both keys are same length
    if (providedBuffer.length !== validBuffer.length) {
        console.warn('[Auth] Request blocked: Invalid API key length');
        console.warn(`  IP: ${req.ip}`);
        return res.status(403).json({
            success: false,
            error: 'forbidden',
            message: 'Invalid API key'
        });
    }

    // Use crypto.timingSafeEqual for secure comparison
    const isValid = crypto.timingSafeEqual(providedBuffer, validBuffer);

    if (!isValid) {
        console.warn('[Auth] Request blocked: Invalid API key');
        console.warn(`  IP: ${req.ip}`);
        console.warn(`  Path: ${req.path}`);
        console.warn(`  Provided key (first 8 chars): ${providedKey.substring(0, 8)}...`);
        return res.status(403).json({
            success: false,
            error: 'forbidden',
            message: 'Invalid API key'
        });
    }

    // Valid API key - allow request
    console.log('[Auth] ✓ Valid API key');
    next();
}

/**
 * Optional: Less strict middleware that allows requests without API key
 * but logs them. Useful for gradual migration.
 */
function optionalApiKey(req, res, next) {
    const providedKey = req.headers['x-api-key'];
    
    if (!providedKey) {
        console.warn('[Auth] Warning: Request without API key (allowed in optional mode)');
        console.warn(`  IP: ${req.ip}`);
        console.warn(`  Path: ${req.path}`);
    }
    
    next();
}

module.exports = {
    requireApiKey,
    optionalApiKey,
};

