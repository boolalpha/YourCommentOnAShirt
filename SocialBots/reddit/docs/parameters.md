# Parameter Reference

Complete documentation for all bot parameters.

## Overview

When mentioning the bot, users can specify parameters to customize their shirt. Parameters use the format:

```
/u/YourCommentOnAShirtBot parameter1: value1, parameter2: value2
```

## Parameter Categories

- **[Control Parameters](#control-parameters)** - Control bot behavior
- **[Shirt Parameters](#shirt-parameters)** - Shirt color and size
- **[Text Parameters](#text-parameters)** - Text styling
- **[Layout Parameters](#layout-parameters)** - Text positioning
- **[Attribution Parameters](#attribution-parameters)** - Author attribution

---

## Control Parameters

These parameters control the bot's behavior and are NOT passed to the product URL.

### `parent`

**Description**: Which comment in the tree to use for the shirt text.

**Type**: Integer (0-N)

**Default**: `1` (parent comment)

**Values**:
- `0`: Use the mention comment itself
- `1`: Use parent comment (default)
- `2`: Use grandparent comment
- `N`: Use comment N levels up the tree

**Examples**:

```
/u/YourCommentOnAShirtBot parent: 0
```
Uses the text of the mention comment itself.

```
/u/YourCommentOnAShirtBot parent: 2
```
Uses the grandparent comment (2 levels up).

**Notes**:
- If parent level exceeds tree depth, uses the highest available comment
- Gracefully handles deleted comments by falling back

---

## Shirt Parameters

Parameters that control the physical shirt attributes.

### `shirtColor` / `attribute_pa_color`

**Description**: The color of the shirt.

**Type**: String (choice)

**Default**: `white`

**Valid Values**:
- `white`
- `black`
- `gold`
- `irish-green`
- `orange`
- `red`
- `royal`

**Aliases**: `shirtColor`, `shirtcolor`

**Examples**:

```
/u/YourCommentOnAShirtBot shirtColor: black
```

```
/u/YourCommentOnAShirtBot shirtColor: irish-green, size: xl
```

---

### `size` / `attribute_pa_size`

**Description**: The size of the shirt.

**Type**: String (choice)

**Default**: `l`

**Valid Values**:
- `xs` (Extra Small)
- `s` (Small)
- `m` (Medium)
- `l` (Large)
- `xl` (Extra Large)
- `2xl` (2X Large)
- `3xl` (3X Large)

**Aliases**: `size`

**Examples**:

```
/u/YourCommentOnAShirtBot size: xl
```

```
/u/YourCommentOnAShirtBot size: 2xl, shirtColor: black
```

---

## Text Parameters

Parameters that control the text appearance.

### `textColor` / `color`

**Description**: The color of the text on the shirt.

**Type**: Hex color code

**Default**: `#000000` (black)

**Format**: 
- 6-digit hex: `#RRGGBB` or `RRGGBB`
- 3-digit hex: `#RGB` or `RGB`

The `#` prefix is optional and will be added automatically.

**Aliases**: `textColor`, `textcolor`

**Examples**:

```
/u/YourCommentOnAShirtBot textColor: #ff0000
```
Red text.

```
/u/YourCommentOnAShirtBot textColor: 00ff00
```
Green text (# prefix added automatically).

```
/u/YourCommentOnAShirtBot textColor: #fff
```
White text (shorthand).

---

### `fontSize` / `fontsize`

**Description**: The size of the text.

**Type**: Integer

**Default**: `120`

**Range**: `20-300`

**Aliases**: `fontSize`, `fontsize`

**Examples**:

```
/u/YourCommentOnAShirtBot fontSize: 200
```
Large text.

```
/u/YourCommentOnAShirtBot fontSize: 50
```
Small text.

**Notes**:
- Values outside the range will be ignored and defaults used
- Very large values may cause text to overflow

---

### `font`

**Description**: The font to use for the text.

**Type**: Integer (font index)

**Default**: `12` (NotoSans)

**Range**: `0-20`

**Available Fonts**:

| Index | Font Name |
|-------|-----------|
| 0 | Annie Use Your Telescope |
| 1 | Asset |
| 2 | BBH Sans Bartle |
| 3 | BBH Sans Bogle |
| 4 | BBH Sans Hegarty |
| 5 | Butcherman |
| 6 | Creepster |
| 7 | Domine |
| 8 | Inter |
| 9 | Jolly Lodger |
| 10 | Kablammo |
| 11 | Nosifer |
| 12 | NotoSans (DEFAULT) |
| 13 | Oooh Baby |
| 14 | Open Sans |
| 15 | Orbitron |
| 16 | Playwrite AU TAS |
| 17 | Press Start 2P |
| 18 | Roboto |
| 19 | Rubik Puddles |
| 20 | Trade Winds |

**Examples**:

```
/u/YourCommentOnAShirtBot font: 11
```
Uses Nosifer font (spooky style).

```
/u/YourCommentOnAShirtBot font: 17
```
Uses Press Start 2P font (retro video game style).

---

## Layout Parameters

Parameters that control text positioning on the shirt.

### `textAlign` / `align`

**Description**: Horizontal alignment of text.

**Type**: String (choice)

**Default**: `center`

**Valid Values**:
- `left`
- `center`
- `right`

**Aliases**: `textAlign`, `textalign`

**Examples**:

```
/u/YourCommentOnAShirtBot textAlign: left
```

```
/u/YourCommentOnAShirtBot textAlign: right, verticalAlign: top
```

---

### `verticalAlign` / `valign`

**Description**: Vertical alignment of text.

**Type**: String (choice)

**Default**: `center`

**Valid Values**:
- `top`
- `center`
- `bottom`

**Aliases**: `verticalAlign`, `verticalalign`

**Examples**:

```
/u/YourCommentOnAShirtBot verticalAlign: top
```

```
/u/YourCommentOnAShirtBot verticalAlign: bottom
```

---

## Attribution Parameters

Parameters for adding author attribution to the text.

### `addAuthor`

**Description**: Whether to add the comment author's username to the text.

**Type**: Boolean

**Default**: `false`

**Valid Values**: `true`, `false`, `yes`, `no`, `1`, `0`

**Format**: Adds `\n- @username` to the end of the text.

**Examples**:

```
/u/YourCommentOnAShirtBot addAuthor: true
```
Adds "- @OriginalAuthor" to the shirt.

```
/u/YourCommentOnAShirtBot addAuthor: yes
```
Same effect (multiple boolean formats accepted).

**Notes**:
- If the comment author is deleted, attribution is skipped
- Overridden by `author` parameter if both specified

---

### `author`

**Description**: Custom author text to add to the shirt.

**Type**: String (any text)

**Default**: None

**Format**: Adds `\n- <your text>` to the end of the text.

**Overrides**: `addAuthor` parameter

**Examples**:

```
/u/YourCommentOnAShirtBot author: Rick Astley
```
Adds "- Rick Astley" to the shirt.

```
/u/YourCommentOnAShirtBot author: Anonymous
```
Adds "- Anonymous" instead of the real username.

**Notes**:
- Use this for custom attribution (quotes, pen names, etc.)
- Takes precedence over `addAuthor`

---

## Complex Examples

### Example 1: Full Customization

```
/u/YourCommentOnAShirtBot shirtColor: black, textColor: #00ff00, size: xl, fontSize: 150, font: 17, textAlign: center, verticalAlign: center, addAuthor: true
```

Creates:
- Black shirt
- Green text
- XL size
- Large font (150)
- Press Start 2P font
- Centered text (both axes)
- Includes author attribution

### Example 2: Targeting Grandparent with Custom Author

```
/u/YourCommentOnAShirtBot parent: 2, author: Confucius, fontSize: 100, font: 7
```

Creates:
- Uses grandparent comment
- Attributes to "Confucius"
- Medium font size
- Domine font (elegant serif)

### Example 3: Minimal Parameters

```
/u/YourCommentOnAShirtBot textColor: red
```

Uses defaults for everything except text color (red).

### Example 4: Targeting Self

```
/u/YourCommentOnAShirtBot parent: 0, shirtColor: gold, textColor: #000
```

Uses the mention comment itself on a gold shirt with black text.

---

## Parameter Parsing Rules

1. **Case-Insensitive**: Keys are case-insensitive (`shirtColor`, `shirtcolor`, `ShirtColor` all work)
2. **Whitespace Flexible**: Spaces around colons and commas are optional
3. **Comma Separated**: Parameters are separated by commas
4. **Validation**: Invalid values are silently ignored (defaults used)
5. **Order Independent**: Parameters can be in any order
6. **Special Params Excluded**: `parent`, `addAuthor`, `author` are not in the URL
7. **Malformed Params Skipped**: If parsing fails, parameter is ignored

## Validation Behavior

When a parameter value is invalid:

- **Invalid Type** (e.g., text for number): Parameter ignored, default used
- **Out of Range** (e.g., fontSize: 500): Parameter ignored, default used
- **Invalid Choice** (e.g., shirtColor: purple): Parameter ignored, default used
- **Malformed** (e.g., missing value): Parameter ignored

The bot logs warnings for invalid parameters but continues processing.

## URL Encoding

- Special characters are properly URL-encoded
- Spaces in comment text become `+` or `%20`
- Newlines become `%0A`
- Hex color `#` becomes `%23`
- Ampersands, quotes, etc. are encoded correctly

## Comment Text Processing

- **Max Length**: Comments are truncated to `max_comment_length` (default 280 characters)
- **Attribution First**: Author attribution is added before truncation
- **Newlines Preserved**: Line breaks in original comment are maintained
- **Special Characters**: Properly encoded for URLs

---

## Testing Parameters

To test parameter combinations without making them public:

1. Create a private subreddit
2. Make test posts/comments
3. Mention the bot with parameters
4. Check the generated URL

Or use a tool like [URL Decoder](https://www.urldecoder.org/) to inspect the generated URLs.

---

## Support

For questions about parameters:
- Review examples in this document
- Check [troubleshooting.md](troubleshooting.md)
- Test in a private subreddit first
- Contact: support@yourcommentonashirt.com

