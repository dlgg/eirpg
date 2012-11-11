<?php

/*
 * EpiKnet Idle RPG (EIRPG)
 * Copyright (C) 2005-2012 Francis D (Homer), cedricpc & EpiKnet
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

/*
 * Modification � apporter � la BD
 * ALTER TABLE  `Utilisateurs` ADD  `pidParrain` INT( 5 ) NULL DEFAULT NULL;
 *
 * et � irpg.conf :
 *
 * [mod_parrainage]
 * actif = "1"
 * lvlBonus = "40"
 * pctBonus = "5"
 *
 */

/**
 * Module mod_parrainage
 * G�res la fonctionnalit� de parrainage sur le bot.
 *
 * @author Homer
 * @author cedricpc
 * @created 18 avril 2010
 */
class parrainage
{
//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**
    var $name;    //Nom du module
    var $version; //Version du module
    var $desc;    //Description du module
    var $depend;  //Modules dont nous sommes d�pendants

    //Variables suppl�mentaires
    var $actif;    //Si la fonctionalit� de parainage est active.
    var $lvlBonus; //Le level requis par le joueur invit� avant de donner le bonus au parrain.
    var $pctBonus; //Le % du TTL retir� au parrain.
//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**

///////////////////////////////////////////////////////////////

    function loadModule()
    {
        //Constructeur; initialisateur du module
        //S'�x�cute lors du (re)chargement du bot ou d'un REHASH
        global $irc, $irpg, $db;

        /* Renseignement des variables importantes */
        $this->name    = "mod_parrainage";
        $this->version = "0.1.0";
        $this->desc    = "Module g�rant les fonctionalit�s de parrainage.";
        $this->depend  = array("idle/1.0.0");

        //Recherche de d�pendances
        if (!$irpg->checkDepd($this->depend)) {
            die("$this->name: d�pendance non r�solue\n");
        }

        //Validation du fichier de configuration sp�cifique au module
        $cfgKeys    = array("actif", "lvlBonus", "pctBonus");
        $cfgKeysOpt = array();

        if (!$irpg->validationConfig($this->name, $cfgKeys, $cfgKeysOpt)) {
            die ($this->name . ": V�rifiez votre fichier de configuration.\n");
        }

        $this->actif    = $irpg->readConfig($this->name, "actif");
        $this->lvlBonus = $irpg->readConfig($this->name, "lvlBonus");
        $this->pctBonus = $irpg->readConfig($this->name, "pctBonus");
    }

///////////////////////////////////////////////////////////////

    function unloadModule()
    {
        //Destructeur; d�charge le module
        //S'�x�cute lors du SHUTDOWN du bot ou d'un REHASH
        global $irc, $irpg, $db;
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

        //Implantation des commandes de base
        $message = trim(str_replace("\n", "", $message));
        $message = explode(" ", $message);
        $nb = count($message) - 1;

        switch (strtoupper($message[0])) {
        case "REGISTER2":
            //Cr�ation d'un compte sur le bot � l'aide d'un parrain
            if ($nb == 4) {
                $this->cmdRegister2($nick, $message[1], $message[2], $message[3], $message[4]);
            } else {
                $irc->notice($nick, "Syntaxe incorrecte. Syntaxe : "
                    . "REGISTER2 <utilisateur> <mot de passe> <courriel> <parrain>");
            }
            break;
        }
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
    }

///////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////

/* Fonctions reli�s aux commandes re�ues par le bot */

