<?php
/*
 -------------------------------------------------------------------------
 Pdfmailer
 Copyright (C) 2016 by RemDev.
 -------------------------------------------------------------------------

 This software is governed under license Apache License

 --------------------------------------------------------------------------  
 */
 
 
	class pdfmailer
	{
  	protected $date;
    	protected $firstday;
    	protected $lastday;
	protected $firstfr;
	protected $lastfr;

		public function __construct()
		{
			$this->setDate($date);
			$this->setlastday($lastday);
			$this->setFirstfr($firstfr);
			$this->setLastfr($lastfr);
		}

		//getter
		public function setDate($date)
		{
			$this->date = date("Y-m-d", mktime(0,0,0,date(m)-1,1,date(Y)));
		}

		public function setLastday($lastday)
		{
			$this->lastday =  date("Y-m-t", strtotime($this->date));
		}

		public function setFirstfr($firstfr)
		{
			//$this->firstfr = '01-'.date(m).'-'.date(Y);
			$this->firstfr = date("d-m-Y", mktime(0,0,0,date(m)-1,1,date(Y)));

		}

		public function setLastfr($lastfr)
		{
			$this->lastfr = date("t-m-Y", strtotime($this->date));
		}

		//Setter
		public function namemonth()
		{
			return $this->namemonth;
		}

		public function date()
		{
			return $this->date;
		}

		public function lastday()
		{
			return $this->lastday;
		}

		public function firstfr()
		{
			return $this->firstfr;
		}

		public function lastfr()
		{
			return $this->lastfr;
		}

		//G�n�ration du rapport en pdf
		public function generate()
		{
			include('/var/www/site/db/db_conf_glpi.php');
			require ('/var/www/site/lib/fpdf/fpdf.php');
			$pdf = new FPDF();
				$pdf->AddPage();
				$pdf->SetTitle('Statistique M&R du '.$this->firstfr.' au '.$this->lastfr);
				$header = $pdf->SetY(37);
				$pdf->SetX(100);
				$pdf->SetFont('Arial','B',10);
				$pdf->Cell(0.5, 6, utf8_decode('Statistiques pour la date du '.$this->firstfr.' au '.$this->lastfr), 0, 0, 'C');
				$pdf->SetX(11);
				$pdf->Cell(180,6,$col,1);
				$pdf->ln();

				$all = $base_glpi->prepare("SELECT
					FROM_UNIXTIME(UNIX_TIMESTAMP(glpi_tickets.date),'%Y-%m') AS Dates, COUNT(glpi_tickets.id) AS Total_cr��s, glpi_users.realname, glpi_users.firstname
					FROM glpi_tickets
					LEFT JOIN glpi_tickets_users ON (glpi_tickets_users.tickets_id = glpi_tickets.id)
					LEFT JOIN glpi_users ON (glpi_users.id = glpi_tickets_users.users_id)
					WHERE NOT glpi_tickets.is_deleted AND (glpi_tickets_users.users_id IN (
						SELECT id
						FROM glpi_users
						WHERE user_dn like '%Service ??%'
					)
					AND glpi_tickets_users.type='2') AND ( glpi_tickets.date >= :debut AND glpi_tickets.date <= ADDDATE(:fin , INTERVAL 1 DAY))
					GROUP BY glpi_tickets_users.users_id
					ORDER BY Total_cr��s desc
				");

				// On execute la requ�te avec les variables
				$all->execute(array(
					'debut' => $this->date,
					'fin' => $this->lastday
				));

				$pdf->SetY(50);
				$pdf->SetX(100);
				$pdf->Cell(0.5,6, utf8_decode("Nombre de tickets Ouvert"),0 ,0, 'C');
				$pdf->SetX(11);
				$pdf->Cell(180, 6, $col, 1);
				$pdf->Ln();
				foreach($all as $tout){
					$name = $tout['realname'];
					$firstname = $tout['firstname'];
					$count = $tout['Total_cr��s'];
						$pdf->SetFont('Arial', 'B', 10);
						$pdf->SetX(11);
						$pdf->Cell(0.1, 6, utf8_decode($name));
						$pdf->Cell(60, 6, $col, 1);
						$pdf->Cell(0.1, 6, utf8_decode($firstname));
						$pdf->Cell(60 ,6 , $col, 1);
						$pdf->Cell(0.1, 6, utf8_decode($count));
						$pdf->cell(60, 6, $col, 1);
						$pdf->Ln();
				}

				//Parametre PDF
				$pdf->SetAuthor('RemDev');
    			$pdf->Output("/var/www/glpi/files/PDF/StatM&R.pdf", 'F');
			}

			public function send()
			{
				include('/var/www/site/db/db_conf_glpi.php');
				//Destinataire
				$mailcon = $base_glpi->query("SELECT name, realname, firstname, email
									FROM glpi_users
									LEFT JOIN glpi_useremails ON ( glpi_useremails.users_id = glpi_users.id)
									WHERE user_dn like '%Service ??%'
				");
				foreach ($mailcon as $contactmail)
				{
					$mail .= $contactmail['email'].', ';
				}


				//Fronti�re
				$boundary = md5(uniqid(rand(), true));
				//En-t�tes du mail
				$headers  = 'MIME-Version: 1.0' . "\r\n";
	 			$headers .= 'FROM: Administrateur GLPI <test@test.fr>' . "\r\n";
				$headers .=	"Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n\n";

				//subject
				$subject = "Statistiques du ".$this->firstfr." au ".$this->lastfr;

				//Corp du mail
				$body="--". $boundary ."\n";
				$body .= "Content-Type: text/html; charset=ISO-8859-1 \r\n\n";
				$body .= "
				Bonjour,<br><br>
				Vous trouverez ci-joint les statistiques du ".$this->firstfr." au ".$this->lastfr."<br><br>
				Cordialement,
				.\n\n";

				//Pi�ce jointe
				$fichier=file_get_contents('/var/www/glpi/files/PDF/StatM&R.pdf');
				$fichier=chunk_split( base64_encode($fichier) );
				if($fichier == true){
					$body = $body . "--" .$boundary. "\n";
					$body .= "Content-Type: application/pdf; name=\"Statistique du ".$this->firstfr." au ".$this->lastfr.".pdf\"\r\n";
					$body .= "Content-Transfer-Encoding: base64\r\n";
					$body .= "Content-Disposition: attachment; filename=\"Statistique du ".$this->firstfr." au ".$this->lastfr.".pdf\"\r\n\n";
					$body .= $fichier;
				}
				else {
					$body .= "Il y a eut une erreur avec le PDF !";
				}
				//Fin fronti�re
				$body = $body . "--" . $boundary ."--";

				//Envoi du mail
				mail($mail, $subject, $body, $headers);
			}
		}
	$reportsend = new pdfmailer;
	$reportsend->generate();
	$reportsend->send();
