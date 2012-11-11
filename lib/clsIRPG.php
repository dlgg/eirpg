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

/**
 * Classe IRPG; classe très large qui regroupe plusieurs
 * fonctions utilisées par le bot
 *
 * @author Homer
 * @author cedricpc
 * @created 30 mai 2005
 * @modified  Monday 01 November 2010 @ 21:50 (CET)
 */
class IRPG
{
    ///////////////////////////////////////////////////////////////
    // Variables privées
    ///////////////////////////////////////////////////////////////
    var $config;   //Paramètres de configuration chargés en mémoire
    var $mod;      //Modules chargés
    var $modules;  //Noms des modules chargés
    var $pause;    //Indique si le jeu est en pause ou non
    var $ignoresN; //Liste des nicks ignorés en mémoire
    var $ignoresH; //Liste des hosts ignorés en mémoire
    ///////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////


    ///////////////////////////////////////////////////////////////
    // Méthodes privées, même si PHP s'en fou !
    ///////////////////////////////////////////////////////////////

    /**
     * Valide une section du fichier de configuration
     *
     * @author Homer
     * @author cedricpc
     * @created 1er juin 2005
     * @modified 19 Avril 2010
     * @return boolean - true si la config est OK, false autrement
     */
    function validationConfig($section, $keys, $keys_opt = null)
    {
        $config = parse_ini_file('irpg.conf', true);

        $this->config[$section] = array();
        $keys     = (array) $keys;
        $keys_opt = (array) $keys_opt;

        //On traite les clés obligatoires
        foreach ( $keys as $key) {
            if (empty($key)) {
                continue;
            }

            $value = (isset($config[$section][$key]) ? $config[$section][$key] : '');
            if ($value != '') {
                $this->config[$section][$key] = $value;
            } else {
                return false;
            }
        }

        //Ensuite, les clés optionnelles
        foreach ($keys_opt as $key) {
            if (empty($key)) {
                continue;
            }

            $this->config[$section][$key] = (isset($config[$section][$key]) ? $config[$section][$key] : '');
        }

        //On est évidemment pas en pause !
        $this->pause = false;

        //Si on s'est rendu ici c'est que tout fonctionne !
        return true;
    }

    ///////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////

    ///////////////////////////////////////////////////////////////
    // Méthodes publiques
    ///////////////////////////////////////////////////////////////

    /**
     * Constructeur; valide et charge la configuration
     *
     * @author Homer
     * @created 30 mai 2005
     * @modified 1er juin 2005
     * @return boolean - true si la config est OK, false autrement
     */
    function init()
    {
        $this->alog("Lecture du fichier de configuration...", true);

        //Traitement de la section DB
        $keys     = array("host", "login", "password", "base"); //clés obligatoires
        $keys_opt = array("prefix", "charset");                 //clés optionnelles

        $ok = $this->validationConfig("SQL", $keys, $keys_opt);
        if (!$ok) {
            return false;
        }

        //Traitement de la section IRC
        $keys     = array(
            "server", "port", "channel", "nick", "altnick", "username", "realname", "modes"
        );                                                                 //clés obligatoires
        $keys_opt = array("password", "nspass", "bind", "key", "charset"); //clés optionnelles

        $ok = $this->validationConfig("IRC", $keys, $keys_opt);
        if (!$ok) {
            return false;
        }

        //Traitement de la section IRPG
        $keys     = array("admin", "debug", "background", "purge", "version", "modules"); //clés obligatoires
        $keys_opt = array("charset");                                                     //clés optionnelles

        $ok = $this->validationConfig("IRPG", $keys, $keys_opt);
        if (!$ok) {
            return false;
        }

        $this->ignoresH = array();
        $this->ignoresN = array();

        //Si on s'est rendu ici c'est que tout est OK :)
        return true;
    }

///////////////////////////////////////////////////////////////

