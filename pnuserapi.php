<?php
/**
 * FEproc - Mail template backend module for FormExpress for 
 *   Zikula Content Management System
 * 
 * @copyrightt (C) 2002 by Jason Judge, 2011 Chris Candreva
 * @Version $Id:                                              $
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
 * Count the number of workflow sets available.
 * @returns int
 * @return number of sets available.
 */
function feproc_userapi_countsets()
{
    // Early security check.
   if (!SecurityUtil::checkPermission ('FEproc::', '::', ACCESS_READ)) {
        return LogUtil::registerPermissionError();
    }

    $pntable = pnDBGetTables();
    $wfColumn = $pntable['feproc_workflow_column'];
    $where = "$wfColumn[type] = 'set'";
    return DBUtil::selectObjectCount('feproc_workflow', $where, 'id', '');

}

/**
 * Count the number of workflow sets available.
 * @returns int
 * @return number of sets available.
 */
function feproc_userapi_countstages($args)
{
    // Early security check.
   if (!SecurityUtil::checkPermission ('FEproc::', '::', ACCESS_READ)) {
        return LogUtil::registerPermissionError();
    }

    if (isset($args['setid']) && is_numeric($args['setid']))
    {
        $setid = $args['setid'];
    } else {
        $setid = 0;
    }
    
    $table = pnDBGetTables();
    $wfColumn = $table['feproc_workflow_column'];
    $where = "$wfColumn[type] = 'set'";
    $where = "$wfColumn[type] != 'set' AND $wfColumn[setid] = $setid";
    return DBUtil::selectObjectCount('feproc_workflow', $where, 'id', '');

}

/**
 * Get summary of all sets.
 * @returns associative array
 *          (setid,name,description,startstageid,startstagename)
 * @return set specification
 */
function feproc_userapi_getallsets($args)
{
    // Early security check.
   if (!SecurityUtil::checkPermission ('FEproc::', '::', ACCESS_READ)) {
        return LogUtil::registerPermissionError();
    }
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

    $sets = array();

    // Get datbase setup
    $table = pnDBGetTables();

    $wfTable = $table['feproc_workflow'];
    $wfColumn = $table['feproc_workflow_column'];
    $where = "tbl.$wfColumn[type] = 'set'";
    // Join to get name of start stage.
    $joinInfo = array();
            $joinInfo[] = array(
            'join_table'    => 'feproc_workflow',
            'join_field'    => 'name',
            'object_field_name' => 'startstagename',
            'compare_field_table'   => 'successid',
            'compare_field_join'   => 'id',
        );

    $sets = DBUtil::selectExpandedObjectArray('feproc_workflow', $joinInfo, $where, 'name', $startnum, $numitems );


    // Rename successid to startstageid
    foreach ($sets as &$set)
    {
        $set['startstageid'] = $set['successid'];
    }
       
    return $sets;
}

/**
 * Get a single set.
 * @returns associative array
 *          (setid,name,description,startstageid,startstagename)
 * @return set specification
 */
function feproc_userapi_getset($args)
{
    // Early security check.
   if (!SecurityUtil::checkPermission ('FEproc::', '::', ACCESS_READ)) {
        return LogUtil::registerPermissionError();
    }
 // Get arguments from argument array.
    extract($args);

    // Early security check.
    if (!pnSecAuthAction(0, 'FEproc::', "::", ACCESS_READ))
    {
        return false;
    }

    if (!isset($setid) || !is_numeric($setid))
    {
        return false;
    }

    // Get datbase setup
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $wfTable = $pntable['feproc_workflow'];
    $wfColumn = $pntable['feproc_workflow_column'];
    $set = DBUtil::selectObjectByID('feproc_workflow', $setid);

    // Get the name of the start stage.
    if ($set['successid'])
    {
        $startstage = pnModAPIFunc('feproc', 'user', 'getstage',
                      array('stageid' => $set['successid']));
        $set['startstage'] = $startstage['name'];
        $set['startstageid'] = $set['successid'];
    } else {
        $set['startstage'] = null;
    }
    // Handle legacy name
    $set['setid'] = $set['id'];

    return $set;
}



/**
 * Get stage specification
 * The input parameters 'tid' and 'tname' are mutualy exclusive - use only
 * one of them (either id or name).
 * @param args['tid'] template id
 * @param args['tname'] template name
 * @returns associative array
 *          (id,name,description,subject,fromaddress,template)
 * @return template specification
 */
