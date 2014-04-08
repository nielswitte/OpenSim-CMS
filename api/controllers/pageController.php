<?php
namespace Controllers;

defined('EXEC') or die('Config not loaded');

/**
 * This class is the page controller
 *
 * @author Niels Witte
 * @version 0.1
 * @date April 4th, 2014
 * @since April 4th, 2014
 */
class PageController {
    private $page;

    /**
     * Constructs a new controller for the given page
     *
     * @param \Models\Page $page
     */
    public function __construct(\Models\Page $page) {
        $this->page = $page;
    }

    /**
     * Updates the UUID of the page to the given value
     *
     * @param string $uuid - The UUID of the page
     * @param \Models\Grid $grid - The grid the texture is used on
     * @return boolean
     * @throws \Exception
     */
    public function setUuid($uuid, \Models\Grid $grid) {
        $results = FALSE;
        if(\Helper::isValidUuid($uuid)) {
            $db = \Helper::getDB();
            $cacheData = array(
                'gridId'        => $db->escape($grid->getId()),
                'uuid'          => $db->escape($uuid),
                'uuidExpires'   => $db->escape(date('Y-m-d H:i:s', strtotime('+'. $grid->getCacheTime())))
            );

            $cacheId = $db->insert('cached_assets', $cacheData);
            $cachePageData = array(
                'pageId'        => $db->escape($this->page->getId()),
                'cacheId'       => $db->escape($cacheId)
            );

            $results = $db->insert('document_pages_cache', $cachePageData);
        } else {
            throw new \Exception('Invalid UUID provided', 2);
        }

        if($results === FALSE) {
            throw new \Exception('Updating UUID failed', 1);
        }
        return $results !== FALSE;
    }
}
