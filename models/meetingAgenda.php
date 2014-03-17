<?php
namespace Models;

defined('EXEC') or die('Config not loaded');

/**
 * This class represents a meeting
 *
 * @author Niels Witte
 * @version 0.1
 * @date March 17th, 2014
 */
class MeetingAgenda {
    private $meeting;
    private $agenda;

    /**
     * Constructs a new participants list for the given meeting
     *
     * @param \Models\Meeting $meeting
     * @param array $agenda - [Optional]
     */
    public function __construct(\Models\Meeting $meeting, $agenda = array()) {
        $this->meeting = $meeting;
        $this->agenda  = $agenda;
    }

    /**
     * Adds the given item to the agenda
     *
     * @param integer $id
     * @param string $value
     * @param integer $order
     * @param integer $parentId
     */
    public function addAgendaItem($id, $value, $order, $parentId = 0) {
        $this->agenda[] = array(
            'id'        => $id,
            'value'     => $value,
            'sort'      => $order,
            'parentId'  => $parentId
        );
    }

    /**
     * Recursive function to build up the agenda as a multidemensional array
     *
     * @source: http://stackoverflow.com/a/8587437
     * @param integer $parentId - [Optional] The starting level for parents to be matched to
     * @return array - The multidemensional sorted array
     */
    public function buildAgenda($parentId = 0) {
        $branch = array();

        // Get child elements for given parent
        foreach ($this->agenda as $element) {
            if ($element['parentId'] == $parentId) {
                $children = $this->buildAgenda($element['id']);
                if ($children) {
                    $element['items'] = $children;
                }
                $branch[] = $element;
            }
        }

        // Sort the arry by the sort value
        usort($branch, function($a, $b) {
            return $a['sort'] - $b['sort'];
        });

        return $branch;
    }
}
