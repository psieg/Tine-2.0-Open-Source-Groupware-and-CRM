<?php
/**
 * backend class for Zend_Json_Server
 *
 * This class handles all Json requests for the admin application
 *
 * @package     Admin
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
class Admin_Json
{
    public function getApplication($applicationId)
    {
        $egwApplications = new Egwbase_Application();
        
        $application = $egwApplications->getApplicationById($applicationId);
        
        return $application->toArray();
    }
    
    //public function getApplications($filter, $sort, $dir, $limit, $start)
    public function getApplications($sort, $dir, $limit, $start)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        $egwApplications = new Egwbase_Application();
        
        $applicationSet = $egwApplications->getApplications($sort, $dir, NULL, $limit, $start);

        $result['results']    = $applicationSet->toArray();
        $result['totalcount'] = $egwApplications->getTotalApplicationCount();
        
        return $result;
    }
    
    /**
     * Returns the structure of the initial tree for this application.
     *
     * This function returns the needed structure, to display the initial tree, after the the logoin.
     * Additional tree items get loaded on demand.
     *
     * @return array
     */
    public function getInitialTree($_location)
    {
        switch($_location) {
            case 'mainTree':
                $treeNodes = array();

                $treeNode = new Egwbase_Ext_Treenode('Admin', 'applications', 'applications', 'Applications', TRUE);
                //$treeNode->setIcon('apps/kaddressbook.png');
                $treeNode->cls = 'treemain';
                $treeNode->jsonMethod = 'Admin.getApplications';
                $treeNode->dataPanelType = 'applications';
                $treeNodes[] = $treeNode;

//                $treeNode = new Egwbase_Ext_Treenode('Addressbook', 'alllists', 'alllists', 'All Lists', FALSE);
//                $treeNode->setIcon('apps/kaddressbook.png');
//                $treeNode->cls = 'treemain';
//                $treeNode->owner = 'alllists';
//                $treeNode->jsonMethod = 'Addressbook.getListsByOwner';
//                $treeNode->dataPanelType = 'lists';
//                $treeNodes[] = $treeNode;

                return $treeNodes;
                 
                break;
        }
    }
}