function feproc_userapi_getstage($args)
{
    // Get arguments from argument array.
    extract($args);

    $pntable = pnDBGetTables();
    $wfColumn = $pntable['feproc_workflow_column'];

    $where = ' 1=1';

    if (isset($stageid) && is_numeric($stageid))
    {
        $where = $where . " AND $wfColumn[id] = $stageid";
    }

    if (isset($setid) && is_numeric($setid))
    {
        $where = $where . " AND $wfColumn[setid] = $setid";
    }

    if (isset($handlerid) && is_numeric($handlerid))
    {
        $where = $where . " AND $wfColumn[handlerid] = $handlerid";
    }

    if (isset($name))
    {
        $where = $where . " AND $wfColumn[name] = '$name'";
    }

    if (isset($set))
    {
        // Calling function has asked for sets.
        $where = $where . " AND $wfColumn[type] = 'set' ";
    }

    if (isset($start))
    {
        // Calling function has asked for starting stages.
        // Get the list of starting stages from the list of sets.
        // o If a stage id has been passed in, then limit to that stage only.
        // o If no stage id has been passed in, then just get default
        //   starting stages.

        // All starting stages will have the starting stage flag set.
        $where = $where . " AND $wfColumn[startstage] > 0 ";

        // TODO: this next bit may be best solved by getting the set and
        // then checking the 'default start stage' attribute for that set.
        if (!isset($stageid))
        {
            // We have just been given the set to look at:
            // get default starting stages for each set.
            $startstages = DBUtil::selectFieldArray('feproc_workflow','successid', 
                "$wfColumn[type] = 'set' AND $wfColumn[successid] > 0");

            if (!empty($startstages))
            {
                $where = $where . " AND $wfColumn[id] IN(" . implode(',', $startstages) . ") ";
            }
        }
    }
    $stages = DBUtil::selectObjectArray('feproc_workflow', $where);
    $stage = $stages[0];
    $stage['stageid'] = $stage['id'];
    $stage['attributes'] = unserialize($stage['attributes']);

    return $stage;
}


/**
 * Get summary of all stages.
 * @returns associative array
 *          (setid,name,description,type,handlerid,handlername,successid,successname,failureid,failurename,setid)
 * @return set specification
 */
function feproc_userapi_getallstages($args)
{
    // Early security check.
   if (!SecurityUtil::checkPermission ('FEproc::', '::', ACCESS_READ)) {
        return LogUtil::registerPermissionError();
    }
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

    $table = pnDBGetTables();
    $wfColumn = $table['feproc_workflow_column'];

    $where = " tbl.$wfColumn[type] != 'set' ";
    if ($setid && is_numeric($setid))
    {
        $where .= " AND tbl.$wfColumn[setid] = $setid";
    }

    $joinInfo = array();
            $joinInfo[] = array(
            'join_table'    => 'feproc_workflow',
            'join_field'    => 'name',
            'object_field_name' => 'startstagename',
            'compare_field_table'   => 'successid',
            'compare_field_join'   => 'id',
        );

    $joinInfo = array(
            array(
            'join_table'    => 'feproc_workflow',
            'join_field'    => 'name',
            'object_field_name' => 'successname',
            'compare_field_table'   => 'successid',
            'compare_field_join'   => 'id',
            ),
            array(
            'join_table'    => 'feproc_workflow',
            'join_field'    => 'name',
            'object_field_name' => 'failurename',
            'compare_field_table'   => 'failureid',
            'compare_field_join'   => 'id',
            ),
        );
    $stages = DBUtil::selectExpandedObjectArray('feproc_workflow', $joinInfo, $where, 'id', $startnum, $numitems );
    foreach ($stages as &$stage) {
        $stage['stageid'] = $stage['id'];
    }
    unset($stage);
    return $stages;
}




/**
 * Return a URL to invoke the specified stage.
 * @returns url or false if none could be determined.
 * @return the url to invoke the stage.
 * @param 'stageid' the ID of the current stage, where known.
 * @param 'reset' indicates if the set is to be reset before running the stage.
 */
