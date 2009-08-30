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
 * view items
 */
function feproc_handleradmin_view()
{
    // Get parameters from whatever input we need.
    $startnum = pnVarCleanFromInput('startnum');

    // Create output object - this object will store all of our output so that
    // we can return it easily when required.
    $output = new pnHTML();

    if (!pnSecAuthAction(0, 'FEproc::Handler', '::', ACCESS_EDIT)) {
        $output->Text(_FXNOAUTH);
        return $output->GetOutput();
    }

    // Load the admin module so we have the menu.
    // TODO: rename menu function so proper call can be made.
    if (!pnModLoad('feproc', 'admin')) {
        $output->Text(_FXMODLOADFAILED);
        return $output->GetOutput();
    }

    // Add menu to output - it helps if all of the module pages have a standard
    // menu at their head to aid in navigation
    $output->SetInputMode(_PNH_VERBATIMINPUT);
    $output->Text(feproc_adminmenu());
    $output->SetInputMode(_PNH_PARSEINPUT);

    // Load API. If the API fails to load an appropriate
    // error message is posted and the function returns
    if (!pnModAPILoad('feproc', 'handleruser')) {
        $output->Text(_FXMODLOADFAILED);
        return $output->GetOutput();
    }

    // The API function is called.  This takes the number of items
    // required and the first number in the list of all items, which we
    // obtained from the input and gets us the information on the appropriate
    // items.
    $items = pnModAPIFunc('feproc', 'handleruser', 'getallhandlers',
                          array('startnum' => $startnum,
                                'numitems' => 10/* FIXME pnModGetVar('FEproc',
                                                          'itemsperpage')*/));

    // Start output table
    $output->TableStart(_FXFETAXHANDLERS,
                        array(_FXID,
                              _FXNAME,
                              _FXDESCRIPTION,
                              _FXTYPE,
                              _FXSOURCE,
                              _FXOPTIONS), 1);

    foreach ($items as $item)
    {
        $row = array();

        // Output whatever we found
        $row[] = "$item[hid]";
        $row[] = "$item[name]";
        $row[] = $item['description'];
        $row[] = $item['type'];
        $row[] = $item['source'];

        // Options for the item
        $options = array();
        $output->SetOutputMode(_PNH_RETURNOUTPUT);
        $options[] = $output->URL(pnModURL('feproc',
                                           'handleradmin',
                                           'helphandler',
                                           array('hid' => $item['hid'])),
                                          _FXHELP );
        $options[] = $output->URL(pnModURL('feproc',
                                           'handleradmin',
                                           'delete',
                                           array('hid' => $item['hid'])),
                                           _FXDELETE);

        $options = join(' | ', $options);
        $output->SetInputMode(_PNH_VERBATIMINPUT);
        $row[] = $output->Text($options);
        $output->SetOutputMode(_PNH_KEEPOUTPUT);
        $output->TableAddRow($row, 'left');
        $output->SetInputMode(_PNH_PARSEINPUT);
    }

    $row = array(null, null, null, null, _FXSOURCEKEY, null);
    $output->SetOutputMode(_PNH_KEEPOUTPUT);
    $output->SetInputMode(_PNH_VERBATIMINPUT);
    $output->TableAddRow($row, 'left');
    $output->SetInputMode(_PNH_PARSEINPUT);

    $output->TableEnd();

    // Call the pnHTML helper function to produce a pager in case of there
    // being many items to display.
    //
    // Note that this function includes another user API function.  The
    // function returns a simple count of the total number of items in the item
    // table so that the pager function can do its job properly
    $output->Pager($startnum,
                    pnModAPIFunc('feproc', 'handleruser', 'counthandlers'),
                    pnModURL('feproc',
                             'handleradmin',
                             'view',
                            array('startnum' => '%%')),
                    10 /* FIXME pnModGetVar('FEproc', 'itemsperpage')*/);


    $modinfo = pnModGetInfo(pnModGetIDFromName('feproc'));
    
    // Return the output that has been generated by this function
    return $output->GetOutput();
}

/**
 * delete item
 * @param 'hid' the id of the item to be deleted
 * @param 'confirmation' confirmation that this item can be deleted
 */
