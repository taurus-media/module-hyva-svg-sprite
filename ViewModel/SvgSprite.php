<?php
/**
 * Copyright (c) 2026. Taurus. All rights reserved
 */

declare(strict_types=1);

namespace Taurus\HyvaSvgSprite\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;

class SvgSprite implements ArgumentInterface
{
    /**
     * @var array
     */
    private array $icons = [];

    /**
     * Add a new icon to the sprite
     *
     * @param string $id
     * @param string $content
     * @return void
     */
    public function addIcon(string $id, string $content): void
    {
        if (!isset($this->icons[$id])) {
            $this->icons[$id] = $this->prepareSvgForSprite($content, $id);
        }
    }

    /**
     * @return string
     */
    public function getSpriteHtml(): string
    {
        if (empty($this->icons)) {
            return '';
        }

        $sprite = '<svg xmlns="http://www.w3.org/2000/svg" style="display: none;" id="hyva-svg-sprite">';
        foreach ($this->icons as $iconHtml) {
            $sprite .= $iconHtml;
        }
        $sprite .= '</svg>';

        return $sprite;
    }

    /**
     * @param string $svgContent
     * @param string $id
     * @return string
     */
    private function prepareSvgForSprite(string $svgContent, string $id): string
    {
        // Remove <xml ...> tag and other headers if present
        $svgContent = preg_replace('/<\?xml.*\?>/i', '', $svgContent);

        // Convert <svg ...> to <symbol id="...">
        $svgContent = preg_replace('/<svg/i', '<symbol id="' . $id . '"', $svgContent);
        $svgContent = preg_replace('/<\/svg>/i', '</symbol>', $svgContent);

        // Remove width, height, x, y attributes from the symbol tag as they are not needed in sprite symbols
        // and might interfere with the 'use' tag styling.
        // We keep viewBox.
        $svgContent = preg_replace_callback('/<symbol([^>]+)>/i', function($matches) {
            $attrs = $matches[1];
            $attrs = preg_replace('/\s(width|height|x|y|xmlns|style)="[^"]*"/i', '', $attrs);
            return '<symbol' . $attrs . '>';
        }, $svgContent);

        return $svgContent;
    }
}
