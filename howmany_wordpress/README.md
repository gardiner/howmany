# HowMany for Wordpress


HowMany ist eine einfache Zugriffsstatistik für Wordpress. Es bietet:

- automatisches Tracking aller (Frontend-)Zugriffe
- übersichtliche Darstellung der Statistik, z.B. Anzahl der Aufrufe, verwendete Browser usw.
- rücksichtsvoller Umgang mit personenbezogenen Daten (siehe *Datenschutz*)


## Datenschutz

HowMany hat das Ziel, die Datenschutzinteressen der Besucher zu wahren. Daher werden

- **keinerlei personenbezogene Daten wie IP-Adresse o.ä. gespeichert**
- alle sensiblen Informationen wie der User Agent o.ä. vereinfacht und verallgemeinert
- zusammenhängende Aufrufe ("Besuche") über einen bewusst unscharfen Fingerprint verfolgt (siehe *Fingerprint*)
- keine Cookies zum Tracking verwendet


## Fingerprint

Um zusammenhängende Aufrufe ("Besuche") zu zählen, müssen Besucher einigermaßen verlässlich identifizierbar sein. Dies steht im Konflikt zum Interesse, keine personenbezogenen Daten zu speichern. Daher weist HowMany Besuchern einen unscharfen Fingerprint zu und fasst Aufrufe mit identischem Fingerprint als Besuche zusammen. Für den Fingerprint wird eine unumkehrbare Prüfsumme (CRC32) aus Teilen der IP-Adresse und des UserAgents der Besucher gebildet. Ziel ist, dass Besuche, die einen identischen Fingerprint haben, mit hoher Wahrscheinlichkeit dem gleichen Besucher zuzuordnen sind (jedoch bewusst nicht eindeutig), und dass umgekehrt unterschiedliche Besucher mit hoher Wahrscheinlichkeit unterschiedliche Fingerprints haben (jedoch bewusst nicht garantiert). So ist gewährleistet, dass sich aus dem Fingerprint keinerlei personenbezogenen Informationen rückschließen lassen. Statistisch enthält dieser Ansatz natürlich eine gewisse Unschärfe (z.B. werden manchmal zusammenhängende Aufrufe nicht als solche identifiziert, andere Aufrufe werden versehentlich dem falschen Besuch zugeordnet), die jedoch in der Auswertung nicht ins Gewicht fällt.


## Ideen

- Tagging von URLs oder Patterns erlauben, Statistik für Tags anzeigen
- Tracking von Downloads über spezielle Count-URLs, die dann weiterleiten
