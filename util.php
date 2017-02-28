<?php

function LEGGI_TUTTO($file)
{

	global $DATA, $NPROC, $PROC, $out, $numerolineelette, $re, $Sistema, $DEBUG, $DD;


	foreach($file as $ff)
	{

		$numerolineelette++;

		$f=trim($ff);

		// Feb 15 13:13:52 mailserver postfix/smtpd[24603]: RESTO
		$r=preg_match("/(.* \d+ \d\d:\d\d:\d\d).* (.*)\[(\d+)\]: (.*)/", $f, $or);

		if($r != 1 ) continue;

		$DATA=date_timestamp_get(DateTime::createFromFormat ( "M j H:i:s" , $or[1] ));
		if(0 < $DEBUG AND $DEBUG < 2  AND !(
				false != strstr( $f, 'Anonymous TLS connection established' )  or
				false != strstr( $f, 'UGFzc3dvcmQ6' )  or
				false != strstr( $f, 'lost connection after AUTH' )

		)  ) echo "\nDEBUG: LINEA LETTA $numerolineelette : $DATA : $f \n";

		$NPROC=$or[2];
		$PROC=$or[3];
		$resto=$or[4];

				//echo $or[4]."\n";

		$i=$or[4];

		//-----------------------------------------------------------------------------------------------------------------
		//
		//
		// 													INZIO PARSER
		//
		//
		//-------------------------------------------      MAIL      -----------------------------------------------------

		$re=1; $l='/([0-9A-Z]{10}): client=(.*)\[(.*)\], sasl_method=([A-Z]*), sasl_username=(.*)/';   // mail nuova che viene spedita con autenticazione
		if( 1 ==  preg_match( $l, $i, $out )    )
		{
			$m=TVM( $out[1], $out[2], $out[3] );
			$m->statusMail=ID;
			$m->userlogin=$out[5];
			//$m->AddI( array('TIPOLOGIN'=>$out[4], 'CREATOIDMAIL'=>$out[1]) );
			continue;
		}

		$re=2; $l='/([0-9A-Z]{10}): info: header Subject: (.*) from (.*)\[(.*)\]\; from\=\<(.*)\> to=\<(.*)\> proto=.?SMTP helo=\<(.*)\>/';
		if( 1 ==  preg_match( $l, $i, $out )    ) { $m=TVM( $out[1], $out[3], $out[4] );  $m->Subject=$out[2]; $m->FROM=$out[5]; $m->TO=$out[6]; $m->statusMail=ID; $m->AddI( array('LABEL'=>'Subject OK', 'HELO'=>$out[7]) ); continue;}


		$re=3; $l='/([0-9A-Z]{10}): client=(.*)\[(.*)\]/';   // mail nuova che arriva
		if( 1 ==  preg_match( $l, $i, $out )    )
		{
			$m=TVM( $out[1], $out[2], $out[3] );
			$m->statusMail=ID;
			//$m->AddI( array('LABEL'=>'ARRIVA MAIL', 'CREATOIDMAIL'=>$out[1]) );
			continue;
		}


		$re=4; $l='/([0-9A-Z]{10}): message-id=<(.*\.[0-9A-Z]{10}@mailserver.esseciesse.net)>/';   // mail nuova da connessione interna
		if( 1 ==  preg_match( $l, $i, $out )    ) {$m=TVM( $out[1], 'internal', '127.0.0.1'  );$m->mid=$out[2]; $m->statusMail=ID; $m->AddI( 'Mail creata direttamente in coda: probabile ricevuta di ritorno interna' ); continue;}

		$re=5; $l='/([0-9A-Z]{10}): message-id=\<(.*)\>/';
		if( 1 ==  preg_match( $l, $i, $out )    ) {  $m=TVM( $out[1] );  $m->mid=$out[2]; $m->statusMail=ID;  $m->AddI( 'mid definito' ); continue;}

		$re=6; $l='/([0-9A-Z]{10}): from=\<(.*)\>\, size=(\d+)\, nrcpt=(\d+) \((.*)\)/';  // accodato..
		if( 1 ==  preg_match( $l, $i, $out )    ) { $m=TVM( $out[1] ); $m->AddI( array('LABEL'=>'accodato..', 'SIZE'=>$out[3] ) ); $m->statusMail=ID;  continue;}

		$re=7; $l='/([0-9A-Z]{10}): to=<(.*)>, relay=(.*), delay=.*, delays=.*, dsn=.*, status=(.*) \((.*)\)/';
		if( 1 ==  preg_match( $l, $i, $out )    )
		{
					//$DEBUG=2;
					$m=TVM( $out[1] );
					$m->AddI( array( 'STATUS'=>$out[4], 'RELAY'=>$out[3], 'MESSAGE'=>$out[5] )  );
					if( $out[4] == 'sent' ) $m->statusMail=CLOSEOK;
					else                    $m->statusMail=CLOSENOK;
					//$DEBUG=$DD;
					continue;
		}


		$re=REGOLA_REMOVED; $l='/([0-9A-Z]{10}): removed/';  // OKKIO CHE LA REGOLA 8 e' rimosso dalla coda .. usato il numero 8 nella associazione mail/connessione
		if( 1 ==  preg_match( $l, $i, $out )    ) { $m=TVM( $out[1] ); $m->dataend=$DATA; $m->AddI( 'rimosso dalla coda' );  /*stampa($m, 'CHIUSA');*/  continue;}



		$re=9; $l='/([0-9A-Z]{10}): no signing table match for \'(.*)\'/';  // lo metto ma serve a poco...
		if( 1 ==  preg_match( $l, $i, $out )    ) { $m=TVM( $out[1] ); $m->AddI( array('OPENDKIM: no signing table match for'=>$out[2] ) ); continue;}

		$re=10; $l='/([0-9A-Z]{10}): no signature data/';
		if( 1 ==  preg_match( $l, $i, $out )    ) { continue;}  // non lo consideriamo...

		$re=11; $l='/([0-9A-Z]{10}): sender non-delivery notification: (.*)/';
		if( 1 ==  preg_match( $l, $i, $out )    ) { $m=TVM( $out[1] ); $m->AddI( 'sender non-delivery notification: NOTIFICA di NON INVIO : '.$out[2] ); continue;}

		$re=12; $l='/([0-9A-Z]{10}): (.*) \[(.*)\] not internal/';  // non si considera
		if( 1 ==  preg_match( $l, $i, $out )    ) { continue;}

		$re=13; $l='/([0-9A-Z]{10}): not authenticated/';  // non si considera
		if( 1 ==  preg_match( $l, $i, $out )    ) { continue;}

		$re=14; $l='/([0-9A-Z]{10}): DKIM verification successful/';
		if( 1 ==  preg_match( $l, $i, $out )    ) {   $m=TVM( $out[1] ); $m->AddI( 'DKIM OK' );  continue;}

		$re=15; $l='/([0-9A-Z]{10}): to=<(.*)>, (.*)/';
		if( 1 ==  preg_match( $l, $i, $out )    ) { if($DEBUG == 3 ) echo "Regola $re NON GESTITA\n"; continue; }

		$re=16; $l="/([0-9A-Z]{10}): can't determine message sender; accepting/";
		if( 1 ==  preg_match( $l, $i, $out )    ) {  $m=TVM( $out[1] ); $m->AddI( 'DKIM can t determine message sender; accepting' );  continue;}

		$re=17; $l='/([0-9A-Z]{10}): skipped, still being delivered/';
		if( 1 ==  preg_match( $l, $i, $out )    ) {  $m=TVM( $out[1] ); $m->AddI( 'QMNGR : skipped, still being delivered' ); continue;}

		$re=18; $l='/([0-9A-Z]{10}): conversation with (.*)\[(.*)\] timed out while receiving the initial server greeting/';
		if( 1 ==  preg_match( $l, $i, $out )    ) { $m=TVM( $out[1], $out[2], $out[3] ); $m->AddI( 'timeout: metto in coda e ci riprovo dopo ' ); continue;}

		$re=19; $l='/([0-9A-Z]{10}): lost connection with (.*)\[(.*)] while (.*)/';
		if( 1 ==  preg_match( $l, $i, $out )    ) {  $m=TVM( $out[1], $out[2], $out[3] ); $m->AddI( 'Lost Connection while: '.$out[4] ); continue;}

		$re=20; $l='/([0-9A-Z]{10}): message has signatures from (.*)/';
		if( 1 ==  preg_match( $l, $i, $out )    ) {  $m=TVM( $out[1] ); $m->AddI( 'DKIM : presente firma '.$out[2] );continue;}

		$re=21; $l='/([0-9A-Z]{10}): host (.*)\[(.*)\] said: (.*)/';
		if( 1 ==  preg_match( $l, $i, $out )    ) {
			$m=TVM( $out[1], $out[2], $out[3] );
			$m->AddW( array('WARNING'=>$out[4]) );
			continue;}

		$re=22; $l='/([0-9A-Z]{10}): sender delivery status notification: (.*)/';
		if( 1 ==  preg_match( $l, $i, $out )    ) { $m=TVM( $out[1] ); $m->AddI( 'Invio ricevuta di ritorno con mail : '.$out[2] ); continue;}

		$re=23; $l='/([0-9A-Z]{10}): DKIM-Signature field added \(s=(.*), d=(.*)\)/';
		if( 1 ==  preg_match( $l, $i, $out )    ) { $m=TVM( $out[1] ); $m->AddI( 'Aggiunta firma DKIM dominio '.$out[3] ); continue;}

		$re=24; $l='/([0-9A-Z]{10}): (.*)/';
		if( 1 ==  preg_match( $l, $i, $out )    ) { $m=TVM( $out[1]); $m->AddI( array( 'LABEL'=>'Informazione generica', 'info'=>$out[2])  ); continue;}

		$re=25; $l='/([0-9A-Z]{10}): host (.*)\[(.*)] refused to talk to me: (.*)/'; // indice di problemi...
		if( 1 ==  preg_match( $l, $i, $out )    ) {  MSGSistema();   $m=TVM( $out[1], $out[2], $out[3] ); $m->AddW( 'PROBLEMA: '.$out[4] );  continue;}

		//
		//-------------------------------------------      CONNECT      -----------------------------------------------------
		//

		$re=50; $l='/NOQUEUE: reject: RCPT from (.*)\[(.*)\]: 450 4.2.0 <(.*)>: Recipient address rejected: Sorry, your mail server has been greylisted for five minutes. Please come back later.; /';
		if( 1 ==  preg_match( $l, $i, $out )    ) { $c=TVC( $out[1], $out[2], $out[3]); $c->TO_pseudo=$out[3]; $c->AddW( array('Reject for GREYLIST: please retry LATER')  );continue;}


		$re=REGOLA_POSTGREY_51; $l='/action=greylist, reason=(.*), client_name=(.*), client_address=(.*), sender=(.*), recipient=(.*)/';
		if( 1 ==  preg_match( $l, $i, $out )    )
		{
			$c=TVC( $out[2], $out[3], $out[5] );
			if( $c == null ) {continue;}  // ritardo nel log di postgrey... non lo registro
			$c->FROM_pseudo=$out[4]; $c->TO_pseudo=$out[5]; $c->AddI( array( 'LABEL'=>'GreyListed', 'action'=>'greylist', 'reason'=>$out[1] ) ); continue;
		}

		$re=REGOLA_POSTGREY_52; $l='/action=greylist, reason=(.*), client_name=(.*), client_address=(.*), recipient=(.*)/';
		if( 1 ==  preg_match( $l, $i, $out )    )
		{
			$c=TVC( $out[2], $out[3], $out[4] );
			if( $c == null ) {continue;}  // ritardo nel log di postgrey... non lo registro
			$c->TO_pseudo=$out[4]; $c->AddI( array( 'LABEL'=>'GreyListed', 'action'=>'greylist', 'reason'=>$out[1] ) ); continue;
		}


		$re=REGOLA_POSTGREY_53; $l='/action=pass, reason=(.*), client_name=(.*), client_address=(.*), sender=(.*), recipient=(.*)/';
		if( 1 ==  preg_match( $l, $i, $out )    )
		{

			$c=TVC( $out[2], $out[3], $out[5] );
			if( $c == null ) {continue;}  // ritardo nel log di postgrey... non lo registro
//			if( $c->ID != ''  or $c->statusConnect==CNTC ) {continue;}  // ritardo nel log di postgrey... non lo registro
			$c->FROM_pseudo=$out[4]; $c->TO_pseudo=$out[5]; $c->AddI( array( 'LABEL'=>'GreyListed', 'action'=>'pass', 'reason'=>$out[1] ) ); continue;
		}

		$re=REGOLA_POSTGREY_54; $l='/action=pass, reason=(.*), client_name=(.*), client_address=(.*), recipient=(.*)/';
		if( 1 ==  preg_match( $l, $i, $out )    )
		{
			$c=TVC( $out[2], $out[3], $out[4] );
			if( $c == null ) {continue;}  // ritardo nel log di postgrey... non lo registro
			//			if( $c->ID != '' or $c->statusConnect==CNTC  ) {continue;}  // ritardo nel log di postgrey... non lo registro
			$c->TO_pseudo=$out[4]; $c->AddI( array( 'LABEL'=>'GreyListed', 'action'=>'pass', 'reason'=>$out[1] ) ); continue;
		}


		$re=55; $l='/NOQUEUE: reject: RCPT from (.*)\[(.*)]: 550 5.1.1 <(.*)>: Recipient address rejected: User unknown in virtual mailbox table; from=<(.*)> to=<(.*)> proto=.?SMTP helo=<(.*)>/';
		if( 1 ==  preg_match( $l, $i, $out )    ) { $c=TVC( $out[1], $out[2], $out[3] );  $c->FROM_pseudo=$out[4]; $c->TO_pseudo=$out[3]; $c->statusError=ERRMAIL; $c->AddW( array( 'LABEL'=>'Recipient address rejected: User unknown', 'To:'=>$out[3], 'HELO'=>$out[6])  ); continue;}

		$re=56; $l='/NOQUEUE: reject: RCPT from (.*)\[(.*)]: 450 4.1.8 <(.*)>: Sender address rejected: Domain not found; from=<(.*)> to=<(.*)> proto=.?SMTP helo=<(.*)>/';
		if( 1 ==  preg_match( $l, $i, $out )    ) { $c=TVC( $out[1], $out[2], $out[3] );  $c->FROM_pseudo=$out[4]; $c->TO_pseudo=$out[3]; $c->statusError=ERRMAIL; $c->AddW( array( 'LABEL'=>'Sender address rejected: Domain not found', 'To:'=>$out[3], 'HELO'=>$out[6])  ); continue;}

		$re=57; $l='/NOQUEUE: reject: RCPT from (.*)\[(.*)]: 554 5.7.1 Service unavailable; Client host (.*) blocked using (.*); from=<(.*)> to=<(.*)> proto=.?SMTP helo=<(.*)>/';
		if( 1 ==  preg_match( $l, $i, $out )    ) { $c=TVC( $out[1], $out[2], $out[6] ); $c->statusError=ERRMAIL; $c->FROM_pseudo=$out[5]; $c->TO_pseudo=$out[6]; $c->AddW( array( 'LABEL'=>'SPAM blocked', 'Service'=>$out[3], 'HELO'=>$out[7])  ); continue;}

		$re=58; $l='/NOQUEUE: reject: RCPT from (.*)\[(.*)\]: 501 5.5.2 <(.*)>: Helo command rejected: Invalid name; from=<(.*)> to=<(.*)> proto=ESMTP helo=<(.*)/';
		if( 1 ==  preg_match( $l, $i, $out )    ) { MSGSistema(); $c=TVC( $out[1], $out[2], $out[3] );  $c->FROM_pseudo=$out[4]; $c->TO_pseudo=$out[3]; $c->statusError=ERRMAIL; $c->AddW( array( 'LABEL'=>'Helo command rejected: Invalid name', 'To:'=>$out[3], 'HELO'=>$out[6])  );  continue;}

		$re=59; $l='/NOQUEUE: reject: RCPT from (.*)\[(.*)]: 554 5.7.1 <(.*)>: Relay access denied; from=<(.*)> to=<(.*)> proto=.?SMTP helo=<(.*)>/';
		if( 1 ==  preg_match( $l, $i, $out )    ) { MSGSistema(); $c=TVC( $out[1], $out[2], $out[3] );  $c->FROM_pseudo=$out[4]; $c->TO_pseudo=$out[3]; $c->statusError=ERRMAIL; $c->AddW( array( 'LABEL'=>'Relay access denied', 'To:'=>$out[3], 'HELO'=>$out[6])  );   continue;}

		$re=60; $l='/NOQUEUE: reject.*: RCPT from (.*)\[(.*)]: 554 5.7.1 (.*) (.*) from=<(.*)> to=<(.*)> proto=ESMTP helo=<(.*)>/';
		if( 1 ==  preg_match( $l, $i, $out )    ) { MSGSistema(); $c=TVC( $out[1], $out[2], $out[6] );  $c->FROM_pseudo=$out[5]; $c->TO_pseudo=$out[6]; $c->statusError=ERRMAIL; $c->AddW( array( 'LABEL'=>'Generic reject', 'msg1'=>$out[3], 'msg2'=>$out[4], 'To:'=>$out[3], 'HELO'=>$out[7])  );   continue;}




		$re=80; $l='/warning: (.*)\[(.*)\]: SASL (.*) authentication failed:(.*)/';
		if( 1 ==  preg_match( $l, $i, $out )    ) {  $c=TVC( $out[1], $out[2]  );$c->AddW( array( 'LABEL'=>'LOGIN authentication failed', 'USER'=>$out[4], 'TYPE'=>$out[3] )  ); continue; }

		$re=81; $l='/connect from localhost\[127.0.0.1\]/';  // prende anche disconnect from localhost[127.0.0.1]
		if( 1 ==  preg_match( $l, $i, $out )    ) { continue;}

		$re=82; $l='/^connect from (.*)\[(.*)\]/';
		if( 1 ==  preg_match( $l, $i, $out )    ) {  new conn( $out[1], $out[2] ); continue;}

		$re=83; $l='/Anonymous TLS connection established from (.*)\[(.*)\]: .*/';
		if( 1 ==  preg_match( $l, $i, $out )    ) {  $DEBUG=0; $c=TVC( $out[1], $out[2] ); $c->AddI( array('Anonymous TLS connection established') ); $DEBUG=$DD;  continue;}

		$re=REGOLA_DISCONNECT; $l='/^disconnect from (.*)\[(.*)\]/';   // OKKIO CHE e' usata nella associazione mail/conn
		if( 1 ==  preg_match( $l, $i, $out )    ) {  $c=TVC( $out[1], $out[2] );$c->disconnect(); $c->dataend=$DATA; Cancella_D_NULL($c);  continue;}

		$re=85; $l='/connect to (.*)\[(.*)\]:(.*) Connection timed out/';
		if( 1 ==  preg_match( $l, $i, $out )    ) {  $c=TVC( $out[2], $out[3] ); $c->statusError=ERRCONN; $c->statusConnect=CNTC; continue; }

		$re=86; $l='/connect to (.*)\[(.*)\]:(\d.): Cannot assign requested address/';  // dovrebbe non apparire piu'.. solo per IPV6 .. non funziona la connect..e quindi si perde la mail.. OKKIO
		if( 1 ==  preg_match( $l, $i, $out )    ) { continue;}


		$re=87; $l='/timeout after END-OF-MESSAGE from (.*)\[(.*)\]/';
		if( 1 ==  preg_match( $l, $i, $out )    ) { $c=TVC( $out[1], $out[2]); $c->AddW( 'Time out sulla connessione' ); continue;}

		$re=88; $l='/lost connection after (.*) from (.*)\[(.*)\]/';
		if( 1 ==  preg_match( $l, $i, $out )    ) { $DEBUG=0; $c=TVC( $out[2], $out[3]); $c->AddI( array( 'LABEL'=>'Lost Connection after ', 'after'=>$out[1])  ); $DEBUG=$DD; continue;}

		$re=89; $l='/SSL_accept error from (.*)\[(.*)\]: (.*)/';
		if( 1 ==  preg_match( $l, $i, $out )    ) { $c=TVC( $out[1], $out[2]); $c->statusError=ERRCONN; $c->AddW( array( 'LABEL'=>'SSL_accept error from', 'error'=>$out[3])  );  continue;}

 		$re=90; $l='/warning: Illegal address syntax from (.*)\[(.*)\] in (.*)/';
		if( 1 ==  preg_match( $l, $i, $out )    ) { $c=TVC( $out[1], $out[2]); $c->AddW( array('Errore di sistassi in connessione', $out[3]) ); $c->statusError=ERRMAIL;   continue;}

		$re=91; $l='/too many errors after DATA from (.*)\[(.*)\]/';
		if( 1 ==  preg_match( $l, $i, $out )    ) { $c=TVC( $out[1], $out[2]); $c->statusError=ERRCONN; $c->AddW( 'too many errors after DATA' ); continue;}

		$re=92; $l='/timeout after (.*) from (.*)\[(.*)\]/';
		if( 1 ==  preg_match( $l, $i, $out )    ) {  $c=TVC( $out[2], $out[3]);  $c->statusError=ERRCONN; $c->AddW( array( 'LABEL'=>'timeout after', 'Cosa'=>$out[1])  ); continue;}

		$re=93; $l='/improper command pipelining after (.*) from (.*)\[(.*)]: (.*)/';
		if( 1 ==  preg_match( $l, $i, $out )    ) {  $c=TVC( $out[2], $out[3]);  $c->statusError=ERRCONN; $c->AddW( array( 'LABEL'=>'improper command pipelining after', 'after'=>$out[1], 'cosa'=>$out[4] )  );  continue;}

		$re=94; $l='/whitelisted: (.*)\[(.*)\]/';
		if( 1 ==  preg_match( $l, $i, $out )    ) {  $c=TVC( $out[1], $out[2]);  $c->AddI( 'Postgrey: indirizzo whilisted dentro file di configurazione'  );  continue;}

		$re=95; $l='/SSL_connect error to (.*)\[(.*)\]:(\d*): (.*)/';
		if( 1 ==  preg_match( $l, $i, $out )    ) { $c=TVC( $out[1], $out[2]);  $c->statusError=ERRCONN; $c->AddW( array( 'SSL_connect error :'.$out[4])  ); continue;}

		$re=96; $l='/too many errors after RCPT from (.*)\[(.*)\](.*)/';
		if( 1 ==  preg_match( $l, $i, $out )    ) { $c=TVC( $out[1], $out[2]);  $c->statusError=ERRCONN; $c->AddW( array( 'too many errors after RCPT : '.$out[3])  ); continue;}



		//
		//-------------------------------------------      MSG   SISTEMA      -----------------------------------------------------
		//

		$re=120; $l='/cleaning (.*)/';
		if( 1 ==  preg_match( $l, $i, $out )    ) { MSGSistema();  continue;}

		$re=121; $l='/warning: TLS library problem/';   // connessione sballata...
		if( 1 ==  preg_match( $l, $i, $out )    ) {  MSGSistema();  continue;}

		$re=122; $l='/warning: (.*): RBL lookup error: (.*)/';
		if( 1 ==  preg_match( $l, $i, $out )    ) { MSGSistema(); continue;}

		$re=123; $l='/warning: hostname (.*) does not resolve to address (.*)/';  // non si considera...
		if( 1 ==  preg_match( $l, $i, $out )    ) { continue;}

		$re=124; $l='/Deleted: (\d*) message/';  // risposta ad un comando...
		if( 1 ==  preg_match( $l, $i, $out )    ) {  MSGSistema(); continue;}

		$re=125; $l='/warning: (.*)/';  // warnign geenrico
		if( 1 ==  preg_match( $l, $i, $out )    ) { MSGSistema();  continue;}

		$re=126; $l='/statistics: (.*)/';
		if( 1 ==  preg_match( $l, $i, $out )    ) { continue;}   // non faccio niente...

		$re=127; $l='/warning: no MX host for (.*) has a valid address record/';  // assoluto sospetto di spam.. non so come eliminare il warnign e regettare...  non lo posso legare ad alcuna connessione..
		if( 1 ==  preg_match( $l, $i, $out )    ) {  MSGSistema();  continue;}

		$re=128; $l='/connect to (.*)\[(.*)]:(\d*): No route to host/';  // connessioni finte di spam che non generano una mail.. la successiva connessione finisce in coda e non puo' essere consegnata..
		if( 1 ==  preg_match( $l, $i, $out )    ) {  MSGSistema();  continue;}

		$re=130; $l='/connect to (.*)\[(.*)]:(\d*): Connection refused/';
		if( 1 ==  preg_match( $l, $i, $out )    ) {  MSGSistema();   continue;}

		$re=131; $l='/Host offered STARTTLS: (.*)\[(.*)\](.*)/'; // non significativo
		if( 1 ==  preg_match( $l, $i, $out )    ) {  continue;}

		$re=132; $l='/fatal: (.*)/';
		if( 1 ==  preg_match( $l, $i, $out )    ) { MSGSistema();  continue;}

		$re=133; $l='/message repeated (.*)/';
		if( 1 ==  preg_match( $l, $i, $out )    ) { MSGSistema();  continue;}

		$re=134; $l='/terminating on signal (.*)/';
		if( 1 ==  preg_match( $l, $i, $out )    ) { MSGSistema();  continue;}

		$re=135; $l='/daemon started (.*)/';
		if( 1 ==  preg_match( $l, $i, $out )    ) { MSGSistema();  continue;}

		$re=136; $l='/reload --/';
		if( 1 ==  preg_match( $l, $i, $out )    ) { MSGSistema();  continue;}
					/*
					 $re=68; $l='//';
					 if( 1 ==  preg_match( $l, $i, $out )    ) { MSGSistema();  continue;}

					 $re=69; $l='//';
					 if( 1 ==  preg_match( $l, $i, $out )    ) { MSGSistema();  continue;}

					 $re=70; $l='//';
					 if( 1 ==  preg_match( $l, $i, $out )    ) { MSGSistema();  continue;}
					 */


		echo "COMANDO NON RICONOSCIUTO :".$i,"\n";

	}


}


