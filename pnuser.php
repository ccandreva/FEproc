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

/*
 * Handler for FormExpress
 * The submit action catches the form content, and the success and 
 * failue actions switch to the next stage.
 * @param $args['action'] - either 'submit', 'success' or 'failure'
 * @param $args['setid'] - the set id
 */
function feproc_user_formexpress($args)
{
    // General check for access to this module.
   if (!SecurityUtil::checkPermission ('FEproc::', 'FEproc::', ACCESS_READ)) {
        return LogUtil::registerPermissionError();
    }

    // Get the arguments
    extract($args);

    if (isset($setid) && !is_numeric($setid))
    {
        pnSessionSetVar('errormsg', __("Invalid Parameters"));
        return false;
    }

    if (!isset($action))
    {
        pnSessionSetVar('errormsg', __("Invalid Parameters"));
        return false;
    } else {
        $action = strtolower($action);
    }

    // Ignore the form submission or validation. Catch the form again
    // at the one of the final stages.
    if ($action != 'success' && $action != 'failure')
    {
        return true;
    }

    // Get form result status.
    if ($action == 'success')
    {
        $actionsuccess = true;
    } else {
        $actionsuccess = false;
    }

    // Get the feproc session object.
    if (!pnModAPILoad('feproc', 'session'))
    {
        pnSessionSetVar('errormsg', _FXMODLOADFAILED);
        return pnRedirect(pnModURL('feproc', 'user', 'view'));
    }

    // Get the FormExpress session object and extract the submitted form id.
    $fxSession = new FXSession();
    $form_id = $fxSession->getSubmittedFormID();

    $sessiondata = new feprocSession();

    // Get set id if one is running.
    $currentsetid = $sessiondata->getSetID();

    $currentstage = false;

    if ($currentsetid)
    {
        // A set is running. Ensure this form is valid for the set.

        // If the set has timed out, then restart it.
        // TODO: wait until these two sections (FE and Proc) are merged. It should be done
        // centrally in getNextStage().
/*
        if ($currentsetid)
        {
            $sessiontimeout = pnModGetVar('FEproc', 'sessiontimeout');
            if ($sessiontimeout > 0 && time() > (60*$sessiontimeout + $sessiondata->getSessionStart()))
            {
                // TODO: tell the user the session has timed out.
                // Set the user back to the start of the set...
                $setid = $currentsetid;
                $stageid = false;
                // ...and close the current set.
                $currentsetid = false;
            }
        }
*/
        if ($currentsetid)
        {
            // Get the last registered stage for this set.
            $stageid = $sessiondata->getStageID();

            // Try with the last stage id first (in case the same form is
            // used more than once in the set.
            $currentstage = pnModAPIFunc('feproc', 'user', 'getstage',
                array(
                    'type' => 'formexpress',
                    'stageid' => $stageid,
                    'handlerid' => $form_id,
                    'setid' => $currentsetid
                )
            );
        }

        if (!$currentstage)
        {
            // Try again without the stage.
            $currentstage = pnModAPIFunc('feproc', 'user', 'getstage',
                array(
                    'type' => 'formexpress',
                    'handlerid' => $form_id,
                    'setid' => $currentsetid
                )
            );
        }

        // If we did not get a stage, then close the set: we may have
        // moved on to a new set.

        if ($currentstage)
        {
            $stageid = $currentstage['stageid'];
        } else {
            $sessiondata->closeSession();
            $currentsetid = false;
            $stageid = false;
        }
    }
    
    if (! $currentsetid)
    {
        // Set is not in process. See if we can start one.

        $currentstage = pnModAPIFunc('feproc', 'user', 'getstage',
            array(
                'start' => true,
                'type' => 'formexpress',
                'handlerid' => $form_id
            )
        );

        if ($currentstage)
        {
            // Yes - this is a starting stage form.
            $currentsetid = $currentstage['setid'];
            $stageid = $currentstage['id'];
            if (SecurityUtil::checkPermission ('FEproc::Set', "::$setid", ACCESS_READ))
            {
                $sessiondata->startSet($currentsetid, $stageid);
            } else {
                return LogUtil::registerPermissionError();
            }
        } else {
            // TODO: error if no starting stage.
            pnSessionSetVar('errormsg', "Not a starting stage (this form can only be run as part of a set)");
            return pnRedirect(pnModURL('feproc', 'user', 'view'));
        }
    }

    // Get the form data if the form submission action was a success.
    if ($actionsuccess)
    {
        // Get the form data.
        // The form cache provides the definition of the form so
        // empty fields can still be included, like unchecked checkboxes.
        $fxCache = new FXCache();
        $formcache = $fxCache->getForm($form_id);

        // Get the submitted form data.
        $formdata = $fxSession->getForm($form_id);

        $newformdata = Array();

        foreach($formcache['items'] as $item)
        {
            $itemName = $item['item_name'];
            // Remove any substitution variables the user may have entered.
            $itemValue = preg_replace('/\${[^:]+:[^}]+}/', '*REMOVED*', $formdata[$itemName]);
            // Ignore some special FormExpress items.
            if ($itemName <> 'BoilerPlate' && $itemName <> 'GroupStart' && $itemName <> 'GroupEnd')
            {
                $newformdata[$itemName] = $itemValue;
            }
        }

        if (!empty($newformdata))
        {
            $sessiondata->addFormData($newformdata);
        }
    }

    if ($actionsuccess)
    {
        $nextstageid = $currentstage['successid'];
    } else {
        $nextstageid = $currentstage['failureid'];
    }

    // Pass in: the next stage id.
    $nextstage = pnModAPIFunc(
        'feproc', 'user', 'nextstage',
        array(
            'stageid' => $nextstageid
        )
    );

    // Return control to the user: either a redirect or display an error.

    if ($nextstage['action'] == 'redirect' || $nextstage['action'] == 'form'
    || $nextstage['action'] == 'display' || $nextstage['action'] == 'formexpress')
    {
        // There is a URL to go to. This could be external to feproc
        // or it could be the next stage in the set.
        return pnRedirect($nextstage['url']);
    }

    if ($nextstage['action'] == 'error')
    {
        // Display the text. This is a templated output.
        $render = pnRender::getInstance('feproc');
        $render->assign('text', $nextstage['text']);
        return $render->fetch('feproc_user_error.html');
    }
    

    // Don't know what the next stage is, so redirect to an error page.
    // TODO: handle better as an error.
    return false;
}


