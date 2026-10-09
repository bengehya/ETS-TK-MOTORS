<?php

/*
| L'adresse du tunnel HTTPS n'est pas écrite ici.
| TRUSTED_PROXIES indique quels proxys peuvent transmettre l'hôte et le schéma
| de la requête (X-Forwarded-Host, X-Forwarded-Proto). En local, la valeur
| par défaut accepte le proxy courant pour qu'un tunnel puisse changer d'URL
| sans modification du code. En production, indiquer l'adresse réelle du proxy,
| ou laisser la variable vide si l'application est jointe directement.
*/

$configured = env('TRUSTED_PROXIES');

if ($configured === null || $configured === '') {
    $configured = env('APP_ENV', 'production') === 'local' ? '*' : null;
}

return [
    'proxies' => $configured,
];