function feproc_handleradmin_delete($args)
{
    // Get parameters from whatever input we need.
    list($hid,
         $objectid,
         $confirmation) = pnVarCleanFromInput('hid',
                                              'objectid',
                                              'confirmation');


    // User functions of this type can be called by other modules.
    // pnVarCleanFromInput().
    extract($args);

    // Check to see if we have been passed $objectid.
    if (!empty($objectid))
    {
        $hid = $objectid;
    }

    $output = new pnHTML();

    // Early security check.
    if (!pnSecAuthAction(0, 'FEproc::Handler', "::$hid", ACCESS_DELETE)) {
        $output->Text(_FXNOAUTH);
        return $output->GetOutput();
    }

    // Load API. If the API fails to load an appropriate
    // error message is posted and the function returns
    if (!pnModAPILoad('feproc', 'handleradmin')) {
        $output->Text(_FXMODLOADFAILED);
        return $output->GetOutput();
    }

    if (!pnModAPILoad('feproc', 'handleruser')) {
        $output->Text(_FXMODLOADFAILED);
        return $output->GetOutput();
    }

    // Load main admin module so we can call up the menu.
    if (!pnModLoad('feproc', 'admin')) {
        $output->Text(_FXMODLOADFAILED);
        return $output->GetOutput();
    }

    // The user API function is called.
    $item = pnModAPIFunc('feproc',
                         'handleruser',
                         'gethandler',
                         array('hid' => $hid));

    if ($item == false) {
        $output->Text(_FXNOSUCHHANDLER);
        return $output->GetOutput();
    }

    // Check for confirmation. 
    if (empty($confirmation))
    {
        // No confirmation yet - display a suitable form to obtain confirmation
        // of this action from the user

        // Create output object - this object will store all of our output so
        // that we can return it easily when required
        $output = new pnHTML();

        // Add menu to output - it helps if all of the module pages have a
        // standard menu at their head to aid in navigation
        $output->SetInputMode(_PNH_VERBATIMINPUT);
        $output->Text(feproc_adminmenu());
        $output->SetInputMode(_PNH_PARSEINPUT);

        // Title - putting a title at the head of each page reminds the user
        // what they are doing
        $output->Title(_FXDELETEHANDLER);

        // Add confirmation to output.
        $output->ConfirmAction(_FXCONFIRMHANDLERDELETE . " '$item[name]'",
                               pnModURL('feproc',
                                        'handleradmin',
                                        'delete'),
                               _FXCANCELHANDLERDELETE,
                               pnModURL('feproc',
                                        'handleradmin',
                                        'view'),
                               array('hid' => $hid));

        // Return the output that has been generated by this function
        return $output->GetOutput();
    }

    // If we get here it means that the user has confirmed the action

    // Confirm authorisation code.
    if (!pnSecConfirmAuthKey()) {
        pnSessionSetVar('errormsg', _FXBADAUTHKEY);
        pnRedirect(pnModURL('feproc', 'handleradmin', 'view'));
        return true;
    }

    // The API function is called.
    if (pnModAPIFunc('feproc',
                     'handleradmin',
                     'delete',
                     array('hid' => $hid))) {
        // Success
        pnSessionSetVar('statusmsg', _FXHANDLERDELETED);
    }

    // This function generated no output, and so now it is complete we redirect
    // the user to an appropriate page for them to carry on their work
    pnRedirect(pnModURL('feproc', 'handleradmin', 'view'));
    
    // Return
    return true;
}

/**
 * add new item
 * This is a standard function that is called whenever an administrator
 * wishes to create a new module item
 */
