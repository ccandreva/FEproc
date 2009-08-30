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
 * Count the number of handlers available.
 * @returns int
 * @return number of handlers available.
 */
function feproc_handleruserapi_counthandlers()
{
  // Early security check.
  if (!pnSecAuthAction(0, 'FEproc::', '::', ACCESS_READ))
  {
    return false;
  }

  // Get datbase setup.
  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $handlerTable = $pntable['feproc_handlers'];
  $handlerColumn = $pntable['feproc_handlers_column'];

  $sql = "SELECT COUNT(*)
          FROM     $handlerTable";

  $result = $dbconn->Execute($sql);

  // Check for an error with the database code, and if so set an appropriate
  // error message and return
  if ($dbconn->ErrorNo() != 0)
  {
    pnSessionSetVar('errormsg', _FXGETFAILED);
    return false;
  }

  list($count) = $result->fields;

  // Close result set.
  $result->Close();

  return $count;
}

/**
 * Get handler specification
 * The input parameters 'hid' and 'hname' are mutualy exclusive - use only
 * one of them (either hid or name).
 * @param args['hid'] handler id
 * @param args['hname'] handler name
 * @returns associative array
 *          (id,name,description,TODO)
 * @return handler specification
 */
function feproc_handleruserapi_gethandler($args)
{
  // Get arguments from argument array.
  extract($args);

  // Argument check - make sure that all required arguments are present,
  // if not then set an appropriate error message and return
  if (!isset($hid)  &&  !isset($name) && !isset($source))
  {
      pnSessionSetVar('errormsg', _FXMODARGSERROR);
      return false;
  }

  // Get database setup.
  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $handlerTable = $pntable['feproc_handlers'];
  $handlerColumn = $pntable['feproc_handlers_column'];

  if (isset($hid))
  {
    $where = "$handlerColumn[id] = " . pnVarPrepForStore($hid) . "";
  }
  elseif (isset($name))
  {
    $where = "$handlerColumn[name] = '" . pnVarPrepForStore($name) . "'";
  }
  elseif (isset($source))
  {
    list($type, $modulename, $apiname, $apifunc) = split(':', $source, 4);
    $where = "$handlerColumn[type] = '" . pnVarPrepForStore($type) . "'"
    . " and $handlerColumn[modulename] = '" . pnVarPrepForStore($modulename) . "'"
    . " and $handlerColumn[apiname] = '" . pnVarPrepForStore($apiname) . "'"
    . " and $handlerColumn[apifunc] = '" . pnVarPrepForStore($apifunc) . "'"
    ;
  }

  $sql = "SELECT   $handlerColumn[id],
                   $handlerColumn[name],
                   $handlerColumn[description],
                   $handlerColumn[type],
                   $handlerColumn[version],
                   $handlerColumn[modulename],
                   $handlerColumn[apiname],
                   $handlerColumn[apifunc],
                   $handlerColumn[attributes]
          FROM     $handlerTable
          WHERE    $where";

  $result = $dbconn->Execute($sql);

  // Check for an error with the database code, and if so set an appropriate
  // error message and return
  if ($dbconn->ErrorNo() != 0)
  {
    pnSessionSetVar('errormsg', _FXGETFAILED);
    return false;
  }

  if ($result->EOF)
  {
    $handler = false;
  } else {
    $handler = array( 'hid' => $result->fields[0],
                       'name' => $result->fields[1],
                       'description' => $result->fields[2],
                       'type' => $result->fields[3],
                       'version' => $result->fields[4],
                       'modulename' => $result->fields[5],
                       'apiname' => $result->fields[6],
                       'apifunc' => $result->fields[7],
                       'attributes' => unserialize($result->fields[8]),
                       'source' => $result->fields[3] .':'. $result->fields[5] .':'. $result->fields[6] .':'. $result->fields[7]);
  }

  // Close result set.
  $result->Close();

  return $handler;
}

/**
 * Get list of all handlers
 * @param $args['startnum'] first handler number (starting from 1)
 * @param $args['numitems'] number of handlers
 * @returns array of associative arrays
 *          (id,name,description,type)
 * @return array of all handlers
 */
function feproc_handleruserapi_getallhandlers($args)
{
  // Get arguments from argument array.
  extract($args);

  // Argument check
  if (!isset($startnum))
  {
    $startnum = 0;
  } else {
    --$startnum;
  }

  if (!isset($numitems))
  {
    $numitems = 999999;
  }

  $handlers = array();

  // Early security check.
  if (!pnSecAuthAction(0, 'FEproc::', "::", ACCESS_READ))
  {
    return $handlers;
  }

  // Get datbase setup.
  list($dbconn) = pnDBGetConn();
  $pntable = pnDBGetTables();

  $handlerTable = $pntable['feproc_handlers'];
  $handlerColumn = $pntable['feproc_handlers_column'];

  $where = "1=1";

  if ($type)
  {
      $where .= " AND $handlerColumn[type] = '" . pnVarPrepForStore($type) . "'";
  }

  $sql = buildsimplequery('feproc_handlers',
                          array('id','name','description','type','modulename','apiname','apifunc'),
                          $where,
                          "$handlerColumn[name]",
                          $numitems,
                          $startnum);

  $result = $dbconn->Execute($sql);

  // Check for an error with the database code, and if so set an appropriate
  // error message and return
  if ($dbconn->ErrorNo() != 0)
  {
    pnSessionSetVar('errormsg', _FXGETFAILED);
    return false;
  }

  for (; !$result->EOF; $result->MoveNext())
  {
    $handlers[] = array('hid' => $result->fields[0],
                         'name' => $result->fields[1],
                         'description' => $result->fields[2],
                         'type' => $result->fields[3],
                         'modulename' => $result->fields[4],
                         'apiname' => $result->fields[5],
                         'apifunc' => $result->fields[6],
                         'source' => $result->fields[3] .':'. $result->fields[4] .':'. $result->fields[5] .':'. $result->fields[6] );
  }

  // Close result set.
  $result->Close();

  return $handlers;
}

?>
