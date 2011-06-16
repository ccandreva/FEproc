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
 * initialise the module
 * This function is only ever called once during the lifetime of a particular
 * module instance
 */
function feproc_init()
{
    // Get datbase setup.
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    /////////////////////
    // Handlers table.
    if ( !DBUtil::createTable('feproc_handlers')) return false;
    if ( !DBUtil::createTable('feproc_workflow')) return false;

    // There are no module variables at this stage. There is ample scope to
    // make much of the behaviour of this module configurable.
    pnModSetVar('FEproc', 'itemsperpage', 30);
    pnModSetVar('FEproc', 'removeunmatched', 0);
    pnModSetVar('FEproc', 'sessiontimeout', 0);
    pnModSetVar('FEproc', 'shareformitems', 1);
    pnModSetVar('FEproc', 'tracestack', 0);

    pnModSetVar('FEproc', 'attrstringlen', 200);
    pnModSetVar('FEproc', 'attrstringsize', 64);
    
    pnModSetVar('FEproc', 'attrtextrows', 6);
    pnModSetVar('FEproc', 'attrtextcols', 40);
    //pnModSetVar('FEproc', 'attrtextsize', 0);

	// Initialisation successful
    return true;
}


/**
 * upgrade the module from an old version
 * This function can be called multiple times
 */
function feproc_upgrade($oldversion)
{
    // Version number is A.B.C[.D] where:-
    // A - major version (significant functionality changes.
    // B - minor version (data or database changes)
    // C - bugfix version (changes to scripts only - database compatible)
    // D - [optional] very minor maintenance release.
    // Only the major and minor versions are checked for an upgrade.

    $dataversion = preg_replace('/^([0-9]+\.[0-9]+)\..*/', '$1', $oldversion);

    // Get datbase setup.
    $pntable = pnDBGetTables();

    // Upgrade dependent on old version number
    switch($dataversion) {
        case "0.1":
            // Create new module variables.
            pnModSetVar('FEproc', 'removeunmatched', 0);
            pnModSetVar('FEproc', 'sessiontimeout', 0);
            pnModSetVar('FEproc', 'shareformitems', 1);
            pnModSetVar('FEproc', 'tracestack', 0);

            pnModSetVar('FEproc', 'attrstringlen', 200);
            pnModSetVar('FEproc', 'attrstringsize', 64);

            pnModSetVar('FEproc', 'attrtextrows', 6);
            pnModSetVar('FEproc', 'attrtextcols', 40);

        // For upgrade to 0.3
        case "0.2":
            // Add the new 'starting stage indicator' to the workflow table
            // to support multiple starting stages.
            DBUtil::changeTable('feproc_workflow');

            // Get a list of starting stages.
            $startingstages = DBUtil::selectFieldArray('feproc_workflow', 'successid', 
                    "WHERE type='set' AND successid > 0'");

            // If there are starting stages, then update their flags.
            if (count($startingstages) > 0)
            {
                $obj = array();
                foreach ($startingstages as $stage) {
                        $obj[] = array('id' => $stage, 'startstage' => 2);
                }
                DBUTil::updateObjectArray($obj, 'feproc_workflow'); 
            }

            // For upgrade to 0.4
        // case "0.3":
    }

    // Update successful
    return true;
}

/**
 * delete the module
 * This function is only ever called once during the lifetime of a particular
 * module instance.
 */
function feproc_delete()
{
    // Drop the tables.
    DBUtil::dropTable('feproc_handlers');
    DBUtil::dropTable('feproc_workflow');
    
    // Delete any module variables
    pnModDelVar('FEproc', 'itemsperpage');
    pnModDelVar('FEproc', 'removeunmatched');
    pnModDelVar('FEproc', 'sessiontimeout');
    pnModDelVar('FEproc', 'shareformitems');
    pnModDelVar('FEproc', 'tracestack');

    pnModDelVar('FEproc', 'attrstringlen');
    pnModDelVar('FEproc', 'attrstringsize');
    
    pnModDelVar('FEproc', 'attrtextrows');
    pnModDelVar('FEproc', 'attrtextcols');

    // Deletion successful
    return true;
}

?>
