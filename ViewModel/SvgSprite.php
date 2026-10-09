<?php
/**
 * Copyright (c) 2026. Taurus. All rights reserved
 */

declare(strict_types=1);

namespace Taurus\HyvaSvgSprite\ViewModel;

use Magento\Framework\App\Cache\Type\Block as BlockCache;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Psr\Log\LoggerInterface;

class SvgSprite implements ArgumentInterface
{
    /**
     * Cache id of the persistent "icon id => <symbol>" registry.
     *
     * Blocks loaded from the block_html cache never call SvgIcons::renderHtml(), so their icons
     * can't register themselves. The registry lets us restore those symbols by id.
     * It lives in the block_html cache type, so it is flushed together with the cached blocks.
     */
    private const REGISTRY_CACHE_ID = 'HYVA_SVG_SPRITE_REGISTRY';

    /**
     * @var array
     */
    private array $icons = [];

    /**
     * @var array|null
     */
    private ?array $registry = null;

    /**
     * @var array
     */
    private array $newRegistryIcons = [];

    /**
     * @var BlockCache
     */
    private BlockCache $cache;

    /**
     * @var Json
     */
    private Json $serializer;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param BlockCache $cache
     * @param Json $serializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        BlockCache $cache,
        Json $serializer,
        LoggerInterface $logger
    ) {
        $this->cache = $cache;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

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

            $registry = $this->getRegistry();
            if (($registry[$id] ?? null) !== $this->icons[$id]) {
                $this->newRegistryIcons[$id] = $this->icons[$id];
            }
        }
    }

    /**
     * Add an icon referenced by already rendered (e.g. cached) HTML
     *
     * @param string $id
     * @return void
     */
    public function addIconById(string $id): void
    {
        if (isset($this->icons[$id])) {
            return;
        }

        $registry = $this->getRegistry();
        if (isset($registry[$id])) {
            $this->icons[$id] = $registry[$id];
        } else {
            $this->logger->debug(sprintf('Taurus_HyvaSvgSprite: no symbol found for "%s"', $id));
        }
    }

    /**
     * @return string
     */
    public function getSpriteHtml(): string
    {
        $this->saveRegistry();

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
     * @return array
     */
    private function getRegistry(): array
    {
        if ($this->registry === null) {
            $this->registry = $this->loadRegistry();
        }

        return $this->registry;
    }

    /**
     * @return array
     */
    private function loadRegistry(): array
    {
        $data = $this->cache->load(self::REGISTRY_CACHE_ID);
        if (!$data) {
            return [];
        }

        try {
            $registry = $this->serializer->unserialize($data);
        } catch (\InvalidArgumentException $e) {
            return [];
        }

        return is_array($registry) ? $registry : [];
    }

    /**
     * @return void
     */
    private function saveRegistry(): void
    {
        if (empty($this->newRegistryIcons)) {
            return;
        }

        // Re-read to merge icons saved by concurrent requests
        $this->registry = array_merge($this->loadRegistry(), $this->newRegistryIcons);
        $this->cache->save($this->serializer->serialize($this->registry), self::REGISTRY_CACHE_ID);
        $this->newRegistryIcons = [];
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