    /**
     * Charge les modules
     *
     * @author Homer
     * @created 20 juin 2005
     * @modified 20 juin 2005
     * @param clsIRC      - Référence à l'objet IRC
     * @param clsIRPG     - Référence à l'objet IRPG
     * @return none
     */
    function loadModules()
    {
        global $irc, $irpg;

        //Chargement des modules
        $this->modules = $this->readConfig("IRPG", "modules");
        $this->modules = explode(',', $this->modules);

        //On vérifie que les modules existent
        $i = 0;
        while ($i != count($this->modules)) {
            if (!file_exists("modules/mod_" . $this->modules[$i] . ".php")) {
                die("Le module mod_" . $this->modules[$i] . " n'existe pas\n");
            } else {
                $this->alog("Chargement du module mod_" . $this->modules[$i] . "...", true);
                include "modules/mod_" . $this->modules[$i] . ".php";
                $this->mod[$this->modules[$i]] = new $this->modules[$i]();
                $this->mod[$this->modules[$i]]->loadModule();
            }
            $i++;
        }
    }

///////////////////////////////////////////////////////////////

    /**
     * Charge un module
     *
     * @author Homer
     * @created 22 juin 2005
     * @modified 22 juin 2005
     * @param nom         - Nom du module à charger (sans le prefixe mod_)
     * @return boolean    - true si module chargé, false autrement
     */
    function loadModule($nom)
    {
        if (!file_exists("modules/mod_" . $nom . ".php")) { //On vérifie que le module existe
            return false;
        } elseif (in_array($nom, $this->modules)) { //On s'assure que le module ne soit pas déjà chargé
            return false;
        } else {
            include_once "modules/mod_" . $nom . ".php"; //TODO: rechercher le module sur REHASH (??)
            $this->modules[] = $nom;
            $this->mod[$nom] = new $nom;
            $this->mod[$nom]->loadModule();
            return true;
        }
    }

///////////////////////////////////////////////////////////////

    function unloadModule($nom)
    {
        //On vérifie si le module existe
        if (!in_array($nom, $this->modules)) {
            return false; //module inexistant
        } else {
            $i = 0;
            foreach ($this->mod as $nomModule => $leModule) {
                $y = 0;
                while ($y != count($this->mod[$nom]->depend)) {
                    if ($this->mod[$nomModule]->depend[$y] == $nom . "/" . $this->mod[$nom]->version) {
                        return false; // On ne peut décharger ce module car il est requis
                        break;        // par un autre module actuellement chargé
                    }
                    $y++;
                }

                $i++;
            }

            //On peut maintenant décharger le module
            //On appel l'évènement unloadmodule() avant de décharger
            $this->mod[$nom]->unloadmodule();
            //On retire le module des tableaux de modules
            unset($this->mod[$nom]);
            unset($this->modules[(array_search($nom, $this->modules))]);
            $this->modules = array_values($this->modules); //reset des indices du tableau
            return true;
        }
    }

///////////////////////////////////////////////////////////////

    /**
     * Config; interprète le fichier de configuration
     *
     * @author Homer
     * @author cedricpc
     * @created 30 mai 2005
     * @modified 19 Avril 2010
     * @param section    - Section du fichier de config (SQL, IRC, IRPG...)
     * @param key        - Élément du fichier que l'on recherche (server, port, login..)'
     * @param fromFile   - Si vrai lis la configuration du fichier, sinon des données en mémoire
     * @return string    - La valeur contenu dans le fichier de configuration
     */
    function readConfig($section, $key, $fromFile = false)
    {
        if ($fromFile) {
            $config = parse_ini_file("irpg.conf", true);
            return (isset($config[$section][$key]) ? $config[$section][$key] : null);
        } else {
            //Retourne ce qui a été préablement chargé en mémoire
            return (isset($this->config[$section][$key]) ? $this->config[$section][$key] : null);
        }
    }

///////////////////////////////////////////////////////////////

