<?php

class feproc_admin_editsetHandler extends pnFormHandler
  {
    
    /* Global variables here */
    var $setid;
    
    /* Functions */
    function setId($id)
    {
        $this->setid = $id;
    }
    
    function initialize(&$render)
    {
        if (isset($this->setid)) {
            $setid = $this->setid;

            // Load item from database
            $item = pnModAPIFunc('feproc', 'user', 'getset', array('setid' => $setid));

            // Get stages for drop-down lists.
            $stages = pnModAPIFunc('feproc', 'user', 'getallstages',
                                  array('setid' => $setid));

            if (is_array($stages))
            {
                // TODO: make the default starting stage a simple attribute. The upgrade
                // would need to do some conversion to do this.
                // Create stages drop-down list.
                $data = Array();
                $data[] = Array('value' => '0', 'text' => '- None -');
                foreach ($stages as &$stage)
                {
                    $data[] = Array('value' => $stage['stageid'], 'text' => "$stage[stageid]: $stage[name]");
                }
                $item['startstageidItems'] = $data;
            }
            $render->assign($item);
        }
        

      // $render->assign('name', __('Set Name'));  
      // $render->assign('description', __('Set Description'));
      return true;
    }
    
    function handleCommand(&$render, &$args)
    {
    
        if (!$render->pnFormIsValid()) return false;
      
        $formData = $render->pnFormGetValues();

        if ($this->setid) {
            $formData['setid'] = $this->setid;
            pnModAPIFunc('feproc', 'admin', 'updateset', $formData);
            pnSessionSetVar('statusmsg', "Set updated."); //TODO
        } else {

            $setid = pnModAPIFunc('feproc', 'admin', 'createset', $formData);

            if ($setid) {
                // Success
                pnSessionSetVar('statusmsg', "Set Created"); //TODO
                pnRedirect(pnModURL('feproc', 'admin', 'viewstages', Array('setid' => $setid)));
                return true;
            } else {
                pnSessionSetVar('statusmsg', "Could not create set"); //TODO
            }
        }

        return pnRedirect(pnModURL('feproc', 'admin', 'viewsets'));
    }
  }
