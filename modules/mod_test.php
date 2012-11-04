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
* Module mod_test
* Module exp�rimental IRPG
*
* @author Homer
* @created 19 juin 2005
* @modified 19 juin 2005
*/

class test  /* Le nom de la classe DOIT �tre du m�me nom que le module (sans le mod_) */
{
//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**
  /* Les variables obligatoires du module */
  var $name;        //Nom du module
  var $version;     //Version du module
  var $desc;        //Description du module
  var $depend;      //Modules dont nous sommes d�pendants

  /* Variables suppl�mentaires � la suite, si n�cessaire */

//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**

///////////////////////////////////////////////////////////////
  Function loadModule()
  {
    //Constructeur; initialisateur du module
    //S'�x�cute lors du (re)chargement du bot ou d'un REHASH
    global $irc, $irpg;

    /* Renseignement des variables importantes */
    $this->name = "mod_test";              /* Nom du module, pr�fix� de mod_ */
    $this->version = "0.1.1";              /* Important de mettre la version sous forme x.y.z */
    $this->desc = "Module exp�rimental";
    $this->depend = Array("test2/0.1.1");  /* Syntaxe: nomModule/version (x.y.z) */

    //Recherche de d�pendances
    /* Ne pas modifier ce qui suit; proc�dure de v�rification des d�pendances */
    If (!$irpg->checkDepd($this->depend))
    {
      die("$this->name: d�pendance non r�solue\n");
    }

    //Validation du fichier de configuration sp�cifique au module
    $cfgKeys = Array("testparam");  //Cl�s obligatoires
    $cfgKeysOpt = Array("");        //Cl�s optionelles

    /* Ne pas modifier ce qui suit; lecture et validation du fichier de configuration */
    If (!$irpg->validationConfig($this->name, $cfgKeys, $cfgKeysOpt))
    {
      die ($this->name.": V�rifiez votre fichier de configuration.\n");
    }


    /*
     * Ajoutez votre programmation � �x�cuter lors du
     * chargement du module � partir d'ici
     *
     */


  }

///////////////////////////////////////////////////////////////
  Function unloadModule()
  {
    //Destructeur; d�charge le module
    //S'�x�cute lors du SHUTDOWN du bot ou d'un REHASH
    global $irc, $irpg;


    /* Placer les instructions de d�chargement de module entre ici et la fin*/





  }

///////////////////////////////////////////////////////////////

  Function onConnect() {
    global $irc, $irpg;
    $testparam = $irpg->readConfig("mod_test", "testparam");
    $irc->privmsg("Homer", "Je viens de me connecter !");
    $irc->notice("Homer", "testparam = $testparam");
  }

///////////////////////////////////////////////////////////////

  Function onPrivmsgCanal($nick, $user, $host, $message) {
    global $irc, $irpg;
    $irc->privmsg("Homer", "$nick!$user@$host a dit: $message");
  }

///////////////////////////////////////////////////////////////


  Function onPrivmsgPrive($nick, $user, $host, $message) {
    global $irc, $irpg;
    $irc->sendRaw("PRIVMSG Homer :$nick!$user@$host m'a dit: $message");
  }

///////////////////////////////////////////////////////////////

  Function onNoticeCanal($nick, $user, $host, $message) {
    global $irc, $irpg;
    $irc->sendRaw("PRIVMSG Homer :$nick!$user@$host a dit en notice: $message");
  }

///////////////////////////////////////////////////////////////

  Function onNoticePrive($nick, $user, $host, $message) {
    global $irc, $irpg;
    $irc->sendRaw("PRIVMSG Homer :$nick!$user@$host m'a dit en notice: $message");
  }

///////////////////////////////////////////////////////////////

  Function onJoin($nick, $user, $host, $channel) {
    global $irc, $irpg;
    $irc->sendRaw("PRIVMSG Homer :$nick!$user@$host a joint $channel");
  }

///////////////////////////////////////////////////////////////

  Function onPart($nick, $user, $host, $channel) {
    global $irc, $irpg;
    $irc->sendRaw("PRIVMSG Homer :$nick!$user@$host a quitt� $channel");
  }

///////////////////////////////////////////////////////////////

  Function onNick($nick, $user, $host, $newnick) {
    global $irc, $irpg;
    $irc->sendRaw("PRIVMSG Homer :$nick!$user@$host a chang� de pseudo pour $newnick");
  }

///////////////////////////////////////////////////////////////

  Function onKick($nick, $user, $host, $channel, $nickkicked) {
    global $irc, $irpg;
    $irc->sendRaw("PRIVMSG Homer :$nick!$user@$host a kick� $nickkicked de $channel");
  }

///////////////////////////////////////////////////////////////

  Function onCTCP($nick, $user, $host, $ctcp) {
    global $irc, $irpg;
    $irc->sendRaw("PRIVMSG Homer :$nick!$user@$host m'a fait un CTCP $ctcp");
  }

///////////////////////////////////////////////////////////////

  Function onQuit($nick, $user, $host, $reason) {
    global $irc, $irpg;
    $irc->sendRaw("PRIVMSG Homer :$nick!$user@$host a quitt� IRC pour la raison suivante: $reason");
  }

///////////////////////////////////////////////////////////////


}



?>
