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
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
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

Loader::requireOnce('includes/pnForm.php');
require_once('pnclass/editsethandler.php');
require_once('pnclass/modifyconfighandler.php');


/**
 * the main administration function
 */
function feproc_admin_main()
{
    return pnModFunc('feproc', 'admin', 'viewsets');
}


function feproc_admin_view()
{
    // Just call viewsets. This had a different permissions check
    // previously so I have left it, but this should likely go away.
   if (!SecurityUtil::checkPermission ('FEproc::Set', '::', ACCESS_EDIT)) {
        return LogUtil::registerPermissionError();
    }

    return pnModFunc('feproc', 'admin', 'viewsets');
}


/*******************
 * SETS
 *******************/

/**
 * view set items
 */
function feproc_admin_viewsets()
{
    // Get parameters from whatever input we need.
    $startnum = pnVarCleanFromInput('startnum');

   if (!SecurityUtil::checkPermission ('FEproc::', '::', ACCESS_EDIT)) {
        return LogUtil::registerPermissionError();
    }

    // Load list of all sets
    $items = pnModAPIFunc('feproc', 'user', 'getallsets',
                          array('startnum' => $startnum,
                                'numitems' => pnModGetVar('FEproc', 'itemsperpage')));

    $render = pnRender::getInstance('feproc');
    $render->assign('items',$items);
    return $render->fetch('feproc_admin_viewsets.tpl');

}


/**
 * add new set item
 */
function feproc_admin_newset()
{
   if (!SecurityUtil::checkPermission ('FEproc::Set', '::', ACCESS_ADD)) {
        return LogUtil::registerPermissionError();
    }
    $render = FormUtil::newpnForm('feproc');
    $formobj = new feproc_admin_editsetHandler();
    return $render->pnFormExecute('feproc_admin_editset.tpl', $formobj);

}

/**
 * modify a set item
 * This is a standard function that is called whenever an administrator
 * wishes to modify a current module item
 * @param 'stageid' the id of the item to be modified
 * @param 'setid' the id of the set the item is in
 */
function feproc_admin_modifyset($args)
{
    if (!SecurityUtil::checkPermission ('FEproc::', '::', ACCESS_EDIT)) {
        return LogUtil::registerPermissionError();
    }

    // Get parameters from whatever input we need.
    $setid = FormUtil::getPassedValue('setid');

    // The API function is called.
    $item = pnModAPIFunc('feproc', 'user', 'getset', array('setid' => $setid));

    // TODO: better error
    if (!$item)
    {
        pnSessionSetVar('errormsg', 'The set does not exist');
        return pnRedirect(pnModURL('feproc', 'admin', 'view'));
    }

    $render = FormUtil::newpnForm('feproc');
    $formobj = new feproc_admin_editsetHandler();
    $formobj->setId($setid);
    return $render->pnFormExecute('feproc_admin_editset.tpl', $formobj);
    
    // Create output object.
    $output = new pnHTML();

    // Add menu to output.
    $output->SetInputMode(_PNH_VERBATIMINPUT);
    $output->Text(feproc_adminmenu());
    $output->SetInputMode(_PNH_PARSEINPUT);

    // Page title.
    $output->Title("Modify Set"); //TODO

    // Start form.
    $output->FormStart(pnModURL('feproc', 'admin', 'updateset'));

    // Add an authorisation ID.
    $output->FormHidden('authid', pnSecGenAuthKey());

    // Add a hidden variable for the item id.
    $output->FormHidden('setid', pnVarPrepForDisplay($setid));

    // Start the table that holds the information to be input.
    $output->TableStart();

    // Name
    $row = array();
    $output->SetOutputMode(_PNH_RETURNOUTPUT);
    $row[] = $output->Text(pnVarPrepForDisplay(_FXNAME));
    $row[] = $output->FormText('name', $item['name'], 32, 64);
    $output->SetOutputMode(_PNH_KEEPOUTPUT);
    $output->SetInputMode(_PNH_VERBATIMINPUT);
    $output->TableAddrow($row, 'left');
    $output->SetInputMode(_PNH_PARSEINPUT);

    // Description
    $row = array();
    $output->SetOutputMode(_PNH_RETURNOUTPUT);
    $row[] = $output->Text(pnVarPrepForDisplay(_FXDESCRIPTION));
    $row[] = $output->FormText('description', $item['description'], 64, 200);
    $output->SetOutputMode(_PNH_KEEPOUTPUT);
    $output->SetInputMode(_PNH_VERBATIMINPUT);
    $output->TableAddrow($row, 'left');
    $output->SetInputMode(_PNH_PARSEINPUT);

    // Get stages for drop-down lists.
    $stages = pnModAPIFunc('feproc', 'user', 'getallstages',
                          array('setid' => $setid));

    if (is_array($stages))
    {
        // TODO: make the default starting stage a simple attribute. The upgrade
        // would need to do some conversion to do this.
        // Create stages drop-down list.
        $data = Array();
        $data[] = Array('id' => '0', 'name' => '- None -');
        foreach ($stages as $stage)
        {
            $data[] = Array('id' => $stage['stageid'], 'name' => "$stage[stageid]: $stage[name]");
        }

        // Default starting stage select.
        $row = Array();
        $output->SetOutputMode(_PNH_RETURNOUTPUT);
        $row[] = $output->Text(pnVarPrepForDisplay('Default starting stage: ')); //TODO: ml
        $row[] = $output->FormSelectMultiple('startstageid', $data, 0, 1, $item['startstageid']);
        $output->SetOutputMode(_PNH_KEEPOUTPUT);
        $output->SetInputMode(_PNH_VERBATIMINPUT);
        $output->TableAddrow($row, 'left');
        $output->SetInputMode(_PNH_PARSEINPUT);

        // Default timeout stage select.
        $row = Array();
        $output->SetOutputMode(_PNH_RETURNOUTPUT);
        $row[] = $output->Text(pnVarPrepForDisplay('Stage on timeout: ')); //TODO: ml
        $row[] = $output->FormSelectMultiple('attributes[timeoutstageid]', $data, 0, 1, $item['attributes']['timeoutstageid']);
        $output->SetOutputMode(_PNH_KEEPOUTPUT);
        $output->SetInputMode(_PNH_VERBATIMINPUT);
        $output->TableAddrow($row, 'left');
        $output->SetInputMode(_PNH_PARSEINPUT);

        // Stage on enexpected error.
        $row = Array();
        $output->SetOutputMode(_PNH_RETURNOUTPUT);
        $row[] = $output->Text(pnVarPrepForDisplay('Stage on error: ')); //TODO: ml
        $row[] = $output->FormSelectMultiple('attributes[errorstageid]', $data, 0, 1, $item['attributes']['errorstageid']);
        $output->SetOutputMode(_PNH_KEEPOUTPUT);
        $output->SetInputMode(_PNH_VERBATIMINPUT);
        $output->TableAddrow($row, 'left');
        $output->SetInputMode(_PNH_PARSEINPUT);
    }

    $output->TableEnd();

    // End form
    $output->Linebreak(2);
    $output->FormSubmit('Update Set'); //TODO
    $output->FormEnd();
    
    // Return the output that has been generated by this function
    return $output->GetOutput();
}


