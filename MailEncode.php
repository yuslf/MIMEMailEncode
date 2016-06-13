<?php
class MIME_MailEncode {
	const TO = 0;
	const CC = 1;
	const BCC = 2;
	const GBK = 'gbk';
	const GB2312 = 'gb2312';
	const UTF8 = 'utf-8';
	const US_ASCII = 'us-ascii';
	const ISO_8859_1 = 'iso-8859-1';
	const EN7BIT = '7bit';
	const EN8BIT = '8bit';
	const ENBINARY = 'binary';
	const ENBASE64 = 'base64';
	const ENQP = 'quoted-printable';
	const ATTACHMENT = 'attachment';
	const INLINE = 'inline';
	const TEXT_PLAIN = 'text/plain';
	const TEXT_HTML = 'text/html';
	const TEXT_XML = 'text/xml';
	const IMAGE_GIF = 'image/gif';
	const IMAGE_JPEG = 'image/jpeg';
	const IMAGE_PNG = 'image/png';
	const IMAGE_BMP = 'image/bmp';
	const APPLICATION_OCTET_STREAM = 'application/octet-stream';
	const MULTIPART_MIXED = 'multipart/mixed';
	const MULTIPART_ALTERNATIVE = 'multipart/alternative';
	const MULTIPART_DIGEST = 'multipart/digest';
	const MULTIPART_RELATED = 'multipart/related';
	const MESSAGE_RFC822 = 'message/rfc822';
	const MESSAGE_PARTIAL = 'message/partial';
	const MESSAGE_EXTERNAL_BODY = 'message/external-body';

	public static function str2QP( $str ) {
		$all = str_split( $str );
		$str = '';
		foreach ( $all as $tmp ) {
			if ( ord( $tmp ) > 128 ) {
				$str .= '=' . bin2hex( $tmp );
			} else {
				$str .= $tmp;
			}
		}
		return $str;
	}

	public static function head2047f( $str, $encode, $charset ) {
		$all = str_split( $str );
		$is8bit = false;
		foreach ( $all as $tmp ) {
			if ( ord( $tmp ) > 128 ) {
				$is8bit = true;
				break;
			}
		}
		if ( $is8bit ) {
			switch( $encode ) {
				case MIME_MailEncode::ENBASE64 :
					$str = "=?" . $charset . "?B?" . base64_encode( $str ) . "?=";
					break;
				case MIME_MailEncode::ENQP :
					$str = "=?" . $charset . "?Q?" . MIME_MailEncode::str2QP( $str ) . "?=";
					break;
			}
			return $str;
		} else {
			return $str;
		}
	}
}
class MIME_Mail {
	protected $From;
	protected $To;
	protected $Subject;
	protected $Date;
	protected $Reply_To;
	protected $Cc;
	protected $Bcc;
	protected $MIME_Version;
	protected $Content_Type;
	protected $isMIME;
	protected $Boundary;
	protected $Content_Transfer_Encoding;
	protected $Content_Disposition;
	protected $Content_ID;
	protected $Content_Location;
	protected $Content_Base;
	protected $issegment;
	protected $segsize;
	protected $charset;
	protected $name;
	protected $filename;
	protected $isBin;
	protected $BODY;

	public function __construct() {
		$time = explode( ' ', microtime() );
		$time = $time[1] . strval( $time[0] );
		$this->To = '';
		$this->Cc = '';
		$this->Bcc = '';
		$this->Reply_To = '';
		$this->MIME_Version = '1.0';
		$this->isMIME = true;
		$this->Content_Transfer_Encoding = MIME_MailEncode::ENBASE64;
		$this->Content_Disposition = null;
		$this->Content_ID = null;
		$this->Content_Location = null;
		$this->Content_Base = null;
		$this->issegment = false;
		$this->segsize = null;
		$this->charset = MIME_MailEncode::UTF8;
		$this->name = null;
		$this->filename = null;
		$this->isBin = true;
		$this->BODY = null;
		$this->Boundary = '--=_NextPart_' . $time;
	}

	public function setFrom( $name, $mail ) {
		if ( !( is_string( $name ) && is_string( $mail ) ) ) {
			return false;
		}
		$this->From = "\"";
		$this->From .= MIME_MailEncode::head2047f( $name, $this->Content_Transfer_Encoding, $this->charset );
		$this->From .= "\" <" . $mail . ">";
		$this->Reply_To = $this->From;
	}

