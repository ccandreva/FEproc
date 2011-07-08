<h1><!--[gt text="Add Set"]--></h1>

<!--[include file="feproc_admin_menu.tpl"]-->

<!--[pnform cssClass="FormExpress"]-->
    
    <!--[pnformvalidationsummary]-->
    <!--[if $errormsg != '']-->
        <div class="validationSummary pn-errormsg">
            <!--[$errormsg]-->
        </div>
    <!--[/if]-->
  <ul class="FEl1 labelleftcol">
    <li>
      <!--[pnformlabel for=name __text="Name" ]-->
      <!--[pnformtextinput id=name width=30em maxLength=50 mandatory=1]-->
    </li>
    <li>
      <!--[pnformlabel for=description __text="Description"]-->
      <!--[pnformtextinput id=description width=30em maxLength=255 mandatory=1]-->
    </li>
    
    <li><!--[pnformbutton commandName="submit" __text="Submit" ]--></li>
    
  </ul>
    
<!--[/pnform]-->
