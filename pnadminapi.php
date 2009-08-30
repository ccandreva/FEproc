<?php
// ----------------------------------------------------------------------
// FEproc - Mail template backend module for FormExpress for
// POST-NUKE Content Management System
// Copyright (C) 2003 by Jason Judge
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
    // Get arguments from argument array.
    extract($args);

    // Argument check.
    if (!isset($name) ||
        !isset($description)
        )
    {
        pnSessionSetVar('errormsg', _FXMODARGSERROR);
        return false;
    }

    // Early security check.
    if (!pnSecAuthAction(0, 'FEproc::', "::", ACCESS_ADD)) {
        pnSessionSetVar('errormsg', _FXNOAUTH);
        return false;
    }

    // Load handler user API.
    if (!pnModAPILoad('feproc', 'handleruser'))
    {
        $output->Text(_FXMODLOADFAILED . ' feproc:handleruser');
        return $output->GetOutput();
    }

    // Get database setup.
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $wftable = $pntable['feproc_workflow'];
    $wfcolumn = &$pntable['feproc_workflow_column'];

    // Get next ID in table.
    $sql = "SELECT MAX($wfcolumn[id]) + 1 FROM $wftable";
    $result = $dbconn->Execute($sql);
    if ($dbconn->ErrorNo() != 0) {
        pnSessionSetVar('errormsg', _FXCREATEFAILED . ' ' . $sql);
        return false;
    }

    list($nextId) = $result->fields;
    
    if (empty($nextId))
    {
        $nextId = 1;
    }

    // Does not appear to work.
    //$nextId = $dbconn->GenId($wftable);

    // Add item.
    $sql = "INSERT INTO $wftable (
              $wfcolumn[id],
              $wfcolumn[name],
              $wfcolumn[description],
              $wfcolumn[type],
              $wfcolumn[setid],
              $wfcolumn[handlerid],
              $wfcolumn[failureid],
              $wfcolumn[successid],
              $wfcolumn[secure],
              $wfcolumn[attributes])
            VALUES (
              $nextId,
              '" . pnVarPrepForStore($name) . "',
              '" . pnVarPrepForStore($description) . "',
              'set',
              $nextId,
              0,
              0,
              0,
              0,
              NULL)";

    $dbconn->Execute($sql);

    // Check for an error with the database code, and if so set an
    // appropriate error message and return
    if ($dbconn->ErrorNo() != 0) {
        pnSessionSetVar('errormsg', _FXCREATEFAILED . ' ' . $sql);
        return false;
    }

    // Get the ID of the item that we inserted.
    $setid = $dbconn->PO_Insert_ID($wftable, $wfcolumn['id']);

    // Let any hooks know that we have created a new item.  As this is a
    // create hook we're passing 'id' as the extra info, which is the
    // argument that all of the other functions use to reference this
    // item
    // TODO: this is not a standard item
    pnModCallHooks('item', 'createset', $setid, 'setid');

    // Return the id of the newly created item to the calling process
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
    // Get arguments from argument array.
    extract($args);

    // Argument check.
    // TODO: success and failure stage IDs
    if (!isset($setid) ||
        !isset($name) ||
        !isset($description)/* ||
        !isset($attributes)*/)
    {
        pnSessionSetVar('errormsg', _FXMODARGSERROR);
        return false;
    }

    if (!pnModAPILoad('feproc', 'user'))
    {
      pnSessionSetVar('errormsg', _FXMODLOADFAILED);
      return false;
    }

    // The API function is called.
    $item = pnModAPIFunc('feproc', 'user', 'getset',
            array('setid' => $setid)
    );

    if ($item == false)
    {
        pnSessionSetVar('errormsg', 'No such set'); // TODO: what next?
        return false;
    }

    // Early security check.

    if (!pnSecAuthAction(0, 'FEproc::', "$hid::", ACCESS_EDIT)) // TODO
    {
        pnSessionSetVar('errormsg', _FXNOAUTH);
        return false;
    }

    // Get database setup.
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $wftable = $pntable['feproc_workflow'];
    $wfcolumn = &$pntable['feproc_workflow_column'];

    // Update the item.
    $sql = "UPDATE $wftable
            SET $wfcolumn[name] = '" . pnVarPrepForStore($name) . "',
                $wfcolumn[description] = '" . pnVarPrepForStore($description) . "',
                $wfcolumn[successid] = '" . pnVarPrepForStore($startstageid) . "'
            WHERE $wfcolumn[id] = " . pnVarPrepForStore($setid);

    $dbconn->Execute($sql);

    // Check for an error with the database code, and if so set an
    // appropriate error message and return
    if ($dbconn->ErrorNo() != 0) {
        pnSessionSetVar('errormsg', _FXUPDATEFAILED . ' ' . $sql);
        return false;
    }

    // Now make sure the start stages are set if appropriate.
    if ($startstageid > 0)
    {
        // Set the start stage.
        $sql = "UPDATE  $wftable
                SET     $wfcolumn[startstage] = 2
                WHERE   $wfcolumn[setid] = " . pnVarPrepForStore($setid) . "
                AND     $wfcolumn[id] = " . pnVarPrepForStore($startstageid) . "
                AND     $wfcolumn[startstage] <> 2";
        $dbconn->Execute($sql);

        // Check for an error with the database code, and if so set an
        // appropriate error message and return
        if ($dbconn->ErrorNo() != 0) {
            pnSessionSetVar('errormsg', _FXUPDATEFAILED . ' ' . $sql);
            return false;
        }

        // Reset the non-default start stages.
        $sql = "UPDATE  $wftable
                SET     $wfcolumn[startstage] = 1
                WHERE   $wfcolumn[setid] = " . pnVarPrepForStore($setid) . "
                AND     $wfcolumn[id] <> " . pnVarPrepForStore($setid) . "
                AND     $wfcolumn[id] <> " . pnVarPrepForStore($startstageid) . "
                AND     $wfcolumn[startstage] = 2";
        $dbconn->Execute($sql);

            // Check for an error with the database code, and if so set an
        // appropriate error message and return
        if ($dbconn->ErrorNo() != 0) {
            pnSessionSetVar('errormsg', _FXUPDATEFAILED . ' ' . $sql);
            return false;
        }
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

    // Argument check.
    if (!isset($setid)) {
        pnSessionSetVar('errormsg', _FXMODARGSERROR);
        return false;
    }

    // Early security check.
    if (!pnSecAuthAction(0, 'FEproc::', "$hid::", ACCESS_DELETE)) {
        pnSessionSetVar('errormsg', _FXNOAUTH);
        return false;
    }

    if (!pnModAPILoad('feproc', 'admin')) {
        pnSessionSetVar('errormsg', _FXMODLOADFAILED);
        pnRedirect(pnModURL('feproc', 'admin', 'viewset', array('setid' => $setid)));
        return true;
    }

    if (!pnModAPILoad('feproc', 'user')) {
        pnSessionSetVar('errormsg', _FXMODLOADFAILED);
        pnRedirect(pnModURL('feproc', 'admin', 'viewset', array('setid' => $setid)));
        return true;
    }

    // Check if there are any stages that have not been deleted yet.
    $stages = pnModAPIFunc('feproc', 'user', 'getallstages',
            array('setid' => $setid));

    if (is_array($stages))
    {
        pnSessionSetVar('errormsg', "Must delete all stages before the set can be deleted.");
        pnRedirect(pnModURL('feproc', 'admin', 'viewset', array('setid' => $setid)));
        return true;
    }

    // The API function is called.
    $item = pnModAPIFunc('feproc', 'user', 'getset',
            array('setid' => $setid));

    if ($item == false) {
        pnSessionSetVar('errormsg', "Set $setid does not exist");
        return false;
    }

    // Get datbase setup.
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $wftable = $pntable['feproc_workflow'];
    $wfcolumn = &$pntable['feproc_workflow_column'];

    // Delete the item.
    $sql = "DELETE FROM $wftable
            WHERE $wfcolumn[id] = " . pnVarPrepForStore($setid);
    $dbconn->Execute($sql);

    // Check for an error with the database code, and if so set an
    // appropriate error message and return.
    if ($dbconn->ErrorNo() != 0) {
        pnSessionSetVar('errormsg', _FXDELETEFAILED);
        return false;
    }

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

    // Early security check.
    if (!pnSecAuthAction(0, 'FEproc::', "::", ACCESS_ADD)) {
        pnSessionSetVar('errormsg', _FXNOAUTH);
        return false;
    }

    // Load handler user API.
    if (!pnModAPILoad('feproc', 'handleruser'))
    {
        $output->Text(_FXMODLOADFAILED . ' feproc:handleruser');
        return $output->GetOutput();
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

    // Get datbase setup.
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $wftable = $pntable['feproc_workflow'];
    $wfcolumn = &$pntable['feproc_workflow_column'];

    // Get next ID in table.
    $sql = "SELECT MAX($wfcolumn[id]) + 1 FROM $wftable";
    $result = $dbconn->Execute($sql);
    if ($dbconn->ErrorNo() != 0) {
        pnSessionSetVar('errormsg', _FXCREATEFAILED . ' ' . $sql);
        return false;
    }

    list($nextId) = $result->fields;
    
    if (empty($nextId))
    {
        $nextId = 1;
    }

    // Does not appear to work.
    //$nextId = $dbconn->GenId($wftable);

    // Add item.
    $sql = "INSERT INTO $wftable (
              $wfcolumn[id],
              $wfcolumn[name],
              $wfcolumn[description],
              $wfcolumn[type],
              $wfcolumn[setid],
              $wfcolumn[handlerid],
              $wfcolumn[secure],
              $wfcolumn[startstage],
              $wfcolumn[attributes])
            VALUES (
              $nextId,
              '" . pnVarPrepForStore($name) . "',
              '" . pnVarPrepForStore($description) . "',
              '" . pnVarPrepForStore($handlertype) . "',
              '" . pnVarPrepForStore($setid) . "',
              '" . pnVarPrepForStore($hid) . "',
              '" . pnVarPrepForStore($secure) . "',
              '" . pnVarPrepForStore($startstage) . "',
              '" . pnVarPrepForStore($sattributes) . "')";
    $dbconn->Execute($sql);

    // Check for an error with the database code, and if so set an
    // appropriate error message and return
    if ($dbconn->ErrorNo() != 0) {
        pnSessionSetVar('errormsg', _FXCREATEFAILED . ' ' . $sql);
        return false;
    }

    // Get the ID of the item that we inserted.
    $stageid = $dbconn->PO_Insert_ID($wftable, $wfcolumn['id']);

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
    // Get arguments from argument array.
    extract($args);

    // Argument check.
    if (!isset($stageid) ||
        !isset($name) ||
        !isset($description)/* ||
        !isset($attributes)*/)
    {
        pnSessionSetVar('errormsg', _FXMODARGSERROR);
        return false;
    }

    if (!isset($secure))
    {
        $secure = 0;
    }

    if (!isset($successid))
    {
        $successid = 0;
    }

    if (!isset($failureid))
    {
        $failureid = 0;
    }

    if (!isset($startstage))
    {
        $startstage = 0;
    }

    if (!is_numeric($startstage) || $startstage < 0 || $startstage > 2)
    {
        $startstage = 0;
    }

    if (!pnModAPILoad('feproc', 'user'))
    {
      pnSessionSetVar('errormsg', _FXMODLOADFAILED);
      return false;
    }

    // The API function is called.
    $item = pnModAPIFunc('feproc', 'user', 'getstage',
            array('stageid' => $stageid)
    );

    if ($item == false)
    {
        pnSessionSetVar('errormsg', 'No such stage'); //TODO
        return false;
    }

    // Early security check.

    if (!pnSecAuthAction(0, 'FEproc::', "$hid::", ACCESS_EDIT)) // TODO
    {
        pnSessionSetVar('errormsg', _FXNOAUTH);
        return false;
    }

    // The attributes will be an array.
    if ($attributes == null || empty($attributes) || !isset($attributes))
    {
        $attributes = false;
    }
    $sattributes = serialize($attributes);
    
    // Get database setup.
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $wftable = $pntable['feproc_workflow'];
    $wfcolumn = &$pntable['feproc_workflow_column'];

    // Update the item.
    $sql = "UPDATE $wftable
            SET $wfcolumn[name] = '" . pnVarPrepForStore($name) . "',
                $wfcolumn[description] = '" . pnVarPrepForStore($description) . "',
                $wfcolumn[secure] = '" . pnVarPrepForStore($secure) . "',
                $wfcolumn[successid] = '" . pnVarPrepForStore($successid) . "',
                $wfcolumn[failureid] = '" . pnVarPrepForStore($failureid) . "',
                $wfcolumn[startstage] = '" . pnVarPrepForStore($startstage) . "',
                $wfcolumn[attributes] = '" . pnVarPrepForStore($sattributes) . "'
            WHERE $wfcolumn[id] = " . pnVarPrepForStore($stageid);

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

    // Security check.
    if (!pnSecAuthAction(0, 'FEproc::', "$hid::", ACCESS_DELETE)) {
        pnSessionSetVar('errormsg', _FXNOAUTH);
        return false;
    }

    if (!pnModAPILoad('feproc', 'admin')) {
        pnSessionSetVar('errormsg', _FXMODLOADFAILED);
        pnRedirect(pnModURL('feproc', 'admin', 'viewset', array('setid' => $setid)));
        return true;
    }

    if (!pnModAPILoad('feproc', 'user')) {
        pnSessionSetVar('errormsg', _FXMODLOADFAILED);
        pnRedirect(pnModURL('feproc', 'admin', 'viewset', array('setid' => $setid)));
        return true;
    }

    // The API function is called.
    $item = pnModAPIFunc('feproc', 'user', 'getstage',
            array('stageid' => $stageid));

    if ($item == false) {
        pnSessionSetVar('errormsg', "Stage $stageid does not exist");
        return false;
    }

    // Get database setup.
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $wftable = $pntable['feproc_workflow'];
    $wfcolumn = &$pntable['feproc_workflow_column'];

    // Delete the item.
    $sql = "DELETE FROM $wftable
            WHERE $wfcolumn[id] = " . pnVarPrepForStore($stageid);
    $dbconn->Execute($sql);

    // Check for an error with the database code, and if so set an
    // appropriate error message and return.
    if ($dbconn->ErrorNo() != 0) {
        pnSessionSetVar('errormsg', _FXDELETEFAILED);
        return false;
    }

    // Update any other stages that point to the deleted item (so there are no
    // dead-ends).
    $sql = "UPDATE $wftable
            SET $wfcolumn[successid] = 0
            WHERE $wfcolumn[successid] = " . pnVarPrepForStore($stageid);
    $dbconn->Execute($sql);

    if ($dbconn->ErrorNo() != 0) {
        pnSessionSetVar('errormsg', _FXDELETEFAILED);
        return false;
    }

    $sql = "UPDATE $wftable
            SET $wfcolumn[failureid] = 0
            WHERE $wfcolumn[failureid] = " . pnVarPrepForStore($stageid);
    $dbconn->Execute($sql);

    if ($dbconn->ErrorNo() != 0) {
        pnSessionSetVar('errormsg', _FXDELETEFAILED);
        return false;
    }

    // Let any hooks know that we have deleted an item.
    // TODO: distinguish between different types of item.
    pnModCallHooks('item', 'deletestage', $stageid, 'stageid');

    // Let the calling process know that we have finished successfully
    return true;
}




?>