function stampa($a, $label='')
{
	echo "<pre>$label\n";
	echo "$label  -> ";
	print_r($a);
	echo "</pre>\n";
}



class mail {

	var $IDX=0;
	var $ID=0;
	var $CA='';
	var $CN='';
	var $statusMail=CML; // 1 connect   2 greylistWait    3 greylistOK    4 ID     99 chiusa mailOK -1 close mail NOT OK con errore
	var $statusError=NOERR;  // 1 errore sulla connessione 2 errore sulla mail
	var $statusConnect=CNTO;
	var $mid='';
	var $dataini=0;
	var $dataend=0;
	var $FROM='';
	var $TO='';
	var $Subject='';
	var $userlogin='';
	var $IN_FILTRO_RICERCA=NO;
	var $idx_conn=0;

	var $linee=array();

	function __construct($ID, $CN=null, $CA=null)
	{
		global $DATA, $NPROC, $PROC, $out, $DEBUG, $MAIL;;

		if( $CN == null ) $CN='nullo.com';
		if( $CA == null ) $CA='0.0.0.0';

		$this->ID=$ID;
		$this->CA = $CA;
		$this->CN = strtolower($CN);
		$this->statusMail=CML;
		$this->dataini = $DATA;

		$MAIL[]=$this;
		end($MAIL);
		$this->IDX=key($MAIL);

		$this->AddI('created');

		//stampa($this, 'init__construct');

		if(0 < $DEBUG AND $DEBUG < 2 ) echo "DEBUG: INIT MAIL : ". $this->CA .' ' .$this->CN. ' ID_mail:'.$this->IDX."\n";



		return $this;
	}

