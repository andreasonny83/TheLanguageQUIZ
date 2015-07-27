<?php
/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author andreasonny83@gmail.com
 */
namespace Config\Database;
use Config\Database\DB_Connect;
use Config\SecureSessionHandler;

class DbHandler {

	private $conn;

	private $session;

	function __construct() {
		$db            = new DB_Connect();
		$this->session = new SecureSessionHandler( 'language_quiz' );
		$this->conn    = $db->connect();
	}

	private function check_brute( $user_id ) {
		// Get timestamp of current time
		$now = time();

		// All login attempts are counted from the past hour
		$valid_attempts = $now - ( 3600 );

		if ( $stmt = $this->conn->prepare( 'SELECT time FROM LQ_login_attempts WHERE user_id = ? AND time > ?' ) ) {
			$stmt->bind_param( 'ii', $user_id, $valid_attempts );
			$stmt->execute();
			$stmt->store_result();

			if ( $stmt->num_rows > 5 ) {
				$stmt->close();
				return true;
			}
			else {
				$stmt->close();
				return false;
			}
		}
		else {
			// can't perform the database request
			return false;
		}
	}

	/**
	 * Checking user login
	 * @param String $email User login email id
	 * @param String $password User login password
	 * @return boolean User login status success/fail
	 */
	public function checkLogin( $input_email, $input_password ) {
		// fetching user by email
		if ( $stmt = $this->conn->prepare( 'SELECT id, email, password, salt FROM LQ_users WHERE email = ? LIMIT 1' ) ) {
			$stmt->bind_param( 's', $input_email );
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result( $db_id, $db_email, $db_password, $db_salt );
			$stmt->fetch();
			// Salt the input password with the salt from the database
			$password = hash( 'sha512', $input_password );
			$password = hash( 'sha512', $input_password . $db_salt );

			if ( $stmt->num_rows === 1 ) {
				$stmt->close();
				// Found user with the email
				// Now verify the password
				if ( $this->check_brute( $db_id ) === true ) {
					// Account is locked
					// Send an email to user saying their account is locked
					return false;
				}
				else {
					// Check if the password in the database matches
					// the password the user submitted.
					if ( $db_password === $password ) {
						// password is correct
						$this->session->start();
						// Generate a new session every time
						$this->session->refresh();

						$session_id = session_id();
						$now        = time();

						$this->conn->query( "UPDATE LQ_users
								SET user_session_id='$session_id', session_expiration='$now'
								WHERE id=$db_id"
							);

						/**
						 * TODO
						 * Will use the following information to store inside the database
						 * The user agent information
						**/
						// $user_agent = $_SERVER['HTTP_USER_AGENT'];
						// XSS protection as we might print this value
						// $user_id = preg_replace( '/[^0-9]+/', '', $db_id );
						// XSS protection as we might print this value
						// $username = preg_replace( '/[^a-zA-Z0-9_\-]+/', '', $db_username );
						// $session->put( 'LQ_user_agent', $user_agent );
						// setcookie( 'lq_userid', $db_uid, time() + ( 86400 * 30 ), '/' ); // 1 day

						return true;
					}
					else {
						// user password is incorrect
						// record this attempt in the database
						$now = time();
						$this->conn->query( "INSERT INTO LQ_login_attempts( user_id, time ) VALUES ( '$db_id', '$now' )" );
						return false;
					}
				}
			}
			else {
				// No user exists.
				$stmt->close();
				return false;
			}
		}

		else {
			// can't perform the database request
			return false;
		}

	}

	public function logOut( $user_session ) {
		$now = time() + 2;

		$this->conn->query( "UPDATE LQ_users
				SET session_expiration='$now'
				WHERE user_session_id='$user_session'"
			);

		$this->session->forget();
	}

	/**
	 * Fetching user by email
	 * @param String $email User email id
	 */
	public function getUserUIDByEmail( $email ) {
		$db_uid = '';

		if ( $stmt = $this->conn->prepare( 'SELECT uid FROM LQ_users WHERE email = ? LIMIT 1' ) ) {
			$stmt->bind_param( 's', $email );
			$stmt->store_result();
			$stmt->bind_result( $db_uid );
			$stmt->execute();
			$stmt->fetch();
			$stmt->close();
		}

		return $db_uid;
	}

	public function fetchGlobalCollections( $useruid, $page ) {
		$collections    = array();
		$items_per_page = 10;
		$page           = $page * $items_per_page;

		if ( $stmt = $this->conn->prepare( 'SELECT uid, name, description, lang, rank, score, flash, taboo FROM LQ_collections WHERE status = 2 LIMIT ?, 10' ) ) {
			$stmt->bind_param( 'd', $page );
			$stmt->store_result();
			$stmt->bind_result( $uid, $name, $description, $lang, $rank, $score, $flash, $taboo );
			$stmt->execute();
			while( $stmt->fetch() ) {
				$collections[] = array(
					'uid'         => $uid,
					'name'        => $name,
					'description' => $description,
					'lang'        => $lang,
					'rank'        => $rank,
					'score'       => $score,
					'flash'       => $flash,
					'taboo'       => $taboo,
				);
			}

			$stmt->close();
		}

		if ( $stmt = $this->conn->prepare( 'SELECT lq_collection_uid, favourite FROM LQ_users_rel WHERE lq_user_uid = ?' ) ) {
			$stmt->bind_param( 's', $useruid );
			$stmt->store_result();
			$stmt->bind_result( $collection_uid, $favourite );
			$stmt->execute();

			while( $stmt->fetch() ) {
				// if ( isset ( $collections[$collection_uid] ) ) {
				// 	$collections[$collection_uid]['favourite'] = $favourite;
				// }
			}

			$stmt->close();
		}

		return $collections;
	}

	public function fetchPrivateCollections( $useruid ) {
		$collections = array();

		if ( $stmt = $this->conn->prepare( 'SELECT uid, name, description, lang, rank, score, flash, taboo, favourite FROM LQ_collections INNER JOIN LQ_users_rel ON lq_collection_uid = uid WHERE lq_user_uid = ? AND permission = 9' ) ) {
			$stmt->bind_param( 'd', $useruid );
			$stmt->store_result();
			$stmt->bind_result( $uid, $name, $description, $lang, $rank, $score, $flash, $taboo, $favourite );
			$stmt->execute();

			while( $stmt->fetch() ) {
				$collections[] = array(
					'uid'         => $uid,
					'name'        => $name,
					'description' => $description,
					'lang'        => $lang,
					'rank'        => $rank,
					'score'       => $score,
					'flash'       => $flash,
					'taboo'       => $taboo,
					'favourite'   => $favourite,
				);
			}

			$stmt->close();
		}

		return $collections;
	}

	public function newCollection( $collection ) {
		$uid = uniqid();

		if ( $stmt = $this->conn->prepare( 'INSERT INTO LQ_collections(uid, name, description, lang, status) VALUES (? ? ? ? ?)' ) ) {
			$stmt->bind_param( 'ssssd', $uid, 'test', 'testdescript', '', 0 );
			$stmt->execute();
			return $uid;
		}
		return false;
	}

}
