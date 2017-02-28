#!/bin/sh


file=$1

# numero linee esaminate

nl=`cat ${file} |  wc -l`
echo "Numero linee totali esaminate                   : $nl"
echo ""

## test di SPAM o attacco
#numero UGFzc3dvcmQ6
ns=`cat ${file}  | egrep "UGFzc3dvcmQ6" | wc -l`




echo "Numero attaicchi UGFzc3dvcmQ6                   : $ns" 


# rapporto attacchi generici da specifici
#cat /var/log/mail.log | egrep 'UGFzc3dvcmQ6' |   awk  '{print $7}'| awk -F '[' '{print $2}' | sed -e 's/]://' | sort -u | wc -l

b=`cat ${file} | egrep 'UGFzc3dvcmQ6' |   awk  '{print $7}'| awk -F '[' '{print $2}' | sed -e 's/]://' | sort -u | wc -l`


echo "Numero host che attaccano UGFzc3dvcmQ6          : $b"
echo "Primi 3 host che attaccano e numero di attacchi"
h=`cat ${file} | egrep 'UGFzc3dvcmQ6' |   awk  '{print $7}'| awk -F '[' '{print $2}' | sed -e 's/]://' | sort |  ./mail_util.php`
echo $h

echo ""

#Numero autenticazioni
na=`cat ${file} | egrep 'sasl_username' | wc -l`

echo "Numero autenticazioni                           : $na"


#numero di host che si autentificano
nha=`cat ${file} | egrep 'sasl_username' | awk '{print $7}' | awk -F '[' '{print $2}' | sed -e 's/],//'  | sort -u | wc -l`

echo "Numero host che si autentificano                : $nha"

echo ""
#numero mail transitate
nmt=`cat ${file} | egrep 'status=sent' | wc -l`
echo "Numero mail consegnate                          : $nmt"

#numero di mail arrivate 
nma=`cat ${file} | egrep ' status=sent' | grep "relay=dovecot" | wc -l`
echo "Numero mail arrivate                            : $nma"


#numero di mail inviate fuori
nmi=`cat ${file}  | egrep ' status=sent' | grep -v "relay=dovecot" | wc -l`

echo "Numero mail inviate                             : $nmi"



#numero mail inviate e ricevute sul server stesso
ir=`expr $na - $nmi`
echo "Numero mail inviate internamente al server      : $ir"





