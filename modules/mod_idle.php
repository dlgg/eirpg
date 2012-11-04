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
* Module mod_idle
* Calcul de l'idle des joueurs
* Module indispensable au fonctionnement du jeu.
*
* M�thodes inter-modules cr�es dans ce module:
* - modIdle_onLvlUp($nick, $uid, $pid, $level2, $next)
*
* @author Homer
* @created 10 septembre 2005
*/

class idle
{
//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**
  var $name;        //Nom du module
  var $version;     //Version du module
  var $desc;        //Description du module
  var $depend;      //Modules dont nous sommes d�pendants

  //Variables suppl�mentaires
  var $idleBase;    //Niveau de base (lu du fichier de config)
  var $expLvlUp;    //Valeur exponentiel de calcul de niveau (lu du fich. de config)

//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**

///////////////////////////////////////////////////////////////
  function loadModule()
  {
    //Constructeur; initialisateur du module
    //S'�x�cute lors du (re)chargement du bot ou d'un REHASH
    global $irc, $irpg, $db;

    /* Renseignement des variables importantes */
    $this->name = "mod_idle";
    $this->version = "1.0.0";
    $this->desc = "Module calculant l'idle";
    $this->depend = array("core/0.5.0");

    //Recherche de d�pendances
    if (!$irpg->checkDepd($this->depend)) {
      die("$this->name: d�pendance non r�solue\n");
    }

    //Validation du fichier de configuration sp�cifique au module
    $cfgKeys = array("idleBase", "expLvlUp");
    $cfgKeysOpt = array("");

    if (!$irpg->validationConfig($this->name, $cfgKeys, $cfgKeysOpt)) {
      die ($this->name.": V�rifiez votre fichier de configuration.\n");
    }

    //Initialisation des param�tres du fich de configuration
    $this->idleBase = $irpg->readConfig($this->name, "idleBase");
    $this->expLvlUp = $irpg->readConfig($this->name, "expLvlUp");

  }

///////////////////////////////////////////////////////////////
  function unloadModule()
  {
    //Destructeur; d�charge le module
    //S'�x�cute lors du SHUTDOWN du bot ou d'un REHASH
    global $irc, $irpg, $db;

    $irc->deconnexion("SHUTDOWN: mod_idle a �t� d�charg�!");
    $db->deconnexion();


  }

///////////////////////////////////////////////////////////////

  function onConnect()
  {
    global $irc, $irpg, $db;

  }

///////////////////////////////////////////////////////////////

  function onPrivmsgCanal($nick, $user, $host, $message)
  {
    global $irc, $irpg, $db;

  }

///////////////////////////////////////////////////////////////


  function onPrivmsgPrive($nick, $user, $host, $message)
  {
    global $irc, $irpg, $db;

  }

///////////////////////////////////////////////////////////////

  function onNoticeCanal($nick, $user, $host, $message)
  {
    global $irc, $irpg, $db;

  }

///////////////////////////////////////////////////////////////

  function onNoticePrive($nick, $user, $host, $message)
  {
    global $irc, $irpg, $db;

  }

///////////////////////////////////////////////////////////////

  function onJoin($nick, $user, $host, $channel)
  {
    global $irc, $irpg, $db;

  }

///////////////////////////////////////////////////////////////

  function onPart($nick, $user, $host, $channel)
  {
    global $irc, $irpg, $db;


  }

///////////////////////////////////////////////////////////////

  function onNick($nick, $user, $host, $newnick)
  {
    global $irc, $irpg, $db;


  }

///////////////////////////////////////////////////////////////

  function onKick($nick, $user, $host, $channel, $nickkicked)
  {
    global $irc, $irpg, $db;

  }

///////////////////////////////////////////////////////////////

  function onCTCP($nick, $user, $host, $ctcp)
  {
    global $irc, $irpg, $db;

  }

///////////////////////////////////////////////////////////////

  function onQuit($nick, $user, $host, $reason)
  {
    global $irc, $irpg, $db;


  }

///////////////////////////////////////////////////////////////

  function on5Secondes()
  {
    global $irc, $irpg;

  }

///////////////////////////////////////////////////////////////


  function on10Secondes()
  {
    global $irc, $irpg;

  }

///////////////////////////////////////////////////////////////


  function on15Secondes()
  {
    global $irc, $irpg, $db;

    //On retire 15 secondes � tous les
    //personnages en ligne !
    $tbPerso = $db->prefix."Personnages";
    $tbIRC = $db->prefix."IRC";
    $db->req("UPDATE $tbPerso SET Next=Next-15, Idled=Idled+15 WHERE Id_Personnages IN (SELECT Pers_Id FROM $tbIRC WHERE NOT ISNULL(Pers_Id))");

    //Level up
    $i = 0;
    $up = $db->getRows("SELECT Id_Personnages, Util_Id, Nom, Level, Class FROM $tbPerso WHERE Next <= '0'");
    while ($i != count($up)) {
      $pid = $up[$i]["Id_Personnages"];
      $uid = $up[$i]["Util_Id"];
      $nomPerso = $up[$i]["Nom"];
      $level = $up[$i]["Level"];
      $level2 = $level + 1;
      $class = $up[$i]["Class"];

      $nick = $irpg->getNickByUID($uid);

      //Calcul du nombre de seconde � idler pour atteindre
      //le prochain niveau
      $next = round($this->idleBase * pow($this->expLvlUp,$level2), 0);

      $db->req("UPDATE $tbPerso SET Level=Level+1, Next='$next' WHERE Id_Personnages='$pid'");
      $irpg->Log($pid, "LEVEL_UP", "0", $level, $level2);

      $cnext = $irpg->convSecondes($next);

      $irc->notice($nick, "Votre personnage $nomPerso vient d'obtenir le niveau $level2 !  Prochain niveau dans $cnext.");
      $irc->privmsg($irc->home, "UP!  $nomPerso, $class vient d'obtenir le niveau $level2 !  Prochain niveau dans $cnext.");

      $y = 0;
      while ($y != count($irpg->mod)) {
        if (method_exists($irpg->mod[$irpg->modules[$y]], "modIdle_onLvlUp")) {
          $irpg->mod[$irpg->modules[$y]]->modIdle_onLvlUp($nick, $uid, $pid, $level2, $next);
        }
        $y++;
      }

      $i++;

    }

  }

///////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////


}

?>
