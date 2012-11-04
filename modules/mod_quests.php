<?php

/*
 * EpiKnet Idle RPG (EIRPG)
 * Copyright (C) 2005-2012 Francis D (Homer), Womby & EpiKnet
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
*/
class quests
{
//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**
  var $name;        //Nom du module
  var $version;     //Version du module
  var $desc;        //Description du module
  var $depend;      //Modules dont nous sommes d�pendants

  //Variables suppl�mentaires
  var $queteEnCours = -1;  	//-1:Pas de quete en cours; 1: Quete d'aventure en cours; 2: quete de Royaume en cours
  var $participants;
  var $listeParticipants;
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
    $this->name = "mod_quests";
    $this->version = "0.1.0";
    $this->desc = "Quetes";
    $this->depend = array("core/0.5.0");

    //Recherche de d�pendances
    if (!$irpg->checkDepd($this->depend)) {
      die("$this->name: d�pendance non r�solue\n");
    }

        //Validation du fichier de configuration sp�cifique au module
    $cfgKeys = array(
      "tempsQueteA", "tempsQueteR", "recompenseA", "recompenseR", "recompenseS", "MinPenalite", "MaxPenalite",
      "MinPenaliteAll", "MaxPenaliteAll", "nbrParticipants", "tempsMinIdleA", "tempsMinIdleR","tempsMinIdleS",
      "lvlMinimumA", "lvlMinimumR","lvlMinimumS","probaAllQuete","probaQueteA",
    );
    $cfgKeysOpt = array("");

    if (!$irpg->validationConfig($this->name, $cfgKeys, $cfgKeysOpt)) {
      die ($this->name.": V�rifiez votre fichier de configuration.\n");
    }

    //Initialisation des param�tres du fichier de configuration
    $this->tempsQueteA = $irpg->readConfig($this->name, "tempsQueteA");
    $this->tempsQueteR = $irpg->readConfig($this->name, "tempsQueteR");
    $this->recompenseA = $irpg->readConfig($this->name, "recompenseA");
    $this->recompenseR = $irpg->readConfig($this->name, "recompenseR");
    $this->recompenseS = $irpg->readConfig($this->name, "recompenseS");
    $this->MinPenalite = $irpg->readConfig($this->name, "MinPenalite");
    $this->MaxPenalite = $irpg->readConfig($this->name, "MaxPenalite");
    $this->MinPenaliteAll = $irpg->readConfig($this->name, "MinPenaliteAll");
    $this->MaxPenaliteAll = $irpg->readConfig($this->name, "MaxPenaliteAll");
	$this->nbrParticipants = $irpg->readConfig($this->name, "nbrParticipants");
	$this->tempsMinIdleA = $irpg->readConfig($this->name, "tempsMinIdleA");
    $this->tempsMinIdleR = $irpg->readConfig($this->name, "tempsMinIdleR");
    $this->tempsMinIdleS = $irpg->readConfig($this->name, "tempsMinIdleS");
    $this->lvlMinimumA = $irpg->readConfig($this->name, "lvlMinimumA");
	$this->lvlMinimumR = $irpg->readConfig($this->name, "lvlMinimumR");
	$this->lvlMinimumS = $irpg->readConfig($this->name, "lvlMinimumS");
	$this->probaAllQuete = $irpg->readConfig($this->name, "probaAllQuete");
    $this->probaQueteA = $irpg->readConfig($this->name, "probaQueteA");
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

