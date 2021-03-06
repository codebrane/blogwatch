<?
/**
 * BlogWatch Class
 *
 * This file contains the implementation of the BlogWatch class.
 * This represents the underlying subscriber community for a
 * post or topic. Each post or topic will have its own BlogWatch
 * instance.
 *
 * @author Alistair Young <alistair@codebrane.com>
 * @package BlogWatch
 */

class BlogWatch extends ElggEntity {
	/** The subscribers to this post or topic */
	public $subscribers;
	
	/**
	 * Constructor
	 */
	public function __construct($guid = null) {
		
		// Setup the base attributes
		$this->initialise_attributes();
		
		// Load up the subscribers from the database
		if ($guid instanceof stdClass) {
			$this->load($guid->guid);
		}
		else if (is_string($guid)) {
		}
		else if ($guid instanceof BlogWatch) {
		}
		else if ($guid instanceof ElggEntity) {
		}
		else if (is_numeric($guid)) {
		}
	}
	
	/**
	 * Initialises the base attributes for the class
	 */
	protected function initialise_attributes() {
		parent::initialise_attributes();
		
		// Metadata instance variables
		$this->type = "object";
		$this->subtype = "blogwatch";
		
		/* Access needs to be public otherwise only the user who originally
		 * subscribed to the post or topic can access the subscription.
		 */
		$this->access_id = 2;
	}

	/**
	 * Saves the class to the database
	 */
	public function save() {
		global $CONFIG;
		
		if (!parent::save()) {
		}

		// If we have no subscribers, no point continuing
		if (count($this->subscribers) == 0) {
			return true;
		}
		
		// Save the subscribers to the database
		foreach ($this->subscribers as $key => $username) {
			// Add any new subscribers to the database
			if (!get_data_row("SELECT blog_guid from {$CONFIG->dbprefix}blogwatch where blog_guid = {$this->watched_guid} and username = '{$username}'")) {
				$result = insert_data("INSERT into {$CONFIG->dbprefix}blogwatch (blog_guid, blog_url, username) values ('{$this->watched_guid}', '{$this->watched_url}', '{$username}')");
			}
		}
		
		return true;
	}
	
	/**
	 * Loads the subscribers from the database
	 */
	public function load($guid) {
		global $CONFIG;
		
		if (!parent::load($guid)) {
			return false;
		}
			
		/* ElggAnnotation::clear_annotations calls get_entity() multiple times which means we'll get loaded with no metadata
		 * during a delete.
		 */
		if ($this->watched_guid != "") {
			$rows = get_data("SELECT * from {$CONFIG->dbprefix}blogwatch where blog_guid={$this->watched_guid}");
			foreach ($rows as $row) {
				$objarray = (array)$row;
				$this->subscribers[] = $objarray['username'];
			}
		}
		
		return true;
	}
	
	/**
	 * Deletes the class and all its metadata and subscribers from the database
	 */
	public function delete() {
		global $CONFIG;
		delete_data("DELETE from {$CONFIG->dbprefix}blogwatch where blog_guid={$this->watched_guid}");
		return parent::delete();
	}
	
	/**
	 * Deletes all subscribers from a watched entity
	 */
	public function delete_all_subscribers() {
		global $CONFIG;
		if (!$this->has_subscribers()) {
			foreach ($this->subscribers as $key => $username) {
				$this->remove_subscriber($username);
			}
		}
	}
	
	/**
	 * Adds a subscriber to the watched entity
	 * @param $username the username to add as a subscriber
	 */
	public function add_subscriber($username) {
		$this->subscribers[] = $username;
		$this->save();
	}

	/**
	 * Removes a subscriber from the watched entity
	 * @param $username the username to remove as a subscriber
	 */	
	public function remove_subscriber($username) {
		global $CONFIG;
		foreach ($this->subscribers as $key => $value) {
			if ($value == $username) {
				unset($this->subscribers[$key]);
				delete_data("DELETE from {$CONFIG->dbprefix}blogwatch where blog_guid={$this->watched_guid} and username = '$username'");
			}
		}
		
		if (!$this->has_subscribers()) {
			$this->delete();
		}
	}
	
	/**
	 * Determines whether the watched entity has subscribers
	 * @return true if it has subscribers, otherwise false
	 */
	public function has_subscribers() {
		if (count($this->subscribers) > 0) return true;
		return false;
	}
	
	/**
	 * Determines whether the specified user is a subscriber
	 * @param $username the username to check as a subscriber
	 * @return true if the user is a subscriber, otherwise false
	 */
	public function is_subscriber($username) {
		foreach ($this->subscribers as $key => $value) {
			if ($value == $username) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Marks the watched entity has having been commented upon or replied to
	 */
	public function new_comment() {
		global $CONFIG;
		$now = (int)strtotime("now");
		$result = update_data("UPDATE {$CONFIG->dbprefix}blogwatch set updated ='{$now}' where blog_guid = {$this->watched_guid}");
	}
	
	/**
	 * Gets the subscribers of the watched entity
	 * @return comma separated list of usernames
	 */
	public function get_subscribers() {
		return implode(",", array_values($this->subscribers));
	}
	
	public function debug($m) {
		$fd = fopen("/tmp/debug", "a+");
		fwrite($fd, $m."\n");
		fclose($fd);
	}
}
?>