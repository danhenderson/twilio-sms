<?php

	require_once(EXTENSIONS . '/twilio_sms/twilio/Services/Twilio.php');

	Class SMSEntry {
	
		// variables
		private $_msg;
		private $_context;
		private $_driver;
		
		// constructor
		public function __construct($msg, $context) {
			
			$this->_msg = $msg;
			$this->_context = $context;
			$this->_driver = Symphony::ExtensionManager()->create('twilio_sms');
			$this->_sendSMS();
		}
		
		// send the sms
		private function _sendSMS() {
		
			try {
			
				Symphony::Log()->pushToLog("SMS Preparing.....", E_NOTICE, true);
			
				// hook up to twilio using our account id and token
				$client = new Services_Twilio($this->_driver->getAccountID(), $this->_driver->getAuthToken());
				
				// get the numbers we want to send to
				if (isset($this->_msg['to_number_callback'])) {
					$number = call_user_func(array("SMSCallbacks", $this->_msg['to_number_callback']));	
					
					// cancel the message if needed
					if ($number === false) {
						Symphony::Log()->pushToLog("SMS Cancelled", E_NOTICE, true);
						return false;
					} else {
						$people = array(
							$number => 'Receiver'
						);
					}
					
				} else {
					$people = array(
						$this->_msg['to_number'] => 'Receiver'
					);
				}
				
				// determine the content of the message
				if (isset($this->_msg['content_callback'])) {
					$content = call_user_func(array("SMSCallbacks", $this->_msg['content_callback']));	
					
					// cancel the message if needed
					if ($content === false) {
						Symphony::Log()->pushToLog("SMS Cancelled", E_NOTICE, true);
						return false;
					}
					
				} else {
					$content = $this->_msg['content'];
				}
				
				// send a message to each number in our array
				foreach($people as $number => $name) {
									
					// send the message
					$sms = $client->account->messages->sendMessage(
						$this->_msg['from-number'],
						$number,
						$content
					);
					
					Symphony::Log()->pushToLog("SMS Sent", E_NOTICE, true);
				}
				
			} catch(Exception $e) {
			
				Symphony::Log()->pushToLog($e->getMessage(), E_NOTICE, true);
			}
			
		}
	}

?>