	public function AddTo( $name, $mail, $head ) {
		if ( !( is_string( $name ) && is_string( $mail ) ) ) {
			return false;
		}
		switch( $head ) {
			case MIME_MailEncode::TO :
				$to = 'To';
				break;
			case MIME_MailEncode::CC :
				$to = 'Cc';
				break;
			case MIME_MailEncode::BCC :
				$to = 'Bcc';
				break;
			default:
				break;
		}
		if ( empty( $this->$to ) ) {
			$param = '';
		} else {
			$param = "," . chr( 13 ) . chr( 10 ) . "\t";
		}
		$this->$to .= $param . "\"";
		$this->$to .= MIME_MailEncode::head2047f( $name, $this->Content_Transfer_Encoding, $this->charset );
		$this->$to .= "\" <" . $mail . ">";
	}

	public function setSubject( $subject ) {
		if ( is_string( $subject ) ) {
			$this->Subject = MIME_MailEncode::head2047f( $subject, $this->Content_Transfer_Encoding, $this->charset );
		}
	}

	public function setDate( $timestamp = null ) {
		if ( empty( $timestamp ) ) {
			$this->Date = date( 'D, j M Y H:i:s +0800' );
		} elseif ( is_int( $timestamp ) ) {
			$this->Date = date( 'D, j M Y H:i:s +0800', $timestamp );
		} else {
		}
	}

	public function setCharSet( $charset ) {
		switch( $charset ) {
			case MIME_MailEncode::GBK :
			case MIME_MailEncode::GB2312 :
			case MIME_MailEncode::UTF8 :
			case MIME_MailEncode::US_ASCII :
			case MIME_MailEncode::ISO_8859_1 :
				$this->charset = $charset;
				break;
			default :
				break;
		}
		return true;
	}

	public function setName( $name ) {
		if ( is_string( $name ) ) {
			$this->name = MIME_MailEncode::head2047f( $name, $this->Content_Transfer_Encoding, $this->charset );
		}
		return true;
	}

	public function setType( $type ) {
		switch( $type ) {
			case MIME_MailEncode::TEXT_PLAIN :
			case MIME_MailEncode::TEXT_HTML :
			case MIME_MailEncode::TEXT_XML :
				$this->Content_Type = $type;
				$this->isMIME = false;
				$this->isBin = false;
				break;
			case MIME_MailEncode::IMAGE_GIF :
			case MIME_MailEncode::IMAGE_JPEG :
			case MIME_MailEncode::IMAGE_PNG :
			case MIME_MailEncode::IMAGE_BMP :
			case MIME_MailEncode::APPLICATION_OCTET_STREAM :
				$this->Content_Type = $type;
				$this->isMIME = false;
				break;
			case MIME_MailEncode::MULTIPART_MIXED :
			case MIME_MailEncode::MULTIPART_ALTERNATIVE :
			case MIME_MailEncode::MULTIPART_DIGEST :
			case MIME_MailEncode::MULTIPART_RELATED :
			case MIME_MailEncode::MESSAGE_RFC822 :
			case MIME_MailEncode::MESSAGE_PARTIAL :
			case MIME_MailEncode::MESSAGE_EXTERNAL_BODY :
				$this->Content_Type = $type;
				$this->isMIME = true;
				break;
			default :
				break;
		}
		return true;
	}

	public function setBoundary( $boundary ) {
		if ( is_string( $boundary ) ) {
			$this->Boundary = $boundary;
		} else {
		}
		if ( $this->isMIME ) {
			$this->BODY->setBoundary( $boundary );
		}
	}

	public function setTransEncode( $encode ) {
		switch( $encode ) {
			case MIME_MailEncode::EN7BIT :
			case MIME_MailEncode::EN8BIT :
			case MIME_MailEncode::ENBINARY :
			case MIME_MailEncode::ENBASE64 :
			case MIME_MailEncode::ENQP :
				$this->Content_Transfer_Encoding = $encode;
				break;
			default :
				break;
		}
	}

	public function setDisposition( $disp, $filename = null ) {
		$this->filename = $filename;
		switch( $disp ) {
			case MIME_MailEncode::ATTACHMENT :
			case MIME_MailEncode::INLINE :
				$this->Content_Disposition = $disp;
				break;
			default :
				break;
		}
	}

	public function setID( $flag = null ) {
		if ( empty( $flag ) ) {
			$flag = '';
		} else {
			$flag = '@' . $flag;
		}
		$time = explode( ' ', microtime() );
		$this->Content_ID = $time[1] . strval( $time[0] ) . $flag;
	}