/*
 * Handler for any stage - except for FE forms (TODO: yet).
 * Used when a display stage link needs to jump to a stage
 * that does not have a user display element to it (not a form,
 * display or error stage.
 * failue actions switch to the next stage.
 * @param $args['stageid'] - the stage id
 * @param $args['setid'] - the set id (to start a set)
 * @param $args['resetid'] - set to reset the set and start again
 * @param $args['stagename'] - identify a stage by name rather than ID
 */
function feproc_user_process($args)
{
    // General check for access to this module.
   if (!SecurityUtil::checkPermission ('FEproc::', '::', ACCESS_READ)) {
        return LogUtil::registerPermissionError();
    }

    list($stageid, $setid, /*$resetid,*/ $reset) = pnVarCleanFromInput('stageid', 'setid', /*'resetid',*/ 'reset');

    // TODO: not sure at all about this logic. Split up parameter checks into 
    // a number of layers: a) at least one is set; b) those that are set are 
    // the correct types.
    if ( (!isset($stageid) || $stageid == 0 || !is_numeric($stageid))
        && (!isset($setid) || $setid == 0 || !is_numeric($setid)) 
        && (!isset($resetid) || $resetid == 0 || !is_numeric($resetid)) )
    {
        pnSessionSetVar('errormsg', "Invalid or missing parameters");
        return pnRedirect(pnModURL('feproc', 'user', 'view'));
    }

    // Get the feproc session object.
    if (!pnModAPILoad('feproc', 'session'))
    {
        pnSessionSetVar('errormsg', _FXMODLOADFAILED);
        return pnRedirect(pnModURL('feproc', 'user', 'view'));
    }

    $sessiondata = new feprocSession();
    $currentsetid = $sessiondata->getSetID();
    $laststageid = $sessiondata->getStageID();

    // A set is already running.
    if ($currentsetid)
    {
        // A set is already running. Make sure this stage is in that set.
        // We don't know what type of stage it is yet.
        // Logic is:
        // setid set : stageid set : action
        // Y : N : setid must be same as current setid; get set starting stage; clear done session (start fresh)
        // Y : Y : setid must be same as current setid; get stage from within set
        // N : Y : get stage from within current set
        // Any failures to get the stage from this logic will result in the current
        // set being closed.

        $stage = false;

        // If 'reset' is passed in, then close the current set.
        if ($reset)
        {
            $currentsetid = false;
        }
        
/*
        // If resetid is sent in, then close everything and restart the set.
        if ($currentsetid && $resetid)
        {
            // Set the user to the start of the set (which may or may not be the
            // same as the current set)...
            $setid = $resetid;
            $stageid = false; // TODO: check this. We need a reset with multiple starting points.
            // ...and close the current set.
            $currentsetid = false;
        }
*/

        // If setid is sent in it must be the same as the running one.
        if ($currentsetid && $setid && $setid <> $currentsetid)
        {
            $currentsetid = false;
        }

        // If this session has timed out, then close it now. The form set
        // session is distinct from the main user session in that it has
        // its own timeout period, which could be shorter than the user's
        // timeout period.
        // TODO: timeout functionality to be done in FE processing too.

        // If the set has timed out, then restart it.
        if ($currentsetid)
        {
            $sessiontimeout = pnModGetVar('FEproc', 'sessiontimeout');
            if ($sessiontimeout > 0 && time() > (60*$sessiontimeout + $sessiondata->getSessionStart()))
            {
                // TODO: tell the user the session has timed out.
                // Set the user back to the start of the set...
                $setid = $currentsetid;
                $stageid = false; // TODO: get default timeout stage from the set attributes.
                // ...and close the current set.
                $currentsetid = false;
            }
        }

        if ($currentsetid && $stageid)
        {
            // A stageid has been supplied - get that stage from the set.
            $stage = pnModAPIFunc('feproc', 'user', 'getstage',
                array(
                    'stageid' => $stageid,
                    'setid' => $currentsetid
                )
            );

            if (!$stage)
            {
                $currentsetid = false;
            }
        }

        if ($currentsetid && !$stageid)
        {
            // A stageid has not been supplied - get the starting stage from the set.
            $stage = pnModAPIFunc('feproc', 'user', 'getstage',
                array(
                    'start' => true,
                    'setid' => $currentsetid
                )
            );

            if ($stage)
            {
                $stageid = $stage['stageid'];
            }

            // Since we have only been provided with the setid, reset the form session and
            // start from afresh.
            //$currentsetid = false;
        }

        // If this stage is not in the set, then close the current set.
        if (!$currentsetid)
        {
            $sessiondata->closeSession();
            $currentsetid = false; // Redundant
        }
    } /*else {
        // No set already running, but do some manipulation of the
        // parameters.

        if ($resetid)
        {
            // Set the user to the start of the set (which may or may not be the
            // same as the current set)...
            $setid = $resetid;
            $stageid = false; // TODO: check this. We need a reset with multiple starting points.
        }
    }*/

    // No set is already running (or one has just been aborted).
    if (! $currentsetid)
    {
        // No set is currently running.
        // This display stage should be at the start of a set.
        // Get the stage details of the stage that starts a set.

        if (!$stage && $stageid)
        {
            // Only fetch it if we don't already have it.
            $stage = pnModAPIFunc(
                'feproc', 'user', 'getstage',
                array(
                    'start' => true,
                    'stageid' => $stageid
                )
            );
        }

        if (!$stage && $setid)
        {
            // Only fetch it if we don't already have it.
            $stage = pnModAPIFunc(
                'feproc', 'user', 'getstage',
                array(
                    'start' => true,
                    'setid' => $setid
                )
            );
        }

        if (!$stage)
        {
             // TODO: error if not a starting stage (redirect).
            pnSessionSetVar('errormsg', "Not a starting stage (this display page can only be run as part of a set)");
            return pnRedirect(pnModURL('feproc', 'user', 'view'));
        } else {
            $setid = $stage['setid'];
            $stageid = $stage['id'];

            // Check we are allowed to run this set before instantiating it.
            if (pnSecAuthAction(0, 'FEproc::Set', "::$setid", ACCESS_READ))
            {
                $sessiondata->startSet($setid, $stageid);
            } else {
                pnSessionSetVar('errormsg', _BADAUTHKEY);
                pnRedirect(pnModURL('feproc', 'user', 'view'));
                return true;
            }
        }
    }

    // If a form user-stage then redirect there now as another page is needed to display it.
    if ($stage['type'] == 'form' || $stage['type'] == 'formexpress')
    {
        if (pnModGetVar('FEproc', 'tracestack'))
        {
            // Log a stack message.
            $sessiondata->setMessages(Array('stack' => "$stageid: $stage[action] $stage[handlerid]"), true);
        }

        // If required, set form items from previously-captured data.
        if (pnModGetVar('FEproc', 'shareformitems') && false) // Not yet supported.
        {
            // Copy session form values to the form about to be launched
            // where the item names are the same, but values differ.
            // The form may not have been launched yet, so initialise it
            // if required.

            // Load the FE module.
            if (!pnModLoad('FormExpress', 'user'))
            {
                pnSessionSetVar('errormsg', _FXMODLOADFAILED);
                pnRedirect(pnModURL('feproc', 'user', 'view'));
                return true;
            }

            // Has form been loaded already? Initialise if not.
            $fxSession = new FXSession();

            // Get submitted form data - there may not be any.
            $formdata = $fxSession->getForm($stage['handlerid']);

            if (!is_array($formdata))
            {
                // The form has not been submitted yet - initialise it.
                // Get the form cache (the definition of the form items).
                $fxCache = new FXCache();
                $formcache = $fxCache->getForm($stage['handlerid']);

                // Create the blank form.
                if (is_array($formcache))
                {
                    // TODO: a little more thought needs to go into this...
                    // FE can take default values from a previous form anyway 
                    // using the function defined here - feproc_user_DefItemValue()
                    // but if revisiting a form, we need a way to refresh the
                    // item value from the cached value as it may have changed.
                }
            }
        }
        
        $url = pnModAPIFunc(
            'feproc', 'user', 'stageurl',
            array('stageid' => $stageid)
        );
        return pnRedirect($url);
    }

    // Execute any backend handler stages then get the next user stage.

    $nextstage = pnModAPIFunc(
        'feproc', 'user', 'nextstage',
        array('stageid' => $stageid)
    );

    if ($nextstage['stageid'] <> $stageid)
    {
        // We have moved on to a new stage. Refresh the details
        // then redirect to the stage so the URL is set correctly.
        // This would happen if several internal stages were
        // executed before the user stage.
        $stage = $nextstage['stage'];
        $stageid = $nextstage['stageid'];

        $url = pnModAPIFunc(
            'feproc', 'user', 'stageurl',
            array('stageid' => $stageid)
        );
        return pnRedirect($url);
    }
    // Display handler: display the page now.
    if ($nextstage['action'] == 'display' || $nextstage['action'] == 'redirect')
    {
        // Get the template-substituted handler data.
        $handlerdata = pnModAPIFunc('feproc', 'user', 'handlerdata',
            array('stageid' => $stageid)
        );

        // Get the handler details.
        $handler = pnModAPIFunc('feproc', 'handleruser', 'gethandler',
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

        // Load the handler API
        if (!pnModAPILoad($handler['modulename'], $handler['apiname']))
        {
            // TODO: proper error message.
            $nextstage = Array(
                'action' => 'error',
                'url' => false,
                'text' => "Failed to load handler API ($handler[modulename], $handler[apiname]).",
                'complete' => true
            );
            break;
        }

        // Call the handler processing function.
        $handlerResult = pnModAPIFunc(
            $handler['modulename'], $handler['apiname'], $handler['apifunc'],
            array('action' => 'execute', 'info' => $handlerdata)
        );

        // TODO: combine the display and redirect as they are very similar?
        // TODO: most of this will go into the 'nextstage' handler anyway.
        if ($nextstage['action'] == 'display')
        {
            // Before returning, if this is the last stage then close the session.
            if (($handlerResult['result'] && !$stage['successid'])
                || (!$handlerResult['result'] && !$stage['failureid']))
            {
                $sessiondata->closeSession();
            }
            if ($handlerResult['result'])
            {
                if (pnModGetVar('FEproc', 'tracestack'))
                {
                    // Log a stack message.
                    $sessiondata->setMessages(Array('stack' => "$stageid: $nextstage[action] success"), true);
                }

                // Handler returned success and some text to display.
                return $handlerResult['text'];

            } else {
                // Handler returned failure - redirect to the error stage.
                if ($stage['failureid'])
                {
                    if (pnModGetVar('FEproc', 'tracestack'))
                    {
                        // Log a stack message.
                        $sessiondata->setMessages(Array('stack' => "$stageid: $nextstage[action] failure to $stage[failureid]"), true);
                    }

                    $url = pnModAPIFunc(
                        'feproc', 'user', 'stageurl',
                        array('stageid' => $stage['failureid'])
                    );
                } else {
                    if (pnModGetVar('FEproc', 'tracestack'))
                    {
                        // Log a stack message.
                        $sessiondata->setMessages(Array('stack' => "$stageid: $nextstage[action] failure no failureid"), true);
                    }

                    pnSessionSetVar('errormsg', "Handler returned error but there is no error stage defined.");
                    $url = pnModURL('feproc', 'user', 'view');
                    // TODO: close session?
                }

                pnRedirect($url);
                return true;
            }
        }

        if ($nextstage['action'] == 'redirect')
        {
            // If there is no next stage then close the session
            if (($handlerResult['result'] && !$stage['successid'])
                || (!$handlerResult['result'] && !$stage['failureid']))
            {
                $sessiondata->closeSession();
            }

            if ($handlerResult['result'])
            {
                // Handler return success with a url.
                $url = $handlerResult['url'];
            } else {
                // Handler returned failure - redirect to the error stage.
                if ($stage['failureid'])
                {
                    $url = pnModAPIFunc(
                        'feproc', 'user', 'stageurl',
                        array('stageid' => $stage['failureid'])
                    );
                } else {
                    pnSessionSetVar('errormsg', "Handler returned error but there is no error stage defined.");
                    $url = pnModURL('feproc', 'user', 'view');
                    // TODO: close session?
                }
            }
            return pnRedirect($url);
        }
    }

    // Return control to the user: either a redirect or display an error.
    // TODO: make all returns a redirect - even for errors.

    if ($nextstage['action'] == 'form' || $nextstage['action'] == 'formexpress')
    {
        // There is a URL to go to. This could be external to feproc
        // or it could be the next stage in the set.
        // TODO: need to jump back to the top really. It may be necessary to
        // pre-load the form with shared values before redirecting to FormExpress.
        // This redirect to FormExpress is being done in two places here - it should
        // be done in one place only. Perhaps just:
        // return feproc_user_process(array('stageid' =>$stageid));
        // would do the trick?

        pnRedirect($nextstage['url']);
        return true;
    }

    if ($nextstage['action'] == 'error')
    {
        // Display the text. This is a templated output.
        // TODO: make this a redirect too.
        return "<h3>Error</h3>" . $nextstage['text'];
    }

    // Don't know what the next stage is, so redirect to an error page.
    // TODO: handle better as an error.
    return false;
}


/*
 * Default user view.
 */

function feproc_user_view($args)
{
    // TODO: Need to handle this a bit better.
    // At present there is no generic 'view' of a set.
    // Perhaps there should be for administrators?

    $message = pnSessionGetVar('errormsg');
    return "An unexpected error has occurred: $message";
}

/*
 * Handy validation functions for FE.
 * These should be moved to another module as there is no point loading
 * them if they are not used in the majority of cases. If FE will support
 * calling functions from module types other than 'user' then perhaps
 * these can be moved to another type, such as 'validation'.
 */

/*
 * Validate credit card numbers.
 * (Rules derived from http://www.beachnet.com/~hstiles/cardtype.html).
 * Parameters are:
 * args['fx_value']: value of item being validated.
 * args['message']: message to display if card number is invalid.
 * Called as an FE validation string-
 *    {FEproc:ValCreditCard&message='Failure over-ride message'}
 * Or-
 *    {FEproc:ValCreditCard&dummy=dummy}
 */

function feproc_user_ValCreditCard($args)
{
    // Validate a credit card number

    extract($args);

    if (!isset($message))
    {
        $message = 'Invalid credit card number';
    }

    if (!isset($fx_value) || strlen($fx_value) == 0)
    {
        // Value is null: nothing to validate.
        return false;
    }

    // Remove spaces and dashes.
    $fx_value = str_replace(' ', '', $fx_value);
    $fx_value = str_replace('-', '', $fx_value);

    // Check characters. Only numeric is allowed.
    if (!ereg('^[0-9]+$', $fx_value))
    {
        return "$message (invalid characters)";
    }

    // Check issuers. Start with a list of card types and issuers.
    $cardtypes = array(
        array('issuer'=>'VISA',         'alg'=>'mod10', 'format'=>'^4(.{12}|.{15})$'),
        array('issuer'=>'MASTERCARD',   'alg'=>'mod10', 'format'=>'^5[1-5].{14}$'),
        array('issuer'=>'AMEX',         'alg'=>'mod10', 'format'=>'^3[47].{13}$'),
        array('issuer'=>'Diners Club/Carte Blanche',
                                        'alg'=>'mod10', 'format'=>'^3(0[0-5].{11}|[68].{12})$'),
        array('issuer'=>'Discover',     'alg'=>'mod10', 'format'=>'^6011.{12}$'),
        array('issuer'=>'JCB',          'alg'=>'mod10', 'format'=>'^(3.{15}|(2131|1800).{11})$'),
        array('issuer'=>'enRoute',      'alg'=>'mod10', 'format'=>'^2(014|149).{11}$'),
        array('issuer'=>'SWITCH',       'alg'=>'mod10', 'format'=>'^([0-9]{19}|[0-9]{18}|[0-9]{16})$')
    );

    /* UK Switch cards (need to confirm this is the full list before imlementing it)
    * I am suspicious that some Switch card numbers are already identified as Visa
    * cards.
    * 19 digits:- 4936 633301 675901 675905 675918 675950-675962 675998
    * 18 digits:- 675938-675940 490302-490309 490335-490340 491174-491182
    * 16 digits:- 564182 633110 490525-490529 491100-491102 633300 633302-633399
                  675900 675902-675904 675906-675917 675919-675937 675941-675949
                  675963-675997 675999
    */

    unset($issuer);
    foreach($cardtypes as $cardtype)
    {
        if (ereg($cardtype['format'], $fx_value))
        {
            $issuer = $cardtype['issuer'];
            $algorithm = $cardtype['alg'];
            break;
        }
    }

    if (!isset($issuer))
    {
        return "$message (card type not recognised)";
    }

    // Now validate the check digit.
    if ($algorithm == 'mod10')
    {
        // Do mod10 check.

        // Reverse the number to make processing a little easier
        // since the RH-most digit is declared 'odd' regardless of
        // the total number of digits in the card number.
        $cardrev = strrev ($fx_value);

        // Loop through the card number one digit at a time.
        // Double the value of every second digit (starting from the RHS
        // of the original card number).
        // Concatenate the new values with the unaffected digits 
        $newdigits = '';
        for ($i = 0; $i < strlen($cardrev); ++$i)
        {
            $newdigits .= ($i % 2) ? $cardrev[$i] * 2 : $cardrev[$i];
        }

        // Sum the resulting digits.
        $digitsum = 0;
        for ($i = 0; $i < strlen($newdigits); ++$i)
        {
            $digitsum += $newdigits[$i];
        }

        // Valid card numbers will have a digit sum divisible by 10.
        if (($digitsum % 10) <> 0)
        {
            return "$message (the $issuer card number is not correct)";
        }
    }

    // A false return means 'no validation failure'.
    return false;
}

/*
 * Function to return a value from the form value cache.
 * This would generally be used to supply a default value
 * to a form.
 * A form value would be obtained using the following syntax
 * in FormExpress-
 *    {FEproc:DefItemValue&name='item-name'}
 */

function feproc_user_DefItemValue($args)
{
    extract ($args);

    if (!isset($name))
    {
        // No name supplied.
        return false;
    }

    // Get the feproc session object.
    if (!pnModAPILoad('feproc', 'session'))
    {
        //pnSessionSetVar('errormsg', _FXMODLOADFAILED);
        return false;
    }

    // Get the FEproc session information.
    $sessiondata = new feprocSession();

    // Get the form data array (form items collected so far).
    $formdata = $sessiondata->getFormData();

    if (isset($formdata[$name]))
    {
        return $formdata[$name];
    } else {
        return false;
    }
}

?>
