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
 * Classe DB; classe de connexion et de requ�tes
 * vers la base de donn�es mySQL
 *
 * @author Homer
 * @author    cedricpc
 * @created 30 mai 2005
 * @modified  Monday 23 November 2010 @ 02:55 (CET)
 */
class DB
{
    ///////////////////////////////////////////////////////////////
    // Variables priv�es
    ///////////////////////////////////////////////////////////////
    var $host;
    var $login;
    var $pass;
    var $base;
    var $prefix;
    var $charset;
    var $connected; //Indique si nous sommes connect�s � la bd SQL
    ///////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////


    ///////////////////////////////////////////////////////////////
    // M�thodes priv�es, m�me si PHP s'en fou !
    ///////////////////////////////////////////////////////////////

    ///////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////

    ///////////////////////////////////////////////////////////////
    // M�thodes publiques
    ///////////////////////////////////////////////////////////////

    function connexion($host, $login, $pass, $base, $prefix, $charset = null)
    {
        global $irpg;

        $this->host    = $host;
        $this->login   = $login;
        $this->pass    = $pass;
        $this->base    = $base;
        $this->prefix  = $prefix;
        $this->charset = $charset;

        $irpg->alog("Connexion au serveur de bases de donn�es...", true);
        if (@mysql_connect($this->host, $this->login, $this->pass)) {
            if (!empty($this->charset) && function_exists('mysql_set_charset')) {
                mysql_set_charset($this->charset);
                $irpg->alog('D�finition du jeu de caract�re ' . $this->charset . '... '
                    . mysql_client_encoding(), true);
            }

            if (mysql_select_db($this->base)) {
                $irpg->alog("Connect� ! (" . $this->host . " ; " . $this->login . " ; " . $this->base . ")", true);
                $this->connected = true;
                return true;
            } else {
                return false;
            }
        } else {
            $irpg->alog('�chou�e : ' . mysql_error(), true);
            return false;
        }
    }

///////////////////////////////////////////////////////////////

    function deconnexion()
    {
        $this->connected = false;
        mysql_close();
    }

///////////////////////////////////////////////////////////////

    function req($query, $ignoredebug = false)
    {
        global $irpg, $irc;

        if ($this->connected) {
            if (($irpg->readConfig("IRPG", "debug")) && (!$ignoredebug)) {
                $irpg->alog("SQL: " . $query);
            }

            if (mysql_ping()) {
                return mysql_query($query);
            } else {
                $this->deconnexion();
                $irpg->pause = true;
                $irpg->alog("Perte de la connexion au serveur de base de donn�es !", true);
                $irc->privmsg($irc->home, "Attention, jeu automatiquement d�sactiv� !! "
                    . "Raison: perte de la connexion au serveur de bases de donn�es. "
                    . "Une nouvelle tentative se fera toutes les 15 secondes...");
            }
        }
    }

///////////////////////////////////////////////////////////////

    function nbLignes($query)
    {
        global $irpg;

        if ($irpg->readConfig("IRPG", "debug")) {
            $irpg->alog("SQL: " . $query);
        }

        return ($result = $this->req($query, true) ? mysql_num_rows($result) : 0);
    }

///////////////////////////////////////////////////////////////

    function getRows($query)
    {
        global $irpg;

        if ($irpg->readConfig("IRPG", "debug")) {
            $irpg->alog("SQL: " . $query);
        }

        $r = $this->req($query, true);
        if (!$r) {
            return false;
        }

        $enregistrements = array();
        while ($li = mysql_fetch_array($r)) {
            $enregistrements[] = $li;
        }

        return $enregistrements;
    }

    ///////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////
}
?>