	function AddI( $a ) { Add($this, array('TIPO'=>'INFO'), $a); 	}
	function AddW( $a )	{ Add($this, array('TIPO'=>'WARNING'), $a); }

}



class linea
{
	var $numlinea=0;
	var $data=0;
	var $datal='';
	var $regola=0;
	var $tipo='';
	var $label='';
	var $linea='';
	var $altro=array();

	function __construct($PA = array())
	{
		$a='';
		foreach($PA as $k => $v)
		{
			switch ($k)
			{
				case 'NLINEA': $this->numlinea = $v;                                      break;
				case 'DATA':   $this->data = $v; $this->datal= date ( "M j H:i:s", $v );  break;
				case 'REG':    $this->regola = $v;                                        break;
				case 'TIPO':   $this->tipo = $v;                                          break;
				case 'LABEL':  $this->label = $v;                                         break;
				case 'LINEA':  $this->linea = $v;                                         break;
				default : $this->altro[]=array($k => $v);					break;
			}
		}
	}

	function stampa()
	{

		$o=sprintf("%5d | %3d | %s | %s | %s | %s\n", $this->numlinea, $this->regola, $this->datal, $this->tipo, $this->label, $this->linea    );
		return $o;
	}

}


class conn
{
	var $IDX;
	var $CA='';
	var $CN='';
	var $statusConnect=CNTO; // open
	var $statusError=NOERR;
	var $dataini=0;
	var $dataend=0;
	var $IN_FILTRO_RICERCA=NO;
	var $TO_pseudo='';
	var $FROM_pseudo='';
	var $linee=array();
	var $nmail=0;