	public function setBODY( $body ) {
		if ( $this->isMIME ) {
			if ( $body instanceof MIME_Entity_Multipart ) {
				if ( $body->Content_Type == $this->Content_Type ) {
					$this->BODY = $body;
					$this->BODY->isSubEntity = false;
					$this->Boundary = $this->BODY->GetBoundary();
				} else {
				}
			} else {
			}
		} else {
			switch( $this->Content_Transfer_Encoding ) {
				case MIME_MailEncode::EN7BIT :
				case MIME_MailEncode::EN8BIT :
				case MIME_MailEncode::ENBINARY :
					$this->BODY = chunk_split( $body );
					break;
				case MIME_MailEncode::ENBASE64 :
					$this->BODY = chunk_split( base64_encode( $body ) );
					break;
				case MIME_MailEncode::ENQP :
					$this->BODY = chunk_split( MIME_MailEncode::str2QP( $body ) );
					break;
				default :
					break;
			}
		}
	}

	public function setLocation( $location = null ) {
		if ( is_string( $location ) || empty( $location ) ) {
			$this->Content_Location = $location;
		}
	}

	public function setBase( $base = null ) {
		if ( is_string( $base ) || empty( $base ) ) {
			$this->Content_Base = $base;
		}
	}

	public function Encode() {
		if ( empty( $this->From ) || empty( $this->To ) || empty( $this->Content_Type ) ) {
			return false;
		}
		$mail = 'From: ' . $this->From . chr( 13 ) . chr( 10 );
		$mail .= 'To: ' . $this->To . chr( 13 ) . chr( 10 );
		if ( !empty( $this->Reply_To ) ) {
			$mail .= 'Reply-To: ' . $this->Reply_To . chr( 13 ) . chr( 10 );
		}
		if ( !empty( $this->Cc ) ) {
			$mail .= 'Cc: ' . $this->Cc . chr( 13 ) . chr( 10 );
		}
		if ( !empty( $this->Bcc ) ) {
			$mail .= 'Bcc' . $this->Bcc . chr( 13 ) . chr( 10 );
		}
		if ( !empty( $this->Subject ) ) {
			$mail .= 'Subject: ' . $this->Subject . chr( 13 ) . chr( 10 );
		}
		if ( !empty( $this->Date ) ) {
			$mail .= 'Date: ' . $this->Date . chr( 13 ) . chr( 10 );
		}
		if ( $this->isMIME ) {
			$mail .= 'MIME-Version: ' . $this->MIME_Version . chr( 13 ) . chr( 10 );
			$mail .= 'Content-Type: ' . $this->Content_Type . ';' . chr( 13 ) . chr( 10 );
			$mail .= "\tboundary=\"" . $this->Boundary . "\"" . chr( 13 ) . chr( 10 ) . chr( 13 ) . chr( 10 );
			$mail .= $this->BODY->Encode();
		} else {
			$mail .= 'Content-Type: ' . $this->Content_Type . ';' . chr( 13 ) . chr( 10 );
			if ( !empty( $this->charset ) && !$this->isBin ) {
				$mail .= "\tcharset=\"" . $this->charset . "\"" . chr( 13 ) . chr( 10 );
			}
			if ( !empty( $this->name ) ) {
				$mail .= "\tname=\"";
				$mail .= MIME_MailEncode::head2047f( $this->name, $this->Content_Transfer_Encoding, $this->charset );
				$mail .= "\"" . chr( 13 ) . chr( 10 );
			}
			if ( !empty( $this->Content_Transfer_Encoding ) ) {
				$mail .= 'Content-Transfer-Encoding: ' . $this->Content_Transfer_Encoding . chr( 13 ) . chr( 10 );
			}
			if ( !empty( $this->Content_Disposition ) ) {
				$mail .= 'Content-Disposition: ' . $this->Content_Disposition;
				if ( !empty( $this->filename ) ) {
					$mail .= ';' . chr( 13 ) . chr( 10 ) . "\tfilename=\"";
					$mail .= MIME_MailEncode::head2047f( $this->filename, $this->Content_Transfer_Encoding,
						$this->charset );
					$mail .= "\"" . chr( 13 ) . chr( 10 );
				} else {
					$mail .= chr( 13 ) . chr( 10 );
				}
			}
			if ( !empty( $this->Content_ID ) ) {
				$mail .= 'Content-ID: <' . $this->Content_ID . '>' . chr( 13 ) . chr( 10 );
			}
			if ( !empty( $this->Content_Location ) ) {
				$mail .= 'Content-Location: ';
				$mail .= MIME_MailEncode::head2047f( $this->Content_Location, $this->Content_Transfer_Encoding,
					$this->charset );
				$mail .= chr( 13 ) . chr( 10 );
			}
			if ( !empty( $this->Content_Base ) ) {
				$mail .= 'Content-Base: ';
				$mail .= MIME_MailEncode::head2047f( $this->Content_Base, $this->Content_Transfer_Encoding,
					$this->charset );
				$mail .= chr( 13 ) . chr( 10 );
			}
			$mail .= chr( 13 ) . chr( 10 ) . $this->BODY;
		}
		return $mail;
	}
}
class MIME_Entity {
	protected $Content_Type;
	protected $Boundary;
	protected $Sub_MIME_Entity;
	protected $Content_Transfer_Encoding;
	protected $Content_Disposition;
	protected $Content_ID;
	protected $Content_Location;
	protected $Content_Base;
	protected $Content;
	protected $filename;
	protected $charset;

