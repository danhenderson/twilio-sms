<?php

	require_once(dirname(__FILE__) . '/lib/class.smsmanager.php');
	require_once(dirname(__FILE__) . '/lib/class.smsentry.php');
	require_once(dirname(__FILE__) . '/lib/class.smscallbacks.php');
	
	Class extension_twilio_sms extends Extension {
	
		/********************************
		SETUP
		********************************/
				
		// extension description
		public function about() {
		
			return array(
				'name'					=> 'Twilio SMS',
				'version'				=> '1.0',
				'release-date'	=> '2014-10-14',
				'author'				=> array(
					'name'					=> 'Dan Henderson',
					'website'				=> 'http://danhenderson.com.au'
				),
				'description' 	=> 'Attach SMS to events.'
			);
		}
		
		// uninstall
		public function uninstall() {
		
			return true;
		}
		
		// install
		public function install() {
		
			return true;
		}
		
		// setup delegates
		public function getSubscribedDelegates() {
		
			return array(
				array(
					'page'			=> '/blueprints/events/edit/',
					'delegate'	=> 'AppendEventFilter',
					'callback'	=> 'appendEventFilter'
				),
				array(
					'page'			=> '/blueprints/events/new/',
					'delegate'	=> 'AppendEventFilter',
					'callback'	=> 'appendEventFilter'
				),
				array(
					'page'     	=> '/frontend/',
					'delegate' 	=> 'EventPostSaveFilter',
					'callback' 	=> 'eventPostSaveFilter'
				),
				array(
					'page'			=> '/system/preferences/',
					'delegate'	=> 'AddCustomPreferenceFieldsets',
					'callback'	=> 'addCustomPreferenceFieldsets'
				)
			);
		}
		
		/********************************
		UTILITIES
		********************************/
		
		// get the account id
		public function getAccountID() {
			
			return Symphony::Configuration()->get('account-id', 'twilio');
		}
		
		// get the auth token
		public function getAuthToken() {
			
			return Symphony::Configuration()->get('auth-token', 'twilio');
		}
		
		/********************************
		DELEGATES
		********************************/
		
		// setup the filters
		public function appendEventFilter($context) {
			
			// get each sms and iterate over them
			$sms = SMSManager::listAll();
			
			foreach($sms as $msg) {
				$handle = 'sms-' . Lang::createHandle($msg['name']);
				$selected = in_array($handle, $context['selected']);
				$context['options'][] = array(
					$handle, $selected, General::sanitize("Send SMS: ".$msg['name'])
				);
			}
		}
		
		// check if any sms needs to be sent
		public function eventPostSaveFilter($context) {
			
			// get each sms and iterate over them
			$sms = SMSManager::listAll();
			
			foreach($sms as $msg) {
				$handle = 'sms-' . Lang::createHandle($msg['name']);
				if (in_array($handle, (array)$context['event']->eParamFILTERS)) {
					$sms_entry = new SMSEntry($msg, $context);
				}
			}
		}
		
		// add custom preferences
		public function addCustomPreferenceFieldsets($context) {
			
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(
				new XMLElement('legend', 'Twilio SMS')
			);
			
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');
			
			$acc = Widget::Label('Account ID');
			$acc->appendChild(Widget::Input(
				'settings[twilio][account-id]', General::Sanitize($this->getAccountID())
			));
			$group->appendChild($acc);
			
			$tok = Widget::Label('Auth Token');
			$tok->appendChild(Widget::Input(
				'settings[twilio][auth-token]', General::Sanitize($this->getAuthToken())
			));
			$group->appendChild($tok);
			
			$fieldset->appendChild($group);
			$context['wrapper']->appendChild($fieldset);
		}
	}
	
?>