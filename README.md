<p align="center"><img src="logo.svg?v=2" width="96" alt="dotmrecesso"></p>

# dotmrecesso — Recesso online per PrestaShop

Modulo PrestaShop che aggiunge al negozio la funzione **«Recedere dal contratto qui»**: il consumatore esercita il diritto di recesso online e riceve subito una ricevuta via email. Il negozio riceve una notifica e trova le dichiarazioni nel back office.

Serve per l'**art. 54-bis del Codice del Consumo** (d.lgs. 209/2025, che recepisce la Direttiva UE 2023/2673): chi vende online deve offrire una funzione per recedere dal contratto con la stessa facilità con cui lo si conclude.

> *English summary: PrestaShop module that adds an online "withdraw from contract here" function (EU Directive 2023/2673, Italian Consumer Code art. 54-bis). Customers submit a withdrawal statement online and get an email receipt; the shop gets a notification and a back-office list. Front office and emails in Italian and English; back office in Italian.*

## Funzionalità

- **Pagina pubblica `/recesso`**, raggiungibile da chiunque:
  - **cliente registrato e loggato**: vede l'elenco dei suoi ordini con il termine di recesso di ciascuno, sceglie l'ordine e i prodotti (con quantità) e conferma;
  - **ospiti e visitatori**: compilano il modulo di recesso (nome, cognome, numero ordine, email, intero ordine o singoli prodotti, informazioni aggiuntive). Non serve trovare l'ordine: la dichiarazione viene **sempre** registrata e confermata. Se numero ordine ed email corrispondono a un ordine, la richiesta viene collegata all'ordine, altrimenti la notifica al negozio lo segnala.
- **Pulsante nell'area clienti** e **riquadro nel dettaglio ordine** (anche nel tracciamento ordini degli ospiti), con il termine di recesso e le dichiarazioni già inviate.
- **Ricevuta al cliente** via email con contenuto della dichiarazione, data e ora di invio, e pagina di conferma stampabile.
- **Notifica al negozio** via email (uno o più indirizzi), con «rispondi a» impostato sul cliente.
- **Back office** in *Ordini → Recessi online*: elenco delle dichiarazioni con filtri, dettaglio e stato (ricevuta, in lavorazione, completata, respinta). Nella pagina dell'ordine un riquadro mostra lo stato del recesso; sull'ordine viene aggiunto un messaggio privato.
- **Calcolo del termine**: dalla data in cui l'ordine passa allo stato «Consegnato» più i giorni di recesso; se l'ordine è spedito ma non risulta consegnato, dalla spedizione più un margine di trasporto configurabile. Prima della spedizione il recesso è sempre possibile. I prodotti già oggetto di recesso non vengono riproposti.
- **Anti-abuso** del modulo pubblico: token, campo trappola nascosto e limite di 5 dichiarazioni per IP all'ora.
- **Aspetto neutro**, adatto a qualsiasi tema: colori grigi, un solo colore configurabile per pulsanti ed email, variabili CSS ridefinibili dal tema.

## Requisiti

