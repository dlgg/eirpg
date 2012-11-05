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
 * Module mod_core
 * Coeur du robot IRPG et module indispensable
 * � son fonctionnement.
 *
 * * M�thodes inter-modules cr�es dans ce module:
 * - modCore_onLogin($nick, $uid, $pid, $level, $next)
 *
 * @author Homer
 * @created 23 juin 2005
 */
class core
{
//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**
    var $name;    //Nom du module
    var $version; //Version du module
    var $desc;    //Description du module
    var $depend;  //Modules dont nous sommes d�pendants

    //Variables suppl�mentaires
    var $users;        //Utilisateurs authentifi�s associ�s � leurs nicks
    var $autologged;   //Utilisateurs auto-loggu�s lors du d�marrage du bot
    var $expPenalite;  //Valeur exponentiel pour calculer les p�nalit�s
    var $penLogout;    //Valeur pour la p�nalit� de la commande LOGOUT
    var $motd;         //MOTD affich� lors d'un LOGIN
    var $timerPing;    //Timer envoi du ping
    var $loginAllowed; //Permet de bloquer la commande LOGIN
//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**

///////////////////////////////////////////////////////////////

    function loadModule()
    {
        //Constructeur; initialisateur du module
        //S'�x�cute lors du (re)chargement du bot ou d'un REHASH
        global $irc, $irpg, $db;

        /* Renseignement des variables importantes */
        $this->name    = "mod_core";
        $this->version = "0.5.0";
        $this->desc    = "Module de base EIRPG";
        $this->depend  = array();

        //Recherche de d�pendances
        if (!$irpg->checkDepd($this->depend)) {
            die("$this->name: d�pendance non r�solue\n");
        }

        //Validation du fichier de configuration sp�cifique au module
        $cfgKeys    = array("maxPerso", "penLogout", "expPenalite");
        $cfgKeysOpt = array("motd");

        if (!$irpg->validationConfig($this->name, $cfgKeys, $cfgKeysOpt)) {
            die($this->name . ": V�rifiez votre fichier de configuration.\n");
        }

        //Initialisation de l'array $this->users et $this->autologged
        $this->users      = array(); //TODO: Si rehash, ne doit pas r�initialiser
        $this->autologged = array();

        $this->maxPerso    = $irpg->readConfig($this->name, "maxPerso");
        $this->penLogout   = $irpg->readConfig($this->name, "penLogout");
        $this->expPenalite = $irpg->readConfig($this->name, "expPenalite");
        $this->motd        = $irpg->readConfig($this->name, "motd");
        $this->timerPing = 0;
        $this->loginAllowed = false;
    }

///////////////////////////////////////////////////////////////

    function unloadModule()
    {
        //Destructeur; d�charge le module
        //S'�x�cute lors du SHUTDOWN du bot ou d'un REHASH
        global $irc, $irpg, $db;

        $irc->deconnexion("SHUTDOWN: mod_core a �t� d�charg�!");
        $db->deconnexion();
    }

///////////////////////////////////////////////////////////////

