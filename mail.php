<?php
/*
$a=getcwd();
echo " cur $a\n";

phpinfo();
die(); */
define ( "CML", 1 );
define ( "GLWT" , 2 );// 1 connect   2 greylistWait    3 greylistOK    4 ID     99 chiusa mailOK -1 close mail NOT OK con errore
define ( "GLOK" , 3 );
define ( "ID" , 4 );
define ( "CLOSEOK" , 10 );
define ( "CLOSENOK" , 20 );

define ( "CNTC" , 30 );// 1 connect  0 chiusa connessione
define ( "CNTO" , 31 );

define ( "NOERR" , 40 );
define ( "ERRCONN" , 41 );
define ( "ERRMAIL" , 42 );
define ( "CONN" , 0 );
define ( "MAIL" , 1 );

define ( "REGOLA_REMOVED", 8);
define ( "REGOLA_DISCONNECT", 84);

define ( "REGOLA_POSTGREY_51", 51);
define ( "REGOLA_POSTGREY_52", 52);
define ( "REGOLA_POSTGREY_53", 53);
define ( "REGOLA_POSTGREY_54", 54);

require_once ('util.php');

session_start();




//print_r($_SESSION);

ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 300);





ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$numerolineelette=0;
$stra=0;

$exefile="./mail_util.sh";


$DEBUG=0;
$DD=$DEBUG;

$Sistema=array();
$MAIL=array();
$CONN=array();
$LINEA=array();





$NomeFile='';

$nome_file_all="/tmp/__MAIL_LOG_ALL.log";



if( isset( $_GET['leggi']   ) )
{

  if(  $_GET['leggi']  == 'tutto'   )
  {
  	system("(zcat `ls -rt /var/log/mail.log.*.gz`; cat /var/log/mail.log) > $nome_file_all");
  	$NomeFile=$nome_file_all;
  }


  if(  $_GET['leggi']  == 'ieri'   )
  {
  	system("zcat /var/log/mail.log.1.gz > $nome_file_all");
  	$NomeFile=$nome_file_all;
  }


  if(  $_GET['leggi']  == 'ierioggi'   )
  {
  	system("(zcat /var/log/mail.log.1.gz; cat /var/log/mail.log) > $nome_file_all");
  	$NomeFile=$nome_file_all;
  }


  if(  $_GET['leggi']  == 'oggi'    )
  {
  	$NomeFile='/var/log/mail.log';
  	//$NomeFile='./mail.log';
  }

    $file=file($NomeFile);

    LEGGI_TUTTO($file);

    //stampa($LINEA);

    AGGANCIA_C_M();

    /* foreach( $MAIL as $m)
    {
    echo "mail e connessione\n\n\n";
      stampa($m);
      stampa( $CONN[$m->idx_conn]  );
    }
 */



    $_SESSION['MAIL']=$MAIL;
    $_SESSION['CONN']=$CONN;
    $_SESSION['LINEA']=$LINEA;
    $_SESSION['Sistema']=$Sistema;


    $_SESSION['NFILE']=$NomeFile;
    $_SESSION['numerolineelette']=$numerolineelette;
    $_SESSION['stra']=$stra;



    header("Location: mail.php?letti");
    die();
}




?>
<!DOCTYPE html>
<html>
<head>
<title>Procedure Analisi MAIL by SCS s.a.s.</title>
<script src="../LIB/jquery.js"></script>
<link rel="stylesheet" href="../LIB/bootstrap/css/bootstrap.min.css" >
<link rel="stylesheet" href="../LIB/bootstrap/css/bootstrap-theme.min.css" >
<meta name="viewport" content="width=device-width, initial-scale=1">
<script src="../LIB/bootstrap/js/bootstrap.min.js" ></script>
<style>
.amio a:hover, a:visited, a:link, a:active {     text-decoration: none;}
.amio { font-size: 20px; }
.label {margin: 0px 2px 0px 8px;  }
</style>
</head>

