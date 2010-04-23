<?php
/**
 * Periodic tasks to perform.  This should handle any internal checkpointing
 * necessary, as the cron task may be called more or less frequently than we
 * expect.
 */

class cron extends Handler
{
	function has_permission ()
	{
		// Always have permission to run cron.  In the future,we may want to
		// restrict this to 127.0.0.1 or something.
		return true;
	}

	function process ()
	{
		return join("", module_invoke_all('cron'));
	}
}

function league_cron()
{
	global $dbh;

	$output = '';

	$sth = $dbh->prepare('SELECT DISTINCT league_id FROM league WHERE status = ? AND season != ? ORDER BY season, day, tier, league_id');
	$sth->execute( array('open', 'none') );
	while( $id = $sth->fetchColumn() ) {
		$league = league_load( array('league_id' => $id) );
		$output .= h2(l($league->name, "league/view/$league->league_id"));

		// Find all games older than our expiry time, and finalize them
		$output .= $league->finalize_old_games();

		// Send any email scoring reminders. Do this after finalizing, so
		// captains don't get useless reminders.
		$output .= $league->send_scoring_reminders();

		// If schedule is round-robin, possibly update the current round
		if($league->schedule_type == 'roundrobin') {
			$league->update_current_round();
		}
	}

	return "$output<pre>Completed league_cron run</pre>";
}

?>