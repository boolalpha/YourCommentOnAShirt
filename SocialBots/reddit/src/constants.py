"""
Constants used throughout the bot application.
"""
from typing import List, Set

# Font list matching the website's available fonts
AVAILABLE_FONTS: List[str] = [
    'Annie Use Your Telescope',  # 0
    'Asset',                     # 1
    'BBH Sans Bartle',          # 2
    'BBH Sans Bogle',           # 3
    'BBH Sans Hegarty',         # 4
    'Butcherman',               # 5
    'Creepster',                # 6
    'Domine',                   # 7
    'Inter',                    # 8
    'Jolly Lodger',             # 9
    'Kablammo',                 # 10
    'Nosifer',                  # 11
    'NotoSans',                 # 12 (DEFAULT)
    'Oooh Baby',                # 13
    'Open Sans',                # 14
    'Orbitron',                 # 15
    'Playwrite AU TAS',         # 16
    'Press Start 2P',           # 17
    'Roboto',                   # 18
    'Rubik Puddles',            # 19
    'Trade Winds'               # 20
]

# Default font index
DEFAULT_FONT_INDEX: int = 12  # NotoSans

# Valid shirt colors
VALID_SHIRT_COLORS: Set[str] = {
    'white', 'black', 'gold', 'irish-green', 'orange', 'red', 'royal'
}

# Valid shirt sizes
VALID_SHIRT_SIZES: Set[str] = {
    'xs', 's', 'm', 'l', 'xl', '2xl', '3xl'
}

# Valid text alignments
VALID_TEXT_ALIGNMENTS: Set[str] = {'left', 'center', 'right'}

# Valid vertical alignments
VALID_VERTICAL_ALIGNMENTS: Set[str] = {'top', 'center', 'bottom'}

# Font size range
MIN_FONT_SIZE: int = 20
MAX_FONT_SIZE: int = 300

# Font index range
MIN_FONT_INDEX: int = 0
MAX_FONT_INDEX: int = 20  # len(AVAILABLE_FONTS) - 1

# Parameter aliases - maps user-friendly names to URL parameter names
PARAMETER_ALIASES: dict = {
    'shirtColor': 'attribute_pa_color',
    'shirtcolor': 'attribute_pa_color',
    'textColor': 'color',
    'textcolor': 'color',
    'size': 'attribute_pa_size',
    'fontSize': 'fontsize',
    'fontsize': 'fontsize',
    'textAlign': 'align',
    'textalign': 'align',
    'verticalAlign': 'valign',
    'verticalalign': 'valign',
}

# Special parameters that control bot behavior (not passed to URL)
SPECIAL_PARAMETERS: Set[str] = {'parent', 'addAuthor', 'addauthor', 'author'}
