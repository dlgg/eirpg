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
 * along with this program. if not, see <http://www.gnu.org/licenses/>.
 */


/**
* Module mod_notice
* Envoi d'une notice on:join
*
* @author Homer
* @created 18 mars 2006
*/

class notice
{
//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**
  var $name;        //Nom du module
  var $version;     //Version du module
  var $desc;        //Description du module
  var $depend;      //Modules dont nous sommes d�pendants

  //Variables suppl�mentaires
  var $message;
  var $actif;

//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**

///////////////////////////////////////////////////////////////
  function loadModule()
  {
    //Constructeur; initialisateur du module
    //S'�x�cute lors du (re)chargement du bot ou d'un REHASH
    global $irc, $irpg, $db;

    /* Renseignement des variables importantes */
    $this->name = "mod_notice";
    $this->version = "1.0.0";
    $this->desc = "Notice on:join";
    $this->depend = array("core/0.5.0");

    //Recherche de d�pendances
    if (!$irpg->checkDepd($this->depend))
    {
      die("$this->name: d�pendance non r�solue\n");
    }

    //Validation du fichier de configuration sp�cifique au module
    $cfgKeys = array("message", "actif");
    $cfgKeysOpt = array();

    if (!$irpg->validationConfig($this->name, $cfgKeys, $cfgKeysOpt))
    {
      die ($this->name.": V�rifiez votre fichier de configuration.\n");
    }

    //Initialisation des param�tres du fich de configuration
    $this->message = $irpg->readConfig($this->name, "message");
    $this->actif = $irpg->readConfig($this->name, "actif");


  }

///////////////////////////////////////////////////////////////
  function unloadModule()
  {
    //Destructeur; d�charge le module
    //S'�x�cute lors du SHUTDOWN du bot ou d'un REHASH
    global $irc, $irpg, $db;


  }

///////////////////////////////////////////////////////////////

  function onConnect() {
    global $irc, $irpg, $db;

  }

///////////////////////////////////////////////////////////////

  function onPrivmsgCanal($nick, $user, $host, $message) {
    global $irc, $irpg, $db;

  }

///////////////////////////////////////////////////////////////


  function onPrivmsgPrive($nick, $user, $host, $message) {
    global $irc, $irpg, $db;


  }

///////////////////////////////////////////////////////////////

  function onNoticeCanal($nick, $user, $host, $message) {
    global $irc, $irpg, $db;

  }

///////////////////////////////////////////////////////////////

  function onNoticePrive($nick, $user, $host, $message) {
    global $irc, $irpg, $db;

  }

///////////////////////////////////////////////////////////////

  function onJoin($nick, $user, $host, $channel) {
    global $irc, $irpg, $db;

    if (($nick != $irc->me) and ($this->actif=="1"))
    {
    	$irc->notice($nick, $this->message);
    }
  }

///////////////////////////////////////////////////////////////

  function onPart($nick, $user, $host, $channel) {
    global $irc, $irpg, $db;


  }

///////////////////////////////////////////////////////////////

  function onNick($nick, $user, $host, $newnick) {
    global $irc, $irpg, $db;


  }

///////////////////////////////////////////////////////////////

  function onKick($nick, $user, $host, $channel, $nickkicked) {
    global $irc, $irpg, $db;

  }

///////////////////////////////////////////////////////////////

  function onCTCP($nick, $user, $host, $ctcp) {
    global $irc, $irpg, $db;

  }

///////////////////////////////////////////////////////////////

  function onQuit($nick, $user, $host, $reason) {
    global $irc, $irpg, $db;

  }

///////////////////////////////////////////////////////////////

  function on5Secondes() {
    global $irc, $irpg;


  }

///////////////////////////////////////////////////////////////


  function on10Secondes() {
    global $irc, $irpg;

  }

///////////////////////////////////////////////////////////////


  function on15Secondes() {
    global $irc, $irpg, $db;



  }



///////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////



}
?>
