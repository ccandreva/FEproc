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
 * Provide information on the mail handler.
 */

function feproc_standardapi_feprochandlerindex()
{
    // The handler API must contain one function named
    // <module>_<api-name>api_feprochandlerindex()
    // This function indicates to FEproc that this API contains FEproc
    // handlers.
    // The function returns an array of handler function names along with
    // an indicator of the type of handler that each function is.

    // type is one of:
    // 'transmit' - handler to transmit form data.
    // 'transform' - handler to transform form data.
    // 'validate' - handler to validate form data.
    // 'display' - handler to display a template.
    //
    // The following handler types are processed in other modules:
    // 'formexpress' - forms are extracted direct from FormExpress.
    // 'form' - custom form (not FormExpress).

    return Array(
        Array('type' => 'display', 'apifunc' => 'display'),
        Array('type' => 'display', 'apifunc' => 'dump'),
        Array('type' => 'transmit', 'apifunc' => 'plaintextmail'),
        Array('type' => 'redirect', 'apifunc' => 'redirect'),
        Array('type' => 'transform', 'apifunc' => 'creditcards'),
        Array('type' => 'transform', 'apifunc' => 'setfields'),
        Array('type' => 'transform', 'apifunc' => 'unixpgp5encode'),
        Array('type' => 'validate', 'apifunc' => 'fieldsareset'),
        Array('type' => 'validate', 'apifunc' => 'checkdomain'),
        Array('type' => 'validate', 'apifunc' => 'fileexists')
    );
}

 // Notes on handler functions
 // ==========================
 // A handler function takes a mandatory parameter that must have at least one element
 // 'action'. The action is one of three values:
 // - 'info': return an array of information on the handler.
 // - 'help': return help text and special instructions for the handler.
 // - 'execute': execute the handler.
 //
 // When the action is 'execute' a further array is passed in: $info. This contains
 // arrays 'attributes', 'form', 'system', 'messages' and 'links'. These are,
 // themselves, arrays of key/value pairs.
 //
 // The array returned when the action is 'info' is as follows:
 // - 'name': the name of the handler
 // - 'description': the description of the handler.
 // - 'type': the type of the handler (see type list above).
 // - 'version': the version of the handler.
 // - 'attributes': the attributes for the handler (see notes below).
 
 // Notes on handler attributes
 // ===========================
 // The attributes is an array of key/value pairs. The key is the name of the
 // attribute and the value is an array that defines the properties of the
 // attribute.
 //
 // - Each attribute is defined as follows:
 //   - 'type': one of {'text' | 'string' | 'list'}
 //   - 'default': the default value for the attribute (when the stage is created)
 //   - 'description': the description of the attribute (will appear to the administrator)
 //   - 'list': for 'type' => 'list', defines an array of possible values.
 // A 'string' type is a single line text string. A 'text' type is a multi-line text edit
 // area.
 //
 // This structure is likely to expand to allow further types, validation, mandatory flag,
 // list separate ID/name values, min/max lengths, field sizes etc. I will try to keep
 // additions backward compatible.


 // The next handler is a 'transmit' handler. The return value for the 'execute' action
 // is an array with elements as follows:
 // - 'result': true or false (indicating success or failure).
 // - 'messages': an optional array of key/value pairs. The keys are the message names
 //   and the values are the messages.

/**
 * Send a plain text mail.
 */