	function __construct($CN, $CA)
	{
		global $DATA, $NPROC, $PROC, $out, $DEBUG, $CONN;

		$this->CA = $CA;
		$this->CN = strtolower($CN);
		$this->statusConnect=CNTO;
		$this->dataini = $DATA;

		$CONN[]=$this;
		end($CONN);
		$this->IDX=key($CONN);

		$this->AddI('connect');

		//stampa($this, 'init__construct');

		if(0 < $DEBUG AND $DEBUG < 2 ) echo "DEBUG: INIT CONN : ". $this->CA .' ' .$this->CN. ' ID_conn:'.$this->IDX."\n";

		//stampa($this);

		return $this;
	}

	function disconnect()
	{
		$this->AddI('disconnect');
		$this->statusConnect=CNTC;
	}

	function AddI( $a )	{ Add($this, array('TIPO'=>'INFO'),    $a); }
	function AddW( $a ) { Add($this, array('TIPO'=>'WARNING'), $a); }

}


function Add(&$obj, $tipo, $x )
{
	global $DATA, $out, $numerolineelette, $DEBUG, $re;

	if(0 < $DEBUG AND $DEBUG < 2 ) echo "DEBUG: Dentro Add ID this=".$obj->IDX."\n";

	if( is_array($x) ) $a=$x;
	else $a=array($x);

//	if( !(  get_class($obj)  == 'mail' and  $obj->IDX == 0 	and  $numerolineelette < 100 ) )
//	{
		$b=array();
		foreach($a as $k => $v)
		{
			if( is_numeric( $k )  ) $b += array('LABEL'=>$v);
			else                    $b += array($k=>$v);
		}

		$x=array_merge( array('NLINEA'=>$numerolineelette, 'REG'=>$re, 'DATA'=>$DATA, 'LINEA'=> $out[0]), $tipo, $b );
		$obj->linee[]=new linea(  $x  );

		if(0 < $DEBUG AND $DEBUG < 2 ) { echo "DEBUG: ADD  \n"; /*stampa($b);*/ }
//	}
}



