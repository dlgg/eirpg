<?php

/*
EpiKnet Idle RPG (EIRPG)
Copyright (C) 2005-2007 Francis D (Homer) & EpiKnet

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU Affero General Public License
version 3 as published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

/**
* Module mod_parrainage
* G�res la fonctionnalit� de parrainage sur le bot.
*
* @author Homer
* @created 18 avril 2010
*/

/*
 * Modification � apporter � la BD
 * ALTER TABLE  `Utilisateurs` ADD  `idParrain` INT( 5 ) NULL DEFAULT NULL ;
 *
 * et � irpg.conf :
 *
 * [mod_parrainage]
 * actif = "1"
 * lvlBonus = "40"
 * pctBonus = "5"
 *
*/

class parrainage
{
//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**
  var $name;        //Nom du module
  var $version;     //Version du module
  var $desc;        //Description du module
  var $depend;      //Modules dont nous sommes d�pendants

  //Variables suppl�mentaires
  var $actif;     //Si la fonctionalit� de parainage est active.
  var $lvlBonus;   //Le level requis par le joueur invit� avant de donner le bonus au parrain.
  var $pctBonus;   //Le % du TTL retir� au parrain.


//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**

///////////////////////////////////////////////////////////////
  Function loadModule()
  {
    //Constructeur; initialisateur du module
    //S'�x�cute lors du (re)chargement du bot ou d'un REHASH
    global $irc, $irpg, $db;

    /* Renseignement des variables importantes */
    $this->name = "mod_parrainage";
    $this->version = "0.1.0";
    $this->desc = "Module g�rant les fonctionalit�s de parrainage.";
    $this->depend = Array("idle/1.0.0");

    //Recherche de d�pendances
    If (!$irpg->checkDepd($this->depend))
    {
      die("$this->name: d�pendance non r�solue\n");
    }

    //Validation du fichier de configuration sp�cifique au module
    $cfgKeys = Array("actif", "lvlBonus", "pctBonus");
    $cfgKeysOpt = Array();

    If (!$irpg->validationConfig($this->name, $cfgKeys, $cfgKeysOpt))
    {
      die ($this->name.": V�rifiez votre fichier de configuration.\n");
    }

    $this->actif = $irpg->readConfig($this->name,"actif");
    $this->lvlBonus = $irpg->readConfig($this->name, "lvlBonus");
    $this->pctBonus = $irpg->readConfig($this->name, "pctBonus");
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

    //Implantation des commandes de base
    $message = trim(str_replace("\n", "", $message));
    $message = explode(" ", $message);
    $nb = count($message) - 1;

    switch (strtoupper($message[0])) {
      case "REGISTER2":
        //Cr�ation d'un compte sur le bot � l'aide d'un parrain
        If ($nb == 4) { $this->cmdRegister2($nick, $message[1], $message[2], $message[3]); }
        Else { $irc->notice($nick, "Syntaxe incorrecte.  Syntaxe: REGISTER2 <utilisateur> <mot de passe> <courriel> <parrain>."); }
        break;


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

/* Fonctions reli�s aux commandes re�ues par le bot */

  //TODO: il serait pr�f�rable de laisser mod_core cr�er le compte et de g�rer le parrain
  // suite � la r�ception d'un signal qu'un nouveau compte a �t� cr��.
  Function cmdRegister2($nick, $username, $password, $email, $parrain)
  {
    global $irc, $irpg, $db;
    /* cmdREGISTER2 : cr�e un compte dans la base de donn�es */

    // on v�rififie si le module est actif
    if ($this->actif!="1")
    {
      $irc->notice($nick, "D�sol�, la fonctionalit� de parrainage n'est pas en fonction.");
      return false;
    }

    //On v�rifie si l'utilisateur est sur le canal
    If (!$irc->isOn($irc->home, $nick))
    {
      $irc->notice($nick, "D�sol�, vous devez �tre sur \002$irc->home\002 pour vous enregistrer.");
      return false;
    }
    //Validation du nom d'utilisateur
    ElseIf (strlen($username) > 30) {
      $irc->notice($nick, "D�sol�, votre nom d'utilisateur est trop long.  La limite autoris�e est de \00230\002 caract�res.");
      return false;
    }
    ElseIf (!eregi("^[a-z0-9_-]+$", $username)) {
      $irc->notice($nick, "D�sol�, votre nom d'utilisateur contient des caract�res interdits.  Seuls les caract�res \002alphanum�riques\002, le \002tiret\002 et la \002barre de soulignement\002 sont autoris�s.");
      return false;
    }
    ElseIf (((strtoupper($username) == "IRPG")) or ((strtoupper($username) == "EIRPG"))) {
      $irc->notice($nick, "D�sol�, ce nom d'utilisateur est r�serv�.");
      return false;
    }
    Else {
      //On v�rifie que le nom n'existe pas d�j�
      $table = $db->prefix."Utilisateurs";
      $r = $db->req("SELECT Username FROM $table WHERE Username='$username'");
      If (mysql_num_rows($r) != 0)
      {
        $irc->notice($nick, "D�sol�, ce nom d'utilisateur existe d�j�.  Veuillez en choisir un autre.");
        return false;
      }
    }

    //Encryption du mot de passe
    $password = md5($password);

    //Validation de l'adresse de courriel
    If (!$this->validerMail($email))
    {
      $irc->notice($nick, "D�sol�, votre adresse de courriel n'est pas valide.");
      return false;
    }

    // on v�rifie que le parrain existe
    $table = $db->prefix."Personnages";
    $r = $db->req("SELECT Nom FROM $table WHERE Nom='$parrain'");
    If (mysql_num_rows($r) == 0)
    {
      $irc->notice($nick, "Votre parrain n'a pas �t� trouv�.  Vous devez utiliser son nom de personnage IRPG.");
      return false;
    }

    //Requ�te SQL maintenant :)
    $table = $db->prefix."Utilisateurs";
    $db->req("INSERT INTO $table (`Username`, `Password`, `Email`, `Created`, `pidParrain`) VALUES ('$username', '$password', '$email', NOW(), '$parrain')");
    $irc->notice($nick, "Votre compte \002$username\002 a �t� cr�� avec succ�s !");
    $irc->notice($nick, "Vous pouvez � pr�sent vous authentifier � l'aide de la commande \002LOGIN\002 puis ensuite cr�er votre premier personnage � l'aide de la commande \002CREATE\002.");
    $irc->privmsg($irc->home, "Bienvenue � notre nouveau joueur $username invit� par $parrain, connect� sous le pseudo $nick !");

  }


///////////////////////////////////////////////////////////////

  function modIdle_onLvlUp($nick, $uid, $pid, $level, $next)
  {
    $tbUtil = $db->prefix."Utilisateurs";
    $tbPerso = $db->prefix."Personnages";

    if ($level==$this->lvlBonus)
    {
	$pidParrain = $db->getRow("SELECT pidParrain FROM $tbUtil WHERE uid=$uid");
	If (mysql_num_rows($r) != 0)
        {
          $pidParrain = $pidParrain[0]["pidParrain"];
          if ($pidParrain!="")
          {
            // on donne le bonus au parrain
            $query = "SELECT Nom, Level, Next FROM $tbPerso WHERE Id_Personnages=$pidParrain";
            if ($db->nbLignes($query) != 1) return false; //parrain non trouv�
            $leParrain = $db->getRows($query);

            $persoParrain = $leParrain[0]['Nom'];
            $level = $leParrain[0]['Level'];
            $next = $leParrain[0]['Next'];

            $bonus = ($this->pctBonus/100)*$next;
            $bonus = round($bonus, 0):
            $cbonus = $irpg->convSecondes($cbonus);
            $nouveauNext = $next - $bonus;
            $cnouveauNext = $irpg->convSecondes($cnouveauNext);

            $db->req("UPDATE $tbPerso SET Next=Next-$bonus WHERE Id_Personnages=$pidParrain");

            $perso = getNomPersoByPID($pid);
            $irc->privmsg($irc->home, "$persoParrain, le parrain de $perso est r�compens� par le retrait de 5% de son TTL.  Ce bonus l'acc�l�re de $cbonus!  Prochain niveau dans $cnouveauNext.");

          }
        }
    }
  }

/////////////////////////////////////////////////////////////

/* Fonctions diverses */

  Function validerMail($mail)
  {

  return ereg('^[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+'.
               '@'.
               '[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.'.
               '[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+$',
               $mail);

  }


////////////////////////////////////////////////////////////////


}

?>