    function onConnect()
    {
        global $irc, $irpg, $db;

        //Effacement de la table IRC lors de la connexion
        $table   = $db->prefix . "IRC";
        $channel = $irpg->readConfig("IRC", "channel");
        $db->req("DELETE FROM $table WHERE ISNULL(Pers_Id)");
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
        case "HELP":
        case "AIDE":
            //Aide sommaire sur les commandes
            if ($nb != 0) {
                $this->cmdHelp($nick, $message[1]);
            } else {
                $this->cmdHelp($nick);
            }
            break;
        case "LOGIN":
            //Authentification au bot
            if ($nb == 2) {
                $this->cmdLogin($nick, $user, $host, $message[1], $message[2]);
            } elseif ($nb == 3) {
                $this->cmdLogin($nick, $user, $host, $message[1], $message[2], $message[3]);
            } else {
                $irc->notice($nick, "Syntaxe incorrecte. Syntaxe : LOGIN <utilisateur> <mot de passe> [personnage]");
                $irc->notice($nick, "Le param�tre personnage est optionnel. Ce param�tre permet de s'authentifier "
                    . "sur un personnage pr�cis. Si ce param�tre n'est pas indiqu�, vous serez authentifi� sur tous "
                    . "vos personnages cr��s sous votre compte.");
            }
            break;
        case "LOGOUT":
            //D�sauthentification au bot
            if ($nb == 0) {
                $this->cmdLogout($nick);
            } elseif ($nb == 2) {
                $this->cmdLogout($nick, $user, $host, $message[1], $message[2]);
            } else {
                $irc->notice($nick, "Syntaxe incorrecte. Syntaxe : LOGOUT [utilisateur] [mot de passe]");
            }
            break;
        case "REGISTER":
            //Cr�ation d'un compte sur le bot
            if ($nb == 3) {
                $this->cmdRegister($nick, $message[1], $message[2], $message[3]);
            } else {
                $irc->notice($nick, "Syntaxe incorrecte. Syntaxe : REGISTER <utilisateur> <mot de passe> <courriel>");
            }
            break;
        case "CREATE":
            //Cr�ation d'un personnage sur le bot
            if ($nb >= 2) {
                $i = 2;
                while ($i != count($message)) {
                    $classe = "$classe $message[$i]";
                    $i++;
                }
                $this->cmdCreate($nick, $user, $host, $message[1], trim($classe));
            } else {
                $irc->notice($nick, "Syntaxe incorrecte. Syntaxe : CREATE <nom personnage> <classe>");
            }
            break;
        case "NOTICE":
            //Pr�f�rence d'envoi en PRIVMSG ou NOTICE
            if ($nb == 1) {
                $this->cmdNotice($nick, $message[1]);
            } else {
                $irc->notice($nick, "Syntaxe incorrecte. Syntaxe : NOTICE <on/off>");
            }
            break;
        case "SENDPASS":
            //Envoi d'un mot de pass perdu par courriel
            if ($nb == 2) {
                $this->cmdSendPass($nick, $message[1], $message[2]);
            } else {
                $irc->notice($nick, "Syntaxe incorrecte. Syntaxe : SENDPASS <utilisateur> <courriel>");
            }
            break;
        case "WHOAMI":
            //Information sur le compte/personnages
            if ($nb == 0) {
                $this->cmdWhoAmI($nick);
            } else {
                $irc->notice($nick, "Syntaxe incorrecte. Syntaxe : WHOAMI");
            }
            break;
        case "INFOUSER":
            if ($nb < 1) {
                $irc->notice($nick, "Syntaxe : INFOUSER <user>");
            }
            break;

        case "INFOPERSO":
            if ($nb < 1) {
                $irc->notice($nick, "Syntaxe : INFOPERSO <perso>");
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

        //Ajout de l'utilisateur dans la table IRC
        $table = $db->prefix . "IRC";
        if ($nick != $irc->me) {
            $channel = strtoupper($channel);
            $db->req("INSERT INTO $table (`Nick`, `Channel`, `UserHost`) VALUES ('$nick', '$channel', '$user@$host')");
        }
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

        if ($nick == $irc->me) {
            $irc->me = $newnick;
        }
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

        //Envoi d'un ping toutes les 60sec pour �viter que le bot
        //ne se d�connecte (par la protection anti-timeout) en cas d'inactivit�
        $this->timerPing++;
        if ($this->timerPing >=4) {
            $irc->sendRaw("PING EIRPG" . mktime());
            $this->timerPing = 0;
        }

        //On autorise le LOGIN
        $this->loginAllowed = true;
    }

///////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////

    /* Fonctions reli�s aux commandes re�ues par le bot */
    function cmdRegister($nick, $username, $password, $email)
    {
        global $irc, $irpg, $db;
        /* cmdREGISTER : cr�e un compte dans la base de donn�es */

        //On v�rifie si l'utilisateur est sur le canal
        if (!$irc->isOn($irc->home, $nick)) {
            $irc->notice($nick, "D�sol�, vous devez �tre sur \002$irc->home\002 pour vous enregistrer.");
            return false;
        } elseif (strlen($username) > 30) { //Validation du nom d'utilisateur
            $irc->notice($nick, "D�sol�, votre nom d'utilisateur est trop long. "
                . "La limite autoris�e est de \00230\002 caract�res.");
            return false;
        } elseif (!eregi("^[a-z0-9_-]+$", $username)) {
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
        if (!$this->validerMail($email)) {
            $irc->notice($nick, "D�sol�, votre adresse de courriel n'est pas valide.");
            return false;
        }

        //Requ�te SQL maintenant :)
        $db->req("INSERT INTO $table (`Username`, `Password`, `Email`, `Created`)
                  VALUES ('$username', '$password', '$email', NOW())");
        $irc->notice($nick, "Votre compte \002$username\002 a �t� cr�� avec succ�s !");
        $irc->notice($nick, "Vous pouvez � pr�sent vous authentifier � l'aide de la commande \002LOGIN\002 puis "
            . "ensuite cr�er votre premier personnage � l'aide de la commande \002CREATE\002.");
        $irc->privmsg($irc->home, "Bienvenue � notre nouveau joueur $username, connect� sous le pseudo $nick !");
    }

///////////////////////////////////////////////////////////////

    function cmdLogin($nick, $user, $host, $username, $password, $perso = null)
    {
        global $irc, $irpg, $db;

        $tbUtil  = $db->prefix . "Utilisateurs";
        $tbPerso = $db->prefix . "Personnages";
        $tbIRC   = $db->prefix . "IRC";

        if (!$this->loginAllowed) {
            //On v�rifie si le login est autoris�
            $irc->notice($nick, "D�sol�, la commande LOGIN est d�sactiv�e. "
                . "Veuillez recommencer dans quelques minutes.");
            return false;
        } elseif (!$irc->isOn($irc->home, $nick)) { //On v�rifie si l'utilisateur est sur le canal
            $irc->notice($nick, "D�sol�, vous devez �tre sur \002$irc->home\002 pour vous authentifier.");
            return false;
        } elseif ($db->nbLignes("SELECT Username FROM $tbUtil
                                 WHERE Username='$username' And Password=MD5('$password')") != 1
        ) {
            //Si mot de passe invalide
            $irc->notice($nick, "D�sol�, votre nom d'utilisateur et/ou votre mot de passe est incorrect.");
            return false;
        } elseif (array_key_exists($username, $this->users)) { //Le compte est d�j� utilis�
            $irc->notice($nick, "D�sol�, quelqu'un (probablement vous) utilise d�j� ce compte. "
                . "Utilisez la commande \002LOGOUT <utilisateur> <mot de passe>\002 pour le d�connecter "
                . "(avec p�nalit� �quivalente � celle d'un QUIT).");
            return false;
        } elseif (in_array($nick, $this->users)) {
            //L'utilisateur est d�j� identifi� sous un autre compte
            $irc->notice($nick, "D�sol�, vous �tes d�j� authentifi� sous un autre compte.");
            return false;
        } elseif (is_null($perso)) {
            //Pas de perso sp�cifi�, si un ou des persos existent,
            //on les logs tous, sinon on demande de cr�er un perso

            //Dans les 2 cas..
            $this->users["$username"] = $nick;

            if ($db->nbLignes("SELECT Nom FROM $tbPerso WHERE Util_Id = (SELECT Id_Utilisateurs FROM $tbUtil
                               WHERE Username = '$username')") == 0
            ) { //Si aucun perso
                $irc->notice($nick, "\002AUTHENTIFICATION R�USSIE!\002 Vous �tes maintenant authentifi� sous le "
                    . "compte \002$username\002, mais sous aucun personnage.");
                $irc->notice($nick, "Pour commencer � jouer, cr�ez votre premier personnage � l'aide de la "
                    . "commande \002CREATE\002.");
                $irc->privmsg($irc->home, "$nick s'est connect� au compte $username.");

                $uid = $irpg->getUIDByUsername($username);

                //Appel aux autres modules
                $y = 0;
                while ($y != count($irpg->mod)) {
                    if (method_exists($irpg->mod[$irpg->modules[$y]], "modCore_onLogin")) {
                        $irpg->mod[$irpg->modules[$y]]->modCore_onLogin($nick, $uid, NULL, NULL, NULL);
                    }
                    $y++;
                }
            } else { //Si au moins un perso
                $persodb = $db->getRows("SELECT Nom, Util_Id, Class, Id_Personnages, Level, Next FROM $tbPerso
                                         WHERE Util_Id = (SELECT Id_Utilisateurs FROM $tbUtil
                                         WHERE Username = '$username')");
                $i = 0;
                while ($i != count($persodb)) {
                    $uid      = $persodb[$i]["Util_Id"];
                    $nomPerso = $persodb[$i]["Nom"];
                    $classe   = $persodb[$i]["Class"];
                    $pid      = $persodb[$i]["Id_Personnages"];
                    $level    = $persodb[$i]["Level"];
                    $next     = $persodb[$i]["Next"];

                    $db->req("INSERT INTO $tbIRC (`Pers_Id`, `Nick`, `UserHost`, `Channel`)
                              VALUES ('$pid', '$nick', '$user@$host', '$irc->home')");

                    if (empty($lstPerso)) {
                        $lstPerso = "\002$nomPerso\002";
                    } else {
                        $lstPerso = "$lstPerso, \002$nomPerso\002";
                    }

                    $cnext = $irpg->convSecondes($next);
                    $irc->privmsg($irc->home, "$nomPerso ($username), $classe de niveau $level est maintenant "
                        . "connect� sous le pseudo $nick. Prochain niveau dans $cnext.");

                    //Update du lastlogin du personnage
                    $db->req("UPDATE $tbPerso SET LastLogin = NOW() WHERE Id_Personnages = '$pid'");

                    //Appel aux autres modules
                    $y = 0;
                    while ($y != count($irpg->mod)) {
                        if (method_exists($irpg->mod[$irpg->modules[$y]], "modCore_onLogin")) {
                            $irpg->mod[$irpg->modules[$y]]->modCore_onLogin($nick, $uid, $pid, $level, $next);
                        }
                        $y++;
                    }

                    $i++;
                }

                $irc->notice($nick, "\002AUTHENTIFICATION R�USSIE!\002 Vous �tes maintenant authentifi� sous "
                    . "le compte \002$username\002.");

                if ($i == 1) {
                    $irc->notice($nick, "Vous jouez actuellement avec le personnage $lstPerso.");
                } else {
                    $irc->notice($nick, "Vous jouez actuellement avec les personnages suivants : $lstPerso.");
                }

                if (!empty($this->motd)) {
                    $irc->notice($nick, "\002MOTD\002 -- ".$this->motd);
                }

                //� d�placer dans un module �ventuellement..
                $tbMod = $db->prefix . "Modules";
                $pub = $db->getRows("SELECT Valeur FROM $tbMod WHERE Module='pub' and Parametre='texte'
                                     ORDER BY RAND() LIMIT 0,1");
                $pub = $pub[0]["Valeur"];

                $irc->notice($nick, "\002Publicit�\002 -- " . $pub);
            }

            //On update aussi le lastlogin du compte
            $db->req("UPDATE $tbUtil SET LastLogin = NOW() WHERE Id_Utilisateurs = '$uid'");
        } else {
            //Login � un personnage sp�cifique
            $irc->notice($nick, "Fonction non d�velopp�e actuellement.");
        }
    }

///////////////////////////////////////////////////////////////

    function cmdLogout($nick, $user = null, $host = null, $username = null, $password = null)
    {
        global $irpg, $db, $irc;

        if (isset($username)) {
            //Logout "distant"..
            $irc->notice($nick, "D�sol�, cette fonction n'est pas encore d�velopp�e.");
        } else {
            //Logout du compte utilis� actuellement
            $tbIRC    = $db->prefix . "IRC";
            $tbPerso  = $db->prefix . "Personnages";
            $username = $irpg->getUsernameByNick($nick);

            if ($username) {
                //$irc->notice($nick, "Vous n'�tes plus authentifi�. "
                //    . "Une p�nalit� P20 a �t� appliqu�e � vos personnages en ligne.");

                //On selectionne les noms de personnages et le next de chaque perso
                $res = $db->getRows("SELECT Nom, Next, Level, Id_Personnages FROM $tbPerso WHERE Id_Personnages
                                     IN (SELECT Pers_Id FROM $tbIRC WHERE Nick='$nick' And NOT ISNULL(Pers_Id))");
                $i = 0;
                while ($i != count($res)) {
                    //On applique les penalites
                    $nom      = $res[$i]["Nom"];
                    $level    = $res[$i]["Level"];
                    $valeur   = $this->penLogout;
                    $expo     = $this->expPenalite;
                    $penalite = $valeur * pow($expo,$level);

                    if ($penalite > 0) {
                        $cpenalite = $irpg->convSecondes($penalite);
                        $pid       = $res[$i]["Id_Personnages"];
                        $perso     = $irpg->getNomPersoByPID($pid);
                        $db->req("UPDATE $tbPerso SET Next=Next+$penalite WHERE Id_Personnages='$pid'");
                        $next = $db->getRows("SELECT Next FROM $tbPerso WHERE Id_Personnages='$pid'");
                        $next = $next[0]["Next"];
                        $cnext = $irpg->convSecondes($res[$i]["Next"]);
                        $irpg->Log($pid, "PENAL_LOGOUT", "$penalite", "$next");
                        $irc->notice($nick, "Personnage $nom d�logu� avec une p�nalit� de $cpenalite. "
                            . "Prochain niveau dans $cnext.");
                    }

                    $i++;
                }

                //On enl�ve l'utilisateur du tableau des utilisateurs en ligne'
                unset($this->users["$username"]);

                //On enl�ve les personnages en ligne du joueur
                $db->req("DELETE FROM $tbIRC WHERE Nick='$nick' And NOT ISNULL(Pers_Id)");

                //On lui retire ses modes..
                $irc->sendRaw("MODE $irc->home -ohv $nick $nick $nick");
            } else {
                $irc->notice($nick, "Impossible de vous d�loguer, car vous n'�tes actuellement pas authentifi�.");
            }
        }
    }

///////////////////////////////////////////////////////////////

    function cmdCreate($nick, $user, $host, $personnage, $classe)
    {
        global $irpg, $irc, $db;

        $tbPerso  = $db->prefix . "Personnages";
        $tbIRC    = $db->prefix . "IRC";
        $username = $irpg->getUsernameByNick($nick);
        $uid      = $irpg->getUIDByUsername($username);

        if (!$username) {
            $irc->notice($nick, "Vous devez �tre authentifi� pour utiliser cette commande.");
        } else {
            //On v�rifie si le nombre maximal de personnage n'a pas �t� atteint
            if ($db->nbLignes("SELECT Nom FROM $tbPerso WHERE Util_Id='$uid'") >= $this->maxPerso) {
                $irc->notice($nick, "D�sol�, vous ne pouvez pas cr�er plus de $this->maxPerso personnage(s).");
            } elseif (strlen($personnage) > 30) { //On v�rifie la validit� du nom de personnage
                $irc->notice($nick, "Le nom de votre personnage est limit� � 30 caract�res.");
            } elseif (((strtoupper($personnage) == "IRPG")) || ((strtoupper($personnage) == "EIRPG"))) {
                $irc->notice($nick, "D�sol�, ce nom de personnage est r�serv�.");
            } elseif ($db->nbLignes("SELECT Nom FROM $tbPerso WHERE Nom = '$personnage'") != 0) {
                $irc->notice($nick, "D�sol�, ce nom de personnage est d�j� en utilisation.");
            } elseif (strlen($classe) > 50) { //Puis la validit� de la classe
                $irc->notice($nick, "La taille de votre classe ne peut d�passer 50 caract�res.");
            } else {
                //Cr�ation du personnage
                $base = $irpg->mod["idle"]->idleBase;
                $db->req("INSERT INTO $tbPerso (`Util_Id`, `Nom`, `Class`, `LastLogin`, `Created`, `Next`)
                          VALUES ('$uid', '$personnage', '$classe', NOW(), NOW(), '$base')");
                $pid = mysql_insert_id();
                $db->req("INSERT INTO $tbIRC (`Pers_Id`, `Nick`, `UserHost`, `Channel`)
                          VALUES ('$pid', '$nick', '$user@$host', '$irc->home')");
                $irc->notice($nick, "Votre personnage \002$personnage\002 a �t� cr�� avec succ�s. "
                    . "Vous avez �t� authentifi� automatiquement avec ce personnage.");
                $irc->notice($nick, "Pour atteindre le niveau 1, vous devez idler pendant "
                    . $irpg->convSecondes($base) . ".");
                $irc->privmsg($irc->home, "Bienvenue � $personnage, " . stripslashes($classe)
                    . " appartenant � $username/$nick. Premier niveau dans " . $irpg->convSecondes($base) . ".");
            }
        }
    }

///////////////////////////////////////////////////////////////

    function cmdHelp($nick, $message = "")
    {
        global $irc;

        $irc->notice($nick, "Aide non disponible.");
    }

///////////////////////////////////////////////////////////////

    function cmdSendPass($nick, $user, $pass)
    {
        global $irc;

        $irc->notice($nick, "Commande non disponible.");
    }

///////////////////////////////////////////////////////////////

    function cmdNotice($nick, $flag)
    {
        global $irc, $db, $irpg;

        if ((strtolower($flag) != "on") && (strtolower($flag) != "off")) {
            $irc->notice($nick, "Syntaxe incorrecte. Syntaxe : NOTICE <on/off>.");
        } else {
            $user   = $irpg->getUsernameByNick($nick);
            $tbUtil = $db->prefix."Utilisateurs";
            if (strtolower($flag) == "on") {
                //On r�tablis le message par notice
                $db->req("UPDATE $tbUtil SET Notice='O' WHERE Username='$user'");
                $irc->notice($nick, "Vous recevrez maintenant vos messages par notices.");
            } else {
                //On active les messages par privmsg
                $db->req("UPDATE $tbUtil SET Notice='N' WHERE Username='$user'");
                $irc->notice($nick, "Vous recevrez maintenant vos messages par privmsg.");
            }
        }
    }

///////////////////////////////////////////////////////////////

    function cmdWhoAmI($nick)
    {
        global $irpg,$irc,$db;

        $user     = $irpg->getUsernameByNick($nick, true);
        $username = $user[0];
        $uid      = $user[1];

        if ($uid) {
            $irc->notice($nick, "Vous �tes actuellement connect� sous le compte \002$username\002.");

            $tbPerso = $db->prefix . "Personnages";
            $res = $db->getRows("SELECT Nom, Class, Level, Next, Equi_Id FROM $tbPerso WHERE Util_Id='$uid'");

            $i = 0;
            while ($i != count($res)) {
                $nom    = $res[$i]["Nom"];
                $class  = $res[$i]["Class"];
                $level  = $res[$i]["Level"];
                $next   = $irpg->convSecondes($res[$i]["Next"]);
                $equipe = $res[$i]["Equi_Id"];

                if (is_null($equipe)) {
                    $irc->notice($nick, "Personnage \002$nom\002, $class de niveau $level. "
                        . "Prochain niveau dans $next.");
                } else {
                    $irc->notice($nick, "Personnage \002$nom\002, $class de niveau $level "
                        . "(membre de l'�quipe \002$equipe\002). Prochain niveau dans $next.");
                }

                $i++;
            }
        } else {
            $irc->notice($nick, "Vous n'�tes pas authentifi� actuellement.");
        }
    }

///////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////

    /* Fonctions diverses */
    function validerMail($mail)
    {
        return ereg('^[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+'
            . '@'
            . '[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.'
            . '[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+$', $mail);
    }

////////////////////////////////////////////////////////////////

    function resetAutoLogin()
    {
        $i = 0;
        $nb = count($this->autologged); //-1 � chaque loop, donc il faut la valeur inititialle
        while ($i != $nb) {
            unset($this->autologged[$i]);
            $i++;
        }
    }

///////////////////////////////////////////////////////////////
}
?>
