<?php
namespace Models;

defined('EXEC') or die('Invalid request');

require_once dirname(__FILE__) .'/file.php';
require_once dirname(__FILE__) .'/page.php';
/**
 * This class is the presentation model
 *
 * @author Niels Witte
 * @version 0.4
 * @date April 2nd, 2014
 * @since February 10th, 2014
 */
class Document extends File {
    /**
     * The currently active page
     * @var integer
     */
    private $currentPage;
    /**
     * List with pages
     * @var array
     */
    private $pages = array();

    /**
     * Constructs a new document with the given id and optional the given page
     *
     * @param integer $id - ID of this presentation
     * @param integer $currentPage - [Optional] The currently selected page
     * @param string $title - [Optional] Title of document
     * @param integer $ownerId - [Optional] ID of the owner
     * @param string $creationDate - [Optional] Creation date time, YYYY-MM-DD HH:mm:ss
     * @param string $modificationDate - [Optional] Date of last modification, YYYY-MM-DD HH:mm:ss
     * @param string $file - [Optional] The file name and extension of this source file
     */
	public function __construct($id, $currentPage = 0, $title = '', $ownerId = '', $creationDate = '', $modificationDate = '', $file = '') {
        $this->currentPage = $currentPage;

        parent::__construct($id, 'document', $title, $ownerId, $creationDate, $modificationDate, $file);
    }

    /**
     * Returns the page number, starts at 1
     *
     * @return integer
     */
	public function getCurrentPage() {
		return $this->currentPage;
	}

    /**
     * Returns an array with pages of this presentation
     *
     * @return array
     */
    public function getPages() {
        if(empty($this->pages)) {
            $db = \Helper::getDB();
            $db->where('documentId', $db->escape($this->getId()));
            $db->orderBy('p.id', 'ASC');
            $db->join('comments c', 'c.itemId = p.id AND c.type = "page" ', 'LEFT');
            $db->groupBy('p.id');
            $results = $db->get('document_pages p', NULL, '*, count(c.id) as commentsCount, p.id as id');

            $i = 1;
            foreach($results as $result) {
                $hasComments = $result['commentsCount'] > 0 ? TRUE : FALSE;
                $this->pages[$i] = new \Models\Page($result['id'], $i, $this->getPath(). DS . $i .'.'. IMAGE_TYPE, $hasComments);
                $i++;
            }
        }

        return $this->pages;
    }

    /**
     * Get the page with the given number
     *
     * @param integer $number
     * @return \Models\Page
     * @throws \Exception
     */
    public function getPageByNumber($number) {
        if(empty($this->pages)) {
            $this->getPages();
        }

        $page = FALSE;
        // Page exists? (array starts counting at 1)
        if(isset($this->pages[$number])) {
            $page = $this->pages[$number];
        } else {
            throw new \Exception('Page does not exist', 6);
        }
        return $page;
    }

    /**
     * Get the page with the given id
     *
     * @param integer $id
     * @return \Models\Page
     * @throws \Exception
     */
    public function getPageById($id) {
        if(empty($this->pages)) {
            $this->getPages();
        }

        $result = FALSE;
        // Page exists? (array starts counting at 1)
        if(count($this->getPages()) > 0) {
            foreach($this->getPages() as $page) {
                if($page->getId() == $id) {
                    return $page;
                }
            }
        }
        throw new \Exception('Page does not exist', 6);
    }

    /**
     * Counts the number of pages
     *
     * @return integer
     */
    public function getNumberOfPages() {
        if(empty($this->pages)) {
            $this->getPages();
        }
        return count($this->pages);
    }

    /**
     * Returns the path to the thumbnails of this presentation
     *
     * @return string
     */
    public function getThumbnailPath() {
        return $this->getPath() . DS . 'thumbs';
    }
}