	public function __construct( $type ) {
		$this->Content_Type = $type;
		$this->Content_Transfer_Encoding = MIME_MailEncode::ENBASE64;
		$this->Content_Disposition = MIME_MailEncode::INLINE;
		$this->Boundary = null;
		$this->Content = '';
		$this->Sub_MIME_Entity = array();
		$this->Content_ID = '';
		$this->Content_Location = null;
		$this->Content_Base = null;
		$this->charset = MIME_MailEncode::UTF8;
		$this->filename = null;
	}

	Public Function __get( $name ) {
		switch( $name ) {
			case 'Content_Type':
				return $this->$name;
				break;
			default:
				break;
		}
	}

	public static function CreateEntity( $type ) {
		switch( $type ) {
			case MIME_MailEncode::TEXT_PLAIN :
			case MIME_MailEncode::TEXT_HTML :
			case MIME_MailEncode::TEXT_XML :
				$entity = new MIME_Entity_Text( $type );
				break;
			case MIME_MailEncode::IMAGE_GIF :
			case MIME_MailEncode::IMAGE_JPEG :
			case MIME_MailEncode::IMAGE_PNG :
			case MIME_MailEncode::IMAGE_BMP :
			case MIME_MailEncode::APPLICATION_OCTET_STREAM :
				$entity = new MIME_Entity_OCT( $type );
				break;
			case MIME_MailEncode::MULTIPART_MIXED :
			case MIME_MailEncode::MULTIPART_ALTERNATIVE :
			case MIME_MailEncode::MULTIPART_DIGEST :
			case MIME_MailEncode::MULTIPART_RELATED :
				$entity = new MIME_Entity_Multipart( $type );
				break;
			case MIME_MailEncode::MESSAGE_RFC822 :
			case MIME_MailEncode::MESSAGE_PARTIAL :
			case MIME_MailEncode::MESSAGE_EXTERNAL_BODY :
				$entity = new MIME_Entity_Message( $type );
				break;
			default :
				break;
		}
		if ( is_object( $entity ) ) {
			return $entity;
		} else {
		}
	}

	public function setTransEncode( $encode ) {
		switch( $encode ) {
			case MIME_MailEncode::EN7BIT :
			case MIME_MailEncode::EN8BIT :
			case MIME_MailEncode::ENBINARY :
			case MIME_MailEncode::ENBASE64 :
			case MIME_MailEncode::ENQP :
				$this->Content_Transfer_Encoding = $encode;
				break;
			default :
				break;
		}
	}

	public function setDisposition( $disp, $filename = null ) {
		$this->filename = $filename;
		switch( $disp ) {
			case MIME_MailEncode::ATTACHMENT :
			case MIME_MailEncode::INLINE :
				$this->Content_Disposition = $disp;
				break;
			default :
				break;
		}
	}

	public function setID( $flag = null ) {
		if ( empty( $flag ) ) {
			$flag = '';
		} else {
			$flag = '@' . $flag;
		}
		$time = explode( ' ', microtime() );
		$this->Content_ID = $time[1] . strval( $time[0] ) . $flag;
	}

	public function setContent( $content ) {
		switch( $this->Content_Transfer_Encoding ) {
			case MIME_MailEncode::EN7BIT :
			case MIME_MailEncode::EN8BIT :
			case MIME_MailEncode::ENBINARY :
				$this->Content = chunk_split( $content );
				break;
			case MIME_MailEncode::ENBASE64 :
				$this->Content = chunk_split( base64_encode( $content ) );
				break;
			case MIME_MailEncode::ENQP :
				$this->Content = chunk_split( MIME_MailEncode::str2QP( $content ) );
				break;
			default :
				break;
		}
	}