function feproc_handleradmin_new()
{
    // For the menu.
    if (!pnModLoad('feproc', 'admin')) {
        pnSessionSetVar('errormsg', _FXMODLOADFAILED);
        pnRedirect(pnModURL('feproc', 'handleradmin', 'view'));
        return true;
    }

    if (!pnModAPILoad('feproc', 'handleradmin')) {
        pnSessionSetVar('errormsg', _FXMODLOADFAILED);
        pnRedirect(pnModURL('feproc', 'handleradmin', 'view'));
        return true;
    }

    // Create output object.
    $output = new pnHTML();

    // Early security check.
    if (!pnSecAuthAction(0, 'FEproc::Handler', '::', ACCESS_ADD)) {
        $output->Text(_FXNOAUTH);
        return $output->GetOutput();
    }

    // Add menu to output.
    $output->SetInputMode(_PNH_VERBATIMINPUT);
    $output->Text(feproc_adminmenu());
    $output->SetInputMode(_PNH_PARSEINPUT);

    // Title - putting a title ad the head of each page reminds the user what
    // they are doing
    $output->Title(_FXADDHANDLER);

    // Start form.
    $output->FormStart(pnModURL('feproc', 'handleradmin', 'scanmodule'));

    // Add an authorisation ID.
    $output->FormHidden('authid', pnSecGenAuthKey());

    // Start the table that holds the information to be input.
    $output->TableStart();

    // Get a list of modules on the system
    $modules = pnModGetUserMods();

    $feprocid = '';
    foreach ($modules as $module)
    {
        $data[] = Array(
            'id' =>  $module['name'],
            'name' => $module['displayname'] . ': ' . $module['description']
        );
    }

    $row = array();
    $output->SetOutputMode(_PNH_RETURNOUTPUT);
    $row[] = $output->Text(pnVarPrepForDisplay(_FXCHOOSEMODULE));
    $row[] = $output->FormSelectMultiple('modulename', $data, 0, 1, 'feproc');
    $output->SetOutputMode(_PNH_KEEPOUTPUT);
    $output->SetInputMode(_PNH_VERBATIMINPUT);
    $output->TableAddrow($row, 'left');
    $output->SetInputMode(_PNH_PARSEINPUT);

    $output->TableEnd();

    // End form
    $output->Linebreak(2);
    $output->FormSubmit(_FXSCANMODULE);
    $output->FormEnd();

    // Return the output that has been generated by this function
    return $output->GetOutput();
}

/**
 * add new item
 * Having chosen a module, scan that module for feproc APIs.
 */
function feproc_handleradmin_scanmodule()
{
    // For the menu.
    if (!pnModLoad('feproc', 'admin')) {
        pnSessionSetVar('errormsg', _FXMODLOADFAILED . ' admin');
        pnRedirect(pnModURL('feproc', 'handleradmin', 'view'));
        return true;
    }

    // Handler admin functions.
    if (!pnModAPILoad('feproc', 'handleradmin')) {
        pnSessionSetVar('errormsg', _FXMODLOADFAILED . ' handleradmin');
        pnRedirect(pnModURL('feproc', 'handleradmin', 'view'));
        return true;
    }

    // Create output object.
    $output = new pnHTML();

    // Early security check.
    if (!pnSecAuthAction(0, 'FEproc::Handler', '::', ACCESS_ADD)) {
        $output->Text(_FXNOAUTH);
        return $output->GetOutput();
    }

    $modulename = pnVarCleanFromInput('modulename');

    // Add menu to output.
    $output->SetInputMode(_PNH_VERBATIMINPUT);
    $output->Text(feproc_adminmenu());
    $output->SetInputMode(_PNH_PARSEINPUT);

    // Title - putting a title ad the head of each page reminds the user what
    // they are doing
    $output->Title(_FXADDHANDLER);

    // Start form.
    $output->FormStart(pnModURL('feproc', 'handleradmin', 'addhandlers'));

    // Add an authorisation ID.
    $output->FormHidden('authid', pnSecGenAuthKey());

    // Start the table that holds the information to be input.
    $output->TableStart(_FXCHOOSEHANDLERS,
                        array(_FXNAME,
                              _FXDESCRIPTION,
                              _FXTYPE,
                              _FXSOURCE,
                              _FXOPTIONS), 1);

    // Scan the module and get the handlers (if any).
    $handlers = pnModAPIFunc('feproc',
                     'handleradmin',
                     'modulehandlers',
                     array('modulename' => $modulename));

    // Other hidden values to identify the module.
    $output->FormHidden('modulename', $modulename);

    // Loop for each handler found.
    if (is_array($handlers))
    {
        $output->SetInputMode(_PNH_VERBATIMINPUT);
        foreach($handlers as $handler)
        {
            $row = Array();
            $row[] = $handler['name'];
            $row[] = $handler['description'];
            $row[] = $handler['type'];
            $row[] = $handler['source'];

            // TODO: this should be a checkbox.
            $output->SetOutputMode(_PNH_RETURNOUTPUT);
            $row[] = $output->FormCheckbox('sources[]', false, $handler['source'], 'checkbox') .' '. _FXIMPORTFLAG;
            $output->SetOutputMode(_PNH_KEEPOUTPUT);

            $output->TableAddRow($row, 'left');
        }

        $row = Array('', '', '', _FXSOURCEKEY);
        $output->SetOutputMode(_PNH_RETURNOUTPUT);
        $row[] = $output->FormCheckbox('allsources', true, '1', 'checkbox') .' '. _FXIMPORTALLFLAG;
        $output->SetOutputMode(_PNH_KEEPOUTPUT);
        $output->TableAddRow($row, 'left');

        $output->SetInputMode(_PNH_PARSEINPUT);
    }

    $output->TableEnd();

    if (!is_array($handlers))
    {
        // TODO: ml message.
        $output->Text(_FXNOHANDLERSINMODULE . $modulename . '"');
    }

    // End form
    $output->Linebreak(2);
    $output->FormSubmit(_FXIMPORTHANDLERS);
    $output->FormEnd();

    // Return the output that has been generated by this function
    return $output->GetOutput();
}

