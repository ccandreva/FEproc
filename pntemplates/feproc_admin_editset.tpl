<!--[include file="feproc_admin_menu.tpl"]-->
<div class="z-admincontainer">
    <div class="z-adminpageicon"><!--[pnimg modname="core" src="configure.gif" set="icons/large" __alt="Settings"]--></div>

    <!--[if $id]-->
        <h2><!--[gt text="Modify Set"]--></h2>
    <!--[else]-->
        <h2><!--[gt text="Add Set"]--></h2>
    <!--[/if]-->

    <!--[pnform cssClass="z-form"]-->
        
        <!--[pnformvalidationsummary]-->
        <!--[if $errormsg != '']-->
            <div class="validationSummary pn-errormsg">
                <!--[$errormsg]-->
            </div>
        <!--[/if]-->

        <div class="z-formrow">
          <!--[pnformlabel for=name __text="Name:" ]-->
          <!--[pnformtextinput id=name width=30em maxLength=50 mandatory=1]-->
        </div>
        <div class="z-formrow">
          <!--[pnformlabel for=description __text="Description:"]-->
          <!--[pnformtextinput id=description width=30em maxLength=255 mandatory=1]-->
        </div>
        <!--[if $id]-->
        <div class="z-formrow">
          <!--[pnformlabel for=startstageid __text="Default starting stage:"]-->
          <!--[pnformdropdownlist id=startstageid width=10em]-->
        </div>
        <!--[/if]-->
        <!--[*
        <div class="z-formrow">
          <!--[pnformlabel for=timeoutstageid __text="Stage on Timeout:"]-->
          <!--[pnformdropdownlist id=timeoutstageid ]-->
        </div>
        <div class="z-formrow">
          <!--[pnformlabel for=errorstageid __text="Stage on error:"]-->
          <!--[pnformdropdownlist id=errorstageid ]-->
        </div>
        *]-->
        <div class="z-formbuttons">
            <!--[pnformimagebutton commandName="submit" __text="Submit" imageUrl="/images/icons/small/button_ok.gif"]-->
            <a href="<!--[pnmodurl modname=feproc type=admin]-->"><!--[pnimg modname=core src="button_cancel.gif" set="icons/small" __alt="Cancel" __title="Cancel"]--></a>
        </div>


    <!--[/pnform]-->
</div>
