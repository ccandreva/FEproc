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

  return DBUtil::selectObjectCount('feproc_handlers', '', 'id', '');
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

  if (isset($hid)) {
    //$where = "id = $hid";
    $handler = DBUtil::SelectObjectById('feproc_handlers', $hid);
  }
  
  elseif (isset($name)) {
    //$where = "name = $name";
    $handler = DBUtil::SelectObjectById('feproc_handlers', $name, 'name');
  }

  elseif (isset($source)) {
    list($type, $modulename, $apiname, $apifunc) = split(':', $source, 4);
    $where = "type = $type"
    . " and modulename = $modulename"
    . " and apiname = $apiname"
    . " and apifunc = $apifunc"
    ;
    $obj = DBUtil::selectObjectArray('feproc_handlers', $where);
    $handler = $obj[0];
  }
  else  // No arguments set, return an error
  {
      pnSessionSetVar('errormsg', _FXMODARGSERROR);
      return false;
  }
      
  $handler['source'] = implode(':', array($handler['type'], $handler['modulename'], $handler['apiname'], $handler['apifunc']));
  $handler['hid'] = $handler['id'];
  
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
    $numitems = -1;
  }

  $handlers = array();

  // Early security check.
  if (!pnSecAuthAction(0, 'FEproc::', "::", ACCESS_READ))
  {
    return array();
  }

  if ($type)
  {
      $where = "type = $type";
  } else {
      $where = '';
  }

  $handlers = DBUtil::selectObjectArray('feproc_handlers', $where, 'name', $startnum, $numitems);
  foreach ($handlers as &$obj) {
    $obj['source'] = implode(':', array($obj['type'], $obj['modulename'], $obj['apiname'], $obj['apifunc']));
    $obj['hid'] = $obj['id'];
  }
  unset($obj);
  
  return $handlers;
}

?>