/**
 * This is a standard function that is called with the results of the
 * form supplied by template_admin_modify() to update a current item
 * @param 'tid' the id of the template to be updated
 * @param 'name' the name of the template to be updated
 * @param 'description' the description of the template to be updated
 * @param 'template' the actual template
 */
function feproc_admin_updateset($args)
{
    // Get parameters from whatever input we need.
    list($setid, $name, $description, $startstageid) = pnVarCleanFromInput(
        'setid', 'name', 'description', 'startstageid'
    );

    // User functions of this type can be called by other modules.
    extract($args);
                            
    // Confirm authorisation code.
    if (!pnSecConfirmAuthKey()) {
        pnSessionSetVar('errormsg', _FXBADAUTHKEY);
        pnRedirect(pnModURL('feproc', 'admin', 'view'));
        return true;
    }

    // Load API.
    if (!pnModAPILoad('feproc', 'admin')) {
        pnSessionSetVar('errormsg', _FXMODLOADFAILED);
        pnRedirect(pnModURL('feproc', 'admin', 'view'));
        return true;
    }

    // TODO: validate parameters.

    // The API function is called.
    // TODO: success and failure stage IDs.
    if(pnModAPIFunc('feproc', 'admin', 'updateset',
                    array('setid' => $setid,
                          'name' => $name,
                          'description' => $description,
                          'startstageid' => $startstageid)))
    {
        // Success
        pnSessionSetVar('statusmsg', _FXTEMPLATEUPDATED);
    }

    pnRedirect(pnModURL('feproc', 'admin', 'viewsets'));

    // Return
    return true;
}

function feproc_admin_deleteset($args)
{
    // Get parameters from whatever input we need.
    list($setid, $objectid, $confirmation)
        = pnVarCleanFromInput('setid', 'objectid', 'confirmation');


    // User functions of this type can be called by other modules.
    extract($args);

    if (!empty($objectid))
    {
        $id = $objectid;
    }

    $output = new pnHTML();

    // Security check.
    if (!pnSecAuthAction(0, 'FEproc::Set', "::$setid", ACCESS_DELETE)) {
        $output->Text(_FXNOAUTH);
        return $output->GetOutput();
    }

    // Load admin API.
    if (!pnModAPILoad('feproc', 'admin'))
    {
        $output->Text(_FXMODLOADFAILED . ' feproc:admin');
        return $output->GetOutput();
    }

    // Load workflow user API.
    if (!pnModAPILoad('feproc', 'user'))
    {
        $output->Text(_FXMODLOADFAILED . ' feproc:user');
        return $output->GetOutput();
    }

    // The user API function is called.
    $item = pnModAPIFunc('feproc', 'user', 'getset', array('setid' => $setid));

    if ($item == false) {
        $output->Text('Set does not exist');
        return $output->GetOutput();
    }

    // Check for confirmation. 
    if (empty($confirmation))
    {
        // No confirmation yet - display a suitable form to obtain confirmation
        // of this action from the user

        // Create output object.
        $output = new pnHTML();

        // Add menu to output.
        $output->SetInputMode(_PNH_VERBATIMINPUT);
        $output->Text(feproc_adminmenu());
        $output->SetInputMode(_PNH_PARSEINPUT);

        // Title.
        $output->Title('Delete Set');

        // Add confirmation to output.
        $output->ConfirmAction('Delete set' . " '$item[name]'",
                               pnModURL('feproc', 'admin', 'deleteset'),
                               'Cancel set delete',
                               pnModURL('feproc', 'admin', 'viewsets'),
                               array('setid' => $setid)
            );

        // Return the output that has been generated by this function
        return $output->GetOutput();
    }

    // If we get here it means that the user has confirmed the action

    // Confirm authorisation code.
    if (!pnSecConfirmAuthKey()) {
        pnSessionSetVar('errormsg', _FXBADAUTHKEY);
        pnRedirect(pnModURL('feproc', 'admin', 'viewsets'));
        return true;
    }

    // The API function is called.
    
    if (pnModAPIFunc('feproc', 'admin', 'deleteset',
        array('setid' => $setid))) {
        // Success
        pnSessionSetVar('statusmsg', 'Set deleted');
    }

    // This function generated no output, and so now it is complete we redirect
    // the user to an appropriate page for them to carry on their work.
    pnRedirect(pnModURL('feproc', 'admin', 'viewsets'));
    
    // Return
    return true;
}





