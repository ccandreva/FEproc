<?php
// ----------------------------------------------------------------------
// FEproc - Mail template backend module for FormExpress for
// POST-NUKE Content Management System
// Copyright (C) 2002 by Jason Judge
// ----------------------------------------------------------------------
// Based on:
// PHP-NUKE Web Portal System - http://phpnuke.org/
// ----------------------------------------------------------------------
// LICENSE
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License (GPL)
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WIthOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
// ----------------------------------------------------------------------
// Original Author of file: Jason Judge.
// Based on template by Jim MacDonald.
// Current Maintainer of file: Klavs Klavsen <kl-feproc@vsen.dk>
// ----------------------------------------------------------------------

/**
 * This function is called internally by the core whenever the module is
 * loaded.  It adds in the database table information.
 */
function feproc_pntables()
{
    // Initialise table array
    $pntable = array();

    /////////////////////
    // Handlers table.

    // Get the name for the handler item table.
    $feprocTableName = pnConfigGetVar('prefix') . '_feproc_handlers';

    // Set the table name.
    $pntable['feproc_handlers'] = $feprocTableName;

    // Set the column names.
    $pntable['feproc_handlers_column']
      = array('id'          => $feprocTableName . '.fp_id',
              'name'        => $feprocTableName . '.fp_name',
              'description' => $feprocTableName . '.fp_description',
              'type'        => $feprocTableName . '.fp_type',
              'version'     => $feprocTableName . '.fp_version',
              'modulename'  => $feprocTableName . '.fp_modulename',
              'apiname'     => $feprocTableName . '.fp_apiname',
              'apifunc'     => $feprocTableName . '.fp_apifunc',
              'attributes'  => $feprocTableName . '.fp_attributes' );

    /////////////////////
    // Workflow table.

    // Get the name for the mail template item table.
    $feprocTableName = pnConfigGetVar('prefix') . '_feproc_workflow';

    // Set the table name.
    $pntable['feproc_workflow'] = $feprocTableName;

    // Set the column names.
    $pntable['feproc_workflow_column']
      = array('id'          => $feprocTableName . '.fp_id',
              'name'        => $feprocTableName . '.fp_name',
              'description' => $feprocTableName . '.fp_description',
              'type'        => $feprocTableName . '.fp_type',
              'handlerid'   => $feprocTableName . '.fp_handler_id',
              'secure'      => $feprocTableName . '.fp_secure',
              'attributes'  => $feprocTableName . '.fp_attributes',
              'setid'       => $feprocTableName . '.fp_set_id',
              'startstage'  => $feprocTableName . '.fp_start_stage',
              'successid'   => $feprocTableName . '.fp_success_id',
              'failureid'   => $feprocTableName . '.fp_failure_id' );

    // Return the table information

    return $pntable;
}

?>
