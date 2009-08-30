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
// Current Maintainer of file: Klavs Klavsen <kl-feproc@vsen.dk>
// ----------------------------------------------------------------------

class feprocSession {

    /**
     * $forms is an array of the form(s) user data
     */
    var $formdata;
    var $currentSet;
    var $messages;

    /** ****************************************************************************
     * Constructor
     * Gets the session variables
     */
    function feprocSession() {

        $this->formdata = pnSessionGetVar('FEprocFormData');
        if (empty($this->formdata))
        {
            $this->formdata = Array();
            pnSessionSetVar('FEprocFormData', $this->formdata);
        }

        $this->currentSet = pnSessionGetVar('FEprocCurrentSet');
        if (empty($this->currentSet))
        {
            $this->currentSet = Array(
                'setid' => false,
                'stageid' => false,
                'starttime' => false,
                'trxid' => false
            );
        }

        $this->messages = pnSessionGetVar('FEprocMessages');
        if (empty($this->messages))
        {
            $this->messages = Array();
            pnSessionSetVar('FEprocMessages', $this->messages);
        }
    }

    /*
     * Get the timestamp when the session started.
     */
    function getSessionStart()
    {
        if (! $this->currentSet['starttime'])
        {
            return false;
        } else {
            return $this->currentSet['starttime'];
        }
    }
    
    /*
     * Get the current set ID or false if a set is not running.
     */
    function getSetID() {
        if (! $this->currentSet['setid'])
        {
            return false;
        } else {
            return $this->currentSet['setid'];
        }
    }

    /*
     * Get the current set ID or false if a set is not running.
     */
    function getTRXID() {
        if (! $this->currentSet['trxid'])
        {
            return false;
        } else {
            return $this->currentSet['trxid'];
        }
    }

    /*
     * Get the current messages array.
     */
    function getMessages() {
        if (! $this->messages)
        {
            return false;
        } else {
            return $this->messages;
        }
    }

    /*
     * Set a message or messages.
     * 
     */
    function setMessages($messages, $append = false) {
        // Parameter is:
        // Array('name1' => 'value1' [,'name2' => 'value2', ...])
        // If $append is true, then value will be concatenated with
        // existing message that may exist.
        
        if (is_array($messages))
        {
            foreach($messages as $name => $message)
            {
                // Append if required, otherwise just set it.
                $this->messages[$name] = (($append && $this->messages[$name]) ? $message ."\n". $this->messages[$name] : $message);
            }
            pnSessionSetVar('FEprocMessages', $this->messages);
        }
        return true;
    }

    /*
     * Get the current stage ID or false if a set is not running.
     */
    function getStageID() {
        if (! $this->currentSet['stageid'])
        {
            return false;
        } else {
            return $this->currentSet['stageid'];
        }
    }

    /*
     * Get the actual starting stage for the current set.
     * This is initialised when the set is initiated.
     */
    function getStartStageID() {
        if (! $this->currentSet['startstageid'])
        {
            return false;
        } else {
            return $this->currentSet['startstageid'];
        }
    }

    /*
     * Set the current stage ID or false if a set is not running.
     */
    function putStageID($stageid) {
        if (is_numeric($stageid))
        {
            $this->currentSet['stageid'] = $stageid;
            pnSessionSetVar('FEprocCurrentSet', $this->currentSet);
        }
        return true;
    }

    /*
     * Start a new set running. This also closes any existing set that
     * may be running.
     */
    function startSet($setid, $stageid) {
        if (is_numeric($setid) && is_numeric($stageid))
        {
            // At the start of a set, the set and the stage are the same.
            $this->currentSet = Array();
            $this->currentSet['setid'] = $setid;
            $this->currentSet['stageid'] = $stageid;
            $this->currentSet['startstageid'] = $stageid;
            // Save the start time so session can time out if necessary.
            $this->currentSet['starttime'] = time();
            // Create an ID for the transaction.
            $this->currentSet['trxid'] = time();
            pnSessionSetVar('FEprocCurrentSet', $this->currentSet);

            // Clear any old form data in the session.
            $this->formdata = Array();
            pnSessionSetVar('FEprocFormData', $this->formdata);

            // Set the first stack message if required.
            if (pnModGetVar('FEproc', 'tracestack'))
            {
                $this->setMessages(Array('stack' => "Start: set $setid; stage $stageid"), true);
            }
            return true;
        } else {
            return false;
        }
    }

   /*
     * Add the contents of a submitted form.
     * The content is an array of name/value pairs, keyed on the name.
     */
    function addFormData($formdata) {
        $this->formdata = pnSessionGetVar('FEprocFormData');
        if (!is_array($this->formdata))
        {
            $this->formdata = Array();
        }

        if (is_array($formdata))
        {
            foreach ($formdata as $key => $item)
            {
                $this->formdata[$key] = $item;
            }
        }
        // Store the updated form data in the session.
        pnSessionSetVar('FEprocFormData', $this->formdata);
    }

    /*
     * Get the contents of all submitted forms.
     * The content is an array of name/value pairs, keyed on the name.
     */
    function getFormData()
    {
        if(empty($this->formdata))
        {
            return false;
        } else {
            return $this->formdata;
        }
    }

    /*
     * Close the session completely and remove all session variables.
     */
    function closeSession()
    {
        $this->formdata = false;
        pnSessionDelVar('FEprocFormData');

        $this->currentset = false;
        pnSessionDelVar('FEprocCurrentSet');

        $this->messages = false;
        pnSessionSetVar('FEprocMessages', $this->messages);

        // Clear FormExpress data.
        if (pnModLoad('FormExpress', 'user'))
        {
            $fxSession = new FXSession();

            // Loop for all FormExpress forms and clear them down.
            $allforms = $fxSession->getForms();
            if (is_array($allforms))
            {
                foreach ($allforms as $formid => $form)
                {
                    $fxSession->delForm($formid);
                }
            }
        }

        return true;
    }
}
?>
