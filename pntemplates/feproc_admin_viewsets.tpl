
<h1><!--[gt text="Sets"]--></h1>

<!--[include file="feproc_admin_menu.tpl"]-->

<table class="feproc">
    <thead>
        <tr>
            <th><!--[gt text="ID: Name"]--></th>
            <th><!--[gt text="Description"]--></th>
            <th><!--[gt text="ID: Start Stage"]--></th>
            <th><!--[gt text="Options"]--></th>
        </tr>
    </thead>
    
    <tbody>
        <!--[foreach from=$items item=item]-->
            <tr>
                <td><!--[$item.id]-->:<!--[$item.name]--></td>
                <td><!--[$item.description]--></td>
                <td><!--[if $item.startstageid]--><!--[$item.startstageid]-->: <!--[$item.startstagename]--><!--[/if]--></td>
                <td>
                    <a href="<!--[pnmodurl modname="feproc" type="admin" func="modifyset" setid=$item.id]-->"><!--[gt text="Edit"]--></a> |
                    <a href="<!--[pnmodurl modname="feproc" type="admin" func="deleteset" setid=$item.id]-->"><!--[gt text="Delete"]--></a> |
                    <a href="<!--[pnmodurl modname="feproc" type="admin" func="viewstages" setid=$item.id]-->"><!--[gt text="Show Stages"]--></a>
                    <!--[if $item.startstageid]-->
                        | <a href="<!--[pnmodurl modname="feproc" type="admin" func="stageurl" setid=$item.id]-->"><!--[gt text="Start"]--></a> |
                        <a href="<!--[pnmodurl modname="feproc" type="admin" func="stageurl" setid=$item.id reset=1]-->"><!--[gt text="Restart"]--></a>
                    <!--[/if]-->
                </td>
                
            </tr>
        <!--[/foreach]-->
    </tbody>
</table>