function feproc_userapi_stageurl($args)
{
    extract($args);

    if (empty($stageid) && empty($setid))
    {
        return false;
    }

    // Search array to fetch the stage.
    $findstage = array();

    // Get the stage details for the set.
    if (!empty($setid))
    {
        $findstage['setid'] = $setid;
    }

    if (!empty($stageid))
    {
        $findstage['stageid'] = $stageid;
    }

    if ($reset || empty($stageid))
    {
        // If reseting the set or just running a set, then we must
        // be going to a starting stage.
        $findstage['start'] = true;
    }

    $stage = pnModAPIFunc('feproc', 'user', 'getstage', $findstage);

    if (!$stage)
    {
        // Could not find any stage to match.
        return false;
    }

    $url = false;

    // Jump straight to the FormExpress module - but only if 
    // we don't want to reset the set first.
    if ($stage['type'] == 'formexpress' && !$reset)
    {
        $url = pnModURL(
            'FormExpress', 'user', 'display_form',
            Array('form_id' => $stage['handlerid'])
        );
    } else {
        // Jump to the FEproc process stage.

        // Query parameters on the final URL.
        $querystring = array();

        // Pass 'reset' to the URL if we want to reset the set when running.
        if ($reset)
        {
            $querystring['reset'] = '1';
        }

        $querystring['stageid'] = $stage['stageid'];

        $url = pnModURL('feproc', 'user', 'process', $querystring);
    }

    // Now switch between secure and non-secure modes if needed.
    // This relies on the fact that pnModURL does not attempt to
    // change the current protocol: it returns a full URL with the
    // current protocol intact.

    if (preg_match('/^http:/i', $url) && $stage['secure'])
    {
        // Needs to be secure but is not yet.
        $url = preg_replace('/^http:/i', 'https:', $url);
    }
    elseif (preg_match('/^https:/i', $url) && !$stage['secure'])
    {
        // Is secure but no need for it.
        $url = preg_replace('/^https:/i', 'http:', $url);
    }

    return $url;
}


/**
 * Get the next user stage. Will pass through as many non-user stages
 * as necessary to get there.
 * @returns array('action', 'url', 'text', 'complete')
 * @return the next stage to be executed in the current set.
 * @param 'type' the type of the current stage (start, display, formexpress, form, etc.)
 * @param 'formid' the ID of the form (regardless whether FormExpress or a standard form)
 * @param 'setid' the ID of the current set, where known.
 * @param 'stageid' the ID of the current stage, where known.
 * @param 'success' the result of the current stage (success or failure - boolean)
 */
