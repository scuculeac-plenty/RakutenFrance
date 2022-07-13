# Rakuten.fr Setup Doku: 

Schließe dich an den Rakuten Marktplatz an mit über 8.500 professionellen Händlern an und präsentiere deinen eigenen personalisierten Shop den 10 Millionen Club R Mitgliedern. Gestalte dabei deinen eigenen Shop, der ausschließlich euren Produkten gewidmet ist und bietet euren Rakuten Kunden dabei 3 % Rakuten- Punkte für den gesamten Katalog, der vollständig von Rakuten finanziert wird.

## Assistent : 
Das Rakuten.fr setup erfolgt nach neustem plentymarkets Standard mittels Assistent.
Nach der Installation des Plugins im Pluginset werden autumatisch 4 Assistenten hinzugefügt:

- Rakuten.fr settings. / Rakuten.fr Einstellungen
- Rakuten.fr Catalogue creation. / Rakuten.fr Katalogerstellung
- Rakuten.fr Catalogue export information./ Rakuten.fr Katalog Exportinformationen
- Rakuten.fr and plentymarkets shipping matching

Die Assistenten können unter **Setup » Assistanten>>Plugins » {{Name des Pluginsets}}** gefunden werden.

### Rakuten.fr Einstellungen Assistent : 
Dieser Assistenten-Setup beinhaltet 4 Schritte zum Setup der grundlegenden Plugin Konfiguration.


