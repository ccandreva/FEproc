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
 * create a new template item
 * @param $args['name'] name of the template
 * @param $args['description'] description of the template
 * @param $args['template'] actual template
 * @returns int
 * @return template item ID on success, false on failure
 */
function feproc_handleradminapi_create($args)
{
    // Get arguments from argument array.
    extract($args);

    // Argument check - make sure that all required arguments are present,
    // if not then set an appropriate error message and return
    if (!isset($name) ||
        !isset($description) ||
        !isset($type) ||
        !isset($version) ||
        !isset($modulename) ||
        !isset($apiname) ||
        !isset($apifunc) ||
        !isset($attributes))
    {
        pnSessionSetVar('errormsg', _FXMODARGSERROR);
        return false;
    }

    // Early security check.
    if (!pnSecAuthAction(0, 'FEproc::', "::", ACCESS_ADD)) {
        pnSessionSetVar('errormsg', _FXNOAUTH);
        return false;
    }

    $sattributes = serialize($attributes);

    // Get datbase setup.
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $handlertable = $pntable['feproc_handlers'];
    $handlercolumn = &$pntable['feproc_handlers_column'];

    // Get next ID in table - this is required prior to any insert that
    // uses a unique ID, and ensures that the ID generation is carried
    // out in a database-portable fashion
    $nextId = $dbconn->GenId($handlertable);

    // Add item.
    $sql = "INSERT INTO $handlertable (
              $handlercolumn[id],
              $handlercolumn[name],
              $handlercolumn[description],
              $handlercolumn[type],
              $handlercolumn[version],
              $handlercolumn[modulename],
              $handlercolumn[apiname],
              $handlercolumn[apifunc],
              $handlercolumn[attributes])
            VALUES (
              $nextId,
              '" . pnVarPrepForStore($name) . "',
              '" . pnVarPrepForStore($description) . "',
              '" . pnVarPrepForStore($type) . "',
              '" . pnVarPrepForStore($version) . "',
              '" . pnVarPrepForStore($modulename) . "',
              '" . pnVarPrepForStore($apiname) . "',
              '" . pnVarPrepForStore($apifunc) . "',
              '" . pnVarPrepForStore($sattributes) . "')";
    $dbconn->Execute($sql);

    // Check for an error with the database code, and if so set an
    // appropriate error message and return
    if ($dbconn->ErrorNo() != 0) {
        pnSessionSetVar('errormsg', _FXCREATEFAILED . ' ' . $sql);
        return false;
    }

    // Get the ID of the item that we inserted.  It is possible, although
    // very unlikely, that this is different from $nextId as obtained
    // above, but it is better to be safe than sorry in this situation
    $hid = $dbconn->PO_Insert_ID($handlertable, $handlercolumn['id']);

    // Let any hooks know that we have created a new item.
    // TODO: this is not a standard item
    pnModCallHooks('item', 'create', $hid, 'hid');

    // Return the id of the newly created item to the calling process
    return $hid;
}

/**
 * delete a feproc handler item
 * @param $args['hid'] ID of the item
 * @returns bool
 * @return true on success, false on failure
 * TODO: don't allow a delete if the handler is being used by any workflow set stages.
 */
function feproc_handleradminapi_delete($args)
{
    // Get arguments from argument array.
    extract($args);

    // Argument check - make sure that all required arguments are present,
    // if not then set an appropriate error message and return.
    if (!isset($hid)) {
        pnSessionSetVar('errormsg', _FXMODARGSERROR);
        return false;
    }

    // Early security check.
    if (!pnSecAuthAction(0, 'FEproc::', "$hid::", ACCESS_DELETE)) {
        pnSessionSetVar('errormsg', _FXNOAUTH);
        return false;
    }

    if (!pnModAPILoad('feproc', 'handleruser')) {
        pnSessionSetVar('errormsg', _FXMODLOADFAILED);
        pnRedirect(pnModURL('feproc', 'admin', 'view'));
        return true;
    }

    // The API function is called.  This takes the item ID which
    // we obtained from the input and gets us the information on the
    // appropriate item.  If the item does not exist we post an appropriate
    // message and return
    $item = pnModAPIFunc('feproc',
            'handleruser',
            'gethandler',
            array('hid' => $hid));

    if ($item == false) {
        pnSessionSetVar('errormsg', _FXNOSUCHTEMPLATE);
        return false;
    }

    // Get datbase setup.
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $handlertable = $pntable['feproc_handlers'];
    $handlercolumn = &$pntable['feproc_handlers_column'];

    // Delete the item - the formatting here is not mandatory, but it does
    // make the SQL statement relatively easy to read.  Also, separating
    // out the sql statement from the Execute() command allows for simpler
    // debug operation if it is ever needed
    $sql = "DELETE FROM $handlertable
            WHERE $handlercolumn[id] = " . pnVarPrepForStore($hid);
    $dbconn->Execute($sql);

    // Check for an error with the database code, and if so set an
    // appropriate error message and return
    if ($dbconn->ErrorNo() != 0) {
        pnSessionSetVar('errormsg', _FXDELETEFAILED);
        return false;
    }

    // Let any hooks know that we have deleted an item.  As this is a
    // delete hook we're not passing any extra info
    // TODO: distinguish between different types of item.
    pnModCallHooks('item', 'delete', $hid, 'hid');

    // Let the calling process know that we have finished successfully
    return true;
}

/**
 * update a template item
 * @param $args['hid'] the ID of the item
 * @param $args['name'] the new name of the item
 * @param $args['description'] the new description of the item
 * @param $args['...'] the actual template
 * TODO: complete parameter list
 */