function Cancella_D_NULL($c)
{
	global $CONN, $DEBUG, $stra;

	foreach($c->linee as $ll )
		if( false != strstr( $ll->linea, 'UGFzc3dvcmQ6' ) or false != strstr( $ll->linea, 'UVXNlcm5hbWU6') )
		{
			unset( $CONN[  $c->IDX  ]  );
			$stra++;
			if(0 < $DEBUG AND $DEBUG < 2 ) { echo "DEBUG: CANCELLATA MAIL UGFzc3dvcmQ6 o UVXNlcm5hbWU6 . $stra ".  $c->IDX  ." \n"; /* $a=array_keys($CONN); stampa($c, "connessine"); */ }

		}
}


function TVM( $ID, $CN=null, $CA=null )  // ID e' chiave.. o la trova o la crea...
{
	global $DATA,  $MAIL, $DEBUG;

	if( $CN != null )   $CN=strtolower("$CN");

	$a=array_keys($MAIL);
	$idx = count( $a )-1;

	//if(0 < $DEBUG AND $DEBUG < 2 ) { echo "dentro test prima di ciclo\n"; stampa( $a ); }

	while( $idx >=0  )
	{
		//if(0 < $DEBUG AND $DEBUG < 2 ) echo "DEBUG: Test mail mail  $idx  \n";
		// cerco ID = ID mio   o   connessione = connesione mia   E   stato mail aperto..

		if(    $DATA - 7200 >  $MAIL[ $a[$idx] ]->dataini   )  // non vado indietro piu' di 2 ore...( la data di ora  - 2 ore piu' grande della data della mail..)
		{
			if(0 < $DEBUG AND $DEBUG < 2 ) echo "DEBUG: NON trovata mail  $CN $CA  $ID per DATA  ". $DATA .' '. $MAIL[ $a[$idx] ]->dataini . "  la CREO\n";

			return new mail($ID, $CN, $CA ) ;
		}


		//if(0 < $DEBUG AND $DEBUG < 2 ) echo "DEBUG: tipi  $ID ".gettype ( $ID )."  ".gettype ($MAIL[ $a[$idx] ]->ID)." ".$MAIL[ $a[$idx] ]->ID." ". $a[$idx]. " $idx \n";
		if(  $ID !== null  and  $ID === $MAIL[ $a[$idx] ]->ID  )
		{
			if(0 < $DEBUG AND $DEBUG < 2 ) { echo "DEBUG: trovata mail test1 idx= ".$a[$idx]. " ". $MAIL[ $a[$idx] ]->ID . " ". $ID ."\n"; /*stampa( $MAIL[ $a[$idx] ], $ID );*/ }

			return $MAIL[ $a[$idx] ];
		}

		$idx--;
	}

	if(0 < $DEBUG AND $DEBUG < 2 ) echo "DEBUG: NON trovata mail  $CN $CA  $ID  \n";
	return new mail($ID, $CN, $CA ) ;

}