function feproc_userapi_nextstage($args)
{
    // Get arguments from argument array.
    extract($args);

    // Early security check.
    if (!pnSecAuthAction(0, 'FEproc::', '::', ACCESS_READ))
    {
        return false;
    }

    // Default return array.
    $nextstage = Array(
        'action' => false,      // Action (redirect or display)
        'url' => false,         // Redirection URL (for redirect action)
        'text' => false,        // Text to show (for display action)
        'complete' => false     // Flag indicates this is the last stage
    );

    // Get the current feproc session so we can validate the stage we just came from.

    $sessiondata = new feprocSession();

    // Validation is as follows:
    // TODO: validation as per design matrix.

    // Now process as many stages as needed before handing 
    // control back to the user.

    $stagesprocessed = Array();

    while (true)
    {
        $stage = pnModAPIFunc(
            'feproc', 'user', 'getstage',
            array('stageid' => $stageid)
        );

        if (!$stage['successid'] && !$stage['failureid'])
        {
            $complete = true;
        } else {
            $complete = false;
        }

        // Error if the stage details could not be fetched.
        // TODO: use templates for errors.
        if (! $stage)
        {
            $nextstage = Array(
                'action' => 'error',
                'url' => false,
                'text' => "The current stage $stageid (set $setid, handler $handlerid) does not exist.",
                'complete' => true
            );
            break;
        }

        // Check that we have not processed this stage too many times.
        if (! $stagesprocessed[$stageid])
        {
            $stagesprocessed[$stageid] = 1;
        } else {
            $stagesprocessed[$stageid] += 1;
            if ($stagesprocessed[$stageid] > 5) // TODO: configurable.
            {
                // TODO: better error message.
                $nextstage = Array(
                    'action' => 'error',
                    'url' => false,
                    'text' => 'Stage is being processed too many times (infinite loop?).',
                    'complete' => true
                );
                break;
            }
        }

        // If the stage is a user stage then return control to the user now.
        // If it is not then execute it.

        // Next stage is a user stage.
        if ($stage['type'] == 'formexpress' || $stage['type'] == 'form'
        || $stage['type'] == 'display' || $stage['type'] == 'redirect')
        {
            $url = pnModAPIFunc(
                'feproc', 'user', 'stageurl',
                array('stageid' => $stageid)
            );

            $nextstage = Array(
                'action' => $stage['type'],
                'url' => $url,
                'text' => false,
                'stageid' => $stageid,
                'stage' => $stage,
                'complete' => $complete
            );

            $sessiondata->putStageID($stageid);
            break;
        }

        $result = false;
        
        // Next stage is an internal handler.
        if ($stage['type'] == 'transmit' || $stage['type'] == 'transform' || $stage['type'] == 'validate')
        {
            // TODO: get the template details and expand the template, then pass in
            // to the transmit API function, along with some other details.
            // This will be similar to the 'display' handler type in that templates
            // need expanding.

            // Get the template-substituted handler data.
            $handlerdata = pnModAPIFunc(
                'feproc', 'user', 'handlerdata',
                array('stageid' => $stageid)
            );

            // Get the handler details.
            $handler = pnModAPIFunc(
                'feproc', 'handleruser', 'gethandler',
                array('hid' => $stage['handlerid'])
            );

            // The handler may not exist.
            if (!$handler)
            {
                $nextstage = Array(
                    'action' => 'error',
                    'url' => false,
                    'text' => "Handler does not exist (hid=$stage[handlerid]).",
                    'complete' => true
                );
                break;
            }

            // Call the handler processing function.
            $handlerReturn = pnModAPIFunc(
                $handler['modulename'], $handler['apiname'], $handler['apifunc'],
                array('action' => 'execute', 'info' => $handlerdata)
            );

            // Set the handler status (success/fail) and loop back for next stage.
            if ($handlerReturn['result'])
            {
                $result = true;
            } else {
                $result = false;
            }

            // Log a stack message.
            // TODO: limit the size of the stack so it cannot be made to overflow.
            if (pnModGetVar('FEproc', 'tracestack'))
            {
                $sessiondata->setMessages(Array('stack' => "$stageid: $stage[type] result: " . ($handlerReturn[result] ? "success":"failure")), true);
            }
        }

        if ($stage['type'] == 'transform' && $result)
        {
            // A successful transform stage was done.
            // Store the values transformed in the form array.
            if (is_array($handlerReturn['form']))
            {
                foreach($handlerReturn['form'] as $tkey => $tvalue)
                {
                    $sessiondata->addFormData(array($tkey => $tvalue));
                }
            }
        }

        // Any handler can return messages. Pick up the messages if any were returned.
        if (is_array($handlerReturn['messages']))
        {
            $sessiondata->setMessages($handlerReturn['messages']);
        }

        // Get the ID for the next stage.
        if ($result)
        {
            $nextstageid = $stage['successid'];
        } else {
            $nextstageid = $stage['failureid'];
        }

        if (! $nextstageid)
        {
            // There is not a next stage defined for this stage.
            // Raise an error as we should not have been able to get
            // here if there was nowhere to go after.
            // TODO: templated error message.
            $nextstage = Array(
                'action' => 'error',
                'url' => false,
                'text' => 'The set has ended unexpectedly. The stage result has nowhere to go.',
                'complete' => true
            );
            break;
        }

        // The next stage is now the current stage.
        // Loop around and process the new stage.
        $stageid = $nextstageid;
        $sessiondata->putStageID($stageid);
    }

    return $nextstage;
}


/**
 * Create handler information with template field substritions where relevant.
 * one of them (either id or name).
 * @param args['$stageid'] current stage id
 * @param args['$successid'] success stage id
 * @param args['$failureid'] failure stage id
 * @returns associative array
 * @return data for handler with template field substitutions.
 */