- PrestaShop **1.7.7** o successivo
- PHP 7.2 o successivo
- URL amichevoli consigliati (per l'indirizzo `/recesso`); senza, la pagina resta raggiungibile su `index.php?fc=module&module=dotmrecesso&controller=recesso`

## Installazione

1. Scarica la repo (*Code → Download ZIP*, oppure una release).
2. La cartella del modulo **deve chiamarsi `dotmrecesso`**. Lo ZIP di GitHub contiene la cartella `ps_recesso-main`: rinominala e ricrea lo ZIP:
   ```sh
   unzip ps_recesso-main.zip && mv ps_recesso-main dotmrecesso
   zip -r dotmrecesso.zip dotmrecesso -x '*.git*'
   ```
3. In back office: *Moduli → Gestione moduli → Carica un modulo* e scegli `dotmrecesso.zip`.
   In alternativa copia la cartella in `modules/dotmrecesso/` sul server e installa il modulo dal Gestore moduli.
4. Apri *Configura* e controlla le impostazioni (vedi sotto).
5. Svuota la cache in *Parametri avanzati → Prestazioni*, così la rotta `/recesso` viene registrata.

L'installazione crea la tabella `ps_dotmrecesso_request`, la voce *Ordini → Recessi online* e aggancia il modulo a `moduleRoutes`, `displayCustomerAccount`, `displayOrderDetail`, `displayAdminOrderSide`, `actionFrontControllerSetMedia`.

## Configurazione

| Impostazione | Predefinito | Note |
|---|---|---|
| Giorni per il recesso | 14 | Dalla consegna. Non si può scendere sotto il minimo di legge (14). |
| Margine di trasporto (giorni) | 10 | Usato se l'ordine è spedito ma non risulta «Consegnato». |
| Email per le notifiche | email del negozio | Uno o più indirizzi separati da virgola. |
| Colore pulsanti | vuoto (`#2b2b2b`) | Formato `#rrggbb`, ad esempio il colore principale del tema. Usato anche nelle email. |
| Stati esclusi | Annullato, Rimborsato, Errore di pagamento | Gli ordini in questi stati non mostrano il pulsante. |

Il termine si basa sullo stato «Consegnato» di PrestaShop (`PS_OS_DELIVERED`) e sugli stati marcati come «spedito»: perché il calcolo sia corretto, gli ordini devono passare per questi stati.

## Dopo l'installazione

- **Collega la pagina** `https://tuonegozio.it/recesso` nel footer (ad esempio con il modulo *Link list*) e nelle pagine CMS sul diritto di recesso.
- **Citala nelle condizioni di vendita**, tra le informazioni precontrattuali (art. 49, c. 1, lett. h Codice del Consumo).
- Fai una prova completa: dichiarazione da cliente loggato, dal modulo pubblico e verifica che ricevuta e notifica arrivino.

## Personalizzazione

Il modulo non contiene nulla di specifico per un negozio: tutto passa dalla configurazione o dagli override del tema.

- **Template**: copia i file da `views/templates/` in `themes/<tuo-tema>/modules/dotmrecesso/views/templates/` e modificali lì.
- **Stile**: il CSS usa solo variabili `--dmr-*` (`--dmr-accent`, `--dmr-ink`, `--dmr-muted`, `--dmr-line`, `--dmr-soft`, `--dmr-card`, `--dmr-font-display`, …) definite su `.dmr` e `.dmr-box`. Ridefiniscile nel CSS del tema, ad esempio:
  ```css
  .dmr, .dmr-box { --dmr-font-display: "Playfair Display", serif; --dmr-soft: #faf7f2; }
  ```
- **Testi**: sono in `lang/it.php` (riferimento) e `lang/en.php`. Per un'altra lingua aggiungi `lang/<iso>.php` con le stesse chiavi (basta tradurre quelle che vuoi: le mancanti arrivano dall'inglese). Le lingue senza file usano l'inglese.
- **Email**: `mails/<iso>/dotmrecesso_customer.{html,txt}` (ricevuta) e `dotmrecesso_admin.{html,txt}` (notifica). Le lingue senza cartella usano l'inglese. Il colore è nella variabile `{dmr_color}`.

## Struttura

```
dotmrecesso.php                         classe del modulo: installazione, configurazione, regole, email, hook
classes/DotmRecessoRequest.php          dichiarazione di recesso (tabella ps_dotmrecesso_request)
controllers/front/recesso.php           pagina pubblica /recesso
controllers/admin/AdminDotmRecessoController.php   Ordini → Recessi online
lang/                                   testi (it, en)
mails/                                  ricevuta e notifica (it, en)
views/templates/{front,hook,admin}/     template Smarty
views/css/front.css, views/js/front.js  stile e righe prodotto del modulo pubblico
upgrade/                                script di aggiornamento tra versioni
```

## Aggiornamento e disinstallazione

- Per aggiornare, sostituisci i file del modulo e avvia l'aggiornamento dal Gestore moduli: gli script in `upgrade/` aggiornano database e configurazione. Dopo modifiche ai template svuota la cache.
- **Attenzione: disinstallare il modulo cancella la tabella con tutte le dichiarazioni di recesso ricevute.** Se devi conservarle (è consigliabile, come prova delle comunicazioni ricevute), esporta prima la tabella `ps_dotmrecesso_request`. Per reinstallare senza perdere dati usa *Reimposta* solo dopo un backup.

## Note legali

Il modulo fornisce lo strumento tecnico. Verifica con il tuo consulente che testi, termini e condizioni di vendita siano adeguati al tuo caso (prodotti esclusi dal recesso, termini diversi, ecc.).

## Licenza

Rilasciato con licenza [MIT](LICENSE).

## Autore

Sviluppato da [dotm.it](https://dotm.it).
