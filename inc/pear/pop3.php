<?php defined('WYSIJA') or die('Restricted access'); ?>
<?php
 if(!class_exists('PEAR')) include(dirname(__FILE__).DS. 'pear.php'); if(!class_exists('Net_Socket')) include(dirname(__FILE__).DS. 'socket.php'); define('NET_POP3_STATE_DISCONNECTED', 1, true); define('NET_POP3_STATE_AUTHORISATION', 2, true); define('NET_POP3_STATE_TRANSACTION', 4, true); class Net_POP3 { var $_maildrop; var $_timestamp; var $_timeout = 0; var $_socket; var $_state; var $_host; var $_port; var $_debug = false; var $supportedAuthMethods=array( 'CRAM-MD5', 'APOP' , 'PLAIN' , 'LOGIN', 'USER'); var $supportedSASLAuthMethods=array('DIGEST-MD5', 'CRAM-MD5'); var $_capability; function Net_POP3() { $this->_timestamp = ''; $this->_maildrop = array(); $this->_state = NET_POP3_STATE_DISCONNECTED; $this->_socket = new Net_Socket(); if ((@include_once 'Auth/SASL.php') == false) { if($this->_debug){ echo "AUTH_SASL NOT PRESENT!\n"; } foreach($this->supportedSASLAuthMethods as $SASLMethod){ $pos = array_search( $SASLMethod, $this->supportedAuthMethods ); if($this->_debug){ echo "DISABLING METHOD $SASLMethod\n"; } unset($this->supportedAuthMethods[$pos]); } } } function setTimeOut($timeOut){ $this->_timeout = $timeOut; } function _raiseError($msg, $code =-1) { return PEAR::raiseError($msg, $code); } function connect($host = 'localhost', $port = 110) { $this->_host = $host; $this->_port = $port; $result = $this->_socket->connect($host, $port, false, $this->_timeout); if ($result === true) { $data = $this->_recvLn(); if( $this->_checkResponse($data) ){ if (preg_match('/<.+@.+>/U', $data, $matches)) { $this->_timestamp = $matches[0]; } $this->_maildrop = array(); $this->_state = NET_POP3_STATE_AUTHORISATION; return true; } } $this->_socket->disconnect(); return false; } function disconnect() { return $this->_cmdQuit(); } function login($user, $pass, $apop = true) { if ($this->_state == NET_POP3_STATE_AUTHORISATION) { if(PEAR::isError($ret= $this->_cmdAuthenticate($user , $pass , $apop ) ) ){ return $ret; } if( ! PEAR::isError($ret)){ $this->_state = NET_POP3_STATE_TRANSACTION; return true; } } return $this->_raiseError('Generic login error' , 1); } function _parseCapability() { if($this->_sendCmd('CAPA')){ $data = $this->_getMultiline(); }else { $data = ''; } $data = preg_split('/\r?\n/', $data, -1, PREG_SPLIT_NO_EMPTY); for ($i = 0; $i < count($data); $i++) { $capa=''; if (preg_match('/^([a-z,\-]+)( ((.*))|$)$/i', $data[$i], $matches)) { $capa=strtolower($matches[1]); switch ($capa) { case 'implementation': $this->_capability['implementation'] = $matches[3]; break; case 'sasl': $this->_capability['sasl'] = preg_split('/\s+/', $matches[3]); break; default : $this->_capability[$capa] = $matches[2]; break; } } } } function _getBestAuthMethod($userMethod = null) { $this->_parseCapability(); if( isset($this->_capability['sasl']) ){ $serverMethods=$this->_capability['sasl']; }else{ $serverMethods=array('USER'); if ($this->_timestamp != null) { $serverMethods[] = 'APOP'; } } if($userMethod !== null && $userMethod !== true ){ $methods = array(); $methods[] = $userMethod; return $userMethod; }else{ $methods = $this->supportedAuthMethods; } if( ($methods != null) && ($serverMethods != null)){ foreach ( $methods as $method ) { if ( in_array( $method , $serverMethods ) ) { return $method; } } $serverMethods=implode(',' , $serverMethods ); $myMethods=implode(',' ,$this->supportedAuthMethods); return $this->_raiseError("$method NOT supported authentication method!. This server " . "supports these methods: $serverMethods, but I support $myMethods"); }else{ return $this->_raiseError("This server don't support any Auth methods"); } } function _cmdAuthenticate($uid , $pwd , $userMethod = null ) { if ( PEAR::isError( $method = $this->_getBestAuthMethod($userMethod) ) ) { return $method; } switch ($method) { case 'DIGEST-MD5': $result = $this->_authDigest_MD5( $uid , $pwd ); break; case 'CRAM-MD5': $result = $this->_authCRAM_MD5( $uid , $pwd ); break; case 'LOGIN': $result = $this->_authLOGIN( $uid , $pwd ); break; case 'PLAIN': $result = $this->_authPLAIN( $uid , $pwd ); break; case 'APOP': $result = $this->_cmdApop( $uid , $pwd ); if( PEAR::isError( $result ) ){ $result=$this->_authUSER( $uid , $pwd ); } break; case 'USER': $result = $this->_authUSER( $uid , $pwd ); break; default : $result = $this->_raiseError( "$method is not a supported authentication method" ); break; } return $result; } function _authUSER($user, $pass ) { if ( PEAR::isError($ret=$this->_cmdUser($user) ) ){ return $ret; } if ( PEAR::isError($ret=$this->_cmdPass($pass) ) ){ return $ret; } return true; } function _authPLAIN($user, $pass ) { $cmd=sprintf('AUTH PLAIN %s', base64_encode( chr(0) . $user . chr(0) . $pass ) ); if ( PEAR::isError( $ret = $this->_send($cmd) ) ) { return $ret; } if ( PEAR::isError( $challenge = $this->_recvLn() ) ){ return $challenge; } if( PEAR::isError($ret=$this->_checkResponse($challenge) )){ return $ret; } return true; } function _authLOGIN($user, $pass ) { $this->_send('AUTH LOGIN'); if ( PEAR::isError( $challenge = $this->_recvLn() ) ) { return $challenge; } if( PEAR::isError($ret=$this->_checkResponse($challenge) )){ return $ret; } if ( PEAR::isError( $ret = $this->_send(sprintf('%s', base64_encode($user))) ) ) { return $ret; } if ( PEAR::isError( $challenge = $this->_recvLn() ) ) { return $challenge; } if( PEAR::isError($ret=$this->_checkResponse($challenge) )){ return $ret; } if ( PEAR::isError( $ret = $this->_send(sprintf('%s', base64_encode($pass))) ) ) { return $ret; } if ( PEAR::isError( $challenge = $this->_recvLn() ) ) { return $challenge; } return $this->_checkResponse($challenge); } function _authCRAM_MD5($uid, $pwd ) { if ( PEAR::isError( $ret = $this->_send( 'AUTH CRAM-MD5' ) ) ) { return $ret; } if ( PEAR::isError( $challenge = $this->_recvLn() ) ) { return $challenge; } if( PEAR::isError($ret=$this->_checkResponse($challenge) )){ return $ret; } $challenge=substr($challenge,2); $challenge = base64_decode( $challenge ); $cram = &Auth_SASL::factory('crammd5'); $auth_str = base64_encode( $cram->getResponse( $uid , $pwd , $challenge ) ); if ( PEAR::isError($error = $this->_send( $auth_str ) ) ) { return $error; } if ( PEAR::isError( $ret = $this->_recvLn() ) ) { return $ret; } return $this->_checkResponse($ret); } function _authDigest_MD5($uid, $pwd) { if ( PEAR::isError( $ret = $this->_send( 'AUTH DIGEST-MD5' ) ) ) { return $ret; } if ( PEAR::isError( $challenge = $this->_recvLn() ) ) { return $challenge; } if( PEAR::isError($ret=$this->_checkResponse($challenge) )){ return $ret; } $challenge=substr($challenge,2); $challenge = base64_decode( $challenge ); $digest = &Auth_SASL::factory('digestmd5'); $auth_str = base64_encode($digest->getResponse($uid, $pwd, $challenge, "localhost", "pop3" )); if ( PEAR::isError($error = $this->_send( $auth_str ) ) ) { return $error; } if ( PEAR::isError( $challenge = $this->_recvLn() ) ) { return $challenge; } if( PEAR::isError($ret=$this->_checkResponse($challenge) )){ return $ret; } if ( PEAR::isError( $challenge = $this->_send("\r\n") ) ) { return $challenge ; } if ( PEAR::isError( $challenge = $this->_recvLn() ) ) { return $challenge; } return $this->_checkResponse($challenge); } function _cmdApop($user, $pass) { if ($this->_state == NET_POP3_STATE_AUTHORISATION) { if (!empty($this->_timestamp)) { if(PEAR::isError($data = $this->_sendCmd('APOP ' . $user . ' ' . md5($this->_timestamp . $pass)) ) ){ return $data; } $this->_state = NET_POP3_STATE_TRANSACTION; return true; } } return $this->_raiseError('Not In NET_POP3_STATE_AUTHORISATION State1'); } function getRawHeaders($msg_id) { if ($this->_state == NET_POP3_STATE_TRANSACTION) { return $this->_cmdTop($msg_id, 0); } return false; } function getParsedHeaders($msg_id) { if ($this->_state == NET_POP3_STATE_TRANSACTION) { $raw_headers = rtrim($this->getRawHeaders($msg_id)); $raw_headers = preg_replace("/\r\n[ \t]+/", ' ', $raw_headers); $raw_headers = explode("\r\n", $raw_headers); foreach ($raw_headers as $value) { $name = strtolower(substr($value, 0, $pos = strpos($value, ':'))); $value = ltrim(substr($value, $pos + 1)); if (isset($headers[$name]) AND is_array($headers[$name])) { $headers[$name][] = $value; } elseif (isset($headers[$name])) { $headers[$name] = array($headers[$name], $value); } else { $headers[$name] = $value; } } return $headers; } return false; } function getBody($msg_id) { if ($this->_state == NET_POP3_STATE_TRANSACTION) { $msg = $this->_cmdRetr($msg_id); return substr($msg, strpos($msg, "\r\n\r\n")+4); } return false; } function getMsg($msg_id) { if ($this->_state == NET_POP3_STATE_TRANSACTION) { return $this->_cmdRetr($msg_id); } return false; } function getSize() { if ($this->_state == NET_POP3_STATE_TRANSACTION) { if (isset($this->_maildrop['size'])) { return $this->_maildrop['size']; } else { list(, $size) = $this->_cmdStat(); return $size; } } return false; } function numMsg() { if ($this->_state == NET_POP3_STATE_TRANSACTION) { if (isset($this->_maildrop['num_msg'])) { return $this->_maildrop['num_msg']; } else { list($num_msg, ) = $this->_cmdStat(); return $num_msg; } } return false; } function deleteMsg($msg_id) { if ($this->_state == NET_POP3_STATE_TRANSACTION) { return $this->_cmdDele($msg_id); } return false; } function getListing($msg_id = null) { if ($this->_state == NET_POP3_STATE_TRANSACTION) { if (!isset($msg_id)){ $list=array(); if ($list = $this->_cmdList()) { if ($uidl = $this->_cmdUidl()) { foreach ($uidl as $i => $value) { $list[$i]['uidl'] = $value['uidl']; } } return $list; }else{ return array(); } } else { if ($list = $this->_cmdList($msg_id) AND $uidl = $this->_cmdUidl($msg_id)) { return array_merge($list, $uidl); } } } return false; } function _cmdUser($user) { if ($this->_state == NET_POP3_STATE_AUTHORISATION) { return $this->_sendCmd('USER ' . $user); } return $this->_raiseError('Not In NET_POP3_STATE_AUTHORISATION State'); } function _cmdPass($pass) { if ($this->_state == NET_POP3_STATE_AUTHORISATION) { return $this->_sendCmd('PASS ' . $pass); } return $this->_raiseError('Not In NET_POP3_STATE_AUTHORISATION State'); } function _cmdStat() { if ($this->_state == NET_POP3_STATE_TRANSACTION) { if(!PEAR::isError($data = $this->_sendCmd('STAT'))){ sscanf($data, '+OK %d %d', $msg_num, $size); $this->_maildrop['num_msg'] = $msg_num; $this->_maildrop['size'] = $size; return array($msg_num, $size); } } return false; } function _cmdList($msg_id = null) { $return=array(); if ($this->_state == NET_POP3_STATE_TRANSACTION) { if (!isset($msg_id)) { if(!PEAR::isError($data = $this->_sendCmd('LIST') )){ $data = $this->_getMultiline(); $data = explode("\r\n", $data); foreach ($data as $line) { if($line !=''){ sscanf($line, '%s %s', $msg_id, $size); $return[] = array('msg_id' => $msg_id, 'size' => $size); } } return $return; } } else { if(!PEAR::isError($data = $this->_sendCmd('LIST ' . $msg_id))){ if($data!=''){ sscanf($data, '+OK %d %d', $msg_id, $size); return array('msg_id' => $msg_id, 'size' => $size); } return array(); } } } return false; } function _cmdRetr($msg_id) { if ($this->_state == NET_POP3_STATE_TRANSACTION) { if(!PEAR::isError($data = $this->_sendCmd('RETR ' . $msg_id) )){ $data = $this->_getMultiline(); return $data; } } return false; } function _cmdDele($msg_id) { if ($this->_state == NET_POP3_STATE_TRANSACTION) { return $this->_sendCmd('DELE ' . $msg_id); } return false; } function _cmdNoop() { if ($this->_state == NET_POP3_STATE_TRANSACTION) { if(!PEAR::isError($data = $this->_sendCmd('NOOP'))){ return true; } } return false; } function _cmdRset() { if ($this->_state == NET_POP3_STATE_TRANSACTION) { if(!PEAR::isError($data = $this->_sendCmd('RSET'))){ return true; } } return false; } function _cmdQuit() { $data = $this->_sendCmd('QUIT'); $this->_state = NET_POP3_STATE_DISCONNECTED; $this->_socket->disconnect(); return (bool)$data; } function _cmdTop($msg_id, $num_lines) { if ($this->_state == NET_POP3_STATE_TRANSACTION) { if(!PEAR::isError($data = $this->_sendCmd('TOP ' . $msg_id . ' ' . $num_lines))){ return $this->_getMultiline(); } } return false; } function _cmdUidl($msg_id = null) { if ($this->_state == NET_POP3_STATE_TRANSACTION) { if (!isset($msg_id)) { if(!PEAR::isError($data = $this->_sendCmd('UIDL') )){ $data = $this->_getMultiline(); $data = explode("\r\n", $data); foreach ($data as $line) { sscanf($line, '%d %s', $msg_id, $uidl); $return[] = array('msg_id' => $msg_id, 'uidl' => $uidl); } return $return; } } else { $data = $this->_sendCmd('UIDL ' . $msg_id); sscanf($data, '+OK %d %s', $msg_id, $uidl); return array('msg_id' => $msg_id, 'uidl' => $uidl); } } return false; } function _sendCmd($cmd) { if (PEAR::isError($result = $this->_send($cmd) )){ return $result ; } if (PEAR::isError($data = $this->_recvLn() )){ return $data; } if ( strtoupper(substr($data, 0, 3)) == '+OK') { return $data; } if($cmd == 'CAPA'){ if($this->_debug){ echo $data.' FROM request : CAPA'; } return false; } return $this->_raiseError($data); } function _getMultiline() { $data = ''; while(!PEAR::isError($tmp = $this->_recvLn() ) ) { if($tmp == '.'){ return substr($data, 0, -2); } if (substr($tmp, 0, 2) == '..') { $tmp = substr($tmp, 1); } $data .= $tmp . "\r\n"; } return substr($data, 0, -2); } function setDebug($debug=true) { $this->_debug=$debug; } function _send($data) { if ($this->_debug) { echo "C: $data\n"; } if (PEAR::isError($error = $this->_socket->writeLine($data))) { return $this->_raiseError('Failed to write to socket: ' . $error->getMessage()); } return true; } function _recvLn() { if (PEAR::isError( $lastline = $this->_socket->readLine( 8192 ) ) ) { return $this->_raiseError('Failed to write to socket: ' . $this->lastline->getMessage() ); } if($this->_debug){ echo "S:$lastline\n" ; } return $lastline; } function _checkResponse($response) { if (@substr(strtoupper($response), 0, 3) == '+OK') { return true; }else{ if (@substr(strtoupper($response), 0, 4) == '-ERR') { return $this->_raiseError($response); }else{ if (@substr(strtoupper($response), 0, 2) == '+ ') { return true; } } } return $this->_raiseError("Unknown Response ($response)"); } } ?>