function feproc_userapi_handlerdata($args)
{
    extract($args);

    $handlerinfo = Array();

    // Form data.
    $sessiondata = new feprocSession();
    $handlerinfo['form'] = $sessiondata->getFormData();

    // Current stage details.
    $stage = pnModAPIFunc(
        'feproc', 'user', 'getstage',
        array('stageid' => $stageid)
    );

    if (!$stage)
    {
        return false;
    }
    
    // Messages.

    $handlerinfo['messages'] = $sessiondata->getMessages();

    // Links. The links are the success, failure and back URLs.
    // TODO: since we call this function so many times, allow a pointer
    // to the stage array to be passed in so save on database queries.
    //
    // Link names are:-
    //
    //      successurl      - success stage
    //      resetsuccessurl - success stage, reset set first, must be starting stage
    //      failureurl      - failure stage
    //      resetfailureurl - failure stage, reset set first, must be starting stage
    //      seturl          - go to set default start stage
    //      reseturl        - go to set default start stage, reset set first
    //      starturl        - go to starting stage that started this stage instance
    //                        (allows for starting again with a multiple entry-point
    //                        set
    //      restarturl      - go to starting stage that started this stage instance,
    //                        reset set first
    //      

    if ($stage['successid'])
    {
        // Get the success stage url.
        $handlerinfo['links']['successurl'] = pnModAPIFunc(
            'feproc', 'user', 'stageurl',
            array('stageid' => $stage['successid'])
        );
        $handlerinfo['links']['resetsuccessurl'] = pnModAPIFunc(
            'feproc', 'user', 'stageurl',
            array('stageid' => $stage['successid'], 'reset' => '1')
        );
    }

    if ($stage['failureid'])
    {
        $handlerinfo['links']['failureurl'] = pnModAPIFunc(
            'feproc', 'user', 'stageurl',
            array('stageid' => $stage['failureid'])
        );
        $handlerinfo['links']['resetfailureurl'] = pnModAPIFunc(
            'feproc', 'user', 'stageurl',
            array('stageid' => $stage['failureid'], 'reset' => '1')
        );
    }

    // Set and reset goes back to the default start stage in the set
    // (if there is one).
    // TODO: check there is a default start stage.
    if ($stage['setid'])
    {
        $handlerinfo['links']['seturl'] = pnModAPIFunc(
            'feproc', 'user', 'stageurl',
            array('setid' => $stage['setid'])
        );
        $handlerinfo['links']['reseturl'] = pnModAPIFunc(
            'feproc', 'user', 'stageurl',
            array('setid' => $stage['setid'], 'reset' => '1')
        );
    }

    // Start goes back to the default actual start stage in the set.
    if ($sessiondata->getStartStageID())
    {
        $handlerinfo['links']['starturl'] = pnModAPIFunc(
            'feproc', 'user', 'stageurl',
            array('stageid' => $sessiondata->getStartStageID())
        );

        // Restart clears down the session then goes back to the actual
        // start stage in the set.
        $handlerinfo['links']['restarturl'] = pnModAPIFunc(
            'feproc', 'user', 'stageurl',
            array('stageid' => $sessiondata->getStartStageID(), 'reset' => '1')
        );
    }

    // System variables.
    $handlerinfo['system'] = Array(
        'setid' => $stage['setid'],
        'stageid' => $stageid,
        'type' => $stage['type'],
        'complete' => (!$stage['successid'] && !$stage['failureid'] ? 1 : 0),
        'transaction' => $sessiondata->getTRXid(),
        'adminmail' => pnConfigGetvar('adminmail'),
        'sitename' => pnConfigGetvar('sitename'),
        'slogan' => pnConfigGetvar('slogan')
    );

    // Attributes from the stage (the template, parameters etc.)
    $handlerinfo['attributes'] = $stage['attributes'];

    // Substitute form and other values in the attributes. Do all the attributes.
    // Loop for each attribute that may need changing.
    foreach ($handlerinfo['attributes'] as $attrName => $attrValue)
    {
      foreach (array('form', 'system', 'links', 'messages') as $type) {
        // Do string replacements from the various data types.
        if (is_array($handlerinfo[$type]))
        {
            foreach ($handlerinfo[$type] as $itemName => $itemValue)
            {
                $attrValue = str_ireplace('${' . $type . ':' . $itemName. '}', $itemValue, $attrValue);
            }
        }
      }

      $handlerinfo['attributes'][$attrName] = $attrValue;
    }    // foreach ($handlerinfo['attributes'] as $attrName => $attrValue)

    $removeunmatched = pnModGetVar('FEproc', 'removeunmatched');

    // Now do the same between the attributes so they can reference each other.
    if (is_array($handlerinfo['attributes']))
    {
        foreach ($handlerinfo['attributes'] as $attrName => $attrValue)
        {
            // Do string replacements on the form data.
            foreach ($handlerinfo['attributes'] as $itemName => $itemValue)
            {
                // Suppress self-referencing loops.
                if ($attrName != $itemName)
                {
                    $attrValue = str_ireplace('${attribute:' . $itemName. '}', $itemValue, $attrValue);

                    if ($removeunmatched)
                    {
                        // Finally remove any fields that have not matched anything.
                        // TODO: visit this preg and make it strictor (form|attribute|...)
                        $attrValue = preg_replace('/\${[^:]+:[^}]+}/', '', $attrValue);
                    }
                }
            }

            $handlerinfo['attributes'][$attrName] = $attrValue;
        }
    }

    return $handlerinfo;
}

?>