function feproc_standardapi_plaintextmail($args)
{
    extract($args);

    $handlerinfo = Array(
        'name' => 'Plain Text Mail',
        'description' => 'Plain text mail handler.',
        'type' => 'transmit',
        'version' => '1.0',
        'attributes' => Array(
            'subject' => Array('type' => 'string', 'default' => 'E-mail subject', 'description' => 'Subject line'),
            'template' => Array('type' => 'text', 'default' => 'Template text...', 'description' => 'Body Template'),
            'to' => Array('type' => 'string', 'default' => '${form:toname} <${form:toaddress}>', 'description' => 'TO recipient/s'),
            'cc' => Array('type' => 'string', 'default' => '', 'description' => 'CC recipient/s'),
            'bcc' => Array('type' => 'string', 'default' => '', 'description' => 'BCC recipient/s'),
            'from' => Array('type' => 'string', 'default' => '${form:sender}', 'description' => 'Sent-from e-mail'),
            'replyto' => Array('type' => 'string', 'default' => '${attribute:from}', 'description' => 'Reply-to e-mail'),
            'priority' => Array('type' => 'list', 'default' => '', 'description' => 'Priority (1-urgent; 3-normal)', 'list' => Array('', '1', '3')),
            'readreceipt' => Array('type' => 'string', 'default' => '', 'description' => 'Read receipt required; enter e-mail, e.g. ${attribute:from}'),
            'importance' => Array('type' => 'list', 'default' => '', 'description' => 'Importance', 'list' => Array('', 'Normal')),
            'encoding' => Array('type' => 'list', 'default' => 'quoted-printable', 'description' => 'E-mail encoding type', 'list' => Array('quoted-printable', 'base64', /*'uuencode',*/ '7bit', '8bit', 'none')),
            'contenttype' => Array('type' => 'list', 'default' => 'text/plain', 'description' => 'E-mail content type', 'list' => Array('text/plain', 'text/html', 'multipart/alternative', '${attribute:custom_content_type}')),
            'custom_content_type' => Array('type' => 'string', 'default' => '', 'description' => 'Custom content type')
        )
    );

    if ($action == 'info')
    {
        // Information on this handler has been requested.
        return $handlerinfo;
    }

    if ($action == 'help')
    {
        $return = '';

        $return .= ("<p>This handler sends plain text mail messages.</p>");

        return $return;
    }

    // TODO: get the headers in the right order as some mail clients don't
    // handle the wrong order very well.
    if ($action == 'execute')
    {
        extract($args);

        if ($info['attributes']['encoding'] == 'base64')
        {
            $info['attributes']['template'] = chunk_split(base64_encode($info['attributes']['template']));
        }

        if ($info['attributes']['encoding'] == 'quoted-printable')
        {
            // There are some notes on www.php.net about doing the str_replace, but it does not seem to work
            // with some clients. For now I'll trust the imap_8bit function.
            //$info['attributes']['template'] = str_replace("=\r\n", "", imap_8bit($info['attributes']['template']));
            $info['attributes']['template'] = imap_8bit($info['attributes']['template']);
        }

        if ($info['attributes']['encoding'] == 'uuencode')
        {
            // TODO: uuencode not yet supported
            $info['attributes']['template'] = chunk_split(base64_encode($info['attributes']['template']));
        }

        if ($info['attributes']['encoding'] == '7bit')
        {
            // TODO: check the template really is 7-bit. If not, then set the encoding to '8bit'.
            $info['attributes']['template'] = $info['attributes']['template'];
        }

        // Create the extra headers.
        $header = array();
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: " . $info['attributes']['contenttype'];
        $headers[] = "Content-Transfer-Encoding: " . $info['attributes']['encoding'];

        if ($info['attributes']['from'])
        {
            $headers[] = "From: " . $info['attributes']['from'];
        }

        if ($info['attributes']['cc'])
        {
            $headers[] = "Cc: " . $info['attributes']['cc'];
        }

        if ($info['attributes']['bcc'])
        {
            $headers[] = "Bcc: " . $info['attributes']['bcc'];
        }

        if ($info['attributes']['replyto'])
        {
            $headers[] = "Reply-To: " . $info['attributes']['replyto'];
        }

        $headers[] = "X-Mailer: PHP/" . phpversion() . "(PostNuke FEproc module)";

        if ($info['attributes']['priority'])
        {
            $headers[] = "X-Priority: " . $info['attributes']['priority'];
        }

        if ($info['attributes']['importance'])
        {
            $headers[] = "Importance: " . $info['attributes']['importance'];
        }

        if ($info['attributes']['readreceipt'] && ereg('.+@.+', $info['attributes']['readreceipt']))
        {
            $headers[] = "Disposition-Notification-To: " . $info['attributes']['readreceipt'];
        }

        $headerString = implode("\r\n", $headers);

        // Now check for errors in the data before sending the mail.
        if ($info['attributes']['to'] !== ''
            && $info['attributes']['subject'] !== ''
            && $info['attributes']['template'] !== '')
        {
            $result = mail(
                trim($info['attributes']['to']),
                trim($info['attributes']['subject']),
                trim($info['attributes']['template']),
                $headerString
            );
        } else {
            // Return an error as parameters are missing.
            $result = false;
        }

        return array(
            'result' => $result
        );
    }

    // default: no action defined.
    return false;
}


// A display type handler will return the HTML to display.
// A false return (if there is no HTML to display) will cause the
// failure path of the stage to be followed. This will be the same
// as the user clicking the 'failure' URL on a templated page output.

