{include file=header.tpl}
<h1>{$title}</h1>
  <div class='pairtable'>
    <table>
	<tr><td>Season:</td><td>{$season->season}</td></tr>
	<tr><td>Year:</td><td>{$season->year}</td></tr>
	<tr><td>Archived:</td><td>{if $season->archived}Yes{else}No{/if}</td></tr>
     </table>
    </div>

    <p></p>
    <h3>Leagues</h3>
    {include file="pages/season/components/league_list.tpl"}

    <p></p>
    <h3>Registration Events</h3>
    {include file="pages/season/components/event_list.tpl"}
{include file=footer.tpl}