    /**
     * Vérifie si les dépendances d'un
     * module sont satisfaites
     *
     * @author Homer
     * @created 20 juin 2005
     * @modified 22 juin 2005
     * @param dep[]    - Tableau contenant les modules nécessaires
     * @return boolean - True si dépendances satisfaites, false autrement.
     */
    function checkDepd($dep)
    {
        $i = 0;
        while ($i != count($dep)) {
            //Nom & version de la dépendance requise
            $module        = explode('/', $dep[$i]);
            $nomModule     = $module[0];
            $versionModule = $module[1];

            if (empty($nomModule)) {
                return true;
            }

            //On vérifie si le module est chargé
            if (!in_array($nomModule, $this->modules)) {
                return false;
            } else {
                //On vérifie que la version du module est suffisante

                //Version requise par le module
                $versionModule = explode('.', $versionModule);
                $vr_majeur     = $versionModule[0];
                $vr_mineur     = $versionModule[1];
                $vr_revision   = $versionModule[2];

                //Version actuelle du module
                $versionActuelle = explode('.', $this->mod[$nomModule]->version);
                $va_majeur       = $versionActuelle[0];
                $va_mineur       = $versionActuelle[1];
                $va_revision     = $versionActuelle[2];

                if ($va_majeur > $vr_majeur) {
                    //return true;
                } elseif ($va_majeur < $vr_majeur) {
                    return false;
                } elseif ($va_mineur > $vr_mineur) {
                    //return true;
                } elseif ($va_mineur < $vr_mineur) {
                    return false;
                } elseif ($va_revision >= $vr_revision) {
                    //return true;
                } elseif ($va_revision < $vr_revision) {
                    return false;
                } else {
                    return false;
                }
            }
            $i++;
        }

        return true;
    }

///////////////////////////////////////////////////////////////

    function alog($msg, $print = false)
    { //Gestion des logs et de l'affichage des info de débuguage
        $date = date("j-m-Y H:i:s");
        if ((!$this->readConfig("IRPG", "background") || ($print))) {
            $charset = $this->readConfig("IRPG", "charset");
            print iconv('ISO-8859-15', ($charset ? $charset : 'ISO-8859-15') . '//TRANSLIT', "[$date] $msg\n");
        }
        $flog = fopen("irpg.log", "a+");
        fwrite($flog, "[$date] " . $msg . "\n");
        fclose($flog);
    }

///////////////////////////////////////////////////////////////

    function getUsernameByNick($nick, $uid = false)
    {
        $username = array_search($nick, $this->mod["core"]->users);

        if (($uid) && ($username)) {
            global $db;
            $table = $db->prefix . "Utilisateurs";
            $uid = $db->getRows("SELECT Id_Utilisateurs FROM $table WHERE Username = '$username'");

            return array($username, (empty($uid) ? null : $uid[0]["Id_Utilisateurs"]));
        } else {
            return $username;
        }
    }

///////////////////////////////////////////////////////////////

    function getNickByUID($uid)
    {
        global $db;

        $tbIRC   = $db->prefix . "IRC";
        $tbPerso = $db->prefix . "Personnages";

        $nick = $db->getRows("SELECT Nick FROM $tbIRC WHERE Pers_Id = (SELECT Id_Personnages FROM $tbPerso
                              WHERE Util_Id='$uid' LIMIT 0,1)");

        return (empty($nick) ? false : $nick[0]["Nick"]);
    }

 ///////////////////////////////////////////////////////////////

    function getUsernameByUID ($uid)
    {
        global $db;

        $tbUtil = $db->prefix . "Utilisateurs";
        $username = $db->getRows("SELECT Username FROM $tbUtil WHERE Id_Utilisateurs = '$uid' LIMIT 0,1");

        return (empty($username) ? false : $username[0]['Username']);
    }

///////////////////////////////////////////////////////////////

    function getNomPersoByPID ($pid)
    {
        global $db;

        $tbPerso = $db->prefix . "Personnages";
        $result = $db->getRows("SELECT Nom FROM $tbPerso WHERE Id_Personnages = '$pid'");

        return (empty($result) ? false : $result[0]['Nom']);
    }

///////////////////////////////////////////////////////////////

    function getPIDByPerso ($perso)
    {
        global $db;

        $tbPerso = $db->prefix . "Personnages";
        $result = $db->getRows("SELECT Id_Personnages FROM $tbPerso WHERE Nom = '$perso'");

        return (empty($result) ? false : $result[0]['Id_Personnages']);
    }

///////////////////////////////////////////////////////////////

    function getPersoByUsername($username)
    {
    }

///////////////////////////////////////////////////////////////

