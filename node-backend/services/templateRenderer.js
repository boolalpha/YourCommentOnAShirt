const path = require('path');

// Font mapping - must match WordPress plugin order exactly
const fontMapping = [
    'Annie Use Your Telescope',
    'Asset',
    'BBH Sans Bartle',
    'BBH Sans Bogle',
    'BBH Sans Hegarty',
    'Butcherman',
    'Creepster',
    'Domine',
    'Inter',
    'Jolly Lodger',
    'Kablammo',
    'Nosifer',
    'NotoSans',
    'Oooh Baby',
    'Open Sans',
    'Orbitron',
    'Playwrite AU TAS',
    'Press Start 2P',
    'Roboto',
    'Rubik Puddles',
    'Trade Winds'
];

// Shirt color mapping - matches WordPress color slugs to hex values
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
 * Generate complete HTML for image rendering
 * This replicates the exact CSS from the WordPress plugin
 */
function generateHTML(params) {
    const {
        commentText,
        textColor,
        textAlign,
        textValign,
        fontSize,
        fontIndex,
        shirtColor
    } = params;

    const fontFamily = fontMapping[fontIndex] || 'NotoSans';
    const bgColor = shirtColorMapping[shirtColor] || '#fffefa';
    
    // Map alignment values to CSS flex properties
    const justifyContent = textAlign === 'left' ? 'flex-start' : 
                          textAlign === 'right' ? 'flex-end' : 'center';
    
    const alignItems = textValign === 'flex-start' ? 'flex-start' :
                       textValign === 'flex-end' ? 'flex-end' : 'center';

    // Calculate scaled font size (matching client-side logic)
    // Text area width is 400px (40% of 1000px due to 30% left/right margins)
    const actualMaskWidth = 400;
    const imageGenerationWidth = 1800;
    const scaleFactor = actualMaskWidth / imageGenerationWidth;
    const scaledFontSize = fontSize * scaleFactor;

    // Get absolute paths for local files
    const fontsPath = path.join(__dirname, '../fonts');
    const imagesPath = path.join(__dirname, '../images');
    const shirtImagePath = path.join(imagesPath, 'front.png');

    console.log(`[Template] Font: ${fontFamily}, Scaled size: ${scaledFontSize.toFixed(2)}px`);
    console.log(`[Template] Shirt image path: ${shirtImagePath}`);
    
    // Load shirt image as base64 for embedding
    const fs = require('fs');
    let shirtImageData = '';
    if (!fs.existsSync(shirtImagePath)) {
        console.error(`[Template] ERROR: Shirt image not found at ${shirtImagePath}`);
    } else {
        console.log(`[Template] ✓ Shirt image found`);
        // Convert to base64 data URL
        const imageBuffer = fs.readFileSync(shirtImagePath);
        const base64Image = imageBuffer.toString('base64');
        shirtImageData = `data:image/png;base64,${base64Image}`;
        console.log(`[Template] ✓ Shirt image loaded as base64 (${Math.round(base64Image.length / 1024)}KB)`);
    }

    return `
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1000, initial-scale=1.0">
    <title>Preview Image</title>
    <style>
        ${generateFontFaces(fontsPath)}
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            width: 1000px;
            height: 1000px;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        
        .container {
            width: 1000px;
            height: 1000px;
            position: relative;
            background-color: ${bgColor};
        }
        
        .shirt-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
            z-index: 0;
        }
        
        .comment-mask {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            margin: 20% 30% 27% 30%;
            display: flex;
            flex-wrap: wrap;
            align-items: ${alignItems};
            align-content: ${alignItems};
            justify-content: ${justifyContent};
            text-align: ${textAlign};
            color: ${textColor};
            font-family: '${fontFamily}', sans-serif;
            font-size: ${scaledFontSize}px;
            white-space: pre-wrap;
            overflow-wrap: break-word;
            word-wrap: break-word;
            word-break: break-word;
            overflow: hidden;
            line-height: 1;
            z-index: 1;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="container">
        ${shirtImageData ? `<img src="${shirtImageData}" class="shirt-image" alt="T-shirt">` : '<!-- Shirt image not found -->'}
        <div class="comment-mask">${escapeHtml(commentText)}</div>
    </div>
</body>
</html>
    `.trim();
}

/**
 * CACHED FONTS - Load once on startup, reuse for all requests
 * This prevents memory exhaustion from re-reading fonts on every request
 */
let CACHED_FONT_FACES = null;
let FONT_CACHE_LOADED = false;

/**
 * Generate @font-face CSS rules for all fonts
 * Embeds fonts as base64 data URLs for Puppeteer compatibility
 * Caches the base64 data on first call to avoid re-reading files
 */