	public function setLocation( $location = null ) {
		if ( is_string( $location ) || empty( $location ) ) {
			$this->Content_Location = $location;
		}
	}

	public function setBase( $base = null ) {
		if ( is_string( $base ) || empty( $base ) ) {
			$this->Content_Base = $base;
		}
	}

	public function setCharSet( $charset ) {
		switch( $charset ) {
			case MIME_MailEncode::GBK :
			case MIME_MailEncode::GB2312 :
			case MIME_MailEncode::UTF8 :
			case MIME_MailEncode::US_ASCII :
			case MIME_MailEncode::ISO_8859_1 :
				$this->charset = $charset;
				break;
			default :
				break;
		}
		return true;
	}
}
class MIME_Entity_Multipart extends MIME_Entity {
	protected $isSubEntity;
	protected $PerantBoundary;

	public function __construct( $type ) {
		parent::__construct( $type );
		$time = explode( ' ', microtime() );
		$time = $time[1] . strval( $time[0] );
		$this->Boundary = '--=_NextPart_' . $time;
		$this->isSubEntity = true;
		$this->PerantBoundary = null;
	}

	Public Function GetBoundary() {
		return $this->Boundary;
	}

	Public Function __set( $name, $value ) {
		switch( $name ) {
			case 'isSubEntity':
				if ( is_bool( $value ) ) {
					$this->$name = $value;
				} else {
					$this->$name = true;
				}
				break;
			default:
				break;
		}
	}

	public function AddEntity( MIME_Entity $entity ) {
		$this->Sub_MIME_Entity[count( $this->Sub_MIME_Entity )] = $entity;
		if ( !( $entity instanceof MIME_Entity_Multipart ) ) {
			$entity->setBoundary( $this->Boundary );
		} else {
			$entity->setPerantBoundary( $this->Boundary );
		}
	}

	public function setPerantBoundary( $boundary ) {
		if ( is_string( $boundary ) ) {
			$this->PerantBoundary = $boundary;
		} else {
		}
	}

	public function setBoundary( $boundary ) {
		if ( is_string( $boundary ) ) {
			$this->Boundary = $boundary;
		} else {
		}
		if ( is_array( $this->Sub_MIME_Entity ) ) {
			for ( $i = 0; $i < count( $this->Sub_MIME_Entity ); $i++ ) {
				if ( !( $this->Sub_MIME_Entity[$i] instanceof MIME_Entity_Multipart ) ) {
					$this->Sub_MIME_Entity[$i]->setBoundary( $boundary );
				} else {
					$this->Sub_MIME_Entity[$i]->setPerantBoundary( $this->Boundary );
				}
			}
		}
	}

	public function Encode() {
		if ( $this->isSubEntity ) {
			$entity = '--' . $this->PerantBoundary . chr( 13 ) . chr( 10 );
			$entity .= 'Content-Type: ' . $this->Content_Type . ';' . chr( 13 ) . chr( 10 );
			$entity .= "\tboundary=\"" . $this->Boundary . "\"" . chr( 13 ) . chr( 10 ) . chr( 13 ) . chr( 10 );
		} else {
			$entity = '';
		}
		for ( $i = 0; $i < count( $this->Sub_MIME_Entity ); $i++ ) {
			$entity .= $this->Sub_MIME_Entity[$i]->Encode();
		}
		$entity .= '--' . $this->Boundary . '--' . chr( 13 ) . chr( 10 ) . chr( 13 ) . chr( 10 );
		return $entity;
	}
}
class MIME_Entity_Text extends MIME_Entity {
	public function __construct( $type ) {
		parent::__construct( $type );
	}

	public function setBoundary( $boundary ) {
		if ( is_string( $boundary ) ) {
			$this->Boundary = $boundary;
		} else {
		}
	}