function feproc_standardapi_display($args)
{
    extract($args);

    if ($action == 'info')
    {
        // Information on this handler has been requested.
        return Array(
            'name' => 'Display text',
            'description' => 'Display the contents of a template.',
            'type' => 'display',
            'version' => '1.0',
            // TODO: string = simple 200 char string; text = text box; list = drop-down list;
            'attributes' => Array(
                'template' => Array('type' => 'text', 'default' => 'Template text...', 'description' => 'Template Text'),
                'filter' => Array('type' => 'list', 'default' => 'no filter', 'description' => 'Filter', 'list' => Array('no filter', 'nl2br'))
            )
        );
    }

    if ($action == 'help')
    {
        return('Display a templated page in the browser window.');
    }

    if ($action == 'execute')
    {
        // Do some filtering of the text, if required.
        if ($info['attributes']['filter'] == 'nl2br')
        {
            $info['attributes']['template'] = nl2br($info['attributes']['template']);
        }

        // Very simple - just return the 'template' attribute.
        return array(
            'result' => true,
            'text' => $info['attributes']['template']
        );
    }

    // default: no action defined.
    return false;
}

// This handler will dump all its parameters to the screen.
// The screen contents are templated, so success/fail links
// can be provided. The dump will be substituted in place of
// the string '${dump}' in the template.

function feproc_standardapi_dump($args)
{
    extract($args);

    if ($action == 'info')
    {
        // Information on this handler has been requested.
        return Array(
            'name' => 'Dump output',
            'description' => 'Dump the contents of info to the screen.',
            'type' => 'display',
            'version' => '1.0',
            'attributes' => Array(
                'template' => Array(
                    'type' => 'text',
                    'default' => 'Next stages:<br/>'
                        .'<a href="${link:successurl}">success</a><br/>'
                        .'<a href="${link:failureurl}">failure</a><br/>'
                        .'<a href="${link:starturl}">start (start again at actual set entry-point)</a><br/>'
                        .'<a href="${link:restarturl}">restart (clear-down first, then start)</a><br/>'
                        .'<a href="${link:seturl}">run set (run from set default start stage)</a><br/>'
                        .'<a href="${link:reseturl}">rerun set (clear-down first)</a><br/>'
                        .'${dump}',
                    'description' => 'Dump Template'
                )
            )
        );
    }

    if ($action == 'help')
    {
        return('Dump the contents of all form session variables. Takes no attributes - can be used for debugging.');
    }

    if ($action == 'execute')
    {
        // TODO: multi-language (ml).
        $return = "<p><strong>Session information dump:</strong></p>";
        foreach ($info as $infokey => $infoitem)
        {
            $return .= "<p><em>$infokey:</em><p>";

            $return .= "<ul>";
            if (is_array($infoitem))
            {
                foreach($infoitem as $infoitemkey => $infoitemvalue)
                {
                    $return .= "<li><strong>" . ($infoitemkey) . "</strong> = "
                        . nl2br(pnVarPrepForDisplay($infoitemvalue)) . "</li>";
                }
            }
            $return .= "</ul>";
        }

        // Links for success/failure.
        if (preg_match('/\${dump}/i', $info['attributes']['template']))
        {
            return array(
                'result' => true,
                'text' => preg_replace('/\${dump}/i', $return, $info['attributes']['template'])
            );
        } else {
            return array(
                'result' => true,
                'text' => $info['attributes']['template'] . '<br/>' . $return
            );
        }
    }

    // default: no action defined.
    return false;
}


// A redirect type handler will return the URL.
// The form session will be closed before the URL redirect is done.
// Returning false (if there is no URL) will cause the failure path for that
// stage to be followed. The form session will not be closed in this case.

// A redirect type handler must return the following array:
// - 'result': true or false to indicate whether the URL is valid.
// - 'url': the URL.
// If the result is false (failure) then the failure stage defined for the current
// stage will be executed next. If the current stage has no 'success' stage (and the
// reult is true (success), then the form set session will be closed (i.e. all form
// values discarded) before the redirectino takes place.
//
// Future extension may allow URLs in new windows, so implementing pop-up windows.

function feproc_standardapi_redirect($args)
{
    extract($args);

    if ($action == 'info')
    {
        // Information on this handler has been requested.
        return Array(
            'name' => 'Redirect',
            'description' => 'Close form session and redirect to a URL.',
            'type' => 'redirect',
            'version' => '1.0',
            // TODO: string = simple 200 char string; text = text box; list = drop-down list;
            'attributes' => Array(
                'url' => Array('type' => 'string', 'default' => 'index.php', 'description' => 'Redirect URL')
            )
        );
    }

    if ($action == 'help')
    {
        return('Redirect to a new URL. If there are no following stages set then the form session will be closed, '
        . 'otherwise it will be left open.');
    }

    if ($action == 'execute')
    {
        // Very simple - just return the 'url' attribute.

        if (isset($info['attributes']['url']) && $info['attributes']['url'])
        {
            $result = true;
        } else {
            $result = false;
        }

        return Array(
            'result' => $result,
            'url' => $info['attributes']['url']
        );
    }

    // default: no action defined.
    return false;
}

