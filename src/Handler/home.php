<?php

class home extends Handler
{
	function has_permission()
	{
		global $lr_session;
		return ( $lr_session->is_valid() );
	}

	function process ()
	{
		global $lr_session;
		$this->setLocation(array( $lr_session->attr_get('fullname') => 0 ));
		return "<div class='splash'>" . join("",module_invoke_all('splash')) . "</div>";
	}
}

function home_splash ()
{
	# Init curl
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, variable_get('rss_feed_url', 'http://www.ocua.ca/taxonomy/term/140/all/feed') );
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 0);

	# Fetch RSS data
	$xml = curl_exec($curl);
	curl_close($curl);

	$xmlObj = simplexml_load_string( $xml );

	$count = 0;
	$limit = variable_get('rss_feed_items', 2);
	$rows = array();

	foreach ( $xmlObj->channel[0]->item as $item )
	{
		$rows[] = array(
			l($item->title, $item->link)
		);
		if( ++$count >= $limit ) {
			break;
		}
	}
	if( $count > 0 ) {
		return table( array( array('data' => variable_get('rss_feed_title', 'OCUA Volunteer Opportunities'), 'colspan' => 2),), $rows);
	} else {
		return '';
	}
}

/**
 * Generate view of teams for initial login splash page.
 */
function team_splash ()
{
	global $lr_session;
	$rows = array();
	$rows[] = array('','', array( 'data' => '','width' => 90), '');

	$rosterPositions = getRosterPositions();
	$rows = array();
	foreach($lr_session->user->teams as $team) {
		$position = $rosterPositions[$team->position];

		$rows[] =
			array(
				l($team->name, "team/view/$team->id") . " ($team->position)",
				array('data' => theme_links(array(
						l("schedule", "team/schedule/$team->id"),
                  l("standings", "league/standings/$team->league_id/$team->team_id"))),
					  'align' => 'right')
		);

	}
	reset($lr_session->user->teams);
	if( count($lr_session->user->teams) < 1) {
		$rows[] = array( array('colspan' => 2, 'data' => 'You are not yet on any teams'));
	}
	if( count($lr_session->user->historical_teams) ) {
		$rows[] = array( array('colspan' => 2, 'data' => 'You have ' . l('historical team data', "person/historical/{$lr_session->user->user_id}") . ' saved'));
	}
	return table( array( array('data' => 'My Teams', 'colspan' => 2),), $rows);
}

/**
 * Generate view of leagues for initial login splash page.
 */
function league_splash ()
{
	global $lr_session;
	if( ! $lr_session->user->is_a_coordinator ) {
		return;
	}

	$header = array(
		array( 'data' => "Leagues Coordinated", 'colspan' => 4)
	);
	$rows = array();

	// TODO: For each league, need to display # of missing scores,
	// pending scores, etc.
	while(list(,$league) = each($lr_session->user->leagues)) {
		$links = array(
			l("edit", "league/edit/$league->league_id")
		);
		if($league->schedule_type != 'none') {
			$links[] = l("schedule", "schedule/view/$league->league_id");
			$links[] = l("standings", "league/standings/$league->league_id");
			$links[] = l("approve scores", "league/approvescores/$league->league_id");
		}

		$rows[] = array(
			array(
				'data' => l($league->fullname, "league/view/$league->league_id"),
				'colspan' => 3
			),
			array(
				'data' => theme_links($links),
				'align' => 'right'
			)
		);
	}
	reset($lr_session->user->leagues);

	return table( $header, $rows );
}


?>