	public function Encode() {
		if ( empty( $this->Boundary ) ) {
			return false;
		}
		$entity = '--' . $this->Boundary . chr( 13 ) . chr( 10 );
		$entity .= 'Content-Type: ' . $this->Content_Type . ';' . chr( 13 ) . chr( 10 );
		$entity .= "\tcharset=\"" . $this->charset . "\"" . chr( 13 ) . chr( 10 );
		$entity .= 'Content-Transfer-Encoding: ' . $this->Content_Transfer_Encoding . chr( 13 ) . chr( 10 );
		if ( !empty( $this->Content_Disposition ) ) {
			$entity .= 'Content-Disposition: ' . $this->Content_Disposition;
			if ( !empty( $this->filename ) ) {
				$entity .= ';' . chr( 13 ) . chr( 10 ) . "\tfilename=\"";
				$entity .= MIME_MailEncode::head2047f( $this->filename, $this->Content_Transfer_Encoding,
					$this->charset );
				$entity .= "\"" . chr( 13 ) . chr( 10 );
			} else {
				$entity .= chr( 13 ) . chr( 10 );
			}
		}
		if ( !empty( $this->Content_ID ) ) {
			$entity .= 'Content-ID: <' . $this->Content_ID . '>' . chr( 13 ) . chr( 10 );
		}
		if ( !empty( $this->Content_Location ) ) {
			$entity .= 'Content-Location: ';
			$entity .= MIME_MailEncode::head2047f( $this->Content_Location, $this->Content_Transfer_Encoding,
				$this->charset );
			$entity .= chr( 13 ) . chr( 10 );
		}
		if ( !empty( $this->Content_Base ) ) {
			$entity .= 'Content-Base: ';
			$entity .= MIME_MailEncode::head2047f( $this->Content_Base, $this->Content_Transfer_Encoding,
				$this->charset );
			$entity .= chr( 13 ) . chr( 10 );
		}
		$entity .= chr( 13 ) . chr( 10 ) . $this->Content . chr( 13 ) . chr( 10 ) . chr( 13 ) . chr( 10 );
		return $entity;
	}
}
class MIME_Entity_OCT extends MIME_Entity {
	protected $name;

	public function __construct( $type ) {
		parent::__construct( $type );
		$this->name = null;
	}

	public function setName( $name ) {
		if ( is_string( $name ) ) {
			$this->name = MIME_MailEncode::head2047f( $name, $this->Content_Transfer_Encoding, $this->charset );
		}
		return true;
	}

	public function setBoundary( $boundary ) {
		if ( is_string( $boundary ) ) {
			$this->Boundary = $boundary;
		} else {
		}
	}

	public function Encode() {
		if ( empty( $this->Boundary ) || empty( $this->name ) ) {
			return false;
		}
		$entity = '--' . $this->Boundary . chr( 13 ) . chr( 10 );
		$entity .= 'Content-Type: ' . $this->Content_Type . ';' . chr( 13 ) . chr( 10 );
		$entity .= "\tname=\"";
		$entity .= MIME_MailEncode::head2047f( $this->name, $this->Content_Transfer_Encoding, $this->charset );
		$entity .= "\"" . chr( 13 ) . chr( 10 );
		$entity .= 'Content-Transfer-Encoding: ' . $this->Content_Transfer_Encoding . chr( 13 ) . chr( 10 );
		if ( !empty( $this->Content_Disposition ) ) {
			$entity .= 'Content-Disposition: ' . $this->Content_Disposition;
			if ( !empty( $this->filename ) ) {
				$entity .= ';' . chr( 13 ) . chr( 10 ) . "\tfilename=\"";
				$entity .= MIME_MailEncode::head2047f( $this->filename, $this->Content_Transfer_Encoding,
					$this->charset );
				$entity .= "\"" . chr( 13 ) . chr( 10 );
			} else {
				$entity .= chr( 13 ) . chr( 10 );
			}
		}
		if ( !empty( $this->Content_ID ) ) {
			$entity .= 'Content-ID: <' . $this->Content_ID . '>' . chr( 13 ) . chr( 10 );
		}
		if ( !empty( $this->Content_Location ) ) {
			$entity .= 'Content-Location: ';
			$entity .= MIME_MailEncode::head2047f( $this->Content_Location, $this->Content_Transfer_Encoding,
				$this->charset );
			$entity .= chr( 13 ) . chr( 10 );
		}
		if ( !empty( $this->Content_Base ) ) {
			$entity .= 'Content-Base: ';
			$entity .= MIME_MailEncode::head2047f( $this->Content_Base, $this->Content_Transfer_Encoding,
				$this->charset );
			$entity .= chr( 13 ) . chr( 10 );
		}
		$entity .= chr( 13 ) . chr( 10 ) . $this->Content . chr( 13 ) . chr( 10 ) . chr( 13 ) . chr( 10 );
		return $entity;
	}
}