/**
 * add new item
 * Having chosen a module, scanned that module and selected the APIs for importing,
 * now do the import.
 */
function feproc_handleradmin_addhandlers()
{
    $output = new pnHTML();

    // For the menu.
    if (!pnModLoad('feproc', 'admin')) {
        pnSessionSetVar('errormsg', _FXMODLOADFAILED . ' admin');
        pnRedirect(pnModURL('feproc', 'handleradmin', 'view'));
        return true;
    }

    // Handler admin functions.
    if (!pnModAPILoad('feproc', 'handleradmin')) {
        pnSessionSetVar('errormsg', _FXMODLOADFAILED . ' handleradmin');
        pnRedirect(pnModURL('feproc', 'handleradmin', 'view'));
        return true;
    }

    // Handler user functions.
    if (!pnModAPILoad('feproc', 'handleruser')) {
        pnSessionSetVar('errormsg', _FXMODLOADFAILED . ' handleradmin');
        pnRedirect(pnModURL('feproc', 'handleradmin', 'view'));
        return true;
    }

    list($modulename, $sources, $allsources) =
    pnVarCleanFromInput('modulename', 'sources', 'allsources');

    // Add menu to output.
    $output->SetInputMode(_PNH_VERBATIMINPUT);
    $output->Text(feproc_adminmenu());
    $output->SetInputMode(_PNH_PARSEINPUT);

    // Get the handlers for the module.
    $handlers = pnModAPIFunc('feproc',
                     'handleradmin',
                     'modulehandlers',
                     array('modulename' => $modulename));

    // $sources will not be set if user did not select any handlers for import.
    if (!is_array($sources))
    {
        $sources = Array();
    }

    // Loop for the handlers and import the required ones.
    if (is_array($handlers))
    {
        foreach ($handlers as $handler)
        {
            // If this is one for importing, then do so.
            if ($allsources || in_array($handler['source'], $sources))
            {
                // Yes - import this one.
                //$output->Text("$handler[source] ");

                $output->Linebreak();

                // Get the existing handler, if it exists.
                if ($item = pnModAPIFunc('feproc', 'handleruser', 'gethandler',
                         array('source' => $handler['source'])
                ))
                {
                    // Already exists: update the handler.
                    $result = pnModAPIFunc('feproc', 'handleradmin', 'update',
                    array(
                            'hid' => $item['hid'],
                            'name' => $handler['name'],
                            'description' => $handler['description'],
                            'type' => $handler['type'],
                            'version' => $handler['version'],
                            'modulename' => $handler['module'],
                            'apiname' => $handler['apiname'],
                            'apifunc' => $handler['apifunc'],
                            'attributes' => $handler['attributes']
                        )
                    );

                    if ($result)
                    {
                        // TODO: ml strings.
                        $output->Text("Updated handler: " . $handler['source']);
                    } else {
                        $output->Text("Error updating handler: " . $handler['source']);
                    }
                    $output->Linebreak();
                } else {
                    $result = pnModAPIFunc('feproc', 'handleradmin', 'create',
                    array(
                            'name' => $handler['name'],
                            'description' => $handler['description'],
                            'type' => $handler['type'],
                            'version' => $handler['version'],
                            'modulename' => $handler['module'],
                            'apiname' => $handler['apiname'],
                            'apifunc' => $handler['apifunc'],
                            'attributes' => $handler['attributes']
                        )
                    );
                    if ($result)
                    {
                        // TODO: ml strings.
                        $output->Text("Inserted handler: " . $handler['source']);
                    } else {
                        $output->Text("Error inserting handler: " . $handler['source']);
                    }
                    $output->Linebreak();
                }
            }
        }
    } else {
        // TODO: ml message.
        // Module should have been stopped before getting here.
        $output->Text(_FXNOHANDLERSINMODULE . $modulename . '"');
    }

    return $output->GetOutput();
}