function feproc_handleradminapi_update($args)
{
    // Get arguments from argument array.
    extract($args);

    // Argument check - make sure that all required arguments are present,
    // if not then set an appropriate error message and return
    if (!isset($hid) ||
        !isset($name) ||
        !isset($description) ||
        !isset($type) ||
        !isset($version) ||
        !isset($modulename) ||
        !isset($apiname) ||
        !isset($apifunc) ||
        !isset($attributes))
    {
        pnSessionSetVar('errormsg', _FXMODARGSERROR);
        return false;
    }

    if (!pnModAPILoad('feproc', 'handleruser'))
    {
      pnSessionSetVar('errormsg', _FXMODLOADFAILED);
      return false;
    }

    // The API function is called.  This takes the item ID which
    // we obtained from the input and gets us the information on the
    // appropriate item.  If the item does not exist we post an appropriate
    // message and return
    $item = pnModAPIFunc('feproc',
            'handleruser',
            'gethandler',
            array('hid' => $hid));

    if ($item == false)
    {
        pnSessionSetVar('errormsg', _FXNOSUCHTEMPLATE);
        return false;
    }

    // Early security check.

    if (!pnSecAuthAction(0, 'FEproc::', "$hid::", ACCESS_EDIT))
    {
        pnSessionSetVar('errormsg', _FXNOAUTH);
        return false;
    }

    // The attributes will be an array.
    $sattributes = serialize($attributes);
    
    // Get datbase setup.
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $handlertable = $pntable['feproc_handlers'];
    $handlercolumn = &$pntable['feproc_handlers_column'];

    // Update the item.
    $sql = "UPDATE $handlertable
            SET $handlercolumn[name] = '" . pnVarPrepForStore($name) . "',
                $handlercolumn[description] = '" . pnVarPrepForStore($description) . "',
                $handlercolumn[type] = '" . pnVarPrepForStore($type) . "',
                $handlercolumn[version] = '" . pnVarPrepForStore($version) . "',
                $handlercolumn[modulename] = '" . pnVarPrepForStore($modulename) . "',
                $handlercolumn[apiname] = '" . pnVarPrepForStore($apiname) . "',
                $handlercolumn[apifunc] = '" . pnVarPrepForStore($apifunc) . "',
                $handlercolumn[attributes] = '" . pnVarPrepForStore($sattributes) . "'
            WHERE $handlercolumn[id] = " . pnVarPrepForStore($hid);

    $dbconn->Execute($sql);

    // Check for an error with the database code, and if so set an
    // appropriate error message and return
    if ($dbconn->ErrorNo() != 0) {
        pnSessionSetVar('errormsg', _FXUPDATEFAILED . ' ' . $sql);
        return false;
    }

    // Let the calling process know that we have finished successfully
    return true;
}


/**
 * Get a list of APIs for a module.
 * TODO: the module may not have any APIs - handle that.
 */
function feproc_handleradminapi_moduleapilist($args)
{
    extract($args);

    $moduleinfo = pnModGetInfo(pnModGetIDFromName($modulename));
    $directory = $moduleinfo['directory'];
    $osfile = "modules/$directory/pn*api.php";

    $apilist = Array();

    $dh = opendir("modules/$directory");
    while ($file = readdir($dh))
    {
        // TODO: are there any rules on the characters in a name? Letter case?
        if (ereg('^pn(.+)api.php$', $file, $regs))
        {
            $apilist[] = Array('modulename' => $modulename, 'apiname' => $regs[1]);
        }
    }

    if (empty($apilist))
    {
        $apilist = false;
    }

    return $apilist;
}


/**
 * Get a list of fetix handlers for a module.
 * $modulename - name of module.
 */
function feproc_handleradminapi_modulehandlers($args)
{
    extract($args);

    if (!pnModAPILoad('feproc', 'handleradmin')) {
        pnSessionSetVar('errormsg', _FXMODLOADFAILED);
        pnRedirect(pnModURL('feproc', 'admin', 'view'));
        return true;
    }

    // Get the list of APIs for the module.
    // TODO: handle there being no APIs in the module.
    $apilist = pnModAPIFunc('feproc',
            'handleradmin',
            'moduleapilist',
            array('modulename' => $modulename));

    if (!$apilist)
    {
        return false;
    }

    $handlerlist = Array();
    
    // Loop for each API and hunt for handlers.
    foreach($apilist as $api)
    {
        $apimodule = $api['modulename'];
        $apiname = $api['apiname'];

        if (pnModAPILoad($apimodule, $apiname))
        {
            // The API has loaded okay.
            // Load a list of fetix handlers, if any.
            if ($handlerindex = pnModAPIFunc($apimodule, $apiname, 'feprochandlerindex'))
            {
                // There is a feproc handler here. Grab the details.
                // Store the info details in the result array.

                // Now get the details for each handler in turn.

                foreach($handlerindex as $handler)
                {
                    $info = Array();

                    // Load the API handler function and get its info.
                    if ($info = pnModAPIFunc($apimodule, $apiname, $handler['apifunc'], Array('action' => 'info')))
                    {
                        // Add a few more fields.
                        $info['module'] = $apimodule;
                        $info['apiname'] = $apiname;
                        $info['apifunc'] = $handler['apifunc'];
                        $info['source'] = $info['type'] .':'. $apimodule .':'. $apiname .':'. $handler['apifunc'];

                        // Store the handler in the array.
                        $handlerlist[] = $info;
                    }
                }
            }
        }
    }
    
    if (empty($handlerlist))
    {
        $handlerlist = false;
    }

    return $handlerlist;
}

?>
