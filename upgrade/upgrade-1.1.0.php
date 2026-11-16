<?php
/**
 * 1.1.0: modulo di recesso pubblico (senza ricerca dell'ordine) e colore dei pulsanti configurabile.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_1_0($module)
{
    if (Configuration::get(Dotmrecesso::CFG_COLOR) === false) {
        Configuration::updateValue(Dotmrecesso::CFG_COLOR, '');
    }

    return true;
}
