<?php

# do not resort this list or things will break
require_once("Handler/Login.php");
require_once("Handler/Menu.php");

require_once("Handler/Person.php");
require_once("Handler/Team.php");
require_once("Handler/League.php");
require_once("Handler/Schedule.php");
require_once("Handler/Field.php");
require_once("Handler/Ward.php");
require_once("Handler/Site.php");
require_once("Handler/Game.php");
require_once("Handler/WaitingList.php");

/**
 * This is the base class for all operation handlers used in the web UI.
 * 
 * It exports a method, get_page_handler() that is used as a factory to create
 * appropriate handler instances for the given operation.
 *
 * It also provides the Handler base class, which implements an API that
 * must be followed by each page handler subclass.
 */
class Handler 
{
	/**
	 * The page title, for display
	 * 
	 * @access private
	 * @var string
	 */
	var $title;

	/**
	 * The operation this handler deals with.  Used for generating
	 * self-referential links, form submission targets, etc.
	 */
	var $op;

	/** 
	 * The section of Leaguerunner under which this handler belongs.
	 * This is used to determine which tab it will appear under.
	 */
	var $section;

	/**
	 * Breadcrumbs.  Used for creating a trail of actions so that
	 * users can backtrack.
	 */
	var $breadcrumbs;

	/**
	 * Things to check for general access permission
	 */
	var $_required_perms;
	
	/**
	 * Permissions bits for various items of interest
	 * @access private
	 * @var array
	 */
	var $_permissions;

	/**
	 * Constructor.  This is called by every handler.
	 * Data that should be initialized for the subclass goes in here.
	 */
	function Handler ()
	{
		global $session;
		$this->_required_perms = null;
		$this->_permissions = array();

		$this->section = 'myaccount';
	}

	/**
	 * Initialize our data
	 * This is where stuff that shouldn't be inherited should go.
	 */
	function initialize ()
	{
		return true;
	}

	/**
	 * Check for unsatisified prerequisites for this operation
	 *
	 * Right now, the main Handler class just needs to check that the acount
	 * is active and the waiver has been signed as appropriate.
	 *
	 * This should be overridden by subclass when performing these checks is
	 * not appropriate (Login/Logout, PersonCreate, etc)
	 */
	function checkPrereqs ( $next )
	{
		global $session;

		if( ! $session->is_loaded() ) {
			return false;
		}
		
		// TODO: This belongs as a config option
		$maxTimeBetweenSignings = 60 * 60 * 24 * 365;

		$time = $session->attr_get('waiver_timestamp');
		if( is_null($time) || ((time() - $time) >= $maxTimeBetweenSignings)) {
			return "op=person_signwaiver&next=$next";
		}

		$time = $session->attr_get('dog_waiver_timestamp');
		if(($session->attr_get('has_dog') =='Y') 
			&& ( is_null($time) || ((time() - $time) >= $maxTimeBetweenSignings) )) {
			return "op=person_signdogwaiver&next=$next";
		}

		if( $session->attr_get('survey_completed') != 'Y' ) {
			return "op=person_survey&next=$next";
		}

		return false;
	}

	/**
	 * Check if the logged-in user has permission for the current op
	 * Returns true/false indicating success/failure.
	 * 
	 * @access public
	 * @return boolean 	Permission success/fail
	 */
	function has_permission() 
	{
		global $session;
		
		if(is_null($this->_required_perms)) {
			$this->error_exit("You do not have permission to perform that operation");
		}
		
		/* Now check particular items, in order */
		foreach($this->_required_perms as $perm_type) {
		
			if($perm_type == 'allow') {
				return true;
			} else if($perm_type == 'deny') {
				$this->error_exit("You do not have permission to perform that operation");
			} else if($perm_type == 'require_valid_session') {
				if(!$session->is_valid()) {
					$this->error_exit("You do not have a valid session");
				}
			} else if($perm_type == 'admin_sufficient') {
				if($session->is_admin()) {
					$this->set_permission_flags('administrator');
					return true;
				}
			} else if($perm_type == 'volunteer_sufficient') {
				if($session->attr_get('class') == 'volunteer') {
					$this->set_permission_flags('volunteer');
					return true;
				}
			} else if($perm_type == 'self_sufficient') {
				$id = var_from_getorpost('id');
				if($session->attr_get('user_id') == $id) {
					$this->set_permission_flags('self');
					return true;
				}
			} else if(strncmp($perm_type,'self_sufficient:',16) == 0) {
				$id_field = substr($perm_type, 16);
				$id_data = var_from_getorpost($id_field);
				if($session->attr_get('user_id') == $id_data) {
					$this->set_permission_flags('self');
					return true;
				}
			} else if($perm_type == 'require_coordinator') {
				$id = var_from_getorpost('id');
				if(!$session->is_coordinator_of($id)) {
					$this->error_exit("You do not have permission to perform that operation");
				} else {
					$this->set_permission_flags('coordinator');
				}
			} else if($perm_type == 'coordinator_sufficient') {
				$id = var_from_getorpost('id');
				if($session->is_coordinator_of($id)) {
					$this->set_permission_flags('coordinator');
					return true;
				}
			} else if(strncmp($perm_type,'coordinate_league_containing:',28) == 0) {
				$id_field = substr($perm_type, 29);
				$id_data = var_from_getorpost($id_field);
				if($session->coordinates_league_containing($id_data)) {
					$this->set_permission_flags('coordinator');
					return true;
				}
			} else if(strncmp($perm_type,'coordinate_game:',15) == 0) {
				$id_field = substr($perm_type, 16);
				$id_data = var_from_getorpost($id_field);
				$result = db_query("SELECT league_id FROM schedule WHERE game_id = %d", $id_data);
				$league_id = db_result($result);
				if($session->is_coordinator_of($league_id)) {
					$this->set_permission_flags('coordinator');
					return true;
				}
			} else if(strncmp($perm_type,'captain_of:',10) == 0) {
				$id_field = substr($perm_type, 11);
				$id_data = var_from_getorpost($id_field);
				if($session->is_captain_of($id_data)) {
					$this->set_permission_flags('captain');
					return true;
				}
			} else if(strncmp($perm_type,'require_var:',11) == 0) {
				$wanted_var = substr($perm_type, 12);
				$got_var = var_from_getorpost($wanted_var);
				if(is_null($got_var)) {
					$this->error_exit("Value missing for $wanted_var in URL");
				}
			}
		}

		$this->error_exit("You do not have permission to perform that operation");
	}

