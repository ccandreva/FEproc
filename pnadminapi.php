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


/*******************
 * SETS
 *******************/

/**
 * create a new set item TODO
 * @param $args['name'] name of the template
 * @param $args['description'] description of the template
 * @returns int
 * @return set item ID on success, false on failure
 */
function feproc_adminapi_createset($args)
{
    // Early security check.
   if (!SecurityUtil::checkPermission ('FEproc::', '::', ACCESS_ADD)) {
        return LogUtil::registerPermissionError();
    }

    // Get arguments from argument array.
    extract($args);

    // Argument check.
    if (!isset($name) || !isset($description)) {
        pnSessionSetVar('errormsg', _FXMODARGSERROR);
        return false;
    }

    $obj = array ('name' => $name, 'description' => $description, 'type' => 'set');
    DBUtil::insertObject($obj, 'feproc_workflow');
    $setid = $obj['setid'];

    // We don't have the object ID until after it is created,
    // so we need to update the setid with the object id.
    $obj['setid'] = $setid;
    DBUtil::updateObject($obj, 'feproc_workflow');
    
    // Let any hooks know that we have created a new set.
    pnModCallHooks('item', 'createset', $setid, 'setid');

    return $setid;
}


/**
 * update a set item
 * @param $args['hid'] the ID of the item
 * @param $args['name'] the new name of the item
 * @param $args['description'] the new description of the item
 * @param $args['...'] the actual template
 * TODO: complete parameter list
 */
function feproc_adminapi_updateset($args)
{
    // Early security check.
   if (!SecurityUtil::checkPermission ('FEproc::', '::', ACCESS_EDIT)) {
        return LogUtil::registerPermissionError();
    }

    // Get arguments from argument array.
    extract($args);

    // Argument check.
    // TODO: success and failure stage IDs
    if (!isset($setid) || !isset($name) || !isset($description)) {
        pnSessionSetVar('errormsg', _FXMODARGSERROR);
        return false;
    }

    // The API function is called.
    $item = pnModAPIFunc('feproc', 'user', 'getset',
            array('setid' => $setid)
    );

    if ($item == false) {
        pnSessionSetVar('errormsg', 'No such set'); // TODO: what next?
        return false;
    }

    $wftable = $pntable['feproc_workflow'];
    $wfcolumn = &$pntable['feproc_workflow_column'];

    // Update the item
    $obj = array('id' => $setid, 'name' => $name, 'description' => $description,
            'successid' => $startstageid);
    DBUtil::updateObject($obj, 'feproc_workflow');

    // Now make sure the start stages are set if appropriate.
    if ($startstageid > 0)
    {
        // Set the start stage.
        $sql = "UPDATE  $wftable
                SET     $wfcolumn[startstage] = 2
                WHERE   $wfcolumn[setid] = " . pnVarPrepForStore($setid) . "
                AND     $wfcolumn[id] = " . pnVarPrepForStore($startstageid) . "
                AND     $wfcolumn[startstage] <> 2";
        $result = DBUtil::executeSQL($sql);

        // Reset the non-default start stages.
        $sql = "UPDATE  $wftable
                SET     $wfcolumn[startstage] = 1
                WHERE   $wfcolumn[setid] = " . pnVarPrepForStore($setid) . "
                AND     $wfcolumn[id] <> " . pnVarPrepForStore($setid) . "
                AND     $wfcolumn[id] <> " . pnVarPrepForStore($startstageid) . "
                AND     $wfcolumn[startstage] = 2";
        $result = DBUtil::executeSQL($sql);
    }

    // Let the calling process know that we have finished successfully
    return true;
}

/**
 * delete a feproc handler item
 * @param $args['hid'] ID of the item
 * @returns bool
 * @return true on success, false on failure
 * TODO: don't allow a delete if the handler is being used by any workflow set stages.
 */
function feproc_adminapi_deleteset($args)
{
    // Get arguments from argument array.
    extract($args);

    // Early security check.
   if (!SecurityUtil::checkPermission ('FEproc::', '::', ACCESS_DELETE)) {
        return LogUtil::registerPermissionError();
    }

    // Argument check.
    if (!isset($setid)) {
        pnSessionSetVar('errormsg', _FXMODARGSERROR);
        return false;
    }

    // Check if there are any stages that have not been deleted yet.
    $stages = pnModAPIFunc('feproc', 'user', 'getallstages',
            array('setid' => $setid));

    if (is_array($stages))
    {
        pnSessionSetVar('errormsg', __("You must delete all stages before the set can be deleted.") );
        pnRedirect(pnModURL('feproc', 'admin', 'viewsets', array('setid' => $setid)));
        return true;
    }

    // Get datbase setup.
    DBUtil::deleteObjectByID('feproc_workflow', $setid);

    // Let any hooks know that we have deleted an item.
    // TODO: distinguish between different types of item.
    pnModCallHooks('item', 'deleteset', $setid, 'setid');

    // Let the calling process know that we have finished successfully
    return true;
}



/*******************
 * STAGES
 *******************/

/**
 * create a new stage item
 * @param $args['name'] name of the template
 * @param $args['description'] description of the template
 * TODO...
 * @returns int
 * @return stage item ID on success, false on failure
 */
