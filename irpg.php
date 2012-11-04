#!/usr/bin/php
<?php

/*
 * EpiKnet Idle RPG (EIRPG)
 * Copyright (C) 2005-2012 Francis D (Homer) & EpiKnet
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License version 3 as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */


/**
* Fichier principal du bot IRPG
* Fichier � ex�cuter pour d�marrer le bot
*
* @author Homer
* @created 1er juin 2005
*/


//Librairies utilis�es
include("lib/clsIRPG.php");   //lib sp�cifique
include("lib/clsIRC.php");    //lib sp�cialis�e sur la connexion IRC
include("lib/clsDB.php");     //lib sp�cialis�e sur la base de donn�es mySQL


print "D�marrage d'Idle RPG...\n";

$pid = posix_getpid();


//Initialisation des objets IRPG, IRC et DB
$db = new DB;
$irpg = new IRPG;
$irc = new IRC;

//Chargement et validation du fichier de configuration
if (!$irpg->init())
{
  die ("Erreur lors du chargement du fichier de configuration.\n") ;
}

//Lecture de la liste des ignores...
$irpg->lireIgnores();

while (true)
{
  //Connexion � IRC
  if (!$irc->connexion($irpg->readConfig("IRC","server"), $irpg->readConfig("IRC","port"), $irpg->readConfig("IRC","username"), $irpg->readConfig("IRC","realname"), $irpg->readConfig("IRC","nick"), $irpg->readConfig("IRC", "bind"), $irpg->readConfig("IRC","password"), $irpg->readConfig("IRPG","debug")))
  {
    $irpg->alog("Impossible de se connecter au serveur IRC.  Reconnexion dans 60 secondes...", true);
    sleep(60);
  }
  else {
    break;
  }
}


if ($irpg->readConfig("IRPG", "background") == "1")
{ //On lance le bot en background
  set_time_limit(0);
  if (pcntl_fork())
  {

  }
  else {
    $pid = posix_getpid();
    $irpg->alog("Chargement en background (PID #$pid)...", true);
    posix_setsid();

    while (true)
    {
      start();
    }
  }
}
else {
  start();
}


function start()
{
  global $irpg, $irc, $db;

  // On doit connecter la DB dans le m�me thread que la connexion IRC,
  // car la connexion est perdue avec PHP5 (fonctionne avec PHP4.3)

  //Connexion � la base de donn�es
  if (!$db->connexion($irpg->readConfig("SQL", "host"), $irpg->readConfig("SQL", "login"), $irpg->readConfig("SQL", "password"), $irpg->readConfig("SQL", "base"), $irpg->readConfig("SQL", "prefix")))
  {
	die ("Impossible de se connecter au serveur de bases de donn�es.\n");
  }

  // Un module peut avoir besoin de la connexion � la base de donn�es
  // pour s'initialiser, il faut donc charger les modules seulement
  // apr�s la connexion de celle-ci.

  //Chargement des modules
  $irpg->loadModules();

  while (true)
  {
    if (!$irc->boucle())
    {
      if ($irc->exit) { sleep(1); die("SHUTDOWN du bot demand�.\n"); }
      $irpg->alog("Connexion IRC perdue... reconnexion dans 20 secondes.");
      sleep(20);
      if ($irc->connexion($irpg->readConfig("IRC","server"), $irpg->readConfig("IRC","port"), $irpg->readConfig("IRC","username"), $irpg->readConfig("IRC","realname"), $irpg->readConfig("IRC","nick"), $irpg->readConfig("IRC", "bind"), $irpg->readConfig("IRC","password"), $irpg->readConfig("IRPG","debug")))
      {
         continue;
      }
    }
    else {
      die("D�connexion du bot, impossible d'entrer dans la boucle !\n");
    }
  }
}


?>
