<h3>PayPal</h3>
<p>You can add this event to a PayPal cart for online payment.</p>
<form target="paypal" action="https://www.paypal.com/cgi-bin/webscr" method="post">

	<!-- Identify your business so that you can collect the payments. -->
	<input type="hidden" name="business" value="{$paypal_email}">
	
	<!-- Specify a PayPal Shopping Cart Add to Cart button. -->
	<input type="hidden" name="cmd" value="_cart">
	<input type="hidden" name="add" value="1">
	
	<!-- Specify details about the item that buyers will purchase. -->
	<input type="hidden" name="item_name" value="{$event->name}">
	<input type="hidden" name="item_number" value="{$registration->formatted_order_id()}">
	<input type="hidden" name="amount" value="{$event->cost}">
	<input type="hidden" name="currency_code" value="CAD">
	
	<!-- Details about the Buyer -->
	<input type="hidden" name="first_name" value="{$user->firstname}">
	<input type="hidden" name="last_name" value="{$user->lastname}">
	<input type="hidden" name="address1" value="{$user->addr_street}">
	<input type="hidden" name="city" value="{$user->addr_city}">
	<input type="hidden" name="country" value="{$user->addr_country}">
	<input type="hidden" name="zip" value="{$user->addr_postalcode}">

	<!-- Continue shopping -->
	<input type="hidden" name="shopping_url" value="http://www.leaguerunner.com">
	
	<!-- Display the payment button. -->
	<input type="image" name="submit" border="0" src="https://www.paypal.com/en_US/i/btn/btn_cart_LG.gif" alt="PayPal - The safer, easier way to pay online">
	<img alt="" border="0" width="1" height="1" src="https://www.paypal.com/en_US/i/scr/pixel.gif" >
</form>