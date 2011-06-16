<?php
/**
 * FEproc - Mail template backend module for FormExpress for 
 *   Zikula Content Management System
 * 
 * @copyrightt (C) 2002 by Jason Judge, 2011 Chris Candreva
 * @Version $Id: tables.php 84 2011-05-27 18:19:28Z ccandreva $
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package FEproc
 *
 *
 * LICENSE
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License (GPL)
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WIthOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * To read the license please visit http://www.gnu.org/copyleft/gpl.html
 * ----------------------------------------------------------------------
 * Original Author of file: Jason Judge.
 * Based on template by Jim MacDonald.
 * Current Maintainer of file: Chris Candreva <chris@westnet.com>
 * ----------------------------------------------------------------------
 * 
 */

/**
 * This function is called internally by the core whenever the module is
 * loaded.  It adds in the database table information.
 */
function feproc_tables()
{
    // Initialise table array
    $table = array();

    // Handlers table.
    $table['feproc_handlers'] = DBUtil::getLimitedTablename('feproc_handlers');
    $table['feproc_handlers_column'] = array(
              'id'          => 'fp_id',
              'name'        => 'fp_name',
              'description' => 'fp_description',
              'type'        => 'fp_type',
              'version'     => 'fp_version',
              'modulename'  => 'fp_modulename',
              'apiname'     => 'fp_apiname',
              'apifunc'     => 'fp_apifunc',
              'attributes'  => 'fp_attributes',
        );
    $table['feproc_handlers_column_def'] = array(
              'id'          => 'I NOTNULL PRIMARY AUTOINCREMENT',
              'name'        => 'C(64)',
              'description' => 'X2',
              'type'        => 'C(30)',
              'version'     => 'C(30)',
              'modulename'  => 'C(60)',
              'apiname'     => 'C(60)',
              'apifunc'     => 'C(60)',
              'attributes'  => 'X2',
        );

    // Workflow table.
    $table['feproc_workflow'] = DBUtil::getLimitedTablename('feproc_workflow');
    $table['feproc_workflow_column'] = array(
              'id'          => 'fp_id',
              'name'        => 'fp_name',
              'description' => 'fp_description',
              'type'        => 'fp_type',
              'handlerid'   => 'fp_handler_id',
              'secure'      => 'fp_secure',
              'attributes'  => 'fp_attributes',
              'setid'       => 'fp_set_id',
              'startstage'  => 'fp_start_stage',
              'successid'   => 'fp_success_id',
              'failureid'   => 'fp_failure_id',
        );
    $table['feproc_workflow_column_def'] = array(
              'id'          => 'I NOTNULL PRIMARY AUTOINCREMENT',
              'name'        => 'C(64)',
              'description' => 'X2',
              'type'        => 'C(30)',
              'handlerid'   => 'I',
              'secure'      => 'L',
              'attributes'  => 'L',
              'setid'       => 'I',
              'startstage'  => 'L',
              'successid'   => 'I',
              'failureid'   => 'I',
        );

    // Return the table information
    return $table;
}

?>
