<?php

class feproc_admin_modifyconfigHandler extends pnFormHandler
  {
    
    /* Global variables here */
   var $configvars = array(
            // Timeout is defined in minutes.
            // 0 is infinite; maximum value is 48 hours.
            array('name' => 'sessiontimeout', 'min' => 0, 'max' => 2880, 'def' => 30), // 60*24*2 = 2880
            array('name' => 'itemsperpage', 'min' => 1, 'max' => 999, 'def' => 30),
            array('name' => 'removeunmatched', 'min' => 0, 'max' => 1, 'def' => 0),
            array('name' => 'tracestack', 'min' => 0, 'max' => 1, 'def' => 0),
            array('name' => 'shareformitems', 'min' => 0, 'max' => 1, 'def' => 0),
            array('name' => 'attrstringsize', 'min' => 1, 'max' => 200, 'def' => 64),
            array('name' => 'attrstringlen', 'min' => 1, 'max' => 400, 'def' => 200),
            array('name' => 'attrtextrows', 'min' => 1, 'max' => 400, 'def' => 6), 
            array('name' => 'attrtextcols', 'min' => 1, 'max' => 400, 'def' => 40),
       );
   
    /* Functions */

    function initialize(&$render)
    {
      foreach ($this->configvars as $configvar) {
          $v = &$configvar['name'];
          $render->assign($v, pnModGetVar('FEproc', $v));
      }
      return true;
    }
    
    function handleCommand(&$render, &$args)
    {
    
        if (!$render->pnFormIsValid()) return false;

        $formData = $render->pnFormGetValues();
        foreach ($this->configvars as $configvar) {
            $name = &$configvar['name'];
            if (!isset($formData[$name]) || $formData[$name] <$configvar['min'] || $formData[$name] > $configvar['max']) {
                    $formData[$name] = $configvar['def'];
            }
            pnModSetVar('FEproc', $name, $formData[$name]);
        }
        pnSessionSetVar('statusmsg', "The configuration has been updated.");

        return pnRedirect(pnModURL('feproc', 'admin', 'view'));
    }
  }