#### Schritt 1: Zugangsdaten 
In diesem Schritt musst Du deinen Benutzer*innen-Namen und Deinen [Token](https://ibb.co/N9s5Bzv) von [Rakuten.fr](https://ibb.co/HK8DwXy) einfügen.


#### Schritt 2: Bestellungen & Profile
##### Bestell-Einstellungen: 
Hier musst Du ein Schlüsselwort angeben, welches zur Identifikation am Beginn des Stornierungsgrunds in der Bestellnotiz/Order Note verwenden werden.
Weitere Informationen hierzu findest Du in der Beschreibung der Ereignisaktion **“Rakuten.fr: Cancel order items”**.

_Beispiel für CANCEL_REASON:_ <br>
Um einen Stornierungsgrund anzugeben, musst Du eine Order Note wie die Folgende in der Bestellung anlegen: <br> 
CANCEL_REASON: _My test cancelation reason_. 
Wenn nun die Ereignisaktion der Bestellstornierung ausgelöst wird, wird alles nach dem Schlüsselwort _CANCEL_REASON_ an Rakuten.fr als Stornierungsgrund übermittelt. In diesem Fall wäre das: _My test cancelation reason_. 

##### Profil Einstellungen: FULLADVERT
An dieser Stelle musst Du Deine Rakuten.fr Profil ID angeben. Diese ID ist die Voraussetzung für den Bestandsabgleich und, falls Dieses verwendest, für EAN-Matching für das Artikelmanagement. Deine Rakuten.fr Profil ID findest Du auf der Website von Rakuten unter: **Inventaire » Envoyer mes fichiers de stock » Soumettre un nouveau fichier (?)**.
Verwende an dieser Stelle bitte **WS_FULLADVERT** als Wert.


##### Profile settings: PRICEQUANTITY
An dieser Stelle musst Du Deine Rakuten fr. Profil ID angeben. Diese ID ist die Voraussetzung für den Bestandsabgleich, sowie Preis-Aktualisierungen und -Synchronisierungen. Deine Rakuten.fr Profil ID findest Du auf der Website von Rakuten unter: **Inventaire » Envoyer mes fichiers de stock » Soumettre un nouveau fichier (?)**.
Verwende an dieser Stelle bitte **PRICEQUANTITY** als Wert.


#### Schritt 3: Account & Jobs
##### Account Einstellungen. 
Bitte wähle in den Account Einstellungen einen Account unter dessen Namen _Order Notes/ Bestellnotizen_ während des Order Managements/ Bestellmanagements angelegt werden sollen.
_Order Notes / Bestellnotizen_ werden dann angelegt, werden dann angelegt, wenn ein Fehler bzw. unvollständige Daten vorliegen, wie z.B.: das Fehlen der SKU.


##### Cron-Job Einstellungen.
In diesem Bereich kannst Du einzelne Funktionen des Plugins ein- bzw. ausschalten.

| Funktion | Beschreibung |
| ------ | ------ |
| **Synchronize marketplace orders** (Every 15 minutes) <br> -> Synchronisation der Marketplace Bestellungen (alle 15 Minuten) | Diese Funktion ist verantwortlich für den Auftragsimport von Rakuten.fr zu plentymarkets. | <br>
| **Synchronize stock with the marketplace** (Every 15 minutes) <br> -> Synchronisation des Bestands mit dem Marketplace. (alle 15 Minuten) | Diese Funktion synchronisiert den Bestand zwischen plentymarkets und Rakuten.fr. <br> Im Export werden Artikel enthalten sein, auf die folgende Kriterien zutreffen: <br> -Bestandsänderungen innerhalb der letzten 30 Minuten. <br> -Eine SKU mit Rakuten.fr Referrer ist vorhanden. | <br>
| **Synchronize marketplace order statuses.** (Every 20 minutes) <br> -> Synchronisation der Marketplace Bestell-Stati. (alle 20 Minuten) | Diese Funktion ist verantwortlich für die Synchronisation von Bestellungen des Status “Cancelled by Customer” oder “Ongoing Claim”. | <br>
| **Synchronize feed to the marketplace.** (Every 1 hour) <br > -> Synchronisation des Feeds zum Marktplace. (stündlich)|Diese Funktion ist zuständig für die Synchronisierung des aktiven Kataloges zu Rakuten.fr via Alias. | <br>
| **Synchronize EAN matching file.** (Every 1 hour) <br> -> Synchronisation des EAN Matching Files. (stündlich) | Diese Funktion ist zuständig für Synchronisierung des aktiven Kataloges zu Rakuten.fr via _EAN-MATCHING_. |


#### Schritt 4 Client / Kund*innen.
in diesem Schritt musst Du eine*n Kund*in auswählen. Dies ist für die Handhabung des Inventars und Warenhaus-Managements notwendig.


## Erstellen des Rakuten.fr Katalogs.
In diesem Assistenten kannst Du eine neue Vorlage für den Katalog wählen, indem Du ein spezifisches Alias von Rakuten.fr auswählst. Hierfür musst Du lediglich einen Namen für den Katalog hinzufügen und unter “Type” eines der Alias von Rakuten.fr auswählen.

Nach der Fertigstellung dieses Assistenten wird eine neue Katalogen Vorlage für das ausgewählte Alias erstellt. Dieses kannst Du unter **Daten -> Kataloge** bzw. **Data->Catalogues**, falls Du Dein System auf Englisch verwendest, einsehen. <br>
_**Hinweis:** Die Einrichtung des Kataloges wird in dem Bereich **Rakuten-fr Katalog Export information** und dessen Unterkategorien erklärt._


### Rakuten.fr Katalog Export Information. 
In diesem Assistenten ist keine Auswahl notwendig, da er für die Sammlung der Reporte der einzelnen Kataloge zuständig ist.

Nach erfolgreichem Export der Kataloge zu Rakuten.fr, wird hier der Export gespeichert, was das Überprüfen des Status des Exports ermöglicht und einen Einblick darin verschafft, was optimal funktioniert hat und wo es noch besser laufen könnte.


### Versandprofil-Abgleich / Shipping Matching. 
In diesem Teil des Assistenten verbindest Du Deine plentymarkets Versandprofile mit den Rakuten.fr-Zusteller*innen.  
Diese Angaben sind obligatorisch für die Verwendung der Ereignisaktion: **Rakuten.fr: Versandbestätigung übermitteln.** 
<br>
Auf Seiten von Rakuten.fr wird die Sendung entsprechend dem durch den Assistenten verbundenen Versandprofil als versendet markiert.
<br>
Rakuten.fr stellt hierbei folgende Versanddienstleister zur Verfügung, welche Du [hier](https://postimg.cc/YLwwZBHG) findest.


## Artikel-Management.
Um die Bestellungen Deiner Artikel zwischen plentymarkets und Rakuten.fr zu verwalten, musst Du unterschiedliche Einrichtungen in den Varianten, Warenhäusern und Verkaufspreis Profilen vornehmen.
<br>
Voraussetzungen für die Inklusion der Artikel in die Kataloge (sowohl via Alias wie EAN-Matching).
<br>
Die Varianten müssen über den Referrer _Rakuten.fr_ in der Märkte Liste verfügen.
Um aussagen zu können, in welchem Katalog sich der Artikel befindet, solltest Du über **Catalog : Rakuten.fr** die entsprechende Vorlage/ das entsprechende Template auswählen.

_Hinweis:_ Den Verkaufspreis kannst Du unter **Einrichtung » Artikel » Verkaufspreise** einstellen. <br>
Die Warenhäuser kannst Du unter **Einrichtung » Waren » Lager** einstellen.

### Wichtig
_**Hinweis**: Wenn die Variation aus irgendeinem Grund nicht den Wert hat, der in der Katalogvorlage zugewiesen und als erforderlich markiert ist, werden die Variationen ebenfalls SKIPPED. 

Alle Fehlermeldungen für fehlgeschlagene Exporte oder Validierungsfehler des Katalogs von Rakuten.fr werden hier angezeigt: **Einrichtung / Märkte / Rakuten.fr / Katalog-Fehler**.

Beide Arten von Katalogen (EAN und Alias) werden stündlich synchronisiert.


### Voraussetzungen für die Artikel-Bestands-Synchronisation.
Die Variante muss den Referrer _Rakuten.fr_ in der Marktplatz-Liste der Variante aufweisen:
Die Variante muss über die Rakuten.fr SKU verfügen. Diese wird automatisch für den neu exportierte Varianten per Plugin angelegt.
<br>
Solltest Du bereits bestehende Angebote von Rakuten.fr mit der Variante matchen und den Bestandsabgleich verwenden, ist es notwendig die SKU manuell hinzuzufügen. Verwende dafür [diesen Wert](https://postimg.cc/pyYDSQQy) von Rakuten.fr als SKU.
<br>
_**Hinweis:** Die Bestandssynchronisation erfolgt im 15 Minutentakt and aktualisiert Varianten, die innerhalb der letzten 30 Minuten verändert wurden.
Solltest Du beispielsweise den Bestand einer Variante vom Vortag aktualisieren wollen, musst Du zunächst den Bestand aktualisieren. In Folge wird der Timestamp aktualisiert und die Variante wird bei der nächsten Aktualisierung mit einbezogen._


## Bestell-Management.
### Bestellimport.
Der Bestellimport arbeitet im 15 Minutentakt und importiert die Bestellungen von Rakuten.fr zu plentymarkets. Bestellungen mit dem Status “REQUESTED ou REMINDED”  werden hierbei importiert. 
Für eine korrekte Zuordnung von Varianten zu Bestellungen, sucht das Plugin nach Varianten entsprechend der SKU oder der VariantenID, falls Du die VariantenID als SKU Identifier oder Rakuten.fr verwendest. 
Sollte die Variante fehlen oder nicht gefunden werden, wird die Bestellung mit fehlender Variante erstellt. Über die fehlende Variante wirst Du in Folge in der Bestellnotiz/ Order Note informiert.

### Ereignisaktionen.
Das Plugin erstellt 4 zusätzliche Ereignisaktionen in Deinem plentymarkets System, um Dir bei der Verwaltung der Bestellungen von plentymarkets in Rakuten.fr zu helfen. 
- Rakuten.fr: Bestellung annehmen
- Rakuten.fr: Auftrag ablehnen
- Rakuten.fr: Versandbestätigung übermitteln
- Rakuten.fr: Auftragspositionen stornieren


#### Rakuten.fr: Bestellung annehmen (accept order items)
Diese Ereignisaktion hilft Dir bei der Annahme von Artikelbestellungen von Rakuten.fr und leitet den Bezahlprozess ein, damit Du Dich mit dem Versandprozess befassen kannst.
Nachdem diese Ereignisaktion ausgelöst wurde, werden die Email und Telefonnummer der Käufer*innen bereitgestellt und die Versandkosten werden der Bestellung hinzugefügt.

Um nur einen von mehreren bestellten Artikeln anzunehmen, musst Du die Bestellung in zwei Versandbestellungen aufteilen. Weitere Informationen dazu findest Du hier:
https://knowledge.plentymarkets.com/slp/orders/managing-orders#300


#### Rakuten.fr: Auftrag ablehnen (Refuse Order items)
Mit dieser Ereignisaktion lehnst Du Aufträge ab. Stelle sicher, dass Du diese Ereignisaktion zum richtigen Zeitpunkt aktivierst.
Du kannst Bestellungen ablehnen, solange sich diese noch im Status “REQUESTED ou REMINDED” befinden. Solltest Du die Bestellung in einem anderen Status ablehnen, erhältst Du eine Fehlermeldung als Order Note.

Um die Bestellung eines oder mehrer Artikel abzulehnen, musst Du die Bestellung in zwei Bestellungen aufteilen. Weitere Informationen dazu findest Du unter folgendem Link:
https://knowledge.plentymarkets.com/slp/orders/managing-orders#300


#### Rakuten.fr: Auftragspositionen stornieren (Cancel order)
Mit dieser Ereignisaktion kannst Du Bestellungen stornieren, falls etwas schiefgelaufen ist und die Käufer*innen bereits eine Zahlung getätigt haben.


#### Rakuten.fr: Versandbestätigung übermitteln (Shipping information)
Diese Ereignisaktion lässt Dich Versandinformationen der Bestellung von plentymarkets zu Rakuten.fr übertragen. Bitte stelle daher bitte sicher, dass diese Ereignisaktion erst ausgelöst wird, nachdem bereits eine Tracking-Nummer für die Bestellung erstellt wurde.


## **FAQ:**
- Hast Du bereits Artikel in Rakuten.fr und möchtest diese synchronisieren? Informationen hierzu findest Du im Punkt **Artikelmanagement » Voraussetzungen für die Artikel-Bestands-Synchronisation » 2**.

- Einige der folgenden **Fehlermeldungen** können in den jeweiligen Bereichen ausgegeben werden: <br>
  -Ereignisaktionen zum Akzeptieren oder Ablehnen von Bestellungen: <br>
   _The parameter itemid is mandatory_ <br>
   _The parameter itemid is invalid_ <br>

  -Ereignisaktionen für Versandinformationen: <br> 
   _Article xxxx : numéro incorrect_: Falsche Artikel Nummer <br>
   _Article xxxx : a déjà été expédié_: Der Artikel wurde bereits verschickt <br>
   _Article xxxx : statut incorrect : L’enregistrement de votre numéro de suivi n’est possible que si le statut de votre article est “confirmé”._: Die Sendungsnummer kann nicht übermittelt werden solange noch kein Versand stattfand.<br>

  -Ereignisaktion zur Stornierung: <br>
   _This item cannot be cancelled_: Der Artikel kann nicht storniert werden. <br>
   _Your message cannot exceed 400 characters._: Es können nicht mehr als 400 Zeichen als Nachricht eingegeben werden 