    													// SI il n'y a aucune quete en cours on ne fait rien du tout (Optimisation).
   if((($this->queteEnCours != -1) || $this->queteSurvivant) && !$irpg->pause ) {
    													// On lit le fichier de config pour verifier que l'action est consid�r�e comme p�nalit�.
		if($irpg->readConfig("mod_penalites","penPrivmsg") != 0) {
														// On verifie que le nick participe a la quete.
                                                        // Si c'est le cas on verifie que la quete est abandonn�e ou non.
														// Si elle est abandonn�e on met a jour le variable de QueteEnCours.
			if($this->VerifFinQuete($nick) == -1) {
				$this->queteEnCours = -1;
            }
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
    global $irc, $irpg, $db;

   if((($this->queteEnCours != -1) || $this->queteSurvivant) && !$irpg->pause ) {
		if(readConfig("mod_penalites","penNotice") != 0) {
			if($this->VerifFinQuete($nick) == -1) {
				$this->queteEnCours = -1;
            }
        }
    }
  }

///////////////////////////////////////////////////////////////

  function onNoticePrive($nick, $user, $host, $message)
  {
    global $irc, $irpg, $db;

   if((($this->queteEnCours != -1) || $this->queteSurvivant) && !$irpg->pause ) {
			if($irpg->readConfig("mod_penalites","penNotice") != 0) {
				if($this->VerifFinQuete($nick) == -1) {
					$this->queteEnCours = -1;
                }
            }
    }
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

   if((($this->queteEnCours != -1) || $this->queteSurvivant) && !$irpg->pause ) {
		if($irpg->readConfig("mod_penalites","penPart") != 0) {
			if($this->VerifFinQuete($nick) == -1) {
				$this->queteEnCours = -1;
            }
        }
    }
	}

///////////////////////////////////////////////////////////////

	function onNick($nick, $user, $host, $newnick)
	{
		global $irc, $irpg, $db;

		if(($this->queteEnCours != -1) || $this->queteSurvivant) {
													// On verifie que le nick changeant participe a la quete et si c'est le cas
													// on met a jour les informations concernant ce participant de le tableau des participants � la quete.
			for($i=0;$i<$this->nbrParticipants;$i++) {
				if($this->participants[$i][1] == $nick) {
					$this->participants[$i][1] = $newnick;
                }
            }
			if(!$irpg->pause) {
				if($irpg->readConfig("mod_penalites","penNick") != 0) {
					if($this->VerifFinQuete($nick) == -1) {
						$this->queteEnCours = -1;
                    }
                }
			}
  		}
	}

///////////////////////////////////////////////////////////////

  function onKick($nick, $user, $host, $channel, $nickkicked)
  {
   global $irc, $irpg, $db;

   if((($this->queteEnCours != -1) || $this->queteSurvivant) && !$irpg->pause ) {
		if($irpg->readConfig("mod_penalites","penKick") != 0) {
			if($this->VerifFinQuete($nickkicked) == -1) {
				$this->queteEnCours = -1;
            }
        }
    }
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

   if((($this->queteEnCours != -1) || $this->queteSurvivant) && !$irpg->pause ) {
	if($irpg->readConfig("mod_penalites","penQuit") != 0) {
			if($this->VerifFinQuete($nick) == -1) {
				$this->queteEnCours = -1;
            }
        }
    }
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

		$listeFinale = "";
		$tbPerso = $db->prefix."Personnages";
		$tbIRC = $db->prefix."IRC";

														//Si il n'y a aucune quete en Cours, on a une chance sur 500 d'un mettre une en route.
														//Si une quete est mise en route il y a 80% de chance que ce soit une quete d'aventure
                                                        //et 20% une quete de Royaume.
		if(!$irpg->pause) {
			if(($this->queteEnCours == -1) && ($this->queteSurvivant == 0)) {
				if(rand(1,$this->probaAllQuete) == 1) {
					$proba = rand(1,100);
					if ($proba <= $this->probaQueteA) {
						$this->queteEnCours = $this->QueteAventure();
					} elseif($proba <= ($this->probaQueteA + round((100 - $this->probaQueteA)/2))) {
						$this->queteEnCours = $this->QueteRoyaume();
					} else {
						if($this->nbrParticipants > 1 || !$this->queteSurvivant) {
							$this->queteSurvivant = $this->QueteSurvivant();
						} else {
							$this->queteEnCours = $this->QueteRoyaume();
                        }
                    }
				}
			} elseif($this->queteEnCours == 1) {
															// Si il y a une quete en cours, on verifie le temps de quete restant. Si il est superieur � 0
                                                            // on enl�ve 15 secondes
															// Sinon on donne une recompense � tout les personnages qui ont particip� jusqu'au bout
                                                            // (uid different de -1)
				if($this->tempsQuete > 0) {
					$this->tempsQuete = $this->tempsQuete - 15;
				} else {
					for($i=0;$i<$this->nbrParticipants;$i++) {
						if($this->participants[$i][0] != -1) {
							$pid = $this->participants[$i][0];
							$cnext = $db->getRows("SELECT Next FROM $tbPerso WHERE Id_Personnages = '$pid'");
							//$cnext = $cnext[0][0]; //Optimisation
							$recompense = round($cnext[0][0]*($this->recompenseA / 100));
							$db->req("UPDATE $tbPerso SET Next=Next-$recompense WHERE Id_Personnages = '$pid'");
							$listeParticipants .= $irpg->getNomPersoByPID($this->participants[$i][0]).", ";
						}
					}
					$listeParticipants = substr($listeParticipants,0,strlen($listeParticipants)-2);
					if(substr_count($listeParticipants,',') != 0) {
						$irc->privmsg($irc->home, $listeParticipants." sont revenus de leur qu�te et ont remplis "
                          . "l'objectif ! Bravo, voil�votre r�compense : ".$this->recompenseA
                          . "% de votre TTL sont enlev�s !");
					} else {
						$irc->privmsg($irc->home, $listeParticipants." est revenu de sa qu�te et a rempli "
                          . "l'objectif ! Bravo, voil�ta r�compense : ".$this->recompenseA
                          . "% de ton TTL est enlev�s !");
                    }
					$this->queteEnCours = -1;
				}
			} elseif($this->queteEnCours == 2) {
				if($this->tempsQuete > 0) {
					$this->tempsQuete = $this->tempsQuete - 15;
				} else {
					for($i=0;$i<$this->nbrParticipants;$i++) {
						if($this->participants[$i][0] != -1) {
							$pid = $this->participants[$i][0];
							$cnext = $db->getRows("SELECT Next FROM $tbPerso WHERE Id_Personnages = '$pid'");
							$recompense = round($cnext[0][0]*($this->recompenseR / 100));
							$db->req("UPDATE $tbPerso SET Next=Next-$recompense WHERE Id_Personnages = '$pid'");
							$listeParticipants .= $irpg->getNomPersoByPID($this->participants[$i][0]).", ";
						}
					}
					$listeParticipants = substr($listeParticipants,0,strlen($listeParticipants)-2);
					if(substr_count($listeParticipants,',') != 0) {
						$irc->privmsg($irc->home, $listeParticipants." sont revenus de qu�te � temps. Le royaume "
                          . "est sauv�...  Ils seront largement r�compens�s. ".$this->recompenseR
                          . "% de leur TTL sont enlev�s !");
					} else {
						$irc->privmsg($irc->home, $listeParticipants." est revenu de qu�te � temps. Le royaume "
                          . "est sauv�...  Il sera largement r�compens�. ".$this->recompenseR
                          . "% de son TTL est enlev� !");
                    }
					$this->queteEnCours = -1;
				}
			}
		}
	}

///////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////

  function QueteAventure()
  {
		global $irpg, $irc, $db;

		$tbPerso = $db->prefix . "Personnages";
		$tbIRC = $db->prefix . "IRC";
		$tbTxt = $db->prefix . "Textes";

														// La query suivante va retourner le nombre de personnage voulu au hasard dont le level est suffisant
														// et dont le temps d'idle est suffisant
														// Le group by sur Util_id permet de ne recuperer qu'un personnage par user.
		$query = "SELECT Id_Personnages, Nom, Util_id, Level FROM $tbPerso WHERE Id_Personnages
                  IN (SELECT Pers_Id FROM $tbIRC WHERE NOT ISNULL(Pers_Id))
                  AND (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(LastLogin))>".$this->tempsMinIdleA."
                  AND Level >=".$this->lvlMinimumA." GROUP BY Util_id ORDER BY RAND()
                  LIMIT 0,".$this->nbrParticipants;

														// Si le nombre de personnage retourn� est inferieur au nombre voulut on ne peut pas commenc� la qu�te
		if ($db->nbLignes($query) != $this->nbrParticipants) {
          return -1;
        }

														// On recupere les infos retourn�es par la query.
		$this->participants = $db->getRows($query);

														// Pour chaque personnage on va recuperer son nick et etablir la liste des participants dans une chaine.
		for($i=0;$i<$this->nbrParticipants;$i++) {
			$this->participants[$i][1] = $irpg->getNickByUID($this->participants[$i][2]);
			$listeParticipants .= $irpg->getNomPersoByPID($this->participants[$i][0]).", ";
		}
		$listeParticipants = substr($listeParticipants,0,strlen($listeParticipants)-2);
														// On prend un texte de quete au hasard.
		$message = $db->getRows("SELECT Valeur FROM $tbTxt WHERE Type='Qa' ORDER BY RAND() LIMIT 0,1");

														// le temps de quete est etablit selon le temps de quete desir� plus un temps au hasard entre 1
                                                        // et ce temps de quete desir�.
														// On peut donc passer du simple au double au hasard.
		$this->tempsQuete = $this->tempsQueteA + rand(1,$this->tempsQueteA);
		if(substr_count($listeParticipants,',') != 0) {
			$irc->privmsg($irc->home, $listeParticipants." ont �t� choisit pour ".$message[0][0].". Ils ont "
              . $irpg->convSecondes($this->tempsQuete)." pour en revenir...");
		} else {
			$irc->privmsg($irc->home, $listeParticipants." a �t� choisit pour ".$message[0][0].". Il a "
              . $irpg->convSecondes($this->tempsQuete)." pour en revenir...");
        }
		return 1;
  }

///////////////////////////////////////////////////////////////

	function QueteRoyaume()
	{
		global $irpg, $irc, $db;

		$tbPerso = $db->prefix . "Personnages";
		$tbIRC = $db->prefix . "IRC";
		$tbTxt = $db->prefix . "Textes";
														// La query suivante va retourner le nombre de personnage voulu au hasard dont le level est suffisant
														// et dont le temps d'idle est suffisant
		$query = "SELECT Id_Personnages, Nom, Util_id, Level FROM $tbPerso WHERE Id_Personnages
                  IN (SELECT Pers_Id FROM $tbIRC WHERE NOT ISNULL(Pers_Id))
                  AND (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(LastLogin))>".$this->tempsMinIdleR."
                  AND Level >=".$this->lvlMinimumR." GROUP BY Util_id ORDER BY RAND()
                  LIMIT 0,".$this->nbrParticipants;

														// Si le nombre de personnage retourn� est inferieur au nombre voulut on ne peut pas commenc� la qu�te
		if ($db->nbLignes($query) != $this->nbrParticipants) {
          return -1;
        }

														// On recupere les infos retourn�es par la query.
		$this->participants = $db->getRows($query);

														// Pour chaque personnage on va recuperer son nick et etablir la liste des participants dans une chaine.
		for($i=0;$i<$this->nbrParticipants;$i++) {
			$this->participants[$i][1] = $irpg->getNickByUID($this->participants[$i][2]);
			$listeParticipants .= $irpg->getNomPersoByPID($this->participants[$i][0]).", ";
		}
		$listeParticipants = substr($listeParticipants,0,strlen($listeParticipants)-2);
												// On prend un texte de quete au hasard.
		$message = $db->getRows("SELECT Valeur FROM $tbTxt WHERE Type='Qr' ORDER BY RAND() LIMIT 0,1");

														// le temps de quete est etablit selon le temps de quete desir� plus un temps au hasard entre 1
                                                        // et ce temps de quete desir�.
														// On peut donc passer du simple au double au hasard.
		$this->tempsQuete = $this->tempsQueteR + rand(1,$this->tempsQueteR);
		if(substr_count($listeParticipants,',') != 0) {
			$irc->privmsg($irc->home, "Qu�te de Royaume ! ".$listeParticipants." ont �t� choisit pour "
              . $message[0][0].". Ils ont ".$irpg->convSecondes($this->tempsQuete)." pour en revenir...");
		} else {
			$irc->privmsg($irc->home, "Qu�te de Royaume ! ".$listeParticipants." a �t� choisit pour "
              . $message[0][0].". Il a ".$irpg->convSecondes($this->tempsQuete)." pour en revenir...");
        }
		return 2;
	}

///////////////////////////////////////////////////////////////

	function QueteSurvivant()
	{
		global $irpg, $irc, $db;

		$tbPerso = $db->prefix . "Personnages";
		$tbIRC = $db->prefix . "IRC";
		$tbTxt = $db->prefix . "Textes";
														// La query suivante va retourner le nombre de personnage voulu au hasard dont le level est suffisant
														// et dont le temps d'idle est suffisant
		$query = "SELECT Id_Personnages, Nom, Util_id, Level FROM $tbPerso WHERE Id_Personnages
                  IN (SELECT Pers_Id FROM $tbIRC WHERE NOT ISNULL(Pers_Id))
                  AND (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(LastLogin))>".$this->tempsMinIdleR."
                  AND Level >=".$this->lvlMinimumR." GROUP BY Util_id ORDER BY RAND()
                  LIMIT 0,".$this->nbrParticipants;

														// Si le nombre de personnage retourn� est inferieur au nombre voulut on ne peut pas commenc� la qu�te
		if ($db->nbLignes($query) != $this->nbrParticipants) {
          return false;
        }

														// On recupere les infos retourn�es par la query.
		$this->participants = $db->getRows($query);

														// Pour chaque personnage on va recuperer son nick et etablir la liste des participants dans une chaine.
		for($i=0;$i<$this->nbrParticipants;$i++) {
			$this->participants[$i][1] = $irpg->getNickByUID($this->participants[$i][2]);
			$listeParticipants .= $irpg->getNomPersoByPID($this->participants[$i][0]).", ";
		}
		$listeParticipants = substr($listeParticipants,0,strlen($listeParticipants)-2);
												// On prend un texte de quete au hasard.
		$message = $db->getRows("SELECT Valeur FROM $tbTxt WHERE Type='Qs' ORDER BY RAND() LIMIT 0,1");
		$irc->privmsg($irc->home, "Qu�te du Survivant ! ".$listeParticipants." ont �t� choisit pour "
          . $message[0][0].". Le dernier � en revenir sera d�clar� vainqueur...");
		return true;
	}

//////////////////////////////////////////////////////

	function cmdQuestStart($nick)
	{
		global $irpg, $irc, $db;
		if(!$irpg->pause) {
			$uid = $irpg->getUsernameByNick($nick,true);
			if($irpg->getAdminlvl($uid[1]) >= 5) {
				if($this->queteEnCours != -1) {
					$irc->notice($nick,"Il y a d�j� une qu�te en cours !");
					$this->cmdQuest($nick);
				} else {
					$proba = rand(1,100);
					if ($proba <= $this->probaQueteA) {
						$this->queteEnCours = $this->QueteAventure();
					} elseif($proba <= ($this->probaQueteA + round((100 -$this->probaQueteA)/2) ) ) {
						$this->queteEnCours = $this->QueteRoyaume();
					} else {
						if(($this->nbrParticipants > 1) || !$this->queteSurvivant) {
							$this->queteSurvivant = $this->QueteSurvivant();
						} else {
							$this->queteEnCours = $this->QueteRoyaume();
                        }
                    }
				}
			} else {
			          $irc->notice($nick, "D�sol�, vous n'avez pas acc�s � cette commande.");
            }
		} else {
			$irc->notice($nick, "Le jeu est en pause aucune information n'est disponible.");
        }
	}

////////////////////////////////////////////////////

	function cmdQuest($nick)
	{
		global $irpg, $irc;
		if(!$irpg->pause) {
			if($this->queteSurvivant) {
				for($i=0;$i<$this->nbrParticipants;$i++) {
					if($this->participants[$i][0] != -1) {
						$listeParticipants .= $irpg->getNomPersoByPID($this->participants[$i][0]).", ";
                    }
                }
					$message = "Il y a une qu�te du survivant qui est en cours ! Les participants encore en lice "
                             . "sont: ".$listeParticipants;
					$irc->notice($nick, $message);
			} else {
			if(($this->tempsQuete <= 0) || ($this->queteEnCours == -1)) {
					$irc->notice($nick, "Aucune qu�te en cours actuellement.");
				} else {
					for($i=0;$i<$this->nbrParticipants;$i++) {
						if($this->participants[$i][0] != -1) {
							$listeParticipants .= $irpg->getNomPersoByPID($this->participants[$i][0]).", ";
                        }
                    }

					$listeParticipants = substr($listeParticipants,0,strlen($listeParticipants)-2);
					if($this->queteEnCours == 1) {
						$message = "La qu�te d'Aventure en cours prendra fin dans "
                                 . $irpg->convSecondes($this->tempsQuete).". Participant(s): "
                                 . $listeParticipants;
					} elseif($this->queteEnCours == 2) {
						$message = "La qu�te de Royaume en cours prendra fin dans "
                                 . $irpg->convSecondes($this->tempsQuete).". Participant(s): "
                                 . $listeParticipants;
					} else {
						$message = "C'est une qu�te du survivant qui est en cours ! Les participants encore "
                                 . "en lice sont: ".$listeParticipants;
                    }

					$irc->notice($nick, $message);
				}
			}
		} else {
			$irc->notice($nick, "Le jeu est en pause aucune information n'est disponible");
        }
	}

///////////////////////////////////////////////////////////////

	function VerifFinQuete($nick)
	{
     	global $irc, $irpg, $db;

	  	$tbPerso = $db->prefix."Personnages";
	  	$tbIRC = $db->prefix."IRC";

        // Permet de stopper la boucle for des que l'on sait que le nick est participant a la quete. (Optimisation)
		$nickIsParticipant = false;
														// Permet aussi de ne pas verifier si la quete a ete abandonn�e par tout les participant
                                                        // si le nick n'est pas un participant.
														// On boucle sur chaque participant de la quete et on arrete si on a trouv� que le nick participe a la quete.
			for($i=0;($i<$this->nbrParticipants) && !$nickIsParticipant;$i++) {
														// Si le nick est participant a la quete et qu'il n'a pas encore abandonn� la quete
                                                        // on lui inflige une penalit� et
														// on l'annonce sur le canal, on l'inscrit dans les logs et on met la variable nickIsParticipant � vrai.
				if(($this->participants[$i][1] == $nick) && ($this->participants[$i][0] != -1)) {
					$pid = $this->participants[$i][0];
					$cnext = $db->getRows("SELECT Next FROM $tbPerso WHERE Id_Personnages = '$pid'");
					$penalite = round($cnext[0][0]*(rand($this->MinPenalite,$this->MaxPenalite) / 100));
					$db->req("UPDATE $tbPerso SET Next=Next + $penalite WHERE Id_Personnages= '$pid'");
					$irc->privmsg($irc->home, $irpg->getNomPersoByPID($this->participants[$i][0])
                      . " rebrousse chemin dans cette qu�te ardue...Tax� par ses compatriotes de couardise le voil� "
                      . "blam�!");
					$this->participants[$i][0] = -1;

          		$irpg->Log($pid, "QUETE_ABANDONN�E", $penalite, "");
          		$nickIsParticipant = true;
				}
            }

			// Si le nick ayant pris une penalit� participe � la quete on test si avec son abandon la quete est abandonn�e
													// Si il ne fait pas partie de la quete on saute toute cette partie. (Optimisation)
			if($nickIsParticipant) {
				$queteAbandonnee = true;		// On considere que la quete est abandonn�e.

													// Si on trouve un des participants qui n'a pas abandonn�e on mettra la variable � zero
				$participantsActif = 0;
				for($i=0;$i<$this->nbrParticipants;$i++) {
					if($this->participants[$i][0] != -1) {
						$queteAbandonnee = false;
						$participantsActif++;//ParticipantsActif servira dans le cas d'une quete du Survivant
					}
                }

				if($this->queteSurvivant && ($participantsActif == 1)) {
					for($i=0;$i<$this->nbrParticipants;$i++) {
						if($this->participants[$i][0] != -1) {
							$gagnant = $irpg->getNomPersoByPID($this->participants[$i][0]);
							$gagnantPID = $this->participants[$i][0];
							$i = $this->nbrParticipants;
						}
                    }
					$irc->privmsg($irc->home, "Nous avons un gagnant dans cette quete du Survivant ! "
                      . $gagnant." sera largement r�compens� pour sa bravoure !");
					$cnext = $db->getRows("SELECT Next FROM $tbPerso WHERE Id_Personnages = ".$gagnantPID);
					$recompense = round($cnext[0][0]*($this->recompenseS / 100));
					$db->req("UPDATE $tbPerso SET Next=Next-$recompense WHERE Id_Personnages = ".$gagnantPID);
					$this->queteSurvivant = false;
					return $this->queteEnCours;
				}

	// Si la quete est abandonn�e on ecrit sur le canal si c'est une quete d'Aventure.
													// Si c'est une quete de royaume on applique une penalit� a tout les connect�s.
				if($queteAbandonnee) {
					if($this->queteEnCours == 1) {
						$irc->privmsg($irc->home, "La qu�te a echou�e... Les aventuriers sont tous revenus "
                          . "bredouilles...");
					} elseif($this->queteEnCours == 2) {
						$penaliteAll = rand($this->MinPenaliteAll,$this->MaxPenaliteAll) / 100;

          //TODO : Ajouter le log � tous les joueurs en ligne..
          //je pourrais le faire maintenant, mais �a ne me tente pas :P
          //$irpg->Log($pid, "QUETE_ROYAUME_ECHOU�E", $penalite, "");

						$db->req("UPDATE $tbPerso SET Next=Next + (Next*$penaliteAll) WHERE Id_Personnages
                                  IN (SELECT Pers_Id FROM $tbIRC WHERE NOT ISNULL(Pers_Id))");
						$irc->privmsg($irc->home, "La qu�te a echou�e... Le royaume est menac� et chaque habitant en "
                          . "subira les cons�quences...");
					}
					return -1;
				}
			}
			return $this->queteEnCours;
	}

///////////////////////////////////////////////////////////////
}
?>
