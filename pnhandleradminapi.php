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
 * create a new template item
 * @param $args['name'] name of the template
 * @param $args['description'] description of the template
 * @param $args['template'] actual template
 * @returns int
 * @return template item ID on success, false on failure
 */
function feproc_handleradminapi_create($args)
{
    // Early security check.
   if (!SecurityUtil::checkPermission ('FEproc::', '::', ACCESS_ADD)) {
        return LogUtil::registerPermissionError();
    }

    // Until all calls are refactored, '$args' is the object itself
    $obj = &$args;
    $obj['attributes'] = serialize($obj['attributes']);
    DBUtil::insertObject($obj, 'feproc_handlers');

    // Get the ID of the item that we inserted.
    $hid = $obj['id'];

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
    // Early security check.
   if (!SecurityUtil::checkPermission ('FEproc::', '::', ACCESS_DELETE)) {
        return LogUtil::registerPermissionError();
    }
    // Get arguments from argument array.
    extract($args);

    if (!isset($hid)) {
        return LogUtil::registerError( __('Missing handler ID'), 500);
    }

    deleteObjectByID ('feproc_handlers', $hid);

    // Let any hooks know that we have deleted a handerl
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
    // Early security check.
   if (!SecurityUtil::checkPermission ('FEproc::', '::', ACCESS_EDIT)) {
        return LogUtil::registerPermissionError();
    }

    // Until all calls are refactored, '$args' is the object itself
    $obj = &$args;
    $obj['attributes'] = serialize($obj['attributes']);
    $obj['id'] = $obj['hid'];
    DBUtil::updateObject($obj, 'feproc_handlers');

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

    // Get the list of APIs for the module.
    // TODO: handle there being no APIs in the module.
    $apilist = pnModAPIFunc('feproc', 'handleradmin', 'moduleapilist',
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
