<?php

/*
 * EpiKnet Idle RPG (EIRPG)
 * Copyright (C) 2005-2012 Francis D (Homer), Womby, cedricpc & EpiKnet
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
 * Module mod_quests
 * @author Womby
 * @author    cedricpc
 * @modified  Monday 01 November 2010 @ 22:50 (CET)
 */
class quests
{
//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**
    var $name;    //Nom du module
    var $version; //Version du module
    var $desc;    //Description du module
    var $depend;  //Modules dont nous sommes d�pendants

    //Variables suppl�mentaires
    var $queteEnCours = -1; //-1:Pas de quete en cours; 1: Quete d'aventure en cours; 2: quete de Royaume en cours
    var $participants = array();
    var $tempsQuete,$probaAllQuete,$probaQueteA;
    var $tempsQueteA,$tempsQueteR;
    var $recompenseA,$recompenseR, $queteSurvivant = false;
    var $MinPenalite,$MaxPenalite,$MinPenaliteAll,$MaxPenaliteAll;
    var $nbrParticipants, $tempsMinIdleA, $tempsMinIdleR, $lvlMinimumA, $lvlMinimumR;
//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**

///////////////////////////////////////////////////////////////

    function loadModule()
    {
        //Constructeur; initialisateur du module
        //S'�x�cute lors du (re)chargement du bot ou d'un REHASH
        global $irc, $irpg, $db;

        /* Renseignement des variables importantes */
        $this->name    = "mod_quests";
        $this->version = "0.2.0";
        $this->desc    = "Quetes";
        $this->depend  = array("core/0.5.0");

        //Recherche de d�pendances
        if (!$irpg->checkDepd($this->depend)) {
            die("{$this->name}: d�pendance non r�solue\n");
        }

        //Validation du fichier de configuration sp�cifique au module
        $cfgKeys    = array(
            "tempsQueteA", "tempsQueteR", "recompenseA", "recompenseR", "recompenseS", "MinPenalite", "MaxPenalite",
            "MinPenaliteAll", "MaxPenaliteAll", "nbrParticipants", "tempsMinIdleA", "tempsMinIdleR", "tempsMinIdleS",
            "lvlMinimumA", "lvlMinimumR", "lvlMinimumS", "probaAllQuete", "probaQueteA",
        );
        $cfgKeysOpt = array("");

        if (!$irpg->validationConfig($this->name, $cfgKeys, $cfgKeysOpt)) {
            die("{$this->name}: V�rifiez votre fichier de configuration.\n");
        }

        //Initialisation des param�tres du fichier de configuration
        $this->tempsQueteA     = $irpg->readConfig($this->name, "tempsQueteA");
        $this->tempsQueteR     = $irpg->readConfig($this->name, "tempsQueteR");
        $this->recompenseA     = $irpg->readConfig($this->name, "recompenseA");
        $this->recompenseR     = $irpg->readConfig($this->name, "recompenseR");
        $this->recompenseS     = $irpg->readConfig($this->name, "recompenseS");
        $this->MinPenalite     = $irpg->readConfig($this->name, "MinPenalite");
        $this->MaxPenalite     = $irpg->readConfig($this->name, "MaxPenalite");
        $this->MinPenaliteAll  = $irpg->readConfig($this->name, "MinPenaliteAll");
        $this->MaxPenaliteAll  = $irpg->readConfig($this->name, "MaxPenaliteAll");
        $this->nbrParticipants = $irpg->readConfig($this->name, "nbrParticipants");
        $this->tempsMinIdleA   = $irpg->readConfig($this->name, "tempsMinIdleA");
        $this->tempsMinIdleR   = $irpg->readConfig($this->name, "tempsMinIdleR");
        $this->tempsMinIdleS   = $irpg->readConfig($this->name, "tempsMinIdleS");
        $this->lvlMinimumA     = $irpg->readConfig($this->name, "lvlMinimumA");
        $this->lvlMinimumR     = $irpg->readConfig($this->name, "lvlMinimumR");
        $this->lvlMinimumS     = $irpg->readConfig($this->name, "lvlMinimumS");
        $this->probaAllQuete   = $irpg->readConfig($this->name, "probaAllQuete");
        $this->probaQueteA     = $irpg->readConfig($this->name, "probaQueteA");
    }

///////////////////////////////////////////////////////////////

    function unloadModule()
    {
        //Destructeur; d�charge le module
        //S'�x�cute lors du SHUTDOWN du bot ou d'un REHASH
    }

///////////////////////////////////////////////////////////////

