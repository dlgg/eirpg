<?php

/*
EpiKnet Idle RPG (EIRPG)
Copyright (C) 2005-2012 Francis D (Homer) & EpiKnet

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU Affero General Public License,
version 3 as published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

/**
* Module mod_hog
* Gestion de la main de Dieu
*
* @author Homer
* @created 11 mars 2006
*/ 

class hog 
{
//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**
  var $name;        //Nom du module
  var $version;     //Version du module
  var $desc;        //Description du module
  var $depend;      //Modules dont nous sommes d�pendants
  
  //Variables suppl�mentaires

  
//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**
  
///////////////////////////////////////////////////////////////
  Function loadModule()
  {
    //Constructeur; initialisateur du module
    //S'�x�cute lors du (re)chargement du bot ou d'un REHASH
    global $irc, $irpg, $db;
    
    /* Renseignement des variables importantes */
    $this->name = "mod_hog";              
    $this->version = "1.0.0";              
    $this->desc = "Main de Dieu";
    $this->depend = Array("core/0.5.0");  
    
    //Recherche de d�pendances
    If (!$irpg->checkDepd($this->depend))
    {
      die("$this->name: d�pendance non r�solue\n");
    }
    
    //Validation du fichier de configuration sp�cifique au module
    $cfgKeys = Array();  
    $cfgKeysOpt = Array();        
    
    If (!$irpg->validationConfig($this->name, $cfgKeys, $cfgKeysOpt))
    {
      die ($this->name.": V�rifiez votre fichier de configuration.\n");
    }
    
    //Initialisation des param�tres du fich de configuration


      
  }
  
///////////////////////////////////////////////////////////////
  Function unloadModule()
  {
    //Destructeur; d�charge le module
    //S'�x�cute lors du SHUTDOWN du bot ou d'un REHASH
    global $irc, $irpg, $db;
      
      
  }
  
///////////////////////////////////////////////////////////////

  Function onConnect() {
    global $irc, $irpg, $db;
    
  }
  
///////////////////////////////////////////////////////////////

  Function onPrivmsgCanal($nick, $user, $host, $message) {
    global $irc, $irpg, $db;

  }
  
///////////////////////////////////////////////////////////////


  Function onPrivmsgPrive($nick, $user, $host, $message) {
    global $irc, $irpg, $db;


    $message = trim(str_replace("\n", "", $message));
    $message = explode(" ", $message);
    $nb = count($message) - 1;


    switch (strtoupper($message[0])) {
      case "HOG":
        //Invoque la main de Dieu (ADMIN)
        $uid = $irpg->getUsernameByNick($nick, true);
        if ($irpg->getAdminLvl($uid[1]) >= 10) {
          $this->cmdHog($nick);
        }
        else {
          $irc->notice($nick, "D�sol�, vous n'avez pas acc�s � la commande HOG.") ;
        }
        break;
    }

  }
  
///////////////////////////////////////////////////////////////
  
  Function onNoticeCanal($nick, $user, $host, $message) {
    global $irc, $irpg, $db;

  }
  
///////////////////////////////////////////////////////////////
  
  Function onNoticePrive($nick, $user, $host, $message) {
    global $irc, $irpg, $db;

  }
  
///////////////////////////////////////////////////////////////
  
  Function onJoin($nick, $user, $host, $channel) {
    global $irc, $irpg, $db;
    
  }
  
///////////////////////////////////////////////////////////////
  
  Function onPart($nick, $user, $host, $channel) {
    global $irc, $irpg, $db;

   
  }
  
///////////////////////////////////////////////////////////////
  
  Function onNick($nick, $user, $host, $newnick) {
    global $irc, $irpg, $db;


  }
  
///////////////////////////////////////////////////////////////
  
  Function onKick($nick, $user, $host, $channel, $nickkicked) {
    global $irc, $irpg, $db;

  }
    
///////////////////////////////////////////////////////////////

  Function onCTCP($nick, $user, $host, $ctcp) {
    global $irc, $irpg, $db;
   
  }
  
///////////////////////////////////////////////////////////////
  
  Function onQuit($nick, $user, $host, $reason) {
    global $irc, $irpg, $db;

  }
  
/////////////////////////////////////////////////////////////// 
  
  Function on5Secondes() {
    global $irc, $irpg;

    if ($irc->ready) {
      //il y a une chance sur 3000 d'invoquer la main de dieu..
      if (rand(1, 3000) == 1) $this->cmdHog();
    }
  }

/////////////////////////////////////////////////////////////// 

  
  Function on10Secondes() {
    global $irc, $irpg;
    
  }

/////////////////////////////////////////////////////////////// 

  
  Function on15Secondes() {
    global $irc, $irpg, $db;
    
   
    
  }
  
 

///////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////

  Function cmdHog($nick = "") {
    global $irpg, $irc, $db;
    $tbPerso = $db->prefix . "Personnages";
    $tbIRC = $db->prefix . "IRC";

    //on s�lectionne d'abord un personnage en ligne
    $query = "SELECT Id_Personnages, Nom, Level, Next FROM $tbPerso WHERE Id_Personnages IN (SELECT Pers_Id FROM $tbIRC WHERE NOT ISNULL(Pers_Id)) ORDER BY RAND() LIMIT 0,1";
    if ($db->nbLignes($query) != 1) return false;
    $res = $db->getRows($query);
    
    $pid = $res[0]['Id_Personnages'];
    $perso = $res[0]['Nom'];
    $level = $res[0]['Level'];
    $level2 = $level + 1;
    $next = $res[0]['Next'];
    
    //La hog peut modifier le TTL entre 5 et 75%
    $time = rand(5, 75);
    
    if (!empty($nick)) $irc->privmsg($irc->home, "$nick a invoqu� la main de Dieu...");
    
    //Il y a 80% de chance que la hog soit positive
    //et 20% qu'elle soit n�gative pour le personnage..
    if (rand(1, 5) <= 4) {
      //hog positive
      $time = round($next * ($time/100), 0);
      $ctime = $irpg->convSecondes($time);
      $next = $next - $time;
      $cnext = $irpg->convSecondes($next);
      $db->req("UPDATE $tbPerso SET Next=$next WHERE Id_Personnages='$pid'");
      $irc->privmsg($irc->home, "Dieu s'est lev� du bon pied ce matin et d�cide d'aider $perso en lui enlevant $ctime avant d'arriver au niveau $level2.  Prochain niveau dans $cnext.");
    }
    else {
      //hog n�gative
      $time = round($next * ($time/100), 0);
      $ctime = $irpg->convSecondes($time);
      $next = $next + $time;
      $cnext = $irpg->convSecondes($next);
      $db->req("UPDATE $tbPerso SET Next=$next WHERE Id_Personnages='$pid'");
      $irc->privmsg($irc->home, "Dieu en a marre de ne plus vous voir � l'�glise et se venge sur $perso en lui ajoutant $ctime avant d'arriver au niveau $level2.  Prochain niveau dans $cnext.");
    }
    
    
    
  }
  
}
?>
