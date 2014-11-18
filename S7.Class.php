<?php

	/*

		SiteHQ Content Management System
		New Business Media
		http://www.nbm.com.au
		Copyright 2010

		S7 (Vision 6) Email Marketing API Class (Requires PHP 5+ and cURL)

	*/


	class S7 {

		private $username = 'theinstitute';
		private $password = 'fitness7';
		private $api_endpoint = 'http://s7.nbm.com.au/api/xmlrpcserver.php?version=1.0'; // Version numbers are mandatory
		private $ch = NULL; // cUrl Handle
		private $encoder = NULL;


		function __construct() {
			// XMLRPC Encoder Required
			require_once(EXTENSIONS.'/twilio_sms/classes/XMLRPC_Encoder.Class.php');
			$this->encoder = new XMLRPC_Encoder();

			// Init cURL
			$this->ch = curl_init();
			curl_setopt_array($this->ch, array(
				CURLOPT_POST => 1,
				CURLOPT_USERAGENT => "XMLRPC",
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_HTTPHEADER => array('Content-Type: text/xml'),
				CURLOPT_URL => $this->api_endpoint
			));

			// Init S7 API Session
			$xml_request = $this->encoder->request('login', array($this->username, $this->password));			
			$response = $this->SendRequest($xml_request);

			if (!empty($response)) {
				// Success. Use New Endpoint URL.
				$this->api_endpoint = $response;
				curl_setopt($this->ch, CURLOPT_URL, $this->api_endpoint);
			} else {
				throw new Exception('Failed to connect to S7 Servers. Username or Password incorrect?');
			}
		}


		// Add a Subscriber to an S7 Database (List)
		function AddSubscriber($details, $list_id) {
			// Re-set details (ensure array key name's are correct, and exclude any extra data)
			$list_id = (int)$list_id;
			$details['Date Added'] = (strtotime($details['DateAdded']) !== FALSE) ? $details['DateAdded'] : date('d/m/Y');
			$details = array_map('trim', $details);

			// Check Details
			if (empty($details['Email'])) return FALSE;
			if (!is_numeric($list_id)) return FALSE;

			// Already Subscribed?
			if ($this->IsSubscribed($details['Email'], $list_id)) {
				return TRUE; // Yes
			}

			// Not Subscribed. Add to S7.
			$xml_request = $this->encoder->request('subscribeContact', array($list_id, $details));	
			$response = $this->SendRequest($xml_request);

			// Return Contact ID on success, FALSE on Failure
			return (is_numeric($response) AND (int)$response > 0) ? (int)$response : FALSE;
		}


		// Check if this person (Email) has subscribed to this Database (List ID)
		private function IsSubscribed($email, $list_id) {
			$email = trim($email);
			$list_id = (int)$list_id;
			if (empty($email)) return FALSE;
			if (!is_numeric($list_id)) return FALSE;

			// Run Search
			$search = array(array('Email', 'exactly', $email), array('is_active', 'exactly', '1'));
			$xml_request = $this->encoder->request('searchContacts', array($list_id, $search, 1, 0, 'Email', 'DESC'));
			$response = $this->SendRequest($xml_request);

			// Return "Yes" or "No"
			return (bool)$response;
		}


		// send request XML through the curl handle, return the response
		private function SendRequest($xml_request, $debug = FALSE) {
			// Set XML Request Data
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $xml_request);

			// Execute Request and Decode Response
			$result = curl_exec($this->ch);
			$decoded_result = $this->encoder->decode($result);

			print_r($decoded_result);
			
			if ($debug) {
				echo '<pre>';
				var_dump($this->api_endpoint, htmlentities($xml_request), htmlentities($result), $decoded_result);
				echo '</pre>';
				exit();
			}

			if($this->encoder->isMethodResponse()) {
				// Success
				return $decoded_result;

			} else {
				// Failure
				throw new Exception('Failed to send S7 API Request.');

				// $this->encoder->isFault()
				// Yes, this is very ungraceful..
				/*
				print 'Fault Occured';
				print_r($decoded_result);
				die;
				*/
				#write to file if subscription error, don't display it
				/*$handle = fopen("subscription_error.txt", "a");
				if($handle) {
					fwrite($handle,date("d-m-Y",time())."\t\t".print_r($decoded_result)."\r\n");

					fclose($handle);
				} */
			}
		}


		function __destruct() {
			// Close cUrl
			curl_close($this->ch);
		}

	}

	// End of S7 Class
	// S7.Class.php