/*******************
 * STAGES
 *******************/

/**
 * view set items
 */
function feproc_admin_viewstages()
{
    // Get parameters from whatever input we need.
    list($startnum, $setid) = pnVarCleanFromInput('startnum', 'setid');

    // Create output object - this object will store all of our output so that
    // we can return it easily when required.
    $output = new pnHTML();

    if (!pnSecAuthAction(0, 'FEproc::', '::', ACCESS_EDIT)) {
        $output->Text(_FXNOAUTH);
        return $output->GetOutput();
    }

    // Add menu to output.
    $output->SetInputMode(_PNH_VERBATIMINPUT);
    $output->Text(feproc_adminmenu());
    $output->SetInputMode(_PNH_PARSEINPUT);

    // Load API. If the API fails to load an appropriate
    // error message is posted and the function returns
    if (!pnModAPILoad('feproc', 'admin')) {
        pnSessionSetVar('errormsg', _FXMODLOADFAILED);
        pnRedirect(pnModURL('feproc', 'admin', 'view')); // TODO
        return true;
    }

    // Load workflow API.
    if (!pnModAPILoad('feproc', 'user')) {
        pnSessionSetVar('errormsg', _FXMODLOADFAILED);
        pnRedirect(pnModURL('feproc', 'admin', 'view')); // TODO
        return true;
    }

    // The API function is called.  This takes the number of items
    // required and the first number in the list of all items, which we
    // obtained from the input and gets us the information on the appropriate
    // items.
    $items = pnModAPIFunc('feproc', 'user', 'getallstages',
                          array('setid' => $setid,
                                'startnum' => $startnum,
                                'numitems' => pnModGetVar('FEproc',
                                                          'itemsperpage')));
    // Start output table
    $output->TableStart('Set Stages', //TODO
                        array(_FXNAMEID,
                              _FXDESCRIPTION,
                              'Type', //TODO
                              'ID: Success Stage', //TODO
                              'ID: Failure Stage', //TODO
                              _FXOPTIONS), 1);

    // TODO: handle case of no stages.
    if (is_array($items))
    {
        foreach ($items as $item)
        {
            $row = array();

            // Output whatever we found
            $row[] = "$item[stageid]: $item[name]";
            $row[] = $item['description'];
            $row[] = $item['type'];

            if($item['successid'])
            {
                $row[] = $item['successid'] . ': ' . $item['successname'];
            } else {
                $row[] = null;
            }

            if($item['failureid'])
            {
                $row[] = $item['failureid'] . ': ' . $item['failurename'];
            } else {
                $row[] = null;
            }

            // Options for the item
            $options = array();
            $output->SetOutputMode(_PNH_RETURNOUTPUT);
            $options[] = $output->URL(pnModURL('feproc', 'admin', 'modifystage',
                array('stageid' => $item['stageid'], 'setid' => $item['setid'])), _FXEDIT);
            $options[] = $output->URL(pnModURL('feproc', 'admin', 'deletestage',
                array('stageid' => $item['stageid'], 'setid' => $item['setid'])), _FXDELETE);

            if ($item['startstage'])
            {
                $options[] = $output->URL(
                    pnModAPIFunc('feproc', 'user', 'stageurl',
                        array('stageid' => $item['stageid'])
                    ), 'Start'
                );
                $options[] = $output->URL(
                    pnModAPIFunc('feproc', 'user', 'stageurl',
                        array('stageid' => $item['stageid'], 'reset' => '1')
                    ), 'Restart'
                );
            }
            
            $options = join(' | ', $options);
            $output->SetInputMode(_PNH_VERBATIMINPUT);
            $row[] = $output->Text($options);
            $output->SetOutputMode(_PNH_KEEPOUTPUT);
            $output->TableAddRow($row, 'left');
            $output->SetInputMode(_PNH_PARSEINPUT);
        }
    }

    $output->TableEnd();

    // If we are showing just one set, then provide options to create stages.
    if ($setid)
    {
        $output->FormStart(pnModURL('feproc', 'admin', 'newstage', Array('setid' => $setid)));

        // TODO: ml constants
        $data = Array(
            Array('id' => 'display', 'name' => 'display: Display a templated page'),
            Array('id' => 'formexpress', 'name' => 'formexpress: FormExpress form'),
            //Array('id' => 'form', 'name' => 'form: Custom form'),
            Array('id' => 'validate', 'name' => 'validate: Validate form data'),
            Array('id' => 'transform', 'name' => 'transform: Transform the data collected so far'),
            Array('id' => 'transmit', 'name' => 'transmit: Send or store the data'),
            Array('id' => 'redirect', 'name' => 'redirect: Jump to a URL')
        );

        $output->Text(pnVarPrepForDisplay('Stage type: ')); //TODO: ml
        $output->FormSelectMultiple('handlertype', $data, 0, 1, $item['handlerid']);

        $output->FormSubmit('New Stage'); //TODO: ml
        $output->FormEnd();
    }

    // Call the pnHTML helper function to produce a pager in case of there
    // being many items to display.
    $output->Pager($startnum,
                    pnModAPIFunc('feproc', 'user', 'countstages', Array('setid' => $setid)),
                    pnModURL('feproc', 'admin', 'viewstages',
                            array('startnum' => '%%', 'setid' => $setid)),
                    pnModGetVar('FEproc', 'itemsperpage'));


    $modinfo = pnModGetInfo(pnModGetIDFromName('feproc'));
    
    // Return the output that has been generated by this function
    return $output->GetOutput();
}

