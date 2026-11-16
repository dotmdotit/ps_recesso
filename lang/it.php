<?php
/**
 * Testi del modulo (italiano, lingua di riferimento).
 * Segnaposto: {days}, {date}, {email}, {reference}, {n}
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

return [
    // pulsanti obbligatori (art. 54-bis)
    'btn_withdraw' => 'Recedere dal contratto qui',
    'btn_confirm' => 'Conferma recesso',

    'page_title' => 'Recesso dal contratto',
    'page_kicker' => 'Diritto di recesso',
    'meta_title' => 'Recesso dal contratto',
    'account_link' => 'Recesso dal contratto',
    'intro' => 'Puoi esercitare il diritto di recesso direttamente da questa pagina, entro {days} giorni dal giorno in cui ricevi i prodotti, senza dover indicare il motivo. Dopo la conferma riceverai via email una ricevuta con il contenuto della tua dichiarazione, la data e l’ora di invio.',
    'intro_short' => 'Hai {days} giorni dalla consegna per recedere dal contratto, senza indicare il motivo.',

    // elenco ordini
    'orders_title' => 'I tuoi ordini',
    'orders_empty' => 'Non hai ancora effettuato ordini.',
    'order_label' => 'Ordine {reference}',
    'order_placed' => 'Effettuato il {date}',
    'order_total' => 'Totale',
    'deadline_until' => 'Recesso possibile fino al {date}',
    'deadline_open' => 'Recesso possibile fino a {days} giorni dopo la consegna',
    'state_expired' => 'Termine di recesso scaduto il {date}',
    'state_excluded' => 'Recesso non disponibile per lo stato attuale dell’ordine',
    'state_done' => 'Recesso già esercitato per tutti i prodotti',
    'state_requested' => 'Dichiarazione di recesso inviata il {date}',

    // modulo pubblico
    'public_title' => 'Modulo di recesso',
    'public_statement' => 'Con la presente comunico il recesso dal contratto di vendita relativo all’ordine indicato qui sotto.',
    'public_login' => 'Hai un account? <a href="{url}">Accedi</a> per scegliere direttamente l’ordine dall’elenco dei tuoi acquisti.',
    'field_firstname' => 'Nome',
    'field_firstname_help' => 'Inserisci il nome usato per l’ordine.',
    'field_lastname' => 'Cognome',
    'field_lastname_help' => 'Inserisci il cognome usato per l’ordine.',
    'field_order' => 'Numero ordine',
    'field_order_help' => 'Usa il numero (riferimento) che trovi nell’email di conferma dell’ordine.',
    'field_email_receipt' => 'Email per la conferma di ricezione',
    'field_email_receipt_help' => 'Invieremo la conferma di ricezione a questo indirizzo.',
    'field_scope' => 'Da che cosa desideri recedere?',
    'scope_whole' => 'Recedere dall’intero ordine',
    'scope_partial' => 'Recedere da singoli prodotti',
    'scope_help' => 'Questi dati servono solo a identificare il contratto o la parte del contratto da cui desideri recedere. Non è necessario indicare un motivo di recesso.',
    'items_help' => 'Per un recesso parziale indica almeno il nome o il codice di ogni prodotto. La quantità aiuta a identificarlo meglio.',
    'item_name' => 'Nome prodotto',
    'item_reference' => 'Codice prodotto',
    'item_remove' => 'Rimuovi',
    'item_add' => '+ Aggiungi un altro prodotto',
    'field_info' => 'Informazioni aggiuntive sul recesso (facoltativo)',
    'field_info_help' => 'Usa questo campo solo per informazioni non coperte dai campi precedenti. Non è necessario indicare un motivo.',
    'err_firstname' => 'Inserisci il nome.',
    'err_lastname' => 'Inserisci il cognome.',
    'err_order' => 'Inserisci il numero dell’ordine.',
    'err_email' => 'Inserisci un indirizzo email valido.',
    'err_scope' => 'Indica se recedi dall’intero ordine o da singoli prodotti.',
    'err_items' => 'Indica almeno un prodotto (nome o codice) oppure scegli il recesso dall’intero ordine.',
    'err_rate' => 'Sono state inviate troppe dichiarazioni in poco tempo da questa connessione. Riprova più tardi o contattaci.',

    'form_title' => 'Dichiarazione di recesso',
    'form_statement' => 'Con la presente comunico il recesso dal contratto di vendita relativo all’ordine {reference} del {date}, per i prodotti indicati qui sotto.',
    'form_consumer' => 'Consumatore',
    'field_name' => 'Nome e cognome',
    'field_confirm_email' => 'La ricevuta verrà inviata a',
    'form_products' => 'Prodotti per cui eserciti il recesso',
    'form_products_help' => 'Sono già selezionati tutti i prodotti: togli la spunta o riduci la quantità per recedere solo da una parte dell’ordine.',
    'col_product' => 'Prodotto',
    'col_qty' => 'Quantità',
    'of_qty' => 'di {n}',
    'field_reason' => 'Motivo (facoltativo)',
    'field_reason_help' => 'Non sei tenuto a indicare il motivo del recesso.',
    'form_note' => 'Cliccando su “Conferma recesso” la dichiarazione viene inviata al venditore. I prodotti personalizzati o realizzati su misura sono esclusi dal diritto di recesso (art. 59 Codice del Consumo).',
    'form_deadline' => 'Puoi esercitare il recesso per questo ordine fino al {date}.',
    'back_to_orders' => 'Torna all’elenco ordini',
    'err_no_items' => 'Seleziona almeno un prodotto.',
    'err_qty' => 'Quantità non valida per uno dei prodotti.',
    'err_token' => 'La sessione è scaduta: ricarica la pagina e riprova.',
    'err_not_eligible' => 'Il recesso non è più disponibile per questo ordine.',
    'err_save' => 'Non è stato possibile registrare la dichiarazione. Riprova o contattaci.',

    // ricevuta
    'done_title' => 'Dichiarazione di recesso ricevuta',
    'done_text' => 'Abbiamo ricevuto la tua dichiarazione di recesso. Ti abbiamo inviato una ricevuta all’indirizzo {email}. Ti contatteremo con le istruzioni per la restituzione dei prodotti.',
    'receipt_number' => 'Numero richiesta',
    'receipt_datetime' => 'Data e ora di invio',
    'receipt_order' => 'Ordine',
    'receipt_name' => 'Nome',
    'receipt_email' => 'Email',
    'receipt_products' => 'Prodotti',
    'receipt_info' => 'Motivo / informazioni aggiuntive',
    'receipt_print' => 'Stampa ricevuta',
    'whole_order' => 'Intero ordine',

    // dettaglio ordine (hook)
    'box_title' => 'Diritto di recesso',
    'box_eligible' => 'Puoi recedere dal contratto per questo ordine fino al {date}.',
    'box_eligible_open' => 'Puoi recedere dal contratto per questo ordine fino a {days} giorni dopo la consegna.',
    'box_requests' => 'Dichiarazioni inviate',

    // stati
    'status_received' => 'Ricevuta',
    'status_processing' => 'In lavorazione',
    'status_completed' => 'Completata',
    'status_rejected' => 'Respinta',

    // email
    'mail_subject_customer' => 'Ricevuta della tua dichiarazione di recesso – ordine {reference}',
    'mail_subject_admin' => 'Nuovo recesso online – ordine {reference}',
    'order_message' => 'Recesso online n. {id} inviato il {date} dal cliente tramite la funzione “Recedere dal contratto qui”. Prodotti: {items}',
    'order_line' => '{reference} del {date}',
    'check_found' => 'Ordine trovato nel negozio. {state}.',
    'check_not_found' => 'ATTENZIONE: nessun ordine trovato con questo numero e questa email. Verifica manualmente la richiesta.',
];
