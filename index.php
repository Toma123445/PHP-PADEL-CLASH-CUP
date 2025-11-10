<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PHP PADEL - Smash Cup 5x5</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

  <div class="container py-5">
    <h1 class="text-center mb-4">Proiect PHP – Smash Cup 5x5</h1>

    <section class="mb-5">
      <h3>Descrierea proiectului</h3>
      <p>
        Smash Cup 5x5 este o aplicatie web in PHP pentru managementul unui turneu de padel pentru amatori.
        Fiecare echipa are 5 jucatori activi, iar un meci se joaca in 5 seturi de dublu, astfel incat fiecare jucator participa de doua ori.
        Sistemul permite inscrierea echipelor online, administrarea meciurilor, calculul handicapurilor si actualizarea automata a clasamentului.
      </p>
    </section>

    <section class="mb-5">
      <h3>Roluri in sistem</h3>
      <ul>
        <li><strong>Administrator</strong> – gestioneaza competitiile, echipele si scorurile.</li>
        <li><strong>Capitan de echipa</strong> – inscrie echipa, adauga jucatorii si raporteaza rezultatele.</li>
        <li><strong>Jucator</strong> – vizualizeaza programul si clasamentele.</li>
      </ul>
    </section>

    <section class="mb-5">
      <h3>Functionalitati principale</h3>
      <ul>
        <li>Formular online de inscriere pentru echipe si jucatori.</li>
        <li>Calcul automat al handicapurilor pe baza diviziilor jucatorilor.</li>
        <li>Fiecare meci are 5 seturi, fiecare jucator joaca de doua ori.</li>
        <li>Clasamente actualizate in timp real.</li>
        <li>Panou de administrare pentru validari si introducerea rezultatelor.</li>
      </ul>
    </section>

    <section class="mb-5">
      <h3>Arhitectura aplicatiei</h3>
      <p>
        Frontend: HTML, CSS, Bootstrap<br>
        Backend: PHP 8<br>
        Baza de date: MySQL<br>
        Hosting: InfinityFree / Domeniu propriu
      </p>
    </section>

    <section class="mb-5 text-center">
      <h3>Schema bazei de date</h3>
      <p>Diagrama UML simplificata a bazei de date:</p>
      <img src="sql-padel.png" alt="Schema bazei de date PHP PADEL" class="img-fluid border rounded shadow-sm" style="max-width: 700px;">
    </section>

    <section class="mb-5">
      <h3>Descriere baza de date</h3>
      <ul>
        <li><strong>divizii</strong> – contine valorile pentru nivelul jucatorilor (1–4)</li>
        <li><strong>jucatori</strong> – stocheaza datele jucatorilor si divizia lor</li>
        <li><strong>echipe</strong> – contine informatii despre echipe si capitani</li>
        <li><strong>competitii</strong> – defineste turneele (ex. Smash Cup 2025)</li>
        <li><strong>grupe</strong> – gestioneaza faza grupelor</li>
        <li><strong>meciuri</strong> – retine intalnirile dintre echipe</li>
        <li><strong>seturi</strong> – cele 5 seturi jucate intr-un meci</li>
        <li><strong>clasament</strong> – calculeaza punctele si diferenta de game-uri</li>
      </ul>
    </section>

    <section class="text-center">
      <h3>Linkuri utile</h3>
      <p>
        Repository GitHub: <a href="https://github.com/FiloteToma/PHP-PADEl-clash-cup" target="_blank">github.com/FiloteToma/PHP-PADEL-CLASH-CUP</a><br>
        Domeniu online: <a href="https://padel-smash-cup-5x5.fwh.is" target="_blank">padel-smash-cup-5x5.fwh.is</a>
      </p>
    </section>
  </div>

</body>
</html>