/**
 * add new stage item
 * This is a standard function that is called whenever an administrator
 * wishes to create a new module item
 */
function feproc_admin_newstage()
{
    if (!pnModAPILoad('feproc', 'admin')) {
        pnSessionSetVar('errormsg', _FXMODLOADFAILED);
        pnRedirect(pnModURL('feproc', 'admin', 'view')); // TODO
        return true;
    }

    // Handlers user API
    if (!pnModAPILoad('feproc', 'handleruser')) {
        pnSessionSetVar('errormsg', _FXMODLOADFAILED);
        pnRedirect(pnModURL('feproc', 'admin', 'view')); // TODO
        return true;
    }

    list($setid, $type) = pnVarCleanFromInput('setid', 'handlertype');

    // Create output object.
    $output = new pnHTML();

    // Security check.
    if (!pnSecAuthAction(0, 'FEproc::Stage', '::', ACCESS_ADD)) {
        $output->Text(_FXNOAUTH);
        return $output->GetOutput();
    }

    // Add menu to output.
    $output->SetInputMode(_PNH_VERBATIMINPUT);
    $output->Text(feproc_adminmenu());
    $output->SetInputMode(_PNH_PARSEINPUT);

    // Title.
    $output->Title(_FXADDTEMPLATE);

    // Start form.
    $output->FormStart(pnModURL('feproc', 'admin', 'createstage'));

    // Add an authorisation ID.
    $output->FormHidden('authid', pnSecGenAuthKey());

    $output->FormHidden('handlertype', $type);
    $output->FormHidden('setid', $setid);

    // Start the table that holds the information to be input.
    $output->TableStart();

    // Handler type (display)
    $row = array();
    $output->SetOutputMode(_PNH_RETURNOUTPUT);
    $row[] = $output->Text(pnVarPrepForDisplay('Handler type')); //TODO
    $row[] = $output->Text(pnVarPrepForDisplay($type));
    $output->SetOutputMode(_PNH_KEEPOUTPUT);
    $output->SetInputMode(_PNH_VERBATIMINPUT);
    $output->TableAddrow($row, 'left');
    $output->SetInputMode(_PNH_PARSEINPUT);

    // Handler - FormExpress type.
    // TODO: get list of forms from FormExpress module.
    if ($type == 'formexpress' || $type == 'form')
    {
        $row = array();
        $output->SetOutputMode(_PNH_RETURNOUTPUT);
        $row[] = $output->Text(pnVarPrepForDisplay('Form ID')); //TODO
        $row[] = $output->FormText('hid', $item['handlerid'], 32, 32);
        $output->SetOutputMode(_PNH_KEEPOUTPUT);
        $output->SetInputMode(_PNH_VERBATIMINPUT);
        $output->TableAddrow($row, 'left');
        $output->SetInputMode(_PNH_PARSEINPUT);
    } else {
        // Handler - drop-down list based on handler type.
        // Get the list of handlers.
            $data = Array();
        if ($handlerlist = pnModAPIFunc(
            'feproc', 'handleruser', 'getallhandlers',
            array('type' => $type)))
        {
            foreach ($handlerlist as $handler)
            {
                $data[] = Array(
                    'id' => $handler['hid'],
                    'name' => $handler['modulename'] . ': ' . $handler['name']
                );
            }
        } else {
            $data[] = Array('hid' => '0', 'name' => _FXHANDLERNONE);
        }

        // Handler
        $row = array();
        $output->SetOutputMode(_PNH_RETURNOUTPUT);
        $row[] = $output->Text(pnVarPrepForDisplay('Handler Name')); //TODO
        $row[] = $output->FormSelectMultiple('hid', $data, 0, 1, $item['handlerid']);
        $output->SetOutputMode(_PNH_KEEPOUTPUT);
        $output->SetInputMode(_PNH_VERBATIMINPUT);
        $output->TableAddrow($row, 'left');
        $output->SetInputMode(_PNH_PARSEINPUT);
    }
    
    // Name
    $row = array();
    $output->SetOutputMode(_PNH_RETURNOUTPUT);
    $row[] = $output->Text(pnVarPrepForDisplay(_FXNAME));
    $row[] = $output->FormText('name', 'Stage name', 32, 64); //TODO
    $output->SetOutputMode(_PNH_KEEPOUTPUT);
    $output->SetInputMode(_PNH_VERBATIMINPUT);
    $output->TableAddrow($row, 'left');
    $output->SetInputMode(_PNH_PARSEINPUT);

    // Description
    $row = array();
    $output->SetOutputMode(_PNH_RETURNOUTPUT);
    $row[] = $output->Text(pnVarPrepForDisplay(_FXDESCRIPTION));
    $row[] = $output->FormText('description', 'Stage Description', 64, 255);
    $output->SetOutputMode(_PNH_KEEPOUTPUT);
    $output->SetInputMode(_PNH_VERBATIMINPUT);
    $output->TableAddrow($row, 'left');
    $output->SetInputMode(_PNH_PARSEINPUT);

    $output->TableEnd();

    // End form
    $output->Linebreak(2);
    $output->FormSubmit('Add Stage'); //TODO
    $output->FormEnd();

    // Return the output that has been generated by this function
    return $output->GetOutput();
}


