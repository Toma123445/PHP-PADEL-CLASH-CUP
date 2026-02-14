# Smash Cup 5x5 – Sistem de Management al Turneului de Padel

## Linkuri
- Domeniu online: https://padel-smash-cup-5x5.fwh.is  

---

## 1. Descriere generala

Smash Cup 5x5 este o aplicatie web in PHP pentru gestionarea unui turneu de padel pentru amatori, inspirat din formatul Victory Cup de minifotbal.

Fiecare echipa are 5 jucatori activi, iar fiecare meci se joaca in 5 seturi de dublu, astfel incat fiecare jucator participa de doua ori.  
Aplicatia gestioneaza inscrierile online, scorurile, handicapurile si clasamentele in mod automat.

---

## 2. Rolurile din sistem

| Rol | Descriere | Actiuni principale |
|------|------------|--------------------|
| Administrator | Gestioneaza competitiile, echipele si jucatorii | Adauga sau sterge echipe, valideaza inscrieri, gestioneaza fazele competitiei, actualizeaza scoruri |
| Capitan de echipa | Inscrie echipa si completeaza foaia de joc | Completeaza formularul online, adauga jucatori, raporteaza scorurile |
| Jucator | Vizualizeaza programul si rezultatele | Urmareste scorurile si clasamentul echipei |

---

## 3. Functionalitati principale

1. **Formular de inscriere online**  
   - Capitanii pot inscrie echipele direct din site.  
   - Se introduc numele echipei, jucatorii si diviziile lor.  
   - Administratorul valideaza ulterior echipa.

2. **Administrarea competitiei**  
   - Adaugare, modificare sau stergere de echipe si jucatori.  
   - Crearea automata a grupelor si etapelor saptamanale.

3. **Gestionarea meciurilor**  
   - Fiecare meci are 5 seturi de dublu.  
   - Sistemul calculeaza automat handicapul pentru fiecare set.  
   - Scorurile sunt introduse manual sau automat de administrator.

4. **Clasament dinamic**  
   - 1 punct pentru fiecare set castigat.  
   - Clasamentul se actualizeaza automat pe baza scorurilor.  
   - Criterii de departajare: diferenta de game-uri, meciuri directe, total game-uri castigate.

5. **Program si rezultate**  
   - Lista meciurilor programate si rezultatele deja jucate.

6. **Roluri si autentificare**  
   - Conturi separate pentru administrator si capitani.

---

## 4. Arhitectura aplicatiei

| Componenta | Descriere |
|-------------|-----------|
| Frontend (HTML, CSS, Bootstrap) | Interfata web pentru utilizatori |
| Backend (PHP 8) | Logica aplicatiei si conectarea la baza de date |
| Baza de date (MySQL) | Stocheaza echipe, jucatori, meciuri si clasamente |

---

### Structura proiectului

/php-padel/

│

├── index.php # Pagina principala

├── inscriere.php # Formular de inscriere echipa

├── echipe.php # Lista echipelor

├── meciuri.php # Lista meciurilor

├── clasament.php # Clasamentul actual

│

├── includes/

│ ├── db_connect.php # Conexiunea la baza de date

│ ├── functions.php # Functii logice

│

├── css/

│ └── style.css

│

└── sql/

└── schema.sql # Structura bazei de date


---

## 5. Descrierea bazei de date

| Tabel | Scop | Campuri principale |
|-------|------|--------------------|
| divizii | Nivelul jucatorilor | id_divizie, nume_divizie, valoare_banda |
| jucatori | Date despre jucatori | id_jucator, nume, prenume, id_echipa, id_divizie |
| echipe | Informatii despre echipe | id_echipa, nume_echipa, capitan, divizie_principala |
| competitii | Turnee inregistrate | id_competitie, nume, sezon |
| grupe | Fazele grupelor | id_grupa, id_competitie, nume |
| meciuri | Intalniri intre echipe | id_meci, id_echipa_a, id_echipa_b, data_meci, faza |
| seturi | Seturile dintr-un meci | id_set, numar_set, jucator_a1, jucator_b1, gameuri_a, gameuri_b |
| clasament | Clasamentul echipelor | id_clasament, id_echipa, puncte, gameuri_plus, gameuri_minus |

---

## 6. UML – Model relational (tabelar)

| Entitate | Atribute principale | Relatii |
|-----------|--------------------|----------|
| Divizie | id_divizie, nume_divizie, valoare_banda | 1-N cu Jucator |
| Jucator | id_jucator, nume, prenume, id_echipa, id_divizie | N-1 cu Echipa, N-1 cu Divizie, N-N cu Set |
| Echipa | id_echipa, nume_echipa, capitan, divizie_principala | 1-N cu Jucator, N-N cu Grupa, 1-N cu Meci |
| Competitie | id_competitie, nume, sezon, faza_curenta | 1-N cu Grupa, 1-N cu Meci, 1-N cu Clasament |
| Grupa | id_grupa, id_competitie, nume | 1-N cu Meci, N-N cu Echipa |
| Meci | id_meci, id_competitie, id_grupa, id_echipa_a, id_echipa_b | 1-N cu Set |
| Set | id_set, id_meci, numar_set, jucator_a1, jucator_a2, jucator_b1, jucator_b2 | N-1 cu Meci, N-N cu Jucator |
| Clasament | id_clasament, id_competitie, id_echipa, puncte | N-1 cu Echipa, N-1 cu Competitie |

---

## 7. Procesele principale

### Inscriere echipa
1. Capitanul completeaza formularul online (inscriere.php).  
2. Datele sunt salvate in baza de date.  
3. Administratorul valideaza echipa si o adauga in competitie.

### Desfasurarea meciurilor
1. Se stabilesc perechile pentru fiecare set.  
2. Sistemul calculeaza automat handicapurile.  
3. Se introduc scorurile.  
4. Clasamentul se actualizeaza automat.

### Clasament
- 1 punct per set castigat.  
- Departajare dupa diferenta de game-uri si meciuri directe.  
- Actualizare automata dupa fiecare meci.

---

## 8. Implementare

- Backend: PHP 8  
- Frontend: HTML5, CSS3, Bootstrap 5  
- Baza de date: MySQL  
- Mediu de lucru: XAMPP  
- Arhitectura: MVC simplificat (separare intre logica si interfata)  
- Scripturi:
  - `insert_team.php` – adauga echipa noua
  - `update_clasament.php` – actualizeaza clasamentul
  - `get_results.php` – afiseaza scorurile

---

## 9. Concluzie

Smash Cup 5x5 este o aplicatie PHP pentru administrarea unui turneu de padel, care automatizeaza procesul de inscriere, gestionarea meciurilor si calculul clasamentului.  
Aplicatia este scalabila si poate fi adaptata cu usurinta pentru alte sporturi precum tenis, minifotbal sau baschet 3x3.

