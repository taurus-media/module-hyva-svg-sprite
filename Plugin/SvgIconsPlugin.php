<?php
/**
 * Copyright (c) 2026. Taurus. All rights reserved
 */

declare(strict_types=1);

namespace Taurus\HyvaSvgSprite\Plugin;

use Hyva\Theme\ViewModel\SvgIcons;
use Taurus\HyvaSvgSprite\ViewModel\SvgSprite;

class SvgIconsPlugin
{
    /**
     * @var SvgSprite
     */
    private SvgSprite $svgSprite;

    /**
     * @param SvgSprite $svgSprite
     */
    public function __construct(
        SvgSprite $svgSprite
    ) {
        $this->svgSprite = $svgSprite;
    }

    /**
     * @param SvgIcons $subject
     * @param callable $proceed
     * @param string $icon
     * @param string $classNames
     * @param int|null $width
     * @param int|null $height
     * @param array $attributes
     * @return string
     */
    public function aroundRenderHtml(
        SvgIcons $subject,
        callable $proceed,
        string $icon,
        string $classNames = '',
        ?int $width = 24,
        ?int $height = 24,
        array $attributes = []
    ): string {
        $iconPath = $this->getIconPath($subject, $icon);

        // Convert the icon id to the following format: 'icon-solid-heart'
        $iconId = 'icon-' . basename(dirname($iconPath)) . '-' . basename($icon);

        try {
            $rawIconSvg = file_get_contents($this->getFilePath($subject, $iconPath));
            $this->svgSprite->addIcon($iconId, $rawIconSvg);

            // Create the <use> tag
            $attributesHtml = $this->serializeAttributes($attributes, $classNames, $width, $height);

            return sprintf(
                '<svg %s><use href="#%s" /></svg>',
                $attributesHtml,
                $iconId
            );
        } catch (\Exception $e) {
            // Fallback to the original method if something goes wrong
            return $proceed($icon, $classNames, $width, $height, $attributes);
        }
    }

    /**
     * @param SvgIcons $subject
     * @param string $icon
     * @return string
     * @throws \ReflectionException
     */
    private function getIconPath(SvgIcons $subject, string $icon): string
    {
        $reflection = new \ReflectionClass($subject);
        $method = $reflection->getMethod('applyPathPrefixAndIconSet');
        $method->setAccessible(true);
        return $method->invoke($subject, $icon);
    }

    /**
     * @param SvgIcons $subject
     * @param string $iconPath
     * @return string
     * @throws \ReflectionException
     */
    private function getFilePath(SvgIcons $subject, string $iconPath): string
    {
        $reflection = new \ReflectionClass($subject);
        $method = $reflection->getMethod('getFilePath');
        $method->setAccessible(true);
        return $method->invoke($subject, $iconPath);
    }

    /**
     * @param array $attributes
     * @param string $classNames
     * @param int|null $width
     * @param int|null $height
     * @return string
     */
    private function serializeAttributes(array $attributes, string $classNames, ?int $width, ?int $height): string
    {
        if (!isset($attributes['role'])) {
            $attributes['role'] = 'img';
        }
        if ($classNames) {
            $attributes['class'] = $classNames;
        }
        if ($width) {
            $attributes['width'] = $width;
        }
        if ($height) {
            $attributes['height'] = $height;
        }

        $html = '';
        foreach ($attributes as $key => $value) {
            $html .= sprintf('%s="%s" ', $key, htmlspecialchars((string)$value));
        }

        return trim($html);
    }
}