	/**
	 * Set any perms flags needed for a particular handler
	 * Should be overridden by subclass if needed.
	 *
	 * @param $type Type of flag to set.  Valid values are * 'administrator', 'coordinator', 'captain'
	 */
	function set_permission_flags($type = '')
	{
		return true;
	}

	/**
	 * Process this operation
	 *
	 * This must be overridden by the subclass.
	 * 
	 * @access public
	 * 
	 */
	function process ()
	{
		trigger_error("Missing handler for process() in this class");
		return false;
	}
	
	/** 
	 * Set both page title and breadcrumbs
	 * Array consists of several key-value pairs.  If there's a nonzero value,
	 * it should be a link component that can be passed to l().
	 */
	function setLocation( $ary ) 
	{
		$titleComponents = array();
		$this->breadcrumbs = array();
		while(list($key,$val) = each($ary)) {
			if($val) {
				$this->breadcrumbs[] = l($key,$val);
			} else {
				$this->breadcrumbs[] = $key;
			}
			array_unshift($titleComponents, $key);
		}
		$this->title = join(' &raquo; ', $titleComponents);
	}

	/**
	 * display the template filled in for this op.
	 *
	 * This displays the HTML output for this operation.  Normally,
	 * this base function gets called to display the contents of the
	 * template as filled by the process() method.
	 *
	 * Individual subclasses can override it as necessary if they need custom
	 * output.
	 * 
	 * @access public
	 * @see process()
	 */
	function display ()
	{
		return true;
	}

	/**
	 * Display the error message and exit.
	 *
	 * Generates an error message page with the given error.
	 *
	 * @access public
	 */
	function error_exit($error = NULL)
	{
		$title = "Error";
		
		$error = $error ? $error : "An unknown error has occurred.";

		print theme_header($title, $this->section, $this->breadcrumbs);
		print "<h1>$title</h1>";
		print theme_error( $error );
		print theme_footer();
		exit;
	}

	/**
	 * Helper fn to turn on all permissions
	 */
	function enable_all_perms()
	{
		reset($this->_permissions);
		while(list($key,) = each($this->_permissions)) {
			$this->_permissions[$key] = true;
		}
		reset($this->_permissions);
	}
	
	/**
	 * Generates list output.  Query should generate rows with two
	 * fields; one named 'id' containing the ID of the object listed,
	 * and 'value', containing a name or descriptive text for each
	 * object
	 */
	function generateSingleList($query, $ops, $dbParams = array())
	{
		
		$result = db_query($query, $dbParams);
		$rows = array();
		while($thisRow = db_fetch_array($result)) {
			$rows[] = array (
				$thisRow['value'],
				theme_links( $this->generateOpsLinks($ops, $thisRow['id'])));
		}
		return table(null, &$rows);
	}

	/**
	 * Generate a list, similar to generateSingleList, but separated into
	 * pages based on the first letter of a given field.
	 */
	function generateAlphaList($query, $ops, $letterField, $fromWhere, $listOp, $letter = null, $dbParams = array())
	{
	
		$letterQuery = db_query("select distinct UPPER(SUBSTRING($letterField,1,1)) as letter from $fromWhere ORDER BY letter asc");
		while($l = db_fetch_object($letterQuery)) {
			$letters[] = $l->letter;
		}
		if(!isset($letter)) {
			$letter = $letters[0];
		}

		$letterLinks = array();
		foreach($letters as $curLetter) {
			if($curLetter == $letter) {
				$letterLinks[] = "<b>$curLetter</b>";
			} else {
				$letterLinks[] = l($curLetter, "op=$listOp&letter=$curLetter");
			}
		}
		$output = para(theme_links($letterLinks, "&nbsp;&nbsp;"));
		$dbParams[] = $letter;
		$output .= $this->generateSingleList($query, $ops, $dbParams);
		return $output;
	
		
		if(!isset($letter)) {
			$letter = $letters[0];
		}
	}

	function generateOpsLinks($opsList, $idValue)
	{
		$opsLinks = array();
		foreach($opsList as $op) {
			$opsLinks[] = l($op['name'], $op['target'] . $idValue);			
		}
		return $opsLinks;
	}
}
?>