/**
 * This is a standard function that is called with the results of the
 * form supplied by template_admin_new() to create a new item
 * @param 'name' the name of the item to be created TODO
 * @param 'number' the number of the item to be created TODO
 */
function feproc_admin_createstage($args)
{
    // Get parameters.
    list($setid, $handlertype, $hid, $name, $description) = pnVarCleanFromInput(
        'setid', 'handlertype', 'hid', 'name', 'description'
    );

    extract($args);

    // Confirm authorisation code.
    if (!pnSecConfirmAuthKey()) {
        pnSessionSetVar('errormsg', _FXBADAUTHKEY);
        pnRedirect(pnModURL('feproc', 'admin', 'view'));
        return true;
    }

    // Load API.
    if (!pnModAPILoad('feproc', 'admin')) {
        pnSessionSetVar('errormsg', _FXMODLOADFAILED);
        pnRedirect(pnModURL('feproc', 'admin', 'view'));
        return true;
    }

    // TODO: validate parameters.
    // ...

    // The API function is called.
    $stageid = pnModAPIFunc('feproc', 'admin', 'createstage',
        array(
            'setid' => $setid,
            'name' => $name,
            'description' => $description,
            'handlertype' => $handlertype,
            'hid' => $hid
        )
    );

    // The return value of the function is checked here, and if the function
    // suceeded then an appropriate message is posted.  Note that if the
    // function did not succeed then the API function should have already
    // posted a failure message so no action is required.
    if ($stageid != false) {
        // Success
        pnSessionSetVar('statusmsg', "Stage Created"); //TODO
        pnRedirect(pnModURL('feproc', 'admin', 'modifystage', Array('setid' => $setid, 'stageid' => $stageid)));
        return true;
    } else {
        pnSessionSetVar('statusmsg', "Could not create stage"); //TODO
        pnRedirect(pnModURL('feproc', 'admin', 'modifyset', Array('setid' => $setid)));
    }

    // Return
    return true;
}

/**
 * modify a stage item
 * This is a standard function that is called whenever an administrator
 * wishes to modify a current module item
 * @param 'stageid' the id of the item to be modified
 * @param 'setid' the id of the set the item is in
 */
