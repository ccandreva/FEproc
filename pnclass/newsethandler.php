<?php

class feproc_admin_newsetHandler extends pnFormHandler
  {
    
    /* Global variables here */
        
    /* Functions */

    function initialize(&$render)
    {

      // $render->assign('name', __('Set Name'));  
      // $render->assign('description', __('Set Description'));
      return true;
    }
    
    function handleCommand(&$render, &$args)
    {
    
        if (!$render->pnFormIsValid()) return false;
      
        $formData = $render->pnFormGetValues();
        $setid = pnModAPIFunc('feproc', 'admin', 'createset', $formData);

        if ($setid) {
            // Success
            pnSessionSetVar('statusmsg', "Set Created"); //TODO
            pnRedirect(pnModURL('feproc', 'admin', 'viewstages', Array('setid' => $setid)));
            return true;
        } else {
            pnSessionSetVar('statusmsg', "Could not create set"); //TODO
            pnRedirect(pnModURL('feproc', 'admin', 'view'));
        }

        return true;
    }
  }