    function onConnect()
    {
    }

///////////////////////////////////////////////////////////////

    function onPrivmsgCanal($nick, $user, $host, $message)
    {
        global $irpg;

        // SI il n'y a aucune quete en cours on ne fait rien du tout (Optimisation).
        if (!$irpg->pause && ($this->queteSurvivant || ($this->queteEnCours > 0))) {
            // On lit le fichier de config pour verifier que l'action est consid�r�e comme p�nalit�.
            if ($irpg->readConfig('mod_penalites', 'penPrivmsg') != 0) {
                // On verifie que le nick participe a la quete, et si la quete est abandonn�e le cas �ch�ant.
                $this->verifFinQuete($nick);
            }
        }
    }

///////////////////////////////////////////////////////////////

    function onPrivmsgPrive($nick, $user, $host, $message)
    {
        global $irc, $irpg, $db;

        $message = trim(str_replace("\n", "", $message));
        $message = explode(" ", $message);
        switch (strtoupper($message[0])) {
        case "QUEST":
        case "QUETE":
            $this->cmdQuest($nick);
            break;
        case "QUESTSTART":
            $this->cmdQuestStart($nick);
            break;
        }
    }

///////////////////////////////////////////////////////////////

    function onNoticeCanal($nick, $user, $host, $message)
    {
        global $irpg;

        if (!$irpg->pause && ($this->queteSurvivant || ($this->queteEnCours > 0))) {
            if ($irpg->readConfig('mod_penalites', 'penNotice') != 0) {
                $this->verifFinQuete($nick);
            }
        }
    }

///////////////////////////////////////////////////////////////

    function onNoticePrive($nick, $user, $host, $message)
    {
        global $irpg;

        if (!$irpg->pause && ($this->queteSurvivant || ($this->queteEnCours > 0))) {
            if($irpg->readConfig('mod_penalites', 'penNotice') != 0) {
                $this->verifFinQuete($nick);
            }
        }
    }

///////////////////////////////////////////////////////////////

    function onJoin($nick, $user, $host, $channel)
    {
    }

///////////////////////////////////////////////////////////////

    function onPart($nick, $user, $host, $channel)
    {
        global $irpg;

        if (!$irpg->pause && ($this->queteSurvivant || ($this->queteEnCours > 0))) {
            if ($irpg->readConfig('mod_penalites', 'penPart') != 0) {
                $this->verifFinQuete($nick);
            }
        }
    }

///////////////////////////////////////////////////////////////

    function onNick($nick, $user, $host, $newnick)
    {
        global $irpg;

        if ($this->queteSurvivant || ($this->queteEnCours > 0)) {
            // On verifie que le nick changeant participe a une quete et si c'est le cas
            // on met a jour les informations concernant ce participant dans le tableau des participants � la quete.
            foreach ($this->participants as $queteId => $persos) {
                foreach ($persos as $i => $perso) {
                    if ($persos[1] == $nick) {
                        $this->participants[$queteId][$i][1] = $newnick;
                    }
                }
            }

            if (!$irpg->pause && ($irpg->readConfig('mod_penalites', 'penNick') != 0)) {
                $this->verifFinQuete($nick);
            }
        }
    }

///////////////////////////////////////////////////////////////

    function onKick($nick, $user, $host, $channel, $nickkicked)
    {
        global $irpg;

        if (!$irpg->pause && ($this->queteSurvivant || ($this->queteEnCours > 0))) {
            if ($irpg->readConfig('mod_penalites', 'penKick') != 0) {
                $this->verifFinQuete($nickkicked);
            }
        }
    }

///////////////////////////////////////////////////////////////

    function onCTCP($nick, $user, $host, $ctcp)
    {
    }

///////////////////////////////////////////////////////////////

    function onQuit($nick, $user, $host, $reason)
    {
        global $irpg;

        if (!$irpg->pause && ($this->queteSurvivant || ($this->queteEnCours > 0))) {
            if ($irpg->readConfig('mod_penalites', 'penQuit') != 0) {
                $this->verifFinQuete($nick);
            }
        }
    }

///////////////////////////////////////////////////////////////

    function on5Secondes()
    {
    }

///////////////////////////////////////////////////////////////

    function on10Secondes()
    {
    }

///////////////////////////////////////////////////////////////