    function getUIDByPID ($pid)
    {
        global $db;

        $tb  = $db->prefix . "Personnages";
        $res = $db->getRows("SELECT Util_Id FROM $tb WHERE Id_Personnages='$pid'");

        return (empty($res) ? false : $res[0]['Util_Id']);
    }

///////////////////////////////////////////////////////////////

    function getUIDByUsername($username)
    {
        global $db;

        $tbUtil = $db->prefix . "Utilisateurs";
        $uid = $db->getRows("SELECT Id_Utilisateurs FROM $tbUtil WHERE Username='$username'");

        return (empty($uid) ? false : $uid[0]['Id_Utilisateurs']);
    }

///////////////////////////////////////////////////////////////

    function convSecondes($sec)
    {
        if ($sec == 0) {
            return "00:00:00";
        } else {
            return sprintf("%d jour%s, %02d:%02d:%02d", $sec / 86400, $sec < 172800 ? "" : "s",
                ($sec % 86400) / 3600, ($sec % 3600) / 60, $sec % 60
            );
        }
    }

///////////////////////////////////////////////////////////////

    function getAdminLvl($uid)
    {
        //Retourne le niveau d'accès admin d'un utilisateur
        global $db;

        $tbUtil = $db->prefix . "Utilisateurs";
        $resultat = $db->getRows("SELECT Admin FROM $tbUtil WHERE Id_Utilisateurs = '$uid'");

        return (empty($resultat) ? 0 : $resultat[0]['Admin']);
    }

///////////////////////////////////////////////////////////////

    function Log($pid, $type, $modif = 0, $d1 = "", $d2 = "", $d3 = "")
    {
        //Ajout dans la table Logs
        global $db;

        $tbLogs = $db->prefix . "Logs";
        if ($pid == null) {
            $db->req("INSERT INTO $tbLogs (`Pers_Id`, `Date`, `Type`, `Modificateur`, `Desc1`, `Desc2`, `Desc3`)
                      VALUES (NULL, NOW(), '$type', '$modif', '$d1', '$d2', '$d3')");
        } else {
            $db->req("INSERT INTO $tbLogs (`Pers_Id`, `Date`, `Type`, `Modificateur`, `Desc1`, `Desc2`, `Desc3`)
                      VALUES ('$pid', NOW(), '$type', '$modif', '$d1', '$d2', '$d3')");
        }
    }

///////////////////////////////////////////////////////////////

    function userExist($user)
    {
        global $db;

        $table = $db->prefix . "Utilisateurs";
        $r = $db->req("SELECT Username FROM $table WHERE Username='$user'");
        if (mysql_num_rows($r) != 0) {
            return true;
        }
        return false;
    }

///////////////////////////////////////////////////////////////

    function persoExist($perso)
    {
        global $db;

        $table = $db->prefix . "Personnages";
        $r = $db->req("SELECT Nom FROM $table WHERE Nom='$perso'");
        if (mysql_num_rows($r) != 0) {
            return true;
        }
        return false;
    }

    function equipeExist($equipe)
    {
        global $db;

        $table = $db->prefix . "Equipes";
        $r = $db->req("SELECT Name FROM $table WHERE Name='$equipe'");
        if (mysql_num_rows($r) != 0) {
            return true;
        }
        return false;
    }

///////////////////////////////////////////////////////////////

    function lireIgnores()
    {
        // lecture de la liste d'ignore en mémoire
        $this->alog("Lecture de la liste des ignores...");
        $f = fopen("ignores.list", "r");
        while (!feof($f)) {
            $ligne = fgets($f);

            if (substr($ligne, 0, 4) == "NICK") {
                $nick = explode(":", $ligne);
                $nick = trim($nick[1]);
                $this->ignoresN[] = $nick;
                $this->alog("Le pseudo $nick est ignoré...");
            } elseif (substr($ligne, 0, 4) == "HOST") {
                $host = explode(":", $ligne);
                $host = trim($host[1]);
                $this->ignoresH[] = $host;
                $this->alog("L'host $host est ignoré...");
            }
        }
        fclose($f);
    }

    function getIgnoresN()
    {
        return $this->ignoresN;
    }

    function getIgnoresH()
    {
        return $this->ignoresH;
    }

    ///////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////
}
?>
