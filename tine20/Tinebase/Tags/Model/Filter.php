<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @subpackage  Tags
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * Task Filter Class
 * @package Tasks
 */
class Tinebase_Tags_Model_Filter extends Tinebase_Record_Abstract
{
	/**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tasks';
    
    protected $_validators = array(
        'id'                   => array('allowEmpty' => true,  'Alnum'),

        'owner'                => array('allowEmpty' => true),
        'application'          => array('allowEmpty' => true),
        'name'                 => array('allowEmpty' => true),
    
        'type'                 => array('presence'   => 'required',
                                        'allowEmpty' => true,
                                        'InArray'    => array(Tinebase_Tags_Model_Tag::TYPE_PERSONAL, Tinebase_Tags_Model_Tag::TYPE_SHARED),
                                        'default'    => ''
                                  ),
    );
    
    /**
     * Returns a select object according to this filter
     * 
     * @return Zend_Db_Select
     */
    public function getSelect()
    {
        $db = Zend_Registry::get('dbAdapter');
        $select = $db->select()
            ->from (array('tags' => SQL_TABLE_PREFIX . 'tags'));
        
        // IF SHARED {
        //  LEFT JOIN tags tags_acl ON id, grant
        //  LEFT JOIN tags_context ON id, context
        // }
        // WHERE name LIKE %$_filter->name% 
        
        if (!empty($this->name)) {
            $select->where($db->quoteInto('tags.name LIKE ?', $this->name));
        }
        if ($this->type) {
            $select->where($db->quoteInto('tags.type = ?', $this->type));
        }
        return $select;
    }
    
    //protected $_datetimeFields = array(
    //);
    

}