    //TODO: il serait pr�f�rable de laisser mod_core cr�er le compte et de g�rer le parrain
    // suite � la r�ception d'un signal qu'un nouveau compte a �t� cr��.
    function cmdRegister2($nick, $username, $password, $email, $parrain)
    {
        global $irc, $irpg, $db;
        /* cmdREGISTER2 : cr�e un compte dans la base de donn�es */

        // on v�rififie si le module est actif
        if ($this->actif != "1") {
            $irc->notice($nick, "D�sol�, la fonctionalit� de parrainage n'est pas en fonction.");
            return false;
        }

        //On v�rifie si l'utilisateur est sur le canal
        if (!$irc->isOn($irc->home, $nick)) {
            $irc->notice($nick, "D�sol�, vous devez �tre sur \002$irc->home\002 pour vous enregistrer.");
            return false;
        } elseif (strlen($username) > 30) { //Validation du nom d'utilisateur
            $irc->notice($nick, "D�sol�, votre nom d'utilisateur est trop long. "
                . "La limite autoris�e est de \00230\002 caract�res.");
            return false;
        } elseif (!preg_match('/[a-z0-9_-]+$/i', $username)) {
            $irc->notice($nick, "D�sol�, votre nom d'utilisateur contient des caract�res interdits. "
                . "Seuls les caract�res \002alphanum�riques\002, le \002tiret\002 et la \002barre de "
                . "soulignement\002 sont autoris�s.");
            return false;
        } elseif ((strtoupper($username) == "IRPG") || (strtoupper($username) == "EIRPG")) {
            $irc->notice($nick, "D�sol�, ce nom d'utilisateur est r�serv�.");
            return false;
        } else {
            //On v�rifie que le nom n'existe pas d�j�
            $table = $db->prefix . "Utilisateurs";
            $r = $db->req("SELECT Username FROM $table WHERE Username='$username'");
            if (mysql_num_rows($r) != 0) {
                $irc->notice($nick, "D�sol�, ce nom d'utilisateur existe d�j�. Veuillez en choisir un autre.");
                return false;
            }
        }

        //Encryption du mot de passe
        $password = md5($password);

        //Validation de l'adresse de courriel
        if (!$irpg->mod['core']->validerMail($email)) {
            $irc->notice($nick, "D�sol�, votre adresse de courriel n'est pas valide.");
            return false;
        }

        // on v�rifie que le parrain existe
        if (!$pid = $irpg->getPIDByPerso($parrain)) {
            $irc->notice($nick, "Votre parrain n'a pas �t� trouv�. Vous devez utiliser son nom de personnage IRPG.");
            return false;
        }

        //Requ�te SQL maintenant :)
        $table = $db->prefix . "Utilisateurs";
        $db->req("INSERT INTO $table (`Username`, `Password`, `Email`, `Created`, `pidParrain`)
                  VALUES ('$username', '$password', '$email', NOW(), '$pid')");
        $irc->notice($nick, "Votre compte \002$username\002 a �t� cr�� avec succ�s !");
        $irc->notice($nick, "Vous pouvez � pr�sent vous authentifier � l'aide de la commande \002LOGIN\002 "
            . "puis ensuite cr�er votre premier personnage � l'aide de la commande \002CREATE\002.");
        $irc->privmsg($irc->home, "Bienvenue � notre nouveau joueur $username invit� par $parrain, connect� "
            . "sous le pseudo $nick !");
    }

///////////////////////////////////////////////////////////////

    function modIdle_onLvlUp($nick, $uid, $pid, $level, $next) {
        global $irc, $irpg, $db;

        $tbPerso = '`' . $db->prefix . 'Personnages`';

        if (($level == $this->lvlBonus) && ($ppid = $this->getParrainPIDByUID($uid))) {
            // on donne le bonus au parrain
            if (!$parrain = $this->getPersoByParrainPID($ppid)) {
                //parrain non trouv�
                return false;
            }

            $pPerso = $parrain['Nom'];
            $pLevel = $parrain['Level'];
            $pNext  = $parrain['Next'];

            $bonus  = round(($this->pctBonus / 100) * $pNext, 0);
            $ttl    = $pNext - $bonus;

            $db->req('UPDATE ' . $tbPerso . ' SET `Next` = `Next` - ' . $bonus . ' WHERE `Id_Personnages` = ' . $ppid);

            $perso = $irpg->getNomPersoByPID($pid);
            $irc->privmsg($irc->home, $pPerso . ', le parrain de ' . $perso . ' est r�compens� par le retrait de 5% '
                . 'de son TTL. Ce bonus l\'acc�l�re de ' . $irpg->convSecondes($bonus) . ' ! Prochain niveau dans '
                . $irpg->convSecondes($ttl) . '.');
        }
    }

/////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////

/* Fonctions diverses */

    function getParrainPIDByUID($uid) {
        global $db;

        $req = $db->getRows('SELECT `pidParrain` FROM `' . $db->prefix . 'Utilisateurs` WHERE `Id_Utilisateurs` = '
             . intval($uid));
        return (count($req) > 0 ? $req[0]['pidParrain'] : false);
    }

////////////////////////////////////////////////////////////////

    function getPersoByParrainPID($ppid) {
        global $db;

        $req = $db->getRows('SELECT * FROM `' . $db->prefix . 'Personnages` WHERE `ID_Personnages` = '
             . intval($ppid));
        return (count($req) > 0 ? $req[0] : false);
    }

////////////////////////////////////////////////////////////////
}
?>
