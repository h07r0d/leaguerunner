{$street}<br />
{$city}, {$province}<br />
{if $postalcode}{$postalcode}<br />{/if}
{if $country}{$country}<br />{/if}
{strip}
<br />[&nbsp;
	<a href="http://maps.google.com?q={$street|urlencode},+{$city|urlencode},+{$province|urlencode}&hl=en">maps.google.com</a>
&nbsp;|&nbsp;
	<a href="http://www.mapquest.com/maps/map.adp?country=ca&zoom=7&city={$city|urlencode}&state={$province|urlencode|truncate:2:""}&address={$street|urlencode}">MapQuest</a>
&nbsp;]
{/strip}
