<?php

	Class SMSManager {
		
		// list all the registered sms
		public static function listAll() {
		
			$json = file_get_contents(EXTENSIONS . "/twilio_sms/setup.json");
			$sms = json_decode($json, true);
			return $sms;
		}
		
	}

?>