function feproc_admin_modifystage($args)
{
    // Get parameters from whatever input we need.
    list($stageid, $setid) = pnVarCleanFromInput('stageid', 'setid');

    extract($args);

    // Create output object.
    $output = new pnHTML();

    // Security check.
    if (!pnSecAuthAction(0, 'FEproc::Stage', "::$stageid", ACCESS_EDIT))
    {
        pnSessionSetVar('errormsg', _FXNOAUTH);
        pnRedirect(pnModURL('feproc', 'admin', 'view')); // TODO
        return true;
    }
/*
    // Load admin API.
    if (!pnModAPILoad('feproc', 'admin'))
    {
        pnSessionSetVar('errormsg', _FXMODLOADFAILED);
        pnRedirect(pnModURL('feproc', 'admin', 'view')); // TODO
        return true;
    }

    // Load workflow user API.
    if (!pnModAPILoad('feproc', 'user'))
    {
        pnSessionSetVar('errormsg', _FXMODLOADFAILED);
        pnRedirect(pnModURL('feproc', 'admin', 'view')); // TODO
        return true;
    }

    // Load handler user API.
    if (!pnModAPILoad('feproc', 'handleruser'))
    {
        pnSessionSetVar('errormsg', _FXMODLOADFAILED);
        pnRedirect(pnModURL('feproc', 'admin', 'view')); // TODO
        return true;
    }
*/
    // The API function is called.
    $item = pnModAPIFunc('feproc', 'user', 'getstage', array('stageid' => $stageid));

    // TODO: better error
    if (! $item)
    {
        $output->Text('Stage does not exist');
        return $output->GetOutput();
    }

    // Add menu to output.
    $output->SetInputMode(_PNH_VERBATIMINPUT);
    $output->Text(feproc_adminmenu());
    $output->SetInputMode(_PNH_PARSEINPUT);

    // Page title.
    $output->Title("Modify Stage"); //TODO

    // Start form.
    $output->FormStart(pnModURL('feproc', 'admin', 'updatestage'));

    // Add an authorisation ID.
    $output->FormHidden('authid', pnSecGenAuthKey());

    // Add a hidden variable for the item id.
    $output->FormHidden('setid', pnVarPrepForDisplay($setid));
    $output->FormHidden('stageid', pnVarPrepForDisplay($stageid));

    // Start the table that holds the information to be input.
    $output->TableStart();

    // Handler type (display only - not a form item)
    $row = array();
    $output->SetOutputMode(_PNH_RETURNOUTPUT);
    $row[] = $output->Text(pnVarPrepForDisplay("Handler Type")); //TODO
    $row[] = $output->Text(pnVarPrepForDisplay($item['type']));
    $output->SetOutputMode(_PNH_KEEPOUTPUT);
    $output->SetInputMode(_PNH_VERBATIMINPUT);
    $output->TableAddrow($row, 'left');
    $output->SetInputMode(_PNH_PARSEINPUT);

    // Handler ID - Form or FormExpress type.
    $handler = false;
    if ($item['type'] == 'form' || $item['type'] == 'formexpress')
    {
        $row = array();
        $output->SetOutputMode(_PNH_RETURNOUTPUT);
        $row[] = $output->Text(pnVarPrepForDisplay('Form ID')); //TODO
        $row[] = $output->FormText('hid', $item['handlerid'], 32, 32);
        $output->SetOutputMode(_PNH_KEEPOUTPUT);
        $output->SetInputMode(_PNH_VERBATIMINPUT);
        $output->TableAddrow($row, 'left');
        $output->SetInputMode(_PNH_PARSEINPUT);
    } else {
        // Handler - just display the name (cannot change as the attributes may be
        // different for a different handler).
        if ($handler = pnModAPIFunc(
            'feproc', 'handleruser', 'gethandler',
            array('hid' => $item['handlerid'])))
        {
            // Handler
            $handlerhelpurl = pnModURL('feproc', 'handleradmin', 'helphandler', array('hid' => $item['handlerid']));
            $output->SetInputMode(_PNH_VERBATIMINPUT);
            $row = array(
                pnVarPrepForDisplay('Handler Name'), //TODO
                '<a target="_new" href="'.$handlerhelpurl.'">'.pnVarPrepForDisplay($handler['name']).'</a>'
            );
            $output->SetInputMode(_PNH_PARSEINPUT);
            $output->SetInputMode(_PNH_VERBATIMINPUT);
            $output->TableAddrow($row, 'left');
            $output->SetInputMode(_PNH_PARSEINPUT);

            $row = array(
                pnVarPrepForDisplay('Handler Source'), //TODO
                pnVarPrepForDisplay($handler['source'])
            );
            $output->SetInputMode(_PNH_VERBATIMINPUT);
            $output->TableAddrow($row, 'left');
            $output->SetInputMode(_PNH_PARSEINPUT);
        }
    }

    // Name
    $row = array();
    $output->SetOutputMode(_PNH_RETURNOUTPUT);
    $row[] = $output->Text(pnVarPrepForDisplay(_FXNAME));
    $row[] = $output->FormText('name', $item['name'], 32, 64);
    $output->SetOutputMode(_PNH_KEEPOUTPUT);
    $output->SetInputMode(_PNH_VERBATIMINPUT);
    $output->TableAddrow($row, 'left');
    $output->SetInputMode(_PNH_PARSEINPUT);

    // Description
    $row = array();
    $output->SetOutputMode(_PNH_RETURNOUTPUT);
    $row[] = $output->Text(pnVarPrepForDisplay(_FXDESCRIPTION));
    $row[] = $output->FormText('description', $item['description'], 64, 200);
    $output->SetOutputMode(_PNH_KEEPOUTPUT);
    $output->SetInputMode(_PNH_VERBATIMINPUT);
    $output->TableAddrow($row, 'left');
    $output->SetInputMode(_PNH_PARSEINPUT);

    // Secure stage
    if ($item['type'] == 'form' || $item['type'] == 'formexpress'
    ||  $item['type'] == 'redirect' || $item['type'] == 'display')
    {
        $row = array();
        $output->SetOutputMode(_PNH_RETURNOUTPUT);
        $row[] = $output->Text(pnVarPrepForDisplay('Secure stage (https)')); //TODO: ml
        $row[] = $output->FormCheckbox('secure', $item['secure']);
        $output->SetOutputMode(_PNH_KEEPOUTPUT);
        $output->SetInputMode(_PNH_VERBATIMINPUT);
        $output->TableAddrow($row, 'left');
        $output->SetInputMode(_PNH_PARSEINPUT);
    }

    // Starting stage stage
    // If this is the default starting stage, then don't allow option to be turned off.
    $row = array();
    $output->SetOutputMode(_PNH_RETURNOUTPUT);
    $row[] = $output->Text(pnVarPrepForDisplay('Starting stage')); //TODO: ml
    if ($item['startstage'] == 2)
    {
        $row[] = $output->Text('Default set starting stage');
        $row[] = $output->FormHidden('startstage', $item['startstage']);
    } else {
        $row[] = $output->FormCheckbox('startstage', $item['startstage']);
    }
    $output->SetOutputMode(_PNH_KEEPOUTPUT);
    $output->SetInputMode(_PNH_VERBATIMINPUT);
    $output->TableAddrow($row, 'left');
    $output->SetInputMode(_PNH_PARSEINPUT);

    // Next stage on success.
    $stages = pnModAPIFunc('feproc', 'user', 'getallstages',
                          array('setid' => $setid));

    if (is_array($stages))
    {
        $data = Array();
        $data[] = Array('id' => '', 'name' => '- None -');
        $row = Array();
        foreach ($stages as $stage)
        {
            if ($stage['stageid'] != $item['stageid'])
            {
                $data[] = Array('id' => $stage['stageid'], 'name' => "$stage[stageid]: $stage[name]");
            }
        }
        $output->SetOutputMode(_PNH_RETURNOUTPUT);
        $row[] = $output->Text(pnVarPrepForDisplay('Stage on success: ')); //TODO: ml
        $row[] = $output->FormSelectMultiple('successid', $data, 0, 1, $item['successid']);
        $output->SetOutputMode(_PNH_KEEPOUTPUT);
        $output->SetInputMode(_PNH_VERBATIMINPUT);
        $output->TableAddrow($row, 'left');
        $output->SetInputMode(_PNH_PARSEINPUT);
    }

    // Next stage on failure.
    if (is_array($stages))
    {
        $data = Array();
        $data[] = Array('id' => '', 'name' => '- None -');
        $row = Array();
        foreach ($stages as $stage)
        {
            if ($stage['stageid'] != $item['stageid'])
            {
                $data[] = Array('id' => $stage['stageid'], 'name' => "$stage[stageid]: $stage[name]");
            }
        }
        $output->SetOutputMode(_PNH_RETURNOUTPUT);
        $row[] = $output->Text(pnVarPrepForDisplay('Stage on failure: ')); //TODO: ml
        $row[] = $output->FormSelectMultiple('failureid', $data, 0, 1, $item['failureid']);
        $output->SetOutputMode(_PNH_KEEPOUTPUT);
        $output->SetInputMode(_PNH_VERBATIMINPUT);
        $output->TableAddrow($row, 'left');
        $output->SetInputMode(_PNH_PARSEINPUT);
    }


    // TODO: the attributes.
    //var_dump($handler);
    if ($handler && is_array($handler['attributes']))
    {
        // Blank table row.
        $output->SetInputMode(_PNH_VERBATIMINPUT);
        $output->TableAddrow(array('<hr/>', NULL), 'left');
        $output->SetInputMode(_PNH_PARSEINPUT);

        foreach ($handler['attributes'] as $key => $attribute)
        {
            // TODO: don't treat everything as a text item! => $attribute['type']
            // TODO: checkbox type (0/1).
            $row = array();
            $output->SetOutputMode(_PNH_RETURNOUTPUT);
            $row[] = $output->Text(pnVarPrepForDisplay($attribute['description'] . ' {attribute:' .$key. '}'));
            if ($attribute['type'] == 'text')
            {
                // Multi-line text item.
                $row[] = $output->FormTextArea("attributes[$key]", $item['attributes'][$key],
					pnModGetVar('FEproc', 'attrtextrows'), pnModGetVar('FEproc', 'attrtextcols'));
            } elseif ($attribute['type'] == 'list')
            {
                // Selection list.
                $list = array();
                foreach($attribute['list'] as $listitem)
                {
                    $list[] = Array('id' => $listitem, 'name' => $listitem);
                } 
                $row[] = $output->FormSelectMultiple("attributes[$key]", $list, 0, 1, $item['attributes'][$key]);
            } else {
                // Default: plain text field.
                $row[] = $output->FormText("attributes[$key]", $item['attributes'][$key],
					pnModGetVar('FEproc', 'attrstringsize'), pnModGetVar('FEproc', 'attrstringlen'));
            }
            $output->SetOutputMode(_PNH_KEEPOUTPUT);
            $output->SetInputMode(_PNH_VERBATIMINPUT);
            $output->TableAddrow($row, 'left');
            $output->SetInputMode(_PNH_PARSEINPUT);
        }
    }

    $output->TableEnd();

    // End form
    $output->Linebreak(2);
    $output->FormSubmit('Update Stage'); //TODO
    $output->FormEnd();
    
    // Return the output that has been generated by this function
    return $output->GetOutput();
}