function feproc_adminapi_createstage($args)
{
    // Early security check.
   if (!SecurityUtil::checkPermission ('FEproc::', '::', ACCESS_ADD)) {
        return LogUtil::registerPermissionError();
    }
    // Get arguments from argument array.
    extract($args);

    // Argument check.
    if (!isset($name) ||
        !isset($description) ||
        !isset($handlertype) ||
        !isset($setid) ||
        !isset($hid))
    {
        pnSessionSetVar('errormsg', _FXMODARGSERROR);
        return false;
    }

    if (!isset($secure))
    {
        $secure = 0;
    }

    if (!isset($startstage))
    {
        $startstage = 0;
    }

    if (!is_numeric($startstage) || $startstage < 0 || $startstage > 2)
    {
        $startstage = 0;
    }

    // TODO: create default attributes for the stage.
    // The attributes come from the handler details.
    // Get the handler.
    $sattributes = false;
    if ($handlertype <> 'formexpress' && $handlertype <> 'form')
    {
        if ($handler = pnModAPIFunc(
            'feproc', 'handleruser', 'gethandler',
            array('hid' => $hid)))
        {
            if (is_array($handler['attributes']))
            {
                $sattributes = array();
                foreach ($handler['attributes'] as $key => $attribute)
                {
                    $sattributes[$key] = $attribute['default'];
                }
            }
        }
    }
    $sattributes = serialize($sattributes);

    // Add item.
    $obj = array(
              'name' => $name,
              'description' => $description,
              'type' => $handlertype,
              'setid' => $setid,
              'handlerid' => $hid,
              'secure' => $secure,
              'startstage' => $startstage,
              'attributes' => $sattributes,
    );
    $result = DBUtil::insertObject($obj, 'feproc_workflow');

    // Check for an error with the database code, and if so set an
    // appropriate error message and return
    if (!$result) {
        pnSessionSetVar('errormsg', _FXCREATEFAILED . ' ' . $sql);
        return false;
    }

    // Get the ID of the item that we inserted.
    $stageid = $obj['id'];

    // Let any hooks know that we have created a new item.
    // TODO: this is not a standard item
    pnModCallHooks('item', 'createstage', $stageid, 'stageid');

    // Return the id of the newly created item to the calling process
    return $stageid;
}

/**
 * update a template item
 * @param $args['hid'] the ID of the item
 * @param $args['name'] the new name of the item
 * @param $args['description'] the new description of the item
 * @param $args['...'] the actual template
 * TODO: complete parameter list
 */
function feproc_adminapi_updatestage($args)
{

    // For now we'll use a pointer, use the object as passed in.
    $obj = &$args;

    // Argument check.
    if (!isset($obj['id']) ||
        !isset($obj['name']) ||
        !isset($obj['description']) )
    {
        pnSessionSetVar('errormsg', _FXMODARGSERROR);
        return false;
    }

    $startstage = &$obj['startstage'];
    $stageid = &$obj['id'];
    
    // Early security check.
   if (!SecurityUtil::checkPermission ('FEproc::', "$stageid::", ACCESS_EDIT)) {
        return LogUtil::registerPermissionError();
    }

    if (!is_numeric($startstage) || $startstage < 0 || $startstage > 2)
    {
        $startstage = 0;
    }

    // The API function is called.
    $item = pnModAPIFunc('feproc', 'user', 'getstage', array('stageid' => $stageid) );

    if ($item == false)
    {
        pnSessionSetVar('errormsg', __('No such stage'));
        return false;
    }

    // The attributes will be an array.
    if ( isset($obj['attributes']) && is_array($obj['attributes']))
    {
        $sattributes = serialize($obj['attributes']);
    } else {
        $sattributes = serialize(false);
    }
    $obj['attributes'] = $sattributes;
    $result = DBUtil::updateObject($obj, 'feproc_workflow');
    
    // Let the calling process know that we have finished successfully
    return $result;
}

/**
 * delete a feproc handler item
 * @param $args['hid'] ID of the item
 * @returns bool
 * @return true on success, false on failure
 * TODO: don't allow a delete if the handler is being used by any workflow set stages.
 */
function feproc_adminapi_deletestage($args)
{
    // Get arguments from argument array.
    extract($args);

    // Argument check.
    if (!isset($stageid) || !isset($setid)) {
        pnSessionSetVar('errormsg', _FXMODARGSERROR);
        return false;
    }

    // Early security check.
   if (!SecurityUtil::checkPermission ('FEproc::', "$stageid::", ACCESS_DELETE)) {
        return LogUtil::registerPermissionError();
    }

    $item = pnModAPIFunc('feproc', 'user', 'getstage',
            array('stageid' => $stageid));

    if ($item == false) {
        pnSessionSetVar('errormsg', "Stage $stageid does not exist");
        return false;
    }

    DBUtil::deleteObjectById('feproc_workflow', $stageid);

    $table = pnDBGetTables();
    $wfcolumn = $table['feproc_workflow_column'];

    // Update any other stages that point to the deleted item (so there are no
    // dead-ends).
    DBUtil::UpdateObject( array('successid' => 0),'feproc_workflow',
                $wfcolumn[successid] . ' = ' . $stageid);
    DBUtil::UpdateObject( array('failureid' => 0),'feproc_workflow',
                $wfcolumn[failureid] . ' = ' . $stageid);
    

    // Let any hooks know that we have deleted an item.
    // TODO: distinguish between different types of item.
    pnModCallHooks('item', 'deletestage', $stageid, 'stageid');

    // Let the calling process know that we have finished successfully
    return true;
}




?>
