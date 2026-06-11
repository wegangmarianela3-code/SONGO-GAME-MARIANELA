<?php
// Auteur: ONANA GREGOIRE LEGRAND
// Matricule: 24G2060
// Projet Songo AJAX Backend

header('Content-Type: application/json');

// ATTENTION POUR LINUX (XAMPP) : il faut donner les droits d'ecriture au fichier state.json !
// commande a taper dans le terminal: chmod 777 state.json
$fichier_etat = 'state.json';

// Si c'est la premiere fois on cree la partie
if (!file_exists($fichier_etat) || (isset($_GET['action']) && $_GET['action'] == 'reset')) {
    $etat_initial = array(
        "le_plateau" => array(5,5,5,5,5,5,5, 5,5,5,5,5,5,5),
        "les_scores" => array(0, 0),
        "joueur_qui_joue" => 0, // 0 = j1(bas), 1 = j2(haut)
        "le_gagnant" => null
    );
    
    file_put_contents($fichier_etat, json_encode($etat_initial));
    
    if (isset($_GET['action']) && $_GET['action'] == 'reset') {
        echo json_encode(array("statut" => "ok reset fait"));
        exit;
    }
}

// on lit le fichier
$json_contenu = file_get_contents($fichier_etat);
$etat_jeu = json_decode($json_contenu, true);

// on envoie juste l'etat au JS
if (isset($_GET['action']) && $_GET['action'] == 'recuperer_etat') {
    echo json_encode($etat_jeu);
    exit;
}

// quand un joueur clique sur un trou
if (isset($_GET['action']) && $_GET['action'] == 'jouer') {
    
    $index_trou = intval($_GET['trou']);
    $id_joueur = intval($_GET['joueur']);

    // verifications
    if ($etat_jeu['le_gagnant'] !== null) {
        echo json_encode(array("erreur" => "Le jeu est fini !")); 
        exit;
    }

    if ($id_joueur != $etat_jeu['joueur_qui_joue']) {
        echo json_encode(array("erreur" => "Doucement, ce n'est pas ton tour !")); 
        exit;
    }

    if ($etat_jeu['le_plateau'][$index_trou] == 0) {
        echo json_encode(array("erreur" => "Tu as cliqué sur un trou vide.")); 
        exit;
    }

    if ($id_joueur == 0 && ($index_trou < 0 || $index_trou > 6)) {
        echo json_encode(array("erreur" => "Triche ! Joue dans ton camp.")); 
        exit;
    }
    if ($id_joueur == 1 && ($index_trou < 7 || $index_trou > 13)) {
        echo json_encode(array("erreur" => "Triche ! Joue dans ton camp.")); 
        exit;
    }

    // recuperation des donnees
    $plateau = $etat_jeu['le_plateau'];
    $scores = $etat_jeu['les_scores'];

    // map du trou suivant 
    $trou_suivant = array(7, 0, 1, 2, 3, 4, 5, 8, 9, 10, 11, 12, 13, 6);
    $trou_precedent = array(1, 2, 3, 4, 5, 6, 13, 0, 7, 8, 9, 10, 11, 12);

    $graines = $plateau[$index_trou];
    $plateau[$index_trou] = 0;
    
    $position = $index_trou;

    // boucle de semaille
    while ($graines > 0) {
        $position = $trou_suivant[$position];
        
        if ($position == $index_trou) {
            // on evite la case depart
            continue;
        }
        
        $plateau[$position] = $plateau[$position] + 1;
        $graines = $graines - 1;
    }

    // verification si on est chez l'adversaire
    $chez_adversaire = false;
    if ($id_joueur == 0 && $position >= 7 && $position <= 13) {
        $chez_adversaire = true;
    } else if ($id_joueur == 1 && $position >= 0 && $position <= 6) {
        $chez_adversaire = true;
    }

    $trou_interdit = 0;
    if($id_joueur == 0) $trou_interdit = 7;
    else $trou_interdit = 0;

    // Prises
    if ($chez_adversaire == true) {
        $pos_capture = $position;
        $capture_totale = 0;

        while(true) {
            // on verifie si on est tjrs chez ladversaire
            $encore_chez_adv = false;
            if ($id_joueur == 0 && $pos_capture >= 7 && $pos_capture <= 13) $encore_chez_adv = true;
            if ($id_joueur == 1 && $pos_capture >= 0 && $pos_capture <= 6) $encore_chez_adv = true;
            
            if($encore_chez_adv == false) {
                break;
            }

            $nb_g = $plateau[$pos_capture];
            if ($nb_g == 2 || $nb_g == 3 || $nb_g == 4) {
                if ($pos_capture == $trou_interdit && $capture_totale == 0) {
                    // on prend pas la 1ere case adverse (c'est la regle)
                    break;
                } else {
                    $capture_totale += $nb_g;
                    $plateau[$pos_capture] = 0;
                }
            } else {
                break; // fin de la chaine
            }
            
            $pos_capture = $trou_precedent[$pos_capture];
        }
        
        $scores[$id_joueur] = $scores[$id_joueur] + $capture_totale;
    }

    // Condition de fin de partie
    $graines_restantes = 0;
    for($x=0; $x<14; $x++) {
        $graines_restantes += $plateau[$x];
    }
    
    if ($graines_restantes < 10 || $scores[0] >= 40 || $scores[1] >= 40) {
        // on ramasse
        for($i=0; $i<=6; $i++) { $scores[0] += $plateau[$i]; $plateau[$i] = 0; }
        for($i=7; $i<=13; $i++) { $scores[1] += $plateau[$i]; $plateau[$i] = 0; }
        
        if($scores[0] > $scores[1]) $etat_jeu['le_gagnant'] = 0;
        else if($scores[1] > $scores[0]) $etat_jeu['le_gagnant'] = 1;
        else $etat_jeu['le_gagnant'] = -1; // -1 = egalité
    }

    // on met a jour l'etat
    $etat_jeu['le_plateau'] = $plateau;
    $etat_jeu['les_scores'] = $scores;
    
    // on change de tour si c pas fini
    if ($etat_jeu['le_gagnant'] === null) {
        if($id_joueur == 0) {
            $etat_jeu['joueur_qui_joue'] = 1;
        } else {
            $etat_jeu['joueur_qui_joue'] = 0;
        }
    }

    // on sauvegarde le fichier (sur linux il faut bien faire attention aux permissions !)
    file_put_contents($fichier_etat, json_encode($etat_jeu));
    
    echo json_encode($etat_jeu);
    exit;
}
?>
