Analisi log email
vers. 0.1


Sistema di analisi dei log delle mail.

Il sistema analizza un file di log di Postfix. Adattabile sicuramente anche ad altri sistemi di mail.


Il software è scritto in PHP, è in versione 0.1 è mooooolto rudimentale, dato che utilizza non solo PHP,
ma invoca anche script sh, che a loro volta chiamano una funziona in php...
Sono cosciente che non è ottimizzato, ma per adesso non ho il tempo materiale per
migliorarlo e renderlo più pulito ed elegante.

Il meccanismo di base è il seguente:
-	Prima pagina, chiedo se leggere il file di log attuale /var/log/mail.log
	o tutti i file di log presenti nella /var/log, cioè /var/log/mail.log.*.gz
-	Legge quello che richiesto, e "per ogni linea" del file di log cerca di fare le seguenti operazioni
	- Interpreta la linea, per mezzo di espressioni regolali, in modo da capire con quale linea ha a che fare;
	i tipi di linea sono riferiti a messaggi di connessione al sistema da parte di un client o di un server,
									relativi ad una particolare mail,
									relativi a messaggi di sistema.

   - Una volta capito il tipo della linea, se e’:
		Una connessione; interpreta di dati di connessione e se viene aperta il programma crea una connessione nuova,
			se la linea si riferisce ad una connessione esistente lo associa a quella connessione.
		Una mail; con ID univoco. Se non esiste si crea la mail, altrimenti la linea si associa ad una mail precedentemente creata. Se esiste di associa ad una connessione creata in precedenza.
		Messaggio di sistema, si memorizza in sequenza.
	- Alla fine di questo passaggio abbiamo
		un array di connessioni con i dati significativi evidenziati,
		un array di mail, con le relative linee e i valori significativi, e un riferimento alla connessione,
		un insieme di messaggi di sistema.
	- Queste informazioni sono memorizzate (IN RAM.... lato server), e viene mostrata una schermata, che permette la ricerca di una serie
		di parametri all’interno dei dati prima individuati. E’ possibile ricercare per
		- Un testo specifico dentro ai campi : ID mail, From, To, Subject o ovunque
		- Avere un filtro che permette di evidenziare le singole mail catalogate sul singolo stato per :
		•	Mail con sola connessione di apertura (SPAM!)
		•	Mail con un ID ma non chiuse
		•	Solo Mail chiuse correttamente
		•	Mail NON chiuse correttamente
		•	Mail con disconnessione
		•	Mail aperte senza disconnessione
		•	Solo Mail inviate
		•	Solo Mail ricevute
	- E’ inoltre possibile vedere, per ogni mail, oltre che un riassunto, anche tutte le relative linee, e l’interno di come interpreta il php  il dato
	- E’ possibile anche vedere il file originale di log, con evidenziate le informazioni significative, e le mail a cui appartengono le linee

  	- in questa versione la ricerca avviene solo per le mail, non per le connessini o i messaggi di sistema

	Installazione
		Copia i 4 file in una directory
		hai bisogno di bootstrap e jquery
		Configura correttamente i path di mail.php
		Attenzione che il sistema richiede molta RAM lato server.. e' una delle tante ottimizzazioni da fare,
		chiama mail.php


Sono graditi solo suggerimenti intelligenti.
Se sono richieste spiegazioni o modifiche provate a conttatarmi.. se mi sarà possibile vi aiutero' volentieri..