<body>


<?php


if(  isset(  $_GET['ALL']  ) )
{

	if( ! isset($_SESSION['MAIL'][0]) ) die('sessione scaduta...');

	$file=file($_SESSION['NFILE']);

	echo "<pre>";
	$nl=0;
	echo "num l | idx M  | idx C  |\n";
	foreach($file as $ff)
	{

		$nl++;

		$f=trim($ff);

		// Feb 15 13:13:52 mailserver postfix/smtpd[24603]: RESTO
		$r=preg_match("/(.* \d+ \d\d:\d\d:\d\d).* (.*)\[(\d+)\]: (.*)/", $f, $or);

		if($r != 1 ) continue;

		$DATA=date_timestamp_get(DateTime::createFromFormat ( "M j H:i:s" , $or[1] ));

		$NPROC=$or[2];
		$PROC=$or[3];
		$resto=$or[4];

		//echo $or[4]."\n";

		$i=$or[4];

		$i= str_replace ( '>' , '&gt;' , $i );
		$i= str_replace ( '<' , '&lt;' , $i );


		$nm="";$nc="";
		if( isset ( $_SESSION['LINEA'][$nl][MAIL] ) )
		{
			$M=$_SESSION['MAIL'][    $_SESSION['LINEA'][$nl][MAIL]    ];
			foreach($M->linee as $l )
			{
				if( $l->numlinea == $nl ) { $nm .= sprintf(" %4d ", $M->IDX);  $nc .= sprintf(" %4d ", $M->idx_conn); break;}
			}
		}

		if( $nm == '' )
		{

			if( isset ( $_SESSION['LINEA'][$nl][CONN] )  and isset(    $_SESSION['CONN'][   $_SESSION['LINEA'][$nl][CONN]    ]   ) )
			{
				$C=$_SESSION['CONN'][   $_SESSION['LINEA'][$nl][CONN]    ];
				foreach($C->linee as $l )
				{
						if( $l->numlinea  == $nl ) { $nc .= sprintf(" %4d ", $C->IDX); break;}
				}
			}
		}

		printf("%5d | %6s | %6s | %s %s\n",  $nl, $nm, $nc, $or[1], $i );

	}



	echo "</pre>";

	echo "</body></html>";

	die();

}