function generateFontFaces(fontsBasePath) {
    // Return cached version if already loaded
    if (FONT_CACHE_LOADED && CACHED_FONT_FACES) {
        return CACHED_FONT_FACES;
    }
    
    console.log('[Font] Loading fonts into memory cache (first time only)...');
    const fs = require('fs');
    const path = require('path');
    
    // Helper function to convert font file to base64 data URL
    function fontToBase64(fontPath) {
        try {
            if (fs.existsSync(fontPath)) {
                const fontBuffer = fs.readFileSync(fontPath);
                const base64Font = fontBuffer.toString('base64');
                return `data:font/truetype;charset=utf-8;base64,${base64Font}`;
            } else {
                console.warn(`[Font] Warning: Font file not found: ${fontPath}`);
                return null;
            }
        } catch (error) {
            console.error(`[Font] Error loading font: ${fontPath}`, error.message);
            return null;
        }
    }
    
    // Define all font paths
    const fonts = [
        { family: 'Annie Use Your Telescope', path: 'Annie_Use_Your_Telescope/AnnieUseYourTelescope-Regular.ttf' },
        { family: 'Asset', path: 'Asset/Asset-Regular.ttf' },
        { family: 'BBH Sans Bartle', path: 'BBH_Sans_Bartle/BBHSansBartle-Regular.ttf' },
        { family: 'BBH Sans Bogle', path: 'BBH_Sans_Bogle/BBHSansBogle-Regular.ttf' },
        { family: 'BBH Sans Hegarty', path: 'BBH_Sans_Hegarty/BBHSansHegarty-Regular.ttf' },
        { family: 'Butcherman', path: 'Butcherman/Butcherman-Regular.ttf' },
        { family: 'Creepster', path: 'Creepster/Creepster-Regular.ttf' },
        { family: 'Domine', path: 'Domine/static/Domine-Regular.ttf' },
        { family: 'Inter', path: 'inter/static/Inter-Regular.ttf' },
        { family: 'Jolly Lodger', path: 'Jolly_Lodger/JollyLodger-Regular.ttf' },
        { family: 'Kablammo', path: 'Kablammo/Kablammo-Regular-VariableFont_MORF.ttf' },
        { family: 'Nosifer', path: 'Nosifer/Nosifer-Regular.ttf' },
        { family: 'NotoSans', path: 'NotoSans/NotoSans[wght].ttf', weight: '100 900' },
        { family: 'Oooh Baby', path: 'Oooh_Baby/OoohBaby-Regular.ttf' },
        { family: 'Open Sans', path: 'Open_Sans/static/OpenSans-Regular.ttf' },
        { family: 'Orbitron', path: 'Orbitron/static/Orbitron-Regular.ttf' },
        { family: 'Playwrite AU TAS', path: 'Playwrite_AU_TAS/static/PlaywriteAUTAS-Regular.ttf' },
        { family: 'Press Start 2P', path: 'Press_Start_2P/PressStart2P-Regular.ttf' },
        { family: 'Roboto', path: 'Roboto/static/Roboto-Regular.ttf' },
        { family: 'Rubik Puddles', path: 'Rubik_Puddles/RubikPuddles-Regular.ttf' },
        { family: 'Trade Winds', path: 'Trade_Winds/TradeWinds-Regular.ttf' }
    ];
    
    let fontFaceRules = '';
    let loadedCount = 0;
    let totalSize = 0;
    
    fonts.forEach(font => {
        const fullPath = path.join(fontsBasePath, font.path);
        const dataUrl = fontToBase64(fullPath);
        
        if (dataUrl) {
            const weightRule = font.weight ? `font-weight: ${font.weight};` : '';
            fontFaceRules += `
        @font-face {
            font-family: '${font.family}';
            src: url('${dataUrl}') format('truetype');
            ${weightRule}
            font-display: swap;
        }`;
            loadedCount++;
            totalSize += dataUrl.length;
        }
    });
    
    // Cache the result for reuse
    CACHED_FONT_FACES = fontFaceRules;
    FONT_CACHE_LOADED = true;
    
    console.log(`[Font] ✅ Loaded ${loadedCount}/${fonts.length} fonts into cache`);
    console.log(`[Font] Total cached size: ${Math.round(totalSize / 1024 / 1024)} MB`);
    console.log(`[Font] Font faces will be reused for all subsequent requests`);
    
    return fontFaceRules;
}

/**
 * Escape HTML special characters to prevent XSS
 */
function escapeHtml(text) {
    return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

module.exports = { 
    generateHTML,
    fontMapping,
    shirtColorMapping 
};

