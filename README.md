# Taurus HyvaSvgSprite

The `Taurus_HyvaSvgSprite` module optimizes SVG rendering in Hyva themes by automatically converting individual SVG icons into a single SVG sprite.

## Overview

In standard Hyva themes, icons are rendered as individual `<svg>` elements directly in the HTML. While efficient, this can lead to a larger DOM size if many icons are used or if the same icon is repeated multiple times.

This module intercepts the `Hyva\Theme\ViewModel\SvgIcons::renderHtml` method. Instead of returning the full SVG content, it:
1. Adds the icon to a centralized `SvgSprite` ViewModel.
2. Returns a light `<svg><use href="#icon-id" /></svg>` tag.
3. Renders all collected icons as `<symbol>` elements within a hidden `<svg>` sprite at the bottom of the page (before the `</body>` tag).

### Cached blocks

Blocks loaded from the `block_html` cache (e.g. Hyva product list items, catalog product widgets) never call
`renderHtml`, so their icons can't register themselves in the sprite. To cover this:

- every symbol added to the sprite is also stored in a persistent registry (`HYVA_SVG_SPRITE_REGISTRY`,
  kept in the `block_html` cache type, so it is flushed together with the cached blocks);
- the `view_block_abstract_to_html_after` observer (`Observer\CollectCachedIcons`) scans each block's output
  (cache hits included) for `<use href="#icon-...">` and adds the missing symbols from the registry.

Only blocks rendered before the sprite block (`before.body.end`) are covered.

## Features

- **Performance Optimization**: Reduces the overall size of the HTML document by reusing SVG definitions.
- **Automatic Integration**: No changes needed to existing templates. It automatically plugins into Hyva's standard icon rendering logic.
- **Zero Configuration**: Works out of the box once enabled.

## Installation

### Via Composer

1. Install the module:
   ```bash
   composer require taurus-media/module-hyva-svg-sprite
   ```

2. Run the following Magento commands:
   ```bash
   bin/magento module:enable Taurus_HyvaSvgSprite
   bin/magento setup:upgrade
   bin/magento cache:flush
   ```

### Manual Installation

1. Copy the module files to `app/code/Taurus/HyvaSvgSprite`.
2. Run the following Magento commands:
   ```bash
   bin/magento module:enable Taurus_HyvaSvgSprite
   bin/magento setup:upgrade
   bin/magento cache:flush
   ```
