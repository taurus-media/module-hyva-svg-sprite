<?php
/**
 * Copyright (c) 2026. Taurus. All rights reserved
 */

declare(strict_types=1);

namespace Taurus\HyvaSvgSprite\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Taurus\HyvaSvgSprite\ViewModel\SvgSprite;

/**
 * Registers icons referenced by block output, including blocks loaded from the block_html cache,
 * where SvgIcons::renderHtml() is never called.
 */
class CollectCachedIcons implements ObserverInterface
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
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $html = (string)$observer->getEvent()->getTransport()->getHtml();
        if (strpos($html, '<use href="#icon-') === false) {
            return;
        }

        if (preg_match_all('/<use href="#(icon-[^"]+)"/', $html, $matches)) {
            foreach (array_unique($matches[1]) as $iconId) {
                $this->svgSprite->addIconById($iconId);
            }
        }
    }
}
