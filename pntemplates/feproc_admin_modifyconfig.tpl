<!--[include file="feproc_admin_menu.tpl"]-->

<div class="z-admincontainer">
    <div class="z-adminpageicon"><!--[pnimg modname="core" src="configure.gif" set="icons/large" __alt="Settings"]--></div>
    <h2><!--[gt text="FEproc Configuration"]--></h2>
        <!--[pnform cssClass="z-form"]-->
        
        <!--[pnformvalidationsummary]-->
        <!--[if $errormsg != '']-->
            <div class="validationSummary pn-errormsg">
                <!--[$errormsg]-->
            </div>
        <!--[/if]-->
        
        <fieldset>
            <legend><!--[gt text="Admin Options"]--></legend>

            <div class="z-formrow">
              <!--[pnformlabel for=itemsperpage __text="Items Per Page" ]-->
              <!--[pnformtextinput id=itemsperpage width=3em maxLength=3 mandatory=1]-->
            </div>
            <div class="z-formrow">
              <!--[pnformlabel for=attrstringlen __text="String Attribute Max Length"]-->
              <!--[pnformtextinput id=attrstringlen width=3em maxLength=3 mandatory=1]-->
            </div>
            <div class="z-formrow">
              <!--[pnformlabel for=attrstringsize __text="String Attribute Field Size"]-->
              <!--[pnformtextinput id=attrstringsize width=3em maxLength=3 mandatory=1]-->
            </div>
            <div class="z-formrow">
              <!--[pnformlabel for=attrtextrows __text="Text Attribute Field Size (rows x cols)"]-->
              <span> <!--[pnformtextinput id=attrtextrows width=3em maxLength=3 mandatory=1]--> x 
              <!--[pnformtextinput id=attrtextcols width=3em maxLength=3 mandatory=1]--> <span>
            </div>
        </fieldset>

        <fieldset>
            <legend><!--[gt text="Runtime/User Options"]--></legend>

            <div class="z-formrow">
              <!--[pnformlabel for=shareformitems __text="Share Item Values Between Different Forms" ]-->
              <!--[pnformcheckbox id=shareformitems ]-->
            </div>
            <div class="z-formrow">
              <!--[pnformlabel for=removeunmatched __text="Hide Unmatched Substitution Variables" ]-->
              <!--[pnformcheckbox id=removeunmatched ]-->
            </div>
            <div class="z-formrow">
              <!--[pnformlabel for=tracestack __text="Enable Trace Stack" ]-->
              <!--[pnformcheckbox id=tracestack ]-->
            </div>
            <div class="z-formrow">
              <!--[pnformlabel for=sessiontimeout __text="Minutes Before Session Is Timed Out" ]-->
              <!--[pnformtextinput id=sessiontimeout width=6em maxLength=6 mandatory=1]-->
            </div>
        </fieldset>

        <div class="z-formbuttons">
            <!--[pnformimagebutton commandName="submit" __text="Submit" imageUrl="/images/icons/small/button_ok.gif"]-->
            <a href="<!--[pnmodurl modname=feproc type=admin]-->"><!--[pnimg modname=core src="button_cancel.gif" set="icons/small" __alt="Cancel" __title="Cancel"]--></a>
        </div>


    <!--[/pnform]-->

</div>
    