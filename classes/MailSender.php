<?php
require_once 'PHPMailer.php';
require_once 'SMTP.class.php';

class MailSender
{
	private $content;
	private $Mail;
	private $subject;

	private $attachment;

	public function __construct($subject = null, $content = null, $title = null, $noFooter = false)
	{
		// Initialize the MailSender with subject, content, and title
		// If no content is provided, it will be set to null
		// If no subject is provided, it will be set to null
		// If no title is provided, it will be set to null
		// If no footer is provided, it will be set to false	

		$this->Mail = array();
		$this->content = null;
		$this->subject = null;
		$this->attachment = null;

		if($content !== null)
		{
			$ipUser = $this->getUserIpAddr();

			$html  = '<!DOCTYPE html>';
			$html .= '<html lang="fr">';
			$html .= '<head>';
			$html .= '  <meta charset="UTF-8">';
			$html .= '  <title>'.$title.'</title>';
			$html .= '  <style>a.btn {background-color: #005192; color: #ffffff !important; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;}</style>';
			$html .= '</head>';
			$html .= '<body style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;">';
			$html .= '  <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">';
			$html .= '    <tr>';
			$html .= '      <td style="background: rgba(177,18,38); color: #05060a; padding: 20px; text-align: center;">';
			$html .= '        <h2 style="color: #ffffff; margin: 0;">Joker Peintre</h2>';
			$html .= '      </td>';
			$html .= '    </tr>';
			$html .= '    <tr>';
			$html .= '      <td style="padding: 25px;">';

			$html .= $content;
			/*
			if(!$noFooter)
			{
				$html .= '        <p>Si vous n’êtes pas à l’origine de cette demande, nous vous invitons à ignorer cet e-mail ou à nous contacter immédiatement.<br> Pour information, Cette demande a été initiée depuis l’adresse IP suivante : <b>'.$ipUser.'</b>.</p>';
			}
			*/

			$html .= '<p>Cordialement,<br>';
			$html .= 'L’équipe Joker Peintre</p>';
			$html .= '      </td>';
			$html .= '    </tr>';
			$html .= '	<tr>';
			$html .= '		<td style="background:#f9fafb;padding:12px;font-size:12px;color:#64748b;text-align:center;">';
			$html .= '			© 2025 Joker Peintre – Cet email est généré automatiquement, merci de ne pas y répondre.';
			$html .= '		</td>';
			$html .= 	'</tr>';
			$html .= '  </table>';
			$html .= '</body>';
			$html .= '</html>';

			$this->content = $html;
		}

		if($subject !== null)
			$this->subject = $subject;

		
	}

	public function getUserIpAddr(){
		global $_SERVER;
	    if(!empty($_SERVER['HTTP_CLIENT_IP'])){
	        //ip from share internet
	        $ip = $_SERVER['HTTP_CLIENT_IP'];
	    }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
	        //ip pass from proxy
	        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	    }else{
	        $ip = $_SERVER['REMOTE_ADDR'];
	    }
	    return $ip;
	}

	public function setContent($content = null)
	{
		
		if($content !== null){
			$this->content = $content;
			return true;
		}

		return false;
	}

	public function setSubject($subject = null)
	{
		
		if($subject !== null){
			$this->subject = $subject;
			return true;
		}

		return false;
	}

	public function addDestinataire($DestinataireMail = null, $DestinataireName = null)
	{
		
		if($DestinataireMail !== null)
		{
			$this->Mail[$DestinataireMail] = ($DestinataireName ===  null ? 'unknown' : $DestinataireName);
			return true;
		}

		return false;
	}

	public function addAttachment($filePath){
		$this->attachment = $filePath;
	}

	public function send(){

		if(sizeof($this->Mail) == 0)
			return 'no mail seted';

		if($this->content === null)
			return 'no content seted';

		if($this->subject === null)
			return 'no subject seted';

		/* Lire les paramètres SMTP depuis la BDD (admin/réglages) avec fallback config.php */
		$smtpHost     = (function_exists('gs') && gs('smtp_host'))      ? gs('smtp_host')      : SMTP_HOST;
		$smtpPort     = (function_exists('gs') && gs('smtp_port'))      ? (int)gs('smtp_port') : SMTP_PORT;
		$smtpUser     = (function_exists('gs') && gs('smtp_user'))      ? gs('smtp_user')      : SMTP_USER;
		$smtpPass     = (function_exists('gs') && gs('smtp_pass'))      ? gs('smtp_pass')      : SMTP_PASS;
		$smtpFrom     = (function_exists('gs') && gs('smtp_from'))      ? gs('smtp_from')      : SMTP_FROM;
		$smtpFromName = (function_exists('gs') && gs('smtp_from_name')) ? gs('smtp_from_name') : SMTP_FROM_NAME;

		try {
			
			$mail = new PHPMailer();
			$mail->isSMTP();
			$mail->SMTPDebug  = 0;
			$mail->setFrom($smtpFrom, $smtpFromName);
			$mail->Host       = $smtpHost;
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
			$mail->Port       = $smtpPort;
			$mail->SMTPAuth   = true;
			$mail->Username   = $smtpUser;
			$mail->Password   = $smtpPass;
		 

			foreach($this->Mail as $UserMail => $UserName){
				$mail->addAddress($UserMail, $UserName);
			}

			if($this->attachment !== null AND file_exists($this->attachment)){
				$mail->addAttachment($this->attachment);
			}

			$mail->isHTML(true);                                      // email au format HTML
			$mail->Subject = $this->subject;
			
			$html = $this->content;

			$mail->Body    = $html;          // corps du message en HTML - Mettre des slashes si apostrophes
			$mail->CharSet = 'UTF-8';
			$mail->Encoding = 'base64';

			if($mail->send())
			{
				return true;
			}

		} catch(Exception $e){
			die('Exception reçue:' . $e->getMessage());
		}


	}
}