function TVC(  $CN, $CA,  $TO=null, $FROM=null )
{
	global $DATA, $out, $CONN, $DEBUG, $re;

	if(0 < $DEBUG AND $DEBUG < 2 ) echo "DEBUG: Inizio trova conn   -$CN-  -$CA-  \n";

	if(  $CN==null or $CA==null ) return  null;
	$a=array_keys($CONN);
	$idx = count( $a )-1;

	//if(0 < $DEBUG AND $DEBUG < 2 ) { echo "dentro test prima di ciclo\n"; stampa( $a ); }

	while( $idx >= 0  )
	{
		if(0 < $DEBUG AND $DEBUG < 2 ) echo "DEBUG: Test conn  $idx  \n";
		// cerco ID = ID mio   o   connessione = connesione mia   E   stato mail aperto..

		if(    $DATA - 7200 >  $CONN[ $a[$idx] ]->dataini   )  // non vado indietro piu' di 2 ore...( la data di ora  - 2 ore piu' grande della data della mail..)
		{
			if(0 < $DEBUG AND $DEBUG < 2 ) echo "DEBUG: NON trovata CONN  $CN $CA  per DATA  ". $DATA .' '. $CONN[ $a[$idx] ]->dataini . "\n";
			if($re == REGOLA_POSTGREY_51 or $re == REGOLA_POSTGREY_52 or $re == REGOLA_POSTGREY_53 or $re == REGOLA_POSTGREY_54) return null;
			return new conn($CN, $CA);
		}

		if(   $TO != null   and $TO != ''  and   $CONN[ $a[$idx] ]->TO_pseudo != ''  )  // se e' prsente il campo TO e non e' vuoto   controllo anche quello..
		{

			if(
					$CONN[ $a[$idx] ]->TO_pseudo == $TO  and
					$CN  ===  $CONN[ $a[$idx] ]->CN 	and
					$CA  ===  $CONN[ $a[$idx] ]->CA  	and
					$CONN[ $a[$idx] ]->statusConnect == CNTO
					)
			{
				if(0 < $DEBUG AND $DEBUG < 2 ) { echo "DEBUG: trovata conn test3 idx= ".$a[$idx]."\n"; /*stampa( $MAIL[ $a[$idx] ] );*/ }

				return $CONN[ $a[$idx] ];
			}



		}
		else
		{
			if(		$CN  ===  $CONN[ $a[$idx] ]->CN 	and
					$CA  ===  $CONN[ $a[$idx] ]->CA  	and
					$CONN[ $a[$idx] ]->statusConnect == CNTO   // cerco connessione aperta ???
					)
			{
				if(0 < $DEBUG AND $DEBUG < 2 ) { echo "DEBUG: trovata conn test2 idx= ".$a[$idx]."\n"; /*stampa( $MAIL[ $a[$idx] ] );*/ }

				return $CONN[ $a[$idx] ];
			}

		}




		$idx--;
	}

	if(0 < $DEBUG AND $DEBUG < 2 ) echo "DEBUG: NON trovata conn  $CN $CA  creo una nuova $re \n";

	if($re == REGOLA_POSTGREY_51 or $re == REGOLA_POSTGREY_52 or $re == REGOLA_POSTGREY_53 or $re == REGOLA_POSTGREY_54) return null;

	return new conn($CN, $CA);

}



function AGGANCIA_C_M()
{
	global $MAIL, $CONN, $DEBUG;


	foreach( $MAIL as $k => $m  )
	{
		$dim=$m->dataini;
		$dem=$m->dataend;

		foreach( $CONN as $y => $c   )
		{

			if(  $dim < $c->dataini   )  continue;   // la data ini mail deve arrivare dopo la connect  m>= c  OK
			if(  $c->dataend !=0 and abs($dem - $c->dataend) > 300  )  continue;   // la differenza asoluta fra chiusura e remove non e' maggiore di 5 minuti...

			//if( $dem + 600 < $c->dataend   ) break;  // ottimizzazione nella ricerca.. se la data di fine mail + 10 minuti non ha trovato alcuna connessione evito di andare oltre .. non ho trovato nulla.

			//le date sono giuste.. controllo gli indirizzi

			if(0 < $DEBUG AND $DEBUG < 2 ) { printf(" IDm %d  IDc %d  CA M e C  CM M e C : -%s-  -%s- -%s- -%s- \n", $k, $y,  $m->CA,  $c->CA ,  $m->CN , $c->CN );  }
			if(  $m->CA == $c->CA    AND  $m->CN == $c->CN   )  // trovata
			{
				if(0 < $DEBUG AND $DEBUG < 2 ) { printf(" TROVATA !!! IDm %d  IDc %d  CA M e C  CM M e C : -%s-  -%s- -%s- -%s- \n", $k, $y,  $m->CA,  $c->CA ,  $m->CN , $c->CN );  }
				$m->idx_conn = $y;
				$c->nmail++;
				$m->statusConnect=$c->statusConnect;

				break;
			}
		}
		if( $m->idx_conn == 0  )   if(0 < $DEBUG AND $DEBUG < 2 ) {  printf("\n NON TROVATA CONNESSIONE x MAIL %d\n", $k  ); }
	}




	foreach( $MAIL as $mail )
	{

		if( $mail->idx_conn == 0  ) continue;  // non ha connessione

		$nl=array();

		$C=$CONN[ $mail->idx_conn ]->linee;
		$M=$mail->linee;

		$m=reset($M); $c=reset($C);

		while(true)
		{

            $m=current($M);
            $c=current($C);

            if( $m == false )
            {
            	while( $c != false )  { $nl[]=$c; $c=next($C);}
            	break;
            }
            if( $c == false )
            {
            	while( $m != false )  { $nl[]=$m; $m=next($M);}
            	break;
            }


			if( $m->data == $c->data  AND
				$c->regola == REGOLA_DISCONNECT  and
				$m->regola == REGOLA_REMOVED  				) 	{  $nl[]=$m; $nl[]=$c; next($M); next($C); continue; }


			if( $m->data == $c->data    )                       {  $nl[]=$c; next($C); continue; }
			if( $m->data >  $c->data    )                       {  $nl[]=$c; next($C); continue; }
			if( $m->data <  $c->data    )                       {  $nl[]=$m; next($M); continue; }

		}

//		stampa($nl);
		$mail->linee=$nl;

	}






}