// The next two handlers are 'transform' handlers. These types of handlers return
// the following array:
// - 'result': true or false (to indicate success or failure)
// - 'form': an array of key/value pairs which contain the transformed values.
//
// The 'form' values will be discarded if the result is false. If the result is
// true then the values will be used to update or create form values (as though
// the values had been entered on a form).


// Munge credit card numbers. This allows parts of credit cards to be sent to
// users or displayed in the browser window for confirmation purposes. There are
// a number of different munging formats as standard.

function feproc_standardapi_creditcards($args)
{
    extract($args);

    if ($action == 'info')
    {
        // Information on this handler has been requested.
        return Array(
            'name' => 'Credit Cards',
            'description' => 'Various transformation for credit card handling.',
            'type' => 'transform',
            'version' => '1.0',
            'attributes' => Array(
                'source' => Array('type' => 'string', 'default' => '${form:ccfield}', 'description' => 'Source Value'),
                'destination' => Array('type' => 'string', 'default' => 'ccmunged', 'description' => 'Destination Form Field'),
                'format' => Array('type' => 'list', 'default' => '', 'description' => 'Output format',
                    'list' => Array('**** **** **** ****', '****************', '*')
                ),
                'digits' => Array('type' => 'list', 'default' => '3', 'description' => 'Digits to show',
                    'list' => Array('1', '2', '3', '4')
                )
            )
        );
    }

    if ($action == 'help')
    {
        return('Various transformations for credit card handling.');
    }

    if ($action == 'execute')
    {
        $newdata = Array();

        // If there is data to process and somewhere to put it then do so.
        if ($info['attributes']['source'] && $info['attributes']['destination'])
        {
            // Remove non-numerics.
            $digits = substr(eregi_replace('[^0123456789]', '', $info['attributes']['source']), (-1)*$info['attributes']['digits']);
            $newdata[$info['attributes']['destination']] =
                substr($info['attributes']['format'], 0, (-1)*$info['attributes']['digits']) . $digits;
        }

        return Array(
            'result' => true,
            'form' => $newdata
        );
    }

    // default: no action defined.
    return false;
}


// Set arbitrary form fields to arbitrary values.
// This is a generic handler that is best used in conjunction
// with other handlers.

function feproc_standardapi_setfields($args)
{
    extract($args);

    $fieldsep = '=';
    $defaultvalue = 'fieldname'.$fieldsep.'value';
    $fieldcount = 5;

    if ($action == 'info')
    {
        // Information on this handler has been requested.
        $return = Array(
            'name' => 'Set Fields',
            'description' => 'Set arbitrary form fields to arbitrary values.',
            'type' => 'transform',
            'version' => '1.0',
            // TODO: string = simple 200 char string; text = text box; list = drop-down list;
            'attributes' => Array()
        );

        // Create some of the fields dynamically for consistency. No need to duplicate lines of code!
        for($i = 1; $i <= $fieldcount; $i++)
        {
            $return['attributes']['string'.$i] = Array('type' => 'string', 'default' => ($i==1 ? $defaultvalue : ''), 'description' => "String $i");
        }

        for($i = 1; $i <= $fieldcount; $i++)
        {
            $return['attributes']['text'.$i] = Array('type' => 'text', 'default' => ($i==1 ? $defaultvalue : ''), 'description' => "Text $i");
        }
        return $return;
    }

    if ($action == 'help')
    {
        return('Set arbitrary form fields to arbitrary values<br/>Set each field as a "name=value" pair.');
    }

    if ($action == 'execute')
    {
        $newdata = Array();

        // If there is data to process and somewhere to put it then do so.
        if (is_array($info['attributes']))
        {
            foreach($info['attributes'] as $attrkey => $attrvalue)
            {
                if ($attrvalue != $defaultvalue && preg_match('/^\w+=/i', $attrvalue))
                {
                    // Split the value at the field separator.
                    $namevalue = split($fieldsep, $attrvalue, 2);
                    $newdata[$namevalue[0]] = $namevalue[1];
                }
            }
        }

        return Array(
            'result' => true,
            'form' => $newdata
        );
    }

    // default: no action defined.
    return false;
}