/**
 * help page for a handler
 * Display the help page for a handler.
 */
function feproc_handleradmin_helphandler()
{
    $output = new pnHTML();

    // Handler user functions.
    if (!pnModAPILoad('feproc', 'handleruser')) {
        pnSessionSetVar('errormsg', _FXMODLOADFAILED . ' handleradmin');
        pnRedirect(pnModURL('feproc', 'handleradmin', 'view'));
        return true;
    }
    
    $hid = pnVarCleanFromInput('hid');

    if (!isset($hid) || !is_numeric($hid))
    {
        pnRedirect(pnModURL('feproc', 'handleradmin', 'view'));
    }

    // Get the item

    $item = pnModAPIFunc(
        'feproc', 'handleruser', 'gethandler',
        array('hid' => $hid)
    );

    if (! $item)
    {
        pnRedirect(pnModURL('feproc', 'handleradmin', 'view'));
        return true;
    }

    // Now load the API for the item and call the help function.

    if (!pnModAPILoad($item['modulename'], $item['apiname'])) {
        pnSessionSetVar('errormsg', _FXMODLOADFAILED . ' handleradmin');
        pnRedirect(pnModURL('feproc', 'handleradmin', 'view'));
        return true;
    }

    $help = pnModAPIFunc(
        $item['modulename'], $item['apiname'], $item['apifunc'],
        array('action' => 'help')
    );

    $info = pnModAPIFunc(
        $item['modulename'], $item['apiname'], $item['apifunc'],
        array('action' => 'info')
    );

    if ($help)
    {
        $output->SetInputMode(_PNH_VERBATIMINPUT);
        $output->Title('Handler Help');
        $output->Text("<strong>Overview:</strong><br/>");
        $output->Text("Handler Type: $item[type]<br/>");
        $output->Text("Source Module: $item[modulename]<br/>");
        $output->Text("API Name In Module: $item[apiname]<br/>");
        $output->Text("Function Name In API: $item[apifunc]<br/>");

        if (is_array($info['attributes']))
        {
            $output->Text("<br/>");
            $output->Text("<strong>Function Attributes:</strong>");
            $output->Text('<ul>');
            foreach ($info['attributes'] as $key => $item)
            {
                $output->Text('<li>'.pnVarPrepHTMLdisplay("$key ($item[description]) type=$item[type]"));
                if ($item['type'] == 'list')
                {
                    $output->Text('<ol>');
                    foreach($item['list'] as $listitem)
                    {
                        if ($listitem == '')
                        {
                            $listitem = '&lt;NULL&gt;';
                        }
                        $output->Text(pnVarPrepHTMLdisplay("<li>$listitem</li>"));
                    }
                    $output->Text('</ol>');
                }
                $output->Text('</li>');
            }
            $output->Text('</ul>');
        }

        $output->Text("<br/>");
        $output->Text("<strong>Description:</strong><br/>");

        // The help function will format its own HTML, so send output verbatim.
        $output->Text($help);
        $output->SetInputMode(_PNH_PARSEINPUT);
    } else {
        // TODO: ml string
        $output->Text(_FXHANDLERNOHELP);
    }

    return $output->GetOutput();
}

?>