    function on15Secondes()
    {
        global $irc, $irpg, $db;

        $tbPerso = $db->prefix . 'Personnages';
        $tbIRC   = $db->prefix . 'IRC';

        if (!$irpg->pause) {
            // Si une quete est mise en route il y a 80 % de chance que ce soit une quete d'aventure,
            // 20 % une quete de Royaume s'il n'y a pas de qu�te du survivant, 10 % chacune dans le cas contraire.
            // Sinon, s'il y a une quete en cours, on verifie le temps de quete restant.
            if (($this->queteEnCours < 1) && !$this->queteSurvivant && (rand(1, $this->probaAllQuete) == 1)) {
                $proba = rand(1, 100);
                if (($this->nbrParticipants > 1) && ($proba > 50 + round($this->probaQueteA / 2))) {
                    $this->queteSurvivant = $this->queteSurvivant();
                } else {
                    $quete = ($proba > $this->probaQueteA ? $this->queteRoyaume() : $this->queteAventure());
                    $this->queteEnCours = $quete;
                }
            } elseif (($this->queteEnCours == 1) || ($this->queteEnCours == 2)) {
                // S'il reste encore suffisamment du temps, on enl�ve 15 secondes.
                // Sinon on donne une recompense � tout les personnages qui ont particip� jusqu'au bout.
                if ($this->tempsQuete > 15) {
                    $this->tempsQuete -= 15;
                } elseif (!empty($this->participants[$this->queteEnCours])) {
                    $recompense = ($this->queteEnCours == 1 ? $this->recompenseA : $this->recompenseR) / 100;

                    foreach ($this->participants[$this->queteEnCours] as $perso) {
                        if ($perso[0] != -1) {
                            $pid   = $perso[0];
                            $cnext = $db->getRows("SELECT Next FROM $tbPerso WHERE Id_Personnages = '$pid'");
                            //$cnext = $cnext[0][0]; //Optimisation
                            $recompense = round($cnext[0][0] * $recompense);
                            $db->req("UPDATE $tbPerso SET Next=Next-$recompense WHERE Id_Personnages = '$pid'");
                        }
                    }

                    $participants = $this->listerParticipants($this->participants[$this->queteEnCours]);
                    if ($listeParticipants[0] > 1) {
                        if ($this->queteEnCours == 1) {
                            $message = "{$participants[1]} sont revenus de leur qu�te et ont rempli l'objectif ! "
                                     . "Bravo, voici votre r�compense : $recompense % de vos TTL sont enlev�s !";
                        } else {
                            $message = "{$participants[1]} sont revenus de leur qu�te � temps. Le royaume est "
                                     . "sauv�... Ils seront largement r�compens�s : $recompense % de leurs TTL sont "
                                     . "enlev�s !";
                        }
                    } else {
                        if ($this->queteEnCours == 1) {
                            $message = "{$participants[1]} est revenu de sa qu�te et a rempli l'objectif ! Bravo, "
                                     . "voil� ta r�compense : $recompense % de ton TTL sont enlev�s !";
                        } else {
                            $message = "{$participants[1]} est revenu de sa qu�te � temps. Le royaume est sauv�... "
                                     . "Il sera largement r�compens� : $recompense % de son TTL sont enlev�s !";
                    }
                    $irc->privmsg($irc->home, $message);

                    $this->queteEnCours = -1;
                    unset($this->participants[$this->queteEnCours]);
                } else {
                    $this->queteEnCours = -1;
                }
            }
        }
    }

///////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////

    function queteAventure()
    {
        global $irpg, $irc, $db;

        $tbPerso = $db->prefix . 'Personnages';
        $tbIRC   = $db->prefix . 'IRC';
        $tbTxt   = $db->prefix . 'Textes';

        // La query suivante va retourner le nombre de personnage voulu au hasard dont le level est suffisant et dont
        // le temps d'idle est suffisant. Le group by sur Util_id permet de ne recuperer qu'un personnage par user.
        $query = "SELECT Id_Personnages, Nom, Util_id, Level FROM $tbPerso
                  WHERE Id_Personnages IN (SELECT Pers_Id FROM $tbIRC WHERE NOT ISNULL(Pers_Id))
                  AND (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(LastLogin)) > {$this->tempsMinIdleA}
                  AND Level >= {$this->lvlMinimumA} GROUP BY Util_id ORDER BY RAND()
                  LIMIT 0, {$this->nbrParticipants}";

        // Si le nombre de personnage retourn� est inferieur au nombre voulut on ne peut pas commenc� la qu�te
        if ($db->nbLignes($query) != $this->nbrParticipants) {
            return -1;
        }

        $queteId = 1;

        // On recupere les infos retourn�es par la query.
        $this->participants[$queteId] = $db->getRows($query);

        // Pour chaque personnage on va recuperer son nick.
        foreach ($this->participants[$queteId] as $i => $perso) {
            $this->participants[$queteId][$i][1] = $irpg->getNickByUID($this->participants[$queteId][$i][2]);
        }

        // On prend un texte de quete au hasard.
        $message = reset(reset($db->getRows("SELECT Valeur FROM $tbTxt WHERE Type='Qa' ORDER BY RAND() LIMIT 0,1")));

        // le temps de quete est etablit selon le temps de quete desir� plus un temps au hasard entre 1
        // et ce temps de quete desir�. On peut donc passer du simple au double au hasard.
        $this->tempsQuete = $this->tempsQueteA + rand(1, $this->tempsQueteA);
        $temps = $irpg->convSecondes($this->tempsQuete);

        //On �tabli la liste des participants dans une chaine.
        $participants = $this->listerParticipants($this->participants[$queteId]);
        if ($participants[0] > 1) {
            $irc->privmsg($irc->home, "{$participants[1]} ont �t� choisis pour $message Ils ont $temps pour en "
                . 'revenir...');
        } else {
            $irc->privmsg($irc->home, "{$participants[1]} a �t� choisi pour $message Il a $temps pour en revenir...");
        }

        return 1;
    }

///////////////////////////////////////////////////////////////

    function queteRoyaume()
    {
        global $irpg, $irc, $db;

        $tbPerso = $db->prefix . 'Personnages';
        $tbIRC   = $db->prefix . 'IRC';
        $tbTxt   = $db->prefix . 'Textes';

        // La query suivante va retourner le nombre de personnage voulu au hasard dont le level est suffisant
        // et dont le temps d'idle est suffisant
        $query = "SELECT Id_Personnages, Nom, Util_id, Level FROM $tbPerso
                  WHERE Id_Personnages IN (SELECT Pers_Id FROM $tbIRC WHERE NOT ISNULL(Pers_Id))
                  AND (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(LastLogin)) > {$this->tempsMinIdleR}
                  AND Level >= {$this->lvlMinimumR} GROUP BY Util_id ORDER BY RAND()
                  LIMIT 0, {$this->nbrParticipants}";

        // Si le nombre de personnage retourn� est inferieur au nombre voulut on ne peut pas commenc� la qu�te
        if ($db->nbLignes($query) != $this->nbrParticipants) {
            return -1;
        }

        $queteId = 2;

        // On recupere les infos retourn�es par la query.
        $this->participants[$queteId] = $db->getRows($query);

        // Pour chaque personnage on va recuperer son nick.
        foreach ($this->participants[$queteId] as $i => $perso) {
            $this->participants[$queteId][$i][1] = $irpg->getNickByUID($this->participants[$queteId][$i][2]);
        }

        // On prend un texte de quete au hasard.
        $message = reset(reset($db->getRows("SELECT Valeur FROM $tbTxt WHERE Type='Qr' ORDER BY RAND() LIMIT 0,1")));

        // le temps de quete est etablit selon le temps de quete desir� plus un temps au hasard entre 1 et ce temps
        // de quete desir�. On peut donc passer du simple au double au hasard.
        $this->tempsQuete = $this->tempsQueteR + rand(1, $this->tempsQueteR);
        $temps = $irpg->convSecondes($this->tempsQuete);

        //On �tabli la liste des participants dans une chaine.
        $participants = $this->listerParticipants($this->participants[$queteId]);
        if ($participants[0] > 1) {
            $irc->privmsg($irc->home, "Qu�te de Royaume ! {$participants[1]} ont �t� choisis pour $message Ils ont "
                . "$temps pour en revenir...");
        } else {
            $irc->privmsg($irc->home, "Qu�te de Royaume ! {$participants[1]} a �t� choisi pour $message Il a "
                . "$temps pour en revenir...");
        }

        return 2;
    }

///////////////////////////////////////////////////////////////

    function queteSurvivant()
    {
        global $irpg, $irc, $db;

        $tbPerso = $db->prefix . 'Personnages';
        $tbIRC   = $db->prefix . 'IRC';
        $tbTxt   = $db->prefix . 'Textes';

        // La query suivante va retourner le nombre de personnage voulu au hasard dont le level est suffisant
        // et dont le temps d'idle est suffisant
        $query = "SELECT Id_Personnages, Nom, Util_id, Level FROM $tbPerso
                  WHERE Id_Personnages IN (SELECT Pers_Id FROM $tbIRC WHERE NOT ISNULL(Pers_Id))
                  AND (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(LastLogin)) > {$this->tempsMinIdleR}
                  AND Level >= {$this->lvlMinimumR} GROUP BY Util_id ORDER BY RAND()
                  LIMIT 0, {$this->nbrParticipants}";

        // Si le nombre de personnage retourn� est inferieur au nombre voulut on ne peut pas commenc� la qu�te
        if ($db->nbLignes($query) != $this->nbrParticipants) {
            return false;
        }

        $queteId = 0;

        // On recupere les infos retourn�es par la query.
        $this->participants[$queteId] = $db->getRows($query);

        // Pour chaque personnage on va recuperer son nick.
        foreach ($this->participants[$queteId] as $i => $perso) {
            $this->participants[$queteId][$i][1] = $irpg->getNickByUID($this->participants[$queteId][$i][2]);
        }

        // On prend un texte de quete au hasard.
        $message = reset(reset($db->getRows("SELECT Valeur FROM $tbTxt WHERE Type='Qs' ORDER BY RAND() LIMIT 0,1")));

        //On �tabli la liste des participants dans une chaine.
        $participants = next($this->listerParticipants($this->participants[$queteId]));
        $irc->privmsg($irc->home, "Qu�te du Survivant ! $participants ont �t� choisis pour $message Le dernier � en "
            . 'revenir sera d�clar� vainqueur...');

        return true;
    }

//////////////////////////////////////////////////////

    function cmdQuestStart($nick)
    {
        global $irpg, $irc, $db;

        if (!$irpg->pause) {
            $uid = $irpg->getUsernameByNick($nick, true);
            if ($irpg->getAdminlvl($uid[1]) >= 5) {
                $proba = rand(1, 100);
                if (!$this->queteSurvivant && ($this->nbrParticipants > 1)
                    && (($this->queteEnCours > 0) || ($proba > 50 + round($this->probaQueteA / 2)))
                ) {
                    $this->queteSurvivant = $this->queteSurvivant();
                    if (!$this->queteSurvivant) {
                        $irc->notice($nick, "D�sol�, il n'y a pas assez de joueurs pour ent�mer une qu�te du "
                            . 'survivant !');
                    }
                } elseif ($this->queteEnCours < 1) {
                    $quete = ($proba > $this->probaQueteA ? $this->queteRoyaume() : $this->queteAventure());
                    $this->queteEnCours = $quete;
                    if ($quete < 1) {
                        $irc->notice($nick, "D�sol�, il n'y a pas assez de joueurs pour d�buter une qu�te "
                            . ($proba > $this->probaQueteA ? 'du Royaume !' : "d'Aventure !"));
                    }
                } else {
                    $irc->notice($nick, 'Il y a d�j� une qu�te en cours !');
                    $this->cmdQuest($nick);
                }
            } else {
                $irc->notice($nick, "D�sol�, vous n'avez pas acc�s � cette commande.");
            }
        } else {
            $irc->notice($nick, "Le jeu est en pause, aucune information n'est disponible.");
        }
    }

////////////////////////////////////////////////////

    function cmdQuest($nick)
    {
        global $irpg, $irc;

        if (!$irpg->pause) {
            if ($this->queteSurvivant) {
                //On �tabli la liste des participants dans une chaine.
                $participants = $this->listerParticipants($this->participants[0]);
                $irc->notice($nick, 'Il y a une qu�te du survivant en cours ! Les participants encore en lice sont '
                    . ": {$participants[1]}.");
            }

            if ($this->tempsQuete && (($this->queteEnCours == 1) || ($this->queteEnCours == 2))) {
                $temps = $irpg->convSecondes($this->tempsQuete);

                //On �tabli la liste des participants dans une chaine.
                $participants = $this->listerParticipants($this->participants[$this->queteEnCours]);

                $irc->notice($nick, 'La qu�te d' . ($this->queteEnCours == 1 ? "'Aventure" : 'e Royaume')
                    . " en cours prendra fin dans {$temps}. Participant" . ($participants[0] > 1 ? 's' : '')
                    . " : {$participants[1]}.");
            } elseif (!$this->queteSurvivant) {
                $irc->notice($nick, "Aucune qu�te n'est en cours actuellement.");
            }
        } else {
            $irc->notice($nick, "Le jeu est en pause, aucune information n'est disponible.");
        }
    }

///////////////////////////////////////////////////////////////

    function verifFinQuete($nick)
    {
        global $irc, $irpg, $db;

        $tbPerso = $db->prefix . 'Personnages';
        $tbIRC   = $db->prefix . 'IRC';

        // On boucle sur chaque participant des quetes et on arrete si on a trouv� que le nick participe a la quete.
        foreach ($this->participants as $queteId => $persos) {
            foreach ($persos as $i => $perso) {
                // Si le nick est participant a la quete et qu'il n'a pas encore abandonn� la quete on lui inflige
                // une penalit� et on l'annonce sur le canal, on l'inscrit dans les logs.
                if (($perso[1] == $nick) && ($perso[0] != -1)) {
                    $pid      = $perso[0];
                    $cnext    = $db->getRows("SELECT Next FROM $tbPerso WHERE Id_Personnages = '$pid'");
                    $penalite = round($cnext[0][0] * rand($this->MinPenalite, $this->MaxPenalite) / 100);
                    $db->req("UPDATE $tbPerso SET Next=Next + $penalite WHERE Id_Personnages= '$pid'");

                    $irc->privmsg($irc->home, $irpg->getNomPersoByPID($pid) . ' rebrousse chemin dans cette qu�te '
                        . 'ardue... Tax� par ses compatriotes de couardise, le voil� blam� !');
                    $irpg->Log($pid, 'QUETE_ABANDONN�E', $penalite, '');

                    $this->participants[$queteId][$i][0] = -1;
                    $queteAbandonnee = true;

                    $participants = $this->listerParticipants($this->participants[$queteId]);
                    //S'il n'y a plus de participant � la qu�te apr�s l'abandon du joueur, on consid�re que la qu�te
                    // est abandonn�e.
                    if ($participants[0] < 1) {
                        if ($queteId == 1) {
                            $irc->privmsg($irc->home, 'La qu�te a echou�e... Les aventuriers sont tous revenus '
                                . 'bredouilles...');
                        } elseif ($queteId == 2) {
                            $penalite = rand($this->MinPenaliteAll, $this->MaxPenaliteAll) / 100;
                            $db->req("UPDATE $tbPerso SET Next=Next + (Next*$penalite) WHERE Id_Personnages
                                IN (SELECT Pers_Id FROM $tbIRC WHERE NOT ISNULL(Pers_Id))");

                            $irc->privmsg($irc->home, 'La qu�te a echou�e... Le royaume est menac� et chaque '
                                . 'habitant en subira les cons�quences...');
                            //TODO : Ajouter le log � tous les joueurs en ligne..
                            //$irpg->Log($pid, 'QUETE_ROYAUME_ECHOU�E', $penalite, '');
                        }
                        $this->queteEnCours = -1;
                    } elseif (($queteId == 0) && ($participants[0] == 1)) {
                        $pid = intval($participants[2][0][0]);
                        $perso = $irpg->getNomPersoByPID($pid);
                        $cnext = $db->getRows("SELECT Next FROM $tbPerso WHERE Id_Personnages = $pid");
                        $recompense = round($cnext[0][0] * $this->recompenseS / 100);
                        $db->req("UPDATE $tbPerso SET Next=Next-$recompense WHERE Id_Personnages = $pid");

                        $irc->privmsg($irc->home, "Nous avons un gagnant dans cette qu�te du Survivant ! $perso "
                            . 'sera largement r�compens� pour sa bravoure !');

                        $this->queteSurvivant = false;
                    }
                }
            }
        }
        return !empty($queteAbandonnee);
    }

///////////////////////////////////////////////////////////////

    function listerParticipants($participants) {
        global $irpg;

        if (!is_array($participants)) {
            return false;
        }

        $listeParticipants = array();
        foreach ($participants as $perso) {
            if (empty($perso[0]) || ($perso[0] == -1)) {
                continue;
            }
            $listeParticipants[] = array($perso, $irpg->getNomPersoByPID($perso[0]));
        }

        if (empty($listeParticipants)) {
            return array(0, '', array());
        }

        $participants = array_map('end', $listeParticipants);
        return array(
            count($listeParticipants),
            implode(' et ', array(implode(', ', array_slice($participants, 0, -1)), end($participants))),
            array_map('reset', $listeParticipants)
        );
    }

///////////////////////////////////////////////////////////////
}
?>
