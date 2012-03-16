<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.0                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CDM
 * @copyright CiviCRM LLC (c) 2004-2011
 * $Id$
 *
 */

class CDM_Utils {
    
    static function getEvents( ) {
        require_once 'CRM/Event/BAO/Event.php';
        $eventInfo = CRM_Event_BAO_Event::getCompleteInfo( );
        if ( ! empty( $eventInfo ) ) {
            $events    = array( );
            foreach ( $eventInfo as $info ) {
                $events[$info['event_id']] = $info['title'];
            }
            return $events;
        }
        return null;
    }

    static function getPriceSets( ) {
        $values = self::getPriceSetsInfo( );

        $priceSets = array( );
        if ( ! empty( $values ) ) {
            foreach ( $values as $set ) {
                $priceSets[$set['item_id']] = "{$set['ps_label']} :: {$set['pf_label']} :: {$set['item_label']}";
            }
        }
        return $priceSets;
    }

    static function getPriceSetsInfo( ) {
        $sql = "
SELECT    pfv.id as item_id,
          pfv.label as item_label,
          pf.label as pf_label,
          ps.title as ps_label
FROM      civicrm_price_field_value as pfv 
LEFT JOIN civicrm_price_field as pf on (pf.id = pfv.price_field_id)
LEFT JOIN civicrm_price_set as ps on (ps.id = pf.price_set_id)
ORDER BY  pf_label, pfv.price_field_id, pfv.weight
";
        $dao = CRM_Core_DAO::executeQuery( $sql );
        $priceSets = array( );
        while ( $dao->fetch( ) ) {
            $priceSets[$dao->item_id] = array(
                                              'item_id' => $dao->item_id,
                                              'item_label' => $dao->item_label,
                                              'pf_label' => $dao->pf_label,
                                              'ps_label' => $dao->ps_label
                                              );
        }

        return $priceSets;
    }

    /**
     * Sort of acts like array_intersect(). We want to match value of one array
     * with key of another to return the id and title for things like events, membership, etc.
     */
    static function getIdsTitles( $ids = array(), $titles = array() ) {
        $a = array();
        foreach ($ids as $k => $v) {
            $a[$v] = $titles[$v];
        }

        return $a;
    }
}

