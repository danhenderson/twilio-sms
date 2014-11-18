<?php
	
	/*
	* Add static functions here that can then be added as
	* callback functions for a particular sms
	*/
	
	Class SMSCallbacks {
	
		/*
		* example_number_callback
		* example callback to determine the number to send the sms to
		*
		* @return various - false if no message or the number to send to
		*/
		public static function example_number_callback() {
			
			return '+6140012341234';
		}
	
		
		/*
		* example_callback
		* callback for the content and status of the aif sms
		*
		* @return various - false if no message or the content of the message
		*/
		public static function example_callback() {
		
			return 'Example callback content';
		}
		
	}

?>