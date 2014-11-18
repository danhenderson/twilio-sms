<?php

//
// +----------------------------------------------------------------------+
// | Vision6 - API - XMLRPC Encoder                                       |
// +----------------------------------------------------------------------+
// | XMLRPC Encoder / Decoder used by the Vision 6 API                    |
// +----------------------------------------------------------------------+
// | Copyright (c) 2004 Vision 6 Pty Ltd                                  |
// +----------------------------------------------------------------------+
//
// $Id: encoder.class.php 3211 2004-11-07 23:57:33Z pvandijk $



    /**
     * XMLRPC Encoder / Decoder
     *
     * be aware that we attempt to define() CRLF below, if it isnt already defined
     *
     * @access public
     *
     */

    class XMLRPC_Encoder {

        // Decoder return values
        var $method_name = '';
        var $params = array();
        var $type = '';
        var $_debug = false;

        // XML parser
        var $_arrays = array();
        var $_element_data = '';
        var $_array_keys = array();
        var $_struct_name = '';

        var $xml_version = '';
        var $xml_encoding = '';

        var $parser = null;

        // {{{ XMLRPC_Encoder

        /**
        * Constructor
        *
        * @access public
        * @return void
        */

        function XMLRPC_Encoder() {
            if(!defined('CRLF')) {
                define('CRLF', "\r\n");
            }
        }

        // }}}
        // {{{ isMethodCall

        /**
        * is the decoded data a method call?
        *
        * @access public
        * @return bool
        */


        function isMethodCall() {
            return $this->type == 'call';
        }

        // }}}
        // {{{ isMethodResponse

        /**
        * is the decoded data a response?
        *
        * @access public
        * @return bool
        */

        function isMethodResponse() {
            return $this->type == 'response';
        }

        // }}}
        // {{{ isFault

        /**
        * is the decoded response a fault?
        *
        * @access public
        * @return bool
        */

        function isFault() {
            return $this->type == 'fault';
        }

        // }}}
        // {{{ request

        /**
        * Form an XMLRPC methodcall request
        *
        * @access public
        * @return xml
        */

        function request($method, $args) {
            $xml = '<?xml version="1.0" encoding="utf-8"?>'.CRLF.'<methodCall>'.CRLF.'<methodName>'.$this->escape($method).'</methodName>'.CRLF.'<params>'.CRLF;
            foreach($args as $value) {
                $xml .= '<param>'.CRLF;
                $xml .= $this->encode($value, false);
                $xml .= '</param>'.CRLF;
            }
            $xml .= '</params>'.CRLF.'</methodCall>';
            return $xml;
        }

        // }}}
        // {{{ response

        /**
        * Form an XMLRPC method response
        *
        * @access public
        * @return xml
        */

        function response($var) {
            $xml = '<?xml version="1.0" encoding="utf-8"?>'.CRLF.'<methodResponse>'.CRLF.'<params>'.CRLF.'<param>'.CRLF;
            $xml .= $this->encode($var, false);
            $xml .= '</param>'.CRLF.'</params>'.CRLF.'</methodResponse>'.CRLF;
            return $xml;
        }
        // }}}
        // {{{ encode

        /**
        * Encode a single php value/array into XMLRPC <params>
        *
        * :NOTE: arrays are always treated as associative arrays,
        * and are therefore always convereted into XMLRPC structs
        *
        * @access public
        * @return xml
        */

        function encode($var, $header = true) {
            if($header) {
                $xml = '<?xml version="1.0" encoding="utf-8"?>'.CRLF.'<params>'.CRLF.'<param>'.CRLF;
            } else {
                $xml = '';
            }
            if(is_array($var)) {
                $xml .= '<value>'.CRLF;
                $xml .= '<struct>'.CRLF;
                foreach($var as $key => $value) {
                    $xml .= '<member>'.CRLF.'<name>'.$this->escape($key).'</name>'.CRLF;
                    $xml .= $this->encode_element($value);
                    $xml .= '</member>'.CRLF;
                }
                $xml .= '</struct>'.CRLF;
                $xml .= '</value>'.CRLF;
            } else {
                $xml .= $this->encode_element($var);
            }

            if($header) {
                $xml .= '</param>'.CRLF.'</params>'.CRLF;
            }
            return $xml;
        }


        // }}}
        // {{{ decode

        /**
        * decode an xmlrpc request or response into a php array
        *
        * @access public
        * @return array
        */

        function decode($xml) {

            // Reset everything:
            $this->method_name = '';
            $this->params = array();
            $this->type = '';
            $this->_arrays = array();
            $this->_array_keys = array();
            $this->_element_data = '';
            $this->_struct_name = '';
            $this->xml_version = '';
            $this->xml_encoding = '';



            $xml_parser = xml_parser_create();

            // expat doesnt seem to like parsing an <?xml> tag, so do it ourselves
            if(preg_match('/^(.*)\<\?xml(.*)\?>/', $xml, $matches)) {
                $attribs = $matches[2];
                if(preg_match('/version=\"(.*?)\"/', $attribs, $matches)) {
                    $this->xml_version = $matches[1];
                }
                if(preg_match('/encoding=\"(.*?)\"/', $attribs, $matches)) {
                    $this->xml_encoding = $matches[1];
                }
            }

            xml_set_object($xml_parser, $this);
            xml_set_element_handler($xml_parser, 'startElement', 'endElement');
            xml_set_character_data_handler($xml_parser, 'elementData');
            if(!xml_parse($xml_parser, $xml)) {
                print htmlspecialchars($xml);
                print xml_get_current_line_number($xml_parser);
                print xml_error_string(xml_get_error_code($xml_parser));
                return array();
            }
            xml_parser_free($xml_parser);

            if($this->type == 'call') {
                return array($this->method_name, $this->params);
            } else {
                return $this->params[0]; // everything other than a method call only has one parameter, so we shift the array down here
            }
        }

        // }}}
        // {{{ fault

        /**
        * create an XMLRPC fault response
        *
        * @access public
        * @return xml
        */

        function fault($code, $str) {
            $error =  '<?xml version="1.0" encoding="utf-8"?>'.CRLF.'<methodResponse>'.CRLF.'<fault>'.CRLF.'<value>'.CRLF.'<struct>'.CRLF.'<member>'.CRLF.'<name>faultCode</name>'.CRLF;
            $error .= '<value><int>'. intval($code) .'</int></value>'.CRLF;
            $error .= '</member>'.CRLF;

            $error .= '<member>'.CRLF.'<name>faultString</name>'.CRLF;
            $error .= '<value><string>'. $str .'</string></value>'.CRLF;
            $error .= '</member>'.CRLF.'</struct>'.CRLF.'</value>'.CRLF.'</fault>'.CRLF.'</methodResponse>'.CRLF;
            return $error;
        }


        // }}}
        // {{{ encode_element

        /**
        * encode a single php var into an XMLRPC <value>
        *
        * @access public
        * @return
        */

        function encode_element($value) {
            $xml = '';
            // unfortunately we have an unusual exception here with <value> tags being handled by encode() first
            if(!is_array($value)) {
                $xml = '<value>'.CRLF;
            }

            if($value === true || $value === false) {
                $xml .= '<boolean>'. ($value ? '1' : '0') .'</boolean>'.CRLF;
            } else if(is_int($value)) {
                $xml .= '<int>'.$value.'</int>'.CRLF;
            } else if(is_float($value)) {
                $xml .= '<double>'.$value.'</double>'.CRLF;
            } else if(is_array($value)) {
                $xml .= $this->encode($value, false);
            } else {
                $xml .= '<string>'.$this->escape($value).'</string>'.CRLF;
            }

            if(!is_array($value)) {
                $xml .= '</value>'.CRLF;
            }
            return $xml;
        }




        // }}}
        // {{{ escape

        /**
        * cleanse and utf8'erize a string
        *
        * @access public
        * @return
        */

        function escape($string) {
            return utf8_encode(strtr($string, array('&'=>'&#38;', '>'=>'&#62;', '<'=>'&#60;')));
        }


        // }}}
        // {{{ unescape

        /**
        * !escape()
        *
        * @access public
        * @return
        */

        function unescape($string) {
            if($this->isUTF8()) {
                $string = utf8_decode($string);
            }
            return strtr($string, array('&#38;'=>'&', '&#62;'=>'>', '&#60;'=>'<'));
        }


        // }}}
        // {{{ isUTF8

        /**
        * checks encoding attrib of <?xml> tag to check if we should utf8 decode or not
        *
        * @access public
        * @return
        */


        function isUTF8() {
            return strtolower($this->xml_encoding) == 'utf-8' ? true : false;
        }


        // }}}
        // {{{ startElement

        /**
        * expat/xml parser start element callback method
        *
        * @access public
        * @return void
        */

        function startElement($parser, $string, $attribs) {
            // All we really care about here is if we have a struct/array,
            // everything else can be handled by the end tag, when we have cdata
            $this->_element_data = '';
            switch($string) {
                case 'METHODCALL':
                    $this->type = 'call';
                    break;
                case 'METHODRESPONSE':
                    $this->type = 'response';
                    break;
                case 'FAULT':
                    $this->type = 'fault';
                    break;

                case 'ARRAY':
                case 'STRUCT':
                    $this->_arrays[] = array();
                    $this->_array_keys[] = $this->_struct_name;
                    $this->_struct_name = '';
                    break;
            }
        }


        // }}}
        // {{{ elementData

        /**
        * expat/xml parser character data callback method
        *
        * @access public
        * @return void
        */

        function elementData($parser, $string) {
            $this->_element_data .= $string;
        }


        // }}}
        // {{{ endElement

        /**
        * expat/xml parser end element callback method
        *
        * :NOTE:
        * This parser is very simplistic, because we really dont care if they mangle the XMLRPC format.
        * It's not going to break anything, the worst that can happen is that the parser returns an empty method call
        * and an array with garbage in it.
        *
        * @access public
        * @return void
        */

        function endElement($parser, $string) {
            $get_value = false;
            switch($string) {
                case 'METHODNAME':
                    $this->method_name = trim($this->unescape(strval($this->_element_data)));
                    break;

                // More tags we dont really care about:
                case 'PARAMS':
                    break;
                case 'PARAM':
                    break;
                case 'VALUE':
                    break;

                // Associative array key
                case 'NAME':
                    $this->_struct_name = $this->unescape($this->_element_data);
                    break;

                // Clear previous keys if any, when we come to the next array element
                case 'MEMBER':
                case 'DATA':
                    $this->_struct_name = '';
                    break;

                // Add a new array to the stack
                case 'ARRAY':
                case 'STRUCT':
                    $this->_struct_name  = array_pop($this->_array_keys);
                    $this->_element_data = array_pop($this->_arrays);
                    $get_value = true;
                    break;

                case 'STRING':
                    $this->_element_data = trim($this->unescape(strval($this->_element_data)));
                    $get_value = true;
                    break;

                case 'FLOAT':
                    $this->_element_data = doubleval($this->_element_data);
                    $get_value = true;
                    break;

                case 'I4':
                case 'INT':
                    $this->_element_data = intval($this->_element_data);
                    $get_value = true;
                    break;

                case 'BOOLEAN':
                    if($this->_element_data == '1' || strtolower($this->_element_data) == 'true') {
                        $this->_element_data = true;
                    } else {
                        $this->_element_data = false;
                    }
                    $get_value = true;

                default:
                    break;
            }

            if($get_value) {
                $array_idx = count($this->_arrays);
                if($array_idx > 0) {
                    if($this->_struct_name) {
                        $this->_arrays[$array_idx - 1][$this->_struct_name] = $this->_element_data;
                    } else {
                        $this->_arrays[$array_idx - 1][] = $this->_element_data;
                    }
                } else {
                    $this->params[] = $this->_element_data;
                }
            }
            $this->_element_data = '';
        }
    }

?>