/**
 * This is a standard function that is called with the results of the
 * form supplied by template_admin_modify() to update a current item
 * @param 'tid' the id of the template to be updated
 * @param 'name' the name of the template to be updated
 * @param 'description' the description of the template to be updated
 * @param 'template' the actual template
 */
function feproc_admin_updatestage($args)
{
    // Get parameters from whatever input we need.
    list($setid, $stageid, $name, $description, $attributes, $secure, $successid, $failureid, $startstage)
    = pnVarCleanFromInput(
        'setid', 'stageid', 'name', 'description', 'attributes', 'secure', 'successid', 'failureid', 'startstage'
    );

    // User functions of this type can be called by other modules.
    extract($args);
                            
    // Confirm authorisation code.
    if (!pnSecConfirmAuthKey()) {
        pnSessionSetVar('errormsg', _FXBADAUTHKEY);
        pnRedirect(pnModURL('feproc', 'admin', 'view'));
        return true;
    }

    // Load API.
    if (!pnModAPILoad('feproc', 'admin')) {
        pnSessionSetVar('errormsg', _FXMODLOADFAILED);
        pnRedirect(pnModURL('feproc', 'admin', 'view'));
        return true;
    }

    // TODO: validate parameters.

    // The API function is called.
    // TODO: success and failure stage IDs.
    if(pnModAPIFunc('feproc', 'admin', 'updatestage',
                    array('id' => $stageid,
                          'name' => $name,
                          'description' => $description,
                          'secure' => $secure,
                          'successid' => $successid,
                          'failureid' => $failureid,
                          'attributes' => $attributes,
                          'startstage' => $startstage)))
    {
        // Success
        pnSessionSetVar('statusmsg', 'Stage updated');
    }

    // This function generated no output, and so now it is complete we redirect
    // the user to an appropriate page for them to carry on their work
    if ($setid)
    {
        pnRedirect(pnModURL('feproc', 'admin', 'viewstages', array('setid' => $setid)));
    } else {
        pnRedirect(pnModURL('feproc', 'admin', 'view'));
    }

    // Return
    return true;
}