if( isset( $_GET['letti']   ) )
{

	if( ! isset($_SESSION['MAIL'][0]) ) die('sessione scaduta...');

    echo '<div class="container">';

    PresetazioneStatistiche();
    echo "<a href='mail.php'>Torna alla scelta iniziale</a><p></p>";
    echo "<a href='mail.php?ALL' target='_blank'>Vedi file raw</a><p></p>";


    ?>
    <p></p>
    <label>Ricerca nelle mail (NO CONNESSIONI). Solo le mail che soddisfano i requisiti sono selezionate.</label><br>
    <form action="mail.php" method="GET">
	<div class="checkbox">
    	<div style="padding-left:50px;">
   				<input type="text" class="form-control" id="ex1" name="CERCA" placeholder="Testo da trovare nei campi della mail selezionati sotto" >
   			<p class="help-block">Dove cerco ...</p>

<div class="row">
  <div class="col-md-1"><label><input type="checkbox" name="IDS">ID</label></div>
  <div class="col-md-1"><label><input type="checkbox" name="FROM">From</label></div>
  <div class="col-md-1"><label><input type="checkbox" name="TO">To</label></div>
  <div class="col-md-1"><label><input type="checkbox" name="mid">id mail</label></div>
  <div class="col-md-1"><label><input type="checkbox" name="Subject">Subject</label></div>
  <div class="col-md-1"><label style=" white-space: nowrap;"><input type="checkbox" name="LINEA">Ovunque</label></div>
  <div class="col-md-1"><label style=" white-space: nowrap;"><input type="checkbox" name="RAW">Raw idx</label></div>
</div>


    	<p></p>
		</div>
    	<b>Filtro su stato</b><br>
    	<div class="row">

  			<div class="col-md-6">
      			<label><input type="checkbox" name="CML" >Mail con sola connessione di apertura (SPAM!)</label><br>
 <!--   			<label><input type="checkbox" name="GLWT">Mail in WAIT su PostGrey ( SPAM ! )</label><br>
     			<label><input type="checkbox" name="GLOK" >Mail in OK su PostGrey e poi nulla..</label><br> -->
   				<label><input type="checkbox" name="ID">Mail con un ID ma non chiuse</label><br>
     			<label><input type="checkbox" name="CLOSEOK" >Solo Mail chiuse correttamente</label><br>
    			<label><input type="checkbox" name="CLOSENOK">Mail NON chiuse correttamente </label><br>
				<p></p>
				<label><input type="checkbox" name="DETAIL">Attiva dettaglio mail e connessione.</label><br>
    		</div>
    		<div class="col-md-6">
     			<label><input type="checkbox" name="CNTC" >Mail con disconnessione</label><br>
    			<label><input type="checkbox" name="CNTO">Mail aperte senza disconnessione </label><br>

<!--    			<label><input type="checkbox" name="NOERR">Stato di errore della mail (NO ERR)</label><br>
     			<label><input type="checkbox" name="ERRCONN" >Stato di errore della mail (ERR CONN)</label><br>
     			<label><input type="checkbox" name="ERRMAIL" >Stato di errore della mail (ERR MAIL)</label><br> -->

    			<label><input type="checkbox" name="INVIATE">Solo Mail inviate</label><br>
    			<label><input type="checkbox" name="RICEVUTE">Solo Mail ricevute</label><br>
				<p></p>
				<label><input type="checkbox" name="TUTTE">Nessun filtro.. mostra tutte le mail.</label><br>
    		</div>

		</div>



    </div>
    <button type="submit" class="btn btn-default" >Submit</button>
    </form>

    </div>
    </body>
    </html>

    <?php
    //stampa($_SESSION['MAIL'][0]);

    die();

}


if(  isset(  $_GET['CERCA']  ) )
{

  if( ! isset($_SESSION['MAIL'][0]) ) die('sessione scaduta...');



  FILTRA($_GET, $quante, $_SESSION['MAIL']);


  echo "<h2>selezionate ".$quante." MAIL</h2>";
  echo "<a href='mail.php'>Torna alla scelta iniziale</a><p></p>";
  echo "<a href='mail.php?letti'>Torna alla pagina di ricerca</a>";

  echo '<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">';


  $a=array();
  if( isset(  $_GET['RAW']  ) )    $a += array("RAW"=>'');
  if( isset(  $_GET['DETAIL']  ) ) $a += array("DETAIL"=>'');


  foreach($_SESSION['MAIL'] as $m) if( $m->IN_FILTRO_RICERCA > 0 ) stampamail($m, $a);

  //stampamail($_SESSION['MAIL'][0]);

  echo '</div>';

  echo "</body></html>";

  die();

}





$oggi=date ( "M j H:i:s" );


unset( $_SESSION['MAIL'] );
unset( $_SESSION['Sistema'] );


unset( $_SESSION['NFILE'] );
unset( $_SESSION['numerolineelette'] );
unset( $_SESSION['stra'] );

?>
<div class="container">

<form>


Carica : <br><br>
<a href="mail.php?leggi=tutto">TUTTI I FILES</a><br><br>
<a href="mail.php?leggi=oggi">Solo oggi <?php echo $oggi ?></a><br><br>
<a href="mail.php?leggi=ieri">ieri</a><br><br>
<a href="mail.php?leggi=ierioggi">ieri e oggi</a><br><br>
<a href="">Un particolare file</a><br><br>
    <label for="exampleInputFile">File input</label>
    <input type="file" id="exampleInputFile">


  <button type="submit" class="btn btn-default">Submit</button>
</form>
</div>
</body>
</html>