function MSGSistema()
{
	global $DATA, $NPROC, $PROC, $out, $numerolineelette, $re, $Sistema;

	$Sistema[]=array('DATA'=>$DATA, 'DATAL'=>date ( "M j H:i:s", $DATA ), 'PROC'=>$PROC, 'NPROC'=>$NPROC, 'LINEA'=> $out[0], 'linealetta'=>$numerolineelette, 'regola'=>$re );

}



function PresetazioneStatistiche()
{

	global $exefile;

	$numerolineelette=$_SESSION['numerolineelette'];
	$stra=$_SESSION['stra'];
	$NomeFile=$_SESSION['NFILE'];




	$C[ CML ]=0;
	$C[ ID ]=0;
	$C[ CLOSEOK ]=0;
	$C[ CLOSENOK ]=0;

	$C[ CNTC ]=0;
	$C[ CNTO ]=0;

	$C[ NOERR ]=0;
	$C[ ERRCONN ]=0;
	$C[ ERRMAIL ]=0;

//	$mail0=count($_SESSION['MAIL'][0]->linee);
	$nmail=count($_SESSION['MAIL']);

	foreach( $_SESSION['MAIL'] as $M )
	{

		$C[ $M->statusMail  ]++;
		//if( $M->statusMail == CLOSENOK ) { echo "PIPPPOOOO ". $M->IDX . " \n"; stampa($M); }
		$C[ $M->statusConnect  ]++;
		$C[ $M->statusError  ]++;

	}

	echo "<pre>";

	echo "File letto: $NomeFile\n";

	$vu="                  ";
	$c1="CONN OPEN   ". str_pad($C[CML], 6, " ", STR_PAD_LEFT);
	$c2="CLOSEOK     ". str_pad($C[CLOSEOK], 6, " ", STR_PAD_LEFT);
	$c3="ID          ". str_pad($C[ID], 6, " ", STR_PAD_LEFT);
	$c4="CNTC         ".str_pad($C[CNTC], 6, " ", STR_PAD_LEFT);
	$c5="CNTO         ".str_pad($C[CNTO], 6, " ", STR_PAD_LEFT);
	$c6="NOERR        ".str_pad($C[NOERR], 6, " ", STR_PAD_LEFT);
	$c7="ERRCONN      ".str_pad($C[ERRCONN], 6, " ", STR_PAD_LEFT);
	$c8="CLOSENOK    ". str_pad($C[CLOSENOK], 6, " ", STR_PAD_LEFT);
	$c9="ERRMAIL      ".str_pad($C[ERRMAIL], 6, " ", STR_PAD_LEFT);
	$c10="Auth NO     ". str_pad($stra, 6, " ", STR_PAD_LEFT);
	$c11="Linee        ".str_pad($numerolineelette, 6, " ", STR_PAD_LEFT);
	$c12="Mail totali <b style='color:red'>".str_pad($nmail, 6, " ", STR_PAD_LEFT).'</b>';

	echo "$c1             $c4\n";
	echo "$c2             $c5\n";
	echo "$c8             \n";
	echo "$c3             $c6\n";
	echo "$vu             $c7\n";
	echo "$c10             $c9\n";
	echo "$c12             $c11\n";

	echo "\n";

	system ( $exefile. " " .  $_SESSION['NFILE'] );

	echo "</pre>";
	//stampa($MAIL, "alla fine...");

	//stampa($Sistema, "ERRORI DI SISTEMA...");




}



function FILTRA($c, &$q, &$MAIL)
{

	$q=0;
    foreach($MAIL as $k => $m) $m->IN_FILTRO_RICERCA=0;  // pulisco filtro di ricerca
	if( isset($c['RAW'])  )
	{
		if( isset(  $MAIL[ $c['CERCA'] ] )   )	{	$MAIL[ $c['CERCA'] ]->IN_FILTRO_RICERCA=1; $q++; return;  }
		else return;
	}

	if(  isset( $c['TUTTE'] ) )
	{
		foreach($MAIL as $k => $m) { $m->IN_FILTRO_RICERCA=2; $q++;}
		return;
	}

	if( isset($c['RAW'])  )
	{
		if( isset(  $MAIL[ $c['CERCA'] ] )  )	{	$MAIL[ $c['CERCA'] ]->IN_FILTRO_RICERCA=3; $q++; return;  }
		else return;
	}


	$cosa=$c['CERCA'];

	if( strlen($cosa) > 2   )  	$cercatesto=true;
	else						$cercatesto=false;

	foreach($MAIL as $k => $m)
	{
		if( $cercatesto == true )
		{
			if( isset( $c['IDS'] )        and   strstr( $m->ID       , $cosa  )    )   { $m->IN_FILTRO_RICERCA=4; $q++; continue; }
			if( isset( $c['FROM'] )      and   strstr( $m->FROM     , $cosa  )    )   { $m->IN_FILTRO_RICERCA=5; $q++; continue; }
			if( isset( $c['TO'] )        and   strstr( $m->TO       , $cosa  )    )   { $m->IN_FILTRO_RICERCA=6; $q++; continue; }
			if( isset( $c['mid'] )       and   strstr( $m->mid      , $cosa  )    )   { $m->IN_FILTRO_RICERCA=7; $q++; continue; }
			if( isset( $c['Subject'] )   and   strstr( $m->Subject  , $cosa  )    )   { $m->IN_FILTRO_RICERCA=8; $q++; continue; }

			if( isset( $c['LINEA'] )  )   foreach(  $m->linee as $l  ) if(  strstr( $l->linea  , $cosa  )  ) { $m->IN_FILTRO_RICERCA=9; $q++; continue; }
		}

		if( isset($c['CLOSEOK'])  and $m->statusMail == CLOSEOK )       { $m->IN_FILTRO_RICERCA=10; $q++; continue; }
		if( isset($c['CLOSENOK'])  and $m->statusMail == CLOSENOK )   	{ $m->IN_FILTRO_RICERCA=11; $q++; continue; }
		if( isset($c['INVIATE'])  and $m->userlogin != '' )             { $m->IN_FILTRO_RICERCA=12; $q++; continue; }
		if( isset($c['RICEVUTE'])  and $m->userlogin == '' )            { $m->IN_FILTRO_RICERCA=13; $q++; continue; }

		if( isset($c['CML'])  and $m->statusMail == CML )            	{ $m->IN_FILTRO_RICERCA=14; $q++; continue; }
		if( isset($c['GLWT'])  and $m->statusMail == GLWT )            	{ $m->IN_FILTRO_RICERCA=15; $q++; continue; }
		if( isset($c['GLOK'])  and $m->statusMail == GLOK )            	{ $m->IN_FILTRO_RICERCA=16; $q++; continue; }
		if( isset($c['ID'])  and $m->statusMail == ID )            		{ $m->IN_FILTRO_RICERCA=17; $q++; continue; }

		if( isset($c['CNTC'])  and $m->statusConnect == CNTC )          { $m->IN_FILTRO_RICERCA=18; $q++; continue; }
		if( isset($c['CNTO'])  and $m->statusConnect == CNTO )          { $m->IN_FILTRO_RICERCA=19; $q++; continue; }

		if( isset($c['NOERR'])  and $m->statusError == NOERR )          { $m->IN_FILTRO_RICERCA=20; $q++; continue; }
		if( isset($c['ERRCONN'])  and $m->statusError == ERRCONN )      { $m->IN_FILTRO_RICERCA=21; $q++; continue; }
		if( isset($c['ERRMAIL'])  and $m->statusError == ERRMAIL )      { $m->IN_FILTRO_RICERCA=22; $q++; continue; }



	}

}