function feproc_admin_deletestage($args)
{
    // Get parameters from whatever input we need.
    list($stageid, $setid, $objectid, $confirmation)
        = pnVarCleanFromInput('stageid', 'setid', 'objectid', 'confirmation');


    extract($args);

    if (!empty($objectid))
    {
        $stageid = $objectid;
    }

    $output = new pnHTML();

    // Security check.
    if (!pnSecAuthAction(0, 'FEproc::Stage', "::$stageid", ACCESS_DELETE)) {
        pnSessionSetVar('errormsg', _FXNOAUTH);
        pnRedirect(pnModURL('feproc', 'admin', 'view')); // TODO
        return true;
    }

    // Load admin API.
    if (!pnModAPILoad('feproc', 'admin'))
    {
        pnSessionSetVar('errormsg', _FXMODLOADFAILED);
        pnRedirect(pnModURL('feproc', 'admin', 'view')); // TODO
        return true;
    }

    // Load workflow user API.
    if (!pnModAPILoad('feproc', 'user'))
    {
        pnSessionSetVar('errormsg', _FXMODLOADFAILED);
        pnRedirect(pnModURL('feproc', 'admin', 'view')); // TODO
        return true;
    }

    // The user API function is called.
    $item = pnModAPIFunc('feproc', 'user', 'getstage', array('stageid' => $stageid));

    if ($item == false) {
        $output->Text('Stage does not exist');
        return $output->GetOutput();
    }

    // Check for confirmation. 
    if (empty($confirmation))
    {
        // No confirmation yet - display a suitable form to obtain confirmation
        // of this action from the user

        // Create output object.
        $output = new pnHTML();

        // Add menu to output.
        $output->SetInputMode(_PNH_VERBATIMINPUT);
        $output->Text(feproc_adminmenu());
        $output->SetInputMode(_PNH_PARSEINPUT);

        // Title.
        $output->Title('Delete Stage');

        // Add confirmation to output.
        $output->ConfirmAction('Delete stage' . " '$item[name]'",
                               pnModURL('feproc', 'admin', 'deletestage'),
                               'Cancel stage delete',
                               pnModURL('feproc', 'admin', 'viewstages', array('setid' => $setid)),
                               array('stageid' => $stageid, 'setid' => $setid)
            );

        // Return the output that has been generated by this function
        return $output->GetOutput();
    }

    // If we get here it means that the user has confirmed the action

    // Confirm authorisation code.
    if (!pnSecConfirmAuthKey()) {
        pnSessionSetVar('errormsg', _FXBADAUTHKEY);
        pnRedirect(pnModURL('feproc', 'admin', 'viewstages', array('setid' => $setid)));
        return true;
    }

    // The API function is called.
    
    if (pnModAPIFunc('feproc', 'admin', 'deletestage',
        array('stageid' => $stageid, 'setid' => $setid))) {
        // Success
        pnSessionSetVar('statusmsg', 'Stage deleted');
    }

    // This function generated no output, and so now it is complete we redirect
    // the user to an appropriate page for them to carry on their work
    pnRedirect(pnModURL('feproc', 'admin', 'viewstages', array('setid' => $setid)));
    
    // Return
    return true;
}





/*******************
 * GLOBAL
 *******************/


/**
 * This is a standard function to modify the configuration parameters of the
 * module
 */
function feproc_admin_modifyconfig()
{
   if (!SecurityUtil::checkPermission ('FEproc::Set', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }
    $render = FormUtil::newpnForm('feproc');
    $formobj = new feproc_admin_modifyconfigHandler();
    return $render->pnFormExecute('feproc_admin_modifyconfig.tpl', $formobj);
}

/**
 * Main administration menu
 */
function feproc_adminmenu()
{
    // Create output object - this object will store all of our output so that
    // we can return it easily when required
    $output = new pnHTML();

    // Display status message if any.  Note that in future this functionality
    // will probably be in the theme rather than in this menu, but this is the
    // best place to keep it for now
    $mes = pnGetStatusMsg();
    if (is_array($mes)) {
        foreach ($mes as $m) $output->Text($m);
    } else {
        $output->Text($mes);
    }

    // Start options menu
    $output->TableStart(_FXFETAX);
    $output->SetOutputMode(_PNH_RETURNOUTPUT);

    // Menu options.  These options are all added in a single row, to add
    // multiple rows of options the code below would just be repeated

    // ROW 1
    // n/a

    // ROW 2
    $output->SetOutputMode(_PNH_RETURNOUTPUT);
    $columns = array();
    $columns[] = $output->URL(pnModURL('feproc',
                                       'handleradmin',
                                       'view'),
                              /*TODO: _FXVIEWTEMPLATES*/ 'Show handlers'); 

    $columns[] = $output->URL(pnModURL('feproc',
                                       'handleradmin',
                                       'new'),
                              /*TODO: _FXNEWTEMPLATE*/ 'Import handler'); 

    $columns[] = $output->URL("modules/" . basename(dirname(__FILE__)) . "/docs/help.html",
                              _FXHELP); 

    $output->SetOutputMode(_PNH_KEEPOUTPUT);

    $output->SetInputMode(_PNH_VERBATIMINPUT);
    $output->TableAddRow($columns);
    $output->SetInputMode(_PNH_PARSEINPUT);

    // ROW 3
    $output->SetOutputMode(_PNH_RETURNOUTPUT);
    $columns = array();
    $columns[] = $output->URL(pnModURL('feproc',
                                       'admin',
                                       'viewsets'),
                              /*TODO: _FXVIEWTEMPLATES*/ 'Show sets'); 

    $columns[] = $output->URL(pnModURL('feproc',
                                       'admin',
                                       'newset'),
                              /*TODO: _FXNEWTEMPLATE*/ 'New set'); 

    $columns[] = $output->URL(pnModURL('feproc',
                                       'admin',
                                       'modifyconfig'),
                              /*TODO: _FXNEWTEMPLATE*/ 'Configuration'); 

    $output->SetOutputMode(_PNH_KEEPOUTPUT);

    $output->SetInputMode(_PNH_VERBATIMINPUT);
    $output->TableAddRow($columns);
    $output->SetInputMode(_PNH_PARSEINPUT);

    // END OF ROWS
    $output->TableEnd();

    // Return the output that has been generated by this function
    return $output->GetOutput();
}

?>