// Do PGP encoding.
// This handler will PGP encode a form field and place the result into another field.
// It has been written for a Unix server since it makes vital use of pipes,
// stdio streams and Gnu shell commands.
// This has been developed and tested with PGP 5.0 under Redhat 7.1
// You must still manage your keys from the command line, but most aspects of the
// PGP command can be customised through the handler admin screen.
// This handler will optionally return the PGP command and the PGP stderr to a named
// message.

function feproc_standardapi_unixpgp5encode($args)
{
    extract($args);

    if ($action == 'info')
    {
        // Information on this handler has been requested.
        return Array(
            'name' => 'Unix PGP Encode',
            'description' => 'Encrypt and sign a message using PGP.',
            'type' => 'transform',
            'version' => '1.0',
            'attributes' => Array(
                'homedir' => Array('type' => 'string', 'default' => '/home', 'description' => 'Home Directory (for PGP keys)'),
                'msgfield' => Array('type' => 'string', 'default' => 'pgpmessage', 'description' => 'Error Message Field Name'),
                'cmdfield' => Array('type' => 'string', 'default' => 'pgpcmd', 'description' => 'Expanded Command Field Name'),
                'template' => Array('type' => 'text', 'default' => 'Template text...${form:sourcefield}...', 'description' => 'Input Template'),
                'outfield' => Array('type' => 'string', 'default' => 'pgpencrypted', 'description' => 'Output Field Name'),
                'pgpcmd' => Array('type' => 'string', 'default' => '/usr/bin/pgpe -af +batchmode=1 +NoBatchInvalidKeys=1', 'description' => 'PGP Base Command'),
                'pgpcmde' => Array('type' => 'string', 'default' => '-r', 'description' => 'Encrypt Userid Option'),
                'pgpcmdeu' => Array('type' => 'string', 'default' => 'somebody@somewhere', 'description' => 'Encrypt Userid'),
                'pgpcmds' => Array('type' => 'string', 'default' => '-s', 'description' => 'Sign Userid Option'),
                'pgpcmdsu' => Array('type' => 'string', 'default' => '', 'description' => 'Sign Userid (no signing if not set)')
            )
        );
    }

    if ($action == 'help')
    {
        return('Perform PGP encryption and/or signing on data. The encrypted data can then by sent via e-mail or stored in a file by a separate "transmit" handler.');
    }

    if ($action == 'execute')
    {
        $result = true;
        $message = '';
        $messageCmd = '';
        $encrypted = '';
        $errprefix = '>>';

        // Do some validation.
        // TODO.

        // Do the encrypting and/or signing if the validation was successful.
        if ($result)
        {
            // This seems convoluted, but consider:
            // 1. Unix shell scripting is never as simple as it first looks.
            // 2. I have built the command step-by-step to help bugfixing and
            //    third-party customisations.

            // Build the command in an array for convenience.
            $command = Array();

            // Wrap the whole command in a sub-shell.
            $command[] = '(';

            // Optionally set the HOME directory.
            // This tells PGP where to find the .pgp directory with the
            // public and private keyrings.
            if ($info['attributes']['homedir'])
            {
                $command[] = 'HOME=' . $info['attributes']['homedir'] . ';';
            }

            // Start the pipe: echo the input data. Escape the template string as it could
            // contain hacking stuff entered by a user.
            $command[] = 'echo ' . escapeshellarg($info['attributes']['template']);

            // Pipe into the PGP command
            $command[] = '|';

            // Include the PGP base command.
            $command[] = $info['attributes']['pgpcmd'];

            // Optional encrypt userid.
            // This is mandatory for pgpe, but not if pgps is used for the
            // base command. This would generally be the userid of the user
            // the message will bve sent to.
            if ($info['attributes']['pgpcmdeu'])
            {
                if ($info['attributes']['pgpcmde'])
                {
                    $command[] = $info['attributes']['pgpcmde'];
                }
                $command[] = $info['attributes']['pgpcmdeu'];
            }

            // Optional sign userid.
            // This will be the userid of the user that is sending the message.
            if ($info['attributes']['pgpcmdsu'])
            {
                if ($info['attributes']['pgpcmds'])
                {
                    $command[] = $info['attributes']['pgpcmds'];
                }
                $command[] = $info['attributes']['pgpcmdsu'];
            }

            // Swap stderr with stdout and prefix each stderr line (after swapping)
            // for filtering out. This is a bit convoluted, but it allows us to take
            // both stdout and stderr from the PGP command through a single file
            // stream.
            $command[] = '3>&1 1>&2 2>&3 | sed \'s/^/' .$errprefix. '/\'';

            // End of complete sub-shell wrap. Send stderr to stdout to catch
            // the encrypted message.
            $command[] = ') 2>&1';

            // Join the command line components into a string.
            $cmd = join(' ', $command);

            // Execute the command.
            $execresult = exec($cmd, $execout, $errorcode);

            // Now all error/message lines will start with $errprefix
            // Strip these out and keep them separate to the encrypted message.

            $execstdout = Array();
            $execstderr = Array();

            foreach($execout as $outvalue)
            {
                if (ereg($errprefix, $outvalue))
                {
                    $execstderr[] = substr($outvalue, strlen($errprefix));
                } else {
                    $execstdout[] = $outvalue;
                }
            }

            $encrypted = join("\n", $execstdout);

            // The only reliable way to tell if it worked is to look for the 
            // PGP message markers. Not sure how multi-language works - there could be
            // different strings for different languages?
            if (!ereg("-----BEGIN PGP MESSAGE-----.*-----END PGP MESSAGE-----", $encrypted))
            {
                // Some error occured.
                $result = false;
            }

            $message = "Code: $errorcode\n" . join("\n", $execstderr);
        }

        // Return stuff to the calling function.
        $return = Array();
        $return['result'] = $result;

        if ($result)
        {
            // Set the output field only if succesful.
            $return['form'] = Array($info['attributes']['outfield'] => $encrypted);
        }

        // Send back the PGP message if requested.
        if ($info['attributes']['msgfield'])
        {
            // If there is a message or the message field has previously been set,
            // then send back a message to that field.
            if ($message || $info['messages'][$info['attributes']['msgfield']])
            {
                $return['messages'][$info['attributes']['msgfield']] = $message;
            }
        }
        
        // Send back the PGP expanded command if requested.
        if ($info['attributes']['cmdfield'])
        {
            // If there is a message or the message field has previously been set,
            // then send back a message to that field.
            if ($cmd || $info['messages'][$info['attributes']['cmdfield']])
            {
                $return['messages'][$info['attributes']['cmdfield']] = $cmd;
            }
        }
        
        return $return;
    }

    // default: no action defined.
    return false;
}


