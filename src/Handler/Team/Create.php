<?php
register_page_handler('team_create', 'TeamCreate');

/**
 * Team create handler
 *
 * @package Leaguerunner
 * @author Dave O'Neill <dmo@acm.org>
 * @access public
 * @copyright GPL
 */
class TeamCreate extends TeamEdit
{
	function initialize ()
	{
		$this->set_title("Create New Team");
		$this->_permissions = array(
			'edit_name'			=> true,
			'edit_website'		=> true,
			'edit_shirt'		=> true,
			'edit_captain' 		=> false,
			'edit_assistant'	=> false,
			'edit_status'		=> true,
		);

		return true;
	}

	function has_permission ()
	{
		global $session;

		if(!$session->is_valid()) {
			$this->error_text = gettext("You do not have a valid session");
			return false;
		}
		
		/* Anyone with a session can create a new team */
		return true;
	}

	/*
	 * Overridden, as we have no info to put in that form.
	 */
	function generate_form () 
	{
		return true;
	}

	function perform ()
	{
		global $DB, $id, $session;
	
		$res = $DB->query("INSERT into team (name,established) VALUES ('new team', NOW())");
		if($this->is_database_error($res)) {
			return false;
		}
		
		$id = $DB->getOne("SELECT LAST_INSERT_ID() from team");
		if($this->is_database_error($id)) {
			return false;
		}

		$res = $DB->query("INSERT INTO leagueteams (league_id, team_id, status) VALUES(1, ?, 'requested')", array($id));
		if($this->is_database_error($res)) {
			return false;
		}
		
		$res = $DB->query("INSERT INTO teamroster (team_id, player_id, status, date_joined) VALUES(?, ?, 'captain', NOW())", array($id, $session->data['user_id']));
		if($this->is_database_error($res)) {
			return false;
		}
		
		return parent::perform();
	}

	function validate_data ()
	{
		global $_POST, $session;
		$err = true;
		
		$team_name = trim(var_from_getorpost("team_name"));
		if(0 == strlen($team_name)) {
			$this->error_text .= gettext("team name cannot be left blank") . "<br>";
			$err = false;
		}
		
		$shirt_colour = trim(var_from_getorpost("shirt_colour"));
		if(0 == strlen($shirt_colour)) {
			$this->error_text .= gettext("shirt colour cannot be left blank") . "<br>";
			$err = false;
		}

		return $err;
	}

}

?>
