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
* Module mod_test
* Module exp�rimental IRPG
*
* @author Homer
* @created 19 juin 2005
* @modified 19 juin 2005
*/

class test2
{
//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**
  var $name;        //Nom du module
  var $version;     //Version du module
  var $desc;        //Description du module
  var $depend;      //Modules dont nous sommes d�pendants
//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**

///////////////////////////////////////////////////////////////
  function loadModule()
  {
    //Constructeur; initialisateur du module
    //S'�x�cute lors du (re)chargement du bot ou d'un REHASH
    global $irc, $irpg;

    $this->name = "mod_test2";
    $this->version = "0.1.1";
    $this->desc = "Module exp�rimental 2";
    $this->depend = array("");

    //Recherche de d�pendances
    if (!$irpg->checkDepd($this->depend))
    {
      die("$this->name: d�pendance non r�solue\n");
    }

    //Validation du fichier de configuration sp�cifique au module
    $cfgKeys = array("");  //Cl�s obligatoires
    $cfgKeysOpt = array("");        //Cl�s optionelles
    if (!$irpg->validationConfig($this->name, $cfgKeys, $cfgKeysOpt))
    {
      die ($this->name.": V�rifiez votre fichier de configuration.\n");
    }


  }

///////////////////////////////////////////////////////////////
  function unloadModule()
  {
    //Destructeur; d�charge le module
    //S'�x�cute lors du SHUTDOWN du bot ou d'un REHASH
    global $irc, $irpg;



    /* Placer les instructions de d�chargement de module entre ici et la fin */




  }

///////////////////////////////////////////////////////////////

  function onConnect() {
    global $irc, $irpg;

  }

///////////////////////////////////////////////////////////////

  function onPrivmsgCanal($nick, $user, $host, $message) {
    global $irc, $irpg;

  }

///////////////////////////////////////////////////////////////


  function onPrivmsgPrive($nick, $user, $host, $message) {
    global $irc, $irpg;
    /* test */
    //Ajout de la commande UNLOADMODULE
    $message = trim(str_replace("\n", "", $message));
    $message = explode(" ", $message);

    if ($message[0] == "UNLOADMODULE")
    {
      if ($irpg->unloadModule($message[1]))
      {
        $irc->notice($nick, "Le module a �t� d�charg�");
      }
      else {
        $irc->notice($nick, "/!\ Le module ne peut �tre d�charg�");
      }
    }
    elseif ($message[0] == "LOADMODULE")
    {
      if ($irpg->loadModule($message[1]))
      {
        $irc->notice($nick, "Le module a �t� charg� avec succ�s");
      }
      else {
        $irc->notice($nick, "/!\ Erreur lors du chargement du module");
      }

    }
    elseif ($message[0] == "MODULES") {
       print_r($irpg->mod);
       print_r($irpg->modules);

    }
    else {
      $irc->notice($nick, "Commande invalide");
    }


  }

///////////////////////////////////////////////////////////////

  function onNoticeCanal($nick, $user, $host, $message) {
    global $irc, $irpg;

  }

///////////////////////////////////////////////////////////////

  function onNoticePrive($nick, $user, $host, $message) {
    global $irc, $irpg;

  }

///////////////////////////////////////////////////////////////

  function onJoin($nick, $user, $host, $channel) {
    global $irc, $irpg;

  }

///////////////////////////////////////////////////////////////

  function onPart($nick, $user, $host, $channel) {
    global $irc, $irpg;

  }

///////////////////////////////////////////////////////////////

  function onNick($nick, $user, $host, $newnick) {
    global $irc, $irpg;

  }

///////////////////////////////////////////////////////////////

  function onKick($nick, $user, $host, $channel, $nickkicked) {
    global $irc, $irpg;

  }

///////////////////////////////////////////////////////////////

  function onCTCP($nick, $user, $host, $ctcp) {
    global $irc, $irpg;

  }

///////////////////////////////////////////////////////////////

  function onQuit($nick, $user, $host, $reason) {
    global $irc, $irpg;

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
    global $irc, $irpg;

  }

///////////////////////////////////////////////////////////////


}



?>
