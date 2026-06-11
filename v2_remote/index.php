<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Songo - Multi Joueur (AJAX)</title>
    <!-- Auteur: KAMDEM WEGANG MARIANELA | Matricule: 24G2126 -->
    <style>
        body {
            background-color: #e0ffff;
            text-align: center;
            font-family: sans-serif;
        }
        h1 { color: #008080; }
        
        .le_plateau {
            background: #20b2aa;
            width: 700px;
            margin: 0 auto;
            padding: 15px;
            border-radius: 10px;
        }
        
        .ligne {
            display: flex;
            justify-content: space-around;
            margin-bottom: 15px;
        }
        
        .trou {
            width: 50px;
            height: 50px;
            background: #e0ffff;
            border-radius: 50%;
            border: 2px solid teal;
            text-align: center;
            line-height: 50px;
            font-size: 20px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .trou:hover { background: #b0e0e6; }
        
        .bloque { opacity: 0.5; cursor: not-allowed; }
        
        .affichage_scores { margin-top: 30px; font-size: 20px; }
        
        #message_erreur { color: red; font-weight: bold; margin-bottom: 10px;}
        
        .bouton_choix {
            padding: 15px 30px;
            font-size: 18px;
            background-color: teal;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 10px;
        }
    </style>
</head>
<body>

    <h1>Le jeu de Songo - AJAX Multi</h1>
    
    <div id="choix_joueur">
        <h2>Qui es-tu ?</h2>
        <button class="bouton_choix" onclick="choisirJoueur(0)">Je suis Joueur 1 (Bas)</button>
        <button class="bouton_choix" onclick="choisirJoueur(1)">Je suis Joueur 2 (Haut)</button>
    </div>

    <div id="zone_jeu" style="display:none;">
        <h3>Mon rôle : <span id="mon_role"></span></h3>
        <h2 id="info_tour">Chargement depuis le serveur (PHP)...</h2>
        
        <div id="message_erreur"></div>

        <div class="le_plateau">
            <div class="ligne" id="ligne_haut"></div>
            <div class="ligne" id="ligne_bas"></div>
        </div>
        
        <div class="affichage_scores">
            <p>Score Joueur 2 (Haut) : <span id="score_j2">0</span></p>
            <p>Score Joueur 1 (Bas) : <span id="score_j1">0</span></p>
        </div>

        <br>
        <button onclick="recommencer()">Reset de la partie</button>
    </div>

    <br><br>
    <p>Par: KAMDEM WEGANG MARIANELA (24G2126)</p>
    <p><em>Note pour le deploiement XAMPP Linux: Penser a faire un `chmod 777 state.json` dans le dossier.</em></p>

    <script>
        // Auteur: KAMDEM WEGANG MARIANELA | Matricule: 24G2126
        
        var mon_id_joueur = -1; // pas encore choisi

        function choisirJoueur(id) {
            mon_id_joueur = id;
            document.getElementById('choix_joueur').style.display = 'none';
            document.getElementById('zone_jeu').style.display = 'block';
            
            if(id == 0) {
                document.getElementById('mon_role').innerHTML = "Joueur 1 (En Bas)";
            } else {
                document.getElementById('mon_role').innerHTML = "Joueur 2 (En Haut)";
            }
            
            // on lance la requete ajax en boucle (Polling toutes les secondes)
            setInterval(demanderEtatAuServeur, 1000);
            demanderEtatAuServeur();
        }

        function recommencer() {
            var requete = new XMLHttpRequest();
            requete.open("GET", "server.php?action=reset", true);
            requete.onload = function() {
                demanderEtatAuServeur();
            };
            requete.send();
        }

        function cliquerTrou(index_trou) {
            var requete = new XMLHttpRequest();
            // on envoie notre coup au php
            requete.open("GET", "server.php?action=jouer&trou=" + index_trou + "&joueur=" + mon_id_joueur, true);
            requete.onload = function() {
                var reponse = JSON.parse(requete.responseText);
                if(reponse.erreur) {
                    document.getElementById('message_erreur').innerHTML = reponse.erreur;
                    // efface l'erreur apres 2 secondes
                    setTimeout(function() {
                        document.getElementById('message_erreur').innerHTML = "";
                    }, 2000);
                } else {
                    mettreAjourAffichage(reponse);
                }
            };
            requete.send();
        }

        function demanderEtatAuServeur() {
            // on utilise XMLHttpRequest (AJAX classique pour faire "etudiant") plutot que fetch
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "server.php?action=recuperer_etat", true);
            xhr.onload = function() {
                if(xhr.status == 200) {
                    var donnees = JSON.parse(xhr.responseText);
                    mettreAjourAffichage(donnees);
                }
            };
            xhr.send();
        }

        function mettreAjourAffichage(etat) {
            var div_haut = document.getElementById('ligne_haut');
            var div_bas = document.getElementById('ligne_bas');
            
            div_haut.innerHTML = "";
            div_bas.innerHTML = "";

            var c_est_mon_tour = false;
            if(etat.joueur_qui_joue == mon_id_joueur) {
                c_est_mon_tour = true;
            }

            // Affichage J2 (Haut) a l'envers (13 à 7)
            for(var i=13; i>=7; i--) {
                var div_trou = document.createElement("div");
                div_trou.className = "trou";
                
                if(c_est_mon_tour == false || mon_id_joueur != 1) {
                    div_trou.className += " bloque";
                }
                
                div_trou.innerHTML = etat.le_plateau[i];
                
                (function(index) {
                    div_trou.onclick = function() { 
                        if(c_est_mon_tour && mon_id_joueur == 1) cliquerTrou(index); 
                    }
                })(i);
                
                div_haut.appendChild(div_trou);
            }

            // Affichage J1 (Bas) de 0 à 6
            for(var i=0; i<=6; i++) {
                var div_trou = document.createElement("div");
                div_trou.className = "trou";
                
                if(c_est_mon_tour == false || mon_id_joueur != 0) {
                    div_trou.className += " bloque";
                }
                
                div_trou.innerHTML = etat.le_plateau[i];
                
                (function(index) {
                    div_trou.onclick = function() { 
                        if(c_est_mon_tour && mon_id_joueur == 0) cliquerTrou(index); 
                    }
                })(i);
                
                div_bas.appendChild(div_trou);
            }

            // on met les scores
            document.getElementById('score_j1').innerHTML = etat.les_scores[0];
            document.getElementById('score_j2').innerHTML = etat.les_scores[1];
            
            // on met le tour
            if(etat.le_gagnant !== null) {
                if(etat.le_gagnant == 0) document.getElementById('info_tour').innerHTML = "FIN ! Le Joueur 1 gagne !";
                else if(etat.le_gagnant == 1) document.getElementById('info_tour').innerHTML = "FIN ! Le Joueur 2 gagne !";
                else document.getElementById('info_tour').innerHTML = "FIN ! Match Nul !";
            } else {
                if(etat.joueur_qui_joue == 0) {
                    document.getElementById('info_tour').innerHTML = "C'est au Joueur 1 (Bas) de jouer";
                } else {
                    document.getElementById('info_tour').innerHTML = "C'est au Joueur 2 (Haut) de jouer";
                }
            }
        }
    </script>
</body>
</html>