// Validate handlers return the following array:
// - 'result': true or false to indicate whether the validation succeded or failed.
// - 'messages': an optional array of key/value pairs (message name/message payload)
//   to indicate the reason for the failure.
//
// A 'validate' handler cannot directly alter the current user data captured from forms
// or other sources.


// This 'validate' handler checks for specified fields being set or not. It also
// validates those fields as being numeric or not, as requested.
// One use for this would be to check that all forms in a multi-form set have
// been visited at least once. Each form can include a hidden field set to some
// value. The validate stage can check that all hidden form fields have been set
// before allowing the control to flow to the final transaction submit stage.

function feproc_standardapi_fieldsareset($args)
{
    extract($args);

    $fieldcount = 10;

    if ($action == 'info')
    {
        // Information on this handler has been requested.
        $return = Array(
            'name' => 'Fields Are Set',
            'description' => 'Confirms specified fields have been set.',
            'type' => 'validate',
            'version' => '1.0',
            // TODO: string = simple 200 char string; text = text box; list = drop-down list;
            'attributes' => Array(
                'msgfield' => Array('type' => 'string', 'default' => 'message', 'description' => 'Message field name'),
                'msgsuccess' => Array('type' => 'string', 'default' => 'Validation successful', 'description' => 'Success message text'),
                'logic' => Array('type' => 'list', 'default' => '', 'description' => 'Joining Logic',
                    'list' => Array('OR', 'AND'))
            )
        );

        // Create some of the fields dynamically for consistency. No need to duplicate lines of code!
        for($i = 1; $i <= $fieldcount; $i++)
        {
            $return['attributes']['field'.$i] = Array('type' => 'string', 'default' => ($i==1 ? '${form:fieldname}' : ''), 'description' => "$i Value A (main)");
            $return['attributes']['fieldb'.$i] = Array('type' => 'string', 'default' => ($i==1 ? '123' : ''), 'description' => "$i Value B");
            $return['attributes']['validate'.$i] = Array('type' => 'list', 'default' => 'None', 'description' => "$i Validation Type",
                    'list' => Array(
                        'None',
                        'Set', 'Empty',
                        'Numeric', 'Non Numeric', 'Zero', 'Non-Zero',
                        'Equal', 'Not Equal', 'Identical', 'Not Identical'
                    )
                );
        }

        return $return;
    }

    if ($action == 'help')
    {
        return('Check that fields have been set in previous stages (e.g. that all mandatory forms have passed in a hidden value to show thay have been executed).');
    }

    if ($action == 'execute')
    {
        $result = true;
        $messages = Array($info['attributes']['msgfield'] => $info['attributes']['msgsuccess']);

        $logic = $info['attributes']['logic'];
        
        if ($logic == 'OR')
        {
            $result = false;
        }

        if ($logic == 'AND')
        {
            $result = true;
        }

        // Loop for the validation tests.
        for($i = 1; $i <= $fieldcount; $i++)
        {
	    //
	    // 23 jul. 2003 - Klavs Klavsen <kl@vsen.dk> 
	    // Changed this handler to use {arrayname,fieldname}
	    // instead of just fieldname (which was then only looked for in
	    // the attributes array).
	    //
            $testfieldvalue = $info['attributes']['field'.$i];
            $testfieldBvalue = $info['attributes']['fieldb'.$i];
	    //strip {} from '{arrayname,fieldname}'
	    $fieldvalue = trim($testfieldvalue, "{}");
	    $fieldBvalue = trim($testfieldBvalue, "{}");
	    //trigger_error("1:$i-$fieldvalue-$fieldBvalue",E_USER_WARNING);
	    // explode arrayname and fieldname 
	    //$fieldvalues = explode(":", $testfieldvalue);
	    //$fieldBvalues = explode(":", $testfieldBvalue);
	    //trigger_error("2:$i-$fieldvalues-$fieldBvalues",E_USER_WARNING);
	    
            //$fieldvalue = $info[$fieldvalues[0]][$fieldvalues[1]];
            //$fieldBvalue = $info[$fieldBvalues[0]][$fieldBvalues[1]];
	    
	    //the original code.
            //$fieldvalue = $info['attributes']['field'.$i];
            //$fieldBvalue = $info['attributes']['fieldb'.$i];

            $fieldtest = $info['attributes']['validate'.$i];

            if ($fieldtest != 'None')
            {
                // Do the test if there is one.
                switch ($fieldtest) {
                    case 'Set':
                        $fieldresult = !empty($fieldvalue);
                        break;
                    case 'Empty':
                        $fieldresult = empty($fieldvalue);
                        break;
                    case 'Numeric':
                        $fieldresult = is_numeric($fieldvalue);
                        break;
                    case 'Non Numeric':
                        $fieldresult = !is_numeric($fieldvalue);
                        break;
                    case 'Zero':
                        $fieldresult = is_numeric($fieldvalue) && $fieldvalue == 0;
                        break;
                    case 'Non-Zero':
                        $fieldresult = is_numeric($fieldvalue) && $fieldvalue <> 0;
                        break;
                    case 'Equal':
                        $fieldresult = ($fieldvalue == $fieldBvalue ? true : false);
                        break;
                    case 'Not Equal':
                        $fieldresult = ($fieldvalue != $fieldBvalue ? true : false);
                        break;
                    case 'Identical':
                        $fieldresult = ($fieldvalue === $fieldBvalue ? true : false);
                        break;
                    case 'Not Identical':
                        $fieldresult = ($fieldvalue !== $fieldBvalue ? true : false);
                        break;
                    default:
                        $fieldresult = false;
                }

                if ($fieldresult && $logic == 'OR')
                {
                    $result = true;
                    break;
                }

                if (!$fieldresult && $logic == 'AND')
                {
                    $result = false;
                    $messages = Array($info['attributes']['msgfield'] => "Field values '$fieldvalue' and ''$fieldBvalue'' failed validation type '$fieldtest'.");
                    break;
                }
            }
        }

        return Array(
            'result' => $result,
            'messages' => $messages
        );
    }

    // default: no action defined.
    return false;
}

