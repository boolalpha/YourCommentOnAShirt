// Load environment variables from .env file
require('dotenv').config();

const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');
const bodyParser = require('body-parser');
const path = require('path');
const { generatePreviewImage } = require('./services/imageGenerator');
const { requireApiKey } = require('./middleware/apiKeyAuth');
const config = require('./config');

const app = express();
const PORT = config.port;

// Security middleware
app.use(helmet());

// CORS configuration
app.use(cors({
    origin: config.cors.origins,
    methods: ['GET', 'POST'],
    credentials: true
}));

// Rate limiting (configurable)
const limiter = rateLimit({
    windowMs: config.rateLimit.windowMs,
    max: config.rateLimit.max,
    message: { error: 'too_many_requests', message: 'Too many requests, please try again later.' }
});
app.use('/api/', limiter);

// Body parser middleware
app.use(bodyParser.json({ limit: '10mb' }));
app.use(bodyParser.urlencoded({ extended: true, limit: '10mb' }));

// Logging middleware
app.use((req, res, next) => {
    const timestamp = new Date().toISOString();
    console.log(`[${timestamp}] ${req.method} ${req.path}`);
    next();
});

// Health check endpoint
app.get('/health', (req, res) => {
    res.json({ 
        status: 'ok', 
        service: 'ycos-image-service',
        timestamp: new Date().toISOString(),
        uptime: process.uptime()
    });
});

// Main image generation endpoint - PROTECTED with API key
app.post('/api/generate-preview', requireApiKey, async (req, res) => {
    const startTime = Date.now();
    
    try {
        const {
            comment_text,
            text_color = '#000000',
            text_align = 'center',
            text_valign = 'center',
            font_size = 120,
            font_index = 12,
            shirt_color = 'white'
        } = req.body;

        // Validate required parameters
        if (!comment_text) {
            return res.status(400).json({
                success: false,
                error: 'missing_parameter',
                message: 'comment_text is required'
            });
        }

        // Validate font size range (20-300)
        const validFontSize = Math.max(20, Math.min(300, parseInt(font_size) || 120));
        
        // Validate font index (0-20 for 21 fonts)
        const validFontIndex = Math.max(0, Math.min(20, parseInt(font_index) || 12));

        console.log(`[Image Generation] Starting...`);
        console.log(`  Text: "${comment_text.substring(0, 50)}${comment_text.length > 50 ? '...' : ''}"`);
        console.log(`  Font: ${validFontIndex}, Size: ${validFontSize}`);
        console.log(`  Color: ${text_color}, Shirt: ${shirt_color}`);
        
        const result = await generatePreviewImage({
            commentText: comment_text,
            textColor: text_color,
            textAlign: text_align,
            textValign: text_valign,
            fontSize: validFontSize,
            fontIndex: validFontIndex,
            shirtColor: shirt_color
        });

        const totalTime = Date.now() - startTime;
        
        console.log(`[Image Generation] Complete in ${totalTime}ms (${result.cached ? 'cached' : 'new'})`);
        console.log(`  Image: ${result.imageUrl}`);
        
        res.json({
            success: true,
            image_url: result.imageUrl,
            full_url: result.fullUrl,
            generation_time_ms: result.generationTime,
            cached: result.cached
        });

    } catch (error) {
        console.error('[Image Generation] Error:', error);
        res.status(500).json({
            success: false,
            error: 'generation_failed',
            message: error.message,
            stack: process.env.NODE_ENV === 'development' ? error.stack : undefined
        });
    }
});

// Note: Images are served by WordPress, not by this Node.js service
// Files are saved to WordPress uploads directory and served at:
// ${config.image.domain}${config.image.urlPath}/

// 404 handler
app.use((req, res) => {
    res.status(404).json({
        error: 'not_found',
        message: 'Endpoint not found',
        path: req.path
    });
});

// Error handler
app.use((err, req, res, next) => {
    console.error('[Error]', err);
    res.status(500).json({
        error: 'internal_error',
        message: err.message
    });
});

// Start server
app.listen(PORT, () => {
    console.log('=====================================');
    console.log('YourCommentOnAShirt Image Service');
    console.log('=====================================');
    console.log(`Environment: ${config.nodeEnv}`);
    console.log(`Server running on port ${PORT}`);
    console.log(`Health check: http://localhost:${PORT}/health`);
    console.log(`API endpoint: http://localhost:${PORT}/api/generate-preview`);
    console.log(`Output directory: ${config.image.outputDir}`);
    console.log(`Images served at: ${config.image.domain}${config.image.urlPath}/`);
    console.log('=====================================');
});

// Graceful shutdown
process.on('SIGTERM', () => {
    console.log('SIGTERM signal received: closing HTTP server');
    process.exit(0);
});

process.on('SIGINT', () => {
    console.log('\nSIGINT signal received: closing HTTP server');
    process.exit(0);
});

