const puppeteer = require('puppeteer');
const fs = require('fs').promises;
const path = require('path');
const crypto = require('crypto');
const { generateHTML } = require('./templateRenderer');
const config = require('../config');

// Cache browser instance for performance
let browserInstance = null;
let pagePool = [];
const MAX_PAGES = 5;

// Shirt color mapping - must match WordPress plugin exactly
const shirtColorMapping = {
    'black': '#141313',
    'gold': '#ffb22d',
    'irish-green': '#00ba69',
    'orange': '#ff5f2e',
    'red': '#d80019',
    'royal': '#175ac7',
    'white': '#fffefa'
};

/**
 * Generate content hash from image parameters
 * 
 * Purpose: Creates deterministic hash for image deduplication
 * Uses MD5 algorithm (same as PHP) for consistency
 */
function generateImageHashPreview(params, prefix = 'product') {
    // Extract parameters with defaults (matching PHP defaults)
    const {
        commentText = '',
        textColor = '#000000',
        textAlign = 'center',
        textValign = 'center',
        fontSize = 120,
        fontIndex = 12,
        shirtColor = ''
    } = params;

    // Normalize shirt color to hex value for consistent hashing
    // If it's a color name (not starting with #), convert to hex
    let normalizedShirtColor = shirtColor;
    if (shirtColor && !shirtColor.startsWith('#')) {
        normalizedShirtColor = shirtColorMapping[shirtColor] || '';
    }

    // Build hash input string (pipe-separated)
    // For product images, include shirtColor; for design masks, exclude it
    const hashInput = prefix === 'product' 
        ? `${commentText}|${textColor}|${textAlign}|${textValign}|${fontSize}|${fontIndex}|${normalizedShirtColor}`
        : `${commentText}|${textColor}|${textAlign}|${textValign}|${fontSize}|${fontIndex}`;

    // Generate MD5 hash (32 characters) - same algorithm as PHP
    const hash = crypto.createHash('md5').update(hashInput).digest('hex');
    
    return hash;
}

/**
 * Get or create browser instance
 * Browser is reused across requests for performance
 */
async function getBrowser() {
    if (!browserInstance) {
        console.log('[Browser] Launching new Chromium instance...');
        browserInstance = await puppeteer.launch({
            headless: 'new',
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu',
                '--disable-software-rasterizer',
                '--disable-extensions'
            ],
            // On macOS, use system Chrome if available for better font rendering
            executablePath: process.platform === 'darwin' 
                ? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome'
                : undefined
        });
        console.log('[Browser] Chromium instance ready');
    }
    return browserInstance;
}

/**
 * Generate preview image using Puppeteer
 * This creates a pixel-perfect render using actual browser engine
 */
async function generatePreviewImage(params) {
    const startTime = Date.now();
    
    const {
        commentText,
        textColor,
        textAlign,
        textValign,
        fontSize,
        fontIndex,
        shirtColor
    } = params;

    // Ensure output directory exists
    const outputDir = config.image.outputDir;
    try {
        await fs.access(outputDir);
    } catch {
        await fs.mkdir(outputDir, { recursive: true });
        console.log(`[Image] Created output directory: ${outputDir}`);
    }

    // Generate hash-based filename for deduplication
    const hash = generateImageHashPreview(params, 'product');
    const filename = `product_${hash}.png`;
    const outputPath = path.join(outputDir, filename);
    
    // Check if file already exists BEFORE doing any expensive work
    try {
        await fs.access(outputPath);
        // File exists! Return immediately without generating
        const cacheCheckTime = Date.now() - startTime;
        console.log(`[Image] Cache hit ${filename} in ${cacheCheckTime}ms`);
        
        const imageUrl = `${config.image.urlPath}/${filename}`;
        const fullUrl = `${config.image.domain}${imageUrl}`;
        
        return {
            imageUrl: imageUrl,
            fullUrl: fullUrl,
            imagePath: outputPath,
            filename: filename,
            generationTime: cacheCheckTime,
            cached: true
        };
    } catch {
        // File doesn't exist, need to generate it
        console.log(`[Image] File not found, generating: ${filename}`);
    }

    // File doesn't exist, so launch browser and generate the image
    const browser = await getBrowser();
    const page = await browser.newPage();

    try {
        // Set viewport to 1000x1000 (matching product image size)
        await page.setViewport({ 
            width: 1000, 
            height: 1000,
            deviceScaleFactor: 1 // Important: 1:1 pixel ratio
        });

        // Generate HTML content with embedded styles
        const html = generateHTML({
            commentText,
            textColor,
            textAlign,
            textValign,
            fontSize,
            fontIndex,
            shirtColor
        });

        // Load the HTML
        await page.setContent(html, { 
            waitUntil: 'networkidle0',
            timeout: 30000
        });

        // Wait for fonts to load - critical for accurate rendering
        await page.evaluate(() => document.fonts.ready);

        // Take screenshot of the entire page
        const screenshot = await page.screenshot({
            type: 'png',
            omitBackground: false,
            fullPage: false
        });

        // Save the new file
        await fs.writeFile(outputPath, screenshot);
        
        const generationTime = Date.now() - startTime;
        console.log(`[Image] Generated ${filename} in ${generationTime}ms`);

        // Construct WordPress URL (images are served by WordPress, not Node.js)
        const imageUrl = `${config.image.urlPath}/${filename}`;
        const fullUrl = `${config.image.domain}${imageUrl}`;

        return {
            imageUrl: imageUrl,
            fullUrl: fullUrl,
            imagePath: outputPath,
            filename: filename,
            generationTime,
            cached: false
        };

    } catch (error) {
        console.error('[Image Generation] Error:', error);
        throw error;
    } finally {
        await page.close();
    }
}

/**
 * Cleanup browser instance on process termination
 */
async function cleanup() {
    console.log('[Browser] Closing browser instance...');
    if (browserInstance) {
        await browserInstance.close();
        browserInstance = null;
    }
}

// Cleanup handlers
process.on('SIGINT', async () => {
    await cleanup();
    process.exit(0);
});

process.on('SIGTERM', async () => {
    await cleanup();
    process.exit(0);
});

module.exports = { 
    generatePreviewImage,
    cleanup 
};