// This 'validate' handler checks if a domainname is available.
// If it's not, it returns the resulting whois-query.
// It depends on the PHPwhois classes from sf.net.

function feproc_standardapi_checkdomain($args)
{
    extract($args);

    if ($action == 'info')
    {
        // Information on this handler has been requested.
        $return = Array(
            'name' => 'Check Domain',
            'description' => 'Makes a whois query on the requested domain.',
            'type' => 'validate',
            'version' => '1.0',
            // Here you put the config options.
            'attributes' => Array(
                'msgfield' => Array('type' => 'string', 'default' => 'message', 'description' => 'Message field name'),
                'msgsuccess' => Array('type' => 'string', 'default' => 'Domain available', 'description' => 'Success message text'),
                'domainnamevar' => Array('type' => 'string', 'default' => '', 'description' => 'name of var to grab domainname from'),
            )
        );

        return $return;
    }

    if ($action == 'help')
    {
        return('Check the given fieldname for a vacant domainname.');
    }

    if ($action == 'execute')
    {
        $result = true;
        $messages = Array($info['attributes']['msgfield'] => $info['attributes']['msgsuccess']);

	//TODO: make it look for the field name given in domainvar instead of
	//expecting domainname.
	//TODO: make it not depend on phpwhois, or include phpwhois in module?
        //$domainnamevar = $info['attributes']['domainnamevar'];

	$domainname = $info['form']['domainname'] . $info['form']['tld'];

	//verify that domainname is actually a valid domainname before doing a
	//whois
	//ereg^[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9].[a-zA-Z]{2,6}$)

	//start the lookup - this file requires phpwhois-3.0.5 or higher.
	require("CheckDomainAvailability.inc.php");

	$whois = new CheckDomainAvailability("$domainname");
	$whoisresult = $whois->CheckIfAvailable();
	
	if($whoisresult)
	{
	   //available
           $messages = Array($info['attributes']['msgfield'] => $info['form']['domainname'] . $info['form']['tld'] . " is available");
           $result = true;
	} else
	{
	   //taken
           $messages = Array($info['attributes']['msgfield'] => $info['form']['domainname'] . $info['form']['tld'] . " is taken");
           $result = false;
	}

        return Array(
            'result' => $result,
            'messages' => $messages
        );
    }

    // default: no action defined.
    return false;
}