function stampamail($m, $OPZ=array() )
{
	if( isset( $OPZ['RAW'] ) )
	{
		echo "<pre>\n";
		echo print_r($m);
		echo "</pre>\n";

		if( $m->idx_conn != 0 and isset(  $_SESSION['CONN'][  $m->idx_conn  ] ) )
		{
			echo "<pre>\n";
			echo print_r($_SESSION['CONN'][  $m->idx_conn  ]);
			echo "</pre>\n";
		}
		return;

	}


	$stM =array( GLWT  => "GreyListWait",    GLOK=> "GreyListOK",  ID=> "ID",   CLOSEOK=> "CLOSE - OK",  CLOSENOK=> "CLOSE NOTOK", CML=>"Connecting" );
	$stMC=array( GLWT  => "warning", GLOK=> "warning",  ID=> "info", CLOSEOK=> "success",  CLOSENOK=> "danger",   CML=>"info" );

	$stC= array( CNTC =>  "DISconnect OK",     CNTO => "Conn Aperta" );
	$stCC=array( CNTC =>  "success",  CNTO => "warning" );

	$stE= array( NOERR => "NO ERR",    ERRCONN => "ERR CONN", ERRMAIL=> "ERR MAIL" );
	$stEC=array( NOERR => "success",  ERRCONN => "warning", ERRMAIL=> "warning" );

	$idmail=$m->IDX;
//stampa($OPZ);
	?>

	<div class="panel panel-default">
    <div class="panel-heading" role="tab" id="heading_<?php echo$idmail;?>">
      <h6 class="panel-title" style="font-size:12px; ">

		<span class="label label-primary">Data</span> <?php echo date ( "M j H:i:s",  $m->dataend)?>
		<span class="label label-primary">From</span> <?php echo $m->FROM?>
		<span class="label label-primary">To</span> <?php echo $m->TO?>
		<span class="label label-primary">Subject</span> <?php echo $m->Subject?>

		<span class="label label-primary">Status:  MAIL | CONN | ERR MAIL </span>
		<span class="label label-<?php echo $stMC[ $m->statusMail ]?>"><?php echo $stM[ $m->statusMail ]?></span>
		<span class="label label-<?php echo $stCC[ $m->statusConnect ]?>"><?php echo $stC[ $m->statusConnect ]?></span>
		<span class="label label-<?php echo $stEC[ $m->statusError ]?>"><?php echo $stE[ $m->statusError ]?></span>

        <a class="amio" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse_<?php echo$idmail;?>"  aria-expanded="false" aria-controls="collapse_<?php echo$idmail;?>">
        <span class=" glyphicon glyphicon-eye-open" aria-hidden="true"></span></a>

		<p></p>

		<span class="label label-primary">IDX</span> <?php echo $m->IDX?>
		<span class="label label-primary">ID</span> <?php echo $m->ID?>
		<span class="label label-primary">Addr</span> <?php echo $m->CA?>
		<span class="label label-primary">IP</span> <?php echo $m->CN?>
		<span class="label label-primary">id</span> <?php echo $m->mid?>
		<span class="label label-primary">Ini</span> <?php echo date( "M j H:i:s",  $m->dataini)?>
		<span class="label label-primary">End</span> <?php echo date( "M j H:i:s",  $m->dataend)?>
		<span class="label label-primary">Login</span> <?php echo $m->userlogin?>

      </h6>
    </div>
    <div id="collapse_<?php echo$idmail;?>" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading_<?php echo$idmail;?>">
      <div class="panel-body">

<pre>
<?php
			$o='';
			foreach($m->linee as $l   )
			{

				$o .= $l->stampa();
			}

			$o= str_replace ( '>' , '&gt;' , $o );
			$o= str_replace ( '<' , '&lt;' , $o );
			echo "$o";

?>
</pre>


<?php if( isset( $OPZ['DETAIL'] )  ) {  ?>

       <a class="amio" role="button" data-toggle="collapse" data-parent="#accordion1" href="#collapse1_<?php echo$idmail;?>"  aria-expanded="false" aria-controls="collapse1_<?php echo$idmail;?>">
        <span class=" glyphicon glyphicon-eye-open" aria-hidden="true"></span></a>

<?php } ?>


      </div>
    </div>

<!--<div class="panel-heading" role="tab" id="heading1_<?php echo$idmail;?>"></div>-->

<?php if( isset( $OPZ['DETAIL'] )  ) {  ?>

    <div id="collapse1_<?php echo$idmail;?>" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading_<?php echo$idmail;?>">
      <div class="panel-body">
<?php


		echo "<pre style='font-size:80%'>\n";
                print_r($m);
                echo "</pre>\n";

                if( $m->idx_conn != 0 and isset(  $_SESSION['CONN'][  $m->idx_conn  ] ) )
                {
                        echo "<pre style='font-size:80%'>\n";
                        print_r($_SESSION['CONN'][  $m->idx_conn  ]);
                        echo "</pre>\n";
                }


?>
     </div>
    </div>

    <?php } ?>


  </div>

	<?php
}

























?>
