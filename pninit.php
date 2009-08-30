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

    $feprocTable  = $pntable['feproc_handlers'];
    $feprocColumn = &$pntable['feproc_handlers_column'];
    $feprocIDcolumn = preg_replace("/^$feprocTable\./", '', $feprocColumn[id]);

    // Create the table.
    $sql = "create table $feprocTable (
            $feprocColumn[id] int(10) not null auto_increment,
            $feprocColumn[name] varchar(64) not null default '',
            $feprocColumn[description] text,
            $feprocColumn[type] varchar(30),
            $feprocColumn[version] varchar(30),
            $feprocColumn[modulename] varchar(60),
            $feprocColumn[apiname] varchar(60),
            $feprocColumn[apifunc] varchar(60),
            $feprocColumn[attributes] text,
            primary key($feprocIDcolumn) )";
    $dbconn->Execute($sql);

    // Check for an error with the database code, and if so set an
    // appropriate error message and return
    if ($dbconn->ErrorNo() != 0)
    {
        pnSessionSetVar('errormsg', _FXCREATETABLEFAILED . " " . $sql);
        return false;
    }

    /////////////////////////
    // Sets and stages table.

    $feprocTable  = $pntable['feproc_workflow'];
    $feprocColumn = &$pntable['feproc_workflow_column'];
    $feprocIDcolumn = preg_replace("/^$feprocTable\./", '', $feprocColumn[id]);

    // Create the table.
    $sql = "create table $feprocTable (
            $feprocColumn[id] int(10) not null auto_increment,
            $feprocColumn[name] varchar(64) not null default '',
            $feprocColumn[description] text,
            $feprocColumn[type] varchar(30),
            $feprocColumn[attributes] text,
            $feprocColumn[setid] int(10) not null default 0,
            $feprocColumn[successid] int(10),
            $feprocColumn[failureid] int(10),
            $feprocColumn[handlerid] int(10),
            $feprocColumn[secure] tinyint(4) not null default 0,
            $feprocColumn[startstage] tinyint(4) not null default 0,
            primary key($feprocIDcolumn) )";
    $dbconn->Execute($sql);

    // Check for an error with the database code, and if so set an
    // appropriate error message and return
    if ($dbconn->ErrorNo() != 0)
    {
        pnSessionSetVar('errormsg', _FXCREATETABLEFAILED . " " . $sql);
        return false;
    }

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
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $feprocTableWF  = $pntable['feproc_workflow'];
    $feprocColumnWF = &$pntable['feproc_workflow_column'];

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
            break;
    }

    // For upgrade to 0.3
    switch($dataversion) {
        case "0.2":
            // Add the new 'starting stage indicator' to the workflow table
            // to support multiple starting stages.
            $sql = "alter   table $feprocTableWF 
                    add     $feprocColumnWF[startstage] tinyint(4) not null default 0";
            $dbconn->Execute($sql);

            // Check for an error with the database code, and if so set an
            // appropriate error message and return
            if ($dbconn->ErrorNo() != 0)
            {
                pnSessionSetVar('errormsg', _FXCREATETABLEFAILED . " " . $sql);
                return false;
            }

            // Get a list of starting stages.
            $sql = "select  $feprocColumnWF[successid]
                    from    $feprocTableWF 
                    where   $feprocColumnWF[type] = 'set'
                    and     $feprocColumnWF[successid] > 0";
            $result = $dbconn->Execute($sql);

            $startingstages = array();
            for (; !$result->EOF; $result->MoveNext())
            {
                $startingstages[] = $result->fields[0];
            }

            // If there are starting stages, then update their flags.
            if (count($startingstages) > 0)
            {
                // Convert into CSV string.
                $startingstages = implode(',', $startingstages);

                // Set the starting stage flag for the current starting stages.
                $sql = "update  $feprocTableWF 
                        set     $feprocColumnWF[startstage] = 2
                        where   $feprocColumnWF[id] in ($startingstages)";
                $dbconn->Execute($sql);
            }

            break;
    }

    // For upgrade to 0.4
    switch($dataversion) {
        case "0.3":
            break;
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
    // Get datbase setup.
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    // Drop the table.
    $sql = "DROP TABLE $pntable[feproc_handlers]";
    $dbconn->Execute($sql);

    // Check for an error with the database code, and if so set an
    // appropriate error message and return
    if ($dbconn->ErrorNo() != 0)
    {
        // Report failed deletion attempt
        return false;
    }

    $sql = "DROP TABLE $pntable[feproc_workflow]";
    $dbconn->Execute($sql);

    // Check for an error with the database code, and if so set an
    // appropriate error message and return
    if ($dbconn->ErrorNo() != 0)
    {
        // Report failed deletion attempt
        return false;
    }

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