// This 'validate' handler checks if a given file/directory exists.
//

function feproc_standardapi_fileexists($args)
{
    extract($args);

    if ($action == 'info')
    {
        // Information on this handler has been requested.
        $return = Array(
            'name' => 'File exists',
            'description' => 'Checks if the given file or folder exists.',
            'type' => 'validate',
            'version' => '1.0',
            // Here you put the config options.
            'attributes' => Array(
                'msgfield' => Array('type' => 'string', 'default' => 'message', 'description' => 'Message field name'),
                'msgsuccess' => Array('type' => 'string', 'default' => 'File exists', 'description' => 'Success message text'),
                'filevar' => Array('type' => 'string', 'default' => '', 'description' => 'name of var to grab file to check for existance'),
                'basedir' => Array('type' => 'string', 'default' => '/some/path', 'description' => 'base path to apply to filename checked for. if you need to check for files in /www/tmp/ then set it to that - and let the filename the user can contribute with, be ONLY the filename - no PATH if possible. / is NOT allowed.'),
            )
        );

        return $return;
    }

    if ($action == 'help')
    {
        return('Checks if the given filename exists.');
    }

    if ($action == 'execute')
    {
        $result = true;
        $messages = Array($info['attributes']['msgfield'] => $info['attributes']['msgsuccess']);

        $filenamevar = $info['attributes']['filevar'];
        $basedir = $info['attributes']['basedir'];

	//TODO - make the prepending part selectable in the info section
	// and ensure it is not allowed to be just / - bad manners.
	$filename = $info['form']["$filenamevar"];
	$filenameinclbasedir = $basedir . $filename;

	//ensure that the contents of the form:$filenamevar is a valid
	//filename without .. ie. only /a-zA-Z0-9_. and wahtever else is valid.
	//currently doesn't handle filenames with . in them (I don't want ..)
	if (!(ereg("^[_a-zA-Z0-9-]+$",$filename)))
	{
	    //invalid filename!
           $messages = Array($info['attributes']['msgfield'] => "data: $filename is invalid");
	   $result = false;
           return Array(
            'result' => $result,
            'messages' => $messages);
	}
	if (!(ereg("^\/[_a-zA-Z0-9\/]+\/$",$basedir)))
	{
	    //invalid basedir!
           $messages = Array($info['attributes']['msgfield'] => "basedir: $basedir is invalid");
	   $result = false;
           return Array(
            'result' => $result,
            'messages' => $messages);
	}


	    
	if (file_exists($filenameinclbasedir)) 	
	{
	   //exists
           $messages = Array($info['attributes']['msgfield'] => "$filenameinclbasedir exists");
           $result = true;
	} else
	{
	   //doesn't exist
           $messages = Array($info['attributes']['msgfield'] => "$filenameinclbasedir does not exist");
           $result = false;
	}

        return Array(
            'result' => $result,
            'messages' => $messages
        );
    }

    // default: no action defined.
    return false;
}
?>
